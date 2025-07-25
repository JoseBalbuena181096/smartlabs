# SMARTLABS Device Status Server

## Descripción

Servidor de monitoreo en tiempo real para dispositivos SMARTLABS que utiliza WebSockets para proporcionar actualizaciones instantáneas del estado de los dispositivos conectados al sistema.

## Características

- **Monitoreo en tiempo real** de estado de dispositivos
- **WebSocket Server** para comunicación bidireccional
- **Conexión a base de datos** con fallback automático
- **Suscripción selectiva** a dispositivos específicos
- **Polling configurable** para actualizaciones de estado
- **Logging detallado** para debugging y monitoreo
- **Manejo de errores** robusto con reconexión automática

## Tecnologías

- **Node.js** - Runtime de JavaScript
- **WebSocket (ws)** - Comunicación en tiempo real
- **MySQL2** - Conexión a base de datos
- **HTTP Server** - Servidor base para WebSocket

## Instalación

### Requisitos

- Node.js 16.0.0 o superior
- MySQL 8.0 o superior
- Acceso a la base de datos SMARTLABS

### Pasos de Instalación

1. **Clonar o acceder al directorio:**
   ```bash
   cd c:\laragon\www\node
   ```

2. **Instalar dependencias:**
   ```bash
   npm install
   ```

3. **Configurar variables de entorno (opcional):**
   ```bash
   # Crear archivo .env
   PORT=3000
   NODE_ENV=development
   ```

4. **Verificar configuración de base de datos:**
   - Editar `src/config/database.js` si es necesario
   - Asegurar acceso a las bases de datos configuradas

## Uso

### Iniciar el Servidor

**Desarrollo:**
```bash
npm run dev
```

**Producción:**
```bash
npm start
```

### Conectar Cliente WebSocket

```javascript
// Conectar al servidor
const ws = new WebSocket('ws://localhost:3000');

// Escuchar eventos
ws.on('open', () => {
    console.log('Conectado al servidor de dispositivos');
    
    // Suscribirse a dispositivos específicos
    ws.send(JSON.stringify({
        type: 'subscribe',
        devices: ['SMART001', 'SMART002'] // o ['all'] para todos
    }));
});

// Recibir actualizaciones de estado
ws.on('message', (data) => {
    const message = JSON.parse(data);
    
    if (message.type === 'device_status') {
        console.log(`Dispositivo ${message.device}:`, message.data);
    }
});
```

## Estructura del Proyecto

```
node/
├── package.json                    # Configuración del proyecto
├── scripts/
│   └── start-device-server.js      # Script de inicio
└── src/
    ├── config/
    │   ├── database.js              # Configuración de base de datos
    │   └── device-status.js         # Configuración del servicio
    └── services/
        └── device-status/
            └── server.js            # Servidor principal WebSocket
```

## Configuración

### Base de Datos (`src/config/database.js`)

```javascript
module.exports = {
    // Configuración principal (base de datos externa)
    primary: {
        host: "192.168.0.100",
        user: "root",
        password: "emqxpass",
        database: "emqx",
        port: 4000
    },
    
    // Configuración de fallback (base de datos local)
    fallback: {
        host: "localhost",
        user: "root",
        password: "",
        database: "emqx",
        port: 3306
    }
};
```

### Servicio (`src/config/device-status.js`)

```javascript
module.exports = {
    websocket: {
        port: 3000,
        host: '0.0.0.0',
        maxConnections: 100
    },
    
    monitoring: {
        pollingInterval: 5000, // 5 segundos
        maxRetries: 3,
        batchSize: 50
    }
};
```

## API WebSocket

### Mensajes del Cliente al Servidor

#### Suscribirse a Dispositivos
```json
{
    "type": "subscribe",
    "devices": ["SMART001", "SMART002"]
}
```

#### Suscribirse a Todos los Dispositivos
```json
{
    "type": "subscribe",
    "devices": ["all"]
}
```

#### Obtener Estado Específico
```json
{
    "type": "get_status",
    "device": "SMART001"
}
```

### Mensajes del Servidor al Cliente

#### Mensaje de Bienvenida
```json
{
    "type": "welcome",
    "message": "Conectado al servidor de estado de dispositivos",
    "devices": 5
}
```

#### Actualización de Estado
```json
{
    "type": "device_status",
    "device": "SMART001",
    "data": {
        "device": "SMART001",
        "state": "on",
        "last_activity": "2024-01-15T10:30:00.000Z",
        "user": "Juan Pérez González",
        "user_registration": "A01234567",
        "user_email": "juan.perez@tec.mx",
        "timestamp": "2024-01-15T10:30:05.123Z"
    }
}
```

## Estados de Dispositivos

| Estado | Descripción |
|--------|-------------|
| `on` | Dispositivo encendido/activo |
| `off` | Dispositivo apagado/inactivo |
| `unknown` | Estado desconocido o error |

## Esquema de Base de Datos

El servicio consulta las siguientes tablas:

### Tabla `traffic`
- `traffic_device` - ID del dispositivo
- `traffic_state` - Estado del dispositivo (0=off, 1=on)
- `traffic_date` - Timestamp de la actividad
- `traffic_hab_id` - ID del usuario

### Tabla `habintants`
- `hab_id` - ID del usuario
- `hab_name` - Nombre del usuario
- `hab_registration` - Matrícula del usuario
- `hab_email` - Email del usuario

## Monitoreo y Logs

### Niveles de Log

- **debug**: Información detallada (desarrollo)
- **info**: Información general (producción)

### Ejemplos de Logs

```
🔌 Intentando conectar a la base de datos principal...
✅ Conectado a la base de datos principal
🔍 Iniciando monitoreo de dispositivos...
📱 Dispositivo SMART001 actualizado: on (2024-01-15T10:30:00.000Z)
📊 Estado actualizado: 5 dispositivos monitoreados
```

## Integración con Frontend

### Ejemplo HTML/JavaScript

```html
<!DOCTYPE html>
<html>
<head>
    <title>Monitor de Dispositivos</title>
</head>
<body>
    <div id="device-status"></div>
    
    <script>
        const ws = new WebSocket('ws://localhost:3000');
        const statusDiv = document.getElementById('device-status');
        
        ws.onopen = () => {
            // Suscribirse a todos los dispositivos
            ws.send(JSON.stringify({
                type: 'subscribe',
                devices: ['all']
            }));
        };
        
        ws.onmessage = (event) => {
            const message = JSON.parse(event.data);
            
            if (message.type === 'device_status') {
                updateDeviceDisplay(message.device, message.data);
            }
        };
        
        function updateDeviceDisplay(deviceId, data) {
            const statusClass = data.state === 'on' ? 'status-on' : 'status-off';
            const statusText = data.state === 'on' ? 'Encendido' : 'Apagado';
            
            statusDiv.innerHTML += `
                <div class="device ${statusClass}">
                    <h3>${deviceId}</h3>
                    <p>Estado: ${statusText}</p>
                    <p>Usuario: ${data.user || 'N/A'}</p>
                    <p>Última actividad: ${new Date(data.last_activity).toLocaleString()}</p>
                </div>
            `;
        }
    </script>
</body>
</html>
```

### CSS para Estados

```css
.device {
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin: 10px;
    transition: all 0.3s ease;
}

.status-on {
    border-color: #28a745;
    background-color: #d4edda;
}

.status-off {
    border-color: #dc3545;
    background-color: #f8d7da;
}

.status-unknown {
    border-color: #ffc107;
    background-color: #fff3cd;
}
```

## Desarrollo

### Scripts Disponibles

```bash
# Desarrollo con auto-reload
npm run dev

# Producción
npm start

# Tests (no implementados)
npm test
```

### Estructura de Desarrollo

1. **Configuración centralizada** en `src/config/`
2. **Servicios modulares** en `src/services/`
3. **Scripts de inicio** en `scripts/`

## Troubleshooting

### Problemas Comunes

**Error de conexión a base de datos:**
```
❌ Error conectando a la base de datos principal: connect ECONNREFUSED
```
- Verificar que MySQL esté ejecutándose
- Comprobar configuración de host y puerto
- Verificar credenciales de acceso

**WebSocket no conecta:**
```
WebSocket connection failed
```
- Verificar que el puerto 3000 esté disponible
- Comprobar firewall y configuración de red
- Verificar que el servidor esté ejecutándose

**No se reciben actualizaciones:**
- Verificar suscripción a dispositivos
- Comprobar que existan datos en la tabla `traffic`
- Verificar logs del servidor para errores

### Comandos de Diagnóstico

```bash
# Verificar puerto en uso
netstat -an | findstr :3000

# Ver procesos Node.js
tasklist | findstr node

# Verificar conexión a MySQL
mysql -h localhost -u root -p
```

## Producción

### Consideraciones

1. **Variables de entorno:**
   ```bash
   NODE_ENV=production
   PORT=3000
   ```

2. **Process Manager (PM2):**
   ```bash
   npm install -g pm2
   pm2 start scripts/start-device-server.js --name "device-status-server"
   ```

3. **Reverse Proxy (Nginx):**
   ```nginx
   location /ws {
       proxy_pass http://localhost:3000;
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "upgrade";
   }
   ```

4. **Monitoreo:**
   - Configurar alertas para caídas del servicio
   - Monitorear uso de memoria y CPU
   - Logs centralizados

## Licencia

MIT License - Ver archivo LICENSE para más detalles.

## Soporte

Para soporte técnico o reportar problemas:
- Revisar logs del servidor
- Verificar configuración de base de datos
- Comprobar conectividad de red
- Contactar al equipo de desarrollo SMARTLABS

---

**Nota**: Este servicio es parte del ecosistema SMARTLABS y está diseñado para trabajar en conjunto con la API principal y el sistema de gestión de dispositivos.