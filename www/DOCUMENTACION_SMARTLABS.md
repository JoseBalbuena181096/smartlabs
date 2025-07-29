# Documentación del Sistema SmartLabs

## Índice
1. [Descripción General](#descripción-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [API Flutter (Node.js)](#api-flutter-nodejs)
4. [Servicio de Monitoreo (Node.js)](#servicio-de-monitoreo-nodejs)
5. [Aplicación Web (PHP)](#aplicación-web-php)
6. [Configuración Docker](#configuración-docker)
7. [Base de Datos](#base-de-datos)
8. [Instalación y Despliegue](#instalación-y-despliegue)
9. [Endpoints de la API](#endpoints-de-la-api)
10. [Configuración de Desarrollo](#configuración-de-desarrollo)

## Descripción General

SmartLabs es un sistema integral de gestión de laboratorios que permite el control y monitoreo de dispositivos IoT, gestión de préstamos de equipos y administración de usuarios. El sistema está compuesto por múltiples servicios que trabajan en conjunto para proporcionar una solución completa.

### Componentes Principales:
- **API REST para Flutter** (Node.js) - Comunicación con aplicaciones móviles
- **Servicio de Monitoreo** (Node.js) - WebSocket para estado en tiempo real
- **Aplicación Web** (PHP) - Panel administrativo y gestión
- **Base de Datos** (MariaDB) - Almacenamiento de datos
- **Broker MQTT** (EMQX) - Comunicación IoT
- **Contenedores Docker** - Orquestación y despliegue

## Arquitectura del Sistema

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Flutter App   │    │   Web Browser   │    │  IoT Devices    │
│   (Mobile)      │    │   (Admin Panel) │    │   (Hardware)    │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          │ HTTP/REST            │ HTTP                 │ MQTT
          │                      │                      │
┌─────────▼───────┐    ┌─────────▼───────┐    ┌─────────▼───────┐
│  Flutter API    │    │   PHP Web App   │    │   EMQX Broker   │
│   (Node.js)     │    │   (Apache/PHP)  │    │     (MQTT)      │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          │                      │                      │
          └──────────────────────┼──────────────────────┘
                                 │
                    ┌─────────▼───────┐    ┌─────────────────┐
                    │   MariaDB       │    │  Device Monitor │
                    │   (Database)    │    │   (WebSocket)   │
                    └─────────────────┘    └─────────────────┘
```

## API Flutter (Node.js)

### Ubicación: `c:\laragon\www\flutter-api`

### Descripción
API REST desarrollada en Node.js que proporciona endpoints para la aplicación móvil Flutter. Maneja la autenticación, control de dispositivos, gestión de préstamos y comunicación MQTT.

### Estructura del Proyecto
```
flutter-api/
├── src/
│   ├── config/
│   │   ├── database.js      # Configuración de base de datos
│   │   └── mqtt.js          # Configuración MQTT
│   ├── controllers/
│   │   ├── deviceController.js    # Control de dispositivos
│   │   ├── prestamoController.js  # Gestión de préstamos
│   │   └── userController.js      # Gestión de usuarios
│   ├── middleware/
│   │   ├── auth.js          # Middleware de autenticación
│   │   └── errorHandler.js  # Manejo de errores
│   ├── routes/
│   │   ├── deviceRoutes.js  # Rutas de dispositivos
│   │   ├── internalRoutes.js # Rutas internas
│   │   ├── prestamoRoutes.js # Rutas de préstamos
│   │   └── userRoutes.js    # Rutas de usuarios
│   ├── services/
│   │   ├── deviceService.js       # Lógica de dispositivos
│   │   ├── mqttListenerService.js # Servicio MQTT
│   │   ├── prestamoService.js     # Lógica de préstamos
│   │   └── userService.js         # Lógica de usuarios
│   └── index.js             # Punto de entrada principal
├── package.json
└── .env
```

### Características Principales
- **Autenticación JWT** (opcional)
- **Rate Limiting** (100 requests/15min)
- **CORS configurado** para múltiples orígenes
- **Validación con Joi**
- **Conexión MQTT** para IoT
- **Manejo de errores centralizado**
- **Health checks**

### Dependencias Principales
```json
{
  "express": "^4.18.2",
  "cors": "^2.8.5",
  "helmet": "^7.1.0",
  "express-rate-limit": "^7.1.5",
  "joi": "^17.11.0",
  "mqtt": "^5.3.4",
  "mysql2": "^3.6.5",
  "dotenv": "^16.3.1"
}
```

### Configuración de Base de Datos
- **Host Principal**: smartlabs-mariadb
- **Usuario**: emqxuser
- **Base de Datos**: emqx
- **Puerto**: 3306
- **Fallback**: Configuración local idéntica

### Configuración MQTT
- **Host**: Configurable via ENV (default: 192.168.0.100)
- **Puerto**: 1883
- **Autenticación**: Usuario/contraseña
- **Client ID**: Generado dinámicamente

## Servicio de Monitoreo (Node.js)

### Ubicación: `c:\laragon\www\node`

### Descripción
Servicio WebSocket que monitorea en tiempo real el estado de los dispositivos conectados al sistema. Proporciona actualizaciones instantáneas sobre el estado de encendido/apagado de los equipos.

### Estructura del Proyecto
```
node/
├── src/
│   ├── config/
│   │   ├── database.js        # Configuración de BD
│   │   └── device-status.js   # Configuración del servicio
│   └── services/
│       └── device-status/
│           └── server.js      # Servidor WebSocket principal
├── scripts/
│   └── start-device-server.js # Script de inicio
└── package.json
```

### Características Principales
- **WebSocket Server** en puerto 8080
- **Polling de base de datos** cada 5 segundos
- **Suscripción selectiva** a dispositivos específicos
- **Reconexión automática** a base de datos
- **Health check endpoint** (/health)
- **Logging configurable** (debug/info)

### Dependencias
```json
{
  "mysql2": "^3.6.0",
  "ws": "^8.18.3"
}
```

### Protocolo WebSocket

#### Mensajes del Cliente
```javascript
// Suscribirse a dispositivos específicos
{
  "type": "subscribe",
  "devices": ["device1", "device2"] // o ["all"] para todos
}

// Obtener estado específico
{
  "type": "get_status",
  "device": "device_id"
}
```

#### Mensajes del Servidor
```javascript
// Bienvenida
{
  "type": "welcome",
  "message": "Conectado al servidor de estado de dispositivos",
  "devices": 5
}

// Estado de dispositivo
{
  "type": "device_status",
  "device": "device_id",
  "data": {
    "device": "device_id",
    "state": "on|off",
    "last_activity": "2024-01-15T10:30:00Z",
    "user_name": "Juan Pérez",
    "user_registration": "A12345678",
    "user_email": "juan@example.com",
    "timestamp": "2024-01-15T10:30:05Z"
  }
}
```

### Configuración
```javascript
// device-status.js
module.exports = {
  websocket: {
    port: 8080,
    host: '0.0.0.0',
    maxConnections: 100
  },
  monitoring: {
    pollingInterval: 5000, // 5 segundos
    maxRetries: 3,
    retryDelay: 2000
  },
  logging: {
    level: 'debug' // 'info' en producción
  }
}
```

## Aplicación Web (PHP)

### Ubicación: `c:\laragon\www\app`, `c:\laragon\www\config`, `c:\laragon\www\public`

### Descripción
Aplicación web desarrollada en PHP con arquitectura MVC que proporciona un panel administrativo completo para la gestión del sistema SmartLabs.

### Estructura del Proyecto
```
app/
├── controllers/
│   ├── AuthController.php        # Autenticación
│   ├── BecariosController.php     # Gestión de becarios
│   ├── DashboardController.php    # Panel principal
│   ├── DeviceController.php       # Gestión de dispositivos
│   ├── EquipmentController.php    # Gestión de equipos
│   ├── HabitantController.php     # Gestión de usuarios
│   ├── LoanAdminController.php    # Admin de préstamos
│   ├── LoanController.php         # Préstamos de usuarios
│   └── StatsController.php        # Estadísticas
├── core/
│   ├── Controller.php             # Controlador base
│   ├── Database.php               # Conexión a BD
│   ├── Router.php                 # Enrutador
│   └── autoload.php               # Carga automática
├── models/
│   ├── Becario.php
│   ├── Card.php
│   ├── Device.php
│   ├── Equipment.php
│   ├── Habitant.php
│   ├── Loan.php
│   ├── Traffic.php
│   └── User.php
└── views/
    ├── auth/
    ├── becarios/
    ├── dashboard/
    ├── device/
    ├── equipment/
    ├── habitant/
    ├── layout/
    ├── loan/
    ├── loan_admin/
    └── stats/

config/
├── app.php                        # Configuración general
└── database.php                   # Configuración de BD

public/
├── index.php                      # Punto de entrada
├── js/                            # JavaScript
└── audio/                         # Archivos de audio
```

### Arquitectura MVC

#### Controladores Principales
1. **AuthController**: Manejo de autenticación y sesiones
2. **DashboardController**: Panel principal con estadísticas
3. **DeviceController**: CRUD de dispositivos del usuario
4. **HabitantController**: Registro y gestión de usuarios del lab
5. **EquipmentController**: Gestión de equipos prestables
6. **LoanController**: Sistema de autopréstamo
7. **LoanAdminController**: Administración de préstamos
8. **StatsController**: Reportes y estadísticas

#### Modelos Principales
- **User**: Usuarios del sistema administrativo
- **Habitant**: Usuarios del laboratorio (estudiantes/personal)
- **Device**: Dispositivos IoT registrados
- **Equipment**: Equipos prestables
- **Loan**: Préstamos de equipos
- **Traffic**: Registro de accesos a dispositivos
- **Card**: Tarjetas RFID

### Características del Sistema Web
- **Autenticación por sesiones**
- **Control de acceso basado en roles**
- **Dashboard en tiempo real**
- **Gestión completa de usuarios**
- **Sistema de préstamos**
- **Reportes y estadísticas**
- **Integración con WebSocket** para tiempo real
- **Interfaz responsive**

### Rutas Principales
```
/                          # Dashboard principal
/Auth/login               # Inicio de sesión
/Dashboard                # Panel de control
/Device                   # Gestión de dispositivos
/Habitant                 # Registro de usuarios
/Equipment                # Gestión de equipos
/Loan                     # Sistema de préstamos
/LoanAdmin                # Administración de préstamos
/Stats                    # Estadísticas
```

## Configuración Docker

### Archivos de Configuración
- `docker-compose.yml` - Configuración de producción completa
- `docker-dev.yml` - Configuración de desarrollo (solo servicios esenciales)
- `docker/` - Dockerfiles y configuraciones específicas

### Servicios en docker-compose.yml

#### 1. MariaDB (Base de Datos)
```yaml
mariadb:
  image: mariadb:11.2
  ports: ["3306:3306"]
  environment:
    MYSQL_ROOT_PASSWORD: ${MARIADB_ROOT_PASSWORD}
    MYSQL_USER: ${MARIADB_USER}
    MYSQL_PASSWORD: ${MARIADB_PASSWORD}
    MYSQL_DATABASE: ${MARIADB_DATABASE}
```

#### 2. EMQX (Broker MQTT)
```yaml
emqx:
  image: emqx/emqx:5.4.1
  ports:
    - "18083:18083"  # Dashboard
    - "1883:1883"    # MQTT
    - "8083:8083"    # WebSocket
```

#### 3. Aplicación Web PHP
```yaml
smartlabs-web:
  build: 
    context: .
    dockerfile: docker/web/Dockerfile
  ports: ["80:80"]
```

#### 4. API Flutter
```yaml
smartlabs-flutter-api:
  build:
    context: ./flutter-api
    dockerfile: ../docker/api/Dockerfile
  ports: ["3000:3000"]
```

#### 5. Monitor de Dispositivos
```yaml
smartlabs-device-monitor:
  build:
    context: ./node
    dockerfile: ../docker/monitor/Dockerfile
  ports: ["8080:8080"]
```

#### 6. PHPMyAdmin
```yaml
phpmyadmin:
  image: phpmyadmin:5.2
  ports: ["8080:80"]
```

#### 7. Nginx (Reverse Proxy)
```yaml
nginx:
  image: nginx:1.25-alpine
  ports:
    - "8000:80"
    - "8443:443"
```

### Configuración de Desarrollo (docker-dev.yml)
Incluye solo los servicios esenciales para desarrollo local:
- MariaDB
- EMQX
- PHPMyAdmin

### Dockerfiles

#### Web (PHP)
```dockerfile
FROM php:8.2-apache
# Instala extensiones: mysqli, pdo, pdo_mysql, zip
# Configura Apache con mod_rewrite
# Copia código y configura permisos
```

#### API (Node.js)
```dockerfile
FROM node:18-alpine
# Usuario no-root para seguridad
# Instala dependencias de producción
# Health check incluido
```

#### Monitor (Node.js)
```dockerfile
FROM node:18-alpine
# Similar a API pero optimizado para WebSocket
# Health check en puerto 8080
```

## Base de Datos

### Esquema Principal
La base de datos `emqx` contiene las siguientes tablas principales:

#### Usuarios y Autenticación
- `users` - Usuarios administrativos del sistema
- `habintants` - Usuarios del laboratorio (estudiantes/personal)
- `cards` - Tarjetas RFID asociadas

#### Dispositivos y Equipos
- `devices` - Dispositivos IoT registrados
- `equipments` - Equipos prestables del laboratorio
- `traffic` - Registro de accesos y uso de dispositivos

#### Préstamos
- `loans` - Registro de préstamos de equipos
- `loan_details` - Detalles específicos de cada préstamo

### Configuración de Conexión
```php
// config/database.php
return [
    'host' => 'smartlabs-mariadb',
    'username' => 'emqxuser',
    'password' => 'emqxpass',
    'database' => 'emqx',
    'port' => 3306,
    'charset' => 'utf8mb4'
];
```

## Instalación y Despliegue

### Requisitos Previos
- Docker y Docker Compose
- Git
- Archivo `.env` configurado

### Configuración del Archivo .env
```bash
# Base de datos
MARIADB_ROOT_PASSWORD=rootpassword
MARIADB_USER=emqxuser
MARIADB_PASSWORD=emqxpass
MARIADB_DATABASE=emqx
MARIADB_PORT=3306

# EMQX MQTT
EMQX_DASHBOARD_USER=admin
EMQX_DASHBOARD_PASSWORD=emqxpass
EMQX_DASHBOARD_PORT=18083
EMQX_MQTT_PORT=1883

# Aplicaciones
WEB_APP_PORT=80
FLUTTER_API_PORT=3000
DEVICE_MONITOR_PORT=8080
PHPMYADMIN_PORT=8080

# Configuración general
TZ=America/Mexico_City
NODE_ENV=production
APP_ENV=production
```

### Despliegue Completo
```bash
# 1. Clonar el repositorio
git clone <repository-url>
cd smartlabs

# 2. Configurar variables de entorno
cp .env.example .env
# Editar .env con los valores apropiados

# 3. Construir y ejecutar todos los servicios
docker-compose up -d --build

# 4. Verificar que todos los servicios estén funcionando
docker-compose ps
```

### Despliegue de Desarrollo
```bash
# Solo servicios esenciales (BD, MQTT, PHPMyAdmin)
docker-compose -f docker-dev.yml up -d

# Ejecutar aplicaciones localmente
# API Flutter
cd flutter-api
npm install
npm run dev

# Monitor de dispositivos
cd node
npm install
npm run dev

# Aplicación web PHP (usar Laragon/XAMPP)
```

### Verificación de Servicios
```bash
# Health checks
curl http://localhost:3000/health      # API Flutter
curl http://localhost:8080/health      # Monitor de dispositivos
curl http://localhost/                 # Aplicación web
curl http://localhost:18083            # EMQX Dashboard
```

## Endpoints de la API

### API Flutter (Puerto 3000)

#### Información General
```
GET  /health                    # Health check
GET  /api                       # Documentación de la API
```

#### Usuarios
```
GET  /api/users/registration/:registration    # Usuario por matrícula
GET  /api/users/rfid/:rfid                   # Usuario por RFID
GET  /api/users/registration/:registration/history  # Historial del usuario
GET  /api/users/validate/:registration       # Validar usuario
```

#### Dispositivos
```
POST /api/devices/control               # Controlar dispositivo
GET  /api/devices                       # Listar dispositivos
GET  /api/devices/:device_serie         # Info del dispositivo
GET  /api/devices/:device_serie/status  # Estado del dispositivo
GET  /api/devices/:device_serie/history # Historial del dispositivo
```

#### Préstamos
```
POST /api/prestamo/control/             # Control manual de préstamo
```

#### MQTT
```
GET  /api/mqtt/status                   # Estado del MQTT Listener
POST /api/mqtt/control                  # Controlar MQTT Listener
```

#### Interno
```
POST /api/internal/loan-session         # Notificar sesión de préstamo
GET  /api/internal/status               # Estado del sistema interno
```

### Ejemplos de Uso

#### Controlar Dispositivo
```bash
curl -X POST http://localhost:3000/api/devices/control \
  -H "Content-Type: application/json" \
  -d '{
    "registration": "A12345678",
    "device_serie": "DEV001",
    "action": "on"
  }'
```

#### Obtener Estado de Dispositivo
```bash
curl http://localhost:3000/api/devices/DEV001/status
```

#### Respuesta Típica
```json
{
  "success": true,
  "message": "Estado obtenido correctamente",
  "data": {
    "device": "DEV001",
    "state": "on",
    "last_activity": "2024-01-15T10:30:00Z",
    "user_name": "Juan Pérez",
    "user_registration": "A12345678"
  }
}
```

## Configuración de Desarrollo

### Desarrollo Local con Laragon

#### 1. Configurar Base de Datos
```bash
# Usar docker-dev.yml para servicios esenciales
docker-compose -f docker-dev.yml up -d
```

#### 2. Configurar Aplicación Web PHP
```bash
# En Laragon, apuntar a c:\laragon\www
# La aplicación estará disponible en http://localhost
```

#### 3. Configurar API Flutter
```bash
cd flutter-api
npm install
npm run dev  # Puerto 3000
```

#### 4. Configurar Monitor de Dispositivos
```bash
cd node
npm install
npm run dev  # Puerto 8080
```

### Variables de Entorno para Desarrollo
```bash
# flutter-api/.env
NODE_ENV=development
DB_HOST=localhost
DB_PORT=3306
MQTT_HOST=localhost
MQTT_PORT=1883

# Configuración PHP (config/database.php)
# Ajustar host a 'localhost' en lugar de 'smartlabs-mariadb'
```

### Debugging

#### Logs de la API
```bash
# API Flutter
tail -f flutter-api/logs/app.log

# Monitor de dispositivos
tail -f node/logs/device-monitor.log
```

#### Logs de Docker
```bash
docker-compose logs -f smartlabs-flutter-api
docker-compose logs -f smartlabs-device-monitor
docker-compose logs -f smartlabs-web-app
```

### Testing

#### Test de Conectividad
```bash
# Verificar base de datos
mysql -h localhost -P 3306 -u emqxuser -pemqxpass emqx

# Verificar MQTT
mqtt_pub -h localhost -p 1883 -u jose -P public -t test -m "hello"

# Verificar APIs
curl http://localhost:3000/health
curl http://localhost:8080/health
```

---

## Notas Adicionales

### Seguridad
- Todas las contraseñas deben cambiarse en producción
- Configurar HTTPS en Nginx para producción
- Implementar autenticación JWT en la API
- Configurar firewall para puertos específicos

### Monitoreo
- Health checks configurados en todos los servicios
- Logs centralizados disponibles
- Métricas de rendimiento en desarrollo

### Escalabilidad
- Servicios containerizados para fácil escalado
- Base de datos con pool de conexiones
- Load balancer con Nginx configurado

### Mantenimiento
- Backups automáticos de base de datos configurables
- Rotación de logs implementada
- Actualizaciones de dependencias documentadas

---

**Versión del Documento**: 1.0  
**Fecha de Actualización**: Enero 2024  
**Mantenido por**: Equipo SmartLabs