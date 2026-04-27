// SMARTLABS · ESP32 lector de USUARIO en estación de préstamo (SMART10003)
// =========================================================================
// Recibe el UID por UART (proviene del Arduino+RC522 con
// arduino_lector_universal.ino), lo publica a {SN}/loan_queryu y reacciona
// a los comandos del backend (found / nofound / unload).
//
// Mejoras respecto a la versión legacy (main_usuariosLV2.cpp):
//   - Credenciales WiFi/MQTT en secrets.h (gitignored).
//   - char[] en lugar de Arduino String para el UID en la tarea RTOS
//     (evita fragmentación de heap y los watchdog crashes asociados).
//   - QueueHandle_t para pasar UIDs entre core 0 (UART) y core 1 (MQTT)
//     en vez de variables compartidas sin sincronizar.
//   - WiFi.begin con timeout de 30s y reset automático si el AP cae.
//   - MQTT con Last Will Testament: {SN}/status retained = "offline" si
//     el ESP32 muere, "online" cuando conecta.
//   - Reconexión MQTT no bloqueante (no while-loop).
//   - Sin temprature_sens_read (deprecated y el backend no lo consume).
//   - Texto OLED corregido ("CREDENCIAL", sin TARGETA).

#include <Arduino.h>
#include <WiFi.h>
#include <PubSubClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SH110X.h>

#include "secrets.h"

// -------------------------------------------------------------------------
// CONFIGURACIÓN DE ESTA ESTACIÓN
// -------------------------------------------------------------------------
static const char*  DEVICE_SN   = "SMART10003";
static const char*  TOPIC_QUERY = "loan_queryu";  // → publica el UID aquí
static const IPAddress LOCAL_IP(192, 168, 0, 34);
static const IPAddress GATEWAY (192, 168, 0, 1);
static const IPAddress SUBNET  (255, 255, 255, 0);
static const IPAddress DNS1    (8, 8, 8, 8);
static const IPAddress DNS2    (8, 8, 4, 4);

// Timeouts
static const unsigned long WIFI_CONNECT_TIMEOUT_MS = 30000;   // 30 s
static const unsigned long MQTT_RETRY_INTERVAL_MS  = 5000;    // 5 s
static const unsigned long SESSION_TIMEOUT_MS      = 150000;  // 2.5 min

// Pines y OLED
#define RXD2          16
#define TXD2          17
#define OLED_RESET    -1
#define SCREEN_WIDTH  128
#define SCREEN_HEIGHT 64
#define I2C_ADDRESS   0x3c
static const uint8_t RELAY_PINS[] = {12, 13, 14};

// Tamaño máximo del UID (NTAG213 produce hasta 21 caracteres en formato decimal)
static const size_t UID_MAX = 24;

// -------------------------------------------------------------------------
// ESTADO
// -------------------------------------------------------------------------
static WiFiClient        espClient;
static PubSubClient      mqtt(espClient);
static Adafruit_SH1106G  display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

struct UidMsg { char data[UID_MAX]; };
static QueueHandle_t uidQueue;

static bool          session_active     = false;
static unsigned long session_start_time = 0;
static char          current_user_rfid[UID_MAX] = {0};
static char          user_name[64]              = {0};
static unsigned long last_mqtt_attempt          = 0;

// -------------------------------------------------------------------------
// FORWARD DECLS
// -------------------------------------------------------------------------
static void uartReaderTask(void* param);
static void onMqttMessage(char* topic, byte* payload, unsigned int length);
static void setupWifi();
static void mqttTryReconnect();
static void blinkLed(int times);
static void screenIdle();
static void screenSending();
static void screenLogged();
static void screenNotFound();
static void screenLogout();
static String topicOf(const char* suffix);

// =========================================================================
// SETUP / LOOP
// =========================================================================
void setup() {
  Serial.begin(115200);
  Serial2.begin(9600, SERIAL_8N1, RXD2, TXD2);
  randomSeed(esp_random());

  pinMode(BUILTIN_LED, OUTPUT);
  for (uint8_t p : RELAY_PINS) {
    pinMode(p, OUTPUT);
    digitalWrite(p, LOW);
  }

  Wire.begin();
  display.begin(I2C_ADDRESS, true);
  display.clearDisplay();
  screenIdle();

  uidQueue = xQueueCreate(4, sizeof(UidMsg));
  xTaskCreatePinnedToCore(uartReaderTask, "uart", 4096, NULL, 1, NULL, 0);

  setupWifi();
  mqtt.setServer(SECRETS_MQTT_HOST, SECRETS_MQTT_PORT);
  mqtt.setCallback(onMqttMessage);
}

void loop() {
  if (!mqtt.connected()) mqttTryReconnect();
  mqtt.loop();

  // Procesar UIDs leídos por la tarea de UART
  UidMsg msg;
  while (xQueueReceive(uidQueue, &msg, 0) == pdTRUE) {
    Serial.print("UID -> ");
    Serial.println(msg.data);

    strncpy(current_user_rfid, msg.data, UID_MAX - 1);
    current_user_rfid[UID_MAX - 1] = '\0';

    screenSending();
    if (mqtt.connected()) {
      mqtt.publish(topicOf(TOPIC_QUERY).c_str(), msg.data);
    }
  }

  // Cierre automático de sesión por inactividad
  if (session_active && (millis() - session_start_time >= SESSION_TIMEOUT_MS)) {
    Serial.println("Timeout de sesion -> reenviando UID para cerrar");
    if (mqtt.connected() && current_user_rfid[0]) {
      mqtt.publish(topicOf(TOPIC_QUERY).c_str(), current_user_rfid);
    }
    session_active = false;  // el comando 'unload' del backend hará el resto
  }
}

