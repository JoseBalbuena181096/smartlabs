# SMARTLABS - Sistema Integral de Gestión de Laboratorios Inteligentes

## Visión General del Sistema

SMARTLABS es un ecosistema completo de gestión de laboratorios inteligentes que integra dispositivos IoT, aplicaciones web, APIs REST y comunicación MQTT para proporcionar una solución integral de monitoreo, control de acceso y gestión de recursos en tiempo real.

## Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           SMARTLABS ECOSYSTEM                                  │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐            │
│  │   Web Client    │    │  Mobile Apps    │    │   IoT Devices   │            │
│  │   (PHP MVC)     │    │   (Flutter)     │    │   (ESP32/etc)   │            │
│  └─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘            │
│            │                      │                      │                    │
│            │ HTTP/WebSocket       │ HTTP/REST API        │ MQTT/WebSocket     │
│            │                      │                      │                    │
│  ┌─────────▼──────────────────────▼──────────────────────▼───────┐            │
│  │                    DOCKER INFRASTRUCTURE                       │            │
│  │                                                                 │            │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │            │
│  │  │      EMQX       │  │    MariaDB      │  │   phpMyAdmin    │ │            │
│  │  │   (Broker)      │◄─┤   (Database)    │◄─┤   (Admin UI)    │ │            │
│  │  │                 │  │                 │  │                 │ │            │
│  │  │ • MQTT Broker   │  │ • Data Storage  │  │ • DB Management │ │            │
│  │  │ • WebSocket     │  │ • User Auth     │  │ • Query Tool    │ │            │
│  │  │ • Dashboard     │  │ • Device Info   │  │ • Monitoring    │ │            │
│  │  │ • API Gateway   │  │ • Traffic Logs  │  │                 │ │            │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────┘ │            │
│  └─────────────────────────────────────────────────────────────────┘            │
│                                                                                 │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐            │
│  │  Flutter API    │    │   Node.js WS    │    │   PHP Web App   │            │
│  │  (Node.js)      │    │   (WebSocket)   │    │   (MVC)         │            │
│  └─────────────────┘    └─────────────────┘    └─────────────────┘            │
└─────────────────────────────────────────────────────────────────────────────────┘
```

## Componentes del Sistema

### 1. Infraestructura Docker (`DOCKER_SMARTLABS-main`)

**Servicios principales:**
- **EMQX Broker**: Broker MQTT para comunicación IoT
- **MariaDB**: Base de datos principal del sistema
- **phpMyAdmin**: Interfaz de administración de base de datos

**Puertos expuestos:**
- `1883`: MQTT TCP
- `8883`: MQTT SSL/TLS
- `8073/8074`: WebSocket/WebSocket Secure
- `18083/18084`: EMQX Dashboard HTTP/HTTPS
- `4000`: MariaDB
- `4001`: phpMyAdmin

### 2. Aplicación Web PHP (`app`)

**Características:**
- Arquitectura MVC personalizada
- Dashboard en tiempo real
- Gestión de usuarios y dispositivos
- Control de acceso y préstamos
- Integración con dispositivos IoT

**Tecnologías:**
- PHP 7.4+
- MySQL/MariaDB
- Apache/Nginx
- Bootstrap 5
- JavaScript/AJAX

### 3. API REST para Flutter (`flutter-api`)

**Funcionalidades:**
- API REST completa para aplicaciones móviles
- Autenticación JWT opcional
- Comunicación MQTT bidireccional
- Gestión de usuarios y dispositivos
- Procesamiento de préstamos

**Tecnologías:**
- Node.js
- Express.js
- MySQL2
- MQTT.js
- Joi (validación)

### 4. Servidor WebSocket (`node`)

**Características:**
- Monitoreo en tiempo real de dispositivos
- Comunicación WebSocket
- Conexión con fallback a base de datos
- Suscripción selectiva a dispositivos

**Tecnologías:**
- Node.js
- WebSocket
- MySQL2
- HTTP Server

## Instalación y Configuración

### Prerrequisitos

- Docker y Docker Compose
- Node.js 16+ (para APIs)
- PHP 7.4+ (para aplicación web)
- Git

### Instalación Completa

1. **Clonar el repositorio principal:**
```bash
git clone <repository-url>
cd smartlabs
```

2. **Configurar infraestructura Docker:**
```bash
cd DOCKER_SMARTLABS-main
cp .env.example .env
# Editar .env con las credenciales deseadas
docker-compose up -d
```

3. **Configurar aplicación web PHP:**
```bash
cd ../app
# Configurar base de datos en config/database.php
# Configurar servidor web (Apache/Nginx)
```

4. **Configurar Flutter API:**
```bash
cd ../flutter-api
npm install
cp .env.example .env
# Configurar variables de entorno
npm start
```

5. **Configurar servidor WebSocket:**
```bash
cd ../node
npm install
cp .env.example .env
# Configurar variables de entorno
npm start
```

### Variables de Entorno

**Docker (.env):**
```env
MARIADB_ROOT_PASSWORD=emqxpass
MARIADB_USER=emqxuser
MARIADB_PASSWORD=emqxpass
MARIADB_DATABASE=emqx
TZ=America/Mexico_City
```

**Flutter API (.env):**
```env
DB_HOST=localhost
DB_PORT=4000
DB_USER=emqxuser
DB_PASSWORD=emqxpass
DB_NAME=emqx
MQTT_HOST=localhost
MQTT_PORT=1883
MQTT_USERNAME=emqx
MQTT_PASSWORD=emqxpass
PORT=3000
```

**Node WebSocket (.env):**
```env
DB_HOST=localhost
DB_PORT=4000
DB_USER=emqxuser
DB_PASSWORD=emqxpass
DB_NAME=emqx
WS_PORT=8080
```

## Estructura de la Base de Datos

### Tablas Principales

**Autenticación MQTT:**
- `mqtt_user`: Usuarios del broker MQTT
- `mqtt_acl`: Control de acceso MQTT

**Sistema SMARTLABS:**
- `users`: Usuarios del sistema web
- `habitant`: Estudiantes/residentes del laboratorio
- `device`: Dispositivos IoT registrados
- `traffic`: Registro de accesos
- `loans`: Préstamos de equipos
- `equipment`: Equipos disponibles
- `cards`: Tarjetas de acceso

### Usuarios por Defecto

**MQTT:**
- Usuario: `emqx` / Contraseña: `emqxpass`
- Usuario: `jose` / Contraseña: `josepass`

**Base de datos:**
- Root: `root` / `emqxpass`
- Admin: `admin_iotcurso` / `18196`

## APIs y Endpoints

### Flutter API (Puerto 3000)

**Usuarios:**
- `GET /api/users/registration/:registration` - Obtener usuario por matrícula
- `GET /api/users/rfid/:rfid` - Obtener usuario por RFID
- `GET /api/users/:id/access-history` - Historial de accesos

**Dispositivos:**
- `GET /api/devices` - Listar dispositivos
- `GET /api/devices/:serie` - Obtener dispositivo
- `POST /api/devices/control` - Controlar dispositivo
- `GET /api/devices/:serie/history` - Historial del dispositivo

**Préstamos:**
- `POST /api/prestamos/procesar` - Procesar préstamo
- `POST /api/prestamos/simular` - Simular dispositivo físico

### WebSocket API (Puerto 8080)

**Mensajes Cliente → Servidor:**
```json
{
  "type": "subscribe",
  "devices": ["SMART10001", "SMART10002"]
}
```

**Mensajes Servidor → Cliente:**
```json
{
  "type": "device_status",
  "device_id": "SMART10001",
  "status": "active",
  "last_seen": "2024-12-20T10:30:00Z"
}
```

## Comunicación MQTT

### Tópicos de Entrada (Hardware → Sistema)

- `SMART{XXXXX}/loan_queryu` - Consultas de usuario
- `SMART{XXXXX}/loan_querye` - Consultas de equipo
- `SMART{XXXXX}/access_query` - Consultas de acceso
- `values` - Datos de sensores

### Tópicos de Salida (Sistema → Hardware)

- `SMART{XXXXX}/loan_response` - Respuestas de préstamo
- `SMART{XXXXX}/access_response` - Respuestas de acceso

### Ejemplo de Uso MQTT

```javascript
// Publicar datos de sensor
mqttClient.publish('SMART10001/data', JSON.stringify({
  temperature: 25.5,
  humidity: 60.2,
  timestamp: new Date().toISOString()
}));

