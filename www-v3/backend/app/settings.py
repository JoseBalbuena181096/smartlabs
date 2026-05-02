from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    postgres_db: str = "smartlabs"
    postgres_user: str = "smartlabs"
    postgres_password: str = "changeme"
    postgres_host: str = "postgres"
    postgres_port: int = 5432

    backend_port: int = 8000
    jwt_secret: str = "dev-secret-change-me"
    jwt_algo: str = "HS256"
    jwt_expire_minutes: int = 720

    mqtt_host: str = "emqx"
    mqtt_port: int = 1883
    mqtt_user: str = "jose"
    mqtt_password: str = "public"
    mqtt_client_id: str = "smartlabs-v3-backend"

    loan_due_hours: int = 8
    inactivity_timeout_seconds: int = 180

    # Reconocimiento facial
    face_service_token: str = "change-me-face-service-token"
    face_similarity_threshold: float = 0.6
    # Bbox width (px) sobre el cual se considera que el usuario está
    # "cerca" de la cámara y la intención es CERRAR la sesión activa.
    # En el stream2 (1280x720) de la Tapo C210, ~220px es ~17% del ancho:
    # corresponde aproximadamente a estar a 60-80cm de la cámara.
    face_close_bbox_px: float = 220.0

    @property
    def database_url(self) -> str:
        return (
            f"postgresql+asyncpg://{self.postgres_user}:{self.postgres_password}"
            f"@{self.postgres_host}:{self.postgres_port}/{self.postgres_db}"
        )

    @property
    def database_url_sync(self) -> str:
        # alembic usa driver síncrono
        return (
            f"postgresql+psycopg2://{self.postgres_user}:{self.postgres_password}"
            f"@{self.postgres_host}:{self.postgres_port}/{self.postgres_db}"
        )


settings = Settings()
