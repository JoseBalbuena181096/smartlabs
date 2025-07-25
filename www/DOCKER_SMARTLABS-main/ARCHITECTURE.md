# Arquitectura Técnica - SMARTLABS Docker Infrastructure

## Visión General de la Arquitectura

La infraestructura SMARTLABS está diseñada como un sistema distribuido basado en contenedores Docker que proporciona servicios IoT, gestión de datos y comunicación en tiempo real para laboratorios inteligentes.

## Diagrama de Arquitectura Completa

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           SMARTLABS ECOSYSTEM                                  │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐            │
│  │   Web Client    │    │  Mobile Apps    │    │   IoT Devices   │            │
│  │   (Browser)     │    │   (Flutter)     │    │   (ESP32/etc)   │            │
│  └─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘            │
│            │                      │                      │                    │
│            │ HTTP/WebSocket       │ HTTP/WebSocket       │ MQTT/WebSocket     │
│            │                      │                      │                    │
│  ┌─────────▼──────────────────────▼──────────────────────▼───────┐            │
│  │                    DOCKER NETWORK (iot_host)                   │            │
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
└─────────────────────────────────────────────────────────────────────────────────┘
```

## Componentes del Sistema

### 1. EMQX Broker (Núcleo de Comunicación)

#### Responsabilidades
- **Broker MQTT**: Gestión de mensajes IoT
- **WebSocket Gateway**: Comunicación en tiempo real con aplicaciones web
- **Autenticación**: Validación de dispositivos y usuarios
- **Dashboard**: Interfaz de monitoreo y administración
- **API REST**: Endpoints para gestión programática

#### Configuración Técnica
```yaml
Puertos Expuestos:
  - 1883: MQTT TCP
  - 8883: MQTT SSL/TLS
  - 8073: WebSocket
  - 8074: WebSocket Secure
  - 18083: Dashboard HTTP
  - 18084: Dashboard HTTPS
  - 8081: Management API HTTP
  - 8082: Management API HTTPS

Plugins Habilitados:
  - emqx_auth_mysql: Autenticación basada en MySQL
  - emqx_dashboard: Interfaz web de administración
  - emqx_management: API de gestión
  - emqx_retainer: Retención de mensajes
  - emqx_recon: Reconexión automática

Límites de Conexión:
  - TCP: 1000 conexiones simultáneas
  - SSL: 1000 conexiones simultáneas
  - WebSocket: 1000 conexiones simultáneas
  - WebSocket Secure: 1000 conexiones simultáneas
```

### 2. MariaDB (Capa de Persistencia)

#### Responsabilidades
- **Almacenamiento de datos**: Usuarios, dispositivos, tráfico
- **Autenticación MQTT**: Credenciales y ACL
- **Logs del sistema**: Registro de actividades
- **Configuración**: Parámetros del sistema

#### Esquema de Base de Datos

##### Tablas de Autenticación MQTT
```sql
-- Usuarios del broker MQTT
mqtt_user (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(100) UNIQUE,
  password VARCHAR(100),
  salt VARCHAR(35),
  is_superuser TINYINT(1),
  created DATETIME
)

-- Control de acceso MQTT
mqtt_acl (
  id INT PRIMARY KEY AUTO_INCREMENT,
  allow INT DEFAULT 0,
  ipaddr VARCHAR(60),
  username VARCHAR(100),
  clientid VARCHAR(100),
  access INT DEFAULT 3,
  topic VARCHAR(100)
)
```

##### Tablas del Sistema SMARTLABS
```sql
-- Usuarios del sistema web
users (
  users_id INT PRIMARY KEY AUTO_INCREMENT,
  users_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  users_email VARCHAR(60) NOT NULL,
  users_password VARCHAR(60) NOT NULL
)

