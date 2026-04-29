from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from ..db import get_session
from ..models import Loan, Tool, User
from ..schemas.tool import ToolCreate, ToolRead, ToolUpdate
from ..security import require_admin
from ..ws.broker import broker

router = APIRouter(prefix="/tools", tags=["tools"])


@router.get("", response_model=list[ToolRead])
async def list_tools(
    q: str | None = Query(None),
    status_: str | None = Query(None, alias="status"),
    limit: int = 100,
    offset: int = 0,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    stmt = select(Tool).where(Tool.active.is_(True))
    if status_:
        stmt = stmt.where(Tool.status == status_)
    if q:
        like = f"%{q}%"
        stmt = stmt.where(
            or_(
                Tool.brand.ilike(like),
                Tool.model.ilike(like),
                Tool.description.ilike(like),
                Tool.rfid.ilike(like),
            )
        )
    stmt = stmt.order_by(Tool.brand, Tool.model).limit(limit).offset(offset)
    return (await db.execute(stmt)).scalars().all()


@router.post("", response_model=ToolRead, status_code=status.HTTP_201_CREATED)
async def create_tool(
    body: ToolCreate,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    tool = Tool(**body.model_dump())
    db.add(tool)
    try:
        await db.commit()
    except Exception as exc:
        await db.rollback()
        raise HTTPException(status.HTTP_400_BAD_REQUEST, f"No se pudo crear: {exc.__class__.__name__}")
    await db.refresh(tool)
    return tool


@router.get("/{tool_id}", response_model=ToolRead)
async def get_tool(
    tool_id: int,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    tool = (await db.execute(select(Tool).where(Tool.id == tool_id))).scalar_one_or_none()
    if not tool:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    return tool


@router.patch("/{tool_id}", response_model=ToolRead)
async def update_tool(
    tool_id: int,
    body: ToolUpdate,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    tool = (await db.execute(select(Tool).where(Tool.id == tool_id))).scalar_one_or_none()
    if not tool:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    for k, v in body.model_dump(exclude_unset=True).items():
        setattr(tool, k, v)
    await db.commit()
    await db.refresh(tool)
    return tool


@router.delete("/{tool_id}", status_code=status.HTTP_200_OK, response_model=ToolRead)
async def retire_tool(
    tool_id: int,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    """
    Retira la herramienta. Si tenía préstamo activo, lo cierra automáticamente
    con `return_reason='tool_deleted'` y emite eventos por WS.
    """
    tool = (await db.execute(select(Tool).where(Tool.id == tool_id))).scalar_one_or_none()
    if not tool:
        raise HTTPException(status.HTTP_404_NOT_FOUND)

    now = datetime.now(timezone.utc)
    open_loans = (
        await db.execute(
            select(Loan).where(Loan.tool_id == tool.id, Loan.returned_at.is_(None))
        )
    ).scalars().all()

    closed_events = []
    for ln in open_loans:
        ln.returned_at = now
        ln.return_reason = "tool_deleted"
        closed_events.append(
            {
                "type": "loan.returned",
                "loan_id": ln.id,
                "tool": {"id": tool.id, "rfid": tool.rfid, "brand": tool.brand, "model": tool.model},
                "user_id": ln.user_id,
                "returned_at": now.isoformat(),
                "return_reason": "tool_deleted",
            }
        )

    tool.active = False
    tool.status = "retired"
    tool.retired_at = now
    await db.commit()
    await db.refresh(tool)

    for ev in closed_events:
        await broker.publish("admin", ev)
    await broker.publish(
        "admin",
        {
            "type": "tool.retired",
            "tool_id": tool.id,
            "rfid": tool.rfid,
            "at": now.isoformat(),
        },
    )
    return tool
