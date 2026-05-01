"""
Handlers MQTT que reemplazan al flutter-api de Node.

Contrato firmware (mode_prestamo.cpp), inalterado:

  ESP32 publica:
    {SN}/loan_queryu  payload=UID    (UID escaneado, sin sesión local activa)
    {SN}/loan_querye  payload=UID    (UID escaneado, con sesión local activa;
                                      puede ser herramienta O credencial del
                                      usuario para cerrar)
    {SN}/status       online|offline (retained, QoS 1; LWT publica offline)

  Backend publica (responde):
    {SN}/command      found|nofound|unload|prestado|devuelto|refused|nologin
    {SN}/user_name    nombre del usuario para mostrar en pantalla

  Timeout firmware (180s sin scan): re-publica el UID de la sesión a
  loan_queryu como señal de timeout. Backend responde `unload`.

Reglas de negocio:
- loan_queryu con usuario válido: si NO había sesión abierta para esa
  estación → la abre (responde `found` + user_name). Si YA había sesión y
  el UID es el mismo (timeout firmware) → la cierra (`unload`,
  close_reason='timeout').
- loan_querye: tres casos. (1) UID == credencial del usuario en sesión →
  cierra sesión (`unload`, close_reason='manual'). (2) UID == credencial
  de OTRO usuario → `refused`. (3) UID == tag de herramienta → préstamo o
  devolución según estado (`prestado` / `devuelto`).
- Si llega loan_querye sin sesión activa en la estación → `nologin`.
- Si la estación no existe en `stations` aún → la auto-crea (status =
  online via el handler de status). Esto permite onboarding sin admin
  manual al conectar el ESP32 por primera vez.

Cuando el UID escaneado NO es ni usuario ni herramienta registrada,
emitimos un evento `tag.unknown` por WebSocket al canal `capture` para que
los formularios admin de "nuevo usuario" / "nueva herramienta" puedan
auto-rellenar el RFID.
"""

from __future__ import annotations

import logging
from datetime import datetime, timedelta, timezone

from sqlalchemy import select, update
from sqlalchemy.ext.asyncio import AsyncSession

from ..db import SessionLocal
from ..models import Loan, LoanSession, Station, Tool, User, InventoryRun, InventoryScan
from ..settings import settings
from ..ws.broker import broker
from .publisher import publisher

log = logging.getLogger(__name__)


# ------------------------------------------------------------------
# Utilidades
# ------------------------------------------------------------------

async def _get_or_create_station(db: AsyncSession, sn: str) -> Station:
    """Auto-crea la station si no existe, y MARCA ONLINE si llega un mensaje
    de su parte. Cualquier scan/mensaje del ESP32 prueba que está vivo —
    aunque el broker tenga retained `offline`, eso era de un blip pasado."""
    res = await db.execute(select(Station).where(Station.serial_number == sn))
    st = res.scalar_one_or_none()
    now = _now()
    became_online = False
    if st is None:
        st = Station(serial_number=sn, alias=None, online=True, last_seen=now)
        db.add(st)
        became_online = True
        await db.flush()
    else:
        if not st.online:
            became_online = True
        st.online = True
        st.last_seen = now
    if became_online:
        # Notificar a admins/kiosko al instante
        await broker.publish_many(
            ["admin", f"station:{sn}"],
            {"type": "station.online", "sn": sn, "at": now.isoformat()},
        )
    return st


def _now() -> datetime:
    return datetime.now(timezone.utc)


async def _emit(channel: str, event: dict) -> None:
    await broker.publish(channel, event)


async def _emit_admin_and_station(sn: str, event: dict) -> None:
    await broker.publish_many(["admin", f"station:{sn}"], event)


async def _open_inventory_run(db: AsyncSession) -> InventoryRun | None:
    res = await db.execute(
        select(InventoryRun).where(InventoryRun.finished_at.is_(None))
    )
    return res.scalar_one_or_none()


async def _open_session_for_station(
    db: AsyncSession, station_id: int
) -> LoanSession | None:
    res = await db.execute(
        select(LoanSession).where(
            LoanSession.station_id == station_id,
            LoanSession.closed_at.is_(None),
        )
    )
    return res.scalar_one_or_none()


