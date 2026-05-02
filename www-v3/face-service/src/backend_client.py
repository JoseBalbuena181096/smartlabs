"""Cliente HTTP hacia el backend de SmartLabs.

Auth con header `X-Service-Token`. El backend valida contra
`settings.face_service_token`. Toda la lógica de DB y MQTT vive allá.
"""
from __future__ import annotations

import logging
from typing import Any

import httpx

from .config import settings

log = logging.getLogger(__name__)


def _headers() -> dict[str, str]:
    return {"X-Service-Token": settings.backend_token}


async def push_embedding(
    user_id: int,
    position: str,
    embedding: list[float],
    det_score: float | None,
) -> dict[str, Any]:
    async with httpx.AsyncClient(base_url=settings.backend_url, timeout=10.0) as c:
        r = await c.post(
            "/api/face/register/embedding",
            headers=_headers(),
            json={
                "user_id": user_id,
                "position": position,
                "embedding": embedding,
                "det_score": det_score,
            },
        )
        r.raise_for_status()
        return r.json()


async def get_state(user_id: int) -> dict[str, Any] | None:
    async with httpx.AsyncClient(base_url=settings.backend_url, timeout=5.0) as c:
        r = await c.get(
            f"/api/face/register/state/{user_id}",
            headers=_headers(),
        )
        if r.status_code == 404:
            return None
        r.raise_for_status()
        return r.json()


async def identify(
    embedding: list[float],
    station_sn: str | None,
    threshold: float | None = None,
    bbox: list[float] | None = None,
    frame_w: int | None = None,
    frame_h: int | None = None,
) -> dict[str, Any] | None:
    """Devuelve dict con match (o None si no hubo match).
    Caso especial: 423 = "estación tiene face_enabled=false" → devolvemos
    {'disabled': True} para que el caller sepa diferenciar."""
    payload: dict[str, Any] = {"embedding": embedding}
    if station_sn:
        payload["station_sn"] = station_sn
    if threshold is not None:
        payload["threshold"] = threshold
    if bbox is not None:
        payload["bbox"] = bbox
    if frame_w is not None:
        payload["frame_w"] = frame_w
    if frame_h is not None:
        payload["frame_h"] = frame_h
    async with httpx.AsyncClient(base_url=settings.backend_url, timeout=10.0) as c:
        r = await c.post("/api/face/identify", headers=_headers(), json=payload)
        if r.status_code == 404:
            return None
        if r.status_code == 423:
            return {"disabled": True}
        r.raise_for_status()
        return r.json()


async def get_station_face_enabled(station_sn: str) -> bool | None:
    """Consulta endpoint público para saber si la estación tiene face on/off.
    None si la estación no existe / error."""
    async with httpx.AsyncClient(base_url=settings.backend_url, timeout=5.0) as c:
        try:
            r = await c.get(f"/api/stations/by-sn/{station_sn}/state")
        except httpx.HTTPError:
            return None
        if r.status_code != 200:
            return None
        data = r.json()
        return bool(data.get("face_enabled", True))
