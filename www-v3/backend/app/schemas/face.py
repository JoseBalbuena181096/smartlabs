from datetime import datetime
from pydantic import Field

from .common import ORM


VALID_POSITIONS = {"frontal", "izquierda", "derecha", "arriba", "abajo", "foto_manual"}


class RegisterStartRequest(ORM):
    user_id: int


class RegisterStartResponse(ORM):
    user_id: int
    positions_required: list[str] = ["frontal", "izquierda", "derecha", "arriba", "abajo"]
    min_positions: int = 3


class EmbeddingPushRequest(ORM):
    user_id: int
    position: str
    embedding: list[float] = Field(min_length=512, max_length=512)
    det_score: float | None = None


class EmbeddingPushResponse(ORM):
    user_id: int
    captured: list[str]
    pending: list[str]
    progress_percent: int
    ready_to_commit: bool


class CommitRequest(ORM):
    user_id: int


class CommitResponse(ORM):
    user_id: int
    vectors_count: int
    positions: list[str]


class IdentifyRequest(ORM):
    embedding: list[float] = Field(min_length=512, max_length=512)
    station_sn: str | None = None  # si se da, abre sesión de préstamo en esa estación
    threshold: float | None = None  # override del umbral por defecto
    bbox: list[float] | None = None  # [x1, y1, x2, y2] para detectar cercanía
    frame_w: int | None = None  # dimensiones del frame original para escalar overlay
    frame_h: int | None = None


class IdentifyMatch(ORM):
    user_id: int
    full_name: str
    rfid: str
    score: float
    session_opened: bool = False
    session_closed: bool = False  # ← nuevo: true si la cara cercana cerró sesión
    session_id: int | None = None


class FaceUserStatus(ORM):
    user_id: int
    full_name: str
    email: str
    rfid: str
    role: str
    active: bool
    has_face: bool
    positions: list[str]
    last_captured_at: datetime | None = None


class RegisteredListResponse(ORM):
    total: int
    user_ids: list[int]
