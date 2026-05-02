"""face-service — wraps InsightFace + RTSP y delega persistencia al backend.

Endpoints (consumidos por el frontend Vue vía nginx):
- GET  /health
- GET  /camera/snapshot              → JPEG en vivo
- GET  /camera/detect                → bboxes + scores (sin embeddings)
- GET  /capture/{user_id}            → tick de registro: frame → analyze →
                                       (si posición nueva) push al backend →
                                       devuelve estado consolidado
- POST /identify?station_sn=SMART... → captura frame, embedding, llama backend
"""
from __future__ import annotations

import logging
from contextlib import asynccontextmanager
from typing import Any

import httpx
from fastapi import FastAPI, HTTPException, Query
from fastapi.responses import Response

from . import auto_identify, backend_client
from .camera import CameraError, capture_frame, encode_jpeg, get_grabber
from .config import settings
from .face_engine import analyze, get_app

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
log = logging.getLogger("face-service")


@asynccontextmanager
async def lifespan(app: FastAPI):
    # Calienta el modelo al arrancar para que la primera petición no espere
    # los ~3-5s de carga de buffalo_l.
    log.info("⏳ cargando modelo InsightFace…")
    get_app()
    log.info("✅ modelo listo (buffalo_l)")
    # Arranca el grabber RTSP en background — primer frame en ~1-2s,
    # después captura es instantánea (lee del buffer en memoria).
    log.info("⏳ arrancando grabber RTSP…")
    get_grabber()
    log.info("✅ grabber RTSP arrancado")
    # Loop autónomo de identificación si STATION_SN está configurado.
    auto_task = auto_identify.spawn(app.state)
    try:
        yield
    finally:
        await auto_identify.cancel(auto_task)


app = FastAPI(title="SmartLabs Face Service", version="1.0.0", lifespan=lifespan)


# ──────────────────────────────────────────────────────────────────────
# Diagnóstico
# ──────────────────────────────────────────────────────────────────────

@app.get("/health")
def health() -> dict[str, Any]:
    return {"status": "ok", "rtsp": settings.rtsp_url.split("@")[-1] if "@" in settings.rtsp_url else "n/a"}


@app.get("/camera/snapshot")
def snapshot() -> Response:
    try:
        frame = capture_frame()
    except CameraError as e:
        raise HTTPException(503, f"Cámara: {e}")
    return Response(content=encode_jpeg(frame), media_type="image/jpeg")


@app.get("/camera/detect")
def detect() -> dict[str, Any]:
    try:
        frame = capture_frame()
    except CameraError as e:
        raise HTTPException(503, f"Cámara: {e}")
    res = analyze(frame)
    if res is None:
        return {"faces_detected": 0}
    return {
        "faces_detected": 1,
        "bbox": res.bbox,
        "det_score": res.det_score,
        "yaw": res.yaw,
        "pitch": res.pitch,
        "position": res.position,
    }


# ──────────────────────────────────────────────────────────────────────
# Loop de captura (consumido por la UI cada ~500ms durante el registro)
# ──────────────────────────────────────────────────────────────────────

