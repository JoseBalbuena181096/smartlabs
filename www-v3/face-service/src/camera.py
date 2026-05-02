"""Captura de frames del stream RTSP de la cámara Tapo.

Diseño: **un grabber thread persistente** que lee frames del RTSP en
loop y guarda el último en memoria. Las llamadas a `capture_frame()`
sólo devuelven el último frame leído — sin abrir/cerrar RTSP cada vez.

Por qué: abrir RTSP por petición tarda 1-25s porque OpenCV/FFMPEG tiene
que negociar SDP, descartar el buffer atrasado, etc. Bajo carga de
polling (UI cada 600ms + snapshot cada ~1s), las peticiones se
encolaban y la cámara entraba en cola hasta colapsar.

Con grabber thread:
- Primer frame: ~1-2s (arranque del thread).
- Frames siguientes: ~10-50ms (sólo copia memoria).

Si el RTSP se cae, el thread reintenta cada 2s sin tirar el servicio.
"""
from __future__ import annotations

import logging
import threading
import time

import cv2
import numpy as np

from .config import settings

log = logging.getLogger(__name__)


class CameraError(RuntimeError):
    pass


class FrameGrabber:
    def __init__(self, url: str) -> None:
        self.url = url
        self._lock = threading.Lock()
        self._frame: np.ndarray | None = None
        self._frame_at: float = 0.0
        self._stop = threading.Event()
        self._thread = threading.Thread(target=self._run, daemon=True, name="rtsp-grabber")
        self._thread.start()

    def _run(self) -> None:
        backoff = 1.0
        while not self._stop.is_set():
            cap = cv2.VideoCapture(self.url, cv2.CAP_FFMPEG)
            if not cap.isOpened():
                log.warning("RTSP open failed; retrying in %.1fs", backoff)
                time.sleep(backoff)
                backoff = min(backoff * 1.5, 8.0)
                continue
            cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
            log.info("RTSP grabber conectado: %s", self.url.split("@")[-1])
            backoff = 1.0
            while not self._stop.is_set():
                ok, frame = cap.read()
                if not ok or frame is None:
                    log.warning("RTSP read fail; reconnecting")
                    break
                with self._lock:
                    self._frame = frame
                    self._frame_at = time.time()
            cap.release()
            time.sleep(0.5)

    def get(self, max_age: float = 5.0) -> np.ndarray:
        with self._lock:
            frame = self._frame
            age = time.time() - self._frame_at
        if frame is None:
            raise CameraError("No hay frame todavía (¿RTSP no conectó?)")
        if age > max_age:
            raise CameraError(f"Último frame es viejo ({age:.1f}s) — ¿cámara caída?")
        return frame.copy()

    def stop(self) -> None:
        self._stop.set()


_grabber: FrameGrabber | None = None


def get_grabber() -> FrameGrabber:
    global _grabber
    if _grabber is None:
        _grabber = FrameGrabber(settings.rtsp_url)
    return _grabber


def capture_frame() -> np.ndarray:
    return get_grabber().get()


def encode_jpeg(frame: np.ndarray, quality: int = 80) -> bytes:
    ok, buf = cv2.imencode(".jpg", frame, [int(cv2.IMWRITE_JPEG_QUALITY), quality])
    if not ok:
        raise CameraError("No se pudo encodear JPEG")
    return buf.tobytes()
