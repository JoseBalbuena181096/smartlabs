"""initial schema

Revision ID: 0001
Revises:
Create Date: 2026-04-29

"""
from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql


revision = "0001"
down_revision = None
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.create_table(
        "campus",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("name", sa.Text, nullable=False),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.func.now()),
    )
    op.execute("INSERT INTO campus (id, name) VALUES (1, 'Default')")
    op.execute("ALTER SEQUENCE campus_id_seq RESTART WITH 2")

    op.create_table(
        "areas",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("campus_id", sa.Integer, sa.ForeignKey("campus.id"), server_default="1"),
        sa.Column("name", sa.Text, nullable=False),
        sa.UniqueConstraint("campus_id", "name", name="uq_area_campus_name"),
    )

    op.create_table(
        "users",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("campus_id", sa.Integer, sa.ForeignKey("campus.id"), server_default="1"),
        sa.Column("full_name", sa.Text, nullable=False),
        sa.Column("email", sa.Text, nullable=False),
        sa.Column("payroll_number", sa.Text),
        sa.Column("area_id", sa.Integer, sa.ForeignKey("areas.id")),
        sa.Column("rfid", sa.Text, nullable=False),
        sa.Column("role", sa.Text, server_default="staff"),
        sa.Column("active", sa.Boolean, server_default=sa.true()),
        sa.Column("password_hash", sa.Text),  # solo para admins (login web)
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.func.now()),
        sa.Column("updated_at", sa.DateTime(timezone=True), server_default=sa.func.now()),
        sa.UniqueConstraint("campus_id", "rfid", name="uq_user_campus_rfid"),
        sa.UniqueConstraint("campus_id", "email", name="uq_user_campus_email"),
        sa.CheckConstraint("role IN ('staff','admin')", name="ck_user_role"),
    )
    op.execute(
        "CREATE INDEX users_search_idx ON users "
        "USING GIN (to_tsvector('spanish', "
        "full_name || ' ' || email || ' ' || coalesce(payroll_number,'')))"
    )

    op.create_table(
        "tools",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("campus_id", sa.Integer, sa.ForeignKey("campus.id"), server_default="1"),
        sa.Column("brand", sa.Text),
        sa.Column("model", sa.Text),
        sa.Column("description", sa.Text),
        sa.Column("rfid", sa.Text, nullable=False),
        sa.Column("location", sa.Text),
        sa.Column("status", sa.Text, nullable=False, server_default="in_stock"),
        sa.Column("active", sa.Boolean, server_default=sa.true()),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.func.now()),
        sa.Column("retired_at", sa.DateTime(timezone=True)),
        sa.UniqueConstraint("campus_id", "rfid", name="uq_tool_campus_rfid"),
        sa.CheckConstraint(
            "status IN ('in_stock','on_loan','retired')", name="ck_tool_status"
        ),
    )
    op.execute(
        "CREATE INDEX tools_search_idx ON tools "
        "USING GIN (to_tsvector('spanish', "
        "coalesce(brand,'') || ' ' || coalesce(model,'') || ' ' || coalesce(description,'')))"
    )

    op.create_table(
        "stations",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("campus_id", sa.Integer, sa.ForeignKey("campus.id"), server_default="1"),
        sa.Column("serial_number", sa.Text, nullable=False),
        sa.Column("alias", sa.Text),
        sa.Column("online", sa.Boolean, server_default=sa.false()),
        sa.Column("last_seen", sa.DateTime(timezone=True)),
        sa.UniqueConstraint("campus_id", "serial_number", name="uq_station_campus_sn"),
    )

    op.create_table(
        "loan_sessions",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("station_id", sa.Integer, sa.ForeignKey("stations.id"), nullable=False),
        sa.Column("user_id", sa.Integer, sa.ForeignKey("users.id"), nullable=False),
        sa.Column("opened_at", sa.DateTime(timezone=True), server_default=sa.func.now()),
        sa.Column("closed_at", sa.DateTime(timezone=True)),
        sa.Column("close_reason", sa.Text),
    )
    op.execute(
        "CREATE UNIQUE INDEX one_open_session_per_station "
        "ON loan_sessions(station_id) WHERE closed_at IS NULL"
    )

    op.create_table(
        "loans",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("session_id", sa.Integer, sa.ForeignKey("loan_sessions.id")),
        sa.Column("user_id", sa.Integer, sa.ForeignKey("users.id"), nullable=False),
        sa.Column("tool_id", sa.Integer, sa.ForeignKey("tools.id"), nullable=False),
        sa.Column("loaned_at", sa.DateTime(timezone=True), server_default=sa.func.now()),
        sa.Column("due_at", sa.DateTime(timezone=True)),
        sa.Column("returned_at", sa.DateTime(timezone=True)),
        sa.Column("return_session_id", sa.Integer, sa.ForeignKey("loan_sessions.id")),
        sa.Column("return_reason", sa.Text),
    )
    op.execute(
        "CREATE INDEX loans_active_user_idx ON loans(user_id) WHERE returned_at IS NULL"
    )
    op.execute(
        "CREATE INDEX loans_active_tool_idx ON loans(tool_id) WHERE returned_at IS NULL"
    )

    op.create_table(
        "inventory_runs",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("campus_id", sa.Integer, sa.ForeignKey("campus.id"), server_default="1"),
        sa.Column("started_by", sa.Integer, sa.ForeignKey("users.id")),
        sa.Column("started_at", sa.DateTime(timezone=True), server_default=sa.func.now()),
        sa.Column("finished_at", sa.DateTime(timezone=True)),
        sa.Column("notes", sa.Text),
    )
    op.execute(
        "CREATE UNIQUE INDEX one_open_inventory_run_per_campus "
        "ON inventory_runs(campus_id) WHERE finished_at IS NULL"
    )

    op.create_table(
        "inventory_scans",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column(
            "run_id",
            sa.Integer,
            sa.ForeignKey("inventory_runs.id", ondelete="CASCADE"),
            nullable=False,
        ),
        sa.Column("tool_rfid", sa.Text, nullable=False),
        sa.Column("tool_id", sa.Integer, sa.ForeignKey("tools.id")),
        sa.Column("station_id", sa.Integer, sa.ForeignKey("stations.id")),
        sa.Column("scanned_at", sa.DateTime(timezone=True), server_default=sa.func.now()),
        sa.UniqueConstraint("run_id", "tool_rfid", name="uq_scan_run_rfid"),
    )


def downgrade() -> None:
    for tbl in (
        "inventory_scans",
        "inventory_runs",
        "loans",
        "loan_sessions",
        "stations",
        "tools",
        "users",
        "areas",
        "campus",
    ):
        op.execute(f"DROP TABLE IF EXISTS {tbl} CASCADE")
