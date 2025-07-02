
#include <Arduino.h>
#include <WiFi.h>
#include <PubSubClient.h>
#include <SPI.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SH110X.h>

const String serial_number = "SMART10001";

#define RXD2 16
#define TXD2 17

TaskHandle_t Task1;

const char *ssid = "dlink";
const char *password = "angelsnek2510";

bool state_device = false;

// para evitar que el dhcp nos asigne ip, o si el ruter no cuenta con dhcp
// podemos seleccionar una ip fija si no lo usas comentar las 5 líneas
IPAddress local_IP(192, 168, 0, 123);
IPAddress gateway(192, 168, 0, 1);
IPAddress subnet(255, 255, 255, 0);
IPAddress primaryDNS(8, 8, 8, 8);
IPAddress secondaryDNS(8, 8, 4, 4);

//*****************************
//***   CONFIGURACION MQTT  ***
//*****************************
const char *mqtt_server = "192.168.0.100";
const int mqtt_port = 1883;
const char *mqtt_user = "jose";
const char *mqtt_pass = "public";

WiFiClient espClient;
PubSubClient client(espClient);

long lastMsg = 0;
char msg[25];
bool send_access_query = false;

//********************************
//***   CONFIGURACION OLED     ***
//********************************

/* Uncomment the initialize the I2C address , uncomment only one, If you get a totally blank screen try the other*/
#define i2c_Address 0x3c // initialize with the I2C addr 0x3C Typically eBay OLED's
// #define i2c_Address 0x3d //initialize with the I2C addr 0x3D Typically Adafruit OLED's

#define SCREEN_WIDTH 128 // OLED display width, in pixels
#define SCREEN_HEIGHT 64 // OLED display height, in pixels
#define OLED_RESET -1    //   QT-PY / XIAO
Adafruit_SH1106G display = Adafruit_SH1106G(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

String rfid = "";
String user_name = "";

//*****************************
//*** DECLARACION FUNCIONES ***
//*****************************
void setup_wifi();
void callback(char *topic, byte *payload, unsigned int length);
void reconnect();
void access_screen(bool access);
void closing();
void iddle();
void sending();

//*****************************
//***   SENSOR INT TEMP     ***
//*****************************

#ifdef __cplusplus
extern "C"
{
#endif

    uint8_t temprature_sens_read();

#ifdef __cplusplus
}
#endif

uint8_t temprature_sens_read();

//*****************************
//***   TAREA OTRO NUCLEO   ***
//*****************************

void codeForTask1(void *parameter)
{
    const char STOP_CHAR = '\t';
    const unsigned long TIMEOUT = 1000; // 1 segundo de timeout

    for (;;)
    {

        unsigned long startTime = millis();

        while (millis() - startTime < TIMEOUT)
        {
            if (Serial2.available())
            {
                char char_now = char(Serial2.read());
                if (char_now == STOP_CHAR)
                {
                    break;
                }
                if (isAlphaNumeric(char_now))
                {
                    rfid += char_now;
                }
            }
            vTaskDelay(1); // Cede el control brevemente
        }

        if (rfid.length() > 9)
        {
            Serial.println("Se busca el dispositivo -> " + rfid);
            send_access_query = true;
        }
        vTaskDelay(10);
    }
}

void setup()
{

    pinMode(BUILTIN_LED, OUTPUT);
    pinMode(13, OUTPUT);
    pinMode(12, OUTPUT);
    pinMode(14, OUTPUT);

    Serial.begin(115200);
    Serial2.begin(9600, SERIAL_8N1, RXD2, TXD2);
    digitalWrite(12, 0);
    digitalWrite(13, 0);
    digitalWrite(14, 0);
    randomSeed(micros());

    xTaskCreatePinnedToCore(
        codeForTask1, /* Task function. */
        "Task_1",     /* name of task. */
        1000,         /* Stack size of task */
        NULL,         /* parameter of the task */
        1,            /* priority of the task */
        &Task1,       /* Task handle to keep track of created task */
        0);           /* Core */

    // Iniciamos display LCD
    display.begin();
    // Limpia la pantalla
    display.clearDisplay();
    iddle();

    setup_wifi();
    client.setServer(mqtt_server, mqtt_port);
    client.setCallback(callback);
}

void loop()
{
    if (!client.connected())
    {
        reconnect();
    }

    client.loop();

    long now = millis();

    if (now - lastMsg > 2000)
    {
        lastMsg = now;
        String to_send = String((temprature_sens_read() - 32) / 1.8);
        to_send.toCharArray(msg, 25);

        char topic[25];
        String topic_aux = serial_number + "/temp";
        topic_aux.toCharArray(topic, 25);

        client.publish(topic, msg);
    }

    if (send_access_query == true)
    {

        String to_send = rfid;
        rfid = "";

        sending();
        to_send.toCharArray(msg, 25);

        char topic[25];
        String topic_aux = serial_number + "/access_query";
        topic_aux.toCharArray(topic, 25);

        client.publish(topic, msg);

        send_access_query = false;

        rfid = "";
    }
}

//*****************************
//*** PANTALLAS ACCESO      ***
//*****************************

void access_screen(bool access)
{

    if (access)
    {
        display.clearDisplay();
        display.setTextSize(1);
        display.setTextColor(SH110X_WHITE);
        display.setCursor(0, 0);
        display.println("HOLA " + user_name);
        display.println("");
        display.println("DISPOSITIVO ENCENDIDO");
        display.display();
        delay(4500);
    }
    else
    {
        display.clearDisplay();
        display.setTextSize(1);
        display.setTextColor(SH110X_WHITE);
        display.setCursor(0, 0);
        display.println("USUARIO NO ENCONTRADO, INTENTE DE NUEVO");
        display.display();
        delay(4000);
        iddle();
    }
}

void closing()
{
    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SH110X_WHITE);
    display.setCursor(0, 0);
    display.setTextSize(1);
    display.println("APAGANDO EQUIPO ... ");
    display.display();
    delay(2500);
    iddle();
}

void sending()
{
    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SH110X_WHITE);
    display.setCursor(0, 0);
    display.setTextSize(1);
    display.println("ESPERA ENVIANDO AL SERVIDOR");
    display.display();
    delay(300);
    display.print(".");
    display.display();
    delay(300);
    display.print(".");
    display.display();
    delay(300);
    display.print(".");
    display.display();
    delay(300);
}

void iddle()
{
    // Limpia la pantalla
    display.clearDisplay();
    // Establece el tamaño del texto
    display.setTextSize(2);
    // Establece el color del texto a blanco
    display.setTextColor(SH110X_WHITE);
    // Establece la posición de inicio del texto
    display.setCursor(0, 0);
    display.println("COLOCA TU TARGETA EN EL LECTOR");
    display.display();
}

//*****************************
//***    CONEXION WIFI      ***
//*****************************
void setup_wifi()
{
    delay(10);
    // este if intentará implementar las ip que seleccionamos si no se usa comentar el if completo
    if (!WiFi.config(local_IP, gateway, subnet, primaryDNS, secondaryDNS))
    {
        Serial.println("STA Failed to configure");
    }

    // Nos conectamos a nuestra red Wifi
    Serial.println();
    Serial.print("Conectando a ");
    Serial.println(ssid);

    WiFi.begin(ssid, password);

    while (WiFi.status() != WL_CONNECTED)
    {
        delay(500);
        Serial.print(".");
    }

    Serial.println("");
    Serial.println("Conectado a red WiFi!");
    Serial.println("Dirección IP: ");
    Serial.println(WiFi.localIP());
}

void callback(char *topic, byte *payload, unsigned int length)
{
    String incoming = "";
    Serial.print("Mensaje recibido desde -> ");
    Serial.print(topic);
    Serial.println("");
    for (int i = 0; i < length; i++)
    {
        incoming += (char)payload[i];
    }
    incoming.trim();
    Serial.println("Mensaje -> " + incoming);

    String str_topic(topic);

    if (str_topic == serial_number + "/command")
    {

        if (incoming == "open")
        {
            state_device = true;
            digitalWrite(BUILTIN_LED, state_device);
            digitalWrite(12, state_device);
            digitalWrite(13, state_device);
            digitalWrite(14, state_device);
            // opening();
        }

        if (incoming == "close")
        {
            state_device = false;
            digitalWrite(BUILTIN_LED, state_device);
            digitalWrite(12, state_device);
            digitalWrite(13, state_device);
            digitalWrite(14, state_device);
            closing();
        }

        if (incoming == "granted1")
        {
            state_device = true;
            digitalWrite(BUILTIN_LED, state_device);
            digitalWrite(12, state_device);
            digitalWrite(13, state_device);
            digitalWrite(14, state_device);
            access_screen(state_device);
        }
        if (incoming == "granted0")
        {
            state_device = false;
            digitalWrite(12, state_device);
            digitalWrite(13, state_device);
            digitalWrite(14, state_device);
            digitalWrite(BUILTIN_LED, state_device);
            closing();
        }

        if (incoming == "refused")
        {
            // state_device = false;
            // digitalWrite(BUILTIN_LED, state_device);
            // digitalWrite(12, state_device);
            // digitalWrite(13, state_device);
            // digitalWrite(14, state_device);
            access_screen(false);
        }
    }

    if (str_topic == serial_number + "/user_name")
    {
        user_name = incoming;
    }
}

void reconnect()
{

    while (!client.connected())
    {
        Serial.print("Intentando conexión Mqtt...");
        // Creamos un cliente ID
        String clientId = "esp32_";
        clientId += String(random(0xffff), HEX);
        // Intentamos conectar
        if (client.connect(clientId.c_str(), mqtt_user, mqtt_pass))
        {
            Serial.println("Conectado!");

            // Nos suscribimos a comandos
            char topic[25];
            String topic_aux = serial_number + "/command";
            topic_aux.toCharArray(topic, 25);
            client.subscribe(topic);

            // Nos suscribimos a username
            char topic2[25];
            String topic_aux2 = serial_number + "/user_name";
            topic_aux2.toCharArray(topic2, 25);
            client.subscribe(topic2);
        }
        else
        {
            Serial.print("falló :( con error -> ");
            Serial.print(client.state());
            Serial.println(" Intentamos de nuevo en 5 segundos");
            delay(2000);
        }
    }
}
