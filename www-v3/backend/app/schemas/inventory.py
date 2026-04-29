from datetime import datetime
from .common import ORM


class InventoryRunRead(ORM):
    id: int
    campus_id: int
    started_by: int | None
    started_at: datetime
    finished_at: datetime | None
    notes: str | None


class InventoryScanRead(ORM):
    id: int
    run_id: int
    tool_rfid: str
    tool_id: int | None
    station_id: int | None
    scanned_at: datetime


class InventoryStartRequest(ORM):
    notes: str | None = None


class InventoryFinishRequest(ORM):
    notes: str | None = None


class InventoryReport(ORM):
    run: InventoryRunRead
    scans: list[InventoryScanRead]
    missing_tool_ids: list[int]
    unknown_rfids: list[str]
