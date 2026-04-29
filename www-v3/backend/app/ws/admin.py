"""
WebSocket /ws/admin — recibe TODOS los eventos del campus.

Soporta query string `?capture=tag` que también suscribe al canal
`capture` (eventos `tag.unknown` desde el dispatcher MQTT). Esto es lo que
permite a los formularios admin de "nuevo usuario" / "nueva herramienta"
auto-rellenar el RFID con un scan del lector ESP32.

Auth: token JWT en el query string `?token=...`. Sin token → 1008 close.
"""

from __future__ import annotations

import asyncio
import logging

from fastapi import APIRouter, WebSocket, WebSocketDisconnect, status
from sqlalchemy import select

from ..db import SessionLocal
from ..models import User
from ..security import decode_token
from .broker import broker

log = logging.getLogger(__name__)
router = APIRouter()


async def _validate_token(token: str | None) -> int | None:
    if not token:
        return None
    try:
        return decode_token(token)
    except Exception:
        return None


@router.websocket("/ws/admin")
async def admin_ws(websocket: WebSocket, token: str | None = None, capture: str | None = None):
    user_id = await _validate_token(token)
    if not user_id:
        await websocket.close(code=status.WS_1008_POLICY_VIOLATION)
        return
    async with SessionLocal() as db:
        user = (
            await db.execute(select(User).where(User.id == user_id, User.role == "admin"))
        ).scalar_one_or_none()
        if not user:
            await websocket.close(code=status.WS_1008_POLICY_VIOLATION)
            return

    await websocket.accept()
    log.info("WS admin connect user=%s capture=%s", user_id, capture)

    channels = ["admin"]
    if capture:
        channels.append("capture")

    queues = [await broker.subscribe(ch) for ch in channels]

    async def reader_for(q: asyncio.Queue) -> None:
        while True:
            ev = await q.get()
            await websocket.send_json(ev)

    tasks = [asyncio.create_task(reader_for(q)) for q in queues]

    try:
        # mantener viva la conexión leyendo del lado cliente (pings, etc.)
        while True:
            await websocket.receive_text()
    except WebSocketDisconnect:
        pass
    except Exception as exc:
        log.exception("WS admin error: %s", exc)
    finally:
        for t in tasks:
            t.cancel()
        for ch, q in zip(channels, queues):
            await broker.unsubscribe(ch, q)
        log.info("WS admin disconnect user=%s", user_id)
