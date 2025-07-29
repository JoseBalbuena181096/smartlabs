# SmartLabs Flutter API - Documentación Técnica

## 📋 Índice

1. [Arquitectura](#arquitectura)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Configuración](#configuración)
4. [Endpoints de la API](#endpoints-de-la-api)
5. [Servicios](#servicios)
6. [Middleware](#middleware)
7. [Base de Datos](#base-de-datos)
8. [MQTT](#mqtt)
9. [Autenticación y Seguridad](#autenticación-y-seguridad)
10. [Ejemplos de Uso](#ejemplos-de-uso)
11. [Testing](#testing)
12. [Deployment](#deployment)

## 🏗️ Arquitectura

### Patrón de Diseño
La API sigue una arquitectura **MVC (Model-View-Controller)** con separación de responsabilidades:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│    Routes       │───▶│   Controllers   │───▶│    Services     │
│  (Endpoints)    │    │   (Logic)       │    │  (Business)     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Middleware    │    │    Models       │    │   Database      │
│ (Validation)    │    │   (Entities)    │    │   (MySQL)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Flujo de Datos
1. **Request** → Middleware (validación, autenticación)
2. **Route** → Controller específico
3. **Controller** → Service (lógica de negocio)
4. **Service** → Database/MQTT
5. **Response** ← JSON estructurado

## 📁 Estructura del Proyecto

```
flutter-api/
├── src/
│   ├── config/
│   │   ├── database.js          # Configuración MySQL
│   │   └── mqtt.js              # Configuración MQTT
│   ├── controllers/
│   │   ├── deviceController.js  # Control de dispositivos
│   │   ├── userController.js    # Gestión de usuarios
│   │   ├── prestamoController.js# Gestión de préstamos
│   │   └── mqttController.js    # Estado MQTT
│   ├── services/
│   │   ├── deviceService.js     # Lógica de dispositivos
│   │   ├── prestamoService.js   # Lógica de préstamos
│   │   └── mqttListenerService.js# Listener MQTT
│   ├── routes/
│   │   ├── deviceRoutes.js      # Rutas de dispositivos
│   │   ├── userRoutes.js        # Rutas de usuarios
│   │   ├── prestamoRoutes.js    # Rutas de préstamos
│   │   ├── mqttRoutes.js        # Rutas MQTT
│   │   └── internalRoutes.js    # Rutas internas
│   ├── middleware/
│   │   ├── auth.js              # Autenticación
│   │   ├── validation.js        # Validación de datos
│   │   └── rateLimiting.js      # Limitación de requests
│   └── utils/
│       ├── logger.js            # Sistema de logs
│       └── helpers.js           # Funciones auxiliares
├── logs/                        # Archivos de log
├── package.json                 # Dependencias
├── .env                         # Variables de entorno
└── app.js                       # Punto de entrada
```

## ⚙️ Configuración

### Variables de Entorno (.env)
```bash
# Servidor
PORT=3000
NODE_ENV=production

# Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_USER=emqxuser
DB_PASSWORD=emqxpass
DB_NAME=emqx

# MQTT
MQTT_HOST=localhost
MQTT_PORT=1883
MQTT_USERNAME=smartlabs
MQTT_PASSWORD=smartlabs123

# Seguridad
JWT_SECRET=your-secret-key
RATE_LIMIT=100
```

### Configuración de Base de Datos
```javascript
// src/config/database.js
const mysql = require('mysql2/promise');

const dbConfig = {
  host: process.env.DB_HOST,
  port: process.env.DB_PORT,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};
```

## 🔗 Endpoints de la API

### Health Check
```http
GET /health
```
**Respuesta:**
```json
{
  "status": "OK",
  "timestamp": "2024-01-15T10:30:00Z",
  "uptime": 3600,
  "version": "1.0.0"
}
```

### Información de la API
```http
GET /api
```
**Respuesta:**
```json
{
  "name": "SmartLabs Flutter API",
  "version": "1.0.0",
  "description": "API REST para aplicación Flutter de control de laboratorios",
  "endpoints": [
    "/api/devices",
    "/api/users",
    "/api/prestamo",
    "/api/mqtt"
  ]
}
```

### Control de Dispositivos

#### Controlar Dispositivo
```http
POST /api/devices/control
Content-Type: application/json

{
  "registration": "A12345678",
  "device_serie": "DEV001",
  "action": "on"
}
```

**Parámetros:**
- `registration` (string): Matrícula del usuario
- `device_serie` (string): Serie del dispositivo
- `action` (string): Acción a realizar (`on`, `off`, `toggle`)

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "message": "Dispositivo controlado exitosamente",
  "data": {
    "device_serie": "DEV001",
    "action": "on",
    "timestamp": "2024-01-15T10:30:00Z",
    "user_registration": "A12345678"
  }
}
```

**Respuesta de Error (400):**
```json
{
  "success": false,
  "error": "Usuario no autorizado para este dispositivo",
  "code": "UNAUTHORIZED_DEVICE"
}
```

#### Obtener Estado de Dispositivos
```http
GET /api/devices/status/:registration
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "user_registration": "A12345678",
    "devices": [
      {
        "device_serie": "DEV001",
        "status": "on",
        "last_update": "2024-01-15T10:30:00Z"
      }
    ]
  }
}
```

### Gestión de Usuarios

#### Obtener Usuario por Matrícula
```http
GET /api/users/registration/:id
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "registration": "A12345678",
    "name": "Juan Pérez",
    "email": "juan.perez@universidad.edu",
    "devices": [
      {
        "device_serie": "DEV001",
        "device_name": "Laboratorio A - Mesa 1"
      }
    ]
  }
}
```

#### Obtener Dispositivos del Usuario
```http
GET /api/users/:registration/devices
```

### Gestión de Préstamos

#### Crear/Actualizar Préstamo
```http
POST /api/prestamo/control/
Content-Type: application/json

{
  "registration": "A12345678",
  "equipment_id": "EQ001",
  "action": "borrow"
}
```

**Parámetros:**
- `registration` (string): Matrícula del usuario
- `equipment_id` (string): ID del equipo
- `action` (string): Acción (`borrow`, `return`)

**Respuesta:**
```json
{
  "success": true,
  "message": "Préstamo registrado exitosamente",
  "data": {
    "loan_id": 123,
    "equipment_id": "EQ001",
    "user_registration": "A12345678",
    "borrowed_at": "2024-01-15T10:30:00Z",
    "due_date": "2024-01-22T10:30:00Z"
  }
}
```

### Estado MQTT

#### Verificar Estado del Listener
```http
GET /api/mqtt/status
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "mqtt_connected": true,
    "listener_active": true,
    "last_message": "2024-01-15T10:29:45Z",
    "subscribed_topics": [
      "smartlabs/devices/+/status",
      "smartlabs/devices/+/control"
    ]
  }
}
```

## 🔧 Servicios

### Device Service
```javascript
// src/services/deviceService.js
class DeviceService {
  async controlDevice(registration, deviceSerie, action) {
    // 1. Validar usuario y dispositivo
    const user = await this.validateUser(registration);
    const device = await this.validateDevice(deviceSerie);
    
    // 2. Verificar permisos
    if (!this.hasPermission(user, device)) {
      throw new Error('Usuario no autorizado');
    }
    
    // 3. Enviar comando MQTT
    await this.sendMQTTCommand(deviceSerie, action);
    
    // 4. Registrar en base de datos
    await this.logDeviceAction(user.id, device.id, action);
    
    return { success: true, timestamp: new Date() };
  }
}
```

### Prestamo Service
```javascript
// src/services/prestamoService.js
class PrestamoService {
  async createLoan(registration, equipmentId) {
    const connection = await db.getConnection();
    
    try {
      await connection.beginTransaction();
      
      // 1. Verificar disponibilidad
      const equipment = await this.checkAvailability(equipmentId);
      
      // 2. Crear préstamo
      const loanId = await this.insertLoan(registration, equipmentId);
      
      // 3. Actualizar estado del equipo
      await this.updateEquipmentStatus(equipmentId, 'borrowed');
      
      await connection.commit();
      return { loanId, success: true };
    } catch (error) {
      await connection.rollback();
      throw error;
    } finally {
      connection.release();
    }
  }
}
```

## 🛡️ Middleware

### Validación de Datos
```javascript
// src/middleware/validation.js
const Joi = require('joi');

const deviceControlSchema = Joi.object({
  registration: Joi.string().pattern(/^[A-Z]\d{8}$/).required(),
  device_serie: Joi.string().min(3).max(20).required(),
  action: Joi.string().valid('on', 'off', 'toggle').required()
});

const validateDeviceControl = (req, res, next) => {
  const { error } = deviceControlSchema.validate(req.body);
  if (error) {
    return res.status(400).json({
      success: false,
      error: error.details[0].message
    });
  }
  next();
};
```

### Rate Limiting
```javascript
// src/middleware/rateLimiting.js
const rateLimit = require('express-rate-limit');

const apiLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutos
  max: 100, // máximo 100 requests por ventana
  message: {
    success: false,
    error: 'Demasiadas solicitudes, intente más tarde'
  }
});
```

## 💾 Base de Datos

### Tablas Principales

#### Usuarios (habintants)
```sql
CREATE TABLE habintants (
  id INT PRIMARY KEY AUTO_INCREMENT,
  registration VARCHAR(10) UNIQUE NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Dispositivos (traffic)
```sql
CREATE TABLE traffic (
  id INT PRIMARY KEY AUTO_INCREMENT,
  device_serie VARCHAR(20) UNIQUE NOT NULL,
  device_name VARCHAR(100),
  status ENUM('on', 'off') DEFAULT 'off',
  last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Préstamos (loans)
```sql
CREATE TABLE loans (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_registration VARCHAR(10) NOT NULL,
  equipment_id VARCHAR(20) NOT NULL,
  borrowed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  returned_at TIMESTAMP NULL,
  due_date TIMESTAMP NOT NULL,
  status ENUM('active', 'returned', 'overdue') DEFAULT 'active'
);
```

### Consultas Comunes

```javascript
// Obtener dispositivos del usuario
const getUserDevices = async (registration) => {
  const query = `
    SELECT t.device_serie, t.device_name, t.status
    FROM traffic t
    JOIN user_devices ud ON t.device_serie = ud.device_serie
    JOIN habintants h ON ud.user_id = h.id
    WHERE h.registration = ?
  `;
  return await db.execute(query, [registration]);
};

// Verificar préstamos activos
const getActiveLoans = async (registration) => {
  const query = `
    SELECT * FROM loans
    WHERE user_registration = ? AND status = 'active'
  `;
  return await db.execute(query, [registration]);
};
```

## 📡 MQTT

### Configuración
```javascript
// src/config/mqtt.js
const mqtt = require('mqtt');

const mqttConfig = {
  host: process.env.MQTT_HOST,
  port: process.env.MQTT_PORT,
  username: process.env.MQTT_USERNAME,
  password: process.env.MQTT_PASSWORD,
  keepalive: 60,
  reconnectPeriod: 1000
};

const client = mqtt.connect(mqttConfig);
```

### Topics

#### Estructura de Topics
```
smartlabs/
├── devices/
│   ├── {device_serie}/
│   │   ├── control          # Comandos de control
│   │   ├── status           # Estado del dispositivo
│   │   └── response         # Respuestas del dispositivo
│   └── broadcast/           # Mensajes broadcast
└── system/
    ├── health               # Health checks
    └── logs                 # Logs del sistema
```

#### Mensajes de Control
```javascript
// Enviar comando de control
const sendDeviceCommand = (deviceSerie, action) => {
  const topic = `smartlabs/devices/${deviceSerie}/control`;
  const message = {
    action: action,
    timestamp: new Date().toISOString(),
    source: 'flutter-api'
  };
  
  client.publish(topic, JSON.stringify(message));
};

// Escuchar respuestas
client.subscribe('smartlabs/devices/+/response');
client.on('message', (topic, message) => {
  const deviceSerie = topic.split('/')[2];
  const data = JSON.parse(message.toString());
  
  // Procesar respuesta del dispositivo
  handleDeviceResponse(deviceSerie, data);
});
```

## 🔐 Autenticación y Seguridad

### Validación de Usuario
```javascript
const validateUser = async (registration) => {
  const query = 'SELECT * FROM habintants WHERE registration = ?';
  const [rows] = await db.execute(query, [registration]);
  
  if (rows.length === 0) {
    throw new Error('Usuario no encontrado');
  }
  
  return rows[0];
};
```

### Autorización de Dispositivos
```javascript
const checkDevicePermission = async (userId, deviceSerie) => {
  const query = `
    SELECT 1 FROM user_devices ud
    JOIN traffic t ON ud.device_serie = t.device_serie
    WHERE ud.user_id = ? AND t.device_serie = ?
  `;
  
  const [rows] = await db.execute(query, [userId, deviceSerie]);
  return rows.length > 0;
};
```

### Headers de Seguridad
```javascript
// Helmet para headers de seguridad
app.use(helmet({
  contentSecurityPolicy: {
    directives: {
      defaultSrc: ["'self'"],
      styleSrc: ["'self'", "'unsafe-inline'"],
      scriptSrc: ["'self'"],
      imgSrc: ["'self'", "data:", "https:"]
    }
  },
  hsts: {
    maxAge: 31536000,
    includeSubDomains: true,
    preload: true
  }
}));
```

## 📝 Ejemplos de Uso

### Cliente Flutter
```dart
// Controlar dispositivo
Future<void> controlDevice(String registration, String deviceSerie, String action) async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/devices/control'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'registration': registration,
      'device_serie': deviceSerie,
      'action': action,
    }),
  );
  
  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    if (data['success']) {
      print('Dispositivo controlado exitosamente');
    }
  }
}

