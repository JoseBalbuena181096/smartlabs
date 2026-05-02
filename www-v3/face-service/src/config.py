from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    # Cámara — la URL incluye usuario y contraseña RTSP de la Tapo C210.
    rtsp_url: str = "rtsp://user:pass@192.168.0.50:554/stream2"

    # Backend — comparte token con el backend FastAPI de SmartLabs.
    backend_url: str = "http://backend:8000"
    backend_token: str = "change-me-face-service-token"

    # Reconocimiento
    similarity_threshold: float = 0.6
    detection_size: int = 640

    # Auto-identify loop: a qué estación pertenece esta cámara y cada cuánto
    # debe intentar identificar a quien aparezca frente a ella. Si station_sn
    # está vacío, el loop NO arranca (modo manual: solo /identify por
    # petición del frontend admin).
    station_sn: str = ""
    auto_identify_interval: float = 2.0
    auto_identify_min_score: float = 0.5
    # Tras un match exitoso, dormir N segundos antes de reintentar — la sesión
    # ya está abierta, no tiene sentido reidentificar mientras dura.
    auto_identify_settle_seconds: float = 60.0


settings = Settings()
