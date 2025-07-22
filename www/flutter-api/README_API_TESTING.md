# 🚀 SMARTLABS Flutter API - Guía de Pruebas

## 📋 Archivos de Documentación Creados

1. **`DOCUMENTACION_API_COMPLETA.md`** - Documentación completa de todos los endpoints
2. **`SMARTLABS_Flutter_API.postman_collection.json`** - Colección de Postman importable
3. **`SMARTLABS_Flutter_API.postman_environment.json`** - Environment de Postman con variables
4. **`README_API_TESTING.md`** - Esta guía de pruebas

## 🔧 Configuración Rápida

### 1. Iniciar la API
```bash
cd c:\laragon\www\flutter-api
npm install
npm run dev
```

### 2. Verificar que funciona
```bash
curl http://localhost:3001/health
```

### 3. Importar en Postman
1. Abrir Postman
2. Ir a **File > Import**
3. Importar `SMARTLABS_Flutter_API.postman_collection.json`
4. Importar `SMARTLABS_Flutter_API.postman_environment.json`
5. Seleccionar el environment "SMARTLABS Flutter API Environment"

## 🧪 Pruebas Básicas

### Health Check
```bash
GET http://localhost:3001/health
```

### Documentación de la API
```bash
GET http://localhost:3001/api
```

### Buscar Usuario
```bash
GET http://localhost:3001/api/users/registration/A01234567
```

### Controlar Dispositivo
```bash
POST http://localhost:3001/api/devices/control
Content-Type: application/json

{
  "registration": "A01234567",
  "device_serie": "DEV001",
  "action": 1
}
```

### Listar Dispositivos
```bash
GET http://localhost:3001/api/devices
```

## 📊 Endpoints Principales

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/health` | Health check |
| GET | `/api` | Documentación |
| GET | `/api/users/registration/:registration` | Buscar usuario por matrícula |
| GET | `/api/users/rfid/:rfid` | Buscar usuario por RFID |
| POST | `/api/devices/control` | Controlar dispositivo |
| GET | `/api/devices` | Listar dispositivos |
| GET | `/api/devices/:serie` | Info de dispositivo |
| GET | `/api/devices/:serie/status` | Estado de dispositivo |
| GET | `/api/devices/:serie/history` | Historial de dispositivo |
| POST | `/api/prestamo/control/` | Control de préstamos (replica hardware) |
| POST | `/api/prestamo/equipo/` | Préstamo de equipo |
| POST | `/api/prestamo/simular/` | Simular dispositivo físico |
| GET | `/api/prestamo/estado/` | Estado de sesión |
| POST | `/api/internal/loan-session` | Notificación interna |
| GET | `/api/internal/status` | Estado interno |

## 🔑 Variables de Environment

| Variable | Valor por Defecto | Descripción |
|----------|-------------------|-------------|
| `base_url` | `http://localhost:3001` | URL base de la API |
| `registration` | `A01234567` | Matrícula de prueba |
| `device_serie` | `DEV001` | Serie de dispositivo de prueba |
| `rfid` | `1234567890` | RFID de prueba |
| `equip_rfid` | `EQ123456789` | RFID de equipo de prueba |
| `action_on` | `1` | Acción encender |
| `action_off` | `0` | Acción apagar |
| `limit_small` | `10` | Límite pequeño |
| `limit_medium` | `20` | Límite mediano |
| `limit_large` | `50` | Límite grande |

## 📝 Ejemplos de Body para POST

### Control de Dispositivo
```json
{
  "registration": "A01234567",
  "device_serie": "DEV001",
  "action": 1
}
```

### Control de Préstamo
```json
{
  "registration": "A01234567",
  "device_serie": "DEV001",
  "action": 1
}
```

### Préstamo de Equipo
```json
{
  "device_serie": "DEV001",
  "equip_rfid": "EQ123456789"
}
```

### Simular Dispositivo Físico
```json
{
  "registration": "A01234567",
  "device_serie": "DEV001"
}
```

### Notificación Interna
```json
{
  "device_serie": "DEV001",
  "user_rfid": "1234567890",
  "action": "on"
}
```

## 🔍 Códigos de Respuesta

| Código | Significado | Cuándo Ocurre |
|--------|-------------|---------------|
| 200 | OK | Operación exitosa |
| 400 | Bad Request | Datos inválidos o faltantes |
| 401 | Unauthorized | Sin sesión activa (préstamos) |
| 404 | Not Found | Usuario/dispositivo no encontrado |
| 500 | Internal Server Error | Error del servidor |

