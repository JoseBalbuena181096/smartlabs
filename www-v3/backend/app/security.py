from datetime import datetime, timedelta, timezone

import bcrypt
import jwt
from fastapi import Depends, HTTPException, status
from fastapi.security import OAuth2PasswordBearer
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from .db import get_session
from .models import User
from .settings import settings

oauth2 = OAuth2PasswordBearer(tokenUrl="/api/auth/login", auto_error=False)


def _trim(p: str) -> bytes:
    # bcrypt limita el secret a 72 bytes; truncamos explícitamente para
    # que claves largas no exploten en runtime.
    return p.encode("utf-8")[:72]


def hash_password(p: str) -> str:
    return bcrypt.hashpw(_trim(p), bcrypt.gensalt()).decode("utf-8")


def verify_password(p: str, h: str) -> bool:
    try:
        return bcrypt.checkpw(_trim(p), h.encode("utf-8"))
    except ValueError:
        return False


def create_token(user_id: int) -> str:
    payload = {
        "sub": str(user_id),
        "exp": datetime.now(timezone.utc)
        + timedelta(minutes=settings.jwt_expire_minutes),
    }
    return jwt.encode(payload, settings.jwt_secret, algorithm=settings.jwt_algo)


def decode_token(token: str) -> int:
    try:
        data = jwt.decode(token, settings.jwt_secret, algorithms=[settings.jwt_algo])
        return int(data["sub"])
    except Exception:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Token inválido")


async def get_current_user(
    token: str | None = Depends(oauth2),
    db: AsyncSession = Depends(get_session),
) -> User:
    if not token:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "No autenticado")
    user_id = decode_token(token)
    res = await db.execute(select(User).where(User.id == user_id, User.active.is_(True)))
    user = res.scalar_one_or_none()
    if not user:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Usuario inválido")
    return user


async def require_admin(user: User = Depends(get_current_user)) -> User:
    if user.role != "admin":
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Requiere rol admin")
    return user
