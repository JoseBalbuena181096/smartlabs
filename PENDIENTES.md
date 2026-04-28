# SMARTLABS · Pendientes

Lista priorizada de mejoras que **no** se aplicaron en los commits del bloque
2026-04-26/27. Se mantienen aquí para tomarlas en bloques posteriores. Cada
ítem incluye el ID interno (PHP-X / BE-X / HW-X) usado en el resto de la doc.

> **Recomendación**: leer junto con `CHANGELOG.md` para no duplicar esfuerzo
> con lo ya hecho.

---

## 1. Hardware (firmware ESP32 / Arduino RC522)

| ID | Severidad | Pendiente |
|---|---|---|
| ~~HW-OTA~~ | ~~Media~~ | ~~OTA con `ArduinoOTA` o `ESPhttpUpdate`.~~ **HECHO** (2026-04-27): `ArduinoOTA` integrado en `esp32/src/main.cpp`. Hostname `{SN}.local`, password opcional desde `SECRETS_OTA_PASSWORD`. Subir con `pio run -e <env> -t upload --upload-port <SN>.local`. |
| HW-NVS | Media | `secrets.h` requiere recompilar al cambiar SSID/MQTT. Reemplazar por NVS / `Preferences` con un portal de configuración WiFi (WiFiManager) en primer arranque. |
| ~~HW-PIO~~ | ~~Media~~ | ~~Refactor a un solo firmware con PlatformIO + `build_flags` por modo (4 envs).~~ **HECHO** (2026-04-27): `www/hadware/esp32/` es un proyecto PlatformIO con 3 envs (`becarios`/`maquinas`/`prestamo`) que comparten `src/main.cpp`. Cada `mode_<x>.cpp` queda activo via `#ifdef MODE_<X>`. ~80 % de duplicación eliminada. |
| ~~HW-BUILTIN~~ | ~~Baja~~ | ~~Confirmar que `BUILTIN_LED` (GPIO2 en ESP32 DevKit) no entra en conflicto con strapping al arrancar.~~ **HECHO** (2026-04-27): `PIN_LED` ahora es definible via `-DPIN_LED=<n>` en `platformio.ini`. Default 2 (DevKit V1, sin issues observados). Comentario explicativo en `main.cpp`. |
| ~~HW-UNIF~~ | ~~A definir~~ | ~~Unificar `esp32_prestamo_lector_USUARIO.cpp` + `esp32_prestamo_lector_HERRAMIENTA.cpp`.~~ **HECHO** (2026-04-27): vive ahora en `esp32/src/mode_prestamo.cpp`. Validado en docker. |

Nuevos pendientes de hardware (post HW-PIO):

| ID | Severidad | Pendiente |
|---|---|---|
| HW-RC522 | **Alta** | El módulo RC522 actual reporta versión `0x82` (chip clon FM17522 o similar). No detecta tags **Mifare Ultralight** ni con WUPA, antena al máximo, SPI 1 MHz, reset periódico ni antenna cycle (probadas todas el 2026-04-27 sin éxito). Lee perfecto MIFARE Classic 1KB. **Acción**: conseguir un módulo RC522 con chip NXP auténtico (versión `0x91`/`0x92`) — en MercadoLibre busca "RC522 NXP original". Alternativa más cara pero industrial: PN532 (~$120 MXN). Mientras tanto, las herramientas pueden marcarse con tarjetas MIFARE en lugar de stickers Ultralight. |
| HW-HEAP | Baja | Publicar heap libre periódico a `{SN}/heap` para detectar fugas en producción. |
| HW-WD   | Baja | Watchdog de aplicación: si pasan N minutos sin loop completo, `ESP.restart`. `PubSubClient` ya cubre el caso de socket muerto pero no el de un mode_*.cpp colgado en `delay()`. |

## 1.b Despliegue en WSL2 (Windows)

