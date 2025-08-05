# 🔧 Solución de Problemas: API se Bloquea/Congela

## 🚨 Problema Identificado

La API de SmartLabs Flutter se bloquea ocasionalmente y solo responde después de:
- Reiniciar el contenedor Docker
- Hacer peticiones desde Postman
- Esperar un tiempo prolongado

## 🔍 Análisis de Causas Raíz

### **1. Conexiones de Base de Datos No Liberadas**
**Problema**: El código actual usa `mysql.createConnection()` que crea conexiones individuales sin pool de conexiones.

**Impacto**: 
- Agotamiento de conexiones disponibles
- Memory leaks
- Timeouts en consultas

### **2. Múltiples Clientes MQTT Activos**
**Problema**: Cada servicio (API principal + MQTT Listener) crea su propio cliente MQTT.

**Impacto**:
- Conflictos de clientId
- Conexiones duplicadas al broker
- Reconexiones infinitas

### **3. Manejo Inadecuado de Errores Asincrónicos**
**Problema**: Promesas no manejadas pueden causar bloqueos silenciosos.

**Impacato**:
- Event loop bloqueado
- Requests que nunca responden
- Acumulación de callbacks pendientes

### **4. Falta de Timeouts en Operaciones**
**Problema**: No hay timeouts configurados para operaciones de BD y MQTT.

**Impacto**:
- Operaciones que cuelgan indefinidamente
- Recursos no liberados

## 🛠️ Soluciones Implementadas

### **Solución 1: Pool de Conexiones de Base de Datos**

```javascript
// Reemplazar en config/database.js
const mysql = require('mysql2/promise');

class DatabaseConfig {
    constructor() {
        this.pool = null;
        this.poolConfig = {
            host: process.env.DB_HOST || 'smartlabs-mariadb',
            user: process.env.DB_USER || 'emqxuser',
            password: process.env.DB_PASSWORD || 'emqxpass',
            database: process.env.DB_NAME || 'emqx',
            port: parseInt(process.env.DB_PORT) || 3306,
            charset: 'utf8mb4',
            connectionLimit: 10,
            acquireTimeout: 60000,
            timeout: 60000,
            reconnect: true,
            idleTimeout: 300000,
            maxReconnects: 3
        };
    }

    async connect() {
        try {
            this.pool = mysql.createPool(this.poolConfig);
            // Probar conexión
            const connection = await this.pool.getConnection();
            await connection.execute('SELECT 1');
            connection.release();
            console.log('✅ Pool de conexiones creado exitosamente');
            return this.pool;
        } catch (error) {
            console.error('❌ Error creando pool:', error);
            throw error;
        }
    }

    getConnection() {
        return this.pool;
    }

    async close() {
        if (this.pool) {
            await this.pool.end();
            console.log('🔌 Pool de conexiones cerrado');
        }
    }
}
```

### **Solución 2: Cliente MQTT Singleton Mejorado**

```javascript
// Mejorar config/mqtt.js
class MQTTConfig {
    constructor() {
        this.client = null;
        this.isConnecting = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.options = {
            host: process.env.MQTT_HOST,
            port: process.env.MQTT_PORT,
            username: process.env.MQTT_USERNAME,
            password: process.env.MQTT_PASSWORD,
            clientId: `flutter_api_${process.pid}_${Date.now()}`,
            clean: true,
            connectTimeout: 10000,
            reconnectPeriod: 5000,
            keepalive: 60,
            reschedulePings: true
        };
    }

    async connect() {
        if (this.client && this.client.connected) {
            return this.client;
        }

        if (this.isConnecting) {
            return new Promise((resolve, reject) => {
                const checkConnection = () => {
                    if (this.client && this.client.connected) {
                        resolve(this.client);
                    } else if (!this.isConnecting) {
                        reject(new Error('Conexión MQTT falló'));
                    } else {
                        setTimeout(checkConnection, 100);
                    }
                };
                checkConnection();
            });
        }

        this.isConnecting = true;

        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
                this.isConnecting = false;
                reject(new Error('Timeout conectando a MQTT'));
            }, 15000);

            this.client = mqtt.connect(`mqtt://${this.options.host}`, this.options);

            this.client.on('connect', () => {
                clearTimeout(timeout);
                this.isConnecting = false;
                this.reconnectAttempts = 0;
                console.log('✅ Conexión MQTT exitosa');
                resolve(this.client);
            });

            this.client.on('error', (error) => {
                clearTimeout(timeout);
                this.isConnecting = false;
                console.error('❌ Error MQTT:', error);
                reject(error);
            });

            this.client.on('close', () => {
                this.isConnecting = false;
                console.log('⚠️ Conexión MQTT cerrada');
            });
        });
    }

    async close() {
        if (this.client) {
            return new Promise((resolve) => {
                this.client.end(true, () => {
                    console.log('📡 Conexión MQTT cerrada forzadamente');
                    this.client = null;
                    resolve();
                });
            });
        }
    }
}
```

### **Solución 3: Timeouts y Circuit Breaker**

```javascript
// Agregar a middleware/timeout.js
const timeoutMiddleware = (timeoutMs = 30000) => {
    return (req, res, next) => {
        const timeout = setTimeout(() => {
            if (!res.headersSent) {
                res.status(408).json({
                    success: false,
                    message: 'Request timeout',
                    error: 'La operación tardó demasiado tiempo'
                });
            }
        }, timeoutMs);

        res.on('finish', () => {
            clearTimeout(timeout);
        });

        next();
    };
};

