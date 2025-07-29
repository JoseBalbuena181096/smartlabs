# Arquitectura SMARTLABS Flutter API

## Visión General

La SMARTLABS Flutter API es un sistema distribuido que actúa como intermediario entre una aplicación móvil Flutter y dispositivos IoT de laboratorio. La arquitectura sigue el patrón **MVC (Model-View-Controller)** adaptado para APIs REST, con una capa de servicios adicional para la lógica de negocio.

## Diagrama de Arquitectura

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Flutter App   │    │   Web Client    │    │  External APIs  │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          └──────────────────────┼──────────────────────┘
                                 │ HTTP/REST
                                 │
┌────────────────────────────────┼────────────────────────────────┐
│                    SMARTLABS Flutter API                        │
│                                │                                │
│  ┌─────────────────────────────┼─────────────────────────────┐  │
│  │              Express.js Server                            │  │
│  │                             │                             │  │
│  │  ┌─────────────┐  ┌─────────┼─────────┐  ┌─────────────┐  │  │
│  │  │ Middleware  │  │    Routes Layer    │  │ Controllers │  │  │
│  │  │             │  │                    │  │             │  │  │
│  │  │ • Auth      │  │ • userRoutes       │  │ • User      │  │  │
│  │  │ • CORS      │  │ • deviceRoutes     │  │ • Device    │  │  │
│  │  │ • Helmet    │  │ • prestamoRoutes   │  │ • Prestamo  │  │  │
│  │  │ • Rate Limit│  │ • internalRoutes   │  │             │  │  │
│  │  └─────────────┘  └────────────────────┘  └─────────────┘  │  │
│  └─────────────────────────────┼─────────────────────────────┘  │
│                                │                                │
│  ┌─────────────────────────────┼─────────────────────────────┐  │
│  │              Services Layer                               │  │
│  │                             │                             │  │
│  │  ┌─────────────┐  ┌─────────┼─────────┐  ┌─────────────┐  │  │
│  │  │ userService │  │ deviceService      │  │prestamoSvc  │  │  │
│  │  └─────────────┘  └────────────────────┘  └─────────────┘  │  │
│  │                                                           │  │
│  │  ┌─────────────────────────────────────────────────────┐  │  │
│  │  │           mqttListenerService                       │  │  │
│  │  │  • Escucha tópicos MQTT                            │  │  │
│  │  │  • Procesa mensajes de hardware                    │  │  │
│  │  │  • Sincroniza estado con servicios                │  │  │
│  │  └─────────────────────────────────────────────────────┘  │  │
│  └─────────────────────────────────────────────────────────┘  │
│                                │                                │
│  ┌─────────────────────────────┼─────────────────────────────┐  │
│  │              Config Layer                                 │  │
│  │                             │                             │  │
│  │  ┌─────────────┐  ┌─────────┼─────────┐                  │  │
│  │  │ database.js │  │    mqtt.js        │                  │  │
│  │  └─────────────┘  └───────────────────┘                  │  │
│  └─────────────────────────────────────────────────────────┘  │
└────────────────────────────────┼────────────────────────────────┘
                                 │
         ┌───────────────────────┼───────────────────────┐
         │                       │                       │
