from .common import ORM


class AreaCreate(ORM):
    name: str


class AreaRead(ORM):
    id: int
    name: str
    campus_id: int
