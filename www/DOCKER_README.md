# SMARTLABS Ecosystem - Containerización Completa

Este documento describe cómo desplegar el ecosistema completo de SMARTLABS utilizando Docker y Docker Compose.

## 🏗️ Arquitectura del Sistema

El ecosistema SMARTLABS containerizado incluye:

### Servicios Principales
- **Aplicación Web PHP** - Interfaz principal del sistema
- **API Flutter** - API REST para aplicaciones móviles
- **Monitor de Dispositivos** - Servicio WebSocket para monitoreo en tiempo real
- **Base de Datos MariaDB** - Almacenamiento de datos
- **Broker EMQX** - Comunicación MQTT con dispositivos IoT
- **Nginx** - Reverse proxy y balanceador de carga
- **phpMyAdmin** - Administración de base de datos

### Servicios Opcionales (Monitoreo)
- **Prometheus** - Métricas del sistema
- **Grafana** - Dashboards de monitoreo
- **Fluentd** - Centralización de logs

## 🚀 Inicio Rápido

### Prerrequisitos
- Docker Desktop instalado y ejecutándose
- Al menos 4GB de RAM disponible
- Puertos 8080, 4000, 4001, 18083 disponibles

### Despliegue Automático

```bash
# Ejecutar el script de inicio (incluye backup completo)
.\start-smartlabs.bat
```

### Restauración con Backup Completo

```bash
# Restaurar base de datos con datos completos
.\restore-backup.bat
```

### Despliegue Manual

```bash
# 1. Construir las imágenes
docker-compose build

# 2. Iniciar servicios base
docker-compose up -d mariadb emqx

# 3. Esperar 30 segundos para inicialización

# 4. Iniciar todos los servicios
docker-compose up -d
```

## 🌐 Acceso a Servicios

| Servicio | URL | Credenciales |
|----------|-----|-------------|
| **Aplicación Web** | http://localhost:8080 | - |
| **API Flutter** | http://localhost:8080/api | - |
| **WebSocket Monitor** | ws://localhost:8080/ws | - |
| **phpMyAdmin** | http://localhost:4001 | admin_iotcurso / iotcurso2024 |
| **EMQX Dashboard** | http://localhost:18083 | admin / smartlabs2024 |
| **Grafana** | http://localhost:3001 | admin / smartlabs2024 |
| **Prometheus** | http://localhost:9090 | - |

## 📁 Estructura del Proyecto

```
c:\laragon\www\
├── docker-compose.yml          # Configuración principal
├── .env                        # Variables de entorno
├── start-smartlabs.bat        # Script de inicio
├── stop-smartlabs.bat         # Script de parada
├── DOCKER_README.md           # Esta documentación
│
├── app/                       # Aplicación web PHP
├── public/                    # Archivos públicos
├── config/                    # Configuraciones
├── node/                      # Servicio de monitoreo
├── flutter-api/               # API para Flutter
├── DOCKER_SMARTLABS-main/     # Configuración original
│
└── docker/                    # Configuraciones Docker
    ├── web/
    │   ├── Dockerfile
    │   ├── apache.conf
    │   └── php.ini
    ├── api/
    │   └── Dockerfile
    ├── monitor/
    │   └── Dockerfile
    └── nginx/
        ├── nginx.conf
        └── conf.d/
            └── smartlabs.conf
```

## 💾 Base de Datos y Backup

### Backup Completo Incluido

El sistema incluye un **backup completo** (`docker/backup_completo2025.sql`) con:
- **401 tarjetas RFID** registradas y asignadas
- **Usuarios del sistema** con credenciales
- **Dispositivos IoT** configurados
- **Datos históricos** de sensores y tráfico
- **Equipos y préstamos** del laboratorio
- **Configuración MQTT** para autenticación
- **Vistas SQL** optimizadas para consultas

### Restauración de Datos

```bash
# Restauración automática (recomendado)
.\restore-backup.bat

# Restauración manual
docker-compose down -v
docker-compose up -d mariadb
# Esperar 45 segundos para la carga completa
```

**Nota:** La primera carga puede tardar 1-2 minutos debido al tamaño del backup (58,733 líneas).

## 🔧 Configuración

### Variables de Entorno

Las principales variables se configuran en `.env`:

```env
# Base de datos
MARIADB_ROOT_PASSWORD=smartlabs_root_2024
MARIADB_USER=smartlabs_user
MARIADB_PASSWORD=smartlabs_secure_2024
MARIADB_DATABASE=smartlabs_db

# MQTT
EMQX_DASHBOARD_PASSWORD=smartlabs2024
MQTT_USERNAME=smartlabs_mqtt
MQTT_PASSWORD=mqtt_secure_2024

# API
JWT_SECRET=smartlabs_jwt_secret_key_2024
```

### Puertos Utilizados

