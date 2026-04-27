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

## 4. Antes de flashear: crear `secrets.h`

Las credenciales WiFi/MQTT ya **no están** en los `.cpp`. Hay que crear un
`secrets.h` local (gitignored) en este mismo directorio:

```bash
cp secrets.h.example secrets.h
# editar secrets.h con tus valores reales
```

Si compilas con Arduino IDE: el `secrets.h` debe estar en la misma carpeta del
`.ino` que abras (Arduino agrupa todos los `.h`/`.cpp` adjuntos al sketch).

## 5. Mejoras aplicadas a los `esp32_*.cpp`

Los cuatro archivos activos fueron reescritos preservando 100 % el contrato
MQTT (mismos topics, mismos comandos, mismo formato de payload). Cambios:

| Mejora | Estado |
|---|---|
| Credenciales WiFi/MQTT en `secrets.h` git-ignored | ✅ aplicado |
| `char[]` en lugar de `Arduino String` para el UID en RTOS task (causa raíz de heap fragmentation y watchdog crashes) | ✅ aplicado |
| `QueueHandle_t` para pasar UIDs entre core 0 (UART) y core 1 (MQTT) en lugar de variables compartidas | ✅ aplicado |
| `WiFi.begin` con timeout 30 s y `ESP.restart()` si el AP cae | ✅ aplicado |
| MQTT Last Will: `{SN}/status` retained `offline` cuando muere, `online` al conectar | ✅ aplicado |
| Reconexión MQTT no bloqueante (sin while-loop) | ✅ aplicado |
| `temprature_sens_read()` y publish de `{SN}/temp` eliminados (deprecated, backend no los consume) | ✅ aplicado |
| OLED `TARGETA` → `TARJETA`, mensajes adaptados al ancho de 128 px | ✅ aplicado |

## 6. Pendientes (siguiente bloque de trabajo)

| Severidad | Tarea |
|---|---|
| Medio    | OTA (Over-The-Air updates) con `ArduinoOTA` o `ESPhttpUpdate` para no flashear por USB cada vez. |
| Medio    | Persistir `secrets.h` en NVS / Preferences con un portal de configuración WiFi en primer arranque (WiFiManager). |
| Bajo     | Confirmar que `BUILTIN_LED` (GPIO2 en ESP32 DevKit) no entre en conflicto con strapping al arrancar. |
| Bajo     | Refactor a un solo firmware con PlatformIO + `build_flags` por modo (4 envs) para eliminar la duplicación restante entre los 4 archivos. |

### Esquema PlatformIO sugerido (cuando se haga)

```
src/main.cpp                 # genérico (lee MODE_* de build_flags)
src/modes/becarios.cpp       # solo handler de comandos + textos OLED
src/modes/maquinas.cpp
src/modes/herramienta.cpp
src/modes/usuario.cpp
include/secrets.h            # gitignored
platformio.ini               # 4 envs: becarios / maquinas / herramienta / usuario
```

Reduciría los ~4 × 350 líneas actuales a ~1 archivo común + 4 archivos de
~80 líneas. Cualquier fix de seguridad/red aplica una sola vez en lugar de
cuatro.
