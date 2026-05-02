"""station.face_enabled flag

Revision ID: 0003
Revises: 0002
Create Date: 2026-05-02

Permite activar/desactivar reconocimiento facial por estación. Cuando
está apagado, el face-service salta los ticks de auto-identify y el
kiosko oculta el panel de cámara — solo opera por RFID.
"""
from alembic import op
import sqlalchemy as sa


revision = "0003"
down_revision = "0002"
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.add_column(
        "stations",
        sa.Column(
            "face_enabled",
            sa.Boolean,
            nullable=False,
            server_default=sa.true(),
        ),
    )


def downgrade() -> None:
    op.drop_column("stations", "face_enabled")
