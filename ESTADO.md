# SMARTLABS · Estado del sistema (snapshot 2026-04-29)

Coexisten dos stacks en el repo. El **legacy** (`www/`) sigue vivo para no
romper lo que ya estaba en producción. El **v3** (`www-v3/`) es la
reescritura limpia para almacén automatizado de herramientas y es el camino
hacia adelante.

```
smartlabs/
├── www/          ← legacy: PHP + Node.js + MariaDB (parchado)
├── www-v3/       ← nuevo: Vue 3 + FastAPI + Postgres
├── RED.md        ← guía portproxy WSL2 + ports
├── PENDIENTES.md ← backlog del legacy
└── ESTADO.md     ← este archivo
```

---

## 1. Stack legacy (`www/`)

PHP MVC + Node.js Express (`flutter-api`) + MariaDB + EMQX + nginx + device
monitor. Diseñado para préstamo de **mesas/máquinas** + becarios. Sigue
arriba pero el caso de uso real cambió — pasa a modo "mantenimiento, no
desarrollo".

### Cambios aplicados en sesiones recientes (2026-04-27 → 2026-04-29)

**Hardware/firmware**:
- HW-UNIF, HW-PIO, HW-OTA, HW-BUILTIN: firmware unificado en
  `www/hadware/esp32/` con 3 envs PlatformIO (`becarios`/`maquinas`/
  `prestamo`) compartiendo `src/main.cpp`. Arduino lector universal en
  `www/hadware/arduino/lector_universal/` con MIFARE Classic + NTAG21x.
- **Fix parser UART** (2026-04-29): el ESP32 descartaba UIDs con ≤ 9
  caracteres (`if (len > 9)` → ahora `if (len >= 4)`), por eso tags con
  bytes pequeños "hacían pic" en el Arduino pero nunca llegaban al broker.
  `www/hadware/esp32/src/main.cpp:133`. Reflasheado en COM8.
- HW-RC522 (pendiente físico): el módulo RC522 actual es chip clon FM17522
  que solo lee MIFARE Classic 1K, no NTAG/Ultralight. Stickers MIFARE
  Classic 1K UID-changeable comprados en AliExpress (listing
  `1005005066403027`).

**Backend / web**:
- BE-A: credenciales MQTT reconciliadas a `jose/public` en 7 archivos.
- PHP-DASH-EXT: `DashboardController` colgaba 60-180 s por 4 conexiones
  fallidas a una "BD externa" muerta en `:4000`. `mysqli` en Linux ignora
  `ini_set('mysqli.connect_timeout')`. Cortocircuitado con `throw new
  Exception` antes de cada `new mysqli(...:4000)` para caer al fallback
  local. Dashboard ahora ~25 ms.
- PHP-WS-TYPO: 3 archivos JS/PHP usaban `wss://localhost:8074/mqtt`
  (puerto inexistente, sin TLS). Cambiado a `ws://localhost:8083/mqtt`.
- CSRF + form fixes en `/Habitant`, `/Loan`: el form de registro no
  embebía `_csrf` ni mandaba `device_id`/`device_serie`. Arreglado para
  que registro de usuarios y consulta de préstamos funcionen.
- `LoanController` AJAX `consult_loan` ahora envía `_csrf` (antes 403
  redirigía al login).

**Ops / red** (documentado en `RED.md`):
- 5 reglas `netsh portproxy` Windows → WSL2 (ports 80, 1883, 8083, 8086,
  3000) para que clientes LAN y otros PC puedan llegar al stack docker.
- Reglas firewall inbound TCP.
- Script de re-arme cuando la IP de WSL cambia tras reboot.
- Opción de `networkingMode=mirrored` en `~/.wslconfig` documentada como
  solución limpia.

### Cómo levantar legacy

```bash
cd www
docker compose up -d
docker compose stop smartlabs-flutter-api   # si quieres dejar libre el broker para v3
```

URLs:
- PHP web: `http://localhost/` (puerto 80, también `:8000`)
- phpMyAdmin: `http://localhost:8080`
- EMQX dashboard: `http://localhost:18083`
- flutter-api: `http://localhost:3000/health`

---

## 2. Stack nuevo v3 (`www-v3/`)

Reescritura para **almacén automatizado de herramientas con credencial NFC**.
Vue 3 (Vuetify + Vite) + FastAPI (uvicorn) + Postgres 16 + EMQX (compartido
con legacy) + nginx. Despliegue local-only por campus (sin dependencias
cloud — los puestos no tienen internet libre).

### Por qué reescribir

El legacy fue diseñado para préstamo de mesas/máquinas/becarios; arrastra
SQL inline, "BD externa" muerta, OPcache bugs, CSRF inconsistente, race
conditions browser ↔ Node ↔ broker, JS legacy con typos. Más fácil
reescribir que parchar.

