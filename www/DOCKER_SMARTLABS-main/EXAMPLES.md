# Ejemplos de Uso - SMARTLABS Docker Infrastructure

## Tabla de Contenidos

1. [Configuración Inicial](#configuración-inicial)
2. [Ejemplos de Conexión MQTT](#ejemplos-de-conexión-mqtt)
3. [Integración con Aplicaciones Web](#integración-con-aplicaciones-web)
4. [Integración con Aplicaciones Móviles](#integración-con-aplicaciones-móviles)
5. [Dispositivos IoT](#dispositivos-iot)
6. [API REST y WebSocket](#api-rest-y-websocket)
7. [Consultas de Base de Datos](#consultas-de-base-de-datos)
8. [Scripts de Automatización](#scripts-de-automatización)
9. [Casos de Uso Específicos](#casos-de-uso-específicos)

## Configuración Inicial

### 1. Configuración Básica del Entorno

#### Archivo .env de Ejemplo

```env
# Configuración de MariaDB
MARIADB_ROOT_PASSWORD=emqxpass
MARIADB_USER=emqxuser
MARIADB_PASSWORD=emqxpass
MARIADB_DATABASE=emqx

# Zona horaria
TZ=America/Mexico_City

# Configuración adicional
EMQX_ADMIN_PASSWORD=emqxpass
EMQX_MAX_CONNECTIONS=1000
DEBUG_MODE=false
```

#### Inicialización del Sistema

```bash
#!/bin/bash
# init_smartlabs.sh - Script de inicialización

echo "Inicializando SMARTLABS..."

# Verificar Docker
if ! command -v docker &> /dev/null; then
    echo "Error: Docker no está instalado"
    exit 1
fi

# Verificar Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo "Error: Docker Compose no está instalado"
    exit 1
fi

# Iniciar servicios
echo "Iniciando servicios..."
docker-compose up -d

# Esperar a que los servicios estén listos
echo "Esperando a que los servicios estén listos..."
sleep 30

# Verificar estado
echo "Verificando estado de servicios..."
docker-compose ps

# Verificar conectividad MQTT
echo "Verificando conectividad MQTT..."
if timeout 5 bash -c "</dev/tcp/localhost/1883"; then
    echo "✓ MQTT Broker: OK"
else
    echo "✗ MQTT Broker: ERROR"
fi

# Verificar dashboard
echo "Verificando dashboard EMQX..."
if curl -s http://localhost:18083 >/dev/null; then
    echo "✓ Dashboard EMQX: OK"
else
    echo "✗ Dashboard EMQX: ERROR"
fi

echo "Inicialización completada!"
echo "Dashboard EMQX: http://localhost:18083 (admin/emqxpass)"
echo "phpMyAdmin: http://localhost:4001"
echo "MQTT Broker: localhost:1883"
```

## Ejemplos de Conexión MQTT

### 1. Cliente MQTT en Python

#### Publicador de Datos

```python
#!/usr/bin/env python3
# mqtt_publisher.py - Publicador de datos MQTT

import paho.mqtt.client as mqtt
import json
import time
import random
from datetime import datetime

class SmartLabsPublisher:
    def __init__(self, broker_host="localhost", broker_port=1883):
        self.broker_host = broker_host
        self.broker_port = broker_port
        self.client = mqtt.Client()
        self.device_id = "SMART00001"
        
        # Configurar callbacks
        self.client.on_connect = self.on_connect
        self.client.on_publish = self.on_publish
        self.client.on_disconnect = self.on_disconnect
        
        # Configurar autenticación
        self.client.username_pw_set("emqx", "emqxpass")
    
    def on_connect(self, client, userdata, flags, rc):
        if rc == 0:
            print(f"Conectado al broker MQTT: {self.broker_host}:{self.broker_port}")
        else:
            print(f"Error de conexión: {rc}")
    
    def on_publish(self, client, userdata, mid):
        print(f"Mensaje publicado: {mid}")
    
    def on_disconnect(self, client, userdata, rc):
        print("Desconectado del broker MQTT")
    
    def connect(self):
        try:
            self.client.connect(self.broker_host, self.broker_port, 60)
            self.client.loop_start()
            return True
        except Exception as e:
            print(f"Error al conectar: {e}")
            return False
    
    def publish_sensor_data(self):
        """Publicar datos de sensores simulados"""
        data = {
            "device_id": self.device_id,
            "timestamp": datetime.now().isoformat(),
            "temperature": round(random.uniform(20.0, 35.0), 2),
            "humidity": round(random.uniform(40.0, 80.0), 2),
            "voltage": round(random.uniform(3.0, 5.0), 2),
            "status": "active"
        }
        
        topic = f"smartlabs/{self.device_id}/data"
        payload = json.dumps(data)
        
        result = self.client.publish(topic, payload, qos=1)
        return result.rc == mqtt.MQTT_ERR_SUCCESS
    
    def publish_access_event(self, user_id, access_granted=True):
        """Publicar evento de acceso"""
        data = {
            "device_id": self.device_id,
            "timestamp": datetime.now().isoformat(),
            "user_id": user_id,
            "access_granted": access_granted,
            "event_type": "access_control"
        }
        
        topic = f"smartlabs/{self.device_id}/access"
        payload = json.dumps(data)
        
        result = self.client.publish(topic, payload, qos=1)
        return result.rc == mqtt.MQTT_ERR_SUCCESS
    
    def disconnect(self):
        self.client.loop_stop()
        self.client.disconnect()

# Ejemplo de uso
if __name__ == "__main__":
    publisher = SmartLabsPublisher()
    
    if publisher.connect():
        print("Iniciando publicación de datos...")
        
        try:
            for i in range(10):
                # Publicar datos de sensores
                if publisher.publish_sensor_data():
                    print(f"Datos de sensores publicados: {i+1}")
                
                # Simular evento de acceso ocasional
                if random.random() < 0.3:
                    user_id = f"USER{random.randint(1, 5):03d}"
                    access_granted = random.choice([True, True, True, False])  # 75% éxito
                    if publisher.publish_access_event(user_id, access_granted):
                        print(f"Evento de acceso publicado: {user_id} - {'Permitido' if access_granted else 'Denegado'}")
                
                time.sleep(5)
                
        except KeyboardInterrupt:
            print("\nDeteniendo publicación...")
        
        finally:
            publisher.disconnect()
    else:
        print("No se pudo conectar al broker MQTT")
```

#### Suscriptor de Datos

```python
#!/usr/bin/env python3
# mqtt_subscriber.py - Suscriptor de datos MQTT

import paho.mqtt.client as mqtt
import json
import mysql.connector
from datetime import datetime

class SmartLabsSubscriber:
    def __init__(self, broker_host="localhost", broker_port=1883):
        self.broker_host = broker_host
        self.broker_port = broker_port
        self.client = mqtt.Client()
        
        # Configurar callbacks
        self.client.on_connect = self.on_connect
        self.client.on_message = self.on_message
        self.client.on_disconnect = self.on_disconnect
        
        # Configurar autenticación
        self.client.username_pw_set("emqx", "emqxpass")
        
        # Configurar base de datos
        self.db_config = {
            'host': 'localhost',
            'port': 4000,
            'user': 'emqxuser',
            'password': 'emqxpass',
            'database': 'emqx'
        }
    
    def on_connect(self, client, userdata, flags, rc):
        if rc == 0:
            print(f"Conectado al broker MQTT: {self.broker_host}:{self.broker_port}")
            # Suscribirse a todos los topics de SMARTLABS
            client.subscribe("smartlabs/+/data", qos=1)
            client.subscribe("smartlabs/+/access", qos=1)
            client.subscribe("smartlabs/+/status", qos=1)
            print("Suscrito a topics de SMARTLABS")
        else:
            print(f"Error de conexión: {rc}")
    
    def on_message(self, client, userdata, msg):
        try:
            topic = msg.topic
            payload = json.loads(msg.payload.decode())
            
            print(f"Mensaje recibido en {topic}:")
            print(json.dumps(payload, indent=2))
            
            # Procesar según el tipo de mensaje
            if "/data" in topic:
                self.process_sensor_data(payload)
            elif "/access" in topic:
                self.process_access_event(payload)
            elif "/status" in topic:
                self.process_status_update(payload)
                
        except Exception as e:
            print(f"Error procesando mensaje: {e}")
    
    def process_sensor_data(self, data):
        """Procesar datos de sensores y guardar en base de datos"""
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor()
            
            # Insertar en tabla data
            query = """
                INSERT INTO data (data_temp1, data_temp2, data_volts) 
                VALUES (%s, %s, %s)
            """
            
            values = (
                data.get('temperature', 0),
                data.get('humidity', 0),
                data.get('voltage', 0)
            )
            
            cursor.execute(query, values)
            conn.commit()
            
            print(f"Datos de sensores guardados en BD: ID {cursor.lastrowid}")
            
        except Exception as e:
            print(f"Error guardando datos de sensores: {e}")
        finally:
            if 'conn' in locals():
                conn.close()
    
    def process_access_event(self, data):
        """Procesar evento de acceso"""
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor()
            
            # Buscar habitante por user_id (simulado)
            device_id = data.get('device_id', 'UNKNOWN')
            access_granted = data.get('access_granted', False)
            
            # Insertar en tabla traffic
            query = """
                INSERT INTO traffic (traffic_hab_id, traffic_device, traffic_state) 
                VALUES (%s, %s, %s)
            """
            
            values = (
                1,  # ID de habitante por defecto
                device_id,
                access_granted
            )
            
            cursor.execute(query, values)
            conn.commit()
            
            print(f"Evento de acceso guardado: {device_id} - {'Permitido' if access_granted else 'Denegado'}")
            
        except Exception as e:
            print(f"Error guardando evento de acceso: {e}")
        finally:
            if 'conn' in locals():
                conn.close()
    
    def process_status_update(self, data):
        """Procesar actualización de estado"""
        print(f"Estado actualizado para dispositivo {data.get('device_id')}: {data.get('status')}")
    
    def on_disconnect(self, client, userdata, rc):
        print("Desconectado del broker MQTT")
    
    def start_listening(self):
        try:
            self.client.connect(self.broker_host, self.broker_port, 60)
            print("Iniciando escucha de mensajes MQTT...")
            self.client.loop_forever()
        except Exception as e:
            print(f"Error al conectar: {e}")

# Ejemplo de uso
if __name__ == "__main__":
    subscriber = SmartLabsSubscriber()
    
    try:
        subscriber.start_listening()
    except KeyboardInterrupt:
        print("\nDeteniendo suscriptor...")
        subscriber.client.disconnect()
```

### 2. Cliente MQTT en JavaScript (Node.js)

#### Aplicación Web con WebSocket

```javascript
// mqtt_client.js - Cliente MQTT para aplicaciones web

const mqtt = require('mqtt');
const WebSocket = require('ws');

class SmartLabsWebClient {
    constructor(options = {}) {
        this.mqttBroker = options.mqttBroker || 'ws://localhost:8073';
        this.wsPort = options.wsPort || 3001;
        this.mqttOptions = {
            username: options.username || 'emqx',
            password: options.password || 'emqxpass',
            clientId: `smartlabs_web_${Math.random().toString(16).substr(2, 8)}`
        };
        
        this.mqttClient = null;
        this.wsServer = null;
        this.connectedClients = new Set();
    }
    
    // Conectar al broker MQTT
    connectMQTT() {
        return new Promise((resolve, reject) => {
            this.mqttClient = mqtt.connect(this.mqttBroker, this.mqttOptions);
            
            this.mqttClient.on('connect', () => {
                console.log('Conectado al broker MQTT via WebSocket');
                
                // Suscribirse a topics relevantes
                this.mqttClient.subscribe('smartlabs/+/data', { qos: 1 });
                this.mqttClient.subscribe('smartlabs/+/access', { qos: 1 });
                this.mqttClient.subscribe('smartlabs/+/status', { qos: 1 });
                
                resolve();
            });
            
            this.mqttClient.on('error', (error) => {
                console.error('Error MQTT:', error);
                reject(error);
            });
            
            this.mqttClient.on('message', (topic, message) => {
                this.handleMQTTMessage(topic, message);
            });
        });
    }
    
    // Manejar mensajes MQTT
    handleMQTTMessage(topic, message) {
        try {
            const data = JSON.parse(message.toString());
            const messageData = {
                topic,
                data,
                timestamp: new Date().toISOString()
            };
            
            console.log(`Mensaje MQTT recibido en ${topic}:`, data);
            
            // Enviar a todos los clientes WebSocket conectados
            this.broadcastToClients(messageData);
            
        } catch (error) {
            console.error('Error procesando mensaje MQTT:', error);
        }
    }
    
    // Iniciar servidor WebSocket para clientes web
    startWebSocketServer() {
        this.wsServer = new WebSocket.Server({ port: this.wsPort });
        
        this.wsServer.on('connection', (ws) => {
            console.log('Cliente WebSocket conectado');
            this.connectedClients.add(ws);
            
            // Enviar mensaje de bienvenida
            ws.send(JSON.stringify({
                type: 'welcome',
                message: 'Conectado a SMARTLABS WebSocket Server',
                timestamp: new Date().toISOString()
            }));
            
            ws.on('message', (message) => {
                this.handleWebSocketMessage(ws, message);
            });
            
            ws.on('close', () => {
                console.log('Cliente WebSocket desconectado');
                this.connectedClients.delete(ws);
            });
            
            ws.on('error', (error) => {
                console.error('Error WebSocket:', error);
                this.connectedClients.delete(ws);
            });
        });
        
        console.log(`Servidor WebSocket iniciado en puerto ${this.wsPort}`);
    }
    
    // Manejar mensajes de clientes WebSocket
    handleWebSocketMessage(ws, message) {
        try {
            const data = JSON.parse(message);
            
            switch (data.type) {
                case 'publish':
                    this.publishToMQTT(data.topic, data.payload);
                    break;
                case 'subscribe':
                    this.mqttClient.subscribe(data.topic, { qos: 1 });
                    break;
                case 'ping':
                    ws.send(JSON.stringify({ type: 'pong', timestamp: new Date().toISOString() }));
                    break;
                default:
                    console.log('Mensaje WebSocket no reconocido:', data);
            }
        } catch (error) {
            console.error('Error procesando mensaje WebSocket:', error);
        }
    }
    
    // Publicar mensaje a MQTT
    publishToMQTT(topic, payload) {
        if (this.mqttClient && this.mqttClient.connected) {
            this.mqttClient.publish(topic, JSON.stringify(payload), { qos: 1 });
            console.log(`Mensaje publicado a ${topic}:`, payload);
        }
    }
    
    // Enviar mensaje a todos los clientes WebSocket
    broadcastToClients(message) {
        const messageStr = JSON.stringify(message);
        
        this.connectedClients.forEach((client) => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(messageStr);
            }
        });
    }
    
    // Iniciar el cliente
    async start() {
        try {
            await this.connectMQTT();
            this.startWebSocketServer();
            console.log('SmartLabs Web Client iniciado correctamente');
        } catch (error) {
            console.error('Error iniciando cliente:', error);
        }
    }
    
    // Detener el cliente
    stop() {
        if (this.mqttClient) {
            this.mqttClient.end();
        }
        
        if (this.wsServer) {
            this.wsServer.close();
        }
        
        console.log('SmartLabs Web Client detenido');
    }
}

// Ejemplo de uso
if (require.main === module) {
    const client = new SmartLabsWebClient({
        mqttBroker: 'ws://localhost:8073',
        wsPort: 3001,
        username: 'emqx',
        password: 'emqxpass'
    });
    
    client.start();
    
    // Manejar cierre graceful
    process.on('SIGINT', () => {
        console.log('\nDeteniendo cliente...');
        client.stop();
        process.exit(0);
    });
}

module.exports = SmartLabsWebClient;
```

## Integración con Aplicaciones Web

### 1. Cliente JavaScript para Navegador

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMARTLABS Dashboard</title>
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 10px 0; }
        .status { padding: 5px 10px; border-radius: 4px; color: white; }
        .online { background-color: #28a745; }
        .offline { background-color: #dc3545; }
        .data-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .log { background-color: #f8f9fa; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>SMARTLABS Dashboard</h1>
        
        <div class="card">
            <h2>Estado de Conexión</h2>
            <span id="connectionStatus" class="status offline">Desconectado</span>
            <button id="connectBtn" onclick="toggleConnection()">Conectar</button>
        </div>
        
        <div class="data-grid">
            <div class="card">
                <h3>Datos de Sensores</h3>
                <div id="sensorData">
                    <p>Temperatura: <span id="temperature">--</span>°C</p>
                    <p>Humedad: <span id="humidity">--</span>%</p>
                    <p>Voltaje: <span id="voltage">--</span>V</p>
                    <p>Última actualización: <span id="lastUpdate">--</span></p>
                </div>
            </div>
            
            <div class="card">
                <h3>Control de Acceso</h3>
                <div id="accessControl">
                    <p>Último acceso: <span id="lastAccess">--</span></p>
                    <p>Estado: <span id="accessStatus">--</span></p>
                    <button onclick="simulateAccess()">Simular Acceso</button>
                </div>
            </div>
            
            <div class="card">
                <h3>Dispositivos Activos</h3>
                <div id="deviceList">
                    <!-- Lista de dispositivos se llenará dinámicamente -->
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>Log de Eventos</h3>
            <div id="eventLog" class="log">
                <!-- Los eventos aparecerán aquí -->
            </div>
            <button onclick="clearLog()">Limpiar Log</button>
        </div>
    </div>

    <script>
        class SmartLabsDashboard {
            constructor() {
                this.client = null;
                this.isConnected = false;
                this.devices = new Map();
                this.eventLog = [];
            }
            
            connect() {
                const options = {
                    username: 'emqx',
                    password: 'emqxpass',
                    clientId: `dashboard_${Math.random().toString(16).substr(2, 8)}`
                };
                
                this.client = mqtt.connect('ws://localhost:8073', options);
                
                this.client.on('connect', () => {
                    this.isConnected = true;
                    this.updateConnectionStatus();
                    this.addLog('Conectado al broker MQTT', 'success');
                    
                    // Suscribirse a topics
                    this.client.subscribe('smartlabs/+/data', { qos: 1 });
                    this.client.subscribe('smartlabs/+/access', { qos: 1 });
                    this.client.subscribe('smartlabs/+/status', { qos: 1 });
                });
                
                this.client.on('message', (topic, message) => {
                    this.handleMessage(topic, message);
                });
                
                this.client.on('error', (error) => {
                    this.addLog(`Error MQTT: ${error.message}`, 'error');
                });
                
                this.client.on('close', () => {
                    this.isConnected = false;
                    this.updateConnectionStatus();
                    this.addLog('Desconectado del broker MQTT', 'warning');
                });
            }
            
            disconnect() {
                if (this.client) {
                    this.client.end();
                    this.isConnected = false;
                    this.updateConnectionStatus();
                }
            }
            
            handleMessage(topic, message) {
                try {
                    const data = JSON.parse(message.toString());
                    const deviceId = data.device_id || 'UNKNOWN';
                    
                    // Actualizar información del dispositivo
                    this.devices.set(deviceId, {
                        ...this.devices.get(deviceId),
                        lastSeen: new Date(),
                        ...data
                    });
                    
                    if (topic.includes('/data')) {
                        this.updateSensorData(data);
                        this.addLog(`Datos recibidos de ${deviceId}`, 'info');
                    } else if (topic.includes('/access')) {
                        this.updateAccessControl(data);
                        this.addLog(`Evento de acceso: ${deviceId} - ${data.access_granted ? 'Permitido' : 'Denegado'}`, data.access_granted ? 'success' : 'warning');
                    } else if (topic.includes('/status')) {
                        this.addLog(`Estado actualizado: ${deviceId} - ${data.status}`, 'info');
                    }
                    
                    this.updateDeviceList();
                    
                } catch (error) {
                    this.addLog(`Error procesando mensaje: ${error.message}`, 'error');
                }
            }
            
            updateSensorData(data) {
                document.getElementById('temperature').textContent = data.temperature || '--';
                document.getElementById('humidity').textContent = data.humidity || '--';
                document.getElementById('voltage').textContent = data.voltage || '--';
                document.getElementById('lastUpdate').textContent = new Date(data.timestamp).toLocaleString();
            }
            
            updateAccessControl(data) {
                document.getElementById('lastAccess').textContent = new Date(data.timestamp).toLocaleString();
                document.getElementById('accessStatus').textContent = data.access_granted ? 'Permitido' : 'Denegado';
            }
            
            updateDeviceList() {
                const deviceListEl = document.getElementById('deviceList');
                deviceListEl.innerHTML = '';
                
                this.devices.forEach((device, deviceId) => {
                    const deviceEl = document.createElement('div');
                    const isOnline = (new Date() - device.lastSeen) < 60000; // 1 minuto
                    
                    deviceEl.innerHTML = `
                        <p><strong>${deviceId}</strong> 
                        <span class="status ${isOnline ? 'online' : 'offline'}">
                            ${isOnline ? 'Online' : 'Offline'}
                        </span></p>
                        <small>Última actividad: ${device.lastSeen.toLocaleString()}</small>
                    `;
                    
                    deviceListEl.appendChild(deviceEl);
                });
            }
            
            updateConnectionStatus() {
                const statusEl = document.getElementById('connectionStatus');
                const btnEl = document.getElementById('connectBtn');
                
                if (this.isConnected) {
                    statusEl.textContent = 'Conectado';
                    statusEl.className = 'status online';
                    btnEl.textContent = 'Desconectar';
                } else {
                    statusEl.textContent = 'Desconectado';
                    statusEl.className = 'status offline';
                    btnEl.textContent = 'Conectar';
                }
            }
            
            addLog(message, type = 'info') {
                const timestamp = new Date().toLocaleString();
                this.eventLog.push({ timestamp, message, type });
                
                // Mantener solo los últimos 100 eventos
                if (this.eventLog.length > 100) {
                    this.eventLog.shift();
                }
                
                this.updateLogDisplay();
            }
            
            updateLogDisplay() {
                const logEl = document.getElementById('eventLog');
                logEl.innerHTML = this.eventLog.map(event => 
                    `<div style="color: ${this.getLogColor(event.type)}">
                        [${event.timestamp}] ${event.message}
                    </div>`
                ).join('');
                
                // Scroll al final
                logEl.scrollTop = logEl.scrollHeight;
            }
            
            getLogColor(type) {
                switch (type) {
                    case 'success': return '#28a745';
                    case 'warning': return '#ffc107';
                    case 'error': return '#dc3545';
                    default: return '#6c757d';
                }
            }
            
            simulateAccess() {
                if (this.client && this.isConnected) {
                    const accessData = {
                        device_id: 'SMART00001',
                        timestamp: new Date().toISOString(),
                        user_id: `USER${Math.floor(Math.random() * 999) + 1:03d}`,
                        access_granted: Math.random() > 0.3,
                        event_type: 'access_control'
                    };
                    
                    this.client.publish('smartlabs/SMART00001/access', JSON.stringify(accessData), { qos: 1 });
                    this.addLog('Evento de acceso simulado enviado', 'info');
                }
            }
            
            clearLog() {
                this.eventLog = [];
                this.updateLogDisplay();
            }
        }
        
        // Instancia global del dashboard
        const dashboard = new SmartLabsDashboard();
        
        function toggleConnection() {
            if (dashboard.isConnected) {
                dashboard.disconnect();
            } else {
                dashboard.connect();
            }
        }
        
        function simulateAccess() {
            dashboard.simulateAccess();
        }
        
        function clearLog() {
            dashboard.clearLog();
        }
        
        // Auto-conectar al cargar la página
        window.addEventListener('load', () => {
            dashboard.connect();
        });
    </script>
</body>
</html>
```

## Integración con Aplicaciones Móviles

### 1. Cliente Flutter/Dart

```dart
// smartlabs_mqtt_client.dart - Cliente MQTT para Flutter

import 'dart:convert';
import 'dart:io';
import 'package:mqtt_client/mqtt_client.dart';
import 'package:mqtt_client/mqtt_server_client.dart';

class SmartLabsMqttClient {
  late MqttServerClient client;
  String broker = 'localhost';
  int port = 1883;
  String username = 'emqx';
  String password = 'emqxpass';
  String clientId = 'flutter_client';
  
  // Callbacks para eventos
  Function(String topic, Map<String, dynamic> data)? onDataReceived;
  Function(bool connected)? onConnectionChanged;
  Function(String message)? onError;
  
  SmartLabsMqttClient({
    this.broker = 'localhost',
    this.port = 1883,
    this.username = 'emqx',
    this.password = 'emqxpass',
    this.clientId = 'flutter_client',
  });
  
  Future<bool> connect() async {
    try {
      client = MqttServerClient.withPort(broker, clientId, port);
      client.logging(on: true);
      client.keepAlivePeriod = 60;
      client.onDisconnected = _onDisconnected;
      client.onConnected = _onConnected;
      client.onSubscribed = _onSubscribed;
      
      final connMessage = MqttConnectMessage()
          .withClientIdentifier(clientId)
          .authenticateAs(username, password)
          .startClean()
          .withWillQos(MqttQos.atLeastOnce);
      
      client.connectionMessage = connMessage;
      
      await client.connect();
      
      if (client.connectionStatus!.state == MqttConnectionState.connected) {
        print('Cliente MQTT conectado');
        _setupMessageListener();
        _subscribeToTopics();
        return true;
      } else {
        print('Error de conexión: ${client.connectionStatus}');
        return false;
      }
    } catch (e) {
      print('Error conectando: $e');
      onError?.call('Error de conexión: $e');
      return false;
    }
  }
  
  void _onConnected() {
    print('Cliente MQTT conectado');
    onConnectionChanged?.call(true);
  }
  
  void _onDisconnected() {
    print('Cliente MQTT desconectado');
    onConnectionChanged?.call(false);
  }
  
  void _onSubscribed(String topic) {
    print('Suscrito a: $topic');
  }
  
  void _setupMessageListener() {
    client.updates!.listen((List<MqttReceivedMessage<MqttMessage>> c) {
      final MqttPublishMessage message = c[0].payload as MqttPublishMessage;
      final String topic = c[0].topic;
      final String payload = MqttPublishPayload.bytesToStringAsString(message.payload.message);
      
      try {
        final Map<String, dynamic> data = json.decode(payload);
        onDataReceived?.call(topic, data);
      } catch (e) {
        print('Error decodificando mensaje: $e');
      }
    });
  }
  
  void _subscribeToTopics() {
    client.subscribe('smartlabs/+/data', MqttQos.atLeastOnce);
    client.subscribe('smartlabs/+/access', MqttQos.atLeastOnce);
    client.subscribe('smartlabs/+/status', MqttQos.atLeastOnce);
  }
  
  void publishSensorData(String deviceId, Map<String, dynamic> sensorData) {
    final String topic = 'smartlabs/$deviceId/data';
    final String payload = json.encode({
      'device_id': deviceId,
      'timestamp': DateTime.now().toIso8601String(),
      ...sensorData,
    });
    
    _publishMessage(topic, payload);
  }
  
  void publishAccessEvent(String deviceId, String userId, bool accessGranted) {
    final String topic = 'smartlabs/$deviceId/access';
    final String payload = json.encode({
      'device_id': deviceId,
      'timestamp': DateTime.now().toIso8601String(),
      'user_id': userId,
      'access_granted': accessGranted,
      'event_type': 'access_control',
    });
    
    _publishMessage(topic, payload);
  }
  
  void _publishMessage(String topic, String payload) {
    if (client.connectionStatus!.state == MqttConnectionState.connected) {
      final builder = MqttClientPayloadBuilder();
      builder.addString(payload);
      client.publishMessage(topic, MqttQos.atLeastOnce, builder.payload!);
      print('Mensaje publicado en $topic');
    } else {
      print('No conectado - no se puede publicar mensaje');
    }
  }
  
  void disconnect() {
    client.disconnect();
  }
  
  bool get isConnected => 
      client.connectionStatus!.state == MqttConnectionState.connected;
}

// Ejemplo de uso en una aplicación Flutter
class SmartLabsApp extends StatefulWidget {
  @override
  _SmartLabsAppState createState() => _SmartLabsAppState();
}

class _SmartLabsAppState extends State<SmartLabsApp> {
  late SmartLabsMqttClient mqttClient;
  bool isConnected = false;
  List<Map<String, dynamic>> messages = [];
  Map<String, dynamic> latestSensorData = {};
  
  @override
  void initState() {
    super.initState();
    _initializeMqttClient();
  }
  
  void _initializeMqttClient() {
    mqttClient = SmartLabsMqttClient(
      broker: '192.168.1.100', // IP del servidor
      port: 1883,
    );
    
    mqttClient.onConnectionChanged = (connected) {
      setState(() {
        isConnected = connected;
      });
    };
    
    mqttClient.onDataReceived = (topic, data) {
      setState(() {
        messages.insert(0, {
          'topic': topic,
          'data': data,
          'timestamp': DateTime.now(),
        });
        
        if (topic.contains('/data')) {
          latestSensorData = data;
        }
        
        // Mantener solo los últimos 50 mensajes
        if (messages.length > 50) {
          messages.removeLast();
        }
      });
    };
    
    mqttClient.onError = (error) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error MQTT: $error')),
      );
    };
  }
  
  Future<void> _connectMqtt() async {
    final success = await mqttClient.connect();
    if (!success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error conectando al broker MQTT')),
      );
    }
  }
  
  void _simulateAccess() {
    mqttClient.publishAccessEvent(
      'SMART00001',
      'USER${Random().nextInt(999).toString().padLeft(3, '0')}',
      Random().nextBool(),
    );
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('SMARTLABS Mobile'),
        backgroundColor: isConnected ? Colors.green : Colors.red,
      ),
      body: Column(
        children: [
          // Estado de conexión
          Container(
            padding: EdgeInsets.all(16),
            child: Row(
              children: [
                Icon(
                  isConnected ? Icons.wifi : Icons.wifi_off,
                  color: isConnected ? Colors.green : Colors.red,
                ),
                SizedBox(width: 8),
                Text(
                  isConnected ? 'Conectado' : 'Desconectado',
                  style: TextStyle(
                    color: isConnected ? Colors.green : Colors.red,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Spacer(),
                ElevatedButton(
                  onPressed: isConnected ? null : _connectMqtt,
                  child: Text('Conectar'),
                ),
              ],
            ),
          ),
          
          // Datos de sensores
          if (latestSensorData.isNotEmpty)
            Card(
              margin: EdgeInsets.all(16),
              child: Padding(
                padding: EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Últimos Datos de Sensores', 
                         style: Theme.of(context).textTheme.headline6),
                    SizedBox(height: 8),
                    Text('Temperatura: ${latestSensorData['temperature']}°C'),
                    Text('Humedad: ${latestSensorData['humidity']}%'),
                    Text('Voltaje: ${latestSensorData['voltage']}V'),
                    Text('Actualizado: ${DateTime.parse(latestSensorData['timestamp'] ?? '').toLocal()}'),
                  ],
                ),
              ),
            ),
          
          // Botón de simulación
          Padding(
            padding: EdgeInsets.all(16),
            child: ElevatedButton(
              onPressed: isConnected ? _simulateAccess : null,
              child: Text('Simular Acceso'),
            ),
          ),
          
          // Lista de mensajes
          Expanded(
            child: ListView.builder(
              itemCount: messages.length,
              itemBuilder: (context, index) {
                final message = messages[index];
                return ListTile(
                  title: Text(message['topic']),
                  subtitle: Text(json.encode(message['data'])),
                  trailing: Text(
                    DateFormat('HH:mm:ss').format(message['timestamp']),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
  
  @override
  void dispose() {
    mqttClient.disconnect();
    super.dispose();
  }
}
```

## Dispositivos IoT

### 1. Código para ESP32 (Arduino)

```cpp
// smartlabs_esp32.ino - Código para dispositivo ESP32

#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <DHT.h>

// Configuración WiFi
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// Configuración MQTT
const char* mqtt_server = "192.168.1.100";  // IP del servidor SMARTLABS
const int mqtt_port = 1883;
const char* mqtt_user = "emqx";
const char* mqtt_password = "emqxpass";
const char* device_id = "SMART00001";

// Configuración de sensores
#define DHT_PIN 4
#define DHT_TYPE DHT22
#define LED_PIN 2
#define BUTTON_PIN 0
#define VOLTAGE_PIN A0

DHT dht(DHT_PIN, DHT_TYPE);
WiFiClient espClient;
PubSubClient client(espClient);

// Variables globales
unsigned long lastSensorRead = 0;
unsigned long lastHeartbeat = 0;
const unsigned long SENSOR_INTERVAL = 5000;  // 5 segundos
const unsigned long HEARTBEAT_INTERVAL = 30000;  // 30 segundos
bool lastButtonState = HIGH;
bool deviceActive = true;

void setup() {
  Serial.begin(115200);
  
  // Configurar pines
  pinMode(LED_PIN, OUTPUT);
  pinMode(BUTTON_PIN, INPUT_PULLUP);
  
  // Inicializar sensores
  dht.begin();
  
  // Conectar WiFi
  setupWiFi();
  
  // Configurar MQTT
  client.setServer(mqtt_server, mqtt_port);
  client.setCallback(mqttCallback);
  
  Serial.println("Dispositivo SMARTLABS iniciado");
}

void loop() {
  // Mantener conexión MQTT
  if (!client.connected()) {
    reconnectMQTT();
  }
  client.loop();
  
  // Leer sensores periódicamente
  if (millis() - lastSensorRead > SENSOR_INTERVAL) {
    readAndPublishSensors();
    lastSensorRead = millis();
  }
  
  // Enviar heartbeat
  if (millis() - lastHeartbeat > HEARTBEAT_INTERVAL) {
    publishHeartbeat();
    lastHeartbeat = millis();
  }
  
  // Verificar botón de acceso
  checkAccessButton();
  
  delay(100);
}

void setupWiFi() {
  delay(10);
  Serial.println();
  Serial.print("Conectando a ");
  Serial.println(ssid);
  
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  Serial.println("");
  Serial.println("WiFi conectado");
  Serial.println("Dirección IP: ");
  Serial.println(WiFi.localIP());
}

void reconnectMQTT() {
  while (!client.connected()) {
    Serial.print("Intentando conexión MQTT...");
    
    String clientId = String(device_id) + "_" + String(random(0xffff), HEX);
    
    if (client.connect(clientId.c_str(), mqtt_user, mqtt_password)) {
      Serial.println(" conectado");
      
      // Suscribirse a topics de control
      String controlTopic = "smartlabs/" + String(device_id) + "/control";
      client.subscribe(controlTopic.c_str());
      
      // Publicar mensaje de conexión
      publishDeviceStatus("online");
      
    } else {
      Serial.print(" falló, rc=");
      Serial.print(client.state());
      Serial.println(" reintentando en 5 segundos");
      delay(5000);
    }
  }
}

void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String message;
  for (int i = 0; i < length; i++) {
    message += (char)payload[i];
  }
  
  Serial.print("Mensaje recibido en ");
  Serial.print(topic);
  Serial.print(": ");
  Serial.println(message);
  
  // Procesar comandos de control
  DynamicJsonDocument doc(1024);
  deserializeJson(doc, message);
  
  if (doc["command"] == "led_on") {
    digitalWrite(LED_PIN, HIGH);
    publishDeviceStatus("led_on");
  } else if (doc["command"] == "led_off") {
    digitalWrite(LED_PIN, LOW);
    publishDeviceStatus("led_off");
  } else if (doc["command"] == "restart") {
    ESP.restart();
  } else if (doc["command"] == "activate") {
    deviceActive = true;
    publishDeviceStatus("activated");
  } else if (doc["command"] == "deactivate") {
    deviceActive = false;
    publishDeviceStatus("deactivated");
  }
}

void readAndPublishSensors() {
  if (!deviceActive) return;
  
  // Leer sensores
  float temperature = dht.readTemperature();
  float humidity = dht.readHumidity();
  float voltage = analogRead(VOLTAGE_PIN) * (3.3 / 4095.0);
  
  // Verificar si las lecturas son válidas
  if (isnan(temperature) || isnan(humidity)) {
    Serial.println("Error leyendo sensor DHT!");
    return;
  }
  
  // Crear JSON con datos
  DynamicJsonDocument doc(1024);
  doc["device_id"] = device_id;
  doc["timestamp"] = getTimestamp();
  doc["temperature"] = round(temperature * 100.0) / 100.0;
  doc["humidity"] = round(humidity * 100.0) / 100.0;
  doc["voltage"] = round(voltage * 100.0) / 100.0;
  doc["status"] = "active";
  doc["wifi_rssi"] = WiFi.RSSI();
  doc["free_heap"] = ESP.getFreeHeap();
  
  String payload;
  serializeJson(doc, payload);
  
  // Publicar datos
  String topic = "smartlabs/" + String(device_id) + "/data";
  client.publish(topic.c_str(), payload.c_str(), true);
  
  Serial.println("Datos de sensores publicados: " + payload);
}

void checkAccessButton() {
  bool currentButtonState = digitalRead(BUTTON_PIN);
  
  // Detectar presión del botón (flanco descendente)
  if (lastButtonState == HIGH && currentButtonState == LOW) {
    delay(50); // Debounce
    
    if (digitalRead(BUTTON_PIN) == LOW) {
      publishAccessEvent();
      
      // Feedback visual
      digitalWrite(LED_PIN, HIGH);
      delay(200);
      digitalWrite(LED_PIN, LOW);
    }
  }
  
  lastButtonState = currentButtonState;
}

void publishAccessEvent() {
  if (!deviceActive) return;
  
  // Simular validación de acceso (en un caso real, aquí iría la lógica de RFID/NFC)
  bool accessGranted = random(100) > 20; // 80% de éxito
  String userId = "USER" + String(random(1, 999));
  
  DynamicJsonDocument doc(1024);
  doc["device_id"] = device_id;
  doc["timestamp"] = getTimestamp();
  doc["user_id"] = userId;
  doc["access_granted"] = accessGranted;
  doc["event_type"] = "access_control";
  doc["method"] = "button";
  
  String payload;
  serializeJson(doc, payload);
  
  String topic = "smartlabs/" + String(device_id) + "/access";
  client.publish(topic.c_str(), payload.c_str());
  
  Serial.println("Evento de acceso publicado: " + payload);
}

void publishDeviceStatus(String status) {
  DynamicJsonDocument doc(1024);
  doc["device_id"] = device_id;
  doc["timestamp"] = getTimestamp();
  doc["status"] = status;
  doc["uptime"] = millis();
  doc["wifi_rssi"] = WiFi.RSSI();
  doc["free_heap"] = ESP.getFreeHeap();
  
  String payload;
  serializeJson(doc, payload);
  
  String topic = "smartlabs/" + String(device_id) + "/status";
  client.publish(topic.c_str(), payload.c_str(), true);
  
  Serial.println("Estado del dispositivo publicado: " + payload);
}

void publishHeartbeat() {
  publishDeviceStatus("heartbeat");
}

String getTimestamp() {
  // En un caso real, aquí se obtendría el timestamp del servidor NTP
  return String(millis());
}
```

## Consultas de Base de Datos

### 1. Consultas SQL Útiles

```sql
-- Consultas de ejemplo para SMARTLABS

-- 1. Obtener todos los dispositivos activos
SELECT 
    d.devices_id,
    d.devices_alias,
    d.devices_serie,
    u.users_email,
    d.devices_date
FROM devices d
JOIN users u ON d.devices_user_id = u.users_id
ORDER BY d.devices_date DESC;

-- 2. Obtener el tráfico de acceso de las últimas 24 horas
SELECT 
    td.traffic_date,
    td.hab_name,
    td.hab_registration,
    td.traffic_device,
    td.traffic_state,
    CASE 
        WHEN td.traffic_state = 1 THEN 'Acceso Permitido'
        ELSE 'Acceso Denegado'
    END as estado_acceso
FROM traffic_devices td
WHERE td.traffic_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY td.traffic_date DESC;

-- 3. Estadísticas de acceso por dispositivo
SELECT 
    traffic_device,
    COUNT(*) as total_intentos,
    SUM(CASE WHEN traffic_state = 1 THEN 1 ELSE 0 END) as accesos_exitosos,
    SUM(CASE WHEN traffic_state = 0 THEN 1 ELSE 0 END) as accesos_denegados,
    ROUND((SUM(CASE WHEN traffic_state = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as porcentaje_exito
FROM traffic
WHERE traffic_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY traffic_device
ORDER BY total_intentos DESC;

-- 4. Usuarios más activos
SELECT 
    h.hab_name,
    h.hab_registration,
    h.hab_email,
    COUNT(t.traffic_id) as total_accesos,
    MAX(t.traffic_date) as ultimo_acceso
FROM habintants h
LEFT JOIN traffic t ON h.hab_id = t.traffic_hab_id
WHERE t.traffic_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY h.hab_id
ORDER BY total_accesos DESC
LIMIT 10;

-- 5. Datos de sensores promedio por día
SELECT 
    DATE(data_date) as fecha,
    AVG(data_temp1) as temperatura_promedio,
    AVG(data_temp2) as humedad_promedio,
    AVG(data_volts) as voltaje_promedio,
    COUNT(*) as total_lecturas
FROM data
WHERE data_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(data_date)
ORDER BY fecha DESC;

-- 6. Préstamos activos
SELECT 
    l.loans_date,
    h.hab_name,
    h.hab_registration,
    e.equipments_name,
    e.equipments_brand,
    CASE 
        WHEN l.loans_state = 1 THEN 'Activo'
        ELSE 'Devuelto'
    END as estado_prestamo
FROM loans l
JOIN habintants h ON l.loans_hab_rfid = (SELECT cards_number FROM cards WHERE cards_id = h.hab_card_id)
JOIN equipments e ON l.loans_equip_rfid = e.equipments_rfid
WHERE l.loans_state = 1
ORDER BY l.loans_date DESC;

-- 7. Reporte de actividad por hora
SELECT 
    HOUR(traffic_date) as hora,
    COUNT(*) as total_accesos,
    SUM(CASE WHEN traffic_state = 1 THEN 1 ELSE 0 END) as accesos_exitosos
FROM traffic
WHERE DATE(traffic_date) = CURDATE()
GROUP BY HOUR(traffic_date)
ORDER BY hora;

-- 8. Dispositivos sin actividad reciente
SELECT 
    d.devices_alias,
    d.devices_serie,
    MAX(t.traffic_date) as ultima_actividad,
    DATEDIFF(NOW(), MAX(t.traffic_date)) as dias_inactivo
FROM devices d
LEFT JOIN traffic t ON d.devices_serie = t.traffic_device
GROUP BY d.devices_id
HAVING ultima_actividad IS NULL OR dias_inactivo > 7
ORDER BY dias_inactivo DESC;

-- 9. Análisis de patrones de acceso
SELECT 
    DAYNAME(traffic_date) as dia_semana,
    HOUR(traffic_date) as hora,
    COUNT(*) as total_accesos,
    AVG(CASE WHEN traffic_state = 1 THEN 1.0 ELSE 0.0 END) as tasa_exito
FROM traffic
WHERE traffic_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DAYNAME(traffic_date), HOUR(traffic_date)
ORDER BY dia_semana, hora;

-- 10. Resumen de equipos más prestados
SELECT 
    e.equipments_name,
    e.equipments_brand,
    COUNT(l.loans_id) as total_prestamos,
    SUM(CASE WHEN l.loans_state = 1 THEN 1 ELSE 0 END) as prestamos_activos
FROM equipments e
LEFT JOIN loans l ON e.equipments_rfid = l.loans_equip_rfid
GROUP BY e.equipments_id
ORDER BY total_prestamos DESC;
```

### 2. Procedimientos Almacenados

```sql
-- Procedimiento para registrar acceso
DELIMITER //
CREATE PROCEDURE RegisterAccess(
    IN p_hab_id INT,
    IN p_device VARCHAR(50),
    IN p_state BOOLEAN
)
BEGIN
    INSERT INTO traffic (traffic_hab_id, traffic_device, traffic_state, traffic_date)
    VALUES (p_hab_id, p_device, p_state, NOW());
    
    SELECT LAST_INSERT_ID() as traffic_id;
END //
DELIMITER ;

-- Procedimiento para obtener estadísticas del dashboard
DELIMITER //
CREATE PROCEDURE GetDashboardStats()
BEGIN
    -- Estadísticas generales
    SELECT 
        (SELECT COUNT(*) FROM habintants) as total_habitantes,
        (SELECT COUNT(*) FROM devices) as total_dispositivos,
        (SELECT COUNT(*) FROM loans WHERE loans_state = 1) as prestamos_activos,
        (SELECT COUNT(*) FROM traffic WHERE DATE(traffic_date) = CURDATE()) as accesos_hoy;
    
    -- Últimos accesos
    SELECT 
        td.traffic_date,
        td.hab_name,
        td.traffic_device,
        td.traffic_state
    FROM traffic_devices td
    ORDER BY td.traffic_date DESC
    LIMIT 10;
    
    -- Datos de sensores recientes
    SELECT 
        data_date,
        data_temp1,
        data_temp2,
        data_volts
    FROM data
    ORDER BY data_date DESC
    LIMIT 20;
END //
DELIMITER ;
```

## Scripts de Automatización

### 1. Script de Backup Automático

```bash
#!/bin/bash
# backup_smartlabs.sh - Script de backup automático

# Configuración
BACKUP_DIR="/opt/smartlabs/backups"
DATE=$(date +"%Y%m%d_%H%M%S")
DB_NAME="emqx"
DB_USER="emqxuser"
DB_PASS="emqxpass"
DB_HOST="localhost"
DB_PORT="4000"
RETENTION_DAYS=30

# Crear directorio de backup si no existe
mkdir -p $BACKUP_DIR

echo "[$(date)] Iniciando backup de SMARTLABS..."

# Backup de base de datos
echo "[$(date)] Realizando backup de base de datos..."
mysqldump -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS $DB_NAME > "$BACKUP_DIR/smartlabs_db_$DATE.sql"

if [ $? -eq 0 ]; then
    echo "[$(date)] Backup de base de datos completado: smartlabs_db_$DATE.sql"
    
    # Comprimir backup
    gzip "$BACKUP_DIR/smartlabs_db_$DATE.sql"
    echo "[$(date)] Backup comprimido: smartlabs_db_$DATE.sql.gz"
else
    echo "[$(date)] ERROR: Falló el backup de base de datos"
    exit 1
fi

# Backup de configuración Docker
echo "[$(date)] Realizando backup de configuración Docker..."
cp docker-compose.yaml "$BACKUP_DIR/docker-compose_$DATE.yaml"
cp .env "$BACKUP_DIR/env_$DATE.txt"
cp -r db/ "$BACKUP_DIR/db_$DATE/"

# Backup de volúmenes Docker
echo "[$(date)] Realizando backup de volúmenes Docker..."
docker run --rm -v docker_smartlabs-main_mariadb:/data -v $BACKUP_DIR:/backup alpine tar czf /backup/mariadb_volume_$DATE.tar.gz -C /data .
docker run --rm -v docker_smartlabs-main_vol-emqx-data:/data -v $BACKUP_DIR:/backup alpine tar czf /backup/emqx_data_$DATE.tar.gz -C /data .

# Limpiar backups antiguos
echo "[$(date)] Limpiando backups antiguos (más de $RETENTION_DAYS días)..."
find $BACKUP_DIR -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "*.yaml" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "*.txt" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -type d -name "db_*" -mtime +$RETENTION_DAYS -exec rm -rf {} +

echo "[$(date)] Backup de SMARTLABS completado exitosamente"

# Enviar notificación (opcional)
# curl -X POST "https://api.telegram.org/bot<TOKEN>/sendMessage" \
#      -d "chat_id=<CHAT_ID>&text=Backup SMARTLABS completado: $DATE"
```

### 2. Script de Monitoreo

```bash
#!/bin/bash
# monitor_smartlabs.sh - Script de monitoreo del sistema

# Configuración
LOG_FILE="/var/log/smartlabs_monitor.log"
ALERT_EMAIL="admin@smartlabs.com"
MQTT_HOST="localhost"
MQTT_PORT="1883"
DB_HOST="localhost"
DB_PORT="4000"
WEB_DASHBOARD="http://localhost:18083"

# Función para logging
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

# Función para enviar alertas
send_alert() {
    local subject="$1"
    local message="$2"
    
    log_message "ALERT: $subject - $message"
    
    # Enviar email (requiere configurar sendmail o similar)
    # echo "$message" | mail -s "SMARTLABS Alert: $subject" $ALERT_EMAIL
    
    # Enviar a Telegram (opcional)
    # curl -s -X POST "https://api.telegram.org/bot<TOKEN>/sendMessage" \
    #      -d "chat_id=<CHAT_ID>&text=🚨 SMARTLABS Alert: $subject%0A$message"
}

# Verificar servicios Docker
check_docker_services() {
    log_message "Verificando servicios Docker..."
    
    local services=("mariadb" "emqx" "phpmyadmin")
    
    for service in "${services[@]}"; do
        if docker-compose ps $service | grep -q "Up"; then
            log_message "✓ Servicio $service: OK"
        else
            send_alert "Servicio Caído" "El servicio $service no está funcionando"
        fi
    done
}

# Verificar conectividad MQTT
check_mqtt_connectivity() {
    log_message "Verificando conectividad MQTT..."
    
    if timeout 5 bash -c "</dev/tcp/$MQTT_HOST/$MQTT_PORT"; then
        log_message "✓ MQTT Broker: OK"
    else
        send_alert "MQTT Broker Caído" "No se puede conectar al broker MQTT en $MQTT_HOST:$MQTT_PORT"
    fi
}

# Verificar base de datos
check_database() {
    log_message "Verificando base de datos..."
    
    if mysql -h $DB_HOST -P $DB_PORT -u emqxuser -pemqxpass -e "SELECT 1" emqx >/dev/null 2>&1; then
        log_message "✓ Base de datos: OK"
        
        # Verificar número de conexiones activas
        local connections=$(mysql -h $DB_HOST -P $DB_PORT -u emqxuser -pemqxpass -e "SHOW STATUS LIKE 'Threads_connected'" emqx | tail -1 | awk '{print $2}')
        log_message "Conexiones activas a BD: $connections"
        
        if [ $connections -gt 100 ]; then
            send_alert "Alto Número de Conexiones" "La base de datos tiene $connections conexiones activas"
        fi
    else
        send_alert "Base de Datos Caída" "No se puede conectar a la base de datos"
    fi
}

# Verificar dashboard web
check_web_dashboard() {
    log_message "Verificando dashboard web..."
    
    if curl -s --max-time 10 $WEB_DASHBOARD >/dev/null; then
        log_message "✓ Dashboard web: OK"
    else
        send_alert "Dashboard Web Caído" "No se puede acceder al dashboard en $WEB_DASHBOARD"
    fi
}

# Verificar uso de disco
check_disk_usage() {
    log_message "Verificando uso de disco..."
    
    local disk_usage=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
    log_message "Uso de disco: $disk_usage%"
    
    if [ $disk_usage -gt 85 ]; then
        send_alert "Disco Lleno" "El uso de disco está al $disk_usage%"
    fi
}

# Verificar memoria
check_memory_usage() {
    log_message "Verificando uso de memoria..."
    
    local mem_usage=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
    log_message "Uso de memoria: $mem_usage%"
    
    if [ $mem_usage -gt 90 ]; then
        send_alert "Memoria Alta" "El uso de memoria está al $mem_usage%"
    fi
}

# Verificar logs de errores
check_error_logs() {
    log_message "Verificando logs de errores..."
    
    # Verificar logs de Docker
    local error_count=$(docker-compose logs --since="1h" 2>&1 | grep -i error | wc -l)
    
    if [ $error_count -gt 10 ]; then
        send_alert "Muchos Errores" "Se encontraron $error_count errores en los logs de la última hora"
    fi
}

# Función principal
main() {
    log_message "=== Iniciando monitoreo de SMARTLABS ==="
    
    check_docker_services
    check_mqtt_connectivity
    check_database
    check_web_dashboard
    check_disk_usage
    check_memory_usage
    check_error_logs
    
    log_message "=== Monitoreo completado ==="
}

# Ejecutar monitoreo
main
```

## Casos de Uso Específicos

### 1. Sistema de Alertas en Tiempo Real

```python
#!/usr/bin/env python3
# alert_system.py - Sistema de alertas para SMARTLABS

import paho.mqtt.client as mqtt
import json
import smtplib
import requests
from email.mime.text import MimeText
from datetime import datetime, timedelta
import mysql.connector
from collections import defaultdict

class SmartLabsAlertSystem:
    def __init__(self):
        self.mqtt_client = mqtt.Client()
        self.setup_mqtt()
        
        # Configuración de alertas
        self.alert_rules = {
            'temperature_high': {'threshold': 35.0, 'enabled': True},
            'temperature_low': {'threshold': 15.0, 'enabled': True},
            'voltage_low': {'threshold': 3.0, 'enabled': True},
            'access_denied_burst': {'count': 5, 'window': 300, 'enabled': True},
            'device_offline': {'timeout': 600, 'enabled': True}
        }
        
        # Historial para detección de patrones
        self.access_history = defaultdict(list)
        self.device_last_seen = {}
        
        # Configuración de notificaciones
        self.email_config = {
            'smtp_server': 'smtp.gmail.com',
            'smtp_port': 587,
            'username': 'alerts@smartlabs.com',
            'password': 'your_password',
            'recipients': ['admin@smartlabs.com']
        }
        
        self.telegram_config = {
            'bot_token': 'YOUR_BOT_TOKEN',
            'chat_id': 'YOUR_CHAT_ID'
        }
    
    def setup_mqtt(self):
        self.mqtt_client.username_pw_set('emqx', 'emqxpass')
        self.mqtt_client.on_connect = self.on_connect
        self.mqtt_client.on_message = self.on_message
        self.mqtt_client.connect('localhost', 1883, 60)
        self.mqtt_client.loop_start()
    
    def on_connect(self, client, userdata, flags, rc):
        print(f"Sistema de alertas conectado al MQTT broker (rc: {rc})")
        client.subscribe('smartlabs/+/data')
        client.subscribe('smartlabs/+/access')
        client.subscribe('smartlabs/+/status')
    
    def on_message(self, client, userdata, msg):
        try:
            topic = msg.topic
            data = json.loads(msg.payload.decode())
            
            if '/data' in topic:
                self.check_sensor_alerts(data)
            elif '/access' in topic:
                self.check_access_alerts(data)
            elif '/status' in topic:
                self.check_device_status(data)
                
        except Exception as e:
            print(f"Error procesando mensaje: {e}")
    
    def check_sensor_alerts(self, data):
        device_id = data.get('device_id')
        temperature = data.get('temperature')
        voltage = data.get('voltage')
        
        # Actualizar última actividad del dispositivo
        self.device_last_seen[device_id] = datetime.now()
        
        # Verificar temperatura alta
        if (temperature and temperature > self.alert_rules['temperature_high']['threshold'] 
            and self.alert_rules['temperature_high']['enabled']):
            self.send_alert(
                'Temperatura Alta',
                f'Dispositivo {device_id}: Temperatura {temperature}°C excede el límite de {self.alert_rules["temperature_high"]["threshold"]}°C',
                'warning'
            )
        
        # Verificar temperatura baja
        if (temperature and temperature < self.alert_rules['temperature_low']['threshold'] 
            and self.alert_rules['temperature_low']['enabled']):
            self.send_alert(
                'Temperatura Baja',
                f'Dispositivo {device_id}: Temperatura {temperature}°C por debajo del límite de {self.alert_rules["temperature_low"]["threshold"]}°C',
                'warning'
            )
        
        # Verificar voltaje bajo
        if (voltage and voltage < self.alert_rules['voltage_low']['threshold'] 
            and self.alert_rules['voltage_low']['enabled']):
            self.send_alert(
                'Voltaje Bajo',
                f'Dispositivo {device_id}: Voltaje {voltage}V por debajo del límite de {self.alert_rules["voltage_low"]["threshold"]}V',
                'critical'
            )
    
    def check_access_alerts(self, data):
        device_id = data.get('device_id')
        access_granted = data.get('access_granted')
        timestamp = datetime.now()
        
        # Registrar intento de acceso
        self.access_history[device_id].append({
            'timestamp': timestamp,
            'granted': access_granted
        })
        
        # Limpiar historial antiguo
        cutoff_time = timestamp - timedelta(seconds=self.alert_rules['access_denied_burst']['window'])
        self.access_history[device_id] = [
            entry for entry in self.access_history[device_id] 
            if entry['timestamp'] > cutoff_time
        ]
        
        # Verificar ráfaga de accesos denegados
        if self.alert_rules['access_denied_burst']['enabled']:
            denied_count = sum(1 for entry in self.access_history[device_id] if not entry['granted'])
            
            if denied_count >= self.alert_rules['access_denied_burst']['count']:
                self.send_alert(
                    'Ráfaga de Accesos Denegados',
                    f'Dispositivo {device_id}: {denied_count} accesos denegados en los últimos {self.alert_rules["access_denied_burst"]["window"]} segundos',
                    'critical'
                )
                
                # Limpiar historial para evitar alertas repetidas
                self.access_history[device_id] = []
    
    def check_device_status(self, data):
        device_id = data.get('device_id')
        status = data.get('status')
        
        self.device_last_seen[device_id] = datetime.now()
        
        if status == 'offline':
            self.send_alert(
                'Dispositivo Desconectado',
                f'Dispositivo {device_id} reportó estado offline',
                'warning'
            )
    
    def check_offline_devices(self):
        """Verificar dispositivos que no han reportado actividad"""
        if not self.alert_rules['device_offline']['enabled']:
            return
            
        current_time = datetime.now()
        timeout = timedelta(seconds=self.alert_rules['device_offline']['timeout'])
        
        for device_id, last_seen in self.device_last_seen.items():
            if current_time - last_seen > timeout:
                self.send_alert(
                    'Dispositivo Sin Respuesta',
                    f'Dispositivo {device_id} sin actividad por más de {self.alert_rules["device_offline"]["timeout"]} segundos',
                    'critical'
                )
                
                # Actualizar para evitar alertas repetidas
                self.device_last_seen[device_id] = current_time
    
    def send_alert(self, title, message, severity='info'):
        timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        full_message = f"[{timestamp}] [{severity.upper()}] {title}\n\n{message}"
        
        print(full_message)
        
        # Enviar por email
        self.send_email_alert(title, full_message)
        
        # Enviar por Telegram
        self.send_telegram_alert(title, full_message, severity)
        
        # Guardar en base de datos
        self.save_alert_to_db(title, message, severity)
    
    def send_email_alert(self, subject, message):
        try:
            msg = MimeText(message)
            msg['Subject'] = f"SMARTLABS Alert: {subject}"
            msg['From'] = self.email_config['username']
            msg['To'] = ', '.join(self.email_config['recipients'])
            
            server = smtplib.SMTP(self.email_config['smtp_server'], self.email_config['smtp_port'])
            server.starttls()
            server.login(self.email_config['username'], self.email_config['password'])
            server.send_message(msg)
            server.quit()
            
            print(f"Email enviado: {subject}")
            
        except Exception as e:
            print(f"Error enviando email: {e}")
    
    def send_telegram_alert(self, title, message, severity):
        try:
            # Emojis según severidad
            emoji_map = {
                'info': 'ℹ️',
                'warning': '⚠️',
                'critical': '🚨'
            }
            
            emoji = emoji_map.get(severity, 'ℹ️')
            telegram_message = f"{emoji} {title}\n\n{message}"
            
            url = f"https://api.telegram.org/bot{self.telegram_config['bot_token']}/sendMessage"
            data = {
                'chat_id': self.telegram_config['chat_id'],
                'text': telegram_message,
                'parse_mode': 'HTML'
            }
            
            response = requests.post(url, data=data, timeout=10)
            
            if response.status_code == 200:
                print(f"Telegram enviado: {title}")
            else:
                print(f"Error enviando Telegram: {response.status_code}")
                
        except Exception as e:
            print(f"Error enviando Telegram: {e}")
    
    def save_alert_to_db(self, title, message, severity):
        try:
            conn = mysql.connector.connect(
                host='localhost',
                port=4000,
                user='emqxuser',
                password='emqxpass',
                database='emqx'
            )
            
            cursor = conn.cursor()
            
            # Crear tabla de alertas si no existe
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS alerts (
                    alert_id INT AUTO_INCREMENT PRIMARY KEY,
                    alert_title VARCHAR(255) NOT NULL,
                    alert_message TEXT NOT NULL,
                    alert_severity ENUM('info', 'warning', 'critical') NOT NULL,
                    alert_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            """)
            
            # Insertar alerta
            cursor.execute(
                "INSERT INTO alerts (alert_title, alert_message, alert_severity) VALUES (%s, %s, %s)",
                (title, message, severity)
            )
            
            conn.commit()
            
        except Exception as e:
            print(f"Error guardando alerta en BD: {e}")
        finally:
            if 'conn' in locals():
                conn.close()
    
    def run(self):
        print("Sistema de alertas SMARTLABS iniciado")
        
        try:
            import time
            while True:
                # Verificar dispositivos offline cada 5 minutos
                self.check_offline_devices()
                time.sleep(300)
                
        except KeyboardInterrupt:
            print("\nDeteniendo sistema de alertas...")
            self.mqtt_client.loop_stop()
            self.mqtt_client.disconnect()

if __name__ == "__main__":
    alert_system = SmartLabsAlertSystem()
    alert_system.run()
```

### 2. Integración con API Externa

```python
#!/usr/bin/env python3
# external_api_integration.py - Integración con APIs externas

import requests
import json
import mysql.connector
from datetime import datetime, timedelta
import schedule
import time

class ExternalAPIIntegration:
    def __init__(self):
        self.db_config = {
            'host': 'localhost',
            'port': 4000,
            'user': 'emqxuser',
            'password': 'emqxpass',
            'database': 'emqx'
        }
        
        # Configuración de APIs externas
        self.weather_api_key = "YOUR_WEATHER_API_KEY"
        self.university_api_url = "https://api.university.edu/students"
        self.university_api_key = "YOUR_UNIVERSITY_API_KEY"
    
    def sync_student_data(self):
        """Sincronizar datos de estudiantes desde API universitaria"""
        try:
            print(f"[{datetime.now()}] Sincronizando datos de estudiantes...")
            
            headers = {
                'Authorization': f'Bearer {self.university_api_key}',
                'Content-Type': 'application/json'
            }
            
            response = requests.get(self.university_api_url, headers=headers, timeout=30)
            
            if response.status_code == 200:
                students = response.json()
                self.update_student_database(students)
                print(f"Sincronizados {len(students)} estudiantes")
            else:
                print(f"Error en API universitaria: {response.status_code}")
                
        except Exception as e:
            print(f"Error sincronizando estudiantes: {e}")
    
    def update_student_database(self, students):
        """Actualizar base de datos con información de estudiantes"""
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor()
            
            for student in students:
                # Verificar si el estudiante ya existe
                cursor.execute(
                    "SELECT hab_id FROM habintants WHERE hab_registration = %s",
                    (student['student_id'],)
                )
                
                existing = cursor.fetchone()
                
                if existing:
                    # Actualizar estudiante existente
                    cursor.execute("""
                        UPDATE habintants 
                        SET hab_name = %s, hab_email = %s, hab_career = %s
                        WHERE hab_registration = %s
                    """, (
                        student['name'],
                        student['email'],
                        student.get('career', ''),
                        student['student_id']
                    ))
                else:
                    # Insertar nuevo estudiante
                    cursor.execute("""
                        INSERT INTO habintants (hab_name, hab_registration, hab_email, hab_career)
                        VALUES (%s, %s, %s, %s)
                    """, (
                        student['name'],
                        student['student_id'],
                        student['email'],
                        student.get('career', '')
                    ))
            
            conn.commit()
            
        except Exception as e:
            print(f"Error actualizando base de datos: {e}")
        finally:
            if 'conn' in locals():
                conn.close()
    
    def get_weather_data(self):
        """Obtener datos meteorológicos"""
        try:
            url = f"http://api.openweathermap.org/data/2.5/weather?q=Mexico City&appid={self.weather_api_key}&units=metric"
            
            response = requests.get(url, timeout=10)
            
            if response.status_code == 200:
                weather = response.json()
                
                weather_data = {
                    'temperature': weather['main']['temp'],
                    'humidity': weather['main']['humidity'],
                    'pressure': weather['main']['pressure'],
                    'description': weather['weather'][0]['description']
                }
                
                self.save_weather_data(weather_data)
                return weather_data
            else:
                print(f"Error en API del clima: {response.status_code}")
                return None
                
        except Exception as e:
            print(f"Error obteniendo datos del clima: {e}")
            return None
    
    def save_weather_data(self, weather_data):
        """Guardar datos meteorológicos en base de datos"""
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor()
            
            # Crear tabla de clima si no existe
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS weather_data (
                    weather_id INT AUTO_INCREMENT PRIMARY KEY,
                    temperature DECIMAL(5,2),
                    humidity INT,
                    pressure DECIMAL(7,2),
                    description VARCHAR(255),
                    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            """)
            
            # Insertar datos del clima
            cursor.execute("""
                INSERT INTO weather_data (temperature, humidity, pressure, description)
                VALUES (%s, %s, %s, %s)
            """, (
                weather_data['temperature'],
                weather_data['humidity'],
                weather_data['pressure'],
                weather_data['description']
            ))
            
            conn.commit()
            print(f"Datos del clima guardados: {weather_data['temperature']}°C")
            
        except Exception as e:
            print(f"Error guardando datos del clima: {e}")
        finally:
            if 'conn' in locals():
                conn.close()
    
    def generate_daily_report(self):
        """Generar reporte diario"""
        try:
            print(f"[{datetime.now()}] Generando reporte diario...")
            
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor()
            
            # Estadísticas del día
            today = datetime.now().date()
            
            # Total de accesos
            cursor.execute(
                "SELECT COUNT(*) FROM traffic WHERE DATE(traffic_date) = %s",
                (today,)
            )
            total_accesos = cursor.fetchone()[0]
            
            # Accesos exitosos
            cursor.execute(
                "SELECT COUNT(*) FROM traffic WHERE DATE(traffic_date) = %s AND traffic_state = 1",
                (today,)
            )
            accesos_exitosos = cursor.fetchone()[0]
            
            # Dispositivos activos
            cursor.execute(
                "SELECT COUNT(DISTINCT traffic_device) FROM traffic WHERE DATE(traffic_date) = %s",
                (today,)
            )
            dispositivos_activos = cursor.fetchone()[0]
            
            # Préstamos del día
            cursor.execute(
                "SELECT COUNT(*) FROM loans WHERE DATE(loans_date) = %s",
                (today,)
            )
            prestamos_dia = cursor.fetchone()[0]
            
            # Crear reporte
            report = {
                'fecha': today.isoformat(),
                'total_accesos': total_accesos,
                'accesos_exitosos': accesos_exitosos,
                'tasa_exito': round((accesos_exitosos / total_accesos * 100) if total_accesos > 0 else 0, 2),
                'dispositivos_activos': dispositivos_activos,
                'prestamos_dia': prestamos_dia
            }
            
            # Enviar reporte por email o API
            self.send_daily_report(report)
            
            return report
            
        except Exception as e:
            print(f"Error generando reporte diario: {e}")
            return None
        finally:
            if 'conn' in locals():
                conn.close()
    
    def send_daily_report(self, report):
        """Enviar reporte diario"""
        try:
            # Formatear reporte
            report_text = f"""
📊 REPORTE DIARIO SMARTLABS - {report['fecha']}

🔑 Accesos:
   • Total: {report['total_accesos']}
   • Exitosos: {report['accesos_exitosos']}
   • Tasa de éxito: {report['tasa_exito']}%

📱 Dispositivos activos: {report['dispositivos_activos']}
📦 Préstamos del día: {report['prestamos_dia']}

---
Generado automáticamente por SMARTLABS
            """
            
            print("Reporte diario:")
            print(report_text)
            
            # Aquí se podría enviar por email, Slack, etc.
            
        except Exception as e:
            print(f"Error enviando reporte: {e}")
    
    def setup_scheduled_tasks(self):
        """Configurar tareas programadas"""
        # Sincronizar estudiantes cada 6 horas
        schedule.every(6).hours.do(self.sync_student_data)
        
        # Obtener datos del clima cada hora
        schedule.every().hour.do(self.get_weather_data)
        
        # Generar reporte diario a las 23:00
        schedule.every().day.at("23:00").do(self.generate_daily_report)
        
        print("Tareas programadas configuradas:")
        print("- Sincronización de estudiantes: cada 6 horas")
        print("- Datos del clima: cada hora")
        print("- Reporte diario: 23:00")
    
    def run(self):
        """Ejecutar integración"""
        print("Iniciando integración con APIs externas...")
        
        self.setup_scheduled_tasks()
        
        # Ejecutar sincronización inicial
        self.sync_student_data()
        self.get_weather_data()
        
        try:
            while True:
                schedule.run_pending()
                time.sleep(60)  # Verificar cada minuto
                
        except KeyboardInterrupt:
            print("\nDeteniendo integración...")

if __name__ == "__main__":
    integration = ExternalAPIIntegration()
    integration.run()
```

---

## Conclusión

Este documento proporciona ejemplos completos y funcionales para integrar y utilizar la infraestructura Docker de SMARTLABS. Los ejemplos cubren desde configuraciones básicas hasta implementaciones avanzadas de monitoreo, alertas e integración con sistemas externos.

### Próximos Pasos

1. **Personalizar configuraciones** según las necesidades específicas de tu laboratorio
2. **Implementar autenticación robusta** para producción
3. **Configurar monitoreo y alertas** según los ejemplos proporcionados
4. **Integrar con sistemas universitarios** existentes
5. **Establecer procedimientos de backup** y recuperación

### Soporte

Para soporte técnico o consultas sobre la implementación, consulta la documentación adicional en `README.md`, `ARCHITECTURE.md` y `DEPLOYMENT.md`.