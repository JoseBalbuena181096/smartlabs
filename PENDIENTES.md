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
| HW-OTA | Media | OTA (Over-The-Air updates) con `ArduinoOTA` o `ESPhttpUpdate`. Hoy se flashea por USB. Con 4-5 estaciones cuesta tiempo cada vez. |
| HW-NVS | Media | `secrets.h` requiere recompilar al cambiar SSID/MQTT. Reemplazar por NVS / `Preferences` con un portal de configuración WiFi (WiFiManager) en primer arranque. |
| HW-PIO | Media | Refactor a un solo firmware con PlatformIO + `build_flags` por modo (4 envs). Hoy hay duplicación de ~80% entre los 4 `esp32_*.cpp`. |
| HW-BUILTIN | Baja | Confirmar que `BUILTIN_LED` (GPIO2 en ESP32 DevKit) no entra en conflicto con strapping al arrancar. |
| ~~HW-UNIF~~ | ~~A definir~~ | ~~Unificar `esp32_prestamo_lector_USUARIO.cpp` + `esp32_prestamo_lector_HERRAMIENTA.cpp`.~~ **HECHO** (2026-04-27): `esp32_prestamo_lector_UNIFICADO.cpp` validado en docker. Ver `CHANGELOG.md`. |

## 2. Backend Node.js (`www/flutter-api/`)

| ID | Severidad | Pendiente |
|---|---|---|
| BE-A | **Crítico** | **Decisión de ops**: reconciliar credenciales MQTT entre `.env` raíz (`smartlabs / smartlabs_mqtt_2024`), `flutter-api/.env` (`jose / public`) y `secrets.h` del firmware. Una sola autentica contra EMQX. |
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

## 4. HW-UNIF · Unificar lector USUARIO + HERRAMIENTA — HECHO (2026-04-27)

Resuelto en `esp32_prestamo_lector_UNIFICADO.cpp` con las siguientes
decisiones tomadas:

1. **Hardware físico**: una sola caja. SN `SMART10003` reusado, IP `.34`
   conservada. SN `SMART10002` queda libre para futuras estaciones.
2. **Quién diferencia credencial vs herramienta**: el backend. El firmware
   solo sabe si tiene sesión.
3. **Credencial ajena durante sesión activa**: rechazar (`refused`).
4. **Timeout de inactividad**: 180 s, reseteado en cada interacción
   (préstamo, devolución, refused, nofound).
5. **Topic MQTT**: se mantuvieron los dos. Sin sesión → `loan_queryu`. Con
   sesión → `loan_querye`. El backend resuelve los tres casos en
   `handleLoanEquipmentQuery`. Cero cambios en el contrato MQTT respecto a
   clientes externos.

---

## Bloques sugeridos para próximos commits

1. **Bloque hardware**: HW-UNIF + HW-PIO (refactor a uno solo + PlatformIO).
2. **Bloque ops**: BE-A reconciliación de credenciales MQTT (requiere
   decisión externa).
3. **Bloque PHP**: PHP-L (entry point único) + PHP-W (N+1) + PHP-V
   (eliminar SQL inline).
4. **Bloque observabilidad**: BE-L endpoint de estado + BE-G eliminar
   polling.
