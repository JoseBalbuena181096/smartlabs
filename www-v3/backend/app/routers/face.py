"""Endpoints de reconocimiento facial.

Dos audiencias:
- **Admin (JWT)**: registra/elimina rostros desde la UI y consulta estado.
- **face-service (X-Service-Token)**: empuja embeddings desde la cámara y
  pide identificación.

El face-service NO toca la DB ni MQTT directamente. Vive aquí toda la
lógica de "un rostro coincide → abrir sesión de préstamo en la estación X
→ enviar `found` + nombre por MQTT al ESP32 + emitir evento WS al admin".

Esto permite que el flujo facial sea idéntico al de RFID (mismo command,
mismo evento `session.opened`, mismo broadcast). El frontend del kiosko
no necesita saber por qué método entró el usuario.
"""
from __future__ import annotations

import logging
from datetime import datetime, timezone

from fastapi import APIRouter, Depends, Header, HTTPException, status
from sqlalchemy import delete, select, text
from sqlalchemy.ext.asyncio import AsyncSession

from ..db import get_session
from ..models import FaceEmbedding, LoanSession, Loan, Station, Tool, User
from ..mqtt.publisher import publisher
from ..schemas.face import (
    CommitRequest,
    CommitResponse,
    EmbeddingPushRequest,
    EmbeddingPushResponse,
    FaceUserStatus,
    IdentifyMatch,
    IdentifyRequest,
    RegisterStartRequest,
    RegisterStartResponse,
    RegisteredListResponse,
    VALID_POSITIONS,
)
from ..security import require_admin
from ..services.face_session import REQUIRED_POSITIONS, registry
from ..settings import settings
from ..ws.broker import broker

log = logging.getLogger(__name__)

router = APIRouter(prefix="/face", tags=["face"])


# ──────────────────────────────────────────────────────────────────────
# Auth para llamadas service-to-service desde face-service
# ──────────────────────────────────────────────────────────────────────

def require_service_token(
    x_service_token: str | None = Header(default=None, alias="X-Service-Token"),
) -> None:
    if not x_service_token or x_service_token != settings.face_service_token:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="X-Service-Token inválido",
        )


def _now() -> datetime:
    return datetime.now(timezone.utc)


def _pgvector_literal(vec: list[float]) -> str:
    """Serializa un vector para `CAST(:v AS vector)` en SQL crudo."""
    return "[" + ",".join(f"{x:.6f}" for x in vec) + "]"


# ──────────────────────────────────────────────────────────────────────
# Registro guiado (admin)
# ──────────────────────────────────────────────────────────────────────

@router.post("/register/start", response_model=RegisterStartResponse)
async def register_start(
    body: RegisterStartRequest,
    db: AsyncSession = Depends(get_session),
    _admin: User = Depends(require_admin),
):
    user = (
        await db.execute(select(User).where(User.id == body.user_id))
    ).scalar_one_or_none()
    if user is None:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Usuario no existe")

    registry.start(body.user_id)
    return RegisterStartResponse(
        user_id=body.user_id,
        positions_required=list(REQUIRED_POSITIONS),
    )


@router.post("/register/embedding", response_model=EmbeddingPushResponse)
async def register_embedding(
    body: EmbeddingPushRequest,
    _svc: None = Depends(require_service_token),
):
    """face-service empuja un embedding cuando detecta una posición nueva."""
    if body.position not in VALID_POSITIONS:
        raise HTTPException(status.HTTP_400_BAD_REQUEST, f"position inválida: {body.position}")

    try:
        s = registry.push(body.user_id, body.position, body.embedding, body.det_score)
    except KeyError:
        raise HTTPException(
            status.HTTP_400_BAD_REQUEST,
            "No hay sesión de captura activa para este usuario",
        )

    # Notificar a admins del progreso para que la UI refresque sin polling
    # (poll también sigue funcionando, esto es solo para feedback inmediato).
    await broker.publish(
        "admin",
        {
            "type": "face.captured",
            "user_id": body.user_id,
            "position": body.position,
            "captured": s.captured_positions(),
            "progress_percent": s.progress_percent(),
            "at": _now().isoformat(),
        },
    )
    return EmbeddingPushResponse(
        user_id=body.user_id,
        captured=s.captured_positions(),
        pending=s.pending_positions(),
        progress_percent=s.progress_percent(),
        ready_to_commit=s.ready_to_commit(),
    )


