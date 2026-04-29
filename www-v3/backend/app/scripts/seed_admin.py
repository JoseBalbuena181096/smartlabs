"""Crea (o actualiza) un usuario admin para login web.

uso:
    python -m app.scripts.seed_admin --email admin@local --password Admin123!
"""

from __future__ import annotations

import argparse
import asyncio

from sqlalchemy import select

from ..db import SessionLocal
from ..models import User
from ..security import hash_password


async def main() -> None:
    p = argparse.ArgumentParser()
    p.add_argument("--email", required=True)
    p.add_argument("--password", required=True)
    p.add_argument("--name", default="Admin")
    p.add_argument("--rfid", default="ADMIN-INIT")
    args = p.parse_args()

    async with SessionLocal() as db:
        existing = (
            await db.execute(select(User).where(User.email == args.email))
        ).scalar_one_or_none()
        if existing:
            existing.password_hash = hash_password(args.password)
            existing.role = "admin"
            existing.active = True
            await db.commit()
            print(f"✅ admin actualizado: {existing.email} (id={existing.id})")
            return

        u = User(
            full_name=args.name,
            email=args.email,
            rfid=args.rfid,
            role="admin",
            active=True,
            password_hash=hash_password(args.password),
        )
        db.add(u)
        await db.commit()
        await db.refresh(u)
        print(f"✅ admin creado: {u.email} (id={u.id})")


if __name__ == "__main__":
    asyncio.run(main())