# ------------------------------------------------------------------
# Topics in
# ------------------------------------------------------------------

async def handle_status(sn: str, payload: str) -> None:
    """Topic: {SN}/status  payload: online|offline (retained)."""
    online = payload.strip().lower() == "online"
    async with SessionLocal() as db:
        station = await _get_or_create_station(db, sn)
        station.online = online
        station.last_seen = _now()
        await db.commit()
    log.info("📡 %s -> %s", sn, "online" if online else "offline")
    await _emit_admin_and_station(
        sn,
        {
            "type": "station.online" if online else "station.offline",
            "sn": sn,
            "at": _now().isoformat(),
        },
    )


async def handle_loan_queryu(sn: str, uid: str) -> None:
    """Topic: {SN}/loan_queryu — usuario escaneó tag SIN sesión local activa."""
    if uid.startswith("APP:"):
        return  # back-compat con cliente viejo

    async with SessionLocal() as db:
        station = await _get_or_create_station(db, sn)

        # ¿hay run de inventario abierta? capturamos el scan al run y
        # respondemos nologin para que el ESP32 muestre "modo inventario".
        run = await _open_inventory_run(db)

        # Buscar usuario por RFID
        user = (
            await db.execute(select(User).where(User.rfid == uid, User.active.is_(True)))
        ).scalar_one_or_none()

        if run is not None:
            # En modo inventario, todo scan se trata como tag de inventario
            await _record_inventory_scan(db, run, sn, station.id, uid)
            await db.commit()
            await publisher.send_command(sn, "nologin")
            return

        if user is None:
            # ¿Será una herramienta? Si lo es, avisamos al canal capture
            # como "ya registrado" para que el admin no la asigne dos veces.
            tool_match = (
                await db.execute(select(Tool).where(Tool.rfid == uid))
            ).scalar_one_or_none()
            if tool_match is not None:
                await db.commit()
                await publisher.send_command(sn, "nofound")
                await broker.publish(
                    "capture",
                    {
                        "type": "tag.known",
                        "rfid": uid,
                        "station_sn": sn,
                        "entity": "tool",
                        "id": tool_match.id,
                        "label": f"{tool_match.brand or ''} {tool_match.model or ''}".strip() or tool_match.rfid,
                        "active": tool_match.active,
                        "at": _now().isoformat(),
                    },
                )
                return

            # Tag desconocido. Emitir evento para captura desde forms admin.
            await db.commit()
            await publisher.send_command(sn, "nofound")
            await broker.publish(
                "capture",
                {"type": "tag.unknown", "rfid": uid, "station_sn": sn, "at": _now().isoformat()},
            )
            return

        # Aquí hay user válido — además del flujo normal, avisamos al canal
        # capture (admin con modal abierto) que la tarjeta ya está asignada.
        await broker.publish(
            "capture",
            {
                "type": "tag.known",
                "rfid": uid,
                "station_sn": sn,
                "entity": "user",
                "id": user.id,
                "label": user.full_name,
                "active": user.active,
                "at": _now().isoformat(),
            },
        )

        # ¿Ya había sesión abierta en esta estación?
        active = await _open_session_for_station(db, station.id)

        if active is not None and active.user_id == user.id:
            # Mismo usuario re-publicando (timeout firmware) → cerrar.
            await _close_session(db, active, "timeout")
            await db.commit()
            await publisher.send_command(sn, "unload")
            await _emit_admin_and_station(
                sn,
                {
                    "type": "session.closed",
                    "session_id": active.id,
                    "station_sn": sn,
                    "user_id": user.id,
                    "reason": "timeout",
                    "at": _now().isoformat(),
                },
            )
            return

        if active is not None and active.user_id != user.id:
            # Otra credencial mientras hay sesión abierta de alguien más.
            await db.commit()
            await publisher.send_command(sn, "refused")
            return

        # Caso normal: abrir sesión nueva.
        new_session = LoanSession(station_id=station.id, user_id=user.id)
        db.add(new_session)
        await db.flush()

        # Cargar préstamos activos previos del usuario para que el kiosko los
        # muestre desde el primer instante (sesión recién abierta = lista no
        # debe arrancar vacía si el usuario tiene equipos ya prestados).
        active_loans_rows = (
            await db.execute(
                select(Loan, Tool)
                .join(Tool, Tool.id == Loan.tool_id)
                .where(Loan.user_id == user.id, Loan.returned_at.is_(None))
                .order_by(Loan.loaned_at.desc())
            )
        ).all()
        active_loans_payload = [
            {
                "loan_id": ln.id,
                "tool": {
                    "id": tl.id,
                    "rfid": tl.rfid,
                    "brand": tl.brand,
                    "model": tl.model,
                },
                "loaned_at": ln.loaned_at.isoformat() if ln.loaned_at else None,
                "due_at": ln.due_at.isoformat() if ln.due_at else None,
            }
            for ln, tl in active_loans_rows
        ]

        await db.commit()

        await publisher.send_user_name(sn, user.full_name)
        await publisher.send_command(sn, "found")
        await _emit_admin_and_station(
            sn,
            {
                "type": "session.opened",
                "session_id": new_session.id,
                "station_sn": sn,
                "user": {"id": user.id, "name": user.full_name, "rfid": user.rfid},
                "active_loans": active_loans_payload,
                "opened_at": _now().isoformat(),
            },
        )