### Plan completo

`/home/jose/.claude/plans/tingly-inventing-gadget.md` (aprobado por usuario,
ejecutado al 100% en sesión 2026-04-29).

### Arquitectura

```
   ESP32 ─MQTT 1883→ EMQX ←─pub/sub─→ FastAPI (uvicorn:8000)
                                       │      │
                                       │      └─ WS broadcaster
                                       └─ asyncpg → Postgres 16
                                                       ▲
                                       Vue SPA ←nginx─┘ (puertos 8090 público)
```

### Modelo de datos (Postgres)

9 tablas con `campus_id` para multi-campus futuro:
- `campus`, `areas`, `users`, `tools`, `stations`
- `loan_sessions` (índice parcial: una sesión abierta por estación)
- `loans` (FK a sesión, con `due_at` configurable)
- `inventory_runs` (índice parcial: un inventario abierto por campus)
- `inventory_scans`

### Contrato MQTT (preservado del legacy, no se cambia)

Topics que el ESP32 publica:
- `{SN}/loan_queryu` — UID escaneado sin sesión local
- `{SN}/loan_querye` — UID escaneado con sesión local activa
- `{SN}/status` — `online`/`offline` retained QoS 1 (con LWT)

Comandos que el backend publica a `{SN}/command`:
`found`, `nofound`, `unload`, `prestado`, `devuelto`, `refused`,
`nologin`. Plus `{SN}/user_name` para mostrar en pantalla del lector.

### Funcionalidad implementada (sesión 2026-04-29)

- **CRUD usuarios** con NFC, email, área, nómina, rol.
- **CRUD herramientas** con marca, modelo, RFID, ubicación, descripción.
  Retiro = soft-delete + cierre automático de préstamos activos asociados
  con `return_reason='tool_deleted'`.
- **Sesión de préstamo automática** vía MQTT: scan credencial → abre,
  scan tag herramienta → presta, scan mismo tag → devuelve, scan
  credencial otra vez → cierra. Timeout firmware (180 s) → cierre por
  inactividad.
- **Multi-estación**: cada ESP32 con su `device_serie` se auto-registra al
  publicar `status=online`. UI admin muestra estaciones online/offline.
- **Vista kiosko `/station/{SN}`** sin login — la pantalla que vive junto
  al lector. Muestra usuario activo, **lista en vivo de "equipos en
  posesión"** (incluyendo préstamos previos pendientes), banner temporal
  "Acabas de prestar/devolver" cuando hay scan, y limpia al cerrar sesión.
- **Vista admin `/admin/loans`** con filtros abierto/cerrado/todos y
  devolución manual con razón.
- **Modo inventario**: admin abre run, los scans van a `inventory_scans`,
  el ESP32 recibe `nologin` mientras dura, al cerrar reporte de
  faltantes (catálogo - escaneadas) y desconocidos (escaneadas no en
  catálogo).
- **Captura RFID desde hardware para registro**: forms admin "Nueva
  herramienta" / "Nuevo usuario" abren WS con `?capture=tag`. Cualquier
  tag escaneado en cualquier estación que NO esté en catálogo dispara
  `tag.unknown` que rellena el campo RFID + toast verde.
- **WebSocket en vivo**: `/ws/admin` (eventos de todo el campus, JWT auth)
  y `/ws/station/{SN}` (eventos solo de esa estación, sin auth).

### Cómo levantar v3

```bash
cd www-v3
cp .env.example .env
docker compose up -d --build
docker compose exec backend alembic upgrade head
docker compose exec backend python -m app.scripts.seed_admin \
    --email admin@admin.com --password Admin123! \
    --name "Admin Local" --rfid "ADMIN-INIT-3"
```

URLs:
- Admin UI: **http://localhost:8090/login**
  - Usuario: `admin@admin.com` / `Admin123!`
- API health: `http://localhost:8090/api/health`
- Estación kiosko: `http://localhost:8090/station/SMART10003`
- Backend directo: `http://localhost:8001`
- Postgres expuesto: `localhost:5433`

> Si el legacy tiene el flutter-api arriba, conviene **detenerlo**
> (`cd www && docker compose stop smartlabs-flutter-api`) durante pruebas
> v3 para evitar respuestas duplicadas al ESP32.

### Validación end-to-end realizada

Pasados todos los casos:

1. Login JWT con admin sembrado.
2. Crear área → crear usuario `Jesus Balbuena` con RFID `1381932005`.
3. Crear herramienta `Bosch GSR-12` con RFID `AABBCC01`.
4. Auto-creación de estación `SMART10003` cuando llega el `status` retained.
5. Captura RFID desde hardware: tag `21117601105` y `553523100` capturados
   por el form abierto.
