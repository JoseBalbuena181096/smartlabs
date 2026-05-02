"""InsightFace: detección + embedding + estimación de pose.

Pose se calcula a partir de los 5 keypoints (ojos, nariz, esquinas boca):
- yaw: desplazamiento horizontal de la nariz vs centro de los ojos.
- pitch: posición vertical de la nariz entre ojos y boca.

Estos no son ángulos exactos (eso requeriría un modelo 3D de cabeza), pero
son aproximaciones suficientemente estables para clasificar en 5 cubetas:
frontal, izquierda, derecha, arriba, abajo. Si falla en condiciones raras
de iluminación, el peor caso es que el sistema no captura — el admin gira
un poco más y reintenta.
"""
from __future__ import annotations

import math
from dataclasses import dataclass

import numpy as np
from insightface.app import FaceAnalysis

from .config import settings


_app: FaceAnalysis | None = None


def get_app() -> FaceAnalysis:
    global _app
    if _app is None:
        _app = FaceAnalysis(
            name="buffalo_l",
            root="/models",
            providers=["CPUExecutionProvider"],
        )
        _app.prepare(ctx_id=0, det_size=(settings.detection_size, settings.detection_size))
    return _app


POSITION_THRESHOLDS = {
    "frontal":   {"yaw": (-10, 10),  "pitch": (-10, 10)},
    "izquierda": {"yaw": (-40, -12), "pitch": (-15, 15)},
    "derecha":   {"yaw": (12, 40),   "pitch": (-15, 15)},
    "arriba":    {"yaw": (-15, 15),  "pitch": (-40, -12)},
    "abajo":     {"yaw": (-15, 15),  "pitch": (12, 40)},
}


@dataclass
class FaceResult:
    embedding: list[float]
    yaw: float
    pitch: float
    position: str | None
    det_score: float
    bbox: list[float]


def _estimate_pose(kps) -> tuple[float, float]:
    eye_l, eye_r, nose, mouth_l, mouth_r = kps[0], kps[1], kps[2], kps[3], kps[4]
    eye_cx = (eye_l[0] + eye_r[0]) / 2
    eye_cy = (eye_l[1] + eye_r[1]) / 2
    eye_dist = math.dist(eye_l, eye_r)
    if eye_dist < 1:
        return 0.0, 0.0

    yaw = ((nose[0] - eye_cx) / eye_dist) * 60.0

    mouth_cy = (mouth_l[1] + mouth_r[1]) / 2
    vertical = mouth_cy - eye_cy
    if vertical < 1:
        return float(round(yaw, 1)), 0.0
    nose_ratio = (nose[1] - eye_cy) / vertical
    # Nariz suele caer al ~45% entre ojos y boca; <0.35 mira arriba, >0.55 abajo.
    pitch = (nose_ratio - 0.45) * 120.0

    return float(round(yaw, 1)), float(round(pitch, 1))


def _classify(yaw: float, pitch: float) -> str | None:
    for name, ranges in POSITION_THRESHOLDS.items():
        ymin, ymax = ranges["yaw"]
        pmin, pmax = ranges["pitch"]
        if ymin <= yaw <= ymax and pmin <= pitch <= pmax:
            return name
    return None


def analyze(frame: np.ndarray) -> FaceResult | None:
    """Toma un frame BGR y devuelve el rostro principal con su pose."""
    faces = get_app().get(frame)
    if not faces:
        return None
    # Rostro más grande (el más cercano a la cámara).
    if len(faces) > 1:
        faces.sort(
            key=lambda f: (f.bbox[2] - f.bbox[0]) * (f.bbox[3] - f.bbox[1]),
            reverse=True,
        )
    f = faces[0]
    yaw, pitch = _estimate_pose(f.kps)
    return FaceResult(
        embedding=f.normed_embedding.astype(float).tolist(),
        yaw=yaw,
        pitch=pitch,
        position=_classify(yaw, pitch),
        det_score=float(f.det_score),
        bbox=f.bbox.astype(float).tolist(),
    )
