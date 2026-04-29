from sqlalchemy import ForeignKey, Integer, Text, UniqueConstraint
from sqlalchemy.orm import Mapped, mapped_column
from ..db import Base


class Area(Base):
    __tablename__ = "areas"
    __table_args__ = (UniqueConstraint("campus_id", "name", name="uq_area_campus_name"),)

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    campus_id: Mapped[int] = mapped_column(
        Integer, ForeignKey("campus.id"), default=1
    )
    name: Mapped[str] = mapped_column(Text, nullable=False)