-- Dispositivos IoT registrados
devices (
  devices_id INT PRIMARY KEY AUTO_INCREMENT,
  devices_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  devices_alias VARCHAR(50) NOT NULL,
  devices_serie VARCHAR(50) NOT NULL,
  devices_user_id INT NOT NULL
)

-- Habitantes/estudiantes del laboratorio
habintants (
  hab_id INT PRIMARY KEY AUTO_INCREMENT,
  hab_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  hab_name VARCHAR(50) NOT NULL,
  hab_registration VARCHAR(50) UNIQUE,
  hab_email VARCHAR(50) NOT NULL,
  hab_card_id INT NOT NULL,
  hab_device_id INT NOT NULL
)

-- Registro de accesos a dispositivos
traffic (
  traffic_id INT PRIMARY KEY AUTO_INCREMENT,
  traffic_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  traffic_hab_id INT NOT NULL,
  traffic_device VARCHAR(50) NOT NULL,
  traffic_state BOOLEAN NOT NULL
)
```

##### Vistas del Sistema
```sql
-- Vista combinada de tráfico y usuarios
CREATE VIEW traffic_devices AS
SELECT 
  t.traffic_id,
  t.traffic_date,
  t.traffic_hab_id,
  t.traffic_device,
  t.traffic_state,
  h.hab_name,
  h.hab_registration,
  h.hab_email,
  h.hab_device_id
FROM traffic t
JOIN habintants h ON h.hab_id = t.traffic_hab_id
ORDER BY t.traffic_id DESC;
```

### 3. phpMyAdmin (Herramienta de Administración)

#### Responsabilidades
- **Gestión de base de datos**: Interface web para administración
- **Monitoreo**: Visualización de datos y estadísticas
- **Backup/Restore**: Herramientas de respaldo
- **Desarrollo**: Entorno para pruebas y desarrollo

## Flujos de Datos

### 1. Flujo de Autenticación de Dispositivos IoT

```
1. Dispositivo IoT → EMQX (Puerto 1883)
   ├─ Credenciales: username/password
   └─ Client ID único

2. EMQX → MariaDB
   ├─ Query: SELECT * FROM mqtt_user WHERE username = ?
   └─ Validación de contraseña (hash)

3. MariaDB → EMQX
   ├─ Resultado de autenticación
   └─ Permisos de usuario (is_superuser)

4. EMQX → Dispositivo IoT
   ├─ CONNACK (Connection Acknowledged)
   └─ Establecimiento de sesión
```

### 2. Flujo de Publicación de Datos

```
1. Dispositivo IoT → EMQX
   ├─ Topic: smartlabs/{device_id}/data
   ├─ Payload: JSON con datos del sensor
   └─ QoS: 1 (At least once)

2. EMQX → Validación ACL
   ├─ Query: mqtt_acl table
   ├─ Verificar permisos de publicación
   └─ Autorizar/Denegar

3. EMQX → Suscriptores
   ├─ Aplicaciones web (WebSocket)
   ├─ Aplicaciones móviles
   └─ Otros dispositivos

4. Aplicación → MariaDB
   ├─ INSERT INTO traffic (...)
   ├─ INSERT INTO data (...)
   └─ Persistencia de datos
```

### 3. Flujo de Acceso Web

```
1. Cliente Web → EMQX Dashboard (Puerto 18083)
   ├─ Autenticación: admin/emqxpass
   └─ Acceso a interfaz de administración

2. Cliente Web → phpMyAdmin (Puerto 4001)
   ├─ Conexión a MariaDB
   ├─ Gestión de datos
   └─ Monitoreo de sistema

3. Aplicación SMARTLABS → EMQX (WebSocket)
   ├─ Puerto 8073 (WS) / 8074 (WSS)
   ├─ Suscripción a topics
   └─ Recepción de datos en tiempo real
```

## Configuración de Red Docker

### Red Bridge Personalizada

```yaml
networks:
  iot:
    name: iot_host
    driver: bridge
    
