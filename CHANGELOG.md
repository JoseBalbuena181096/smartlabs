# SMARTLABS · Changelog

Cambios agrupados por capa. Los commits están ordenados como aparecen en
`git log --oneline`. Cada entrada referencia el ID interno del bug
(p.ej. `PHP-A`) que se mantiene en `www/migrations/README.md` y notas
internas para trazabilidad.

## 2026-04-26 — 2026-04-27

### Hardware (`www/hadware/`)

| Commit | Cambios |
|---|---|
| `14ac797` | **Lector universal MIFARE+NTAG.** `arduino_lector_universal.ino` reemplaza al sketch viejo que solo leía MIFARE Classic 4-byte UID. Ahora soporta NTAG21x 7-byte (las pegatinas de herramienta nunca se leían antes). Mismo formato de salida hacia el ESP32 → no requiere cambios en BD. Renombrados los `main_*.cpp` a `esp32_<rol>.cpp` para que sea evidente cuál archivo va en cada estación. Versiones obsoletas con prefijo `_DEPRECATED_`. |
| `b632a02` | **Refactor de los 4 ESP32 activos** preservando 100% el contrato MQTT: credenciales en `secrets.h` (gitignored), `char[]` en RTOS task en lugar de `String` (causa raíz de heap fragmentation y watchdog crashes), `QueueHandle_t` para pasar UID entre cores, `WiFi.begin` con timeout y `ESP.restart` si el AP cae, MQTT Last Will (`{SN}/status` retained `online`/`offline`), reconexión MQTT no bloqueante, fuera `temprature_sens_read()` deprecated, OLED corregido (`TARGETA → TARJETA`). Plantilla `secrets.h.example`. |

### Backend Node.js (`www/flutter-api/`)

| Commit | Cambios |
|---|---|
| `ff4eeb2` | **`prestamoService.handleLoanUserQuery` publica `nofound`.** Antes solo logueaba cuando el RFID no existía y el ESP32 quedaba colgado en "ESPERA ENVIANDO AL SERVER" hasta el timeout local. Comentario zombie del "servidor IoT legacy" eliminado. |
| `c8382f3` | **Cliente MQTT y pool DB unificados, sesión persistida, status retained.** <br>• **BE-D**: `prestamoService` y `mqttListenerService` usan el singleton `config/mqtt.js` (que ya existía pero estaba ignorado). Una sola conexión al broker. <br>• **BE-F**: ambos servicios usan `dbConfig.execute()` del pool (también ignorado). Antes abrían `mysql.createConnection` por método. <br>• **BE-B**: estado de sesión de préstamo (`countLoanCard`/`serialLoanUser`) que vivía en RAM se persiste en `loan_sessions` (migración 002). Sobrevive reinicios y se atomiza por PRIMARY KEY. <br>• **BE-E**: el listener escucha `+/status` y registra online/offline en `device_status` (migración 003). Para que la UI/admin vea estaciones caídas. <br>• **BE-H**: `handleSensorData` huérfano eliminado. <br>• **BE-I**: `procesarPrestamo` ya no publica `APP:rfid` (zombie del filtro). |

### PHP (`www/app/`, `www/config/`)