┌────────▼────────┐    ┌─────────▼─────────┐    ┌────────▼────────┐
│   MySQL DB      │    │   MQTT Broker     │    │   IoT Devices   │
│                 │    │   (EMQX)          │    │                 │
│ • users         │    │                   │    │ • ESP32/Arduino │
│ • devices       │    │ Topics:           │    │ • Sensors       │
│ • loans         │    │ • SMART*/loan_*   │    │ • Actuators     │
│ • access_logs   │    │ • SMART*/access_* │    │ • RFID Readers  │
│ • sensor_data   │    │ • SMART*/sensor_* │    │                 │
└─────────────────┘    └───────────────────┘    └─────────────────┘
```

## Componentes Principales

### 1. Capa de Presentación (Express.js Server)

#### Middleware Stack
- **Helmet**: Configuración de headers de seguridad HTTP
- **CORS**: Manejo de Cross-Origin Resource Sharing
- **Rate Limiting**: Protección contra ataques de fuerza bruta
- **Auth Middleware**: Autenticación por API Key
- **Error Handler**: Manejo centralizado de errores
- **Request Logger**: Logging de peticiones HTTP

#### Rutas (Routes)
```javascript
/api/users/*          → userRoutes.js
/api/devices/*        → deviceRoutes.js
/api/prestamos/*      → prestamoRoutes.js
/internal/*           → internalRoutes.js
```

### 2. Capa de Control (Controllers)

Los controladores manejan las peticiones HTTP y coordinan las respuestas:

- **UserController**: Gestión de usuarios y autenticación
- **DeviceController**: Control de dispositivos IoT
- **PrestamoController**: Lógica de préstamos de equipos

#### Responsabilidades:
- Validación de entrada con Joi
- Coordinación con servicios
- Formateo de respuestas HTTP
- Manejo de errores específicos

### 3. Capa de Servicios (Business Logic)

#### UserService
```javascript
• getUserByRegistration(registration)
• getUserByRFID(rfid)
• getUserHistory(registration, limit)
• validateUser(registration)
```

#### DeviceService
```javascript
• getDeviceBySerial(serial)
• controlDevice(serial, action)
• getDeviceHistory(serial, limit)
• getAllDevices()
• getDeviceStatus(serial)
```

#### PrestamoService
```javascript
• procesarPrestamo(registration, deviceSerie, action)
• prestarEquipo(registration, deviceSerie)
• simularDispositivoFisico(data)
• obtenerEstadoSesion()
```

#### MQTTListenerService
```javascript
• startListening()
• handleMQTTMessage(topic, message)
• handleLoanUserQuery(serial, rfid)
• handleLoanEquipmentQuery(serial, rfid)
• handleAccessQuery(serial, rfid)
• publishMQTTCommand(serial, user, command)
```

### 4. Capa de Configuración

#### DatabaseConfig
- Configuración de conexión MySQL
- Pool de conexiones
- Fallback automático
- Reconexión automática

#### MQTTConfig
- Configuración del cliente MQTT
- Manejo de suscripciones
- Publicación de mensajes
- Reconexión automática

## Patrones de Diseño Implementados

### 1. Singleton Pattern
```javascript
// Servicios implementados como singletons
module.exports = new UserService();
module.exports = new DatabaseConfig();
module.exports = new MQTTListenerService();
```

### 2. Factory Pattern
```javascript
// Creación de conexiones de base de datos
class DatabaseConfig {
    async connect() {
        // Factory para crear conexiones con fallback
    }
}
```

### 3. Observer Pattern
```javascript
// MQTT Listener como observer de mensajes
class MQTTListenerService {
    constructor() {
        this.messageHandlers = new Map();
    }
}
```

### 4. Strategy Pattern
```javascript
// Diferentes estrategias de manejo de mensajes MQTT
handleLoanUserQuery()
handleLoanEquipmentQuery()
handleAccessQuery()
handleScholarQuery()
```

## Flujo de Datos

### 1. Flujo HTTP Request/Response
```
Client Request → Middleware → Routes → Controller → Service → Database
                                                           ↓
Client Response ← Middleware ← Routes ← Controller ← Service ← Database
```

### 2. Flujo MQTT
```
IoT Device → MQTT Broker → MQTTListenerService → PrestamoService → Database
                                    ↓
IoT Device ← MQTT Broker ← MQTTListenerService ← Response Processing
```

### 3. Flujo de Control de Dispositivos
```
Flutter App → POST /api/devices/control → DeviceController → DeviceService
                                                                    ↓
                                                            MQTT Publish
                                                                    ↓
                                                            IoT Device
```

## Comunicación Entre Componentes

### 1. HTTP REST API
- **Protocolo**: HTTP/HTTPS
- **Formato**: JSON
- **Autenticación**: API Key
- **Validación**: Joi schemas

### 2. MQTT Communication
- **Protocolo**: MQTT v3.1.1/v5.0
- **QoS**: 0, 1, 2 según necesidad
- **Topics**: Patrón jerárquico `SMART*/category/action`
- **Payload**: JSON estructurado

### 3. Database Access
- **Protocolo**: MySQL Protocol
- **Pool**: Conexiones reutilizables
- **Transacciones**: Para operaciones críticas
- **Charset**: UTF8MB4

## Seguridad

### 1. Autenticación y Autorización
```javascript
// API Key Authentication
const authenticateApiKey = (req, res, next) => {
    const apiKey = req.headers['x-api-key'] || req.query.api_key;
    // Validación de API Key
};
```

### 2. Validación de Datos
```javascript
// Joi Validation Schemas
const schema = Joi.object({
    registration: Joi.string().required().min(1).max(50),
    device_serie: Joi.string().required().min(1).max(50),
    action: Joi.number().integer().valid(0, 1).required()
});
```

### 3. Seguridad HTTP
- **Helmet**: Headers de seguridad
- **CORS**: Control de origen
- **Rate Limiting**: Protección DDoS
- **Input Sanitization**: Prevención de inyecciones

## Escalabilidad

### 1. Horizontal Scaling
- **Load Balancer**: Nginx/HAProxy
- **Multiple Instances**: PM2 cluster mode
- **Database Sharding**: Por región/laboratorio

### 2. Vertical Scaling
- **Connection Pooling**: MySQL2 pools
- **Memory Management**: Garbage collection optimization
- **CPU Optimization**: Async/await patterns

### 3. Caching Strategy
- **Redis**: Para sesiones y datos frecuentes
- **Memory Cache**: Para configuraciones
- **Database Query Cache**: MySQL query cache

## Monitoreo y Observabilidad

### 1. Logging
```javascript
// Structured Logging
console.log('🔌 Conectando a base de datos principal...');
console.log('✅ Conexión exitosa a base de datos principal');
console.log('❌ Error en conexión:', error.message);
```

### 2. Health Checks
- **Database**: Ping periódico
- **MQTT**: Estado de conexión
- **Memory**: Uso de memoria
- **CPU**: Carga del sistema

### 3. Métricas
- **Request Rate**: Peticiones por segundo
- **Response Time**: Latencia promedio
- **Error Rate**: Porcentaje de errores
- **Device Status**: Estado de dispositivos IoT

## Configuración de Entornos

### 1. Development
```env
NODE_ENV=development
PORT=3000
DB_HOST=localhost
MQTT_HOST=localhost
```

### 2. Staging
```env
NODE_ENV=staging
PORT=3000
DB_HOST=staging-db.example.com
MQTT_HOST=staging-mqtt.example.com
```

### 3. Production
```env
NODE_ENV=production
PORT=80
DB_HOST=prod-db.example.com
MQTT_HOST=prod-mqtt.example.com
```

## Manejo de Errores

### 1. Error Hierarchy
```javascript
// Custom Error Classes
class ValidationError extends Error {}
class DatabaseError extends Error {}
class MQTTError extends Error {}
class AuthenticationError extends Error {}
```

### 2. Error Handling Strategy
- **Try-Catch**: En todas las operaciones async
- **Error Middleware**: Manejo centralizado
- **Graceful Degradation**: Fallbacks automáticos
- **Circuit Breaker**: Para servicios externos

## Testing Strategy

### 1. Unit Tests
- **Services**: Lógica de negocio
- **Controllers**: Manejo de requests
- **Utilities**: Funciones auxiliares

### 2. Integration Tests
- **Database**: Operaciones CRUD
- **MQTT**: Comunicación con broker
- **API Endpoints**: Flujo completo

### 3. E2E Tests
- **User Flows**: Casos de uso completos
- **Device Control**: Interacción con IoT
- **Error Scenarios**: Manejo de fallos

## Deployment

### 1. Containerization
```dockerfile
# Dockerfile example
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY src/ ./src/
EXPOSE 3000
CMD ["npm", "start"]
```

### 2. Orchestration
- **Docker Compose**: Desarrollo local
- **Kubernetes**: Producción
- **PM2**: Process management

### 3. CI/CD Pipeline
```yaml
# GitHub Actions example
name: Deploy SMARTLABS API
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup Node.js
        uses: actions/setup-node@v2
        with:
          node-version: '18'
      - run: npm ci
      - run: npm test
      - run: npm run build
      - name: Deploy to production
        run: ./deploy.sh
