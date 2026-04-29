from datetime import datetime
from .common import ORM
from .user import UserRead
from .tool import ToolRead


class LoanRead(ORM):
    id: int
    user_id: int
    tool_id: int
    session_id: int | None
    loaned_at: datetime
    due_at: datetime | None
    returned_at: datetime | None
    return_reason: str | None


class LoanReadExpanded(LoanRead):
    user: UserRead | None = None
    tool: ToolRead | None = None


class LoanReturnRequest(ORM):
    reason: str = "admin_manual"


class LoanExtendRequest(ORM):
    due_at: datetime


class LoanSessionRead(ORM):
    id: int
    station_id: int
    user_id: int
    opened_at: datetime
    closed_at: datetime | None
    close_reason: str | None