async def handle_loan_querye(sn: str, uid: str) -> None:
    """Topic: {SN}/loan_querye — scan CON sesión local activa.

    Puede ser:
      - credencial del usuario en sesión → cierre manual
      - credencial de OTRO usuario       → refused
      - tag de herramienta               → préstamo / devolución
    """
    if uid.startswith("APP:"):
        return

    async with SessionLocal() as db:
        station = await _get_or_create_station(db, sn)

        run = await _open_inventory_run(db)
        if run is not None:
            await _record_inventory_scan(db, run, sn, station.id, uid)
            await db.commit()
            await publisher.send_command(sn, "nologin")
            return

        active = await _open_session_for_station(db, station.id)
        if active is None:
            # firmware piensa que hay sesión pero el backend no → resync
            await db.commit()
            await publisher.send_command(sn, "nologin")
            return

        # ¿Es la propia credencial del usuario en sesión?
        owner = (
            await db.execute(select(User).where(User.id == active.user_id))
        ).scalar_one()

        if uid == owner.rfid:
            await _close_session(db, active, "manual")
            await db.commit()
            await publisher.send_command(sn, "unload")
            await _emit_admin_and_station(
                sn,
                {
                    "type": "session.closed",
                    "session_id": active.id,
                    "station_sn": sn,
                    "user_id": owner.id,
                    "reason": "manual",
                    "at": _now().isoformat(),
                },
            )
            return

        # ¿Es credencial de otro usuario distinto?
        other_user = (
            await db.execute(select(User).where(User.rfid == uid, User.active.is_(True)))
        ).scalar_one_or_none()
        if other_user is not None:
            await db.commit()
            await publisher.send_command(sn, "refused")
            return

        # ¿Es herramienta?
        tool = (
            await db.execute(select(Tool).where(Tool.rfid == uid, Tool.active.is_(True)))
        ).scalar_one_or_none()

        if tool is None:
            # Tag desconocido durante sesión. Emitir captura por si admin
            # quiere registrarlo como herramienta nueva.
            await db.commit()
            await publisher.send_command(sn, "nofound")
            await broker.publish(
                "capture",
                {"type": "tag.unknown", "rfid": uid, "station_sn": sn, "at": _now().isoformat()},
            )
            return

        # Tool conocida — además del flujo normal de préstamo/devolución,
        # avisamos al canal capture si un admin tenía el modal abierto.
        await broker.publish(
            "capture",
            {
                "type": "tag.known",
                "rfid": uid,
                "station_sn": sn,
                "entity": "tool",
                "id": tool.id,
                "label": f"{tool.brand or ''} {tool.model or ''}".strip() or tool.rfid,
                "active": tool.active,
                "at": _now().isoformat(),
            },
        )

        # ¿Préstamo o devolución?
        existing = (
            await db.execute(
                select(Loan).where(
                    Loan.tool_id == tool.id,
                    Loan.returned_at.is_(None),
                )
            )
        ).scalar_one_or_none()

        if existing is not None and existing.user_id == owner.id:
            # devolución del propio usuario
            existing.returned_at = _now()
            existing.return_session_id = active.id
            existing.return_reason = "scan"
            tool.status = "in_stock"
            await db.commit()
            await publisher.send_command(sn, "devuelto")
            await _emit_admin_and_station(
                sn,
                {
                    "type": "loan.returned",
                    "loan_id": existing.id,
                    "tool": {
                        "id": tool.id,
                        "rfid": tool.rfid,
                        "brand": tool.brand,
                        "model": tool.model,
                    },
                    "user_id": owner.id,
                    "returned_at": _now().isoformat(),
                    "return_reason": "scan",
                },
            )
            return

        if existing is not None and existing.user_id != owner.id:
            # herramienta prestada a OTRO usuario; no se puede devolver así
            await db.commit()
            await publisher.send_command(sn, "refused")
            return

        # crear préstamo nuevo
        due_at = _now() + timedelta(hours=settings.loan_due_hours)
        loan = Loan(
            session_id=active.id,
            user_id=owner.id,
            tool_id=tool.id,
            due_at=due_at,
        )
        tool.status = "on_loan"
        db.add(loan)
        await db.flush()
        await db.commit()

        await publisher.send_command(sn, "prestado")
        await _emit_admin_and_station(
            sn,
            {
                "type": "loan.created",
                "loan_id": loan.id,
                "tool": {
                    "id": tool.id,
                    "rfid": tool.rfid,
                    "brand": tool.brand,
                    "model": tool.model,
                },
                "user_id": owner.id,
                "due_at": due_at.isoformat(),
                "loaned_at": _now().isoformat(),
            },
        )