| ID | Severidad | Pendiente |
|---|---|---|
| OPS-WSL | Media | Si el broker EMQX se levanta en docker dentro de WSL2, los firmwares ESP32 (que viven en otra red WiFi) no llegan a `192.168.0.100:1883` por sí solos — Windows no reenvía ese puerto a WSL2 por defecto. **Workaround aplicado el 2026-04-27**: <br>`netsh interface portproxy add v4tov4 listenaddress=192.168.0.100 listenport=1883 connectaddress=<WSL2_IP> connectport=1883`<br>`netsh advfirewall firewall add rule name="MQTT 1883 in" dir=in action=allow protocol=TCP localport=1883`<br>(ejecutar una vez como Admin). El `<WSL2_IP>` cambia con cada reinicio de WSL si no se usa `[wsl2] networkingMode=mirrored` en `~/.wslconfig`. **Pendiente**: configurar mirrored mode en `~/.wslconfig` para una solución sin port-proxy, o documentar el script de re-arme automático. |

## 2. Backend Node.js (`www/flutter-api/`)

| ID | Severidad | Pendiente |
|---|---|---|
| ~~BE-A~~ | ~~**Crítico**~~ | ~~Reconciliar credenciales MQTT entre `.env` raíz, `flutter-api/.env` y `secrets.h`.~~ **HECHO** (2026-04-27): unificado a `jose / public` (la unica cred existente en `mqtt_user` de MariaDB). 7 archivos alineados (firmware secrets.h, ambos `.env`, ambos `.env.example`, `docker-compose.yml`, `flutter-api/.env`). |
| BE-C | Alta | Lock/transacción explícita en `loan_sessions` para entornos con múltiples instancias del flutter-api detrás de un balanceador. La PK ya da atomicidad para INSERT, pero no para read-then-update. |
| BE-G | Media | `device-status/server.js` polea la tabla `traffic` cada 5 s con `GROUP BY ... MAX(traffic_date)`. Reemplazar por subscribe MQTT directo o triggers DB. |
| BE-J | Baja | Limpiar comentarios "del servidor IoT Node.js anterior" que quedaron tras la migración a flutter-api. |
| BE-K | Baja | El `mqttListenerService.stopListening()` no des-suscribe del singleton; solo marca `isListening=false`. Si se desactivara con frecuencia conviene `mqttClient.unsubscribe(...)`. |
| BE-L | Baja | Agregar endpoint REST `/api/devices/status` que lea `device_status` para que la UI/admin muestre estaciones online/offline. |

## 3. PHP (`www/app/`)

| ID | Severidad | Pendiente |
|---|---|---|
| PHP-L | Alta | Tres puntos de entrada coexistiendo (`index.php` raíz, `public/index.php`). Decidir uno y forzar `.htaccess`. Hoy si entras por `/public/`, `app.php` cae a defaults. |
| PHP-W | Media | `LoanController::consultarPrestamos` hace N+1: por cada préstamo activo dispara un SELECT extra para verificar devolución posterior. Reescribir con LEFT JOIN o subquery. |
| PHP-V | Media | Varios controllers hacen SQL inline en lugar de pasar por el modelo (`HabitantController::index`, `LoanAdminController::*`). Inconsistencia estructural. |
| PHP-R | Baja | `Router` no whitelistea controllers. El sufijo `Controller.php` + `file_exists` mitigan path traversal pero conviene una lista explícita. |
| PHP-U | Baja | `Database::query` requiere `mysqlnd` (`fetch_all`). Si el deploy no lo tiene, falla raro. |
| PHP-X | Baja | `auth/login.php` hardcoded a `/Auth/register` aunque ese endpoint ahora requiere auth (PHP-C). Actualizar el copy o esconder el link si no estás logueado. |

---

## Bloques sugeridos para próximos commits

1. ~~**Bloque hardware**: HW-UNIF + HW-PIO~~ (ya hechos). Siguiente bloque hardware lógico = **HW-NVS** (portal de configuración WiFi).
2. **Bloque ops**: BE-A reconciliación de credenciales MQTT (requiere
   decisión externa).
3. **Bloque PHP**: PHP-L (entry point único) + PHP-W (N+1) + PHP-V
   (eliminar SQL inline).
4. **Bloque observabilidad**: BE-L endpoint de estado + BE-G eliminar
   polling + HW-HEAP métrica de heap.
