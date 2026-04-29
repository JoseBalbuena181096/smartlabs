from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ..db import get_session
from ..models import Station
from ..schemas.station import StationCreate, StationRead, StationUpdate
from ..security import require_admin

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
    s = Station(serial_number=body.serial_number.strip(), alias=body.alias)
    db.add(s)
    try:
        await db.commit()
    except Exception:
        await db.rollback()
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Estación duplicada")
    await db.refresh(s)
    return s


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
    for k, v in body.model_dump(exclude_unset=True).items():
        setattr(s, k, v)
    await db.commit()
    await db.refresh(s)
    return s
