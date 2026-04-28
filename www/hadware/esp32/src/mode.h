// Interfaz comun entre el scaffolding (main.cpp) y los modos por estacion
// (mode_becarios.cpp, mode_maquinas.cpp, mode_prestamo.cpp).
//
// Cada mode_*.cpp queda envuelto en `#ifdef MODE_<X>` -> exactamente uno
// aporta `kMode` y los handlers, los demas son traducciones vacias.
#pragma once

#include <Arduino.h>
#include <IPAddress.h>
#include <Adafruit_SH110X.h>

struct ModeConfig {
  const char* serial_number;   // p.ej. "SMART10003" — tambien es el hostname mDNS y el prefijo de topic
  IPAddress   local_ip;
  IPAddress   gateway;
  IPAddress   subnet;
  IPAddress   dns1;
  IPAddress   dns2;
};

// Implementado por exactamente un mode_*.cpp segun el flag MODE_<X>.
extern const ModeConfig kMode;

// OLED compartido. Lo aporta main.cpp; los modos pintan directo sobre el.
extern Adafruit_SH1106G display;

// Helpers que main.cpp expone a los modos.
String topicOf(const char* suffix);                  // "{SN}/{suffix}"
void   publishOnTopic(const char* suffix, const char* payload);
bool   mqttIsConnected();
void   setRelays(bool on);
void   blinkLed(int times);

// Hooks que cada mode_*.cpp implementa.
void modeSetup();                       // tras OLED listo, antes de devolver setup()
void modeOnUidRead(const char* uid);    // UID llegado por UART (Arduino + RC522)
void modeOnCommand(const char* cmd);    // payload llegado a {SN}/command
void modeOnUserName(const char* name);  // payload llegado a {SN}/user_name
void modeLoopTick();                    // se invoca cada loop(); usar para timeouts
