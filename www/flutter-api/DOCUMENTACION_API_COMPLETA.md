# 📚 SMARTLABS Flutter API - Documentación Completa

## 🚀 Información General

- **Nombre**: SMARTLABS Flutter API
- **Versión**: 1.0.0
- **Puerto**: 3001 (configurable en .env)
- **Base URL**: `http://localhost:3001`
- **Descripción**: API REST para aplicación Flutter de control de equipos SMARTLABS

## 🔧 Configuración

### Variables de Entorno (.env)
```env
# Base de Datos
DB_HOST=192.168.0.100
DB_USER=root
DB_PASSWORD=emqxpass
DB_NAME=emqx
DB_PORT=4000

# Base de Datos Local (Fallback)
DB_LOCAL_HOST=localhost
DB_LOCAL_USER=root
DB_LOCAL_PASSWORD=emqxpass
DB_LOCAL_NAME=emqx
DB_LOCAL_PORT=3306

# MQTT
MQTT_HOST=192.168.0.100
MQTT_PORT=1883
MQTT_USERNAME=jose
MQTT_PASSWORD=public

# Servidor
PORT=3001
NODE_ENV=development

# Seguridad
JWT_SECRET=smartlabs_flutter_api_secret_key_2024
API_KEY=smartlabs_api_key_flutter_2024
```

### Instalación y Ejecución
```bash
# Instalar dependencias
npm install

# Ejecutar en desarrollo
npm run dev

# Ejecutar en producción
npm start
```

## 📋 Endpoints de la API

### 🏥 Health Check

#### GET /health
**Descripción**: Verifica el estado de la API

**URL**: `http://localhost:3001/health`

**Método**: GET

**Headers**: Ninguno requerido

**Body**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "SMARTLABS Flutter API funcionando correctamente",
  "data": {
    "status": "healthy",
    "timestamp": "2024-01-15T10:30:00.000Z",
    "version": "1.0.0",
    "environment": "development"
  }
}
```

---

### 📖 Documentación de la API

#### GET /api
**Descripción**: Obtiene información general y documentación de endpoints

**URL**: `http://localhost:3001/api`

**Método**: GET

**Headers**: Ninguno requerido

**Body**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "SMARTLABS Flutter API",
  "data": {
    "name": "SMARTLABS Flutter API",
    "version": "1.0.0",
    "description": "API REST para aplicación Flutter de control de equipos SMARTLABS",
    "endpoints": {
      "users": "/api/users",
      "devices": "/api/devices"
    },
    "documentation": {
      "users": {
        "GET /api/users/registration/:registration": "Obtiene usuario por matrícula",
        "GET /api/users/rfid/:rfid": "Obtiene usuario por RFID",
        "GET /api/users/registration/:registration/history": "Historial de acceso del usuario",
        "GET /api/users/validate/:registration": "Valida si un usuario existe"
      },
      "devices": {
        "POST /api/devices/control": "Controla dispositivo (body: {registration, device_serie, action})",
        "GET /api/devices": "Lista todos los dispositivos",
        "GET /api/devices/:device_serie": "Información del dispositivo",
        "GET /api/devices/:device_serie/status": "Estado actual del dispositivo",
        "GET /api/devices/:device_serie/history": "Historial de uso del dispositivo"
      },
      "prestamo": {
        "POST /api/prestamo/control/": "Controla préstamo de dispositivo manualmente"
      },
      "internal": {
        "POST /api/internal/loan-session": "Notifica sesión de préstamo (interno)",
        "GET /api/internal/status": "Estado del sistema interno"
      }
    }
  }
}
```

---

## 👥 Endpoints de Usuarios

### 1. Obtener Usuario por Matrícula

#### GET /api/users/registration/:registration
**Descripción**: Busca un usuario por su matrícula

**URL**: `http://localhost:3001/api/users/registration/{registration}`

**Método**: GET

**Parámetros de URL**:
- `registration` (string, requerido): Matrícula del usuario

**Ejemplo URL**: `http://localhost:3001/api/users/registration/A01234567`

**Headers**: 
```
Content-Type: application/json
```

**Body**: Ninguno

