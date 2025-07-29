# Arquitectura del SMARTLABS Device Status Server

## Resumen Ejecutivo

El **SMARTLABS Device Status Server** es un servicio de monitoreo en tiempo real diseñado con una arquitectura orientada a eventos que utiliza WebSockets para proporcionar actualizaciones instantáneas del estado de dispositivos IoT. El sistema implementa patrones de diseño robustos para garantizar alta disponibilidad, escalabilidad y rendimiento.

## Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                    SMARTLABS Device Status Server               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────┐ │
│  │   WebSocket     │    │   HTTP Server   │    │   Config    │ │
│  │   Clients       │◄──►│   (Express)     │◄──►│   Manager   │ │
│  │                 │    │                 │    │             │ │
│  └─────────────────┘    └─────────────────┘    └─────────────┘ │
│           │                       │                     │       │
│           ▼                       ▼                     ▼       │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────┐ │
│  │   Connection    │    │   Device Status │    │   Database  │ │
│  │   Manager       │    │   Monitor       │    │   Manager   │ │
│  │                 │    │                 │    │             │ │
│  └─────────────────┘    └─────────────────┘    └─────────────┘ │
│           │                       │                     │       │
│           └───────────┬───────────┘                     │       │
│                       │                                 │       │
│                       ▼                                 ▼       │
│              ┌─────────────────┐                ┌─────────────┐ │
│              │   Event Bus     │                │   MySQL     │ │
│              │   (Internal)    │                │   Database  │ │
│              │                 │                │             │ │
│              └─────────────────┘                └─────────────┘ │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                        External Systems                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────┐ │
│  │   Web Clients   │    │   Mobile Apps   │    │   Dashboard │ │
│  │   (Browser)     │    │   (Flutter)     │    │   (Admin)   │ │
│  └─────────────────┘    └─────────────────┘    └─────────────┘ │
│           │                       │                     │       │
│           └───────────────────────┼─────────────────────┘       │
│                                   │                             │
│                          WebSocket Connection                   │
│                                   │                             │
│                                   ▼                             │
│                    ┌─────────────────────────┐                 │
│                    │   Device Status Server  │                 │
│                    │   (Port 3000)          │                 │
│                    └─────────────────────────┘                 │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Componentes Principales

### 1. WebSocket Server

**Responsabilidad**: Gestión de conexiones en tiempo real con clientes

**Características**:
- Servidor WebSocket basado en la librería `ws`
- Soporte para múltiples conexiones concurrentes
- Gestión de suscripciones por dispositivo
- Broadcast selectivo de actualizaciones

**Configuración**:
```javascript
websocket: {
    port: 3000,
    host: '0.0.0.0',
    pingInterval: 30000,
    pingTimeout: 5000,
    maxConnections: 100
}
```

### 2. Device Status Monitor

**Responsabilidad**: Monitoreo continuo del estado de dispositivos

**Características**:
- Polling periódico a la base de datos
- Detección de cambios de estado
- Caché en memoria para optimización
- Notificaciones automáticas a clientes

**Flujo de Trabajo**:
1. Consulta periódica cada 5 segundos (configurable)
2. Comparación con estado anterior en caché
3. Detección de cambios
4. Broadcast a clientes suscritos

### 3. Database Manager

**Responsabilidad**: Gestión de conexiones y consultas a MySQL

**Características**:
- Conexión principal y fallback automático
- Pool de conexiones para optimización
- Reconexión automática en caso de fallo
- Consultas optimizadas con índices

**Configuración de Fallback**:
```javascript
primary: {
    host: "192.168.0.100",
    port: 4000,
    database: "emqx"
},
fallback: {
    host: "localhost",
    port: 3306,
    database: "emqx"
}
```

### 4. Configuration Manager

**Responsabilidad**: Gestión centralizada de configuraciones

**Módulos**:
- `database.js`: Configuración de conexiones a BD
- `device-status.js`: Configuración del servidor WebSocket

## Patrones de Diseño Implementados

### 1. Observer Pattern

**Implementación**: Sistema de suscripciones WebSocket

```javascript
// Los clientes se suscriben a dispositivos específicos
clients.set(ws, deviceIds);

// Notificación automática cuando cambia el estado
broadcastDeviceStatus(deviceId, status);
```

### 2. Singleton Pattern

**Implementación**: Gestión de conexión a base de datos

```javascript
let dbConnection = null;

async function connectToDatabase() {
    if (!dbConnection) {
        dbConnection = await mysql.createConnection(config);
    }
    return dbConnection;
}
```

### 3. Strategy Pattern

**Implementación**: Estrategia de conexión a base de datos (principal/fallback)

