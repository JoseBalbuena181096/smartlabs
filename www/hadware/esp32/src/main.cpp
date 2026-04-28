// SMARTLABS · firmware ESP32 — scaffolding compartido.
// =============================================================================
// Selecciona el modo con un build flag (`-DMODE_BECARIOS` / `-DMODE_MAQUINAS` /
// `-DMODE_PRESTAMO`) en platformio.ini. El comportamiento especifico vive en
// src/mode_<x>.cpp y entra a este archivo via la interfaz de mode.h.
//
// Mejoras heredadas del bloque anterior (b632a02) y nuevas en este commit:
//   - char[] para UID en RTOS task (sin String -> sin heap fragmentation)
//   - QueueHandle_t entre core 0 (UART) y core 1 (MQTT)
//   - WiFi.begin con timeout y ESP.restart si el AP no aparece
//   - MQTT con Last Will (`{SN}/status` retained: online/offline)
//   - Reconexion MQTT no bloqueante
//   - HW-OTA: ArduinoOTA con hostname = SN y password opcional desde secrets.h
//   - HW-BUILTIN: GPIO2 explicito; en DevKit V1 no es strapping critico
//     (boot_mode lee solo GPIO0/2/5/12/15 en arranque y GPIO2 con pull-down
//     externo es seguro). Si una placa especifica colgara al bootear, usar
//     LED_BUILTIN en otro pin via build_flag -DPIN_LED=<n>.
//
// Lo que NO esta unificado todavia (HW-NVS): leer credenciales desde Preferences
// con captive portal en primer arranque. Hoy siguen viniendo de secrets.h.
// =============================================================================

#include <Arduino.h>
#include <WiFi.h>
#include <ESPmDNS.h>
#include <PubSubClient.h>
#include <ArduinoOTA.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SH110X.h>

#include "secrets.h"
#include "mode.h"

// -----------------------------------------------------------------------------
// PINOUT / CONST
// -----------------------------------------------------------------------------
#define RXD2          16
#define TXD2          17
#define OLED_RESET    -1
#define SCREEN_WIDTH  128
#define SCREEN_HEIGHT 64
#define I2C_ADDRESS   0x3c

#ifndef PIN_LED
#define PIN_LED       2     // BUILTIN_LED en ESP32 DevKit V1
#endif

static const uint8_t  RELAY_PINS[]              = {12, 13, 14};
static const unsigned long WIFI_CONNECT_TIMEOUT_MS = 30000;
static const unsigned long MQTT_RETRY_INTERVAL_MS  = 5000;
static const size_t   UID_MAX                   = 24;

// -----------------------------------------------------------------------------
// ESTADO GLOBAL
// -----------------------------------------------------------------------------
static WiFiClient    espClient;
static PubSubClient  mqtt(espClient);
Adafruit_SH1106G     display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

struct UidMsg { char data[UID_MAX]; };
static QueueHandle_t uidQueue;

static unsigned long last_mqtt_attempt = 0;
static bool          ota_ready         = false;

// -----------------------------------------------------------------------------
// FORWARDS
// -----------------------------------------------------------------------------
static void uartReaderTask(void* param);
static void onMqttMessage(char* topic, byte* payload, unsigned int length);
static void setupWifi();
static void setupOTA();
static void mqttTryReconnect();

// =============================================================================
// SETUP / LOOP
// =============================================================================
void setup() {
  Serial.begin(115200);
  Serial2.begin(9600, SERIAL_8N1, RXD2, TXD2);
  randomSeed(esp_random());

  pinMode(PIN_LED, OUTPUT);
  digitalWrite(PIN_LED, LOW);
  for (uint8_t p : RELAY_PINS) {
    pinMode(p, OUTPUT);
    digitalWrite(p, LOW);
  }

  Wire.begin();
  display.begin(I2C_ADDRESS, true);
  display.clearDisplay();

  uidQueue = xQueueCreate(4, sizeof(UidMsg));
  xTaskCreatePinnedToCore(uartReaderTask, "uart", 4096, nullptr, 1, nullptr, 0);

  setupWifi();
  setupOTA();

  mqtt.setServer(SECRETS_MQTT_HOST, SECRETS_MQTT_PORT);
  mqtt.setCallback(onMqttMessage);
  mqtt.setBufferSize(512);

  modeSetup();
}

void loop() {
  if (ota_ready) ArduinoOTA.handle();

  if (!mqtt.connected()) mqttTryReconnect();
  mqtt.loop();

  UidMsg msg;
  while (xQueueReceive(uidQueue, &msg, 0) == pdTRUE) {
    Serial.printf("UID -> %s\n", msg.data);
    modeOnUidRead(msg.data);
  }

  modeLoopTick();
}