**Respuesta Exitosa (200)**:
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
    "device_id": "DEV001"
  }
}
```

**Respuesta Error (404)**:
```json
{
  "success": false,
  "message": "Usuario no encontrado",
  "data": null
}
```

**Respuesta Error (400)**:
```json
{
  "success": false,
  "message": "Matrícula inválida",
  "error": "\"registration\" is required"
}
```

---

### 2. Obtener Usuario por RFID

#### GET /api/users/rfid/:rfid
**Descripción**: Busca un usuario por su RFID

**URL**: `http://localhost:3001/api/users/rfid/{rfid}`

**Método**: GET

**Parámetros de URL**:
- `rfid` (string, requerido): RFID del usuario

**Ejemplo URL**: `http://localhost:3001/api/users/rfid/1234567890`

**Headers**: 
```
Content-Type: application/json
```

**Body**: Ninguno

**Respuesta Exitosa (200)**:
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
    "device_id": "DEV001"
  }
}
```

---

### 3. Obtener Historial de Usuario

#### GET /api/users/registration/:registration/history
**Descripción**: Obtiene el historial de acceso de un usuario

**URL**: `http://localhost:3001/api/users/registration/{registration}/history`

**Método**: GET

**Parámetros de URL**:
- `registration` (string, requerido): Matrícula del usuario

**Query Parameters**:
- `limit` (number, opcional): Límite de registros (default: 10, max: 100)

**Ejemplo URL**: `http://localhost:3001/api/users/registration/A01234567/history?limit=20`

**Headers**: 
```
Content-Type: application/json
```

**Body**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Historial obtenido exitosamente",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Pérez",
      "registration": "A01234567"
    },
    "history": [
      {
        "id": 1,
        "device_serie": "DEV001",
        "action": "on",
        "timestamp": "2024-01-15T10:30:00.000Z"
      }
    ],
    "total": 1
  }
}
```

---

### 4. Validar Usuario

#### GET /api/users/validate/:registration
**Descripción**: Valida si un usuario existe

**URL**: `http://localhost:3001/api/users/validate/{registration}`

**Método**: GET

**Parámetros de URL**:
- `registration` (string, requerido): Matrícula del usuario

**Ejemplo URL**: `http://localhost:3001/api/users/validate/A01234567`

**Headers**: 
```
Content-Type: application/json
```

**Body**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Usuario válido",
  "data": {
    "registration": "A01234567",
    "isValid": true
  }
}
```

---

## 🔧 Endpoints de Dispositivos

### 1. Controlar Dispositivo

#### POST /api/devices/control
**Descripción**: Controla un dispositivo (encender/apagar) usando matrícula y serie del dispositivo

**URL**: `http://localhost:3001/api/devices/control`

**Método**: POST

**Headers**: 
```
Content-Type: application/json
```

**Body (JSON)**:
```json
{
  "registration": "A01234567",
  "device_serie": "DEV001",
  "action": 1
}
```

**Parámetros del Body**:
- `registration` (string, requerido): Matrícula del usuario
- `device_serie` (string, requerido): Serie del dispositivo
- `action` (number, requerido): Acción a realizar (0 = apagar, 1 = encender)

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Dispositivo controlado exitosamente",
  "data": {
    "action": "on",
    "state": "active",
    "device": {
      "id": 1,
      "serie": "DEV001",
      "alias": "Dispositivo Principal"
    },
    "user": {
      "id": 1,
      "name": "Juan Pérez"
    },
    "timestamp": "2024-01-15T10:30:00.000Z"
  }
}
```

**Respuesta Error (404)**:
```json
{
  "success": false,
  "message": "Usuario no encontrado",
  "data": null
}
```

**Respuesta Error (400)**:
```json
{
  "success": false,
  "message": "Datos inválidos",
  "error": "\"action\" must be one of [0, 1]"
}
```

---

### 2. Obtener Información de Dispositivo

#### GET /api/devices/:device_serie
**Descripción**: Obtiene información de un dispositivo por su serie

**URL**: `http://localhost:3001/api/devices/{device_serie}`

**Método**: GET

**Parámetros de URL**:
- `device_serie` (string, requerido): Serie del dispositivo

**Ejemplo URL**: `http://localhost:3001/api/devices/DEV001`

**Headers**: 
```
Content-Type: application/json
```

