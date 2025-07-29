# SmartLabs Device Monitor

## 🚀 Descripción

Servicio de monitoreo en tiempo real desarrollado en Node.js que utiliza WebSockets para transmitir el estado de dispositivos IoT desde la base de datos a clientes conectados (aplicaciones web y móviles).

## ⚡ Inicio Rápido

### Instalación
```bash
# Instalar dependencias
npm install

# Configurar variables de entorno
cp .env.example .env
# Editar .env con tus configuraciones

# Ejecutar en desarrollo
npm run dev

# Ejecutar en producción
npm start
```

### Docker
```bash
# Construir imagen
docker build -t smartlabs-device-monitor .

# Ejecutar contenedor
docker run -p 8080:8080 --env-file .env smartlabs-device-monitor
```

## 🔌 Conexión WebSocket

### URL de Conexión
```
ws://localhost:8080
```

### Ejemplo de Conexión
```javascript
const ws = new WebSocket('ws://localhost:8080');

ws.onopen = () => {
  console.log('Conectado al monitor');
  
  // Suscribirse a dispositivos específicos
  ws.send(JSON.stringify({
    type: 'subscribe',
    devices: ['DEV001', 'DEV002']
  }));
  
  // O suscribirse a todos los dispositivos
  ws.send(JSON.stringify({
    type: 'subscribe_all'
  }));
};

ws.onmessage = (event) => {
  const data = JSON.parse(event.data);
  console.log('Mensaje recibido:', data);
};
```

## 📡 Protocolo de Mensajes

### Mensajes del Cliente

#### Suscribirse a Dispositivos
```json
{
  "type": "subscribe",
  "devices": ["DEV001", "DEV002"]
}
```

#### Suscribirse a Todos
```json
{
  "type": "subscribe_all"
}
```

#### Heartbeat
```json
{
  "type": "ping"
}
```

### Mensajes del Servidor

#### Estado Inicial
```json
{
  "type": "initial_status",
  "timestamp": "2024-01-15T10:30:00Z",
  "devices": [
    {
      "device_serie": "DEV001",
      "device_name": "Lab A - Mesa 1",
      "status": "on",
      "last_update": "2024-01-15T10:29:45Z"
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
    "status": "off",
    "last_update": "2024-01-15T10:30:00Z",
    "previous_status": "on"
  }
}
```

## 🏗️ Arquitectura

```
src/
├── config/
│   ├── database.js          # Configuración MySQL
│   └── device-status.js     # Configuración del monitor
├── services/
│   └── device-status/
│       ├── server.js        # Servidor WebSocket principal
│       ├── connectionManager.js    # Gestión de conexiones
│       ├── subscriptionManager.js  # Gestión de suscripciones
│       ├── deviceMonitor.js        # Monitor de dispositivos
│       └── databaseService.js      # Servicio de base de datos
└── utils/
    ├── logger.js            # Sistema de logs
    └── helpers.js           # Funciones auxiliares
```

## ⚙️ Configuración

### Variables de Entorno
```bash
# Servidor
WS_PORT=8080
HTTP_PORT=8080
NODE_ENV=development

# Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_USER=emqxuser
DB_PASSWORD=emqxpass
DB_NAME=emqx

# Monitor
MONITOR_INTERVAL=5000
HEARTBEAT_INTERVAL=30000
CONNECTION_TIMEOUT=60000

# Logs
LOG_LEVEL=info
LOG_DIR=./logs
```

## 🔧 Dependencias

### Principales
- **ws**: Servidor WebSocket
- **mysql2**: Cliente MySQL con soporte para promesas
- **winston**: Sistema de logging
- **express**: Servidor HTTP para health checks

### Desarrollo
- **nodemon**: Auto-restart en desarrollo
- **jest**: Testing framework

## 📊 Endpoints HTTP

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/health` | Health check y estadísticas |
| GET | `/metrics` | Métricas de performance |

### Health Check
```bash
curl http://localhost:8080/health
```

**Respuesta:**
```json
{
  "status": "OK",
  "timestamp": "2024-01-15T10:30:00Z",
  "uptime": 3600,
  "connections": {
    "totalConnections": 5,
    "activeConnections": 4
  },
  "database": {
    "primary": true,
    "fallback": false
  }
}
```

## 🧪 Testing

```bash
# Ejecutar tests
npm test