@router.get("/register/poll/{user_id}", response_model=EmbeddingPushResponse)
async def register_poll(
    user_id: int,
    _admin: User = Depends(require_admin),
):
    s = registry.get(user_id)
    if s is None:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "No hay sesión de captura activa")
    return EmbeddingPushResponse(
        user_id=user_id,
        captured=s.captured_positions(),
        pending=s.pending_positions(),
        progress_percent=s.progress_percent(),
        ready_to_commit=s.ready_to_commit(),
    )


@router.get("/register/state/{user_id}", response_model=EmbeddingPushResponse)
async def register_state(
    user_id: int,
    _svc: None = Depends(require_service_token),
):
    """Variante con X-Service-Token para que el face-service consulte el
    estado de captura sin necesitar JWT de admin."""
    s = registry.get(user_id)
    if s is None:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "No hay sesión de captura activa")
    return EmbeddingPushResponse(
        user_id=user_id,
        captured=s.captured_positions(),
        pending=s.pending_positions(),
        progress_percent=s.progress_percent(),
        ready_to_commit=s.ready_to_commit(),
    )


@router.post("/register/commit", response_model=CommitResponse)
async def register_commit(
    body: CommitRequest,
    db: AsyncSession = Depends(get_session),
    _admin: User = Depends(require_admin),
):
    s = registry.get(body.user_id)
    if s is None:
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "No hay sesión de captura activa")
    if not s.can_commit():
        raise HTTPException(
            status.HTTP_400_BAD_REQUEST,
            f"Mínimo 3 posiciones. Capturadas: {len(s.by_position)}",
        )

    # Re-registro limpio: borra embeddings previos del user y persiste los nuevos.
    await db.execute(delete(FaceEmbedding).where(FaceEmbedding.user_id == body.user_id))
    for position, cap in s.by_position.items():
        db.add(
            FaceEmbedding(
                user_id=body.user_id,
                position=position,
                embedding=cap.embedding,
                det_score=cap.det_score,
            )
        )
    await db.commit()
    registry.commit_drain(body.user_id)

    positions = list(s.by_position.keys())
    await broker.publish(
        "admin",
        {
            "type": "face.registered",
            "user_id": body.user_id,
            "positions": positions,
            "at": _now().isoformat(),
        },
    )
    return CommitResponse(
        user_id=body.user_id,
        vectors_count=len(positions),
        positions=positions,
    )


@router.post("/register/cancel")
async def register_cancel(
    body: RegisterStartRequest,
    _admin: User = Depends(require_admin),
):
    registry.cancel(body.user_id)
    return {"status": "cancelled", "user_id": body.user_id}


# ──────────────────────────────────────────────────────────────────────
# Identificación (face-service)
# ──────────────────────────────────────────────────────────────────────