// =========================================================================
// TAREA UART (core 0): lee bytes del Arduino+RC522 hasta '\t'.
// Usa char[] en lugar de String para no fragmentar el heap.
// =========================================================================
static void uartReaderTask(void* /*param*/) {
  static char buf[UID_MAX];
  static size_t len = 0;
  for (;;) {
    while (Serial2.available()) {
      char c = (char)Serial2.read();
      if (c == '\t') {
        if (len > 9 && len < UID_MAX) {  // mismo umbral que el firmware viejo
          buf[len] = '\0';
          UidMsg msg;
          memcpy(msg.data, buf, len + 1);
          xQueueSend(uidQueue, &msg, 0);
        }
        len = 0;
      } else if (isAlphaNumeric(c) && len < UID_MAX - 1) {
        buf[len++] = c;
      } else if (len >= UID_MAX - 1) {
        len = 0;  // overflow → descarta
      }
    }
    vTaskDelay(pdMS_TO_TICKS(5));
  }
}

// =========================================================================
// MQTT
// =========================================================================
static void onMqttMessage(char* topic, byte* payload, unsigned int length) {
  char incoming[64] = {0};
  size_t n = (length < sizeof(incoming) - 1) ? length : sizeof(incoming) - 1;
  memcpy(incoming, payload, n);
  incoming[n] = '\0';
  while (n > 0 && (incoming[n - 1] == ' ' || incoming[n - 1] == '\r' || incoming[n - 1] == '\n')) {
    incoming[--n] = '\0';
  }

  String t(topic);
  if (t == topicOf("user_name")) {
    strncpy(user_name, incoming, sizeof(user_name) - 1);
    user_name[sizeof(user_name) - 1] = '\0';
    return;
  }

  if (t == topicOf("command")) {
    if (strcmp(incoming, "found") == 0) {
      session_active = true;
      session_start_time = millis();
      blinkLed(4);
      screenLogged();
    } else if (strcmp(incoming, "nofound") == 0) {
      session_active = false;
      current_user_rfid[0] = '\0';
      blinkLed(4);
      screenNotFound();
    } else if (strcmp(incoming, "unload") == 0) {
      session_active = false;
      current_user_rfid[0] = '\0';
      blinkLed(4);
      screenLogout();
    }
  }
}

static void setupWifi() {
  Serial.println();
  Serial.print("Conectando a ");
  Serial.println(SECRETS_WIFI_SSID);

  WiFi.mode(WIFI_STA);
  if (!WiFi.config(LOCAL_IP, GATEWAY, SUBNET, DNS1, DNS2)) {
    Serial.println("STA Failed to configure");
  }
  WiFi.begin(SECRETS_WIFI_SSID, SECRETS_WIFI_PASSWORD);

  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED) {
    if (millis() - start > WIFI_CONNECT_TIMEOUT_MS) {
      Serial.println("\nWiFi timeout - reiniciando.");
      delay(500);
      ESP.restart();
    }
    delay(500);
    Serial.print(".");
  }
  Serial.print("\nIP: ");
  Serial.println(WiFi.localIP());
}

static void mqttTryReconnect() {
  if (millis() - last_mqtt_attempt < MQTT_RETRY_INTERVAL_MS) return;
  last_mqtt_attempt = millis();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("MQTT: WiFi caido, reintentando despues");
    return;
  }

  String clientId = String("esp32_") + DEVICE_SN + "_" + String(random(0xffff), HEX);
  String willTopic = topicOf("status");
  Serial.print("MQTT connect... ");
  bool ok = mqtt.connect(
      clientId.c_str(),
      SECRETS_MQTT_USER, SECRETS_MQTT_PASS,
      willTopic.c_str(), /*willQos*/ 1, /*willRetain*/ true, "offline");
  if (!ok) {
    Serial.print("fallo "); Serial.println(mqtt.state());
    return;
  }
  Serial.println("OK");
  mqtt.publish(willTopic.c_str(), "online", true);
  mqtt.subscribe(topicOf("command").c_str());
  mqtt.subscribe(topicOf("user_name").c_str());
}

// =========================================================================
// HELPERS
// =========================================================================
static void blinkLed(int times) {
  for (int i = 0; i < times; i++) {
    digitalWrite(BUILTIN_LED, HIGH);
    delay(150);
    digitalWrite(BUILTIN_LED, LOW);
    delay(150);
  }
}

static String topicOf(const char* suffix) {
  String t = DEVICE_SN;
  t += "/";
  t += suffix;
  return t;
}

// =========================================================================
// PANTALLAS OLED
// =========================================================================
static void screenIdle() {
  display.clearDisplay();
  display.setTextSize(2);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println("COLOCA TU");
  display.println("CREDENCIAL");
  display.display();
}

static void screenSending() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println("Enviando al servidor...");
  display.display();
}

static void screenLogged() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.print("HOLA ");
  display.println(user_name);
  display.println();
  display.println("Sesion iniciada");
  display.display();
}

static void screenNotFound() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println("Usuario no encontrado");
  display.println("Intenta de nuevo o");
  display.println("registrate.");
  display.display();
  delay(4000);
  screenIdle();
}

static void screenLogout() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println(user_name);
  display.println();
  display.println("Sesion finalizada");
  display.display();
  delay(3000);
  screenIdle();
}