```javascript
try {
    connection = await mysql.createConnection(primaryDbConfig);
} catch (error) {
    connection = await mysql.createConnection(fallbackDbConfig);
}
```

### 4. Factory Pattern

**Implementación**: Creación de objetos de estado de dispositivo

```javascript
const createDeviceStatus = (device) => ({
    device: device.traffic_device,
    state: device.traffic_state == 1 ? 'on' : 'off',
    last_activity: device.traffic_date,
    user: device.hab_name,
    timestamp: new Date()
});
```

## Flujo de Datos

### 1. Flujo de Monitoreo

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Timer     │───►│   Query DB  │───►│   Compare   │───►│  Broadcast  │
│ (5 seconds) │    │   Status    │    │   Changes   │    │  to Clients │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
       ▲                                      │
       │                                      ▼
       │                              ┌─────────────┐
       └──────────────────────────────│ Update Cache│
                                      └─────────────┘
```

### 2. Flujo de Conexión Cliente

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Client    │───►│  WebSocket  │───►│  Subscribe  │───►│  Receive    │
│  Connects   │    │ Handshake   │    │ to Devices  │    │  Updates    │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
                           │                                      ▲
                           ▼                                      │
                   ┌─────────────┐                        ┌─────────────┐
                   │   Welcome   │                        │   Status    │
                   │   Message   │                        │  Changes    │
                   └─────────────┘                        └─────────────┘
```

### 3. Flujo de Consulta Específica

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Client    │───►│   Check     │───►│   Query     │───►│   Return    │
│  Requests   │    │   Cache     │    │  Database   │    │   Status    │
│   Status    │    │             │    │             │    │             │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
                           │                                      ▲
                           ▼                                      │
                   ┌─────────────┐                        ┌─────────────┐
                   │   Cache     │                        │   Update    │
                   │    Hit      │                        │   Cache     │
                   └─────────────┘                        └─────────────┘
```

## Protocolos de Comunicación

### 1. WebSocket Protocol

**Puerto**: 3000 (configurable)
**Formato**: JSON
**Tipos de Mensaje**:

#### Cliente → Servidor

```json
// Suscripción
{
    "type": "subscribe",
    "devices": ["device001", "device002"]
}

// Consulta específica
{
    "type": "get_status",
    "device": "device001"
}
```

#### Servidor → Cliente

```json
// Bienvenida
{
    "type": "welcome",
    "message": "Conectado al servidor",
    "devices": 25
}

// Estado del dispositivo
{
    "type": "device_status",
    "device": "device001",
    "data": {
        "device": "device001",
        "state": "on",
        "last_activity": "2025-01-08T10:30:00.000Z",
        "user": "Juan Pérez",
        "user_registration": "2021001",
        "timestamp": "2025-01-08T10:30:05.123Z"
    }
}
```

### 2. Database Protocol

**Tipo**: MySQL
**Driver**: mysql2/promise
**Pool**: Configurado para optimización

**Consulta Principal**:
```sql
SELECT t.traffic_device, t.traffic_state, t.traffic_date, 
       h.hab_name, h.hab_registration, h.hab_email
FROM traffic t
LEFT JOIN habintants h ON t.traffic_hab_id = h.hab_id
WHERE (t.traffic_device, t.traffic_date) IN (
    SELECT traffic_device, MAX(traffic_date) 
    FROM traffic 
    GROUP BY traffic_device
)
```

## Seguridad

### 1. Conexión a Base de Datos

- **Credenciales**: Configuradas en archivos de configuración
- **Timeout**: Configurado para evitar conexiones colgadas
- **Pool Limits**: Límite de conexiones concurrentes

### 2. WebSocket Security

- **Connection Limits**: Máximo de conexiones configurables
- **Message Validation**: Validación JSON de mensajes entrantes
- **Error Handling**: Manejo seguro de errores sin exposición de información

### 3. Data Validation

```javascript
try {
    const data = JSON.parse(message);
    // Validación de estructura
    if (data.type && allowedTypes.includes(data.type)) {
        // Procesar mensaje
    }
} catch (e) {
    console.error('Error procesando mensaje:', e);
}
```

## Escalabilidad

### 1. Escalabilidad Horizontal

**Estrategias**:
- Load balancer para múltiples instancias
- Sticky sessions para WebSocket
- Base de datos compartida

**Configuración Sugerida**:
```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Instance  │    │   Instance  │    │   Instance  │
│      1      │    │      2      │    │      3      │
│  (Port 3000)│    │  (Port 3001)│    │  (Port 3002)│
└─────────────┘    └─────────────┘    └─────────────┘
       │                   │                   │
       └───────────────────┼───────────────────┘
                           │
                   ┌─────────────┐
                   │ Load Balancer│
                   │  (Nginx)    │
                   └─────────────┘
