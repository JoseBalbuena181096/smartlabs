# SMARTLABS · Firmware de estaciones IoT

Firmware para los lectores RFID que controlan acceso, máquinas y préstamo de
herramientas. Cada estación se compone de **dos placas**:

```
  ┌──────────────────────────┐                  ┌─────────────────────────┐
  │ Arduino + MFRC522        │  UART 9600 bps   │ ESP32 DevKit V1 30-pin  │
  │ arduino/lector_universal │ ───────────────▶ │ esp32/ (PlatformIO)     │
  │ Lee MIFARE/NTAG          │   '\t' terminator│ WiFi + MQTT + OLED      │
  └──────────────────────────┘                  └─────────────────────────┘
                                                         │
                                                         │ MQTT 1883
                                                         ▼
                                              broker EMQX  192.168.0.100
```

Estructura del directorio:

```
www/hadware/
├── arduino/                    sketches Arduino IDE para el RC522
│   └── lector_universal/
│       └── lector_universal.ino
├── esp32/                      proyecto PlatformIO unificado
│   ├── platformio.ini          3 envs (becarios / maquinas / prestamo)
│   ├── include/
│   │   └── secrets.h.example   plantilla; copiar a secrets.h (gitignored)
│   └── src/
│       ├── main.cpp            scaffolding comun (UART/WiFi/MQTT/OTA/OLED)
│       ├── mode.h              interfaz comun
│       ├── mode_becarios.cpp   SMART10000  (acceso becarios)
│       ├── mode_maquinas.cpp   SMART10001  (acceso maquinas)
│       └── mode_prestamo.cpp   SMART10003  (prestamo unificado HW-UNIF)
├── INFERIOR.3mf · SUPERIOR.3mf · Gerber_*.zip   (carcasa y PCB)
└── README.md
```

## 1. Arduino + RC522 (todos los lectores físicos)

Un solo sketch sirve para cualquier estación:

| Archivo | Soporta |
|---|---|
| `arduino/lector_universal/lector_universal.ino` | MIFARE Classic 4 bytes (credenciales del Tec) **+** NTAG21x 7 bytes (pegatinas de herramienta) |

> Fix histórico: la versión vieja filtraba `PICC_TYPE_MIFARE_*` y declaraba
> `byte nuidPICC[4]`, así que las pegatinas NTAG213 nunca se leían. El sketch
> universal acepta cualquier ISO14443A y dimensiona el UID dinámicamente.

Compilar/flashear con Arduino IDE: abrir el `.ino` en `arduino/lector_universal/`.
El nombre de la carpeta tiene que coincidir con el del sketch para que el IDE
lo reconozca.

## 2. ESP32 con PlatformIO (un único `main.cpp`, 3 estaciones)

Un solo proyecto compila los 3 firmwares activos. La diferencia entre
estaciones es un build flag (`-DMODE_BECARIOS`, `-DMODE_MAQUINAS`,
`-DMODE_PRESTAMO`) y el archivo `mode_<x>.cpp` correspondiente.

| env | Estación | SN | IP | Topic publish |
|---|---|---|---|---|
| `becarios` | acceso de becarios al espacio | `SMART10000` | 192.168.0.185 | `scholar_query` |
| `maquinas` | encendido de máquinas / mesas | `SMART10001` | 192.168.0.123 | `access_query` |
| `prestamo` | préstamo unificado credencial+herramienta | `SMART10003` | 192.168.0.34 | `loan_queryu` (sin sesión) / `loan_querye` (con sesión) |

### Compilar / flashear

```bash
cd www/hadware/esp32
cp include/secrets.h.example include/secrets.h    # editar con tus valores

# por USB, primer flasheo
pio run -e becarios -t upload && pio device monitor -e becarios
pio run -e maquinas -t upload && pio device monitor -e maquinas
pio run -e prestamo -t upload && pio device monitor -e prestamo

# por OTA, una vez la estacion ya esta en red
pio run -e prestamo -t upload --upload-port SMART10003.local

# limpiar build
pio run -t clean
```

### Cómo funciona la unificación

`main.cpp` contiene todo el código común: WiFi STA con IP fija, MQTT con LWT,
UART con `QueueHandle_t` para pasar UIDs entre cores, OLED y ArduinoOTA.
Cada `mode_<x>.cpp` queda envuelto en `#ifdef MODE_<X>`, así por env solo una
unidad de traducción aporta `kMode` (config IP+SN) y los cuatro hooks:

```cpp
void modeSetup();                    // pinta la pantalla idle inicial
void modeOnUidRead(const char* uid); // UART -> publica al backend
void modeOnUserName(const char* n);  // backend -> nombre humano para el OLED
void modeOnCommand(const char* cmd); // backend -> granted/refused/found/...
void modeLoopTick();                 // timeouts y trabajo periodico
```

