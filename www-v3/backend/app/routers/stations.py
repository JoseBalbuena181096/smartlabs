from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ..db import get_session
from ..models import Loan, LoanSession, Station, Tool, User
from ..schemas.station import StationCreate, StationRead, StationUpdate
from ..security import require_admin
from ..ws.broker import broker

router = APIRouter(prefix="/stations", tags=["stations"])


@router.get("", response_model=list[StationRead])
async def list_stations(
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    return (await db.execute(select(Station).order_by(Station.serial_number))).scalars().all()


@router.post("", response_model=StationRead, status_code=status.HTTP_201_CREATED)
async def create_station(
    body: StationCreate,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    s = Station(
        serial_number=body.serial_number.strip(),
        alias=body.alias,
        face_enabled=body.face_enabled,
    )
    db.add(s)
    try:
        await db.commit()
    except Exception:
        await db.rollback()
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Estación duplicada")
    await db.refresh(s)
    return s


@router.get("/by-sn/{sn}/state")
async def station_state(sn: str, db: AsyncSession = Depends(get_session)):
    """Endpoint **público** (sin auth) para que el kiosko en `/station/{sn}`
    pueda hidratar su estado al cargar. Devuelve si la estación está online
    y la sesión activa actual (si la hay) con sus préstamos abiertos.

    Sin esto, al recargar el browser el kiosko se queda mostrando "Pasa tu
    credencial" aunque haya alguien en sesión — porque la WS solo entrega
    eventos futuros, no el estado actual."""
    st = (await db.execute(select(Station).where(Station.serial_number == sn))).scalar_one_or_none()
    if st is None:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Station no registrada")

    sess = (
        await db.execute(
            select(LoanSession).where(
                LoanSession.station_id == st.id,
                LoanSession.closed_at.is_(None),
            )
        )
    ).scalar_one_or_none()

    session_payload = None
    if sess is not None:
        u = (await db.execute(select(User).where(User.id == sess.user_id))).scalar_one()
        active_loans = (
            await db.execute(
                select(Loan, Tool)
                .join(Tool, Tool.id == Loan.tool_id)
                .where(Loan.user_id == u.id, Loan.returned_at.is_(None))
                .order_by(Loan.loaned_at.desc())
            )
        ).all()
        session_payload = {
            "session_id": sess.id,
            "opened_at": sess.opened_at.isoformat(),
            "user": {"id": u.id, "name": u.full_name, "rfid": u.rfid},
            "active_loans": [
                {
                    "loan_id": ln.id,
                    "tool": {"id": tl.id, "rfid": tl.rfid, "brand": tl.brand, "model": tl.model},
                    "loaned_at": ln.loaned_at.isoformat() if ln.loaned_at else None,
                    "due_at": ln.due_at.isoformat() if ln.due_at else None,
                }
                for ln, tl in active_loans
            ],
        }

    return {
        "sn": st.serial_number,
        "alias": st.alias,
        "online": st.online,
        "last_seen": st.last_seen.isoformat() if st.last_seen else None,
        "face_enabled": st.face_enabled,
        "session": session_payload,
    }


@router.patch("/{station_id}", response_model=StationRead)
async def update_station(
    station_id: int,
    body: StationUpdate,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    s = (await db.execute(select(Station).where(Station.id == station_id))).scalar_one_or_none()
    if not s:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    data = body.model_dump(exclude_unset=True)
    face_changed = "face_enabled" in data and data["face_enabled"] != s.face_enabled
    for k, v in data.items():
        setattr(s, k, v)
    await db.commit()
    await db.refresh(s)
    if face_changed:
        # Notifica al kiosko (que escucha WS de su station) y al face-service
        # (que polea estado cada 10s) — la UI puede ocultar el panel sin
        # esperar al refresh.
        await broker.publish_many(
            ["admin", f"station:{s.serial_number}"],
            {
                "type": "station.face_changed",
                "sn": s.serial_number,
                "face_enabled": s.face_enabled,
                "at": datetime.now(timezone.utc).isoformat(),
            },
        )
    return s
