from datetime import datetime
from pydantic import EmailStr, Field
from .common import ORM


class UserBase(ORM):
    full_name: str = Field(min_length=1)
    email: EmailStr
    payroll_number: str | None = None
    area_id: int | None = None
    rfid: str = Field(min_length=4)
    role: str = "staff"
    active: bool = True


class UserCreate(UserBase):
    password: str | None = None  # solo si role=admin


class UserUpdate(ORM):
    full_name: str | None = None
    email: EmailStr | None = None
    payroll_number: str | None = None
    area_id: int | None = None
    rfid: str | None = None
    role: str | None = None
    active: bool | None = None
    password: str | None = None


class UserRead(UserBase):
    id: int
    campus_id: int
    created_at: datetime
    updated_at: datetime


class LoginRequest(ORM):
    email: EmailStr
    password: str


class TokenResponse(ORM):
    token: str
    user: UserRead
