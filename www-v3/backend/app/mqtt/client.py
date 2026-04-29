"""
Cliente MQTT con aiomqtt. Se suscribe a +/loan_queryu, +/loan_querye y
+/status, y delega al dispatcher de handlers. Reconecta solo si la conexión
se cae.
"""

from __future__ import annotations

import asyncio
import logging

from aiomqtt import Client, MqttError

from ..settings import settings
from . import handlers
from .publisher import publisher

log = logging.getLogger(__name__)

TOPICS = ["+/loan_queryu", "+/loan_querye", "+/status"]


async def _run_once() -> None:
    async with Client(
        hostname=settings.mqtt_host,
        port=settings.mqtt_port,
        username=settings.mqtt_user,
        password=settings.mqtt_password,
        identifier=settings.mqtt_client_id,
    ) as client:
        publisher.attach(client)
        log.info(
            "✅ MQTT conectado %s:%s (user=%s)",
            settings.mqtt_host,
            settings.mqtt_port,
            settings.mqtt_user,
        )
        try:
            for topic in TOPICS:
                await client.subscribe(topic, qos=1)
                log.info("📡 suscrito %s", topic)
            async for message in client.messages:
                try:
                    topic_str = (
                        message.topic.value
                        if hasattr(message.topic, "value")
                        else str(message.topic)
                    )
                    await handlers.dispatch(topic_str, message.payload)
                except Exception as exc:
                    log.exception("error en handler: %s", exc)
        finally:
            publisher.detach()


async def run_forever() -> None:
    """Loop con reconnect-backoff. Se llama desde el lifespan de FastAPI."""
    delay = 1.0
    while True:
        try:
            await _run_once()
            delay = 1.0
        except MqttError as exc:
            log.warning("MQTT desconectado: %s; reintentando en %.1fs", exc, delay)
        except asyncio.CancelledError:
            raise
        except Exception as exc:
            log.exception("MQTT error inesperado: %s", exc)
        await asyncio.sleep(delay)
        delay = min(delay * 2, 30.0)
