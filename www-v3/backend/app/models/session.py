from datetime import datetime
from typing import TYPE_CHECKING
from sqlalchemy import DateTime, ForeignKey, Integer, Text, func
from sqlalchemy.orm import Mapped, mapped_column, relationship
from ..db import Base

if TYPE_CHECKING:
    from .user import User
    from .station import Station


class LoanSession(Base):
    __tablename__ = "loan_sessions"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    station_id: Mapped[int] = mapped_column(
        Integer, ForeignKey("stations.id"), nullable=False
    )
    user_id: Mapped[int] = mapped_column(
        Integer, ForeignKey("users.id"), nullable=False
    )
    opened_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )
    closed_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    close_reason: Mapped[str | None] = mapped_column(Text)

    user: Mapped["User"] = relationship(lazy="raise")
    station: Mapped["Station"] = relationship(lazy="raise")
