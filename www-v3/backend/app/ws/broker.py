"""
In-process pub/sub para fan-out de eventos a clientes WebSocket.

El backend publica eventos a un canal lógico (`admin`, `station:<SN>`,
`capture`) y cada conexión WS abre una `asyncio.Queue` que recibe solo los
eventos del canal al que se suscribió.

Sin Redis. Single-process. Si en algún momento el deployment escala a
múltiples replicas del backend, este módulo se reemplaza por Redis pub/sub
sin tocar callers.
"""

from __future__ import annotations

import asyncio
from collections import defaultdict
from typing import Any


class EventBroker:
    def __init__(self) -> None:
        # canal -> set de queues
        self._channels: dict[str, set[asyncio.Queue[dict[str, Any]]]] = defaultdict(set)
        self._lock = asyncio.Lock()

    async def subscribe(self, channel: str) -> asyncio.Queue[dict[str, Any]]:
        q: asyncio.Queue[dict[str, Any]] = asyncio.Queue(maxsize=256)
        async with self._lock:
            self._channels[channel].add(q)
        return q

    async def unsubscribe(self, channel: str, q: asyncio.Queue[dict[str, Any]]) -> None:
        async with self._lock:
            self._channels[channel].discard(q)
            if not self._channels[channel]:
                self._channels.pop(channel, None)

    async def publish(self, channel: str, event: dict[str, Any]) -> None:
        async with self._lock:
            queues = list(self._channels.get(channel, ()))
        for q in queues:
            try:
                q.put_nowait(event)
            except asyncio.QueueFull:
                # consumidor lento: descarta evento más viejo y mete el nuevo
                try:
                    q.get_nowait()
                except asyncio.QueueEmpty:
                    pass
                try:
                    q.put_nowait(event)
                except asyncio.QueueFull:
                    pass

    async def publish_many(self, channels: list[str], event: dict[str, Any]) -> None:
        for ch in channels:
            await self.publish(ch, event)

    def client_count(self) -> int:
        return sum(len(qs) for qs in self._channels.values())


broker = EventBroker()
