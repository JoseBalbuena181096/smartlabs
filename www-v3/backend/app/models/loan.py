from datetime import datetime
from sqlalchemy import DateTime, ForeignKey, Integer, Text, func
from sqlalchemy.orm import Mapped, mapped_column
from ..db import Base


class Loan(Base):
    __tablename__ = "loans"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    session_id: Mapped[int | None] = mapped_column(
        Integer, ForeignKey("loan_sessions.id")
    )
    user_id: Mapped[int] = mapped_column(
        Integer, ForeignKey("users.id"), nullable=False
    )
    tool_id: Mapped[int] = mapped_column(
        Integer, ForeignKey("tools.id"), nullable=False
    )
    loaned_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now()
    )
    due_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    returned_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    return_session_id: Mapped[int | None] = mapped_column(
        Integer, ForeignKey("loan_sessions.id")
    )
    return_reason: Mapped[str | None] = mapped_column(Text)
