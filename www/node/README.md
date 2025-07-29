# SMARTLABS Device Status Server

## Descripción

El **SMARTLABS Device Status Server** es un servidor WebSocket en tiempo real que monitorea constantemente el estado de los dispositivos IoT en el sistema SMARTLABS. Proporciona actualizaciones en tiempo real sobre el estado de los dispositivos (encendido/apagado) y la información del usuario que los está utilizando.

## Características Principales

- **Monitoreo en Tiempo Real**: Consulta periódica del estado de dispositivos desde la base de datos
- **WebSocket Server**: Comunicación bidireccional en tiempo real con clientes
- **Suscripción Selectiva**: Los clientes pueden suscribirse a dispositivos específicos o a todos
- **Fallback de Base de Datos**: Conexión automática a base de datos de respaldo en caso de fallo
- **Configuración Centralizada**: Configuraciones modulares y reutilizables
- **Logging Configurable**: Sistema de logs con diferentes niveles de detalle
- **Gestión de Conexiones**: Manejo eficiente de múltiples clientes WebSocket

## Tecnologías Utilizadas

- **Node.js**: Runtime de JavaScript
- **WebSocket (ws)**: Comunicación en tiempo real
- **MySQL2**: Conexión a base de datos MySQL
- **HTTP**: Servidor HTTP base para WebSocket

## Instalación

### Prerrequisitos

- Node.js (versión 14 o superior)
- npm o yarn
- MySQL Server
- Acceso a la base de datos SMARTLABS

### Pasos de Instalación

1. **Clonar o navegar al directorio del proyecto**:
   ```bash
   cd c:\laragon\www\node
   ```

2. **Instalar dependencias**:
   ```bash
   npm install
   ```

3. **Configurar variables de entorno** (opcional):
   ```bash
   # Crear archivo .env si es necesario
   PORT=3000
   NODE_ENV=development
   ```

4. **Verificar configuración de base de datos**:
   - Editar `src/config/database.js` si es necesario
   - Asegurar acceso a las bases de datos configuradas

## Estructura del Proyecto

```
node/
├── package.json                    # Configuración del proyecto y dependencias
├── scripts/
│   └── start-device-server.js      # Script de inicio del servidor
└── src/
    ├── config/
    │   ├── database.js              # Configuración de conexión a BD
    │   └── device-status.js         # Configuración del servidor WebSocket
    └── services/
        └── device-status/
            └── server.js            # Servidor principal WebSocket
```

## Configuración

### Base de Datos (`src/config/database.js`)

```javascript
{
  primary: {
    host: "192.168.0.100",
    user: "root",
    password: "emqxpass",
    database: "emqx",
    port: 4000
  },
  fallback: {
    host: "localhost",
    user: "root",
    password: "",
    database: "emqx",
    port: 3306
  }
}
```

### Servidor WebSocket (`src/config/device-status.js`)

```javascript
{
  websocket: {
    port: 3000,
    host: '0.0.0.0',
    pingInterval: 30000,
    maxConnections: 100
  },
  monitoring: {
    pollingInterval: 5000,  // 5 segundos
    maxRetries: 3,
    batchSize: 50
  }
}
```

## Uso

### Iniciar el Servidor

```bash
# Modo producción
npm start

# Modo desarrollo (con nodemon)
npm run dev
```

### Conectar Cliente WebSocket

```javascript
const ws = new WebSocket('ws://localhost:3000');

ws.on('open', () => {
    console.log('Conectado al servidor');
    
    // Suscribirse a dispositivos específicos
    ws.send(JSON.stringify({
        type: 'subscribe',
        devices: ['device001', 'device002'] // o ['all'] para todos
    }));
});

ws.on('message', (data) => {
    const message = JSON.parse(data);
    console.log('Estado del dispositivo:', message);
});
```

## Protocolo WebSocket

### Mensajes del Cliente al Servidor

#### Suscripción a Dispositivos
```json
{
    "type": "subscribe",
    "devices": ["device001", "device002"]
}
```

#### Solicitar Estado Específico
```json
{
    "type": "get_status",
    "device": "device001"
}
```

### Mensajes del Servidor al Cliente