Sin esto los 3 firmwares duplicaban ~80 % del código (~350 líneas cada uno).
Cualquier fix de seguridad o conectividad ahora aplica una sola vez en
`main.cpp`.

## 3. Contrato MQTT (lo que el backend espera)

`flutter-api/src/services/mqttListenerService.js` está suscrito a:

```
+/loan_queryu      consulta usuario en prestamo
+/loan_querye      consulta herramienta en prestamo
+/access_query     control de acceso a maquina
+/scholar_query    control de acceso de becarios
+/status           LWT de cada estacion (online/offline retained)
values             datos de sensores (CSV: t1,t2,v)
```

Y publica de vuelta a:

```
{SN}/user_name     nombre humano del usuario o herramienta
{SN}/command       comando para el ESP32 (ver tabla)
```

| Estación | Topic publish del ESP32 | Comandos válidos del backend |
|---|---|---|
| becarios   | `{SN}/scholar_query` | `granted1` `granted0` `refused` |
| maquinas   | `{SN}/access_query`  | `granted1` `granted0` `refused` |
| prestamo (sin sesión) | `{SN}/loan_queryu` | `found` `nofound` |
| prestamo (con sesión) | `{SN}/loan_querye` | `prestado` `devuelto` `nofound` `unload` `refused` `nologin` |

Reglas que el firmware respeta siempre:

1. El payload de cada `*_query` es el UID en el mismo formato que produce
   `lector_universal.ino` (decimal por byte con padding solo si byte<16,
   terminador `\t` en UART).
2. El backend ignora payloads que empiezan con `APP:` (esos vienen de la app
   Flutter cuando simula el dispositivo). El firmware no debe publicar con
   ese prefijo.
3. El `serial_number` (SN) va siempre como primer segmento del topic.

## 4. Préstamo unificado (`mode_prestamo.cpp`)

Una sola estación física hace el flujo completo:

```
                Pasa tag fisico
                     │
                     ▼
            ┌─────────────────┐
            │ ¿hay sesion?    │
            └────┬─────────┬──┘
                NO         SI
                │           │
                ▼           ▼
   publish a loan_queryu    publish a loan_querye
   (UID = el tag leido)     (UID = el tag leido)
                │           │
                │           ├─ backend resuelve:
                │           │   • UID == credencial actual → close + 'unload'
                │           │   • UID es OTRA credencial    → 'refused'
                │           │   • UID es equipment          → 'prestado'/'devuelto'
                │           │   • UID no existe             → 'nofound'
                │           │
                ▼           ▼
   backend resuelve:   firmware reacciona y refresca timer.
   • UID en cards → 'found' + user_name (abre sesion)
   • si no       → 'nofound'
```

- **Timeout de inactividad**: 180 s (`INACTIVITY_TIMEOUT_MS` en `mode_prestamo.cpp`).
  Cualquier interacción lo resetea. Al expirar el firmware republica el UID
  original a `loan_queryu` y el backend cierra con `unload`.
- **Decisión credencial vs equipment delegada al backend**. El firmware solo
  sabe si tiene sesión.

## 5. Mejoras aplicadas (estado actual)

| Mejora | ID | Estado |
|---|---|---|
| Lector universal MIFARE+NTAG | — | ✅ |
| `char[]` en lugar de `String` en RTOS task (heap fragmentation) | — | ✅ |
| `QueueHandle_t` UART↔MQTT entre cores | — | ✅ |
| WiFi.begin con timeout y `ESP.restart` | — | ✅ |
| MQTT Last Will retained `{SN}/status` | — | ✅ |
| Reconexión MQTT no bloqueante | — | ✅ |
| Préstamo unificado credencial+herramienta | HW-UNIF | ✅ |
| Refactor a un solo proyecto PlatformIO con `build_flags` | HW-PIO | ✅ |
| OTA con `ArduinoOTA` (hostname `{SN}.local`) | HW-OTA | ✅ |
| Confirmación de strapping GPIO2 (override `-DPIN_LED=<n>` si una placa cuelga) | HW-BUILTIN | ✅ |

## 6. Pendientes

| Severidad | Tarea |
|---|---|
| Media | **HW-NVS**: `secrets.h` -> `Preferences`/NVS con captive portal (WiFiManager) en primer arranque. Hoy hay que recompilar para cambiar SSID/MQTT. |
| Baja  | Métrica de heap libre periódica publicada a `{SN}/heap` para detectar fugas en producción. |
| Baja  | Watchdog de aplicación: si pasan N minutos sin loop completo, `ESP.restart`. |

## 7. Diagrama de archivos físicos

`INFERIOR.3mf`, `SUPERIOR.3mf` y `Gerber_smartlabs-2_PCB_smartlabs-copy_2025-09-11.zip`
son la carcasa impresa en 3D y el PCB original. No tocar a menos que cambie
el pinout (`RXD2=16`, `TXD2=17`, `RELAY_PINS={12,13,14}`, OLED I2C `0x3c`).
