from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from ..db import get_session
from ..models import Loan, Tool, User
from ..schemas.loan import LoanReadExpanded
from ..schemas.user import UserCreate, UserRead, UserUpdate
from ..security import hash_password, require_admin

router = APIRouter(prefix="/users", tags=["users"])


@router.get("", response_model=list[UserRead])
async def list_users(
    q: str | None = Query(None),
    area_id: int | None = None,
    limit: int = 100,
    offset: int = 0,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    stmt = select(User).where(User.active.is_(True))
    if area_id:
        stmt = stmt.where(User.area_id == area_id)
    if q:
        like = f"%{q}%"
        stmt = stmt.where(
            or_(
                User.full_name.ilike(like),
                User.email.ilike(like),
                User.payroll_number.ilike(like),
                User.rfid.ilike(like),
            )
        )
    stmt = stmt.order_by(User.full_name).limit(limit).offset(offset)
    rows = (await db.execute(stmt)).scalars().all()
    return rows


@router.post("", response_model=UserRead, status_code=status.HTTP_201_CREATED)
async def create_user(
    body: UserCreate,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    user = User(
        full_name=body.full_name.strip(),
        email=body.email.lower(),
        payroll_number=body.payroll_number,
        area_id=body.area_id,
        rfid=body.rfid.strip(),
        role=body.role,
        active=body.active,
        password_hash=hash_password(body.password) if body.password else None,
    )
    db.add(user)
    try:
        await db.commit()
    except Exception as exc:
        await db.rollback()
        raise HTTPException(status.HTTP_400_BAD_REQUEST, f"No se pudo crear: {exc.__class__.__name__}")
    await db.refresh(user)
    return user


@router.get("/{user_id}", response_model=UserRead)
async def get_user(
    user_id: int,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    user = (await db.execute(select(User).where(User.id == user_id))).scalar_one_or_none()
    if not user:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    return user


@router.patch("/{user_id}", response_model=UserRead)
async def update_user(
    user_id: int,
    body: UserUpdate,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    user = (await db.execute(select(User).where(User.id == user_id))).scalar_one_or_none()
    if not user:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    data = body.model_dump(exclude_unset=True)
    if "password" in data:
        pw = data.pop("password")
        user.password_hash = hash_password(pw) if pw else None
    for k, v in data.items():
        setattr(user, k, v)
    await db.commit()
    await db.refresh(user)
    return user


@router.delete("/{user_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_user(
    user_id: int,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    user = (await db.execute(select(User).where(User.id == user_id))).scalar_one_or_none()
    if not user:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    user.active = False
    await db.commit()


@router.get("/{user_id}/loans", response_model=list[LoanReadExpanded])
async def user_loans(
    user_id: int,
    only_active: bool = True,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    stmt = select(Loan).where(Loan.user_id == user_id)
    if only_active:
        stmt = stmt.where(Loan.returned_at.is_(None))
    stmt = stmt.order_by(Loan.loaned_at.desc())
    loans = (await db.execute(stmt)).scalars().all()
    out: list[LoanReadExpanded] = []
    for ln in loans:
        tool = (await db.execute(select(Tool).where(Tool.id == ln.tool_id))).scalar_one_or_none()
        user = (await db.execute(select(User).where(User.id == ln.user_id))).scalar_one_or_none()
        out.append(
            LoanReadExpanded.model_validate(
                {
                    **{c.name: getattr(ln, c.name) for c in ln.__table__.columns},
                    "tool": tool,
                    "user": user,
                }
            )
        )
    return out
