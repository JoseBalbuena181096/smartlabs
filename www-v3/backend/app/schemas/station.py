from datetime import datetime
from .common import ORM


class StationCreate(ORM):
    serial_number: str
    alias: str | None = None
    face_enabled: bool = True


class StationUpdate(ORM):
    alias: str | None = None
    face_enabled: bool | None = None


class StationRead(ORM):
    id: int
    campus_id: int
    serial_number: str
    alias: str | None
    online: bool
    last_seen: datetime | None
    face_enabled: bool
