from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ..db import get_session
from ..models import Loan, LoanSession, Tool, User
from ..schemas.loan import (
    LoanExtendRequest,
    LoanReadExpanded,
    LoanReturnRequest,
    LoanSessionRead,
)
from ..security import require_admin
from ..ws.broker import broker

router = APIRouter(prefix="/loans", tags=["loans"])


async def _expand(db: AsyncSession, ln: Loan) -> LoanReadExpanded:
    tool = (await db.execute(select(Tool).where(Tool.id == ln.tool_id))).scalar_one_or_none()
    user = (await db.execute(select(User).where(User.id == ln.user_id))).scalar_one_or_none()
    return LoanReadExpanded.model_validate(
        {
            **{c.name: getattr(ln, c.name) for c in ln.__table__.columns},
            "tool": tool,
            "user": user,
        }
    )


@router.get("", response_model=list[LoanReadExpanded])
async def list_loans(
    status_: str | None = Query("all", alias="status"),
    user_id: int | None = None,
    tool_id: int | None = None,
    limit: int = 200,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    stmt = select(Loan)
    if status_ == "open":
        stmt = stmt.where(Loan.returned_at.is_(None))
    elif status_ == "closed":
        stmt = stmt.where(Loan.returned_at.is_not(None))
    if user_id:
        stmt = stmt.where(Loan.user_id == user_id)
    if tool_id:
        stmt = stmt.where(Loan.tool_id == tool_id)
    stmt = stmt.order_by(Loan.loaned_at.desc()).limit(limit)
    rows = (await db.execute(stmt)).scalars().all()
    return [await _expand(db, ln) for ln in rows]


@router.get("/active", response_model=list[LoanReadExpanded])
async def active_loans(
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    rows = (
        await db.execute(
            select(Loan).where(Loan.returned_at.is_(None)).order_by(Loan.loaned_at.desc())
        )
    ).scalars().all()
    return [await _expand(db, ln) for ln in rows]


@router.post("/{loan_id}/return", response_model=LoanReadExpanded)
async def return_loan(
    loan_id: int,
    body: LoanReturnRequest,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    ln = (await db.execute(select(Loan).where(Loan.id == loan_id))).scalar_one_or_none()
    if not ln:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    if ln.returned_at is not None:
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Préstamo ya cerrado")
    now = datetime.now(timezone.utc)
    ln.returned_at = now
    ln.return_reason = body.reason or "admin_manual"
    tool = (await db.execute(select(Tool).where(Tool.id == ln.tool_id))).scalar_one()
    if tool.status == "on_loan":
        tool.status = "in_stock"
    await db.commit()
    await broker.publish(
        "admin",
        {
            "type": "loan.returned",
            "loan_id": ln.id,
            "tool": {"id": tool.id, "rfid": tool.rfid, "brand": tool.brand, "model": tool.model},
            "user_id": ln.user_id,
            "returned_at": now.isoformat(),
            "return_reason": ln.return_reason,
        },
    )
    return await _expand(db, ln)


@router.post("/{loan_id}/extend", response_model=LoanReadExpanded)
async def extend_loan(
    loan_id: int,
    body: LoanExtendRequest,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    ln = (await db.execute(select(Loan).where(Loan.id == loan_id))).scalar_one_or_none()
    if not ln:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    ln.due_at = body.due_at
    await db.commit()
    return await _expand(db, ln)


# --- Sesiones ---

sessions_router = APIRouter(prefix="/sessions", tags=["sessions"])


@sessions_router.get("/active", response_model=list[LoanSessionRead])
async def active_sessions(
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    from sqlalchemy.orm import selectinload
    return (
        await db.execute(
            select(LoanSession)
            .options(selectinload(LoanSession.user), selectinload(LoanSession.station))
            .where(LoanSession.closed_at.is_(None))
            .order_by(LoanSession.opened_at.desc())
        )
    ).scalars().all()


@sessions_router.post("/{session_id}/close", response_model=LoanSessionRead)
async def close_session(
    session_id: int,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    s = (
        await db.execute(select(LoanSession).where(LoanSession.id == session_id))
    ).scalar_one_or_none()
    if not s:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    if s.closed_at is not None:
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Sesión ya cerrada")
    now = datetime.now(timezone.utc)
    s.closed_at = now
    s.close_reason = "admin_manual"
    await db.commit()
    await broker.publish(
        "admin",
        {
            "type": "session.closed",
            "session_id": s.id,
            "user_id": s.user_id,
            "reason": "admin_manual",
            "at": now.isoformat(),
        },
    )
    return s