// Obtener información del usuario
Future<User> getUserInfo(String registration) async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/users/registration/$registration'),
  );
  
  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return User.fromJson(data['data']);
  }
  
  throw Exception('Error al obtener usuario');
}
```

### Cliente JavaScript
```javascript
// Clase para interactuar con la API
class SmartLabsAPI {
  constructor(baseUrl) {
    this.baseUrl = baseUrl;
  }
  
  async controlDevice(registration, deviceSerie, action) {
    const response = await fetch(`${this.baseUrl}/api/devices/control`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        registration,
        device_serie: deviceSerie,
        action
      })
    });
    
    return await response.json();
  }
  
  async getUserDevices(registration) {
    const response = await fetch(`${this.baseUrl}/api/users/${registration}/devices`);
    return await response.json();
  }
}

// Uso
const api = new SmartLabsAPI('http://localhost:3000');
api.controlDevice('A12345678', 'DEV001', 'on')
  .then(result => console.log(result))
  .catch(error => console.error(error));
```

## 🧪 Testing

### Tests Unitarios
```javascript
// tests/services/deviceService.test.js
const DeviceService = require('../../src/services/deviceService');

describe('DeviceService', () => {
  let deviceService;
  
  beforeEach(() => {
    deviceService = new DeviceService();
  });
  
  test('should control device successfully', async () => {
    const result = await deviceService.controlDevice('A12345678', 'DEV001', 'on');
    expect(result.success).toBe(true);
  });
  
  test('should throw error for unauthorized user', async () => {
    await expect(
      deviceService.controlDevice('INVALID', 'DEV001', 'on')
    ).rejects.toThrow('Usuario no autorizado');
  });
});
```

### Tests de Integración
```javascript
// tests/integration/api.test.js
const request = require('supertest');
const app = require('../../app');