@router.post("/identify", response_model=IdentifyMatch)
async def identify(
    body: IdentifyRequest,
    db: AsyncSession = Depends(get_session),
    _svc: None = Depends(require_service_token),
):
    """KNN sobre face_embeddings con pgvector. Si supera el umbral y se da
    `station_sn`, abre `LoanSession` y publica MQTT igual que un RFID."""

    # Si la estación tiene face_enabled=false, abortamos sin tocar DB ni
    # MQTT ni emitir eventos al kiosko. El face-service interpreta 423 como
    # "esta estación no acepta cara, salta el tick".
    if body.station_sn:
        st = (
            await db.execute(
                select(Station).where(Station.serial_number == body.station_sn)
            )
        ).scalar_one_or_none()
        if st is not None and not st.face_enabled:
            raise HTTPException(
                status.HTTP_423_LOCKED,
                f"face_enabled=false en {body.station_sn}",
            )

    threshold = body.threshold if body.threshold is not None else settings.face_similarity_threshold
    vec_lit = _pgvector_literal(body.embedding)

    # `embedding <=> $vec` es la distancia coseno (0=idénticos, 2=opuestos).
    # similitud = 1 - distancia. Buscamos el más cercano (ORDER BY <=>) y
    # aplicamos threshold sobre la similitud.
    stmt = text(
        """
        SELECT fe.user_id,
               1 - (fe.embedding <=> CAST(:vec AS vector)) AS sim,
               u.full_name, u.rfid, u.active
          FROM face_embeddings fe
          JOIN users u ON u.id = fe.user_id
         WHERE u.active = TRUE
         ORDER BY fe.embedding <=> CAST(:vec AS vector)
         LIMIT 1
        """
    )
    row = (await db.execute(stmt, {"vec": vec_lit})).first()
    recognized = row is not None and float(row.sim) >= threshold

    # face.live: el kiosko escucha esto y dibuja el bbox sobre la imagen.
    # Verde + nombre si recognized, rojo + "no identificado" si no.
    if body.station_sn:
        live_event = {
            "type": "face.live",
            "station_sn": body.station_sn,
            "recognized": recognized,
            "bbox": body.bbox,
            "frame_w": body.frame_w,
            "frame_h": body.frame_h,
            "score": float(row.sim) if row else 0.0,
            "at": _now().isoformat(),
        }
        if recognized:
            live_event["user"] = {
                "id": int(row.user_id),
                "name": row.full_name,
                "rfid": row.rfid,
            }
        await broker.publish_many(
            ["admin", f"station:{body.station_sn}"],
            live_event,
        )

    if not recognized:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "No hay match facial")

    user_id = int(row.user_id)
    full_name = row.full_name
    rfid = row.rfid
    score = float(row.sim)

    # Sin station_sn: solo identifica (modo "test" del admin).
    if not body.station_sn:
        return IdentifyMatch(
            user_id=user_id,
            full_name=full_name,
            rfid=rfid,
            score=score,
        )

    # Con station_sn: replica el flujo de loan_queryu para abrir sesión.
    sn = body.station_sn
    station = (
        await db.execute(select(Station).where(Station.serial_number == sn))
    ).scalar_one_or_none()
    if station is None:
        # No auto-creamos por face: queremos que la station ya esté registrada
        # vía MQTT (handler de status). Si no existe, no abrimos nada.
        raise HTTPException(status.HTTP_404_NOT_FOUND, f"Station {sn} no registrada")

    active = (
        await db.execute(
            select(LoanSession).where(
                LoanSession.station_id == station.id,
                LoanSession.closed_at.is_(None),
            )
        )
    ).scalar_one_or_none()

    # Heurística de cercanía: bbox ancho > face_close_bbox_px = usuario
    # está intencionalmente frente a la cámara (no de paso). En ese caso,
    # si hay sesión activa y es del mismo usuario, la CIERRA.
    bbox_width = 0.0
    if body.bbox and len(body.bbox) >= 4:
        bbox_width = float(body.bbox[2] - body.bbox[0])
    is_close = bbox_width >= settings.face_close_bbox_px

    if active is not None:
        if active.user_id == user_id and is_close:
            # Mismo usuario CERCA → cerrar sesión (alternativa al RFID).
            now = _now()
            active.closed_at = now
            active.close_reason = "face"
            await db.commit()

            await publisher.send_command(sn, "unload")
            await broker.publish_many(
                ["admin", f"station:{sn}"],
                {
                    "type": "session.closed",
                    "session_id": active.id,
                    "station_sn": sn,
                    "user_id": user_id,
                    "reason": "face",
                    "at": now.isoformat(),
                },
            )
            log.info("👋 face-close: %s cerró sesión por cara cercana en %s (bbox_w=%.0fpx)", full_name, sn, bbox_width)
            return IdentifyMatch(
                user_id=user_id,
                full_name=full_name,
                rfid=rfid,
                score=score,
                session_opened=False,
                session_closed=True,
                session_id=active.id,
            )

        # Sesión activa pero (a) usuario lejos, o (b) usuario distinto.
        # No tocamos nada — devolvemos el id por si el cliente lo necesita.
        return IdentifyMatch(
            user_id=user_id,
            full_name=full_name,
            rfid=rfid,
            score=score,
            session_opened=False,
            session_id=active.id,
        )

    new_session = LoanSession(station_id=station.id, user_id=user_id)
    db.add(new_session)
    await db.flush()

    active_loans_rows = (
        await db.execute(
            select(Loan, Tool)
            .join(Tool, Tool.id == Loan.tool_id)
            .where(Loan.user_id == user_id, Loan.returned_at.is_(None))
            .order_by(Loan.loaned_at.desc())
        )
    ).all()
    active_loans_payload = [
        {
            "loan_id": ln.id,
            "tool": {"id": tl.id, "rfid": tl.rfid, "brand": tl.brand, "model": tl.model},
            "loaned_at": ln.loaned_at.isoformat() if ln.loaned_at else None,
            "due_at": ln.due_at.isoformat() if ln.due_at else None,
        }
        for ln, tl in active_loans_rows
    ]

    await db.commit()

    # Mismo contrato MQTT que el flujo RFID — el ESP32 se entera por estos
    # dos topics y muestra el nombre y "found" en pantalla.
    await publisher.send_user_name(sn, full_name)
    await publisher.send_command(sn, "found")
    await broker.publish_many(
        ["admin", f"station:{sn}"],
        {
            "type": "session.opened",
            "session_id": new_session.id,
            "station_sn": sn,
            "user": {"id": user_id, "name": full_name, "rfid": rfid},
            "active_loans": active_loans_payload,
            "opened_at": _now().isoformat(),
            "method": "face",
        },
    )

    return IdentifyMatch(
        user_id=user_id,
        full_name=full_name,
        rfid=rfid,
        score=score,
        session_opened=True,
        session_id=new_session.id,
    )