// Suscribirse a respuestas
mqttClient.subscribe('SMART10001/loan_response');
```

## Monitoreo y Administración

### Interfaces Web

- **EMQX Dashboard**: http://localhost:18083 (admin/emqxpass)
- **phpMyAdmin**: http://localhost:4001
- **Aplicación SMARTLABS**: http://localhost/smartlabs

### Comandos de Gestión Docker

```bash
# Ver estado de servicios
docker-compose ps

# Ver logs
docker-compose logs -f emqx
docker-compose logs -f mariadb

# Reiniciar servicios
docker-compose restart

# Backup de base de datos
docker-compose exec mariadb mysqldump -u root -pemqxpass emqx > backup.sql

# Restaurar base de datos
docker-compose exec -T mariadb mysql -u root -pemqxpass emqx < backup.sql
```

## Seguridad

### Medidas Implementadas

- **Autenticación MQTT**: Usuario/contraseña en base de datos
- **Control de acceso**: ACL por tópicos y usuarios
- **Prepared Statements**: Prevención de inyección SQL
- **CORS**: Configuración de orígenes permitidos
- **Rate Limiting**: Limitación de solicitudes por IP
- **Input Validation**: Validación con Joi y sanitización

### Recomendaciones de Producción

- Cambiar contraseñas por defecto
- Habilitar SSL/TLS en todos los servicios
- Configurar firewall para puertos específicos
- Implementar autenticación JWT
- Configurar backup automático
- Monitoreo con alertas

## Desarrollo y Contribución

### Estructura del Proyecto

```
smarllabs/
├── DOCKER_SMARTLABS-main/     # Infraestructura Docker
│   ├── docker-compose.yaml
│   ├── .env
│   └── db/init.sql
├── app/                       # Aplicación web PHP
│   ├── app/
│   ├── public/
│   ├── config/
│   └── index.php
├── flutter-api/               # API REST Node.js
│   ├── src/
│   ├── package.json
│   └── .env
├── node/                      # Servidor WebSocket
│   ├── src/
│   ├── package.json
│   └── .env
└── README.md                  # Este archivo
```

### Flujo de Desarrollo

1. **Fork del repositorio**
2. **Crear rama de feature**: `git checkout -b feature/nueva-funcionalidad`
3. **Desarrollar y probar**
4. **Commit con mensaje descriptivo**
5. **Push y crear Pull Request**

### Estándares de Código

- **PHP**: PSR-12, documentación PHPDoc
- **JavaScript**: ESLint, Prettier
- **SQL**: Nomenclatura snake_case
- **Git**: Conventional Commits

## Troubleshooting

### Problemas Comunes

**Puerto en uso:**
```bash
# Verificar puertos ocupados
netstat -tulpn | grep :1883