```

## Consideraciones de Performance

### 1. Database Optimization
- **Índices**: En campos de búsqueda frecuente
- **Query Optimization**: Consultas eficientes
- **Connection Pooling**: Reutilización de conexiones

### 2. MQTT Optimization
- **QoS Levels**: Según criticidad del mensaje
- **Topic Design**: Estructura jerárquica eficiente
- **Message Size**: Payloads optimizados

### 3. Memory Management
- **Garbage Collection**: Configuración optimizada
- **Memory Leaks**: Monitoreo y prevención
- **Buffer Management**: Para datos binarios

## Roadmap y Mejoras Futuras

### 1. Funcionalidades Pendientes
- [ ] Sistema de notificaciones push
- [ ] Dashboard de administración web
- [ ] API de reportes y analytics
- [ ] Integración con sistemas externos

### 2. Mejoras Técnicas
- [ ] Implementación de GraphQL
- [ ] Migración a TypeScript
- [ ] Implementación de microservicios
- [ ] Cache distribuido con Redis

### 3. Seguridad Avanzada
- [ ] OAuth 2.0 / JWT
- [ ] Encriptación end-to-end
- [ ] Audit logging
- [ ] Penetration testing

---

**Documento de Arquitectura v2.0**  
**SMARTLABS Team**  
**Fecha: 2025**