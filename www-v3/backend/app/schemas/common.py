from datetime import datetime
from pydantic import BaseModel, ConfigDict


class ORM(BaseModel):
    model_config = ConfigDict(from_attributes=True)


class TimestampedRead(ORM):
    id: int
    created_at: datetime
