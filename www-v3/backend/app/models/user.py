from datetime import datetime
from sqlalchemy import (
    Boolean,
    CheckConstraint,
    DateTime,
    ForeignKey,
    Integer,
    Text,
    UniqueConstraint,
    func,
)
from sqlalchemy.orm import Mapped, mapped_column
from ..db import Base


class User(Base):
    __tablename__ = "users"
    __table_args__ = (
        UniqueConstraint("campus_id", "rfid", name="uq_user_campus_rfid"),
        UniqueConstraint("campus_id", "email", name="uq_user_campus_email"),
        CheckConstraint("role IN ('staff','admin')", name="ck_user_role"),
    )

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    campus_id: Mapped[int] = mapped_column(
        Integer, ForeignKey("campus.id"), default=1
    )
    full_name: Mapped[str] = mapped_column(Text, nullable=False)
    email: Mapped[str] = mapped_column(Text, nullable=False)
    payroll_number: Mapped[str | None] = mapped_column(Text)
    area_id: Mapped[int | None] = mapped_column(Integer, ForeignKey("areas.id"))
    rfid: Mapped[str] = mapped_column(Text, nullable=False)
    role: Mapped[str] = mapped_column(Text, default="staff")
    active: Mapped[bool] = mapped_column(Boolean, default=True)
    password_hash: Mapped[str | None] = mapped_column(Text)
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )
    updated_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), onupdate=func.now()
    )