```

### 2. Escalabilidad Vertical

**Optimizaciones**:
- Aumento de memoria para caché
- CPU adicional para procesamiento
- Optimización de consultas SQL

### 3. Optimización de Performance

**Caché en Memoria**:
```javascript
const deviceStatus = {}; // Cache global

// Evitar consultas innecesarias
if (deviceStatus[deviceId]) {
    return deviceStatus[deviceId];
}
```

**Batch Processing**:
```javascript
monitoring: {
    batchSize: 50, // Procesar 50 dispositivos por lote
    pollingInterval: 5000 // Intervalo optimizado
}
```

## Monitoreo y Observabilidad

### 1. Logging

**Niveles**:
- **Debug**: Información detallada de desarrollo
- **Info**: Información general de operación
- **Warn**: Advertencias no críticas
- **Error**: Errores que requieren atención

**Ejemplos**:
```javascript
console.log('🚀 Servidor WebSocket iniciado en puerto 3000');
console.log('📱 Dispositivo device001 actualizado: on');
console.warn('⚠️ Error conectando a la base de datos principal');
console.error('❌ Error consultando estado de dispositivos');
```

### 2. Health Checks

**Endpoint de Salud** (sugerido):
```javascript
app.get('/health', (req, res) => {
    res.json({
        status: 'healthy',
        uptime: process.uptime(),
        connections: wss.clients.size,
        database: dbConnection ? 'connected' : 'disconnected',
        timestamp: new Date().toISOString()
    });
});
```

### 3. Métricas

**Métricas Clave**:
- Número de conexiones WebSocket activas
- Tiempo de respuesta de consultas a BD
- Frecuencia de actualizaciones de dispositivos
- Errores de conexión

## Configuración de Entorno

### 1. Variables de Entorno

```bash
# Servidor
PORT=3000
NODE_ENV=production

# Base de datos principal
DB_PRIMARY_HOST=192.168.0.100
DB_PRIMARY_PORT=4000
DB_PRIMARY_USER=root
DB_PRIMARY_PASSWORD=emqxpass
DB_PRIMARY_DATABASE=emqx

# Base de datos fallback
DB_FALLBACK_HOST=localhost
DB_FALLBACK_PORT=3306
DB_FALLBACK_USER=root
DB_FALLBACK_PASSWORD=
DB_FALLBACK_DATABASE=emqx
```

### 2. Configuración por Entorno

**Desarrollo**:
```javascript
{
    logging: { level: 'debug' },
    monitoring: { pollingInterval: 2000 },
    websocket: { maxConnections: 10 }
}
```

**Producción**:
```javascript
{
    logging: { level: 'info' },
    monitoring: { pollingInterval: 5000 },
    websocket: { maxConnections: 100 }
}
```

## Manejo de Errores

### 1. Estrategias de Recuperación

**Reconexión a Base de Datos**:
```javascript
process.on('uncaughtException', async (error) => {
    console.error('Error no capturado:', error);
    if (error.code === 'PROTOCOL_CONNECTION_LOST') {
        dbConnection = await connectToDatabase();
    }
});
```

**Fallback Automático**:
```javascript
try {
    connection = await mysql.createConnection(primaryDbConfig);
} catch (error) {
    console.warn('Usando base de datos de fallback');
    connection = await mysql.createConnection(fallbackDbConfig);
}
```

### 2. Graceful Shutdown

```javascript
process.on('SIGINT', () => {
    console.log('Cerrando servidor...');
    
    // Cerrar conexiones WebSocket
    wss.clients.forEach(client => {
        client.close(1000, 'Servidor cerrando');
    });
    
    // Cerrar conexión a BD
    if (dbConnection) {
        dbConnection.end();
    }
    
    // Cerrar servidor HTTP
    server.close(() => {
        console.log('Servidor cerrado');
        process.exit(0);
    });
});
```

## Estrategia de Testing

### 1. Unit Tests (Sugerido)

```javascript
// tests/unit/device-monitor.test.js
describe('Device Monitor', () => {
    test('should detect state changes', () => {
        const oldState = { device: 'dev001', state: 'off' };
        const newState = { device: 'dev001', state: 'on' };
        
        expect(hasStateChanged(oldState, newState)).toBe(true);
    });
});
```

### 2. Integration Tests (Sugerido)

```javascript
// tests/integration/websocket.test.js
describe('WebSocket Server', () => {
    test('should accept connections and send welcome message', (done) => {
        const ws = new WebSocket('ws://localhost:3000');
        
        ws.on('message', (data) => {
            const message = JSON.parse(data);
            expect(message.type).toBe('welcome');
            done();
        });
    });
});
```

### 3. Load Tests (Sugerido)

```javascript
// tests/load/websocket-load.test.js
describe('WebSocket Load Test', () => {
    test('should handle 100 concurrent connections', async () => {
        const connections = [];
        
        for (let i = 0; i < 100; i++) {
            connections.push(new WebSocket('ws://localhost:3000'));
        }
        
        // Verificar que todas las conexiones se establezcan
        await Promise.all(connections.map(ws => 
            new Promise(resolve => ws.on('open', resolve))
        ));
        
        expect(connections.length).toBe(100);
    });
});
```

## Deployment

### 1. Containerización

**Dockerfile**:
```dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY src/ ./src/
COPY scripts/ ./scripts/

