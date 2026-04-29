from datetime import datetime
from sqlalchemy import (
    DateTime,
    ForeignKey,
    Integer,
    Text,
    UniqueConstraint,
    func,
)
from sqlalchemy.orm import Mapped, mapped_column
from ..db import Base


class InventoryRun(Base):
    __tablename__ = "inventory_runs"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    campus_id: Mapped[int] = mapped_column(Integer, ForeignKey("campus.id"), default=1)
    started_by: Mapped[int | None] = mapped_column(Integer, ForeignKey("users.id"))
    started_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )
    finished_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    notes: Mapped[str | None] = mapped_column(Text)


class InventoryScan(Base):
    __tablename__ = "inventory_scans"
    __table_args__ = (
        UniqueConstraint("run_id", "tool_rfid", name="uq_scan_run_rfid"),
    )

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    run_id: Mapped[int] = mapped_column(
        Integer,
        ForeignKey("inventory_runs.id", ondelete="CASCADE"),
        nullable=False,
    )
    tool_rfid: Mapped[str] = mapped_column(Text, nullable=False)
    tool_id: Mapped[int | None] = mapped_column(Integer, ForeignKey("tools.id"))
    station_id: Mapped[int | None] = mapped_column(
        Integer, ForeignKey("stations.id")
    )
    scanned_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )
