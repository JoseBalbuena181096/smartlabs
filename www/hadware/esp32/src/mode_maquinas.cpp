// SMART10001 — acceso a maquinas / mesas.
// Publica UID a `access_query`; el backend hace toggle (granted1/granted0).
#ifdef MODE_MAQUINAS

#include "mode.h"

const ModeConfig kMode = {
  /* serial_number */ "SMART10001",
  /* local_ip */ IPAddress(192, 168, 0, 123),
  /* gateway  */ IPAddress(192, 168, 0, 1),
  /* subnet   */ IPAddress(255, 255, 255, 0),
  /* dns1     */ IPAddress(8, 8, 8, 8),
  /* dns2     */ IPAddress(8, 8, 4, 4),
};

static char user_name[64] = {0};

static void screenIdle() {
  display.clearDisplay();
  display.setTextSize(2);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println("COLOCA TU");
  display.println("TARJETA");
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

static void screenAccessGranted() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.print("HOLA ");
  display.println(user_name);
  display.println();
  display.println("Equipo encendido");
  display.display();
}

static void screenAccessClosing() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println("Apagando equipo...");
  display.display();
  delay(2500);
  screenIdle();
}

static void screenRefused() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println("Usuario no encontrado");
  display.println("Intenta de nuevo.");
  display.display();
  delay(4000);
  screenIdle();
}

void modeSetup() {
  screenIdle();
}

void modeOnUidRead(const char* uid) {
  screenSending();
  publishOnTopic("access_query", uid);
}

void modeOnUserName(const char* name) {
  strncpy(user_name, name, sizeof(user_name) - 1);
  user_name[sizeof(user_name) - 1] = '\0';
}

void modeOnCommand(const char* cmd) {
  if (strcmp(cmd, "granted1") == 0 || strcmp(cmd, "open") == 0) {
    setRelays(true);
    blinkLed(2);
    screenAccessGranted();
  } else if (strcmp(cmd, "granted0") == 0 || strcmp(cmd, "close") == 0) {
    setRelays(false);
    blinkLed(2);
    screenAccessClosing();
  } else if (strcmp(cmd, "refused") == 0) {
    blinkLed(4);
    screenRefused();
  }
}

void modeLoopTick() {}

#endif
