# SMARTLABS · Firmware de estaciones IoT

Firmware Arduino/ESP32 para los lectores RFID que controlan acceso, máquinas y
préstamo de herramientas. Cada estación se compone de **dos placas**:

```
  ┌──────────────────────────┐                  ┌─────────────────────────┐
  │ Arduino + MFRC522        │  UART 9600 bps   │ ESP32 DevKit V1 30-pin  │
  │ arduino_lector_universal │ ───────────────▶ │ esp32_*.cpp             │
  │ Lee MIFARE/NTAG          │   '\t' terminator│ WiFi + MQTT + OLED      │
  └──────────────────────────┘                  └─────────────────────────┘
                                                         │
                                                         │ MQTT 1883
                                                         ▼
                                              broker EMQX  192.168.0.100
```

## 1. Qué flashear en cada placa

### Arduino con RC522 (todos los lectores físicos)

Solo hay un sketch que aplica a cualquier estación:

| Archivo | Estado | Soporta |
|---|---|---|
| `arduino_lector_universal.ino` | **Usar este** | MIFARE Classic 4 bytes (credenciales del Tec) **+** NTAG21x 7 bytes (pegatinas de herramienta) |
| `_DEPRECATED_arduino_solo_mifare.ino` | NO usar | Solo MIFARE Classic. **No lee NTAG213**, por eso las pegatinas de herramienta no eran detectadas. Conservado como referencia histórica. |

### ESP32 DevKit V1 (un firmware distinto por estación)

| Archivo | Serie | IP | Estación física |
|---|---|---|---|
| `esp32_acceso_becarios.cpp`            | `SMART10000` | 192.168.0.185 | Acceso de becarios al espacio |
| `esp32_acceso_maquinas.cpp`            | `SMART10001` | 192.168.0.123 | Encendido de máquinas / mesas |
| `esp32_prestamo_lector_HERRAMIENTA.cpp`| `SMART10002` | 192.168.0.33  | **Préstamo: lee la pegatina NTAG de la herramienta** |
| `esp32_prestamo_lector_USUARIO.cpp`    | `SMART10003` | 192.168.0.34  | **Préstamo: lee la credencial del usuario** |
| `_DEPRECATED_esp32_prestamo_usuario_v1.cpp` | (mismo SMART10003) | — | Versión vieja del lector de usuario. Solo conservada como referencia; **no flashear**. |

> **El autopréstamo de herramientas usa dos placas ESP32 conviviendo**:
> primero el usuario pasa su credencial en `SMART10003` (lector USUARIO) y el
> backend abre sesión; después el usuario pasa la pegatina NTAG de la herramienta
> en `SMART10002` (lector HERRAMIENTA) y el backend asocia el préstamo.

## 2. Contrato MQTT (lo que el backend espera)

El backend (`flutter-api/src/services/mqttListenerService.js`) está suscrito a:

```
+/loan_queryu      → consulta usuario en préstamo
+/loan_querye      → consulta herramienta en préstamo
+/access_query     → control de acceso a máquina
+/scholar_query    → control de acceso de becarios
values             → datos de sensores (CSV: t1,t2,v)
```

Y publica de vuelta a:

```
{SN}/user_name     → nombre humano del usuario o herramienta
{SN}/command       → comando para el ESP32 (ver tabla abajo)
```

| Estación | Topic publish del ESP32 | Comandos válidos del backend |
|---|---|---|
| `esp32_acceso_becarios.cpp`             | `{SN}/scholar_query` | `granted1` `granted0` `refused` |
| `esp32_acceso_maquinas.cpp`             | `{SN}/access_query`  | `granted1` `granted0` `refused` |
| `esp32_prestamo_lector_USUARIO.cpp`     | `{SN}/loan_queryu`   | `found` `nofound` `unload` |
| `esp32_prestamo_lector_HERRAMIENTA.cpp` | `{SN}/loan_querye`   | `prestado` `devuelto` `nofound` `nologin` |

**Reglas que el firmware debe respetar siempre**:

1. El payload de cada `*_query` es el UID en el mismo formato que produce el
   `arduino_lector_universal.ino` (decimal por byte con padding solo si byte<16,
   terminador `\t` en UART).
2. El backend ignora payloads que empiezan con `APP:` (esos vienen de la app
   Flutter cuando simula el dispositivo). El firmware **no debe** publicar con
   ese prefijo.
