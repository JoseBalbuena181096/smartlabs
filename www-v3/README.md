# SmartLabs v3 — Almacén inteligente de herramientas

Stack nuevo en paralelo al `www/` legacy. Vue 3 + FastAPI + Postgres 16 +
EMQX + nginx, todo en docker compose. Despliegue local-only por campus.

> Plan completo: ver `/home/jose/.claude/plans/tingly-inventing-gadget.md`.

## Levantar

```bash
cd www-v3
cp .env.example .env
docker compose up -d --build
docker compose exec backend alembic upgrade head
docker compose exec backend python -m app.scripts.seed_admin \
    --email admin@local --password Admin123!
```

UI: http://localhost:8090
API: http://localhost:8090/api/health

## Apagar el stack viejo durante cutover

El nuevo backend escucha el MISMO broker EMQX que el viejo `flutter-api`.
Para que el ESP32 no reciba doble respuesta:

```bash
cd ../www
docker compose stop smartlabs-flutter-api
```

## Migración schema y rollback

```bash
docker compose exec backend alembic upgrade head        # aplicar
docker compose exec backend alembic downgrade -1        # revertir 1
docker compose exec backend alembic revision --autogenerate -m "msg"
```

## Estructura

```
backend/    FastAPI + alembic + MQTT bridge
frontend/   Vue 3 + Vuetify + Vite (build estático servido por nginx)
nginx/      reverse proxy SPA + /api + /ws
```
