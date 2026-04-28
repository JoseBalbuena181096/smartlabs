/*
 * SMARTLABS - Lector RFID universal MIFARE Classic + NTAG213/215/216 (Ultralight)
 * --------------------------------------------------------------------------------
 * Reemplaza a RF_ID_ONE_EQUIPOS_FINAL_II.ino. Diferencias clave:
 *
 *   1. Lee UIDs de 4, 7 y 10 bytes (el original solo aceptaba MIFARE Classic 4 bytes,
 *      lo que descartaba las pegatinas NTAG213 que se usan en herramientas).
 *   2. No llama a la API Crypto1 para tags Ultralight (no la soportan).
 *   3. Anti-rebote: ignora la misma tarjeta durante REPEAT_WINDOW_MS si sigue presente.
 *
 * Formato de salida (sin cambios respecto al sketch original — la BD existente sigue
 * matcheando): cada byte se imprime en decimal, con prefijo " 0" si byte < 0x10,
 * sin separador entre bytes, terminador '\t', luego tono breve.
 *
 * Hardware:
 *   - Arduino Nano / Pro Mini (5V) + módulo MFRC522 RC522 vía SPI.
 *   - SoftwareSerial 3 (RX), 4 (TX) → conectado al Serial2 del ESP32 DevKit (16/17).
 *   - Buzzer pasivo en pin 6 (tone).
 *
 * Pinout MFRC522:
 *   RST  -> 9
 *   SDA  -> 10 (SS)
 *   MOSI -> 11
 *   MISO -> 12
 *   SCK  -> 13
 */

#include <SPI.h>
#include <MFRC522.h>
#include <SoftwareSerial.h>

#define SS_PIN  10
#define RST_PIN 9
#define BUZZER_PIN 6
#define BAUD 9600

static const uint16_t REPEAT_WINDOW_MS = 1500;  // ignorar misma UID dentro de esta ventana
static const uint16_t POST_READ_DELAY_MS = 500; // pausa tras lectura exitosa

SoftwareSerial mySerial(3, 4); // RX, TX hacia ESP32
MFRC522 rfid(SS_PIN, RST_PIN);

byte lastUid[10];
byte lastUidSize = 0;
unsigned long lastReadAt = 0;

void setup() {
  Serial.begin(BAUD);
  mySerial.begin(BAUD);
  SPI.begin();
  rfid.PCD_Init();
  pinMode(BUZZER_PIN, OUTPUT);

  Serial.println(F("SMARTLABS lector universal listo (MIFARE Classic + NTAG21x)."));
}

void loop() {
  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial())   return;

  if (isRepeat(rfid.uid.uidByte, rfid.uid.size)) {
    // Mismo tag aún presente → no reenviar.
    rfid.PICC_HaltA();
    return;
  }

  MFRC522::PICC_Type piccType = rfid.PICC_GetType(rfid.uid.sak);
  Serial.print(F("Tag detectado tipo: "));
  Serial.print(rfid.PICC_GetTypeName(piccType));
  Serial.print(F(" | UID size: "));
  Serial.println(rfid.uid.size);

  // Enviar UID al ESP32 — formato idéntico al sketch original (decimal por byte,
  // con prefijo " 0" si byte<16, sin separador, terminador '\t').
  emitUid(rfid.uid.uidByte, rfid.uid.size);

  // Recordar para anti-rebote.
  rememberUid(rfid.uid.uidByte, rfid.uid.size);

  // Solo MIFARE Classic usa Crypto1; para Ultralight no aplica pero llamarlo es no-op.
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  delay(POST_READ_DELAY_MS);
}

void emitUid(byte *uid, byte size) {
  for (byte i = 0; i < size; i++) {
    if (uid[i] < 0x10) {
      Serial.print(F(" 0"));
      mySerial.print(F(" 0"));
    }
    Serial.print(uid[i], DEC);
    mySerial.print(uid[i], DEC);
  }
  Serial.println();
  mySerial.print('\t');

  tone(BUZZER_PIN, 1000, 200);
}

bool isRepeat(byte *uid, byte size) {
  if (size != lastUidSize) return false;
  if (millis() - lastReadAt > REPEAT_WINDOW_MS) return false;
  for (byte i = 0; i < size; i++) {
    if (uid[i] != lastUid[i]) return false;
  }
  return true;
}

void rememberUid(byte *uid, byte size) {
  lastUidSize = size;
  for (byte i = 0; i < size && i < sizeof(lastUid); i++) {
    lastUid[i] = uid[i];
  }
  lastReadAt = millis();
}
