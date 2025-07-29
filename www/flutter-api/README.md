# SMARTLABS Flutter API

## Descripción

API REST para aplicación Flutter de control de equipos SMARTLABS. Esta API proporciona funcionalidades para el manejo de usuarios, dispositivos IoT y préstamos de equipos en un entorno de laboratorio inteligente.

## Características Principales

- 🔐 **Autenticación por API Key**
- 📱 **Integración con Flutter**
- 🏠 **Comunicación MQTT con dispositivos IoT**
- 📊 **Base de datos MySQL**
- 🔄 **Sistema de préstamos de equipos**
- 📈 **Historial de uso y accesos**
- 🛡️ **Middleware de seguridad**

## Tecnologías Utilizadas

- **Node.js** - Runtime de JavaScript
- **Express.js** - Framework web
- **MySQL2** - Cliente de base de datos
- **MQTT** - Protocolo de comunicación IoT
- **Joi** - Validación de datos
- **Helmet** - Seguridad HTTP
- **CORS** - Cross-Origin Resource Sharing
- **dotenv** - Gestión de variables de entorno

## Instalación

### Prerrequisitos

- Node.js (v14 o superior)
- MySQL Server
- Broker MQTT (EMQX recomendado)

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone <repository-url>
   cd flutter-api
   ```

2. **Instalar dependencias**
   ```bash
   npm install
   ```

3. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   ```
   
   Editar el archivo `.env` con tus configuraciones:
   ```env
   # Configuración del Servidor
   PORT=3000
   NODE_ENV=development
   
   # Base de Datos MySQL
   DB_HOST=localhost
   DB_USER=tu_usuario
   DB_PASSWORD=tu_password
   DB_NAME=smartlabs
   DB_PORT=3306
   
   # MQTT Broker
   MQTT_HOST=192.168.0.100
   MQTT_PORT=1883
   MQTT_USERNAME=admin
   MQTT_PASSWORD=public
   MQTT_CLIENT_ID=flutter_api_client
   ```

4. **Iniciar la aplicación**
   ```bash
   # Desarrollo
   npm run dev
   
   # Producción
   npm start
   ```

## Estructura del Proyecto

```
src/
├── config/
│   ├── database.js      # Configuración de MySQL
│   └── mqtt.js          # Configuración de MQTT
├── controllers/
│   ├── userController.js     # Controlador de usuarios
│   ├── deviceController.js   # Controlador de dispositivos
│   └── prestamoController.js # Controlador de préstamos
├── middleware/
│   ├── auth.js          # Middleware de autenticación
│   └── errorHandler.js  # Manejo de errores
├── routes/
│   ├── userRoutes.js    # Rutas de usuarios
│   ├── deviceRoutes.js  # Rutas de dispositivos
│   ├── prestamoRoutes.js # Rutas de préstamos
│   └── internalRoutes.js # Rutas internas
├── services/
│   ├── userService.js        # Lógica de negocio de usuarios
│   ├── deviceService.js      # Lógica de negocio de dispositivos
│   ├── prestamoService.js    # Lógica de negocio de préstamos
│   └── mqttListenerService.js # Servicio de escucha MQTT
└── index.js             # Punto de entrada de la aplicación
```

## API Endpoints

### Usuarios

#### `GET /api/users/registration/:registration`
Obtiene un usuario por matrícula.

**Parámetros:**
- `registration` (string): Matrícula del usuario

**Respuesta:**
```json
{
  "success": true,
  "message": "Usuario encontrado exitosamente",
  "data": {
    "id": 1,
    "name": "Juan Pérez",
    "registration": "12345",
    "email": "juan@example.com",
    "cards_number": "ABCD1234",
    "device_id": "DEV001"
  }
}
```

#### `GET /api/users/rfid/:rfid`
Obtiene un usuario por RFID.

**Parámetros:**
- `rfid` (string): Código RFID del usuario

#### `GET /api/users/registration/:registration/history`
Obtiene el historial de acceso de un usuario.

**Query Parameters:**
- `limit` (number, opcional): Límite de registros (default: 10, max: 100)

#### `GET /api/users/validate/:registration`
Valida si un usuario existe.

### Dispositivos

#### `POST /api/devices/control`
Controla un dispositivo (encender/apagar).

**Body:**
```json
{
  "registration": "12345",
  "device_serie": "DEV001",
  "action": 1
}
```