Aliases de Red:
  - mariadb_host → Contenedor MariaDB
  - emqx_host → Contenedor EMQX
  - phpmyadmin_host → Contenedor phpMyAdmin
```

### Comunicación Inter-Contenedores

```
EMQX ←→ MariaDB:
  - Host: mariadb_host:3306
  - Protocolo: MySQL/TCP
  - Autenticación: emqxuser/emqxpass
  - Base de datos: emqx

phpMyAdmin ←→ MariaDB:
  - Host: mariadb_host:3306
  - Protocolo: MySQL/TCP
  - Interface: Web HTTP
```

## Volúmenes Persistentes

### Configuración de Almacenamiento

```yaml
volumes:
  # Datos de EMQX
  vol-emqx-data:
    name: foo-emqx-data
    mountpoint: /opt/emqx/data
    
  # Configuración de EMQX
  vol-emqx-etc:
    name: foo-emqx-etc
    mountpoint: /opt/emqx/etc
    
  # Logs de EMQX
  vol-emqx-log:
    name: foo-emqx-log
    mountpoint: /opt/emqx/log
    
  # Datos de MariaDB
  mariadb:
    driver: local
    mountpoint: /var/lib/mysql
```

## Seguridad y Autenticación

### Niveles de Seguridad

#### 1. Autenticación MQTT
```
Nivel 1: Autenticación de Usuario
  - Username/Password en base de datos
  - Hash SHA256 para contraseñas
  - Validación contra tabla mqtt_user

Nivel 2: Control de Acceso (ACL)
  - Permisos por topic
  - Restricciones por IP
  - Control de publicación/suscripción
```

#### 2. Autenticación Web
```
EMQX Dashboard:
  - Usuario: admin
  - Contraseña: emqxpass
  - Acceso: Puerto 18083/18084

phpMyAdmin:
  - Usuario: root / admin_iotcurso
  - Contraseña: emqxpass / 18196
  - Acceso: Puerto 4001
```

#### 3. Configuración SSL/TLS
```
Certificados requeridos:
  - /opt/emqx/etc/certs/cert.pem
  - /opt/emqx/etc/certs/key.pem

Puertos seguros:
  - 8883: MQTT over SSL
  - 8074: WebSocket Secure
  - 18084: Dashboard HTTPS
  - 8082: Management API HTTPS
```

## Monitoreo y Observabilidad

### Métricas del Sistema

#### EMQX Metrics
```
Conexiones:
  - Conexiones activas
  - Conexiones por segundo
  - Desconexiones

Mensajes:
  - Mensajes publicados
  - Mensajes entregados
  - Mensajes retenidos
  - Cola de mensajes

Sesiones:
  - Sesiones activas
  - Sesiones persistentes
  - Suscripciones activas
```

#### MariaDB Metrics
```
Rendimiento:
  - Consultas por segundo
  - Conexiones activas
  - Tiempo de respuesta

Almacenamiento:
  - Tamaño de base de datos
  - Espacio libre
  - Fragmentación de tablas

Replicación:
  - Estado de replicación
  - Lag de replicación
  - Errores de replicación
```

### Logs del Sistema

#### Ubicaciones de Logs
```
EMQX Logs:
  - /opt/emqx/log/emqx.log.1
  - /opt/emqx/log/error.log
  - /opt/emqx/log/crash.log

MariaDB Logs:
  - /var/log/mysql/error.log
  - /var/log/mysql/mysql.log
  - /var/log/mysql/slow.log

Docker Logs:
  - docker-compose logs emqx
  - docker-compose logs mariadb
  - docker-compose logs phpmyadmin
```

## Escalabilidad y Rendimiento

### Optimizaciones Actuales

#### EMQX Configuration
```
Límites de Conexión:
  - listener.tcp.external.max_connections = 1000
  - listener.ssl.external.max_connections = 1000
  - listener.ws.external.max_connections = 1000
  - listener.wss.external.max_connections = 1000

