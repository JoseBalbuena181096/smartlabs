// SMART10003 — prestamo unificado credencial+herramienta (HW-UNIF).
//
// Sin sesion -> publica UID a `loan_queryu` (backend abre sesion).
// Con sesion -> publica UID a `loan_querye` (backend resuelve cierre / refused
// / prestamo / devolucion).
//
// Comandos esperados desde {SN}/command:
//   found / nofound / unload      flujo de sesion
//   prestado / devuelto / nofound flujo de equipment
//   refused                       credencial ajena durante sesion activa
//   nologin                       backend dice que no hay sesion (resync)
//
// Timeout de inactividad: 100 s. Cualquier interaccion lo resetea. Al expirar
// republicamos el UID original a loan_queryu y el backend cierra con `unload`.
#ifdef MODE_PRESTAMO

#include "mode.h"

const ModeConfig kMode = {
  /* serial_number */ "SMART10003",
  /* local_ip */ IPAddress(192, 168, 0, 34),
  /* gateway  */ IPAddress(192, 168, 0, 1),
  /* subnet   */ IPAddress(255, 255, 255, 0),
  /* dns1     */ IPAddress(8, 8, 8, 8),
  /* dns2     */ IPAddress(8, 8, 4, 4),
};

static const unsigned long INACTIVITY_TIMEOUT_MS = 100000UL;
static const size_t        UID_MAX               = 24;

static bool          session_active     = false;
static unsigned long last_activity_ms   = 0;
static char          pending_uid[UID_MAX] = {0};
static char          session_uid[UID_MAX] = {0};
static char          user_name[64]      = {0};
static char          equipment_name[64] = {0};

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
  if (session_active) screenLogged(); else screenIdle();
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

void modeSetup() {
  screenIdle();
}

void modeOnUidRead(const char* uid) {
  strncpy(pending_uid, uid, UID_MAX - 1);
  pending_uid[UID_MAX - 1] = '\0';
  screenSending();
  publishOnTopic(session_active ? "loan_querye" : "loan_queryu", uid);
  last_activity_ms = millis();
}

void modeOnUserName(const char* name) {
  if (session_active) {
    strncpy(equipment_name, name, sizeof(equipment_name) - 1);
    equipment_name[sizeof(equipment_name) - 1] = '\0';
  } else {
    strncpy(user_name, name, sizeof(user_name) - 1);
    user_name[sizeof(user_name) - 1] = '\0';
  }
}

void modeOnCommand(const char* cmd) {
  last_activity_ms = millis();
  if (strcmp(cmd, "found") == 0) {
    session_active = true;
    strncpy(session_uid, pending_uid, UID_MAX - 1);
    session_uid[UID_MAX - 1] = '\0';
    blinkLed(2);
    screenLogged();
  } else if (strcmp(cmd, "nofound") == 0) {
    blinkLed(4);
    screenNotFound();
  } else if (strcmp(cmd, "unload") == 0) {
    session_active = false;
    session_uid[0]    = '\0';
    pending_uid[0]    = '\0';
    user_name[0]      = '\0';
    equipment_name[0] = '\0';
    blinkLed(2);
    screenLogout();
  } else if (strcmp(cmd, "prestado") == 0) {
    blinkLed(2);
    screenLent();
  } else if (strcmp(cmd, "devuelto") == 0) {
    blinkLed(2);
    screenReturned();
  } else if (strcmp(cmd, "refused") == 0) {
    blinkLed(4);
    screenRefused();
  } else if (strcmp(cmd, "nologin") == 0) {
    session_active = false;
    session_uid[0] = '\0';
    blinkLed(4);
    screenLogout();
  }
}

void modeLoopTick() {
  if (session_active && (millis() - last_activity_ms >= INACTIVITY_TIMEOUT_MS)) {
    Serial.println("[prestamo] timeout inactividad - solicitando cierre");
    if (session_uid[0]) {
      publishOnTopic("loan_queryu", session_uid);
    }
    last_activity_ms = millis(); // evita spam si el backend no responde
  }
}

#endif