@app.get("/capture/{user_id}")
async def capture_tick(user_id: int) -> dict[str, Any]:
    """Tick del registro guiado.

    1. Captura frame de la cámara.
    2. Analiza pose.
    3. Si la posición detectada es nueva (no estaba en `captured` del backend)
       empuja el embedding y devuelve `just_captured=<position>`.
    4. Devuelve siempre el estado consolidado del backend + pose en vivo
       para que la UI pinte la guía visual.
    """
    # Estado actual antes de tomar la decisión.
    try:
        state = await backend_client.get_state(user_id)
    except httpx.HTTPError as e:
        raise HTTPException(502, f"backend: {e}")

    if state is None:
        raise HTTPException(
            400,
            "No hay sesión de captura activa. Llama POST /api/face/register/start primero.",
        )

    # Intenta capturar un frame.
    try:
        frame = capture_frame()
    except CameraError as e:
        return {
            **state,
            "status": "no_camera",
            "message": f"Cámara: {e}",
            "current_pose": None,
            "current_position": None,
            "just_captured": None,
        }

    res = analyze(frame)
    h, w = frame.shape[:2]
    if res is None:
        return {
            **state,
            "status": "no_face",
            "message": "Acércate a la cámara, no se detecta rostro",
            "current_pose": None,
            "current_position": None,
            "just_captured": None,
            "bbox": None,
            "frame_w": int(w),
            "frame_h": int(h),
            "det_score": 0.0,
            "distance": "unknown",
        }

    pose = {"yaw": res.yaw, "pitch": res.pitch}
    detected = res.position
    captured: list[str] = state.get("captured", [])

    # Heurística de distancia para guía visual: bbox_w en frames 1280px de
    # ancho. Calibrado con la Tapo C210 a 0.5–2m.
    bbox_w = float(res.bbox[2] - res.bbox[0])
    if bbox_w < 130:
        distance = "far"      # demasiado lejos
    elif bbox_w < 200:
        distance = "okay"     # un poco lejos pero registrable
    elif bbox_w < 380:
        distance = "good"     # ideal
    elif bbox_w < 520:
        distance = "close"    # muy cerca
    else:
        distance = "too_close"

    just_captured: str | None = None
    # Solo capturamos si la distancia es razonable (no demasiado lejos ni
    # pegado). Guía visual el resto del tiempo.
    if detected is not None and detected not in captured and distance in ("okay", "good", "close"):
        # Posición nueva y a buena distancia → empujar al backend.
        try:
            state = await backend_client.push_embedding(
                user_id=user_id,
                position=detected,
                embedding=res.embedding,
                det_score=res.det_score,
            )
            just_captured = detected
            log.info("📸 user_id=%d posición=%s capturada (dist=%s bbox_w=%.0f)", user_id, detected, distance, bbox_w)
        except httpx.HTTPError as e:
            log.warning("backend rechazó embedding: %s", e)

    pending = state.get("pending", [])
    if pending:
        next_pos = pending[0]
        instruction_map = {
            "frontal":   "Mira de frente a la cámara",
            "izquierda": "Gira la cabeza a la izquierda",
            "derecha":   "Gira la cabeza a la derecha",
            "arriba":    "Mira ligeramente hacia arriba",
            "abajo":     "Mira ligeramente hacia abajo",
        }
        instruction = instruction_map.get(next_pos, "Sigue las indicaciones")
    else:
        instruction = "¡Listo! Todas las posiciones capturadas"

    # Si la distancia no es óptima, prioriza esa instrucción sobre la pose.
    if distance == "far":
        instruction = "Acércate a la cámara — estás muy lejos"
    elif distance == "too_close":
        instruction = "Aléjate un poco — estás demasiado cerca"

    return {
        **state,
        "status": "captured" if just_captured else "waiting",
        "just_captured": just_captured,
        "instruction": instruction,
        "current_pose": pose,
        "current_position": detected,
        "det_score": res.det_score,
        "bbox": res.bbox,
        "frame_w": int(w),
        "frame_h": int(h),
        "distance": distance,
    }


# ──────────────────────────────────────────────────────────────────────
# Identificación (la abre el backend si llega station_sn)
# ──────────────────────────────────────────────────────────────────────

@app.post("/identify")
async def identify(
    station_sn: str | None = Query(default=None),
    threshold: float | None = Query(default=None),
) -> dict[str, Any]:
    try:
        frame = capture_frame()
    except CameraError as e:
        raise HTTPException(503, f"Cámara: {e}")
    res = analyze(frame)
    if res is None:
        raise HTTPException(400, "No se detectó rostro")
    try:
        match = await backend_client.identify(
            embedding=res.embedding,
            station_sn=station_sn,
            threshold=threshold,
        )
    except httpx.HTTPError as e:
        raise HTTPException(502, f"backend: {e}")
    if match is None:
        raise HTTPException(404, "Sin match")
    return match