module.exports = { timeoutMiddleware };
```

### **Solución 4: Manejo Mejorado de Errores**

```javascript
// Mejorar middleware/errorHandler.js
const asyncHandler = (fn) => {
    return (req, res, next) => {
        Promise.resolve(fn(req, res, next)).catch(next);
    };
};

const errorHandler = (err, req, res, next) => {
    console.error('❌ Error no manejado:', {
        message: err.message,
        stack: err.stack,
        url: req.url,
        method: req.method,
        timestamp: new Date().toISOString()
    });
    
    // Liberar recursos si es necesario
    if (req.dbConnection) {
        req.dbConnection.release();
    }
    
    if (!res.headersSent) {
        res.status(500).json({
            success: false,
            message: 'Error interno del servidor',
            error: process.env.NODE_ENV === 'development' ? err.message : 'Error interno'
        });
    }
};

module.exports = { errorHandler, asyncHandler };
```

## 🚀 Implementación de Fixes

### **Paso 1: Actualizar Dependencias**
```bash
cd /c/laragon/www/flutter-api
npm install --save express-timeout-handler
npm install --save generic-pool
```

### **Paso 2: Aplicar Cambios de Código**
1. Reemplazar `config/database.js` con pool de conexiones
2. Actualizar `config/mqtt.js` con cliente singleton mejorado
3. Agregar middleware de timeout
4. Mejorar manejo de errores

### **Paso 3: Variables de Entorno**
```env
# Agregar a .env
DB_CONNECTION_LIMIT=10
DB_ACQUIRE_TIMEOUT=60000
DB_TIMEOUT=60000
MQTT_CONNECT_TIMEOUT=10000
MQTT_KEEPALIVE=60
API_REQUEST_TIMEOUT=30000
```

### **Paso 4: Configuración Docker**
```yaml
# Agregar a docker-compose.yml
smartlabs-flutter-api:
  environment:
    - DB_CONNECTION_LIMIT=10
    - DB_ACQUIRE_TIMEOUT=60000
    - API_REQUEST_TIMEOUT=30000
  deploy:
    resources:
      limits:
        memory: 512M
      reservations:
        memory: 256M
```

## 📊 Monitoreo y Debugging

### **Health Check Mejorado**
```javascript
// Agregar a routes/internalRoutes.js
router.get('/health/detailed', async (req, res) => {
    const health = {
        status: 'ok',
        timestamp: new Date().toISOString(),
        uptime: process.uptime(),
        memory: process.memoryUsage(),
        database: 'disconnected',
        mqtt: 'disconnected'
    };

    try {
        // Verificar BD
        const db = dbConfig.getConnection();
        if (db) {
            await db.execute('SELECT 1');
            health.database = 'connected';
        }
    } catch (error) {
        health.database = `error: ${error.message}`;
        health.status = 'degraded';
    }

    try {
        // Verificar MQTT
        const mqtt = mqttConfig.getClient();
        if (mqtt && mqtt.connected) {
            health.mqtt = 'connected';
        }
    } catch (error) {
        health.mqtt = `error: ${error.message}`;
        health.status = 'degraded';
    }

    res.json(health);
});
```

### **Logging Estructurado**
```javascript
// Agregar winston para logging
const winston = require('winston');

const logger = winston.createLogger({
    level: 'info',
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

## 🔍 Comandos de Debugging

```bash
# Verificar estado de contenedores
docker ps
docker stats smartlabs-flutter-api

# Ver logs en tiempo real
docker logs -f smartlabs-flutter-api

# Verificar conexiones de red
docker exec smartlabs-flutter-api netstat -tulpn

# Verificar memoria y CPU
docker exec smartlabs-flutter-api top

# Reiniciar solo la API
docker restart smartlabs-flutter-api

# Verificar health check
curl http://localhost:3000/health
curl http://localhost:3000/api/health/detailed
```

## ✅ Checklist de Verificación

- [ ] Pool de conexiones implementado
- [ ] Cliente MQTT singleton configurado
- [ ] Timeouts agregados a todas las operaciones
- [ ] Manejo de errores mejorado
- [ ] Health checks detallados funcionando
- [ ] Logging estructurado implementado
- [ ] Variables de entorno configuradas
- [ ] Monitoreo de recursos activo

## 🚨 Señales de Alerta

**Reiniciar la API si observas:**
- Memory usage > 80%
- Response time > 5 segundos
- Error rate > 10%
- Conexiones BD > 8/10
- MQTT desconectado > 1 minuto

**Comandos de emergencia:**
```bash
# Reinicio rápido
docker restart smartlabs-flutter-api

# Reinicio completo del stack
docker-compose restart

# Limpiar recursos
docker system prune -f
```

Esta documentación debe resolver los problemas de bloqueo de la API y proporcionar herramientas para prevenir futuros incidentes.