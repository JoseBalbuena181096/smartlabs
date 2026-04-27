// SMARTLABS · ESP32 lector UNIFICADO de préstamo (SMART10003)
// =========================================================================
// Reemplaza a esp32_prestamo_lector_USUARIO.cpp + esp32_prestamo_lector_HERRAMIENTA.cpp.
// Una sola caja física: el lector inicia sesión cuando se pasa una credencial,
// permite cargar herramientas mientras la sesión está abierta, y se cierra al
// pasar la misma credencial otra vez o tras N minutos de inactividad.
//
// Decide a qué topic publicar según su estado local:
//   - Sin sesión   → {SN}/loan_queryu  (el backend abre sesión)
//   - Con sesión   → {SN}/loan_querye  (el backend resuelve: si UID == credencial
//                                       del usuario actual cierra; si es otra
//                                       credencial conocida rechaza con 'refused';
//                                       si es equipment, registra préstamo).
//
// Comandos que reconoce (publish del backend a {SN}/command):
//   found / nofound / unload         — flujo de sesión
//   prestado / devuelto / nofound    — flujo de equipment
//   refused                          — credencial ajena durante sesión activa
//   nologin                          — por completitud (no debería ocurrir aquí)
//
// Diferencias con los firmware separados:
//   - Una sola QueueHandle_t y un solo loop.
//   - Timer de inactividad refrescado en CADA interacción (no solo login),
//     para que múltiples préstamos extiendan la sesión.
//   - `pending_uid` se conserva mientras esperamos respuesta del backend, así
//     cuando llega 'found' sabemos qué UID abrió esta sesión y podemos enviar
//     el cierre auto en el timeout.

#include <Arduino.h>
#include <WiFi.h>
#include <PubSubClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SH110X.h>

#include "secrets.h"

// -------------------------------------------------------------------------
// CONFIGURACIÓN
// -------------------------------------------------------------------------
static const char*  DEVICE_SN     = "SMART10003";
static const char*  TOPIC_QUERY_U = "loan_queryu";  // sin sesión
static const char*  TOPIC_QUERY_E = "loan_querye";  // con sesión
static const IPAddress LOCAL_IP(192, 168, 0, 34);
static const IPAddress GATEWAY (192, 168, 0, 1);
static const IPAddress SUBNET  (255, 255, 255, 0);
static const IPAddress DNS1    (8, 8, 8, 8);
static const IPAddress DNS2    (8, 8, 4, 4);

static const unsigned long WIFI_CONNECT_TIMEOUT_MS = 30000;
static const unsigned long MQTT_RETRY_INTERVAL_MS  = 5000;
static const unsigned long INACTIVITY_TIMEOUT_MS   = 180000; // 3 min

#define RXD2          16
#define TXD2          17
#define OLED_RESET    -1
#define SCREEN_WIDTH  128
#define SCREEN_HEIGHT 64
#define I2C_ADDRESS   0x3c
static const uint8_t RELAY_PINS[] = {12, 13, 14};

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
static unsigned long last_activity_ms   = 0;
static char          pending_uid[UID_MAX] = {0};   // último UID enviado, para asociar con 'found'
static char          session_uid[UID_MAX] = {0};   // UID que abrió la sesión, usado para timeout
static char          user_name[64]        = {0};
static char          equipment_name[64]   = {0};
static unsigned long last_mqtt_attempt    = 0;

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
static void screenLogout();
static void screenNotFound();
static void screenLent();
static void screenReturned();
static void screenRefused();
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

  // Procesar UIDs leídos
  UidMsg msg;
  while (xQueueReceive(uidQueue, &msg, 0) == pdTRUE) {
    Serial.printf("UID -> %s (sesión=%d)\n", msg.data, session_active);

    // Recordamos el UID por si el backend responde 'found' para esta lectura.
    strncpy(pending_uid, msg.data, UID_MAX - 1);
    pending_uid[UID_MAX - 1] = '\0';

    screenSending();
    if (mqtt.connected()) {
      const char* suffix = session_active ? TOPIC_QUERY_E : TOPIC_QUERY_U;
      mqtt.publish(topicOf(suffix).c_str(), msg.data);
    }
    last_activity_ms = millis();
  }

  // Timeout de inactividad: cerrar la sesión publicando el UID original a
  // loan_queryu (el backend hace toggle y manda 'unload').
  if (session_active && (millis() - last_activity_ms >= INACTIVITY_TIMEOUT_MS)) {
    Serial.println("Timeout de inactividad: solicitando cierre de sesión");
    if (mqtt.connected() && session_uid[0]) {
      mqtt.publish(topicOf(TOPIC_QUERY_U).c_str(), session_uid);
    }
    last_activity_ms = millis(); // evita spamear si no hay respuesta
  }
}

