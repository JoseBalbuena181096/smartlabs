import asyncio
import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from .mqtt.client import run_forever as run_mqtt
from .routers import areas, auth, health, inventory, loans, stations, tools, users
from .ws import admin as ws_admin
from .ws import station as ws_station

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(name)s: %(message)s",
)


@asynccontextmanager
async def lifespan(app: FastAPI):
    mqtt_task = asyncio.create_task(run_mqtt(), name="mqtt-bridge")
    try:
        yield
    finally:
        mqtt_task.cancel()
        try:
            await mqtt_task
        except (asyncio.CancelledError, Exception):
            pass


app = FastAPI(title="SmartLabs v3", version="0.1.0", lifespan=lifespan)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # local-only deploy; nginx fija orígenes en prod si hace falta
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# REST
app.include_router(health.router, prefix="/api")
app.include_router(auth.router, prefix="/api")
app.include_router(users.router, prefix="/api")
app.include_router(tools.router, prefix="/api")
app.include_router(areas.router, prefix="/api")
app.include_router(stations.router, prefix="/api")
app.include_router(loans.router, prefix="/api")
app.include_router(loans.sessions_router, prefix="/api")
app.include_router(inventory.router, prefix="/api")

# WebSockets (sin prefix /api porque nginx hace upgrade en /ws)
app.include_router(ws_admin.router)
app.include_router(ws_station.router)
