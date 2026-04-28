# SMARTLABS · Arduino + RC522

Sketch único que se flashea en el Arduino acoplado a cualquiera de las 3
estaciones (becarios, máquinas, préstamo). El RC522 lee el tag y emite el UID
por UART (9600 bps, terminador `\t`) hacia el ESP32 que tenga al lado.

```
arduino/
└── lector_universal/
    └── lector_universal.ino    soporta MIFARE Classic + NTAG21x
```

## Compilar / flashear

Arduino IDE 1.x o 2.x:

1. Abrir `lector_universal/lector_universal.ino`.
2. Seleccionar la placa correcta (Arduino UNO/Nano/Mega según el hardware
   real de la estación).
3. Instalar la librería **MFRC522** desde el Library Manager.
4. Subir.

## Pinout RC522

| RC522 | Arduino UNO/Nano |
|---|---|
| SDA / SS | D10 |
| SCK     | D13 |
| MOSI    | D11 |
| MISO    | D12 |
| RST     | D9  |
| 3.3 V   | 3.3 V |
| GND     | GND  |

UART hacia el ESP32: TX del Arduino → RXD2 (GPIO16) del ESP32, GND común.
9600 bps, 8N1.

## Bug histórico

`_DEPRECATED_arduino_solo_mifare.ino` (eliminado en este commit) descartaba
todo lo que no fuera MIFARE Classic y declaraba `byte nuidPICC[4]`, así que
las pegatinas NTAG213 nunca se leían. El sketch universal acepta cualquier
ISO14443A y dimensiona el UID dinámicamente.
