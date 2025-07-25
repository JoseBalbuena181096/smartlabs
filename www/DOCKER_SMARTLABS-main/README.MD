# SMARTLABS Docker Infrastructure

## Descripción General

Este proyecto proporciona la infraestructura completa de contenedores Docker para el sistema SMARTLABS, incluyendo un broker MQTT (EMQX), base de datos MariaDB y herramientas de administración. El sistema está diseñado para gestionar dispositivos IoT, control de acceso y monitoreo en tiempo real en laboratorios inteligentes.

## Arquitectura del Sistema

### Componentes Principales

1. **EMQX Broker (v4.4.19)**
   - Broker MQTT para comunicación IoT
   - Dashboard web de administración
   - Autenticación basada en MySQL
   - Soporte para WebSockets y MQTT

2. **MariaDB**
   - Base de datos principal del sistema
   - Almacena usuarios, dispositivos, tráfico y configuraciones
   - Puerto expuesto: 4000

3. **phpMyAdmin**
   - Interfaz web para administración de base de datos
   - Puerto expuesto: 4001

### Diagrama de Red

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     EMQX        │    │    MariaDB      │    │   phpMyAdmin    │
│   (Broker)      │◄──►│   (Database)    │◄──►│   (Admin UI)    │
│                 │    │                 │    │                 │
│ Ports:          │    │ Port: 4000      │    │ Port: 4001      │
│ - 1883 (MQTT)   │    │                 │    │                 │
│ - 8883 (MQTTS)  │    │                 │    │                 │
│ - 8073 (WS)     │    │                 │    │                 │
│ - 8074 (WSS)    │    │                 │    │                 │
│ - 18083 (HTTP)  │    │                 │    │                 │
│ - 18084 (HTTPS) │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Instalación y Configuración

### Prerrequisitos

#### 1. Instalar Docker en Ubuntu

```bash
# Actualizar el sistema
sudo apt update
sudo apt install apt-transport-https ca-certificates curl gnupg lsb-release

# Agregar la clave GPG oficial de Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Configurar el repositorio estable
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Instalar Docker Engine
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io
```

#### 2. Instalar Docker Compose

```bash
# Descargar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/1.28.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose

# Dar permisos de ejecución
sudo chmod +x /usr/local/bin/docker-compose
```

#### 3. Verificar Instalación

```bash
sudo docker --version
sudo docker-compose --version
```

### Configuración del Proyecto

#### 1. Clonar el Repositorio

```bash
git clone https://github.com/JoseBalbuena181096/DOCKER_SMARTLABS.git
cd DOCKER_SMARTLABS
```

#### 2. Configurar Variables de Entorno

El archivo `.env` ya está configurado con los valores por defecto:

```env
# Configuración de MariaDB
MARIADB_ROOT_PASSWORD=emqxpass
MARIADB_USER=emqxuser
MARIADB_PASSWORD=emqxpass
MARIADB_DATABASE=emqx

# Zona horaria
TZ=America/Mexico_City
```

#### 3. Ejecutar los Servicios

```bash
# Iniciar todos los servicios en segundo plano
sudo docker-compose up -d

# Ver el estado de los contenedores
sudo docker-compose ps

# Ver logs de todos los servicios
sudo docker-compose logs -f
```

## Estructura de la Base de Datos

### Tablas Principales

#### Autenticación MQTT
- **mqtt_user**: Usuarios del broker MQTT
- **mqtt_acl**: Lista de control de acceso MQTT

#### Sistema SMARTLABS
- **users**: Usuarios del sistema web
- **devices**: Dispositivos IoT registrados
- **habintants**: Habitantes/estudiantes del laboratorio
- **cards**: Tarjetas RFID para control de acceso
- **traffic**: Registro de accesos a dispositivos
- **loans**: Préstamos de equipamiento
- **equipments**: Catálogo de equipos disponibles
- **data**: Datos de sensores (temperatura, voltaje)
- **temps**: Registro histórico de temperaturas

#### Vistas del Sistema
- **habslab**: Vista combinada de préstamos y equipos
- **cards_habs**: Relación entre tarjetas y habitantes
- **traffic_devices**: Tráfico de dispositivos con información de usuarios

### Usuarios Predeterminados

#### MQTT
- **Usuario**: emqx / **Contraseña**: emqxpass (superusuario)
- **Usuario**: jose / **Contraseña**: emqxpass (superusuario)

#### Sistema Web
- **Email**: josebalbuena181096@gmail.com
- **Contraseña**: 181096

#### Base de Datos
- **Usuario**: admin_iotcurso
- **Contraseña**: 18196

## Puertos y Servicios

### EMQX Broker
| Puerto | Protocolo | Descripción |
|--------|-----------|-------------|
| 1883   | MQTT      | Protocolo MQTT estándar |
| 8883   | MQTTS     | MQTT sobre SSL/TLS |
| 8073   | WebSocket | WebSocket para MQTT |
| 8074   | WSS       | WebSocket Secure |
| 18083  | HTTP      | Dashboard web |
| 18084  | HTTPS     | Dashboard web seguro |
| 8081   | HTTP      | API de gestión |
| 8082   | HTTPS     | API de gestión segura |

### MariaDB
| Puerto | Servicio | Descripción |
|--------|----------|-------------|
| 4000   | MySQL    | Acceso a base de datos |

### phpMyAdmin
| Puerto | Servicio | Descripción |
|--------|----------|-------------|
| 4001   | HTTP     | Interfaz web de administración |

## Configuración Avanzada

### Configuración EMQX

El broker EMQX está configurado con:

- **Autenticación**: Basada en MySQL
- **Máximas conexiones**: 1000 por protocolo
- **Plugins habilitados**:
  - emqx_recon (reconexión)
  - emqx_retainer (retención de mensajes)
  - emqx_management (API de gestión)
  - emqx_dashboard (interfaz web)
  - emqx_auth_mysql (autenticación MySQL)

### Volúmenes Persistentes

- **vol-emqx-data**: Datos de EMQX
- **vol-emqx-etc**: Configuración de EMQX
- **vol-emqx-log**: Logs de EMQX
- **mariadb**: Datos de la base de datos

### Red Docker

- **Red**: iot_host (bridge)
- **Aliases**:
  - mariadb_host
  - phpmyadmin_host
  - emqx_host

## Comandos Útiles

### Gestión de Contenedores

```bash
# Iniciar servicios
sudo docker-compose up -d

# Detener servicios
sudo docker-compose down

# Reiniciar un servicio específico
sudo docker-compose restart emqx

# Ver logs en tiempo real
sudo docker-compose logs -f emqx

# Acceder al contenedor de MariaDB
sudo docker-compose exec mariadb mysql -u root -p

# Backup de la base de datos
sudo docker-compose exec mariadb mysqldump -u root -pemqxpass emqx > backup.sql

# Restaurar base de datos
sudo docker-compose exec -T mariadb mysql -u root -pemqxpass emqx < backup.sql
```

### Monitoreo

```bash
# Ver estado de contenedores
sudo docker-compose ps

# Ver uso de recursos
sudo docker stats

# Ver logs de errores
sudo docker-compose logs --tail=50 emqx | grep ERROR
```

## Acceso a Interfaces Web

### EMQX Dashboard
- **URL**: http://localhost:18083
- **Usuario**: admin
- **Contraseña**: emqxpass

### phpMyAdmin
- **URL**: http://localhost:4001
- **Servidor**: mariadb_host
- **Usuario**: root o admin_iotcurso
- **Contraseña**: emqxpass o 18196

## Integración con Aplicaciones

### Conexión MQTT desde Aplicaciones

```javascript
// Ejemplo de conexión WebSocket
const client = mqtt.connect('ws://localhost:8073', {
  username: 'emqx',
  password: 'emqxpass'
});

client.on('connect', () => {
  console.log('Conectado al broker MQTT');
  client.subscribe('smartlabs/+/data');
});
```

### Conexión a Base de Datos

```php
// Configuración PHP
$host = 'localhost';
$port = 4000;
$database = 'emqx';
$username = 'admin_iotcurso';
$password = '18196';

$pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
```

## Seguridad

### Recomendaciones

1. **Cambiar contraseñas por defecto** en producción
2. **Configurar SSL/TLS** para conexiones seguras
3. **Implementar firewall** para restringir acceso a puertos
4. **Actualizar regularmente** las imágenes Docker
5. **Monitorear logs** para detectar actividad sospechosa

### Configuración de SSL

Para habilitar SSL en EMQX, coloque los certificados en:
- `vol-emqx-etc/certs/cert.pem`
- `vol-emqx-etc/certs/key.pem`

## Troubleshooting

### Problemas Comunes

#### Error de conexión a MariaDB
```bash
# Verificar que el contenedor esté ejecutándose
sudo docker-compose ps mariadb

# Revisar logs de MariaDB
sudo docker-compose logs mariadb
```

#### EMQX no inicia
```bash
# Verificar configuración
sudo docker-compose config

# Revisar logs de EMQX
sudo docker-compose logs emqx
```

#### Problemas de permisos
```bash
# Verificar permisos de volúmenes
sudo docker volume inspect docker_smartlabs_vol-emqx-data
```

## Desarrollo y Contribución

### Estructura del Proyecto

```
DOCKER_SMARTLABS/
├── docker-compose.yaml    # Configuración de servicios
├── .env                   # Variables de entorno
├── db/
│   └── init.sql          # Script de inicialización de BD
└── README.md             # Esta documentación
```

### Contribuir

1. Fork del repositorio
2. Crear rama para nueva funcionalidad
3. Realizar cambios y pruebas
4. Enviar Pull Request

## Licencia

Este proyecto está bajo la licencia MIT. Ver archivo LICENSE para más detalles.

## Soporte

Para soporte técnico o reportar problemas:
- **Email**: josebalbuena181096@gmail.com
- **Issues**: GitHub Issues del repositorio

## Changelog

### v1.0.0
- Configuración inicial de EMQX 4.4.19
- Integración con MariaDB
- Scripts de inicialización de base de datos
- Configuración de phpMyAdmin
- Documentación completa

---

**Desarrollado por**: José Ángel Balbuena Palma  
**Institución**: Tecnológico de Monterrey  
**Proyecto**: SMARTLABS IoT Infrastructure