**Body**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Información del dispositivo obtenida exitosamente",
  "data": {
    "device": {
      "id": 1,
      "alias": "Dispositivo Principal",
      "serie": "DEV001",
      "date": "2024-01-01T00:00:00.000Z"
    },
    "currentState": {
      "state": "active",
      "lastUpdate": "2024-01-15T10:30:00.000Z",
      "isFirstUse": false
    }
  }
}
```

---

### 3. Obtener Estado de Dispositivo

#### GET /api/devices/:device_serie/status
**Descripción**: Obtiene el estado actual de un dispositivo

**URL**: `http://localhost:3001/api/devices/{device_serie}/status`

**Método**: GET

**Parámetros de URL**:
- `device_serie` (string, requerido): Serie del dispositivo

**Ejemplo URL**: `http://localhost:3001/api/devices/DEV001/status`

**Headers**: 
```
Content-Type: application/json
```

**Body**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Estado del dispositivo obtenido exitosamente",
  "data": {
    "device_serie": "DEV001",
    "state": "active",
    "lastUpdate": "2024-01-15T10:30:00.000Z",
    "isFirstUse": false
  }
}
```

---

### 4. Obtener Historial de Dispositivo

#### GET /api/devices/:device_serie/history
**Descripción**: Obtiene el historial de uso de un dispositivo

**URL**: `http://localhost:3001/api/devices/{device_serie}/history`

**Método**: GET

**Parámetros de URL**:
- `device_serie` (string, requerido): Serie del dispositivo

**Query Parameters**:
- `limit` (number, opcional): Límite de registros (default: 20, max: 100)

**Ejemplo URL**: `http://localhost:3001/api/devices/DEV001/history?limit=50`

**Headers**: 
```
Content-Type: application/json
```

**Body**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Historial obtenido exitosamente",
  "data": {
    "device": {
      "id": 1,
      "alias": "Dispositivo Principal",
      "serie": "DEV001"
    },
    "history": [
      {
        "id": 1,
        "user_name": "Juan Pérez",
        "action": "on",
        "timestamp": "2024-01-15T10:30:00.000Z"
      }
    ],
    "total": 1
  }
}
```

---

### 5. Listar Todos los Dispositivos

#### GET /api/devices
**Descripción**: Lista todos los dispositivos disponibles

**URL**: `http://localhost:3001/api/devices`

**Método**: GET

**Headers**: 
```
Content-Type: application/json
```

**Body**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Lista de dispositivos obtenida exitosamente",
  "data": {
    "devices": [
      {
        "devices_id": 1,
        "devices_alias": "Dispositivo Principal",
        "devices_serie": "DEV001",
        "devices_date": "2024-01-01T00:00:00.000Z"
      },
      {
        "devices_id": 2,
        "devices_alias": "Dispositivo Secundario",
        "devices_serie": "DEV002",
        "devices_date": "2024-01-01T00:00:00.000Z"
      }
    ],
    "total": 2
  }
}
```

---

## 🎯 Endpoints de Préstamos

### 1. Controlar Préstamo

#### POST /api/prestamo/control/
**Descripción**: Controla un dispositivo de préstamo (replica COMPLETAMENTE la funcionalidad del hardware main_usuariosLV2.cpp)

**Funcionalidad agregada**:
- ✅ Obtiene automáticamente el RFID del usuario desde la base de datos
- ✅ Publica el RFID al tópico MQTT `{device_serie}/loan_queryu` (igual que el hardware)
- ✅ Replica exactamente el comportamiento del código Arduino

**URL**: `http://localhost:3001/api/prestamo/control/`

**Método**: POST

**Headers**: 
```
Content-Type: application/json
```

**Body (JSON)**:
```json
{
  "registration": "A01234567",
  "device_serie": "DEV001",
  "action": 1
}
```