| Commit | Cambios |
|---|---|
| `eb41396` | **Lógica de préstamos unificada y errores SQL como excepciones.** <br>• `Loan::returnLoan` ahora hace INSERT con `state=0` (consistente con append-only que el resto del sistema asume; antes hacía UPDATE que rompía auditoría). <br>• `Loan::getActiveLoans` y `getActiveLoanByEquipment` consideran el último estado real por equipo; antes filtraban `state=1` sin verificar si después hubo devolución. <br>• `Database` activa `mysqli_report STRICT` → errores SQL son excepciones, los `try/catch` existentes funcionan, ya no hay `die()` que fugue el SQL al usuario. <br>• Soporta `bool` en `bind_param` (mapeado a `'i'` con cast a int). <br>• `LoanAdminController::devolverPrestamo` valida que exista préstamo activo y que el usuario sea el correcto antes de insertar. <br>• Helper `jsArg()` cierra el XSS en los `onclick` que interpolaban RFIDs sin escape. |
| `e46813f` | **Bcrypt + rate limit + CSRF base + register cerrado + sesión sana.** <br>• **PHP-A**: `User::create/authenticate/updatePassword` usan `password_hash(BCRYPT)`. `authenticate` acepta hashes SHA1 legacy y los rehash perezosamente. <br>• **PHP-E**: `logout` limpia `$_SESSION`, expira la cookie con `setcookie(time-42000)` y luego `session_destroy`. <br>• **PHP-C**: `Auth/register` requiere `requireAuth` (antes lo usaba cualquiera). <br>• **PHP-K**: email lowercase + `LOWER(users_email)` en query. <br>• **PHP-Q**: rate limit 5 intentos / 5 min. <br>• **PHP-P**: `$_SESSION['devices']` carga `findByUserId`, no `getAll`. <br>• **PHP-D**: `gc_probability=1/100`, `gc_maxlifetime=12h` (antes 68 años). <br>• **PHP-O**: `Controller::sanitize` ya no aplica `htmlspecialchars` en input. Helper `Controller::e()` para output. <br>• **PHP-B**: `csrfToken()` y `verifyCsrf()` aplicado en `auth/login.php`. |
| `474e0bd` | **CSRF en POST, role admin, DB desde .env, reconnect, fixes Habitant/Loan.** <br>• **PHP-B**: `verifyCsrf()` en cada POST handler de Auth/Device/Loan/LoanAdmin/Habitant. Token expuesto en `<meta>` y `<input hidden>`. `footer.php` inyecta `X-CSRF-Token` automáticamente en `jQuery.ajaxSetup` y `window.fetch`. <br>• **PHP-G**: `users_role` (migración 001), helper `requireAdmin`, aplicado a `LoanAdminController::exportarCSV` y `generarCSVJSON`. <br>• **PHP-H**: POST-redirect-GET en `HabitantController::index` (antes refrescar duplicaba el form). <br>• **PHP-I**: `hab_device_id` desde el form (validado contra `devices`), no hardcoded a `'1'`. <br>• **PHP-J**: `searchByRFID` solo busca el RFID exacto en `cards` (antes hacía LIKE en nombre/email como fallback → falsos positivos). <br>• **DeviceController**: create/edit/delete validan ownership antes de modificar. <br>• **PHP-M**: `config/database.php` lee de `$_ENV` con fallbacks. <br>• **PHP-N**: `Database::ensureAlive` hace ping y reconecta si el socket murió (típico tras 8h por `wait_timeout`). <br>• **PHP-T**: borrados `public/test.php` y `html/devices.php` (demos legacy). |

### Migraciones SQL (`www/migrations/`)

| ID | Archivo | Para |
|---|---|---|
| 001 | `2026-04-26_001_users_role.sql` | PHP-G — diferenciar admin de user |
| 002 | `2026-04-26_002_loan_sessions.sql` | BE-B — sesión persistida |
| 003 | `2026-04-26_003_device_status.sql` | BE-E — registrar online/offline LWT |

Aplicar manualmente:
```bash
for f in www/migrations/2026-04-26_*.sql; do
  docker exec -i smartlabs-mariadb mariadb -uemqxuser -pemqxpass emqx < "$f"
done
```

## Validación

Probado end-to-end con `docker compose up`:

1. **MariaDB** (con migraciones aplicadas) ✅
2. **EMQX** broker en `localhost:1883` ✅
3. **flutter-api**:
   - Pool MySQL conectado ✅
   - MQTT singleton conectado, suscrito a `+/loan_queryu`, `+/loan_querye`, `+/access_query`, `+/scholar_query`, `+/status` ✅
4. **Flujo MQTT**:
   - `mosquitto_pub -t SMART10003/loan_queryu -m '5242243191'` →
     - Backend identifica `Jose Angel Balbuena Palma`
     - Publica `SMART10003/user_name` y `SMART10003/command: found`
     - Inserta en `loan_sessions` con `expires_at = +150s` ✅
   - `mosquitto_pub -t SMART10002/loan_querye -m '832102211121'` →
     - Backend identifica `BROCAS 1`
     - Inserta en `loans` con `state=1`
     - Publica `SMART10002/command: prestado` ✅
   - `mosquitto_pub -t SMART10003/status -m 'online' -r` →
     - Backend registra en `device_status` ✅
5. **PHP Auth**:
   - GET `/Auth/login` devuelve token CSRF en cookie + meta + form hidden ✅
   - POST sin token → `403` ✅
   - POST con token y password SHA1 legacy → `302 → /Dashboard` y el hash se rehashea a `$2y$...` (bcrypt 60 chars) en la BD ✅

## 2026-04-27 (post)

### HW-UNIF · firmware unificado de préstamo