6. Préstamo: scan credencial → `session.opened` con `active_loans=[]`,
   scan tag → `loan.created`, `prestado` publicado al ESP32.
7. Devolución scan: tag de nuevo → `loan.returned reason='scan'`.
8. Retiro de herramienta con préstamo activo → `loan.returned
   reason='tool_deleted'` automático.
9. Cierre de sesión: scan credencial otra vez → `unload` + clear vista.
10. **Persistencia entre sesiones**: si un usuario tiene préstamo abierto y
    cierra sesión, al volver a abrir ve el equipo como "Pendiente" desde
    el primer momento.

### Estado físico del hardware

- ESP32 SMART10003 reflasheado con fix del parser UART. Sigue conectado a
  la red WiFi `IronMakers` y al broker EMQX (`192.168.0.100:1883` via
  portproxy).
- Arduino Nano + RC522 (clone FM17522) en COM10. Solo lee MIFARE Classic
  1K confiable. NTAG/Ultralight pendiente de stickers nuevos.

---

## 3. Pendientes (próxima sesión)

### Bloqueantes operativos
- [ ] Llegar stickers MIFARE Classic 1K de AliExpress, registrar las
      herramientas físicas reales, etiquetarlas con su marca/modelo
      desde `/admin/tools` con captura por hardware.
- [ ] Decidir si v3 toma puertos canónicos (80, 1883, 5432) y se archiva
      el legacy en `www-legacy/`, o conviven más tiempo.

### Mejoras v3 (priorizadas por valor)
1. **Auth de captura sin admin**: hoy `/admin/users/new` y
   `/admin/tools/new` requieren admin para abrir el WS de captura. Si
   queremos que un becario pueda registrar sin pasar por admin, hay que
   añadir rol `staff_register` o permitir captura sin token.
2. **Búsqueda full-text**: ya hay índice GIN spanish en `users` y `tools`
   pero el cliente Vue solo usa `q` con LIKE básico. Cambiar a
   `to_tsquery` para mejor relevancia.
3. **Notificaciones de vencimiento**: tabla `loans` tiene `due_at`. Falta
   un job que detecte préstamos vencidos y los marque/avise.
4. **Importación legacy**: endpoint `POST /api/import/legacy` que reciba
   CSV exportado de `cards` + `habintants` del MariaDB legacy y los
   inserte en Postgres mapeando RFID + nombre. Útil cuando se haga
   cutover.
5. **Multi-campus real**: hoy `campus_id=1` hardcoded. Cuando se requiera,
   agregar header `X-Campus-Id` + filtros en queries + UI selector.
6. **Tests**: cero tests automatizados aún. Mínimo: pytest para handlers
   MQTT (mock broker), e2e Playwright para Vue.

### Mejoras pendientes legacy (si se sigue usando)
Ver `PENDIENTES.md` (BE-C, BE-G, BE-J, BE-K, BE-L, PHP-L, PHP-V, PHP-W,
PHP-R, PHP-U, PHP-X, HW-NVS, HW-HEAP, HW-WD).

---

## 4. Cambios del día 2026-04-29

| # | Lo que se hizo | Archivos clave |
|---|---|---|
| 1 | Diseño + plan completo del stack v3 | `~/.claude/plans/tingly-inventing-gadget.md` |
| 2 | Backend FastAPI con MQTT bridge, REST, WebSocket | `www-v3/backend/app/**/*.py` |
| 3 | Postgres schema + alembic migration | `www-v3/backend/alembic/versions/0001_initial.py` |
| 4 | Frontend Vue 3 + Vuetify (login + 6 vistas admin + kiosko) | `www-v3/frontend/src/**/*.vue` |
| 5 | nginx + docker-compose paralelo al legacy | `www-v3/{nginx,docker-compose.yml}` |
| 6 | Switch passlib → bcrypt directo (compat 4.x) | `www-v3/backend/app/security.py` |
| 7 | Switch asyncio-mqtt → aiomqtt (API moderna) | `www-v3/backend/app/mqtt/{client,publisher}.py` |
| 8 | Captura RFID desde hardware en forms admin | `handlers.py` `tag.unknown` + `Tools.vue`/`Users.vue` |
| 9 | Vista kiosko mejorada: "equipos en posesión" persistente | `www-v3/frontend/src/views/station/Station.vue` |
| 10 | Fix parser UART ESP32 (`len > 9` → `len >= 4`) | `www/hadware/esp32/src/main.cpp:133` |

**Verificación end-to-end ejecutada** — todos los casos del plan pasaron
con datos reales (ESP32 físico SMART10003 publicando, scans reales de
tags `1381932005`, `21117601105`, `553523100`, `AABBCC01`, `AABBCC02`).