EXPOSE 3000

CMD ["npm", "start"]
```

**docker-compose.yml**:
```yaml
version: '3.8'
services:
  device-status-server:
    build: .
    ports:
      - "3000:3000"
    environment:
      - NODE_ENV=production
      - PORT=3000
    depends_on:
      - mysql
    restart: unless-stopped

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: password
      MYSQL_DATABASE: emqx
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```

### 2. Orquestación

**Kubernetes Deployment**:
```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: device-status-server
spec:
  replicas: 3
  selector:
    matchLabels:
      app: device-status-server
  template:
    metadata:
      labels:
        app: device-status-server
    spec:
      containers:
      - name: device-status-server
        image: smartlabs/device-status-server:latest
        ports:
        - containerPort: 3000
        env:
        - name: NODE_ENV
          value: "production"
        - name: PORT
          value: "3000"
```

### 3. CI/CD Pipeline

**GitHub Actions**:
```yaml
name: Deploy Device Status Server

on:
  push:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    - uses: actions/setup-node@v2
      with:
        node-version: '18'
    - run: npm ci
    - run: npm test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    - name: Build and push Docker image
      run: |
        docker build -t smartlabs/device-status-server:${{ github.sha }} .
        docker push smartlabs/device-status-server:${{ github.sha }}
    - name: Deploy to production
      run: |
        kubectl set image deployment/device-status-server \
          device-status-server=smartlabs/device-status-server:${{ github.sha }}
```

## Consideraciones de Performance

### 1. Optimización de Memoria

```javascript
// Limpieza periódica de caché
setInterval(() => {
    const now = Date.now();
    Object.keys(deviceStatus).forEach(deviceId => {
        const lastUpdate = new Date(deviceStatus[deviceId].timestamp).getTime();
        if (now - lastUpdate > 3600000) { // 1 hora
            delete deviceStatus[deviceId];
        }
    });
}, 300000); // Cada 5 minutos
```

### 2. Optimización de Red

```javascript
// Compresión de mensajes WebSocket
const zlib = require('zlib');

function compressMessage(data) {
    return zlib.gzipSync(JSON.stringify(data));
}

function decompressMessage(buffer) {
    return JSON.parse(zlib.gunzipSync(buffer).toString());
}
```

### 3. Optimización de Base de Datos

```sql
-- Índices optimizados
CREATE INDEX idx_traffic_device_date ON traffic(traffic_device, traffic_date DESC);
CREATE INDEX idx_traffic_state ON traffic(traffic_state);
CREATE INDEX idx_habintants_id ON habintants(hab_id);

-- Particionamiento por fecha (sugerido)
ALTER TABLE traffic PARTITION BY RANGE (YEAR(traffic_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

## Roadmap y Mejoras Futuras

### Fase 1: Optimización (Q1 2025)
- [ ] Implementación de Redis para caché distribuido
- [ ] Métricas avanzadas con Prometheus
- [ ] Health checks automáticos
- [ ] Logging estructurado con Winston

### Fase 2: Escalabilidad (Q2 2025)
- [ ] Clustering para múltiples procesos
- [ ] Load balancing con Nginx
- [ ] Implementación de Circuit Breaker
- [ ] Rate limiting por cliente

### Fase 3: Funcionalidades Avanzadas (Q3 2025)
- [ ] Autenticación y autorización
- [ ] Filtros avanzados de dispositivos
- [ ] Alertas y notificaciones
- [ ] Dashboard de administración

### Fase 4: Inteligencia (Q4 2025)
- [ ] Predicción de fallos de dispositivos
- [ ] Análisis de patrones de uso
- [ ] Optimización automática de polling
- [ ] Machine Learning para detección de anomalías

---

**Versión de Arquitectura**: 2.0.0  
**Fecha**: Enero 2025  
**Mantenido por**: Equipo SMARTLABS  
**Próxima Revisión**: Abril 2025