| Commit | Cambios |
|---|---|
| (siguiente) | **`esp32_prestamo_lector_UNIFICADO.cpp` reemplaza a USUARIO + HERRAMIENTA.** Una sola caja: sin sesión publica el UID a `loan_queryu`, con sesión publica a `loan_querye`. Timer de inactividad de 180 s reseteado en cada interacción; al expirar el firmware republica a `loan_queryu` para que el backend cierre. Comandos nuevos que reconoce: `unload` (cierre con la propia credencial) y `refused` (credencial ajena). Variantes anteriores movidas a `_DEPRECATED_*_split.cpp`. |
| (siguiente) | **`prestamoService.handleLoanEquipmentQuery` resuelve 3 casos** sobre el UID entrante: 1) `uid === session.cards_number` → `_closeSession` + `unload`. 2) `getUserByRFID(uid)` no nulo → `refused`. 3) cualquier otra cosa → flujo de equipment normal. Cada préstamo refresca `expires_at` con `_refreshSession()` para que el timeout sea de inactividad real, no de tiempo desde el login. |
| (siguiente) | **README hardware** actualizado con la tabla nueva, contrato MQTT del flujo dual y diagrama del flujo unificado. SN `SMART10002` libre para futuras estaciones. |

### Validación (post)

- Sin sesión + credencial Jose → `found` + `user_name` ✅
- Con sesión + equipment BROCAS 1 → `prestado` ✅
- Con sesión + credencial David (ajena) → `refused`, sesión de Jose intacta ✅
- Con sesión + credencial Jose (la misma que abrió) → `unload`, sesión cerrada ✅

## 2026-04-27 (post-2)

### HW-PIO · proyecto PlatformIO unificado + HW-OTA + HW-BUILTIN

| Commit | Cambios |
|---|---|
| (siguiente) | **`www/hadware/` reorganizado en `arduino/` + `esp32/`.** El `.ino` del lector RC522 vive en `arduino/lector_universal/`. El firmware ESP32 pasa de 3 archivos `.cpp` independientes (~350 líneas cada uno, ~80 % duplicado) a un proyecto PlatformIO con: <br>• `esp32/platformio.ini` con 3 envs (`becarios`/`maquinas`/`prestamo`). <br>• `esp32/src/main.cpp` único con todo el scaffolding compartido (UART RTOS task, WiFi STA con timeout y `ESP.restart`, MQTT con Last Will retained `{SN}/status`, OLED, ArduinoOTA con hostname `{SN}.local`). <br>• `esp32/src/mode.h` con la interfaz `modeSetup/modeOnUidRead/modeOnCommand/modeOnUserName/modeLoopTick`. <br>• `esp32/src/mode_<x>.cpp` envueltos en `#ifdef MODE_<X>`: solo uno aporta `kMode` y los handlers por env. <br>• `esp32/include/secrets.h.example` plantilla; `secrets.h` gitignored. <br>**HW-OTA**: ArduinoOTA listo en cada estación, descubrible por mDNS como `<SN>.local`. Password opcional via `SECRETS_OTA_PASSWORD`. <br>**HW-BUILTIN**: `PIN_LED` ahora es overridable con `-DPIN_LED=<n>` por env si una placa concreta cuelga al arrancar por strapping de GPIO2. <br>Eliminados los 3 `.cpp` viejos + 4 `_DEPRECATED_*` que ya no aportan (la historia git los conserva). |

### Validación (post-2)

- `pio run -e becarios -e maquinas -e prestamo` debe compilar los tres firmwares sin warnings nuevos. El usuario probará el flasheo en hardware real (USB la primera vez, OTA en adelante).
- Contrato MQTT no cambió → backend y app no requieren cambios.

## Pendientes (siguiente bloque, no incluido aquí)

- **HW-NVS**: secrets en NVS/Preferences + WiFiManager (captive portal) en primer arranque.
- **BE-A**: reconciliar credenciales MQTT entre `.env` raíz (`smartlabs/...`), `flutter-api/.env` (`jose/public`) y `secrets.h` del firmware. Decisión de ops.
- **BE-C**: lock/transacción explícita en `loan_sessions` para entornos con múltiples instancias del flutter-api (la PK ya da atomicidad, pero un mutex en DB previene escrituras parciales en escenarios de fallo).
- **BE-G**: reemplazar polling de `traffic` cada 5s en `device-status/server.js` por subscribe MQTT directo o triggers DB.
- **PHP-L**: decidir un único entry point (raíz `index.php` vs `public/index.php`) y `.htaccess` que redirija.
- **PHP-W**: N+1 en `LoanController::consultarPrestamos` (un SELECT extra por préstamo activo).
- **PHP-V**: muchos controllers todavía hacen SQL inline en lugar de pasar por el modelo.
