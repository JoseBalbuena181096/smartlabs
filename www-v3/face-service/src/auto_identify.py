"""Loop autónomo que vigila la cámara y dispara identificación.

Cuando una cámara está asignada a una estación (env STATION_SN), este
loop arranca al iniciar el servicio y:

  1. Cada `auto_identify_interval` segundos captura un frame.
  2. Si detecta rostro con score >= `auto_identify_min_score`, lo envía
     a backend via /api/face/identify con station_sn.
  3. El backend hace KNN en pgvector. Si hay match:
       - Abre `LoanSession` para ese user en esa estación.
       - Publica MQTT `{SN}/command found` + `{SN}/user_name <nombre>`.
       - Broadcast WS `session.opened` con `method: "face"`.
     Si no hay match, no pasa nada (404 silencioso).
  4. Tras un match, duerme `auto_identify_settle_seconds` (60s default)
     para no spamear: la sesión ya está abierta, no hay nada que hacer.

Esto es lo que cierra el loop "cara → kiosko" sin que nadie pulse nada.
"""
from __future__ import annotations

import asyncio
import logging
from contextlib import suppress

import httpx

from . import backend_client
from .camera import CameraError, capture_frame
from .config import settings
from .face_engine import analyze

log = logging.getLogger("face-service.auto")

# Estado del flag face_enabled de la estación. Se refresca cada
# `_ENABLED_REFRESH_S` segundos contra el backend; entre refreshes el
# loop confía en este cache para no preguntar en cada tick.
_face_enabled: bool = True
_enabled_last_check: float = 0.0
_ENABLED_REFRESH_S = 10.0


async def _refresh_face_enabled() -> None:
    """Pregunta al backend si la estación tiene face habilitado."""
    global _face_enabled, _enabled_last_check
    import time
    now = time.time()
    if now - _enabled_last_check < _ENABLED_REFRESH_S:
        return
    _enabled_last_check = now
    val = await backend_client.get_station_face_enabled(settings.station_sn)
    if val is None:
        return  # mantén lo que tenías
    if val != _face_enabled:
        log.info(
            "🔁 station=%s face_enabled cambió: %s -> %s",
            settings.station_sn, _face_enabled, val,
        )
    _face_enabled = val


async def _tick() -> str:
    """Una iteración. Devuelve la acción que tomó:
    - "" (vacío)  → no acción (no rostro, no match, lejos, etc.)
    - "opened"    → abrió sesión nueva
    - "closed"    → cerró sesión por cara cercana
    El loop usa esto para decidir cuánto tiempo dormir antes del siguiente
    tick — el cooldown post-CLOSE debe ser largo (~15s) para que la persona
    se aleje físicamente, si no haría ping-pong open↔close cada 5s."""
    global _face_enabled
    await _refresh_face_enabled()
    if not _face_enabled:
        return ""

    try:
        frame = capture_frame()
    except CameraError as e:
        log.debug("auto-identify: cámara no disponible — %s", e)
        return ""

    # analyze() es CPU-bound (InsightFace ONNX). Lo ejecutamos en un
    # executor para no bloquear el event loop de FastAPI mientras hay
    # peticiones del admin/frontend.
    loop = asyncio.get_running_loop()
    res = await loop.run_in_executor(None, analyze, frame)
    if res is None:
        return ""

    if res.det_score < settings.auto_identify_min_score:
        log.debug("auto-identify: rostro débil score=%.2f", res.det_score)
        return ""

    h, w = frame.shape[:2]
    try:
        match = await backend_client.identify(
            embedding=res.embedding,
            station_sn=settings.station_sn,
            bbox=res.bbox,
            frame_w=int(w),
            frame_h=int(h),
        )
    except httpx.HTTPError as e:
        log.warning("auto-identify backend error: %s", e)
        return ""

    if match is not None and match.get("disabled"):
        # backend respondió 423 — la estación tiene face desactivado.
        # Refrescamos el cache para que el siguiente tick lo salte.
        _face_enabled = False
        return ""

    if match is None:
        log.debug("auto-identify: rostro no reconocido (station=%s)", settings.station_sn)
        return ""

    if match.get("session_opened"):
        log.info(
            "✅ auto-identify: %s ENTRÓ en %s (score=%.2f)",
            match.get("full_name"),
            settings.station_sn,
            match.get("score"),
        )
        return "opened"

    if match.get("session_closed"):
        log.info(
            "👋 auto-identify: %s CERRÓ sesión en %s (cara cercana)",
            match.get("full_name"),
            settings.station_sn,
        )
        return "closed"

    # Match pero sesión ya activa y usuario lejos — sigue tickeando para
    # detectar cuando se acerque y quiera cerrar.
    log.debug(
        "auto-identify: %s en sesión pero lejos (score=%.2f)",
        match.get("full_name"),
        match.get("score"),
    )
    return ""


async def auto_identify_loop() -> None:
    if not settings.station_sn:
        log.info("STATION_SN vacío — loop de auto-identify desactivado")
        return

    log.info(
        "🎥 auto-identify activo: station=%s interval=%.1fs settle=%.0fs threshold>=%.2f",
        settings.station_sn,
        settings.auto_identify_interval,
        settings.auto_identify_settle_seconds,
        settings.auto_identify_min_score,
    )

    # Pequeña espera inicial para que el grabber RTSP tenga frames.
    await asyncio.sleep(3.0)

    # Cooldowns diferenciados:
    # - Tras OPEN: 3s, el usuario probablemente va a moverse a operar la
    #   estación. Si vuelve a la cámara cerca, queremos detectar el cierre.
    # - Tras CLOSE: 15s, evita ping-pong cuando alguien se queda parado
    #   frente a la cámara después de cerrar (caso típico).
    OPEN_COOLDOWN = 3.0
    CLOSE_COOLDOWN = 15.0

    while True:
        try:
            action = await _tick()
        except Exception as e:  # noqa: BLE001 — el loop no debe morir nunca
            log.exception("auto-identify tick exception: %s", e)
            action = ""

        if action == "closed":
            await asyncio.sleep(CLOSE_COOLDOWN)
        elif action == "opened":
            await asyncio.sleep(OPEN_COOLDOWN)
        else:
            await asyncio.sleep(settings.auto_identify_interval)


def spawn(app_state) -> asyncio.Task | None:
    """Llamado desde lifespan. Devuelve la Task para cancelar al shutdown."""
    if not settings.station_sn:
        return None
    task = asyncio.create_task(auto_identify_loop(), name="auto-identify")
    return task


async def cancel(task: asyncio.Task | None) -> None:
    if task is None:
        return
    task.cancel()
    with suppress(asyncio.CancelledError, Exception):
        await task
