from fastapi import APIRouter, Depends
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession

from ..db import get_session
from ..mqtt.publisher import publisher
from ..ws.broker import broker

router = APIRouter(tags=["health"])


@router.get("/health")
async def health(db: AsyncSession = Depends(get_session)):
    db_ok = "ok"
    try:
        await db.execute(text("SELECT 1"))
    except Exception as exc:
        db_ok = f"error: {exc.__class__.__name__}"
    return {
        "db": db_ok,
        "mqtt": "connected" if publisher.connected else "disconnected",
        "ws_clients": broker.client_count(),
    }