## 🧪 Flujo de Pruebas Recomendado

### 1. Verificación Básica
1. Health Check
2. Documentación API
3. Listar dispositivos

### 2. Gestión de Usuarios
1. Buscar usuario por matrícula
2. Buscar usuario por RFID
3. Validar usuario
4. Obtener historial de usuario

### 3. Control de Dispositivos
1. Obtener info de dispositivo
2. Obtener estado de dispositivo
3. Controlar dispositivo (encender)
4. Controlar dispositivo (apagar)
5. Obtener historial de dispositivo

### 4. Sistema de Préstamos
1. Simular dispositivo físico
2. Control de préstamo
3. Préstamo de equipo
4. Obtener estado de sesión

### 5. Endpoints Internos
1. Notificar sesión de préstamo
2. Estado del sistema interno

## 🤖 Funcionalidad MQTT Agregada

### Replicación Completa del Hardware

El endpoint `/api/prestamo/control/` ahora replica **COMPLETAMENTE** el comportamiento del hardware `main_usuariosLV2.cpp`:

#### ✅ Funcionalidades Implementadas:
1. **Obtención automática de RFID**: Busca el RFID del usuario en la base de datos usando su matrícula
2. **Publicación MQTT**: Publica el RFID al tópico `{device_serie}/loan_queryu` (igual que el hardware)
3. **Comandos de control**: Envía comandos al dispositivo via MQTT
4. **Sincronización**: Mantiene sincronización con el backend Node.js

#### 📤 Tópicos MQTT Utilizados:
- `{device_serie}/loan_queryu` - Publica el RFID del usuario
- `{device_serie}/command` - Envía comandos (found, nofound, unload)
- `{device_serie}/user_name` - Envía el nombre del usuario

#### 🔄 Flujo de Operación:
1. Recibe matrícula del usuario
2. Busca RFID en la base de datos
3. Publica RFID en tópico MQTT
4. Procesa la lógica de préstamo
5. Envía comandos de control
6. Notifica al backend Node.js

#### ⚠️ COMPORTAMIENTO CORREGIDO:
- **action: 1** - Siempre ejecuta 'found' y mantiene sesión ACTIVA
- **action: 0** - Ejecuta 'unload' y finaliza la sesión
- **NO** cierra automáticamente la sesión con múltiples action:1
- Replica exactamente el comportamiento del hardware ESP32

## 🚨 Troubleshooting

### Error: ECONNREFUSED
- Verificar que la API esté ejecutándose en el puerto 3001
- Revisar el archivo `.env` para configuración correcta

### Error: Usuario no encontrado
- Verificar que existan usuarios en la base de datos
- Usar matrículas válidas del sistema

### Error: Dispositivo no encontrado
- Verificar que existan dispositivos en la base de datos
- Usar series de dispositivos válidas

### Error: Base de datos
- Verificar conexión a MySQL
- Revisar credenciales en `.env`
- Verificar que el servicio MySQL esté ejecutándose

### Error: MQTT
- Verificar conexión al broker MQTT
- Revisar configuración MQTT en `.env`
- Verificar que el broker MQTT esté ejecutándose
- Monitorear publicaciones en tópicos MQTT para replicación del hardware

## 📞 Soporte

Para más información, revisar:
- `DOCUMENTACION_API_COMPLETA.md` - Documentación detallada
- Logs de la aplicación en consola
- Configuración en archivo `.env`

## 🔧 Configuración de Desarrollo

### Variables de Entorno Requeridas
```env
# Base de Datos
DB_HOST=192.168.0.100
DB_USER=root
DB_PASSWORD=emqxpass
DB_NAME=emqx
DB_PORT=4000

# MQTT
MQTT_HOST=192.168.0.100
MQTT_PORT=1883
MQTT_USERNAME=jose
MQTT_PASSWORD=public

# Servidor
PORT=3001
NODE_ENV=development
```

### Scripts Disponibles
```bash
npm start      # Ejecutar en producción
npm run dev    # Ejecutar en desarrollo con nodemon
npm test       # Ejecutar tests (no implementado)
```

---

*Guía creada para facilitar las pruebas de SMARTLABS Flutter API v1.0.0*