| Puerto | Servicio | Descripción |
|--------|----------|-------------|
| 8080 | Nginx | Proxy principal |
| 4000 | MariaDB | Base de datos |
| 4001 | phpMyAdmin | Admin DB |
| 18083 | EMQX Dashboard | Admin MQTT |
| 1883 | EMQX MQTT | Broker MQTT |
| 8883 | EMQX MQTT SSL | Broker MQTT seguro |
| 8081 | EMQX WebSocket | WebSocket MQTT |
| 3001 | Grafana | Monitoreo |
| 9090 | Prometheus | Métricas |

## 🛠️ Comandos Útiles

### Gestión de Servicios

```bash
# Ver estado de servicios
docker-compose ps

# Ver logs de todos los servicios
docker-compose logs -f

# Ver logs de un servicio específico
docker-compose logs -f web
docker-compose logs -f flutter-api
docker-compose logs -f device-monitor

# Reiniciar un servicio
docker-compose restart web

# Detener todos los servicios
docker-compose down

# Detener y eliminar volúmenes
docker-compose down -v
```

### Mantenimiento

```bash
# Reconstruir imágenes
docker-compose build --no-cache

# Limpiar sistema Docker
docker system prune

# Ver uso de recursos
docker stats

# Acceder a un contenedor
docker-compose exec smartlabs-web bash
docker-compose exec smartlabs-mariadb mysql -u root -p
```

### Gestión de Backup

```bash
# Crear nuevo backup
docker-compose exec smartlabs-mariadb mysqldump -u root -p --all-databases > nuevo_backup.sql

# Verificar datos del backup actual
docker-compose exec smartlabs-mariadb mysql -u smartlabs_user -p smartlabs_db -e "SELECT COUNT(*) as total_cards FROM cards;"
docker-compose exec smartlabs-mariadb mysql -u smartlabs_user -p smartlabs_db -e "SELECT COUNT(*) as total_users FROM users;"

# Restaurar desde backup específico
docker cp mi_backup.sql smartlabs-mariadb:/tmp/
docker-compose exec smartlabs-mariadb mysql -u root -p < /tmp/mi_backup.sql
```

## 🔍 Monitoreo y Logs

### Health Checks
Todos los servicios incluyen health checks automáticos:

```bash
# Ver estado de salud
docker-compose ps
```

### Logs Centralizados
Los logs se almacenan en:
- `/var/log/nginx/` - Logs de Nginx
- `/app/logs/` - Logs de aplicaciones
- Docker logs accesibles via `docker-compose logs`

### Métricas con Prometheus
Prometheus recolecta métricas de:
- Contenedores Docker
- Nginx (requests, response times)
- Base de datos MariaDB
- Aplicaciones Node.js

## 🚨 Solución de Problemas

### Problemas Comunes

1. **Puerto en uso**
   ```bash
   # Verificar puertos ocupados
   netstat -an | findstr :8080
   ```

2. **Servicios no inician**
   ```bash
   # Verificar logs
   docker-compose logs [servicio]
   ```

3. **Base de datos no conecta**
   ```bash
   # Verificar estado de MariaDB
   docker-compose exec mariadb mysql -u root -p -e "SHOW DATABASES;"
   ```

4. **Memoria insuficiente**
   ```bash
   # Verificar uso de recursos
   docker stats
   ```

### Reinicio Completo

```bash
# Parada completa
.\stop-smartlabs.bat

# Limpiar volúmenes (CUIDADO: elimina datos)
docker-compose down -v

# Limpiar imágenes
docker system prune -a

# Inicio limpio
.\start-smartlabs.bat
```

## 🔒 Seguridad

### Configuraciones de Seguridad
- Usuarios no-root en contenedores
- Headers de seguridad en Nginx
- Rate limiting configurado
- Secrets en variables de entorno
- Red interna para comunicación entre servicios

### Recomendaciones de Producción
- Cambiar todas las contraseñas por defecto
- Configurar HTTPS con certificados válidos
- Implementar backup automático de base de datos
- Configurar monitoreo de seguridad
- Usar Docker secrets para credenciales

## 📊 Escalabilidad

### Escalado Horizontal
```bash
# Escalar servicios específicos
docker-compose up -d --scale flutter-api=3
docker-compose up -d --scale device-monitor=2
```

### Optimización de Recursos
- Configurar límites de memoria y CPU
- Usar multi-stage builds para imágenes más pequeñas
- Implementar caché de aplicaciones
- Configurar load balancing en Nginx

## 🤝 Contribución

Para contribuir al proyecto:
1. Fork del repositorio
2. Crear rama de feature
3. Realizar cambios
4. Probar con `docker-compose up`
5. Crear Pull Request

## 📞 Soporte

Para soporte técnico:
- Revisar logs: `docker-compose logs -f`
- Verificar configuración en `.env`
- Consultar documentación de servicios individuales
- Crear issue en el repositorio del proyecto