**Parámetros:**
- `registration` (string): Matrícula del usuario
- `device_serie` (string): Serie del dispositivo
- `action` (number): 0 = apagar, 1 = encender

#### `GET /api/devices/:device_serie`
Obtiene información de un dispositivo por su serie.

#### `GET /api/devices/:device_serie/history`
Obtiene el historial de uso de un dispositivo.

**Query Parameters:**
- `limit` (number, opcional): Límite de registros (default: 20, max: 100)

#### `GET /api/devices/:device_serie/status`
Obtiene el estado actual de un dispositivo.

#### `GET /api/devices/`
Obtiene todos los dispositivos disponibles.

### Préstamos

#### `POST /api/prestamos/control`
Controla un dispositivo de préstamo.

**Body:**
```json
{
  "registration": "12345",
  "device_serie": "DEV001",
  "action": 1
}
```

#### `POST /api/prestamos/prestar`
Realiza un préstamo de equipo.

#### `POST /api/prestamos/simular-dispositivo`
Simula el comportamiento del dispositivo físico.

#### `GET /api/prestamos/estado-sesion`
Obtiene el estado actual de la sesión de préstamos.

## Comunicación MQTT

La API incluye un servicio de escucha MQTT que maneja las comunicaciones con los dispositivos IoT del laboratorio.

### Tópicos MQTT

- `SMART*/loan_queryu` - Consultas de usuario
- `SMART*/loan_querye` - Consultas de equipo
- `SMART*/access_query` - Consultas de acceso
- `SMART*/scholar_query` - Consultas de becarios
- `SMART*/sensor_data` - Datos de sensores

### Configuración MQTT

La configuración MQTT se realiza a través de variables de entorno:

```env
MQTT_HOST=192.168.0.100
MQTT_PORT=1883
MQTT_USERNAME=admin
MQTT_PASSWORD=public
MQTT_CLIENT_ID=flutter_api_client
```

## Base de Datos

La API utiliza MySQL como base de datos principal. La configuración incluye:

- Conexión principal con fallback automático
- Pool de conexiones para mejor rendimiento
- Manejo de reconexión automática
- Charset UTF8MB4 para soporte completo de Unicode

### Tablas Principales

- `users` - Información de usuarios
- `devices` - Información de dispositivos
- `loans` - Registro de préstamos
- `access_logs` - Logs de acceso
- `sensor_data` - Datos de sensores

## Seguridad

### Autenticación

La API utiliza autenticación por API Key:

```javascript
// Header
X-API-Key: tu_api_key_aqui

// Query Parameter
?api_key=tu_api_key_aqui
```

### Middleware de Seguridad

- **Helmet**: Configuración de headers de seguridad HTTP
- **CORS**: Control de acceso cross-origin
- **Rate Limiting**: Limitación de peticiones por IP
- **Validación de datos**: Validación con Joi

## Desarrollo

### Scripts Disponibles

```bash
# Desarrollo con auto-reload
npm run dev

# Producción
npm start

# Tests (pendiente implementación)
npm test
```

### Variables de Entorno

Copia `.env.example` a `.env` y configura las siguientes variables:

| Variable | Descripción | Valor por defecto |
|----------|-------------|-------------------|
| `PORT` | Puerto del servidor | 3000 |
| `NODE_ENV` | Entorno de ejecución | development |
| `DB_HOST` | Host de MySQL | localhost |
| `DB_USER` | Usuario de MySQL | admin_iotcurso |
| `DB_PASSWORD` | Contraseña de MySQL | - |
| `DB_NAME` | Nombre de la base de datos | smartlabs |
| `DB_PORT` | Puerto de MySQL | 3306 |
| `MQTT_HOST` | Host del broker MQTT | 192.168.0.100 |
| `MQTT_PORT` | Puerto del broker MQTT | 1883 |
| `MQTT_USERNAME` | Usuario MQTT | admin |
| `MQTT_PASSWORD` | Contraseña MQTT | public |

## Monitoreo y Logs

La aplicación incluye logging detallado para:

- Conexiones de base de datos
- Comunicaciones MQTT
- Peticiones HTTP
- Errores y excepciones
- Estado de dispositivos

## Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## Soporte

Para soporte técnico o preguntas, contacta al equipo de SMARTLABS.

---

**SMARTLABS Team** - Sistema de Control de Equipos IoT