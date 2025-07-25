# SMARTLABS Flutter API

## Descripción

API REST para aplicación Flutter de control de equipos SMARTLABS. Esta API permite gestionar usuarios, dispositivos IoT y préstamos de equipos a través de comunicación MQTT y base de datos MySQL.

## Características

- 🔐 **Autenticación opcional** con JWT
- 📡 **Comunicación MQTT** para dispositivos IoT
- 🗄️ **Base de datos MySQL** con fallback automático
- 🛡️ **Seguridad** con Helmet y Rate Limiting
- 🌐 **CORS** configurado para Flutter
- 📝 **Validación** de datos con Joi
- 🔄 **Reconexión automática** MQTT y BD

## Tecnologías

- **Node.js** + **Express.js**
- **MySQL2** para base de datos
- **MQTT.js** para comunicación IoT
- **Joi** para validación
- **Helmet** para seguridad
- **CORS** para cross-origin

## Instalación

### 1. Clonar el repositorio
```bash
git clone <repository-url>
cd flutter-api
```

### 2. Instalar dependencias
```bash
npm install
```

### 3. Configurar variables de entorno
```bash
cp .env.example .env
```

Editar `.env` con tus configuraciones:

```env
# Servidor
PORT=3000
NODE_ENV=development

# Base de Datos MySQL
DB_HOST=localhost
DB_USER=admin_iotcurso
DB_PASSWORD=tu_password_aqui
DB_NAME=smartlabs
DB_PORT=3306

# MQTT Broker
MQTT_HOST=192.168.0.100
MQTT_PORT=1883
MQTT_USERNAME=admin
MQTT_PASSWORD=public
MQTT_CLIENT_ID=flutter_api_client

# Autenticación (Opcional)
JWT_SECRET=tu_jwt_secret_aqui
JWT_EXPIRES_IN=24h
```

### 4. Ejecutar la aplicación

**Desarrollo:**
```bash
npm run dev
```

**Producción:**
```bash
npm start
```

## Estructura del Proyecto

```
src/
├── config/
│   ├── database.js      # Configuración MySQL con fallback
│   └── mqtt.js          # Configuración MQTT
├── controllers/
│   ├── deviceController.js    # Control de dispositivos
│   ├── prestamoController.js  # Control de préstamos
│   └── userController.js      # Gestión de usuarios
├── middleware/
│   ├── auth.js          # Autenticación JWT opcional
│   └── errorHandler.js  # Manejo de errores
├── routes/
│   ├── deviceRoutes.js  # Rutas de dispositivos
│   ├── internalRoutes.js # Rutas internas
│   ├── prestamoRoutes.js # Rutas de préstamos
│   └── userRoutes.js    # Rutas de usuarios
├── services/
│   ├── deviceService.js       # Lógica de dispositivos
│   ├── mqttListenerService.js # Listener MQTT para hardware
│   ├── prestamoService.js     # Lógica de préstamos
│   └── userService.js         # Lógica de usuarios
└── index.js             # Punto de entrada
```

## API Endpoints

### 🏥 Health Check

#### `GET /health`
Verifica el estado de la API.

**Respuesta:**
```json
{
  "success": true,
  "message": "SMARTLABS Flutter API funcionando correctamente",
  "data": {
    "status": "healthy",
    "timestamp": "2024-01-01T00:00:00.000Z",
    "version": "1.0.0",
    "environment": "development"
  }
}
```

### 📋 Información de la API

#### `GET /api`
Obtiene información general y documentación de endpoints.

### 👥 Usuarios

#### `GET /api/users/registration/:registration`
Obtiene un usuario por matrícula.

**Parámetros:**
- `registration` (string): Matrícula del usuario

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Usuario encontrado exitosamente",
  "data": {
    "id": 1,
    "name": "Juan Pérez",
    "registration": "A01234567",
    "email": "juan.perez@tec.mx",
    "cards_number": "1234567890",
    "device_id": null
  }
}
```

#### `GET /api/users/rfid/:rfid`
Obtiene un usuario por RFID.

**Parámetros:**
- `rfid` (string): Número RFID del usuario

#### `GET /api/users/registration/:registration/history`
Obtiene el historial de acceso de un usuario.

**Parámetros:**
- `registration` (string): Matrícula del usuario
- `limit` (query, opcional): Límite de registros (default: 10, max: 100)

#### `GET /api/users/validate/:registration`
Valida si un usuario existe.

**Parámetros:**
- `registration` (string): Matrícula del usuario

### 🔧 Dispositivos

#### `POST /api/devices/control`
Controla un dispositivo (encender/apagar).

**Body:**
```json
{
  "registration": "A01234567",
  "device_serie": "SMART10001",
  "action": 1
}
```

**Parámetros:**
- `registration` (string): Matrícula del usuario
- `device_serie` (string): Serie del dispositivo
- `action` (number): 0 = apagar, 1 = encender

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Dispositivo encendido exitosamente",
  "data": {
    "action": "on",
    "state": 1,
    "device": {
      "serie": "SMART10001",
      "alias": "Dispositivo Lab 1"
    },
    "user": {
      "name": "Juan Pérez"
    },
    "timestamp": "2024-01-01T00:00:00.000Z"
  }
}
```