# ------------------------------------------------------------------
# Helpers
# ------------------------------------------------------------------

async def _close_session(
    db: AsyncSession, session: LoanSession, reason: str
) -> None:
    session.closed_at = _now()
    session.close_reason = reason


async def _record_inventory_scan(
    db: AsyncSession,
    run: InventoryRun,
    sn: str,
    station_id: int,
    rfid: str,
) -> None:
    tool = (
        await db.execute(select(Tool).where(Tool.rfid == rfid))
    ).scalar_one_or_none()
    # idempotente: UNIQUE (run_id, tool_rfid) lo garantiza
    existing = (
        await db.execute(
            select(InventoryScan).where(
                InventoryScan.run_id == run.id,
                InventoryScan.tool_rfid == rfid,
            )
        )
    ).scalar_one_or_none()
    if existing is not None:
        return
    scan = InventoryScan(
        run_id=run.id,
        tool_rfid=rfid,
        tool_id=tool.id if tool else None,
        station_id=station_id,
    )
    db.add(scan)
    await broker.publish_many(
        ["admin", f"station:{sn}"],
        {
            "type": "inventory.scan",
            "run_id": run.id,
            "tool_rfid": rfid,
            "tool_id": tool.id if tool else None,
            "station_sn": sn,
            "at": _now().isoformat(),
        },
    )


# ------------------------------------------------------------------
# Router de mensajes (entry point del client MQTT)
# ------------------------------------------------------------------

async def dispatch(topic: str, payload: bytes) -> None:
    try:
        msg = payload.decode("utf-8", errors="replace").strip()
    except Exception:
        return

    parts = topic.split("/")
    if len(parts) != 2:
        return
    sn, query = parts

    log.info("📨 %s -> %s", topic, msg)

    if query == "loan_queryu":
        await handle_loan_queryu(sn, msg)
    elif query == "loan_querye":
        await handle_loan_querye(sn, msg)
    elif query == "status":
        await handle_status(sn, msg)
    else:
        log.debug("topic ignorado: %s", topic)
