"""
WebSocket /ws/station/{SN} — eventos solo de UNA estación.

Sin auth: la pantalla kiosko que vive junto al ESP32 no debe pedir login.
Solo ve eventos de su propia estación; no expone datos de otros usuarios o
estaciones.
"""

from __future__ import annotations

import asyncio
import logging

from fastapi import APIRouter, WebSocket, WebSocketDisconnect

from .broker import broker

log = logging.getLogger(__name__)
router = APIRouter()


@router.websocket("/ws/station/{sn}")
async def station_ws(websocket: WebSocket, sn: str):
    await websocket.accept()
    channel = f"station:{sn}"
    q = await broker.subscribe(channel)
    log.info("WS station connect sn=%s", sn)

    async def reader() -> None:
        while True:
            ev = await q.get()
            await websocket.send_json(ev)

    task = asyncio.create_task(reader())
    try:
        while True:
            await websocket.receive_text()
    except WebSocketDisconnect:
        pass
    except Exception as exc:
        log.exception("WS station error: %s", exc)
    finally:
        task.cancel()
        await broker.unsubscribe(channel, q)
        log.info("WS station disconnect sn=%s", sn)