# Cambiar puertos en docker-compose.yaml
```

**Base de datos no responde:**
```bash
# Verificar estado del contenedor
docker-compose ps mariadb

# Verificar logs
docker-compose logs mariadb

# Reiniciar servicio
docker-compose restart mariadb
```

**Problemas de conectividad MQTT:**
```bash
# Probar conexión MQTT
mosquitto_pub -h localhost -p 1883 -u emqx -P emqxpass -t test -m "hello"

# Verificar usuarios MQTT en base de datos
docker-compose exec mariadb mysql -u root -pemqxpass -e "SELECT * FROM mqtt_user;" emqx
```

## Roadmap

### Corto Plazo
- [ ] Implementación completa de SSL/TLS
- [ ] Dashboard unificado
- [ ] Notificaciones push
- [ ] Backup automático

### Mediano Plazo
- [ ] Clustering EMQX
- [ ] Microservicios con API Gateway
- [ ] Monitoreo con Prometheus/Grafana
- [ ] CI/CD pipeline

### Largo Plazo
- [ ] Machine Learning para análisis predictivo
- [ ] Edge computing
- [ ] Blockchain para trazabilidad
- [ ] Migración a Kubernetes

## Licencia

Este proyecto está licenciado bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## Soporte

- **Documentación**: Ver carpetas individuales para documentación específica
- **Issues**: Reportar problemas en GitHub Issues
- **Contacto**: [correo@ejemplo.com]

---

**Desarrollado por**: Equipo SMARTLABS  
**Última actualización**: Diciembre 2024  
**Versión**: 2.0.0