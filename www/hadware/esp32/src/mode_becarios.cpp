// SMART10000 — acceso de becarios al espacio.
// Publica UID a `scholar_query`; espera granted1/granted0/refused.
#ifdef MODE_BECARIOS

#include "mode.h"

const ModeConfig kMode = {
  /* serial_number */ "SMART10000",
  /* local_ip */ IPAddress(192, 168, 0, 185),
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

static void screenIngreso() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.print("HOLA ");
  display.println(user_name);
  display.println();
  display.println("Becario registrado");
  display.display();
  delay(4000);
  screenIdle();
}

static void screenSalida() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println("Adios becario...");
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
  publishOnTopic("scholar_query", uid);
}

void modeOnUserName(const char* name) {
  strncpy(user_name, name, sizeof(user_name) - 1);
  user_name[sizeof(user_name) - 1] = '\0';
}

void modeOnCommand(const char* cmd) {
  if (strcmp(cmd, "granted1") == 0 || strcmp(cmd, "open") == 0) {
    setRelays(true);
    blinkLed(2);
    screenIngreso();
  } else if (strcmp(cmd, "granted0") == 0 || strcmp(cmd, "close") == 0) {
    setRelays(false);
    blinkLed(2);
    screenSalida();
  } else if (strcmp(cmd, "refused") == 0) {
    blinkLed(4);
    screenRefused();
  }
}

void modeLoopTick() {}

#endif
