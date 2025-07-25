# Guía de Despliegue - SMARTLABS Docker Infrastructure

## Tabla de Contenidos

1. [Requisitos del Sistema](#requisitos-del-sistema)
2. [Instalación en Desarrollo](#instalación-en-desarrollo)
3. [Instalación en Producción](#instalación-en-producción)
4. [Configuración Avanzada](#configuración-avanzada)
5. [Monitoreo y Mantenimiento](#monitoreo-y-mantenimiento)
6. [Backup y Recuperación](#backup-y-recuperación)
7. [Troubleshooting](#troubleshooting)
8. [Checklist de Despliegue](#checklist-de-despliegue)

## Requisitos del Sistema

### Requisitos Mínimos

#### Hardware
```
CPU: 2 cores (2.0 GHz)
RAM: 4 GB
Almacenamiento: 20 GB SSD
Red: 100 Mbps
```

#### Hardware Recomendado
```
CPU: 4 cores (2.5 GHz)
RAM: 8 GB
Almacenamiento: 50 GB SSD
Red: 1 Gbps
```

#### Software
```
Sistema Operativo: Ubuntu 20.04 LTS o superior
Docker: 20.10.0 o superior
Docker Compose: 1.28.0 o superior
Puertos disponibles: 1883, 4000, 4001, 8073, 8074, 8081, 8082, 8883, 18083, 18084
```

### Verificación de Requisitos

```bash
#!/bin/bash
# Script de verificación de requisitos

echo "=== Verificación de Requisitos SMARTLABS ==="

# Verificar sistema operativo
echo "Sistema Operativo:"
lsb_release -a

# Verificar recursos
echo "\nRecursos del Sistema:"
echo "CPU Cores: $(nproc)"
echo "RAM Total: $(free -h | awk '/^Mem:/ {print $2}')"
echo "Espacio en Disco: $(df -h / | awk 'NR==2 {print $4}')"

# Verificar Docker
echo "\nDocker:"
docker --version
docker-compose --version

# Verificar puertos
echo "\nPuertos disponibles:"
for port in 1883 4000 4001 8073 8074 8081 8082 8883 18083 18084; do
    if ! netstat -tuln | grep -q ":$port "; then
        echo "Puerto $port: DISPONIBLE"
    else
        echo "Puerto $port: EN USO"
    fi
done

echo "\n=== Verificación Completada ==="
```

## Instalación en Desarrollo

### 1. Preparación del Entorno

#### Instalación de Docker en Ubuntu

```bash
#!/bin/bash
# Script de instalación de Docker

# Actualizar sistema
sudo apt update
sudo apt upgrade -y

# Instalar dependencias
sudo apt install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release

# Agregar clave GPG de Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Agregar repositorio de Docker
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Instalar Docker
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io

# Agregar usuario al grupo docker
sudo usermod -aG docker $USER

# Instalar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verificar instalación
docker --version
docker-compose --version

echo "Docker instalado correctamente. Reinicie la sesión para aplicar cambios."
```

### 2. Configuración del Proyecto

#### Clonar y Configurar

```bash
# Clonar repositorio
git clone https://github.com/JoseBalbuena181096/DOCKER_SMARTLABS.git
cd DOCKER_SMARTLABS

# Verificar archivos
ls -la

# Verificar configuración
cat .env
cat docker-compose.yaml
```

#### Configuración de Variables de Entorno

```bash
# Crear archivo .env personalizado para desarrollo
cp .env .env.dev

# Editar configuración de desarrollo
nano .env.dev
```

**Configuración de desarrollo (.env.dev):**
```env
# Configuración de MariaDB para desarrollo
MARIADB_ROOT_PASSWORD=dev_password_123
MARIADB_USER=dev_user
MARIADB_PASSWORD=dev_password_123
MARIADB_DATABASE=emqx_dev

# Zona horaria
TZ=America/Mexico_City

# Configuración de desarrollo
ENVIRONMENT=development
DEBUG=true
```

### 3. Despliegue en Desarrollo

```bash
# Usar archivo de entorno de desarrollo
cp .env.dev .env

# Iniciar servicios
docker-compose up -d

# Verificar estado
docker-compose ps

# Ver logs
docker-compose logs -f
```

## Instalación en Producción

### 1. Preparación del Servidor

#### Configuración de Seguridad

```bash
#!/bin/bash
# Script de configuración de seguridad para producción

# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Configurar firewall
sudo ufw enable
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Permitir SSH
sudo ufw allow ssh

# Permitir puertos de SMARTLABS
sudo ufw allow 1883/tcp    # MQTT
sudo ufw allow 8883/tcp    # MQTTS
sudo ufw allow 8073/tcp    # WebSocket
sudo ufw allow 8074/tcp    # WebSocket Secure
sudo ufw allow 18083/tcp   # EMQX Dashboard
sudo ufw allow 18084/tcp   # EMQX Dashboard HTTPS

# Puertos de administración (restringir por IP si es necesario)
sudo ufw allow from 192.168.1.0/24 to any port 4000  # MariaDB
sudo ufw allow from 192.168.1.0/24 to any port 4001  # phpMyAdmin

# Verificar reglas
sudo ufw status verbose

# Configurar fail2ban
sudo apt install -y fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

#### Configuración de Límites del Sistema

```bash
# Configurar límites para Docker
echo "* soft nofile 65536" | sudo tee -a /etc/security/limits.conf
echo "* hard nofile 65536" | sudo tee -a /etc/security/limits.conf
echo "root soft nofile 65536" | sudo tee -a /etc/security/limits.conf
echo "root hard nofile 65536" | sudo tee -a /etc/security/limits.conf

# Configurar parámetros del kernel
echo "vm.max_map_count=262144" | sudo tee -a /etc/sysctl.conf
echo "net.core.somaxconn=65535" | sudo tee -a /etc/sysctl.conf
echo "net.ipv4.tcp_max_syn_backlog=65535" | sudo tee -a /etc/sysctl.conf

# Aplicar cambios
sudo sysctl -p
```

### 2. Configuración de Producción

#### Variables de Entorno de Producción

```env
# .env.prod - Configuración de producción

# Configuración de MariaDB
MARIADB_ROOT_PASSWORD=SUPER_SECURE_PASSWORD_2024!
MARIADB_USER=smartlabs_user
MARIADB_PASSWORD=SECURE_USER_PASSWORD_2024!
MARIADB_DATABASE=smartlabs_prod

# Zona horaria
TZ=America/Mexico_City

# Configuración de producción
ENVIRONMENT=production
DEBUG=false

# Configuración SSL (opcional)
SSL_CERT_PATH=/opt/ssl/cert.pem
SSL_KEY_PATH=/opt/ssl/key.pem
```

#### Docker Compose para Producción

```yaml
# docker-compose.prod.yaml
version: '3.8'

volumes:
  vol-emqx-data:
    name: smartlabs-emqx-data
  vol-emqx-etc:
    name: smartlabs-emqx-etc
  vol-emqx-log:
    name: smartlabs-emqx-log
  mariadb:
    driver: local

networks:
  smartlabs:
    name: smartlabs_network
    driver: bridge

services:
  mariadb:
    container_name: smartlabs-mariadb
    image: mariadb:10.8
    restart: unless-stopped
    environment:
      TZ: ${TZ}
      MYSQL_ROOT_PASSWORD: ${MARIADB_ROOT_PASSWORD}
      MYSQL_USER: ${MARIADB_USER}
      MYSQL_PASSWORD: ${MARIADB_PASSWORD}
      MYSQL_DATABASE: ${MARIADB_DATABASE}
    ports:
      - "127.0.0.1:4000:3306"  # Bind solo a localhost
    volumes:
      - mariadb:/var/lib/mysql
      - ./db:/docker-entrypoint-initdb.d
      - ./config/mariadb:/etc/mysql/conf.d
    networks:
      smartlabs:
        aliases:
          - mariadb_host
    deploy:
      resources:
        limits:
          memory: 2G
          cpus: '1.0'
        reservations:
          memory: 1G
          cpus: '0.5'

  emqx:
    container_name: smartlabs-emqx
    image: emqx/emqx:4.4.19
    depends_on:
      - mariadb
    restart: unless-stopped
    ports:
      - "1883:1883"    # MQTT
      - "8883:8883"    # MQTTS
      - "8073:8083"    # WebSocket
      - "8074:8084"    # WebSocket Secure
      - "18083:18083"  # Dashboard
      - "18084:18084"  # Dashboard HTTPS
      - "8081:8081"    # Management API
      - "8082:8082"    # Management API HTTPS
    volumes:
      - vol-emqx-data:/opt/emqx/data
      - vol-emqx-etc:/opt/emqx/etc
      - vol-emqx-log:/opt/emqx/log
      - ./ssl:/opt/emqx/etc/certs  # Certificados SSL
    environment:
      TZ: ${TZ}
      EMQX_NAME: smartlabs-prod
      EMQX_HOST: 0.0.0.0
      EMQX_DASHBOARD__DEFAULT_USER__PASSWORD: ${MARIADB_ROOT_PASSWORD}
      EMQX_ALLOW_ANONYMOUS: "false"
      EMQX_NOMATCH: "deny"
      EMQX_AUTH__MYSQL__SERVER: "mariadb_host:3306"
      EMQX_AUTH__MYSQL__USERNAME: ${MARIADB_USER}
      EMQX_AUTH__MYSQL__PASSWORD: ${MARIADB_PASSWORD}
      EMQX_AUTH__MYSQL__DATABASE: ${MARIADB_DATABASE}
      EMQX_LOADED_PLUGINS: "emqx_recon,emqx_retainer,emqx_management,emqx_dashboard,emqx_auth_mysql"
    networks:
      smartlabs:
        aliases:
          - emqx_host
    deploy:
      resources:
        limits:
          memory: 2G
          cpus: '2.0'
        reservations:
          memory: 1G
          cpus: '1.0'

  # phpMyAdmin solo para administración (opcional en producción)
  phpmyadmin:
    container_name: smartlabs-phpmyadmin
    image: phpmyadmin:latest
    depends_on:
      - mariadb
    restart: unless-stopped
    ports:
      - "127.0.0.1:4001:80"  # Solo acceso local
    environment:
      TZ: ${TZ}
      PMA_HOST: mariadb_host
      PMA_PORT: 3306
      PMA_ABSOLUTE_URI: http://localhost:4001/
    networks:
      smartlabs:
        aliases:
          - phpmyadmin_host
    profiles:
      - admin  # Solo se inicia con --profile admin
```

### 3. Despliegue en Producción

```bash
#!/bin/bash
# Script de despliegue en producción

# Configurar variables
cp .env.prod .env

# Crear directorios necesarios
mkdir -p ssl config/mariadb logs

# Configurar permisos
sudo chown -R $USER:docker .
sudo chmod 600 .env

# Iniciar servicios de producción
docker-compose -f docker-compose.prod.yaml up -d

# Verificar estado
docker-compose -f docker-compose.prod.yaml ps

# Verificar logs
docker-compose -f docker-compose.prod.yaml logs --tail=50

echo "Despliegue completado. Verificar servicios en:"
echo "- EMQX Dashboard: http://localhost:18083"
echo "- MQTT Broker: localhost:1883"
echo "- WebSocket: localhost:8073"
```

## Configuración Avanzada

### 1. Configuración SSL/TLS

#### Generar Certificados SSL

```bash
#!/bin/bash
# Generar certificados SSL autofirmados

mkdir -p ssl
cd ssl

# Generar clave privada
openssl genrsa -out key.pem 2048

# Generar certificado
openssl req -new -x509 -key key.pem -out cert.pem -days 365 -subj "/C=MX/ST=NL/L=Monterrey/O=SMARTLABS/CN=localhost"

# Configurar permisos
chmod 600 key.pem
chmod 644 cert.pem

echo "Certificados SSL generados en ./ssl/"
```

#### Configuración EMQX con SSL

```bash
# Configurar EMQX para usar SSL
cat > config/emqx/ssl.conf << EOF
## SSL/TLS Configuration
listener.ssl.external = 8883
listener.ssl.external.keyfile = etc/certs/key.pem
listener.ssl.external.certfile = etc/certs/cert.pem
listener.ssl.external.verify = verify_none
listener.ssl.external.fail_if_no_peer_cert = false

## WebSocket SSL
listener.wss.external = 8074
listener.wss.external.keyfile = etc/certs/key.pem
listener.wss.external.certfile = etc/certs/cert.pem
EOF
```

### 2. Configuración de Backup Automático

#### Script de Backup

```bash
#!/bin/bash
# backup.sh - Script de backup automático

BACKUP_DIR="/opt/smartlabs/backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Crear directorio de backup
mkdir -p $BACKUP_DIR

# Backup de base de datos
echo "Iniciando backup de base de datos..."
docker-compose exec -T mariadb mysqldump -u root -p$MARIADB_ROOT_PASSWORD $MARIADB_DATABASE > $BACKUP_DIR/db_backup_$DATE.sql

# Backup de volúmenes EMQX
echo "Iniciando backup de datos EMQX..."
docker run --rm -v smartlabs-emqx-data:/data -v $BACKUP_DIR:/backup alpine tar czf /backup/emqx_data_$DATE.tar.gz -C /data .
docker run --rm -v smartlabs-emqx-etc:/data -v $BACKUP_DIR:/backup alpine tar czf /backup/emqx_etc_$DATE.tar.gz -C /data .

# Backup de configuración
echo "Iniciando backup de configuración..."
tar czf $BACKUP_DIR/config_$DATE.tar.gz docker-compose*.yaml .env* config/

# Limpiar backups antiguos
echo "Limpiando backups antiguos..."
find $BACKUP_DIR -name "*_backup_*" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "*_data_*" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "*_etc_*" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "config_*" -mtime +$RETENTION_DAYS -delete

echo "Backup completado: $BACKUP_DIR"
ls -la $BACKUP_DIR/*$DATE*
```

#### Configurar Cron para Backup Automático

```bash
# Agregar tarea cron para backup diario a las 2:00 AM
(crontab -l 2>/dev/null; echo "0 2 * * * /opt/smartlabs/backup.sh >> /var/log/smartlabs-backup.log 2>&1") | crontab -

# Verificar cron
crontab -l
```

### 3. Monitoreo con Prometheus y Grafana

#### Docker Compose para Monitoreo

```yaml
# docker-compose.monitoring.yaml
version: '3.8'

services:
  prometheus:
    image: prom/prometheus:latest
    container_name: smartlabs-prometheus
    ports:
      - "9090:9090"
    volumes:
      - ./monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--web.console.libraries=/etc/prometheus/console_libraries'
      - '--web.console.templates=/etc/prometheus/consoles'
    networks:
      - smartlabs

  grafana:
    image: grafana/grafana:latest
    container_name: smartlabs-grafana
    ports:
      - "3000:3000"
    volumes:
      - grafana_data:/var/lib/grafana
      - ./monitoring/grafana:/etc/grafana/provisioning
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin123
    networks:
      - smartlabs

  node-exporter:
    image: prom/node-exporter:latest
    container_name: smartlabs-node-exporter
    ports:
      - "9100:9100"
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.sysfs=/host/sys'
      - '--collector.filesystem.ignored-mount-points=^/(sys|proc|dev|host|etc)($$|/)'
    networks:
      - smartlabs

volumes:
  prometheus_data:
  grafana_data:

networks:
  smartlabs:
    external: true
    name: smartlabs_network
```

## Monitoreo y Mantenimiento

### 1. Scripts de Monitoreo

#### Health Check Script

```bash
#!/bin/bash
# health_check.sh - Verificación de salud del sistema

echo "=== SMARTLABS Health Check ==="
echo "Fecha: $(date)"
echo

# Verificar contenedores
echo "Estado de Contenedores:"
docker-compose ps
echo

# Verificar conectividad MQTT
echo "Conectividad MQTT:"
if timeout 5 bash -c "</dev/tcp/localhost/1883"; then
    echo "✓ Puerto MQTT 1883: OK"
else
    echo "✗ Puerto MQTT 1883: ERROR"
fi

# Verificar base de datos
echo "\nBase de Datos:"
if docker-compose exec -T mariadb mysql -u root -p$MARIADB_ROOT_PASSWORD -e "SELECT 1;" >/dev/null 2>&1; then
    echo "✓ MariaDB: OK"
else
    echo "✗ MariaDB: ERROR"
fi

# Verificar dashboard EMQX
echo "\nDashboard EMQX:"
if curl -s http://localhost:18083 >/dev/null; then
    echo "✓ Dashboard EMQX: OK"
else
    echo "✗ Dashboard EMQX: ERROR"
fi

# Verificar uso de recursos
echo "\nUso de Recursos:"
docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}"

# Verificar logs de errores
echo "\nErrores Recientes:"
echo "EMQX Errors:"
docker-compose logs --tail=10 emqx 2>/dev/null | grep -i error || echo "No hay errores"
echo "\nMariaDB Errors:"
docker-compose logs --tail=10 mariadb 2>/dev/null | grep -i error || echo "No hay errores"

echo "\n=== Health Check Completado ==="
```

### 2. Alertas y Notificaciones

#### Script de Alertas

```bash
#!/bin/bash
# alerts.sh - Sistema de alertas

ALERT_EMAIL="admin@smartlabs.com"
SMTP_SERVER="smtp.gmail.com"
SMTP_PORT="587"

# Función para enviar alerta
send_alert() {
    local subject="$1"
    local message="$2"
    
    echo "$message" | mail -s "[SMARTLABS ALERT] $subject" $ALERT_EMAIL
    echo "$(date): ALERT - $subject" >> /var/log/smartlabs-alerts.log
}

# Verificar contenedores
for container in smartlabs-emqx smartlabs-mariadb; do
    if ! docker ps | grep -q $container; then
        send_alert "Container Down" "El contenedor $container no está ejecutándose"
    fi
done

# Verificar uso de disco
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    send_alert "Disk Space Warning" "Uso de disco: ${DISK_USAGE}%"
fi

# Verificar memoria
MEM_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ $MEM_USAGE -gt 90 ]; then
    send_alert "Memory Warning" "Uso de memoria: ${MEM_USAGE}%"
fi
```

## Backup y Recuperación

### 1. Estrategia de Backup

#### Backup Completo

```bash
#!/bin/bash
# full_backup.sh - Backup completo del sistema

BACKUP_ROOT="/opt/smartlabs/backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$BACKUP_ROOT/full_backup_$DATE"

mkdir -p $BACKUP_DIR

echo "Iniciando backup completo..."

# 1. Parar servicios (opcional para consistencia)
echo "Pausando servicios..."
docker-compose pause

# 2. Backup de base de datos
echo "Backup de base de datos..."
docker-compose exec -T mariadb mysqldump -u root -p$MARIADB_ROOT_PASSWORD --all-databases > $BACKUP_DIR/full_database.sql

# 3. Backup de volúmenes Docker
echo "Backup de volúmenes..."
docker run --rm -v smartlabs-emqx-data:/data -v $BACKUP_DIR:/backup alpine tar czf /backup/emqx_data.tar.gz -C /data .
docker run --rm -v smartlabs-emqx-etc:/data -v $BACKUP_DIR:/backup alpine tar czf /backup/emqx_etc.tar.gz -C /data .
docker run --rm -v smartlabs-emqx-log:/data -v $BACKUP_DIR:/backup alpine tar czf /backup/emqx_log.tar.gz -C /data .

# 4. Backup de configuración
echo "Backup de configuración..."
cp -r . $BACKUP_DIR/project_files/

# 5. Reanudar servicios
echo "Reanudando servicios..."
docker-compose unpause

# 6. Crear archivo de metadatos
cat > $BACKUP_DIR/backup_info.txt << EOF
Backup Date: $(date)
Backup Type: Full
Docker Compose Version: $(docker-compose --version)
Docker Version: $(docker --version)
System Info: $(uname -a)
Containers:
$(docker-compose ps)
EOF

# 7. Comprimir backup
echo "Comprimiendo backup..."
tar czf $BACKUP_ROOT/smartlabs_full_backup_$DATE.tar.gz -C $BACKUP_ROOT full_backup_$DATE/
rm -rf $BACKUP_DIR

echo "Backup completo finalizado: smartlabs_full_backup_$DATE.tar.gz"
```

### 2. Procedimiento de Recuperación

#### Recuperación Completa

```bash
#!/bin/bash
# restore.sh - Restauración completa del sistema

BACKUP_FILE="$1"

if [ -z "$BACKUP_FILE" ]; then
    echo "Uso: $0 <archivo_backup.tar.gz>"
    exit 1
fi

echo "Iniciando restauración desde: $BACKUP_FILE"

# 1. Detener servicios
echo "Deteniendo servicios..."
docker-compose down

# 2. Extraer backup
echo "Extrayendo backup..."
RESTORE_DIR="/tmp/smartlabs_restore_$(date +%s)"
mkdir -p $RESTORE_DIR
tar xzf $BACKUP_FILE -C $RESTORE_DIR

# 3. Restaurar configuración
echo "Restaurando configuración..."
cp -r $RESTORE_DIR/*/project_files/* .

# 4. Recrear volúmenes
echo "Recreando volúmenes..."
docker volume rm smartlabs-emqx-data smartlabs-emqx-etc smartlabs-emqx-log 2>/dev/null || true
docker volume create smartlabs-emqx-data
docker volume create smartlabs-emqx-etc
docker volume create smartlabs-emqx-log

# 5. Restaurar datos de volúmenes
echo "Restaurando datos de volúmenes..."
docker run --rm -v smartlabs-emqx-data:/data -v $RESTORE_DIR:/backup alpine tar xzf /backup/*/emqx_data.tar.gz -C /data
docker run --rm -v smartlabs-emqx-etc:/data -v $RESTORE_DIR:/backup alpine tar xzf /backup/*/emqx_etc.tar.gz -C /data
docker run --rm -v smartlabs-emqx-log:/data -v $RESTORE_DIR:/backup alpine tar xzf /backup/*/emqx_log.tar.gz -C /data

# 6. Iniciar servicios
echo "Iniciando servicios..."
docker-compose up -d mariadb
sleep 30  # Esperar que MariaDB esté listo

# 7. Restaurar base de datos
echo "Restaurando base de datos..."
docker-compose exec -T mariadb mysql -u root -p$MARIADB_ROOT_PASSWORD < $RESTORE_DIR/*/full_database.sql

# 8. Iniciar todos los servicios
echo "Iniciando todos los servicios..."
docker-compose up -d

# 9. Limpiar
rm -rf $RESTORE_DIR

echo "Restauración completada. Verificar servicios:"
docker-compose ps
```

## Troubleshooting

### 1. Problemas Comunes

#### Error: Puerto en Uso

```bash
# Identificar proceso usando el puerto
sudo netstat -tulpn | grep :1883

# Detener proceso si es necesario
sudo kill -9 <PID>

# O cambiar puerto en docker-compose.yaml
# ports:
#   - "1884:1883"  # Usar puerto 1884 en lugar de 1883
```

#### Error: Volumen No Encontrado

```bash
# Listar volúmenes
docker volume ls

# Recrear volúmenes
docker-compose down
docker volume prune
docker-compose up -d
```

#### Error: Base de Datos No Responde

```bash
# Verificar logs de MariaDB
docker-compose logs mariadb

# Reiniciar solo MariaDB
docker-compose restart mariadb

# Verificar conectividad
docker-compose exec mariadb mysql -u root -p$MARIADB_ROOT_PASSWORD -e "SELECT 1;"
```

### 2. Logs y Diagnóstico

#### Script de Diagnóstico

```bash
#!/bin/bash
# diagnostic.sh - Diagnóstico completo del sistema

DIAG_DIR="/tmp/smartlabs_diagnostic_$(date +%s)"
mkdir -p $DIAG_DIR

echo "Generando diagnóstico en: $DIAG_DIR"

# Información del sistema
echo "=== SYSTEM INFO ===" > $DIAG_DIR/system_info.txt
uname -a >> $DIAG_DIR/system_info.txt
free -h >> $DIAG_DIR/system_info.txt
df -h >> $DIAG_DIR/system_info.txt

# Estado de Docker
echo "=== DOCKER INFO ===" > $DIAG_DIR/docker_info.txt
docker --version >> $DIAG_DIR/docker_info.txt
docker-compose --version >> $DIAG_DIR/docker_info.txt
docker ps -a >> $DIAG_DIR/docker_info.txt
docker volume ls >> $DIAG_DIR/docker_info.txt
docker network ls >> $DIAG_DIR/docker_info.txt

# Logs de contenedores
docker-compose logs emqx > $DIAG_DIR/emqx_logs.txt 2>&1
docker-compose logs mariadb > $DIAG_DIR/mariadb_logs.txt 2>&1
docker-compose logs phpmyadmin > $DIAG_DIR/phpmyadmin_logs.txt 2>&1

# Configuración
cp docker-compose*.yaml $DIAG_DIR/
cp .env* $DIAG_DIR/ 2>/dev/null || true

# Crear archivo comprimido
tar czf smartlabs_diagnostic_$(date +%Y%m%d_%H%M%S).tar.gz -C /tmp $(basename $DIAG_DIR)
rm -rf $DIAG_DIR

echo "Diagnóstico completado: smartlabs_diagnostic_*.tar.gz"
```

## Checklist de Despliegue

### Pre-Despliegue

- [ ] Verificar requisitos del sistema
- [ ] Instalar Docker y Docker Compose
- [ ] Configurar firewall y seguridad
- [ ] Preparar certificados SSL (si aplica)
- [ ] Configurar variables de entorno
- [ ] Verificar puertos disponibles
- [ ] Configurar backup automático

### Despliegue

- [ ] Clonar repositorio
- [ ] Configurar archivo .env
- [ ] Ejecutar docker-compose up -d
- [ ] Verificar estado de contenedores
- [ ] Verificar logs de servicios
- [ ] Probar conectividad MQTT
- [ ] Probar dashboard EMQX
- [ ] Probar phpMyAdmin
- [ ] Verificar base de datos

### Post-Despliegue

- [ ] Configurar monitoreo
- [ ] Configurar alertas
- [ ] Realizar backup inicial
- [ ] Documentar credenciales
- [ ] Capacitar al equipo
- [ ] Establecer procedimientos de mantenimiento
- [ ] Programar backups automáticos
- [ ] Configurar logs centralizados

### Verificación Final

- [ ] Todos los servicios están ejecutándose
- [ ] Conectividad MQTT funcional
- [ ] Dashboard EMQX accesible
- [ ] Base de datos operativa
- [ ] Backup funcionando
- [ ] Monitoreo activo
- [ ] Alertas configuradas
- [ ] Documentación actualizada

---

**Guía de despliegue desarrollada por**: José Ángel Balbuena Palma  
**Última actualización**: $(date +%Y-%m-%d)  
**Versión**: 1.0.0