describe('API Integration Tests', () => {
  test('POST /api/devices/control', async () => {
    const response = await request(app)
      .post('/api/devices/control')
      .send({
        registration: 'A12345678',
        device_serie: 'DEV001',
        action: 'on'
      })
      .expect(200);
    
    expect(response.body.success).toBe(true);
  });
});
```

## 🚀 Deployment

### Docker
```dockerfile
# Dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY src/ ./src/
COPY app.js ./

EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:3000/health || exit 1

CMD ["npm", "start"]
```

### Variables de Producción
```bash
# .env.production
NODE_ENV=production
PORT=3000

# Base de datos de producción
DB_HOST=db.smartlabs.com
DB_PORT=3306
DB_USER=api_user
DB_PASSWORD=secure_password
DB_NAME=smartlabs_prod

# MQTT de producción
MQTT_HOST=mqtt.smartlabs.com
MQTT_PORT=1883
MQTT_USERNAME=api_client
MQTT_PASSWORD=mqtt_secure_password

# Seguridad
JWT_SECRET=super-secure-jwt-secret-for-production
RATE_LIMIT=1000
```

### Monitoreo
```javascript
// src/utils/monitoring.js
const prometheus = require('prom-client');

// Métricas personalizadas
const httpRequestDuration = new prometheus.Histogram({
  name: 'http_request_duration_seconds',
  help: 'Duration of HTTP requests in seconds',
  labelNames: ['method', 'route', 'status']
});