3. El `serial_number` (SN) va siempre como primer segmento del topic. Es el
   identificador único del dispositivo.

## 3. Bug crítico resuelto: NTAG213 no se leía

`_DEPRECATED_arduino_solo_mifare.ino` tenía dos defectos que impedían leer las
pegatinas NTAG213 que se usan como tag pegado a las herramientas:

```cpp
// Filtro descartaba cualquier tag que no fuera MIFARE Classic
if (piccType != PICC_TYPE_MIFARE_MINI &&
    piccType != PICC_TYPE_MIFARE_1K &&
    piccType != PICC_TYPE_MIFARE_4K) { return; }

// Y el array de UID solo tenía 4 bytes (NTAG213 = 7 bytes)
byte nuidPICC[4];
```

`arduino_lector_universal.ino` corrige ambos. El formato de salida hacia el ESP32
se mantiene idéntico, por lo que las tarjetas MIFARE ya registradas en la BD
siguen matcheando sin migración.

**Plan de migración recomendado**:

1. Flashear el Arduino del **lector de HERRAMIENTA** con
   `arduino_lector_universal.ino` (es la estación que tiene NTAGs y donde el
   bug está bloqueando uso real).
2. Probar que una pegatina NTAG213 nueva produce un UID que se puede registrar
   en la tabla `equipments` (campo `equipments_rfid`).
3. Probar que una credencial MIFARE Classic conocida sigue produciendo el mismo
   string que antes (verificar contra `cards.cards_number`).
4. Flashear los demás Arduinos (lectores de USUARIO, becarios, máquinas) con el
   mismo sketch — soporta ambos tipos de tag, reemplaza al antiguo sin cambios
   en BD.

## 4. Bugs conocidos pendientes en los `esp32_*.cpp`

Estos NO se han tocado todavía — listados para el siguiente refactor:

| Severidad | Archivo(s) | Problema |
|---|---|---|
| Crítico  | TODOS  | Credenciales WiFi/MQTT hardcoded (`dlink/angelsnek2510`, `jose/public`). Mover a `secrets.h` git-ignored o NVS. |
| Crítico  | TODOS  | `String rfid` acumulado en RTOS task → fragmentación de heap, probable causa raíz de los watchdog crashes. Reemplazar por `char buf[24]` + índice. |
| Alto     | TODOS  | Race condition entre core 0 (`codeForTask1`) y core 1 (`loop`) sobre `rfid` y `send_access_query`. Sin `portMUX` ni queue. |
| Alto     | TODOS  | `while (WiFi.status() != WL_CONNECTED) delay(500)` cuelga indefinido si AP cae. Falta timeout + reset. |
| Alto     | usuarios | Mismo `serial_number = SMART10003` en `esp32_prestamo_lector_USUARIO.cpp` y `_DEPRECATED_esp32_prestamo_usuario_v1.cpp`. Solo flashear el activo. |
| Medio    | TODOS  | Falta MQTT Last Will (`{SN}/status` con `offline` retained) para que el backend sepa cuándo el equipo se cae. |
| Medio    | TODOS  | `temprature_sens_read()` está deprecated en IDF nuevo y el backend no consume `{SN}/temp` — eliminar o reemplazar por NTC externo. |
| Bajo     | TODOS  | Texto OLED `setTextSize(2)` con strings de 30 chars no cabe en 128 px. Y `TARGETA` → `TARJETA`. |
| Bajo     | TODOS  | `BUILTIN_LED` en ESP32 DevKit es GPIO2 (strapping pin) — confirmar que no haya conflicto al arrancar. |

## 5. Siguiente paso sugerido

Refactorizar a **un solo firmware con PlatformIO** y `build_flags` por modo:

```
src/main.cpp                 # genérico (lee MODE_* de build_flags)
src/modes/becarios.cpp       # solo handler de comandos + textos OLED
src/modes/maquinas.cpp
src/modes/herramienta.cpp
src/modes/usuario.cpp
include/secrets.h            # gitignored, con WIFI_SSID, MQTT_PASS, etc.
platformio.ini               # 4 envs: becarios / maquinas / herramienta / usuario
```

Esto reduce ~5 × 400 líneas duplicadas a ~1 archivo + 4 archivos de ~80 líneas.
Cualquier fix de seguridad/red se aplica una sola vez en lugar de cinco.
