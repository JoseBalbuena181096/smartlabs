"""face embeddings (pgvector)

Revision ID: 0002
Revises: 0001
Create Date: 2026-05-01

Tabla `face_embeddings` para reconocimiento facial. Usa la extensión
`pgvector` (la imagen pgvector/pgvector:pg16 ya la trae instalada, solo
necesitamos `CREATE EXTENSION` para activarla en esta DB).

Cada usuario puede tener varios embeddings, uno por posición de cabeza
(frontal, izquierda, derecha, arriba, abajo) — esto sube la tasa de
reconocimiento en condiciones reales. ON DELETE CASCADE: al borrar un user
se borran sus embeddings sin mantenimiento manual.

Index HNSW con `vector_cosine_ops` porque InsightFace produce embeddings
ya normalizados (norma 1) y la similitud coseno es la métrica natural.
"""
from alembic import op
import sqlalchemy as sa


revision = "0002"
down_revision = "0001"
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.execute("CREATE EXTENSION IF NOT EXISTS vector")

    op.execute(
        """
        CREATE TABLE face_embeddings (
            id          SERIAL PRIMARY KEY,
            user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            position    TEXT NOT NULL,
            embedding   vector(512) NOT NULL,
            det_score   REAL,
            created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            CONSTRAINT uq_face_user_position UNIQUE (user_id, position)
        )
        """
    )
    op.execute("CREATE INDEX face_embeddings_user_idx ON face_embeddings(user_id)")
    op.execute(
        "CREATE INDEX face_embeddings_hnsw_idx ON face_embeddings "
        "USING hnsw (embedding vector_cosine_ops) "
        "WITH (m = 16, ef_construction = 64)"
    )


def downgrade() -> None:
    op.execute("DROP TABLE IF EXISTS face_embeddings CASCADE")
    # No bajamos la extensión: puede haber otras tablas que la usen.