#### `GET /api/devices/:device_serie`
Obtiene información de un dispositivo.

**Parámetros:**
- `device_serie` (string): Serie del dispositivo

#### `GET /api/devices/:device_serie/history`
Obtiene el historial de uso de un dispositivo.

**Parámetros:**
- `device_serie` (string): Serie del dispositivo
- `limit` (query, opcional): Límite de registros (default: 20, max: 100)

#### `GET /api/devices/:device_serie/status`
Obtiene el estado actual de un dispositivo.

#### `GET /api/devices`
Lista todos los dispositivos disponibles.

### 📦 Préstamos

#### `POST /api/prestamo/control`
Controla un préstamo de dispositivo manualmente.

**Body:**
```json
{
  "registration": "A01234567",
  "device_serie": "SMART10001",
  "action": 1
}
```

### 📡 MQTT

#### `GET /api/mqtt/status`
Obtiene el estado del MQTT Listener para hardware.

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "mqtt_listener": {
      "active": true,
      "session": {
        "active": false,
        "user": null,
        "count": 0
      }
    }
  }
}
```

#### `POST /api/mqtt/control`
Controla el MQTT Listener (iniciar/detener).

**Body:**
```json
{
  "action": "start"
}
```

**Acciones disponibles:**
- `start`: Inicia el listener MQTT
- `stop`: Detiene el listener MQTT

### 🔒 Rutas Internas

#### `POST /api/internal/loan-session`
Notifica sesión de préstamo (uso interno).

#### `GET /api/internal/status`
Obtiene el estado del sistema interno.

## Comunicación MQTT

### Tópicos Soportados

La API escucha los siguientes tópicos MQTT del hardware:

- `+/loan_queryu`: Consultas de usuario para préstamos
- `+/loan_querye`: Consultas de equipo para préstamos
- `+/access_query`: Consultas de acceso
- `+/scholar_query`: Consultas de becarios
- `values`: Datos de sensores

### Formato de Mensajes

**Consulta de usuario (loan_queryu):**
```
Tópico: SMART10001/loan_queryu
Mensaje: 1234567890
```

**Respuesta del dispositivo:**
```
Tópico: SMART10001/loan_response
Mensaje: Juan Pérez,1
```

## Seguridad

### Rate Limiting
- **Ventana:** 15 minutos
- **Límite:** 100 requests por IP
- **Aplica a:** Todas las rutas `/api/*`

### CORS
Configurado para permitir:
- `localhost` en puertos comunes (3000, 3001, 8080, 5000)
- `127.0.0.1` en puertos comunes
- Patrones dinámicos para desarrollo

### Headers de Seguridad
- **Helmet.js** configurado
- **Cross-Origin Resource Policy:** cross-origin

## Base de Datos

### Configuración con Fallback

La API soporta configuración dual:
1. **Base de datos principal** (remota)
2. **Base de datos fallback** (local)

Si la conexión principal falla, automáticamente usa el fallback.

### Tablas Principales

- `habitant`: Usuarios del sistema
- `device`: Dispositivos IoT
- `traffic`: Historial de accesos
- `equipment`: Equipos prestables
- `loan`: Préstamos activos

## Manejo de Errores

### Códigos de Estado HTTP

- `200`: Éxito
- `400`: Datos inválidos
- `401`: No autorizado
- `404`: Recurso no encontrado
- `429`: Rate limit excedido
- `500`: Error interno del servidor

### Formato de Respuesta de Error

```json
{
  "success": false,
  "message": "Descripción del error",
  "error": "Detalles técnicos (solo en desarrollo)"
}
```

## Logging

### Niveles de Log
- ✅ **Éxito:** Operaciones exitosas
- ⚠️ **Advertencia:** Situaciones no críticas
- ❌ **Error:** Errores que requieren atención
- 🔄 **Info:** Información general del sistema

### Ejemplos de Logs
```
✅ MQTT Listener conectado al broker
📨 [MQTT Listener] Mensaje recibido desde -> SMART10001/loan_queryu
❌ Error en consulta de usuario para préstamo
🔄 Reconectando MQTT...
```

## Desarrollo

### Scripts Disponibles

```bash
# Desarrollo con auto-reload
npm run dev

# Producción
npm start

# Tests (pendiente implementar)
npm test
```

### Variables de Entorno de Desarrollo

```env
NODE_ENV=development
PORT=3000
DB_HOST=localhost
MQTT_HOST=192.168.0.100
```

## Producción

### Consideraciones

1. **Variables de entorno:** Configurar todas las variables requeridas
2. **Base de datos:** Asegurar conectividad a MySQL
3. **MQTT Broker:** Verificar acceso al broker EMQX
4. **Firewall:** Abrir puertos necesarios
5. **SSL/TLS:** Implementar HTTPS en producción

### Monitoreo

- **Health check:** `GET /health`
- **Estado MQTT:** `GET /api/mqtt/status`
- **Estado interno:** `GET /api/internal/status`

## Contribución

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Licencia

MIT License - ver archivo LICENSE para detalles.

## Soporte

Para soporte técnico, contactar al equipo SMARTLABS.

---

**SMARTLABS Team** - Control de equipos IoT para laboratorios educativos