// =========================================================================
// TAREA UART
// =========================================================================
static void uartReaderTask(void* /*param*/) {
  static char buf[UID_MAX];
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
    // El backend usa este topic para enviar tanto el nombre del usuario
    // (al inicio de sesión) como el nombre del equipo (en cada préstamo).
    // Decidimos a qué buffer va según el estado de sesión.
    if (session_active) {
      strncpy(equipment_name, incoming, sizeof(equipment_name) - 1);
      equipment_name[sizeof(equipment_name) - 1] = '\0';
    } else {
      strncpy(user_name, incoming, sizeof(user_name) - 1);
      user_name[sizeof(user_name) - 1] = '\0';
    }
    return;
  }

  if (t != topicOf("command")) return;

  // refrescar el timer ante cualquier respuesta del backend
  last_activity_ms = millis();

  if (strcmp(incoming, "found") == 0) {
    session_active = true;
    strncpy(session_uid, pending_uid, UID_MAX - 1);
    session_uid[UID_MAX - 1] = '\0';
    blinkLed(2);
    screenLogged();
  } else if (strcmp(incoming, "nofound") == 0) {
    blinkLed(4);
    screenNotFound();
  } else if (strcmp(incoming, "unload") == 0) {
    session_active = false;
    session_uid[0] = '\0';
    pending_uid[0] = '\0';
    user_name[0] = '\0';
    equipment_name[0] = '\0';
    blinkLed(2);
    screenLogout();
  } else if (strcmp(incoming, "prestado") == 0) {
    blinkLed(2);
    screenLent();
  } else if (strcmp(incoming, "devuelto") == 0) {
    blinkLed(2);
    screenReturned();
  } else if (strcmp(incoming, "refused") == 0) {
    blinkLed(4);
    screenRefused();
  } else if (strcmp(incoming, "nologin") == 0) {
    // El backend dice que no hay sesión pero localmente creíamos que sí.
    // Sincronizar: tratar como cierre.
    session_active = false;
    session_uid[0] = '\0';
    blinkLed(4);
    screenLogout();
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
    Serial.println("MQTT: WiFi caido");
    return;
  }

  String clientId = String("esp32_") + DEVICE_SN + "_" + String(random(0xffff), HEX);
  String willTopic = topicOf("status");
  Serial.print("MQTT connect... ");
  bool ok = mqtt.connect(
      clientId.c_str(),
      SECRETS_MQTT_USER, SECRETS_MQTT_PASS,
      willTopic.c_str(), 1, true, "offline");
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
  display.println("Coloca herramientas o");
  display.println("vuelve a pasar tu");
  display.println("credencial para cerrar.");
  display.display();
}

static void screenLogout() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println(user_name[0] ? user_name : "OK");
  display.println();
  display.println("Sesion finalizada");
  display.display();
  delay(2500);
  screenIdle();
}

static void screenNotFound() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  if (session_active) {
    display.println("Tag no reconocido");
    display.println("(no es equipo ni tu");
    display.println("credencial).");
  } else {
    display.println("Usuario no encontrado");
    display.println("Intenta de nuevo o");
    display.println("registrate.");
  }
  display.display();
  delay(3500);
  if (session_active) {
    screenLogged();
  } else {
    screenIdle();
  }
}

static void screenLent() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println(equipment_name);
  display.println();
  display.println("Equipo prestado");
  display.display();
  delay(3000);
  screenLogged();
}

static void screenReturned() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println(equipment_name);
  display.println();
  display.println("Equipo devuelto");
  display.display();
  delay(3000);
  screenLogged();
}

static void screenRefused() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println("Credencial ajena.");
  display.println();
  display.println("Hay otra sesion activa.");
  display.println("Espera a que termine.");
  display.display();
  delay(3500);
  screenLogged();
}
