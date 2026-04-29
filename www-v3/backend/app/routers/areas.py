from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ..db import get_session
from ..models import Area
from ..schemas.area import AreaCreate, AreaRead
from ..security import require_admin

router = APIRouter(prefix="/areas", tags=["areas"])


@router.get("", response_model=list[AreaRead])
async def list_areas(
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    return (await db.execute(select(Area).order_by(Area.name))).scalars().all()


@router.post("", response_model=AreaRead, status_code=status.HTTP_201_CREATED)
async def create_area(
    body: AreaCreate,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    a = Area(name=body.name.strip())
    db.add(a)
    try:
        await db.commit()
    except Exception:
        await db.rollback()
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Área duplicada")
    await db.refresh(a)
    return a


@router.delete("/{area_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_area(
    area_id: int,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    a = (await db.execute(select(Area).where(Area.id == area_id))).scalar_one_or_none()
    if not a:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    await db.delete(a)
    await db.commit()