const deviceControlCounter = new prometheus.Counter({
  name: 'device_control_total',
  help: 'Total number of device control requests',
  labelNames: ['action', 'status']
});

// Endpoint de métricas
app.get('/metrics', async (req, res) => {
  res.set('Content-Type', prometheus.register.contentType);
  res.end(await prometheus.register.metrics());
});
```

## 📊 Logs y Debugging

### Configuración de Logs
```javascript
// src/utils/logger.js
const winston = require('winston');

const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || 'info',
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.json()
  ),
  transports: [
    new winston.transports.File({ filename: 'logs/error.log', level: 'error' }),
    new winston.transports.File({ filename: 'logs/combined.log' }),
    new winston.transports.Console({
      format: winston.format.simple()
    })
  ]
});
```

### Debugging
```javascript
// Habilitar debug en desarrollo
if (process.env.NODE_ENV === 'development') {
  process.env.DEBUG = 'smartlabs:*';
}

const debug = require('debug')('smartlabs:api');

// Uso en controladores
const deviceController = {
  async control(req, res) {
    debug('Control request received:', req.body);
    
    try {
      const result = await deviceService.controlDevice(
        req.body.registration,
        req.body.device_serie,
        req.body.action
      );
      
      debug('Control successful:', result);
      res.json({ success: true, data: result });
    } catch (error) {
      debug('Control error:', error);
      res.status(400).json({ success: false, error: error.message });
    }
  }
};
```

---

**Versión**: 1.0  
**Última actualización**: Enero 2024  
**Mantenido por**: Equipo SmartLabs