**Parámetros del Body**:
- `registration` (string, requerido): Matrícula del usuario
- `device_serie` (string, requerido): Serie del dispositivo
- `action` (number, requerido): Acción a realizar (0 = apagar, 1 = encender)

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Usuario autenticado para préstamo",
  "data": {
    "device_serie": "DEV001",
    "device_name": "Dispositivo Principal",
    "user": {
      "name": "Juan Pérez"
    },
    "action": "on",
    "state": "active",
    "rfid_published": "1234567890",
    "timestamp": "2024-01-15T10:30:00.000Z"
  }
}
```

**Comportamiento MQTT replicado**:
- Publica el RFID del usuario en el tópico: `DEV001/loan_queryu`
- Envía comandos de control al dispositivo: `DEV001/command`
- Envía nombre del usuario: `DEV001/user_name`

**⚠️ COMPORTAMIENTO CORREGIDO**:
- **action: 1** - Siempre ejecuta 'found' y mantiene la sesión ACTIVA (como el hardware ESP32)
- **action: 0** - Ejecuta 'unload' y finaliza la sesión
- **NO** se cierra automáticamente la sesión al enviar action:1 múltiples veces
- Replica exactamente el comportamiento del `main_usuariosLV2.cpp`

**Respuesta Error (400)**:
```json
{
  "success": false,
  "message": "Error procesando préstamo",
  "error": "Error procesando préstamo"
}
```

---

### 2. Préstamo de Equipo

#### POST /api/prestamo/equipo/
**Descripción**: Procesa préstamo de equipo (replica loan_querye del dispositivo físico)

**URL**: `http://localhost:3001/api/prestamo/equipo/`

**Método**: POST

**Headers**: 
```
Content-Type: application/json
```

**Body (JSON)**:
```json
{
  "device_serie": "DEV001",
  "equip_rfid": "EQ123456789"
}
```

**Parámetros del Body**:
- `device_serie` (string, requerido): Serie del dispositivo
- `equip_rfid` (string, requerido): RFID del equipo

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Préstamo de equipo procesado exitosamente",
  "data": {
    "device_serie": "DEV001",
    "equipment": {
      "rfid": "EQ123456789",
      "name": "Laptop Dell",
      "type": "laptop"
    },
    "user": {
      "name": "Juan Pérez",
      "registration": "A01234567"
    },
    "action": "loan",
    "state": "loaned",
    "timestamp": "2024-01-15T10:30:00.000Z"
  }
}
```

**Respuesta Error (401)**:
```json
{
  "success": false,
  "message": "No hay sesión activa",
  "error": "Error procesando préstamo de equipo",
  "action": "no_login"
}
```

---

### 3. Simular Dispositivo Físico

#### POST /api/prestamo/simular/
**Descripción**: Simula el dispositivo físico RFID: busca usuario por matrícula y obtiene su RFID automáticamente

**URL**: `http://localhost:3001/api/prestamo/simular/`

**Método**: POST

**Headers**: 
```
Content-Type: application/json
```

**Body (JSON)**:
```json
{
  "registration": "A01234567",
  "device_serie": "DEV001"
}
```

**Parámetros del Body**:
- `registration` (string, requerido): Matrícula del usuario
- `device_serie` (string, requerido): Serie del dispositivo

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Dispositivo físico simulado exitosamente",
  "data": {
    "device_serie": "DEV001",
    "user": {
      "name": "Juan Pérez",
      "registration": "A01234567",
      "rfid": "1234567890"
    },
    "simulation_result": "success",
    "timestamp": "2024-01-15T10:30:00.000Z"
  }
}
```

---

### 4. Obtener Estado de Sesión

#### GET /api/prestamo/estado/
**Descripción**: Obtiene el estado actual de la sesión de préstamo

**URL**: `http://localhost:3001/api/prestamo/estado/`

**Método**: GET

**Headers**: 
```
Content-Type: application/json
```

**Body**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Estado de sesión obtenido exitosamente",
  "data": {
    "session_active": true,
    "user": {
      "name": "Juan Pérez",
      "registration": "A01234567",
      "rfid": "1234567890"
    },
    "device_serie": "DEV001",
    "start_time": "2024-01-15T10:30:00.000Z",
    "duration": "00:15:30"
  }
}
```

---

## 🔒 Endpoints Internos

### 1. Notificar Sesión de Préstamo

#### POST /api/internal/loan-session
**Descripción**: Endpoint interno para notificar sesiones de préstamo desde la API Flutter al backend Node.js

**URL**: `http://localhost:3001/api/internal/loan-session`

**Método**: POST

**Headers**: 
```
Content-Type: application/json
```

**Body (JSON)**:
```json
{
  "device_serie": "DEV001",
  "user_rfid": "1234567890",
  "action": "on"
}
```