// =============================================================================
// UART (core 0)
// =============================================================================
static void uartReaderTask(void* /*param*/) {
  static char   buf[UID_MAX];
  static size_t len = 0;
  for (;;) {
    while (Serial2.available()) {
      char c = (char)Serial2.read();
      if (c == '\t') {
        if (len > 9 && len < UID_MAX) {
          buf[len] = '\0';
          UidMsg msg;
          memcpy(msg.data, buf, len + 1);
          xQueueSend(uidQueue, &msg, 0);
        }
        len = 0;
      } else if (isAlphaNumeric(c) && len < UID_MAX - 1) {
        buf[len++] = c;
      } else if (len >= UID_MAX - 1) {
        len = 0;
      }
    }
    vTaskDelay(pdMS_TO_TICKS(5));
  }
}

// =============================================================================
// MQTT
// =============================================================================
static void onMqttMessage(char* topic, byte* payload, unsigned int length) {
  char incoming[96] = {0};
  size_t n = (length < sizeof(incoming) - 1) ? length : sizeof(incoming) - 1;
  memcpy(incoming, payload, n);
  incoming[n] = '\0';
  while (n > 0 && (incoming[n - 1] == ' ' || incoming[n - 1] == '\r' || incoming[n - 1] == '\n')) {
    incoming[--n] = '\0';
  }

  String t(topic);
  if (t == topicOf("user_name")) {
    modeOnUserName(incoming);
  } else if (t == topicOf("command")) {
    modeOnCommand(incoming);
  }
}

static void setupWifi() {
  Serial.printf("\n[wifi] Conectando a %s\n", SECRETS_WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.setHostname(kMode.serial_number);
  if (!WiFi.config(kMode.local_ip, kMode.gateway, kMode.subnet, kMode.dns1, kMode.dns2)) {
    Serial.println("[wifi] STA Failed to configure (fallback DHCP)");
  }
  WiFi.begin(SECRETS_WIFI_SSID, SECRETS_WIFI_PASSWORD);

  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED) {
    if (millis() - start > WIFI_CONNECT_TIMEOUT_MS) {
      Serial.println("\n[wifi] timeout - restart");
      delay(500);
      ESP.restart();
    }
    delay(500);
    Serial.print(".");
  }
  Serial.printf("\n[wifi] IP=%s host=%s\n",
                WiFi.localIP().toString().c_str(), kMode.serial_number);
}

static void setupOTA() {
  ArduinoOTA.setHostname(kMode.serial_number);
#ifdef SECRETS_OTA_PASSWORD
  ArduinoOTA.setPassword(SECRETS_OTA_PASSWORD);
#endif
  ArduinoOTA.onStart([]() { Serial.println("[ota] start"); });
  ArduinoOTA.onEnd  ([]() { Serial.println("[ota] end -> reboot"); });
  ArduinoOTA.onError([](ota_error_t e) { Serial.printf("[ota] err %u\n", e); });
  ArduinoOTA.begin();
  ota_ready = true;
  Serial.printf("[ota] listo en %s.local\n", kMode.serial_number);
}

static void mqttTryReconnect() {
  if (millis() - last_mqtt_attempt < MQTT_RETRY_INTERVAL_MS) return;
  last_mqtt_attempt = millis();
  if (WiFi.status() != WL_CONNECTED) return;

  String clientId = String("esp32_") + kMode.serial_number + "_" + String(random(0xffff), HEX);
  String willTopic = topicOf("status");
  Serial.print("[mqtt] connect... ");
  bool ok = mqtt.connect(
      clientId.c_str(),
      SECRETS_MQTT_USER, SECRETS_MQTT_PASS,
      willTopic.c_str(), 1, true, "offline");
  if (!ok) {
    Serial.printf("fail %d\n", mqtt.state());
    return;
  }
  Serial.println("ok");
  mqtt.publish(willTopic.c_str(), "online", true);
  mqtt.subscribe(topicOf("command").c_str());
  mqtt.subscribe(topicOf("user_name").c_str());
}

// =============================================================================
// API expuesta a los modos
// =============================================================================
String topicOf(const char* suffix) {
  String t = kMode.serial_number;
  t += '/';
  t += suffix;
  return t;
}

void publishOnTopic(const char* suffix, const char* payload) {
  if (mqtt.connected()) {
    mqtt.publish(topicOf(suffix).c_str(), payload);
  }
}

bool mqttIsConnected() { return mqtt.connected(); }

void setRelays(bool on) {
  for (uint8_t p : RELAY_PINS) digitalWrite(p, on ? HIGH : LOW);
  digitalWrite(PIN_LED, on ? HIGH : LOW);
}

void blinkLed(int times) {
  for (int i = 0; i < times; i++) {
    digitalWrite(PIN_LED, HIGH);
    delay(150);
    digitalWrite(PIN_LED, LOW);
    delay(150);
  }
}
