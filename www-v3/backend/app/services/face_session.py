"""Estado en memoria de sesiones de captura facial.

El admin pulsa "Iniciar registro" para `user_id=42` → entra en
`face_sessions[42]` con un dict vacío. El face-service va detectando
posiciones y haciendo POST por cada embedding capturado. Cuando se llama
`commit`, los vectores acumulados se persisten en `face_embeddings` y la
sesión se borra de memoria.

No persistimos esto en DB porque es transitorio (~30 segundos por usuario).
Si el backend se reinicia a media captura, el admin simplemente reinicia.
"""
from __future__ import annotations

from dataclasses import dataclass, field
from datetime import datetime, timezone


REQUIRED_POSITIONS = ("frontal", "izquierda", "derecha", "arriba", "abajo")
MIN_POSITIONS = 3
SESSION_TTL_SECONDS = 600  # 10 min — si pasa más, se considera abandonada


@dataclass
class CapturedEmbedding:
    embedding: list[float]
    det_score: float | None
    captured_at: datetime


@dataclass
class CaptureSession:
    user_id: int
    started_at: datetime = field(default_factory=lambda: datetime.now(timezone.utc))
    by_position: dict[str, CapturedEmbedding] = field(default_factory=dict)

    def captured_positions(self) -> list[str]:
        return list(self.by_position.keys())

    def pending_positions(self) -> list[str]:
        captured = set(self.by_position.keys())
        return [p for p in REQUIRED_POSITIONS if p not in captured]

    def progress_percent(self) -> int:
        return int(len(self.by_position) / len(REQUIRED_POSITIONS) * 100)

    def ready_to_commit(self) -> bool:
        return len(self.pending_positions()) == 0

    def can_commit(self) -> bool:
        return len(self.by_position) >= MIN_POSITIONS

    def is_stale(self) -> bool:
        age = (datetime.now(timezone.utc) - self.started_at).total_seconds()
        return age > SESSION_TTL_SECONDS


class CaptureRegistry:
    def __init__(self) -> None:
        self._sessions: dict[int, CaptureSession] = {}

    def start(self, user_id: int) -> CaptureSession:
        # Limpia cualquier sesión vieja para este user (re-registro).
        self._sessions[user_id] = CaptureSession(user_id=user_id)
        self._gc()
        return self._sessions[user_id]

    def get(self, user_id: int) -> CaptureSession | None:
        s = self._sessions.get(user_id)
        if s and s.is_stale():
            del self._sessions[user_id]
            return None
        return s

    def push(
        self,
        user_id: int,
        position: str,
        embedding: list[float],
        det_score: float | None,
    ) -> CaptureSession:
        s = self.get(user_id)
        if s is None:
            raise KeyError(f"no active capture session for user {user_id}")
        # Idempotente: si ya estaba esa posición, la sobreescribimos con la última.
        s.by_position[position] = CapturedEmbedding(
            embedding=embedding, det_score=det_score, captured_at=datetime.now(timezone.utc)
        )
        return s

    def cancel(self, user_id: int) -> None:
        self._sessions.pop(user_id, None)

    def commit_drain(self, user_id: int) -> CaptureSession | None:
        s = self._sessions.pop(user_id, None)
        return s

    def _gc(self) -> None:
        stale = [uid for uid, s in self._sessions.items() if s.is_stale()]
        for uid in stale:
            del self._sessions[uid]


registry = CaptureRegistry()