# Tests con coverage
npm run test:coverage

# Tests en modo watch
npm run test:watch
```

## 📊 Monitoreo

### Logs en Tiempo Real
```bash
# Ver todos los logs
tail -f logs/combined.log

# Ver solo errores
tail -f logs/error.log

# Ver logs de WebSocket
tail -f logs/websocket.log
```

### Métricas
```bash
# Obtener métricas
curl http://localhost:8080/metrics
```

## 🔄 Flujo de Datos

1. **Cliente** se conecta vía WebSocket
2. **Servidor** asigna ID único al cliente
3. **Cliente** se suscribe a dispositivos específicos o todos
4. **Monitor** consulta base de datos cada 5 segundos
5. **Servidor** detecta cambios y notifica a clientes suscritos
6. **Heartbeat** mantiene conexiones activas

## 📱 Integración

### Flutter/Dart
```dart
import 'package:web_socket_channel/web_socket_channel.dart';

final channel = WebSocketChannel.connect(
  Uri.parse('ws://localhost:8080'),
);

// Suscribirse
channel.sink.add(jsonEncode({
  'type': 'subscribe',
  'devices': ['DEV001']
}));

// Escuchar actualizaciones
channel.stream.listen((message) {
  final data = jsonDecode(message);
  print('Device update: $data');
});
```

### JavaScript/React
```javascript
const ws = new WebSocket('ws://localhost:8080');

ws.onmessage = (event) => {
  const data = JSON.parse(event.data);
  
  if (data.type === 'status_update') {
    updateDeviceUI(data.device);
  }
};

// Suscribirse a todos los dispositivos
ws.send(JSON.stringify({ type: 'subscribe_all' }));
```

## 🚀 Despliegue

### Producción
```bash
# Variables de producción
export NODE_ENV=production
export WS_PORT=8080
export DB_HOST=production-db-host

# Instalar dependencias de producción
npm ci --only=production

# Ejecutar
npm start
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
      - DB_HOST=mariadb
      - MONITOR_INTERVAL=5000
    depends_on:
      - mariadb
    restart: unless-stopped
```

## 🔐 Seguridad

- **Heartbeat**: Detecta conexiones inactivas
- **Rate Limiting**: Previene spam de mensajes
- **Validación**: Valida mensajes de entrada
- **Timeouts**: Cierra conexiones inactivas
- **Logs**: Registra todas las actividades

## 📚 Documentación

- **Documentación Técnica**: [`docs/MONITOR_DOCUMENTATION.md`](./docs/MONITOR_DOCUMENTATION.md)
- **Protocolo WebSocket**: Documentado en `/docs`
- **API Reference**: Disponible en health check

## 🐛 Debugging

### Modo Debug
```bash
# Habilitar debug
DEBUG=smartlabs:* npm run dev

# Debug específico del monitor
DEBUG=smartlabs:monitor npm run dev
```

### Logs Detallados
```bash
# Nivel de log debug
LOG_LEVEL=debug npm run dev
```

## 📄 Scripts NPM

```json
{
  "scripts": {
    "start": "node scripts/start-device-server.js",
    "dev": "nodemon scripts/start-device-server.js",
    "test": "jest",
    "test:watch": "jest --watch",
    "test:coverage": "jest --coverage",
    "lint": "eslint src/",
    "lint:fix": "eslint src/ --fix"
  }
}
```

## 🤝 Contribución

1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📞 Soporte

- **Issues**: [GitHub Issues](https://github.com/smartlabs/device-monitor/issues)
- **Documentación**: [`docs/`](./docs/)
- **Email**: soporte@smartlabs.com

---

**Puerto por defecto**: 8080  
**Versión**: 1.0.0  
**Node.js**: >= 18.0.0  
**Protocolo**: WebSocket + HTTP