Dashboard Limits:
  - dashboard.listener.http.max_clients = 2
  - dashboard.listener.https.max_clients = 10
  - dashboard.listener.https.acceptors = 10
```

#### MariaDB Tuning
```
Configuración de Memoria:
  - innodb_buffer_pool_size = 128M
  - query_cache_size = 16M
  - tmp_table_size = 32M

Configuración de Conexiones:
  - max_connections = 100
  - connect_timeout = 10
  - wait_timeout = 600
```

### Estrategias de Escalamiento

#### Escalamiento Horizontal
```
EMQX Cluster:
  - Múltiples nodos EMQX
  - Load balancer (HAProxy/Nginx)
  - Shared storage para configuración

MariaDB Cluster:
  - Master-Slave replication
  - Galera Cluster
  - Read replicas
```

#### Escalamiento Vertical
```
Recursos de Contenedor:
  - CPU: 2-4 cores por servicio
  - RAM: 2-8GB por servicio
  - Storage: SSD para mejor rendimiento
```

## Backup y Recuperación

### Estrategia de Backup

#### Backup Automático
```bash
#!/bin/bash
# Script de backup automático

# Backup de MariaDB
docker-compose exec mariadb mysqldump -u root -pemqxpass emqx > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup de volúmenes EMQX
docker run --rm -v foo-emqx-data:/data -v $(pwd):/backup alpine tar czf /backup/emqx_data_$(date +%Y%m%d_%H%M%S).tar.gz -C /data .

# Backup de configuración
cp docker-compose.yaml backup/
cp .env backup/
```

#### Recuperación
```bash
# Restaurar base de datos
docker-compose exec -T mariadb mysql -u root -pemqxpass emqx < backup.sql

# Restaurar volúmenes EMQX
docker run --rm -v foo-emqx-data:/data -v $(pwd):/backup alpine tar xzf /backup/emqx_data.tar.gz -C /data
```

## Troubleshooting Avanzado

### Diagnóstico de Problemas

#### Problemas de Conectividad MQTT
```bash
# Verificar conectividad MQTT
mosquitto_pub -h localhost -p 1883 -u emqx -P emqxpass -t test/topic -m "Hello World"

# Monitorear conexiones
docker-compose exec emqx emqx_ctl clients list

# Verificar suscripciones
docker-compose exec emqx emqx_ctl subscriptions list
```

#### Problemas de Base de Datos
```bash
# Verificar estado de MariaDB
docker-compose exec mariadb mysqladmin -u root -pemqxpass status

# Verificar tablas
docker-compose exec mariadb mysql -u root -pemqxpass -e "SHOW TABLES;" emqx

# Verificar logs de errores
docker-compose logs mariadb | grep ERROR
```

#### Problemas de Rendimiento
```bash
# Monitorear recursos
docker stats

# Verificar logs de EMQX
docker-compose logs emqx | grep -i "overload\|error\|warning"

# Verificar procesos de MariaDB
docker-compose exec mariadb mysql -u root -pemqxpass -e "SHOW PROCESSLIST;"
```

## Roadmap Técnico

### Mejoras a Corto Plazo
- [ ] Implementación de SSL/TLS completo
- [ ] Configuración de clustering EMQX
- [ ] Optimización de consultas de base de datos
- [ ] Implementación de métricas con Prometheus

### Mejoras a Mediano Plazo
- [ ] Migración a Kubernetes
- [ ] Implementación de CI/CD
- [ ] Monitoreo con Grafana
- [ ] Backup automático a cloud storage

### Mejoras a Largo Plazo
- [ ] Microservicios con API Gateway
- [ ] Machine Learning para análisis predictivo
- [ ] Edge computing para dispositivos IoT
- [ ] Blockchain para trazabilidad

---

**Documento técnico desarrollado por**: José Ángel Balbuena Palma  
**Última actualización**: $(date +%Y-%m-%d)  
**Versión**: 1.0.0