**Parámetros del Body**:
- `device_serie` (string, requerido): Serie del dispositivo
- `user_rfid` (string, requerido): RFID del usuario
- `action` (string, requerido): Acción ("on" o "off")

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Sesión iniciada correctamente",
  "data": {
    "device_serie": "DEV001",
    "user_rfid": "1234567890",
    "action": "on",
    "timestamp": "2024-01-15T10:30:00.000Z",
    "source": "flutter-api",
    "backend_synced": true
  }
}
```

**Respuesta Error (400)**:
```json
{
  "success": false,
  "message": "Parámetros requeridos: device_serie, user_rfid, action",
  "error": "Parámetros faltantes"
}
```

---

### 2. Estado del Sistema Interno

#### GET /api/internal/status
**Descripción**: Estado del sistema interno

**URL**: `http://localhost:3001/api/internal/status`

**Método**: GET

**Headers**: 
```
Content-Type: application/json
```

**Body**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Sistema interno funcionando correctamente",
  "data": {
    "service": "flutter-api-internal",
    "status": "active",
    "timestamp": "2024-01-15T10:30:00.000Z"
  }
}
```

---

## 📦 Colección de Postman

### Configuración de Environment
Crea un environment en Postman con las siguientes variables:

```json
{
  "base_url": "http://localhost:3001",
  "registration": "A01234567",
  "device_serie": "DEV001",
  "rfid": "1234567890",
  "equip_rfid": "EQ123456789"
}
```

### Ejemplos de Requests para Postman

#### 1. Health Check
```
GET {{base_url}}/health
```

#### 2. Obtener Usuario por Matrícula
```
GET {{base_url}}/api/users/registration/{{registration}}
```

#### 3. Controlar Dispositivo
```
POST {{base_url}}/api/devices/control
Content-Type: application/json

{
  "registration": "{{registration}}",
  "device_serie": "{{device_serie}}",
  "action": 1
}
```

#### 4. Listar Dispositivos
```
GET {{base_url}}/api/devices
```

#### 5. Controlar Préstamo
```
POST {{base_url}}/api/prestamo/control/
Content-Type: application/json

{
  "registration": "{{registration}}",
  "device_serie": "{{device_serie}}",
  "action": 1
}
```

#### 6. Préstamo de Equipo
```
POST {{base_url}}/api/prestamo/equipo/
Content-Type: application/json

{
  "device_serie": "{{device_serie}}",
  "equip_rfid": "{{equip_rfid}}"
}
```

---

## 🚨 Códigos de Error Comunes

| Código | Descripción | Causa Común |
|--------|-------------|-------------|
| 400 | Bad Request | Datos de entrada inválidos o faltantes |
| 401 | Unauthorized | No hay sesión activa para préstamos |
| 404 | Not Found | Usuario o dispositivo no encontrado |
| 500 | Internal Server Error | Error interno del servidor o base de datos |

---

## 🔧 Dependencias

- **express**: Framework web para Node.js
- **cors**: Middleware para CORS
- **helmet**: Middleware de seguridad
- **joi**: Validación de esquemas
- **mysql2**: Cliente MySQL
- **mqtt**: Cliente MQTT
- **dotenv**: Manejo de variables de entorno
- **express-rate-limit**: Limitación de velocidad
- **axios**: Cliente HTTP

---

## 📝 Notas Importantes

1. **Autenticación**: Los endpoints principales usan autenticación opcional
2. **Rate Limiting**: La API implementa limitación de velocidad
3. **CORS**: Configurado para permitir requests cross-origin
4. **Validación**: Todos los inputs son validados usando Joi
5. **Base de Datos**: Soporte para conexión principal y fallback local
6. **MQTT**: Integración con broker MQTT para comunicación IoT
7. **Logs**: Sistema de logging detallado para debugging
8. **Graceful Shutdown**: Cierre limpio del servidor y conexiones

---

## 🚀 Inicio Rápido

1. **Clonar y configurar**:
   ```bash
   cd c:\laragon\www\flutter-api
   npm install
   cp .env.example .env
   # Editar .env con tus configuraciones
   ```

2. **Ejecutar en desarrollo**:
   ```bash
   npm run dev
   ```

3. **Verificar funcionamiento**:
   ```bash
   curl http://localhost:3001/health
   ```

4. **Ver documentación**:
   ```bash
   curl http://localhost:3001/api
   ```

---

*Documentación generada para SMARTLABS Flutter API v1.0.0*