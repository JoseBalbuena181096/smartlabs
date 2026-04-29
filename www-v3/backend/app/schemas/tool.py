from datetime import datetime
from pydantic import Field
from .common import ORM


class ToolBase(ORM):
    brand: str | None = None
    model: str | None = None
    description: str | None = None
    rfid: str = Field(min_length=4)
    location: str | None = None


class ToolCreate(ToolBase):
    pass


class ToolUpdate(ORM):
    brand: str | None = None
    model: str | None = None
    description: str | None = None
    rfid: str | None = None
    location: str | None = None
    status: str | None = None


class ToolRead(ToolBase):
    id: int
    campus_id: int
    status: str
    active: bool
    created_at: datetime
    retired_at: datetime | None
