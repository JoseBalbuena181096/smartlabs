from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ..db import get_session
from ..models import InventoryRun, InventoryScan, Tool, User
from ..schemas.inventory import (
    InventoryFinishRequest,
    InventoryReport,
    InventoryRunRead,
    InventoryScanRead,
    InventoryStartRequest,
)
from ..security import require_admin
from ..ws.broker import broker

router = APIRouter(prefix="/inventory", tags=["inventory"])


@router.get("", response_model=list[InventoryRunRead])
async def list_runs(
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    return (
        await db.execute(select(InventoryRun).order_by(InventoryRun.started_at.desc()).limit(50))
    ).scalars().all()


@router.post("/start", response_model=InventoryRunRead, status_code=status.HTTP_201_CREATED)
async def start_run(
    body: InventoryStartRequest,
    db: AsyncSession = Depends(get_session),
    user: User = Depends(require_admin),
):
    open_run = (
        await db.execute(select(InventoryRun).where(InventoryRun.finished_at.is_(None)))
    ).scalar_one_or_none()
    if open_run:
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Ya hay un inventario abierto")
    run = InventoryRun(started_by=user.id, notes=body.notes)
    db.add(run)
    await db.commit()
    await db.refresh(run)
    await broker.publish(
        "admin",
        {"type": "inventory.started", "run_id": run.id, "at": run.started_at.isoformat()},
    )
    return run


@router.post("/{run_id}/finish", response_model=InventoryReport)
async def finish_run(
    run_id: int,
    body: InventoryFinishRequest,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    run = (
        await db.execute(select(InventoryRun).where(InventoryRun.id == run_id))
    ).scalar_one_or_none()
    if not run:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    if run.finished_at is not None:
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Inventario ya cerrado")
    run.finished_at = datetime.now(timezone.utc)
    if body.notes:
        run.notes = (run.notes + "\n" if run.notes else "") + body.notes
    await db.commit()

    return await _build_report(db, run)


@router.get("/{run_id}", response_model=InventoryReport)
async def get_run(
    run_id: int,
    db: AsyncSession = Depends(get_session),
    _admin=Depends(require_admin),
):
    run = (
        await db.execute(select(InventoryRun).where(InventoryRun.id == run_id))
    ).scalar_one_or_none()
    if not run:
        raise HTTPException(status.HTTP_404_NOT_FOUND)
    return await _build_report(db, run)


async def _build_report(db: AsyncSession, run: InventoryRun) -> InventoryReport:
    scans = (
        await db.execute(
            select(InventoryScan).where(InventoryScan.run_id == run.id).order_by(InventoryScan.scanned_at)
        )
    ).scalars().all()

    expected_tools = (
        await db.execute(
            select(Tool).where(Tool.active.is_(True), Tool.status != "retired")
        )
    ).scalars().all()
    expected_ids = {t.id for t in expected_tools}

    seen_tool_ids = {s.tool_id for s in scans if s.tool_id is not None}
    missing_tool_ids = sorted(expected_ids - seen_tool_ids)
    unknown_rfids = sorted({s.tool_rfid for s in scans if s.tool_id is None})

    return InventoryReport(
        run=InventoryRunRead.model_validate(run),
        scans=[InventoryScanRead.model_validate(s) for s in scans],
        missing_tool_ids=missing_tool_ids,
        unknown_rfids=unknown_rfids,
    )