#### Mensaje de Bienvenida
```json
{
    "type": "welcome",
    "message": "Conectado al servidor de estado de dispositivos",
    "devices": 25
}
```

#### Estado del Dispositivo
```json
{
    "type": "device_status",
    "device": "device001",
    "data": {
        "device": "device001",
        "state": "on",
        "last_activity": "2025-01-08T10:30:00.000Z",
        "user": "Juan Pérez",
        "user_registration": "2021001",
        "user_email": "juan.perez@example.com",
        "timestamp": "2025-01-08T10:30:05.123Z"
    }
}
```

## Base de Datos

### Tabla Principal: `traffic`

```sql
CREATE TABLE traffic (
    traffic_id INT AUTO_INCREMENT PRIMARY KEY,
    traffic_device VARCHAR(50) NOT NULL,
    traffic_state TINYINT NOT NULL,
    traffic_date DATETIME NOT NULL,
    traffic_hab_id INT,
    INDEX idx_device_date (traffic_device, traffic_date)
);
```

### Tabla de Usuarios: `habintants`

```sql
CREATE TABLE habintants (
    hab_id INT AUTO_INCREMENT PRIMARY KEY,
    hab_name VARCHAR(100) NOT NULL,
    hab_registration VARCHAR(20) UNIQUE,
    hab_email VARCHAR(100)
);
```

## Monitoreo y Logs

### Niveles de Log

- **Production**: Solo logs importantes (info, warn, error)
- **Development**: Logs detallados incluyendo debug

### Ejemplo de Logs

```
🔧 Iniciando servidor de estado de dispositivos...
🔌 Intentando conectar a la base de datos principal...
✅ Conectado a la base de datos principal
🚀 Servidor WebSocket iniciado en puerto 3000
🔍 Iniciando monitoreo de dispositivos...
📱 Dispositivo device001 actualizado: on (2025-01-08T10:30:00.000Z)
```

## Scripts Disponibles

```json
{
  "start": "node scripts/start-device-server.js",
  "dev": "nodemon scripts/start-device-server.js",
  "test": "echo \"Error: no test specified\" && exit 1"
}
```

## Seguridad

- **Conexión de Base de Datos**: Credenciales configurables
- **Límite de Conexiones**: Máximo de conexiones WebSocket configurables
- **Timeout de Conexión**: Timeouts configurables para evitar conexiones colgadas
- **Validación de Mensajes**: Validación JSON de mensajes entrantes

## Escalabilidad

### Consideraciones de Rendimiento

- **Polling Interval**: Configurable según necesidades (por defecto 5 segundos)
- **Batch Size**: Consultas por lotes para optimizar rendimiento
- **Connection Pooling**: Pool de conexiones a base de datos
- **Memory Management**: Gestión eficiente de estado en memoria

### Optimizaciones

- Índices en base de datos para consultas rápidas
- Caché en memoria del estado actual
- Broadcast selectivo solo a clientes interesados
- Reconexión automática a base de datos

## Solución de Problemas

### Problemas Comunes

1. **Error de Conexión a Base de Datos**:
   ```
   ⚠️ Error conectando a la base de datos principal
   🔌 Intentando conectar a la base de datos local...
   ```
   - Verificar configuración en `src/config/database.js`
   - Comprobar que MySQL esté ejecutándose

2. **Puerto en Uso**:
   ```
   Error: listen EADDRINUSE :::3000
   ```
   - Cambiar puerto en configuración o variable de entorno
   - Verificar que no haya otro proceso usando el puerto

3. **Clientes No Reciben Actualizaciones**:
   - Verificar suscripción correcta con mensaje `subscribe`
   - Comprobar que WebSocket esté conectado
   - Revisar logs del servidor para errores

## Contribución

1. Fork del proyecto
2. Crear rama para nueva funcionalidad (`git checkout -b feature/nueva-funcionalidad`)
3. Commit de cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Licencia

MIT License - ver archivo LICENSE para más detalles.

## Soporte

Para soporte técnico o preguntas:
- **Equipo**: SMARTLABS
- **Documentación**: Ver archivos en `/docs`
- **Issues**: Reportar problemas en el repositorio del proyecto

---

**Versión**: 2.0.0  
**Última Actualización**: Enero 2025  
**Mantenido por**: Equipo SMARTLABS