# ──────────────────────────────────────────────────────────────────────
# Consultas / borrado (admin)
# ──────────────────────────────────────────────────────────────────────

@router.get("/registered", response_model=RegisteredListResponse)
async def registered(
    db: AsyncSession = Depends(get_session),
    _admin: User = Depends(require_admin),
):
    rows = (
        await db.execute(select(FaceEmbedding.user_id).distinct())
    ).scalars().all()
    user_ids = sorted(set(int(r) for r in rows))
    return RegisteredListResponse(total=len(user_ids), user_ids=user_ids)


@router.get("/users", response_model=list[FaceUserStatus])
async def list_users_with_face_status(
    db: AsyncSession = Depends(get_session),
    _admin: User = Depends(require_admin),
):
    """Lista los usuarios activos junto con su estado de registro facial.
    Usado por la UI para los indicadores verde/gris en el panel izquierdo."""
    users = (
        await db.execute(
            select(User).where(User.active.is_(True)).order_by(User.full_name)
        )
    ).scalars().all()

    rows = (
        await db.execute(
            select(FaceEmbedding.user_id, FaceEmbedding.position, FaceEmbedding.created_at)
        )
    ).all()
    by_user: dict[int, list[tuple[str, datetime]]] = {}
    for uid, pos, created in rows:
        by_user.setdefault(int(uid), []).append((pos, created))

    out: list[FaceUserStatus] = []
    for u in users:
        entries = by_user.get(u.id, [])
        positions = [p for p, _ in entries]
        last_at = max((c for _, c in entries), default=None)
        out.append(
            FaceUserStatus(
                user_id=u.id,
                full_name=u.full_name,
                email=u.email,
                rfid=u.rfid,
                role=u.role,
                active=u.active,
                has_face=len(positions) > 0,
                positions=positions,
                last_captured_at=last_at,
            )
        )
    return out


@router.delete("/{user_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_user_face(
    user_id: int,
    db: AsyncSession = Depends(get_session),
    _admin: User = Depends(require_admin),
):
    res = await db.execute(
        delete(FaceEmbedding).where(FaceEmbedding.user_id == user_id)
    )
    await db.commit()
    if res.rowcount == 0:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Sin embeddings para este usuario")
    await broker.publish(
        "admin",
        {"type": "face.deleted", "user_id": user_id, "at": _now().isoformat()},
    )
