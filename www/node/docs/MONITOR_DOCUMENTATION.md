# SmartLabs Device Monitor - Documentación Técnica

## 📋 Índice

1. [Arquitectura](#arquitectura)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Configuración](#configuración)
4. [Protocolo WebSocket](#protocolo-websocket)
5. [Base de Datos](#base-de-datos)
6. [Servicios](#servicios)
7. [Monitoreo en Tiempo Real](#monitoreo-en-tiempo-real)
8. [API REST](#api-rest)
9. [Manejo de Conexiones](#manejo-de-conexiones)
10. [Logging y Debugging](#logging-y-debugging)
11. [Performance](#performance)
12. [Deployment](#deployment)

## 🏗️ Arquitectura

### Patrón de Diseño
El servicio de monitoreo utiliza una arquitectura **Event-Driven** con WebSockets para comunicación en tiempo real:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   WebSocket     │───▶│   Connection    │───▶│   Subscription  │
│   Server        │    │   Manager       │    │   Manager       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   HTTP Server   │    │   Device        │    │   Database      │
│  (Health Check) │    │   Monitor       │    │   (MySQL)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Flujo de Datos
1. **Cliente** se conecta vía WebSocket
2. **Suscripción** a dispositivos específicos o todos
3. **Monitor** consulta base de datos periódicamente
4. **Broadcast** de cambios a clientes suscritos
5. **Heartbeat** para mantener conexiones activas

## 📁 Estructura del Proyecto

```
node/
├── src/
│   ├── config/
│   │   ├── database.js              # Configuración MySQL
│   │   └── device-status.js         # Configuración del monitor
│   ├── services/
│   │   └── device-status/
│   │       ├── server.js            # Servidor WebSocket principal
│   │       ├── connectionManager.js # Gestión de conexiones
│   │       ├── subscriptionManager.js# Gestión de suscripciones
│   │       ├── deviceMonitor.js     # Monitor de dispositivos
│   │       └── databaseService.js   # Servicio de base de datos
│   └── utils/
│       ├── logger.js                # Sistema de logs
│       └── helpers.js               # Funciones auxiliares
├── scripts/
│   └── start-device-server.js       # Script de inicio
├── logs/                            # Archivos de log
├── package.json                     # Dependencias
├── .env                             # Variables de entorno
└── README.md                        # Documentación básica
```

## ⚙️ Configuración

### Variables de Entorno (.env)
```bash
# Servidor WebSocket
WS_PORT=8080
HTTP_PORT=8080
NODE_ENV=production

# Base de Datos Principal
DB_HOST=localhost
DB_PORT=3306
DB_USER=emqxuser
DB_PASSWORD=emqxpass
DB_NAME=emqx

# Base de Datos Fallback
DB_FALLBACK_HOST=localhost
DB_FALLBACK_PORT=3306
DB_FALLBACK_USER=backup_user
DB_FALLBACK_PASSWORD=backup_pass
DB_FALLBACK_NAME=emqx_backup

# Configuración del Monitor
MONITOR_INTERVAL=5000
HEARTBEAT_INTERVAL=30000
CONNECTION_TIMEOUT=60000

# Logs
LOG_LEVEL=info
LOG_DIR=./logs
```

### Configuración del Monitor
```javascript
// src/config/device-status.js
module.exports = {
  server: {
    port: process.env.WS_PORT || 8080,
    httpPort: process.env.HTTP_PORT || 8080,
    heartbeatInterval: parseInt(process.env.HEARTBEAT_INTERVAL) || 30000,
    connectionTimeout: parseInt(process.env.CONNECTION_TIMEOUT) || 60000
  },
  
  monitor: {
    interval: parseInt(process.env.MONITOR_INTERVAL) || 5000,
    batchSize: 100,
    maxRetries: 3,
    retryDelay: 1000
  },
  
  database: {
    connectionLimit: 10,
    acquireTimeout: 60000,
    timeout: 60000,
    reconnect: true
  },
  
  queries: {
    deviceStatus: `
      SELECT 
        device_serie,
        device_name,
        status,
        last_update,
        UNIX_TIMESTAMP(last_update) as timestamp
      FROM traffic 
      WHERE active = 1
      ORDER BY last_update DESC
    `,
    
    userDevices: `
      SELECT 
        h.registration,
        h.name as user_name,
        t.device_serie,
        t.device_name,
        t.status,
        t.last_update
      FROM habintants h
      JOIN user_devices ud ON h.id = ud.user_id
      JOIN traffic t ON ud.device_serie = t.device_serie
      WHERE h.active = 1 AND t.active = 1
    `
  }
};
```

## 🔌 Protocolo WebSocket

### Conexión
```javascript
// Cliente se conecta
const ws = new WebSocket('ws://localhost:8080');

ws.onopen = () => {
  console.log('Conectado al monitor de dispositivos');
};
```

### Mensajes del Cliente

#### Suscribirse a Dispositivos Específicos
```json
{
  "type": "subscribe",
  "devices": ["DEV001", "DEV002", "DEV003"]
}
```

#### Suscribirse a Todos los Dispositivos
```json
{
  "type": "subscribe_all"
}
```

#### Desuscribirse
```json
{
  "type": "unsubscribe",
  "devices": ["DEV001"]
}
```

#### Ping (Heartbeat)
```json
{
  "type": "ping"
}
```

### Mensajes del Servidor

#### Estado Inicial (al suscribirse)
```json
{
  "type": "initial_status",
  "timestamp": "2024-01-15T10:30:00Z",
  "devices": [
    {
      "device_serie": "DEV001",
      "device_name": "Laboratorio A - Mesa 1",
      "status": "on",
      "last_update": "2024-01-15T10:29:45Z",
      "timestamp": 1705312185
    }
  ]
}
```

#### Actualización de Estado
```json
{
  "type": "status_update",
  "timestamp": "2024-01-15T10:30:00Z",
  "device": {
    "device_serie": "DEV001",
    "device_name": "Laboratorio A - Mesa 1",
    "status": "off",
    "last_update": "2024-01-15T10:30:00Z",
    "timestamp": 1705312200,
    "previous_status": "on"
  }
}
```

#### Pong (Respuesta a Ping)
```json
{
  "type": "pong",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

#### Error
```json
{
  "type": "error",
  "message": "Dispositivo no encontrado",
  "code": "DEVICE_NOT_FOUND",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

#### Información de Conexión
```json
{
  "type": "connection_info",
  "client_id": "client_123456",
  "connected_at": "2024-01-15T10:30:00Z",
  "subscriptions": ["DEV001", "DEV002"]
}
```

## 💾 Base de Datos

### Configuración de Conexión
```javascript
// src/config/database.js
const mysql = require('mysql2/promise');

const primaryConfig = {
  host: process.env.DB_HOST,
  port: process.env.DB_PORT,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  acquireTimeout: 60000,
  timeout: 60000,
  reconnect: true
};

const fallbackConfig = {
  host: process.env.DB_FALLBACK_HOST,
  port: process.env.DB_FALLBACK_PORT,
  user: process.env.DB_FALLBACK_USER,
  password: process.env.DB_FALLBACK_PASSWORD,
  database: process.env.DB_FALLBACK_NAME,
  // ... mismas opciones
};
```

### Tablas Monitoreadas

#### Tabla `traffic` (Estado de Dispositivos)
```sql
CREATE TABLE traffic (
  id INT PRIMARY KEY AUTO_INCREMENT,
  device_serie VARCHAR(20) UNIQUE NOT NULL,
  device_name VARCHAR(100),
  status ENUM('on', 'off', 'error', 'maintenance') DEFAULT 'off',
  last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  active BOOLEAN DEFAULT TRUE,
  location VARCHAR(100),
  device_type VARCHAR(50),
  INDEX idx_device_serie (device_serie),
  INDEX idx_status (status),
  INDEX idx_last_update (last_update),
  INDEX idx_active (active)
);
```

#### Tabla `habintants` (Usuarios)
```sql
CREATE TABLE habintants (
  id INT PRIMARY KEY AUTO_INCREMENT,
  registration VARCHAR(10) UNIQUE NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_registration (registration),
  INDEX idx_active (active)
);
```

#### Tabla `user_devices` (Relación Usuario-Dispositivo)
```sql
CREATE TABLE user_devices (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  device_serie VARCHAR(20) NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  active BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (user_id) REFERENCES habintants(id),
  FOREIGN KEY (device_serie) REFERENCES traffic(device_serie),
  UNIQUE KEY unique_user_device (user_id, device_serie),
  INDEX idx_user_id (user_id),
  INDEX idx_device_serie (device_serie)
);
```

### Consultas Optimizadas

```javascript
// Obtener estado de todos los dispositivos
const getAllDevicesStatus = async () => {
  const query = `
    SELECT 
      device_serie,
      device_name,
      status,
      last_update,
      UNIX_TIMESTAMP(last_update) as timestamp,
      location,
      device_type
    FROM traffic 
    WHERE active = 1
    ORDER BY last_update DESC
  `;
  
  return await db.execute(query);
};

// Obtener dispositivos de un usuario específico
const getUserDevicesStatus = async (registration) => {
  const query = `
    SELECT 
      t.device_serie,
      t.device_name,
      t.status,
      t.last_update,
      UNIX_TIMESTAMP(t.last_update) as timestamp,
      t.location,
      t.device_type,
      h.registration,
      h.name as user_name
    FROM traffic t
    JOIN user_devices ud ON t.device_serie = ud.device_serie
    JOIN habintants h ON ud.user_id = h.id
    WHERE h.registration = ? AND h.active = 1 AND t.active = 1 AND ud.active = 1
    ORDER BY t.last_update DESC
  `;
  
  return await db.execute(query, [registration]);
};

// Detectar cambios desde la última consulta
const getDeviceChanges = async (lastTimestamp) => {
  const query = `
    SELECT 
      device_serie,
      device_name,
      status,
      last_update,
      UNIX_TIMESTAMP(last_update) as timestamp
    FROM traffic 
    WHERE active = 1 AND UNIX_TIMESTAMP(last_update) > ?
    ORDER BY last_update ASC
  `;
  
  return await db.execute(query, [lastTimestamp]);
};
```

## 🔧 Servicios

### Device Monitor Service
```javascript
// src/services/device-status/deviceMonitor.js
class DeviceMonitor {
  constructor(databaseService, subscriptionManager) {
    this.db = databaseService;
    this.subscriptions = subscriptionManager;
    this.lastTimestamp = 0;
    this.isRunning = false;
    this.intervalId = null;
  }
  
  start() {
    if (this.isRunning) return;
    
    this.isRunning = true;
    this.intervalId = setInterval(() => {
      this.checkForUpdates();
    }, config.monitor.interval);
    
    logger.info('Device monitor started');
  }
  
  stop() {
    if (!this.isRunning) return;
    
    this.isRunning = false;
    if (this.intervalId) {
      clearInterval(this.intervalId);
      this.intervalId = null;
    }
    
    logger.info('Device monitor stopped');
  }
  
  async checkForUpdates() {
    try {
      const changes = await this.db.getDeviceChanges(this.lastTimestamp);
      
      if (changes.length > 0) {
        for (const device of changes) {
          this.subscriptions.broadcastDeviceUpdate(device);
          this.lastTimestamp = Math.max(this.lastTimestamp, device.timestamp);
        }
        
        logger.debug(`Processed ${changes.length} device updates`);
      }
    } catch (error) {
      logger.error('Error checking for device updates:', error);
    }
  }
  
  async getInitialStatus(deviceSeries = null) {
    try {
      if (deviceSeries && deviceSeries.length > 0) {
        return await this.db.getSpecificDevicesStatus(deviceSeries);
      } else {
        return await this.db.getAllDevicesStatus();
      }
    } catch (error) {
      logger.error('Error getting initial device status:', error);
      throw error;
    }
  }
}
```

### Connection Manager
```javascript
// src/services/device-status/connectionManager.js
class ConnectionManager {
  constructor() {
    this.connections = new Map();
    this.heartbeatInterval = null;
  }
  
  addConnection(ws, clientId) {
    const connection = {
      id: clientId,
      ws: ws,
      connectedAt: new Date(),
      lastPing: new Date(),
      subscriptions: new Set(),
      isAlive: true
    };
    
    this.connections.set(clientId, connection);
    
    // Configurar ping/pong
    ws.on('pong', () => {
      connection.lastPing = new Date();
      connection.isAlive = true;
    });
    
    logger.info(`Client ${clientId} connected`);
    return connection;
  }
  
  removeConnection(clientId) {
    const connection = this.connections.get(clientId);
    if (connection) {
      this.connections.delete(clientId);
      logger.info(`Client ${clientId} disconnected`);
    }
  }
  
  startHeartbeat() {
    this.heartbeatInterval = setInterval(() => {
      this.connections.forEach((connection, clientId) => {
        if (!connection.isAlive) {
          logger.warn(`Client ${clientId} failed heartbeat, terminating`);
          connection.ws.terminate();
          this.removeConnection(clientId);
          return;
        }
        
        connection.isAlive = false;
        connection.ws.ping();
      });
    }, config.server.heartbeatInterval);
  }
  
  stopHeartbeat() {
    if (this.heartbeatInterval) {
      clearInterval(this.heartbeatInterval);
      this.heartbeatInterval = null;
    }
  }
  
  getConnectionStats() {
    return {
      totalConnections: this.connections.size,
      activeConnections: Array.from(this.connections.values())
        .filter(conn => conn.isAlive).length,
      connections: Array.from(this.connections.values()).map(conn => ({
        id: conn.id,
        connectedAt: conn.connectedAt,
        lastPing: conn.lastPing,
        subscriptions: Array.from(conn.subscriptions),
        isAlive: conn.isAlive
      }))
    };
  }
}
```

### Subscription Manager
```javascript
// src/services/device-status/subscriptionManager.js
class SubscriptionManager {
  constructor(connectionManager) {
    this.connections = connectionManager;
    this.deviceSubscriptions = new Map(); // device_serie -> Set of client IDs
    this.globalSubscriptions = new Set(); // client IDs subscribed to all devices
  }
  
  subscribe(clientId, deviceSeries) {
    const connection = this.connections.connections.get(clientId);
    if (!connection) return false;
    
    if (deviceSeries && deviceSeries.length > 0) {
      // Suscripción a dispositivos específicos
      deviceSeries.forEach(deviceSerie => {
        if (!this.deviceSubscriptions.has(deviceSerie)) {
          this.deviceSubscriptions.set(deviceSerie, new Set());
        }
        this.deviceSubscriptions.get(deviceSerie).add(clientId);
        connection.subscriptions.add(deviceSerie);
      });
    } else {
      // Suscripción global
      this.globalSubscriptions.add(clientId);
      connection.subscriptions.add('*');
    }
    
    logger.debug(`Client ${clientId} subscribed to devices:`, deviceSeries || 'ALL');
    return true;
  }
  
  unsubscribe(clientId, deviceSeries = null) {
    const connection = this.connections.connections.get(clientId);
    if (!connection) return false;
    
    if (deviceSeries && deviceSeries.length > 0) {
      // Desuscribirse de dispositivos específicos
      deviceSeries.forEach(deviceSerie => {
        const subscribers = this.deviceSubscriptions.get(deviceSerie);
        if (subscribers) {
          subscribers.delete(clientId);
          if (subscribers.size === 0) {
            this.deviceSubscriptions.delete(deviceSerie);
          }
        }
        connection.subscriptions.delete(deviceSerie);
      });
    } else {
      // Desuscribirse de todo
      this.globalSubscriptions.delete(clientId);
      connection.subscriptions.forEach(deviceSerie => {
        if (deviceSerie === '*') {
          connection.subscriptions.delete('*');
        } else {
          const subscribers = this.deviceSubscriptions.get(deviceSerie);
          if (subscribers) {
            subscribers.delete(clientId);
            if (subscribers.size === 0) {
              this.deviceSubscriptions.delete(deviceSerie);
            }
          }
          connection.subscriptions.delete(deviceSerie);
        }
      });
    }
    
    return true;
  }
  
  broadcastDeviceUpdate(device) {
    const message = {
      type: 'status_update',
      timestamp: new Date().toISOString(),
      device: device
    };
    
    const messageStr = JSON.stringify(message);
    const notifiedClients = new Set();
    
    // Notificar a suscriptores específicos del dispositivo
    const deviceSubscribers = this.deviceSubscriptions.get(device.device_serie);
    if (deviceSubscribers) {
      deviceSubscribers.forEach(clientId => {
        this.sendToClient(clientId, messageStr);
        notifiedClients.add(clientId);
      });
    }
    
    // Notificar a suscriptores globales
    this.globalSubscriptions.forEach(clientId => {
      if (!notifiedClients.has(clientId)) {
        this.sendToClient(clientId, messageStr);
      }
    });
  }
  
  sendToClient(clientId, message) {
    const connection = this.connections.connections.get(clientId);
    if (connection && connection.ws.readyState === 1) { // WebSocket.OPEN
      try {
        connection.ws.send(message);
      } catch (error) {
        logger.error(`Error sending message to client ${clientId}:`, error);
        this.connections.removeConnection(clientId);
      }
    }
  }
  
  getSubscriptionStats() {
    return {
      deviceSubscriptions: Object.fromEntries(
        Array.from(this.deviceSubscriptions.entries()).map(([device, clients]) => [
          device,
          Array.from(clients)
        ])
      ),
      globalSubscriptions: Array.from(this.globalSubscriptions),
      totalDeviceSubscriptions: this.deviceSubscriptions.size,
      totalGlobalSubscriptions: this.globalSubscriptions.size
    };
  }
}
```

## 🌐 API REST

### Health Check Endpoint
```javascript
// Health check endpoint
app.get('/health', (req, res) => {
  const stats = {
    status: 'OK',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    memory: process.memoryUsage(),
    connections: connectionManager.getConnectionStats(),
    subscriptions: subscriptionManager.getSubscriptionStats(),
    database: {
      primary: databaseService.isPrimaryConnected(),
      fallback: databaseService.isFallbackConnected()
    }
  };
  
  res.json(stats);
});
```

### Métricas Endpoint
```javascript
// Métricas para monitoreo
app.get('/metrics', async (req, res) => {
  try {
    const deviceCount = await databaseService.getDeviceCount();
    const activeDevices = await databaseService.getActiveDeviceCount();
    
    const metrics = {
      timestamp: new Date().toISOString(),
      devices: {
        total: deviceCount,
        active: activeDevices,
        inactive: deviceCount - activeDevices
      },
      connections: connectionManager.getConnectionStats(),
      subscriptions: subscriptionManager.getSubscriptionStats(),
      performance: {
        uptime: process.uptime(),
        memory: process.memoryUsage(),
        cpu: process.cpuUsage()
      }
    };
    
    res.json(metrics);
  } catch (error) {
    logger.error('Error getting metrics:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});
```

## 📊 Monitoreo en Tiempo Real

### Cliente JavaScript
```javascript
// Ejemplo de cliente para monitoreo
class DeviceMonitorClient {
  constructor(url) {
    this.url = url;
    this.ws = null;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 1000;
  }
  
  connect() {
    this.ws = new WebSocket(this.url);
    
    this.ws.onopen = () => {
      console.log('Conectado al monitor de dispositivos');
      this.reconnectAttempts = 0;
      this.startHeartbeat();
    };
    
    this.ws.onmessage = (event) => {
      const message = JSON.parse(event.data);
      this.handleMessage(message);
    };
    
    this.ws.onclose = () => {
      console.log('Conexión cerrada');
      this.stopHeartbeat();
      this.attemptReconnect();
    };
    
    this.ws.onerror = (error) => {
      console.error('Error de WebSocket:', error);
    };
  }
  
  subscribeToDevices(deviceSeries) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({
        type: 'subscribe',
        devices: deviceSeries
      }));
    }
  }
  
  subscribeToAll() {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({
        type: 'subscribe_all'
      }));
    }
  }
  
  handleMessage(message) {
    switch (message.type) {
      case 'initial_status':
        this.onInitialStatus(message.devices);
        break;
      case 'status_update':
        this.onStatusUpdate(message.device);
        break;
      case 'pong':
        // Heartbeat response
        break;
      case 'error':
        this.onError(message);
        break;
    }
  }
  
  onInitialStatus(devices) {
    console.log('Estado inicial de dispositivos:', devices);
    // Actualizar UI con estado inicial
  }
  
  onStatusUpdate(device) {
    console.log('Actualización de dispositivo:', device);
    // Actualizar UI con nuevo estado
  }
  
  onError(error) {
    console.error('Error del servidor:', error);
  }
  
  startHeartbeat() {
    this.heartbeatInterval = setInterval(() => {
      if (this.ws && this.ws.readyState === WebSocket.OPEN) {
        this.ws.send(JSON.stringify({ type: 'ping' }));
      }
    }, 30000);
  }
  
  stopHeartbeat() {
    if (this.heartbeatInterval) {
      clearInterval(this.heartbeatInterval);
      this.heartbeatInterval = null;
    }
  }
  
  attemptReconnect() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      console.log(`Reintentando conexión (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
      
      setTimeout(() => {
        this.connect();
      }, this.reconnectDelay * this.reconnectAttempts);
    } else {
      console.error('Máximo número de reintentos alcanzado');
    }
  }
}

// Uso
const monitor = new DeviceMonitorClient('ws://localhost:8080');
monitor.connect();
monitor.subscribeToAll();
```

### Cliente Flutter/Dart
```dart
// Ejemplo para Flutter
import 'package:web_socket_channel/web_socket_channel.dart';
import 'dart:convert';

class DeviceMonitorService {
  WebSocketChannel? _channel;
  final String _url;
  
  DeviceMonitorService(this._url);
  
  void connect() {
    _channel = WebSocketChannel.connect(Uri.parse(_url));
    
    _channel!.stream.listen(
      (message) {
        final data = jsonDecode(message);
        _handleMessage(data);
      },
      onError: (error) {
        print('WebSocket error: $error');
      },
      onDone: () {
        print('WebSocket connection closed');
        _attemptReconnect();
      },
    );
  }
  
  void subscribeToDevices(List<String> deviceSeries) {
    _send({
      'type': 'subscribe',
      'devices': deviceSeries,
    });
  }
  
  void subscribeToAll() {
    _send({'type': 'subscribe_all'});
  }
  
  void _send(Map<String, dynamic> message) {
    if (_channel != null) {
      _channel!.sink.add(jsonEncode(message));
    }
  }
  
  void _handleMessage(Map<String, dynamic> message) {
    switch (message['type']) {
      case 'initial_status':
        _onInitialStatus(message['devices']);
        break;
      case 'status_update':
        _onStatusUpdate(message['device']);
        break;
      case 'error':
        _onError(message);
        break;
    }
  }
  
  void _onInitialStatus(List<dynamic> devices) {
    // Actualizar estado inicial
  }
  
  void _onStatusUpdate(Map<String, dynamic> device) {
    // Actualizar dispositivo específico
  }
  
  void _onError(Map<String, dynamic> error) {
    print('Server error: ${error['message']}');
  }
  
  void _attemptReconnect() {
    // Lógica de reconexión
    Future.delayed(Duration(seconds: 5), () {
      connect();
    });
  }
  
  void dispose() {
    _channel?.sink.close();
  }
}
```

## 📊 Performance

### Optimizaciones de Base de Datos
```javascript
// Pool de conexiones optimizado
const poolConfig = {
  connectionLimit: 10,
  acquireTimeout: 60000,
  timeout: 60000,
  reconnect: true,
  idleTimeout: 300000,
  maxReusableConnections: 5
};

// Consultas preparadas
const preparedStatements = {
  getDeviceStatus: 'SELECT device_serie, status, last_update FROM traffic WHERE device_serie = ?',
  getAllDevices: 'SELECT device_serie, device_name, status, last_update FROM traffic WHERE active = 1',
  getDeviceChanges: 'SELECT * FROM traffic WHERE active = 1 AND UNIX_TIMESTAMP(last_update) > ?'
};
```

### Métricas de Performance
```javascript
// Monitoreo de performance
class PerformanceMonitor {
  constructor() {
    this.metrics = {
      messagesSent: 0,
      messagesReceived: 0,
      dbQueries: 0,
      dbQueryTime: 0,
      wsConnections: 0,
      errors: 0
    };
  }
  
  incrementMessagesSent() {
    this.metrics.messagesSent++;
  }
  
  incrementMessagesReceived() {
    this.metrics.messagesReceived++;
  }
  
  recordDbQuery(duration) {
    this.metrics.dbQueries++;
    this.metrics.dbQueryTime += duration;
  }
  
  getAverageDbQueryTime() {
    return this.metrics.dbQueries > 0 
      ? this.metrics.dbQueryTime / this.metrics.dbQueries 
      : 0;
  }
  
  getMetrics() {
    return {
      ...this.metrics,
      averageDbQueryTime: this.getAverageDbQueryTime(),
      uptime: process.uptime(),
      memory: process.memoryUsage()
    };
  }
}
```

## 🚀 Deployment

### Docker
```dockerfile
# Dockerfile
FROM node:18-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache curl bash

# Crear usuario no-root
RUN addgroup -g 1001 -S nodejs
RUN adduser -S nodejs -u 1001

WORKDIR /app

# Copiar archivos de dependencias
COPY package*.json ./
RUN npm ci --only=production && npm cache clean --force

# Copiar código fuente
COPY src/ ./src/
COPY scripts/ ./scripts/

# Crear directorio de logs
RUN mkdir -p logs && chown -R nodejs:nodejs logs

# Cambiar a usuario no-root
USER nodejs

EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:8080/health || exit 1

CMD ["npm", "start"]
```

### Docker Compose
```yaml
services:
  device-monitor:
    build: .
    ports:
      - "8080:8080"
    environment:
      - NODE_ENV=production
      - WS_PORT=8080
      - DB_HOST=mariadb
      - DB_PORT=3306
      - DB_USER=emqxuser
      - DB_PASSWORD=emqxpass
      - DB_NAME=emqx
      - MONITOR_INTERVAL=5000
      - LOG_LEVEL=info
    depends_on:
      - mariadb
    volumes:
      - ./logs:/app/logs
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
```

## 📝 Logging y Debugging

### Configuración de Logs
```javascript
// src/utils/logger.js
const winston = require('winston');
const path = require('path');

const logDir = process.env.LOG_DIR || './logs';

const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || 'info',
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.json()
  ),
  defaultMeta: { service: 'device-monitor' },
  transports: [
    new winston.transports.File({ 
      filename: path.join(logDir, 'error.log'), 
      level: 'error' 
    }),
    new winston.transports.File({ 
      filename: path.join(logDir, 'combined.log') 
    }),
    new winston.transports.File({ 
      filename: path.join(logDir, 'websocket.log'),
      level: 'debug',
      format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.printf(({ timestamp, level, message, ...meta }) => {
          return `${timestamp} [${level.toUpperCase()}]: ${message} ${Object.keys(meta).length ? JSON.stringify(meta) : ''}`;
        })
      )
    })
  ]
});

// Agregar console en desarrollo
if (process.env.NODE_ENV !== 'production') {
  logger.add(new winston.transports.Console({
    format: winston.format.combine(
      winston.format.colorize(),
      winston.format.simple()
    )
  }));
}

module.exports = logger;
```

### Debug Mode
```javascript
// Habilitar debug detallado
if (process.env.NODE_ENV === 'development') {
  process.env.DEBUG = 'smartlabs:*';
}

const debug = require('debug')('smartlabs:monitor');

// Uso en el código
debug('WebSocket connection established for client %s', clientId);
debug('Device status update: %o', device);
debug('Database query executed in %dms', duration);
```

---

**Versión**: 1.0  
**Última actualización**: Enero 2024  
**Mantenido por**: Equipo SmartLabs