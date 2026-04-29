"""
Publica comandos al ESP32. El firmware se suscribe a `{SN}/command` y
`{SN}/user_name`. Comandos válidos: found, nofound, unload, prestado,
devuelto, refused, nologin (ver mode_prestamo.cpp).
"""

from __future__ import annotations

import logging
from typing import TYPE_CHECKING

if TYPE_CHECKING:
    from aiomqtt import Client

log = logging.getLogger(__name__)


class MQTTPublisher:
    def __init__(self) -> None:
        self._client: "Client | None" = None

    def attach(self, client: "Client") -> None:
        self._client = client

    def detach(self) -> None:
        self._client = None

    @property
    def connected(self) -> bool:
        return self._client is not None

    async def send_command(self, sn: str, command: str) -> None:
        if not self._client:
            log.warning("MQTT no conectado al publicar command=%s sn=%s", command, sn)
            return
        await self._client.publish(f"{sn}/command", command, qos=0)
        log.info("📤 %s/command -> %s", sn, command)

    async def send_user_name(self, sn: str, name: str) -> None:
        if not self._client:
            log.warning("MQTT no conectado al publicar user_name sn=%s", sn)
            return
        await self._client.publish(f"{sn}/user_name", name, qos=0)
        log.info("📤 %s/user_name -> %s", sn, name)


publisher = MQTTPublisher()
