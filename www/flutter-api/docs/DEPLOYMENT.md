# Guía de Despliegue - SMARTLABS Flutter API

## Tabla de Contenidos

1. [Requisitos del Sistema](#requisitos-del-sistema)
2. [Configuración del Entorno](#configuración-del-entorno)
3. [Despliegue Local](#despliegue-local)
4. [Despliegue con Docker](#despliegue-con-docker)
5. [Despliegue en Producción](#despliegue-en-producción)
6. [Configuración de Base de Datos](#configuración-de-base-de-datos)
7. [Configuración MQTT](#configuración-mqtt)
8. [Monitoreo y Mantenimiento](#monitoreo-y-mantenimiento)
9. [Troubleshooting](#troubleshooting)

## Requisitos del Sistema

### Mínimos
- **CPU**: 2 cores
- **RAM**: 2GB
- **Almacenamiento**: 10GB
- **Node.js**: v16.0.0 o superior
- **MySQL**: v8.0 o superior
- **MQTT Broker**: EMQX v5.0 o superior

### Recomendados
- **CPU**: 4 cores
- **RAM**: 4GB
- **Almacenamiento**: 50GB SSD
- **Node.js**: v18.0.0 LTS
- **MySQL**: v8.0.35
- **MQTT Broker**: EMQX v5.4

### Sistemas Operativos Soportados
- Ubuntu 20.04 LTS / 22.04 LTS
- CentOS 8 / Rocky Linux 8
- Windows Server 2019/2022
- macOS 12+ (desarrollo)

## Configuración del Entorno

### 1. Instalación de Node.js

**Ubuntu/Debian:**
```bash
# Instalar Node.js 18 LTS
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verificar instalación
node --version
npm --version
```

**CentOS/RHEL:**
```bash
# Instalar Node.js 18 LTS
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs

# Verificar instalación
node --version
npm --version
```

**Windows:**
```powershell
# Usar Chocolatey
choco install nodejs

# O descargar desde https://nodejs.org/
```

### 2. Instalación de MySQL

**Ubuntu/Debian:**
```bash
# Instalar MySQL Server
sudo apt update
sudo apt install mysql-server

# Configuración segura
sudo mysql_secure_installation

# Iniciar servicio
sudo systemctl start mysql
sudo systemctl enable mysql
```

**CentOS/RHEL:**
```bash
# Instalar MySQL Server
sudo yum install mysql-server

# Iniciar servicio
sudo systemctl start mysqld
sudo systemctl enable mysqld

# Obtener contraseña temporal
sudo grep 'temporary password' /var/log/mysqld.log
```

### 3. Instalación de EMQX

**Ubuntu/Debian:**
```bash
# Agregar repositorio EMQX
wget https://www.emqx.com/en/downloads/broker/5.4.0/emqx-5.4.0-ubuntu20.04-amd64.deb
sudo dpkg -i emqx-5.4.0-ubuntu20.04-amd64.deb

# Iniciar servicio
sudo systemctl start emqx
sudo systemctl enable emqx
```

**Docker:**
```bash
# Ejecutar EMQX en Docker
docker run -d --name emqx \
  -p 1883:1883 \
  -p 8083:8083 \
  -p 8084:8084 \
  -p 8883:8883 \
  -p 18083:18083 \
  emqx/emqx:5.4.0
```

## Despliegue Local

### 1. Clonar el Repositorio

```bash
git clone <repository-url>
cd flutter-api
```

### 2. Instalar Dependencias

```bash
npm install
```

### 3. Configurar Variables de Entorno

```bash
cp .env.example .env
```

Editar `.env`:
```env
# Configuración del Servidor
PORT=3000
NODE_ENV=development

# Base de Datos
DB_HOST=localhost
DB_USER=smartlabs_user
DB_PASSWORD=secure_password_123
DB_NAME=smartlabs
DB_PORT=3306

# MQTT
MQTT_HOST=localhost
MQTT_PORT=1883
MQTT_USERNAME=admin
MQTT_PASSWORD=public

# Seguridad
JWT_SECRET=your_super_secret_jwt_key_here_min_32_chars
JWT_EXPIRES_IN=24h
```

### 4. Configurar Base de Datos

```bash
# Conectar a MySQL
mysql -u root -p
```

```sql
-- Crear base de datos y usuario
CREATE DATABASE smartlabs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smartlabs_user'@'localhost' IDENTIFIED BY 'secure_password_123';
GRANT ALL PRIVILEGES ON smartlabs.* TO 'smartlabs_user'@'localhost';
FLUSH PRIVILEGES;

-- Usar la base de datos
USE smartlabs;

-- Crear tablas (ver schema en sección siguiente)
```

### 5. Ejecutar la Aplicación

**Desarrollo:**
```bash
npm run dev
```

**Producción:**
```bash
npm start
```

### 6. Verificar Instalación

```bash
# Health check
curl http://localhost:3000/health

# API info
curl http://localhost:3000/api
```

## Despliegue con Docker

### 1. Dockerfile

```dockerfile
# Dockerfile
FROM node:18-alpine

# Crear directorio de trabajo
WORKDIR /app

# Copiar archivos de dependencias
COPY package*.json ./

# Instalar dependencias
RUN npm ci --only=production && npm cache clean --force

# Copiar código fuente
COPY src/ ./src/

# Crear usuario no-root
RUN addgroup -g 1001 -S nodejs
RUN adduser -S nodejs -u 1001

# Cambiar propietario
RUN chown -R nodejs:nodejs /app
USER nodejs

# Exponer puerto
EXPOSE 3000

# Comando de inicio
CMD ["npm", "start"]
```

### 2. Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  # API Service
  smartlabs-api:
    build: .
    container_name: smartlabs-api
    ports:
      - "3000:3000"
    environment:
      - NODE_ENV=production
      - PORT=3000
      - DB_HOST=mysql
      - DB_USER=smartlabs_user
      - DB_PASSWORD=secure_password_123
      - DB_NAME=smartlabs
      - DB_PORT=3306
      - MQTT_HOST=emqx
      - MQTT_PORT=1883
      - MQTT_USERNAME=admin
      - MQTT_PASSWORD=public
      - JWT_SECRET=your_super_secret_jwt_key_here_min_32_chars
    depends_on:
      - mysql
      - emqx
    restart: unless-stopped
    networks:
      - smartlabs-network

  # MySQL Database
  mysql:
    image: mysql:8.0
    container_name: smartlabs-mysql
    environment:
      - MYSQL_ROOT_PASSWORD=root_password_123
      - MYSQL_DATABASE=smartlabs
      - MYSQL_USER=smartlabs_user
      - MYSQL_PASSWORD=secure_password_123
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./init.sql:/docker-entrypoint-initdb.d/init.sql
    restart: unless-stopped
    networks:
      - smartlabs-network

  # EMQX MQTT Broker
  emqx:
    image: emqx/emqx:5.4.0
    container_name: smartlabs-emqx
    ports:
      - "1883:1883"    # MQTT
      - "8083:8083"    # WebSocket
      - "8084:8084"    # WSS
      - "8883:8883"    # MQTT SSL
      - "18083:18083"  # Dashboard
    environment:
      - EMQX_NAME=emqx
      - EMQX_HOST=127.0.0.1
    volumes:
      - emqx_data:/opt/emqx/data
      - emqx_log:/opt/emqx/log
    restart: unless-stopped
    networks:
      - smartlabs-network

  # Nginx Reverse Proxy (Opcional)
  nginx:
    image: nginx:alpine
    container_name: smartlabs-nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - smartlabs-api
    restart: unless-stopped
    networks:
      - smartlabs-network

volumes:
  mysql_data:
  emqx_data:
  emqx_log:

networks:
  smartlabs-network:
    driver: bridge
```

### 3. Comandos Docker

```bash
# Construir y ejecutar
docker-compose up -d --build

# Ver logs
docker-compose logs -f smartlabs-api

# Detener servicios
docker-compose down

# Detener y eliminar volúmenes
docker-compose down -v

# Reiniciar servicio específico
docker-compose restart smartlabs-api
```

## Despliegue en Producción

### 1. Configuración del Servidor

**Actualizar sistema:**
```bash
sudo apt update && sudo apt upgrade -y
```

**Instalar herramientas esenciales:**
```bash
sudo apt install -y curl wget git unzip htop
```

**Configurar firewall:**
```bash
# UFW (Ubuntu)
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
sudo ufw allow 3000
sudo ufw allow 1883
sudo ufw enable
```

### 2. Configuración con PM2

**Instalar PM2:**
```bash
npm install -g pm2
```

**Archivo de configuración PM2:**
```javascript
// ecosystem.config.js
module.exports = {
  apps: [{
    name: 'smartlabs-api',
    script: 'src/index.js',
    instances: 'max',
    exec_mode: 'cluster',
    env: {
      NODE_ENV: 'development',
      PORT: 3000
    },
    env_production: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    error_file: './logs/err.log',
    out_file: './logs/out.log',
    log_file: './logs/combined.log',
    time: true,
    max_memory_restart: '1G',
    node_args: '--max_old_space_size=1024'
  }]
};
```

**Comandos PM2:**
```bash
# Iniciar aplicación
pm2 start ecosystem.config.js --env production

# Ver estado
pm2 status

# Ver logs
pm2 logs

# Reiniciar
pm2 restart smartlabs-api

# Detener
pm2 stop smartlabs-api

# Configurar inicio automático
pm2 startup
pm2 save
```

### 3. Configuración de Nginx

**Instalar Nginx:**
```bash
sudo apt install nginx
```

**Configuración del sitio:**
```nginx
# /etc/nginx/sites-available/smartlabs-api
server {
    listen 80;
    server_name api.smartlabs.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.smartlabs.com;
    
    # SSL Configuration
    ssl_certificate /etc/ssl/certs/smartlabs.crt;
    ssl_certificate_key /etc/ssl/private/smartlabs.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;
    
    # Security Headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";
    
    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req zone=api burst=20 nodelay;
    
    # Proxy to Node.js
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # Health check endpoint
    location /health {
        access_log off;
        proxy_pass http://localhost:3000/health;
    }
    
    # Static files (if any)
    location /static/ {
        alias /var/www/smartlabs/static/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

**Habilitar sitio:**
```bash
sudo ln -s /etc/nginx/sites-available/smartlabs-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 4. SSL con Let's Encrypt

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d api.smartlabs.com

# Renovación automática
sudo crontab -e
# Agregar: 0 12 * * * /usr/bin/certbot renew --quiet
```

## Configuración de Base de Datos

### Schema SQL

```sql
-- init.sql
CREATE DATABASE IF NOT EXISTS smartlabs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartlabs;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS habitant (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    registration VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255),
    cards_number VARCHAR(50),
    device_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_registration (registration),
    INDEX idx_cards_number (cards_number)
);

-- Tabla de dispositivos
CREATE TABLE IF NOT EXISTS device (
    id INT PRIMARY KEY AUTO_INCREMENT,
    alias VARCHAR(255) NOT NULL,
    serie VARCHAR(50) UNIQUE NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status TINYINT DEFAULT 0,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_serie (serie),
    INDEX idx_status (status)
);

-- Tabla de tráfico/historial
CREATE TABLE IF NOT EXISTS traffic (
    id INT PRIMARY KEY AUTO_INCREMENT,
    habitant_id INT,
    device_id INT,
    action VARCHAR(10) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (habitant_id) REFERENCES habitant(id) ON DELETE SET NULL,
    FOREIGN KEY (device_id) REFERENCES device(id) ON DELETE SET NULL,
    INDEX idx_timestamp (timestamp),
    INDEX idx_habitant_id (habitant_id),
    INDEX idx_device_id (device_id)
);

-- Tabla de equipos
CREATE TABLE IF NOT EXISTS equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    rfid VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    status ENUM('available', 'loaned', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rfid (rfid),
    INDEX idx_status (status)
);

-- Tabla de préstamos
CREATE TABLE IF NOT EXISTS loan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    habitant_id INT NOT NULL,
    equipment_id INT NOT NULL,
    device_id INT NOT NULL,
    loan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    return_date TIMESTAMP NULL,
    status ENUM('active', 'returned', 'overdue') DEFAULT 'active',
    notes TEXT,
    FOREIGN KEY (habitant_id) REFERENCES habitant(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES device(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_loan_date (loan_date),
    INDEX idx_habitant_id (habitant_id)
);

-- Datos de ejemplo
INSERT INTO habitant (name, registration, email, cards_number) VALUES
('Juan Pérez González', 'A01234567', 'juan.perez@tec.mx', '1234567890'),
('María García López', 'A01234568', 'maria.garcia@tec.mx', '1234567891'),
('Carlos Rodríguez Martín', 'A01234569', 'carlos.rodriguez@tec.mx', '1234567892');

INSERT INTO device (alias, serie, location) VALUES
('Laboratorio IoT 1', 'SMART10001', 'Edificio A - Lab 101'),
('Laboratorio IoT 2', 'SMART10002', 'Edificio A - Lab 102'),
('Laboratorio IoT 3', 'SMART10003', 'Edificio B - Lab 201');

INSERT INTO equipment (name, rfid, description) VALUES
('Arduino Uno R3', 'RFID001', 'Microcontrolador Arduino Uno R3 con cable USB'),
('Sensor DHT22', 'RFID002', 'Sensor de temperatura y humedad DHT22'),
('Módulo ESP32', 'RFID003', 'Módulo WiFi ESP32 DevKit V1');
```

### Backup y Restauración

**Crear backup:**
```bash
# Backup completo
mysqldump -u root -p smartlabs > smartlabs_backup_$(date +%Y%m%d_%H%M%S).sql

# Backup solo estructura
mysqldump -u root -p --no-data smartlabs > smartlabs_schema.sql

# Backup solo datos
mysqldump -u root -p --no-create-info smartlabs > smartlabs_data.sql
```

**Restaurar backup:**
```bash
mysql -u root -p smartlabs < smartlabs_backup.sql
```

**Script de backup automático:**
```bash
#!/bin/bash
# backup.sh
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
FILENAME="smartlabs_backup_$DATE.sql"

mkdir -p $BACKUP_DIR
mysqldump -u backup_user -p$BACKUP_PASSWORD smartlabs > $BACKUP_DIR/$FILENAME
gzip $BACKUP_DIR/$FILENAME

# Eliminar backups antiguos (más de 7 días)
find $BACKUP_DIR -name "smartlabs_backup_*.sql.gz" -mtime +7 -delete
```

## Configuración MQTT

### 1. Configuración EMQX

**Archivo de configuración:**
```hocon
# emqx.conf
node {
  name = "emqx@127.0.0.1"
  cookie = "emqxsecretcookie"
  data_dir = "/opt/emqx/data"
}

cluster {
  name = smartlabs
  discovery_strategy = manual
}

listeners.tcp.default {
  bind = "0.0.0.0:1883"
  max_connections = 1024000
  max_conn_rate = 1000
}

listeners.ws.default {
  bind = "0.0.0.0:8083"
  max_connections = 1024000
  websocket.mqtt_path = "/mqtt"
}

listeners.ssl.default {
  bind = "0.0.0.0:8883"
  max_connections = 512000
  ssl_options {
    keyfile = "/opt/emqx/etc/certs/key.pem"
    certfile = "/opt/emqx/etc/certs/cert.pem"
  }
}

dashboard {
  listeners.http {
    bind = 18083
  }
  default_username = "admin"
  default_password = "public"
}

authorization {
  sources = [
    {
      type = built_in_database
      enable = true
    }
  ]
  no_match = allow
  deny_action = ignore
  cache = {
    enable = true
    max_size = 32
    ttl = 1m
  }
}
```

### 2. Configuración de Usuarios MQTT

```bash
# Crear usuario admin
emqx_ctl users add admin public

# Crear usuario para dispositivos
emqx_ctl users add device_user device_pass

# Configurar ACL
echo "user admin" >> /opt/emqx/etc/acl.conf
echo "topic readwrite #" >> /opt/emqx/etc/acl.conf
echo "" >> /opt/emqx/etc/acl.conf
echo "user device_user" >> /opt/emqx/etc/acl.conf
echo "topic readwrite SMART+/+" >> /opt/emqx/etc/acl.conf
```

## Monitoreo y Mantenimiento

### 1. Monitoreo de Aplicación

**Script de monitoreo:**
```bash
#!/bin/bash
# monitor.sh

API_URL="http://localhost:3000/health"
LOG_FILE="/var/log/smartlabs/monitor.log"

check_api() {
    response=$(curl -s -o /dev/null -w "%{http_code}" $API_URL)
    if [ $response -eq 200 ]; then
        echo "$(date): API OK" >> $LOG_FILE
    else
        echo "$(date): API ERROR - HTTP $response" >> $LOG_FILE
        # Reiniciar servicio si es necesario
        pm2 restart smartlabs-api
    fi
}

check_mysql() {
    mysql -u smartlabs_user -p$DB_PASSWORD -e "SELECT 1" > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "$(date): MySQL OK" >> $LOG_FILE
    else
        echo "$(date): MySQL ERROR" >> $LOG_FILE
    fi
}

check_mqtt() {
    mosquitto_pub -h localhost -p 1883 -t "test/monitor" -m "ping" > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "$(date): MQTT OK" >> $LOG_FILE
    else
        echo "$(date): MQTT ERROR" >> $LOG_FILE
    fi
}

check_api
check_mysql
check_mqtt
```

**Crontab para monitoreo:**
```bash
# Ejecutar cada 5 minutos
*/5 * * * * /opt/smartlabs/scripts/monitor.sh
```

### 2. Logs y Rotación

**Configuración logrotate:**
```bash
# /etc/logrotate.d/smartlabs
/var/log/smartlabs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 nodejs nodejs
    postrotate
        pm2 reloadLogs
    endscript
}
```

### 3. Alertas

**Script de alertas:**
```bash
#!/bin/bash
# alerts.sh

SLACK_WEBHOOK="https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK"
EMAIL="admin@smartlabs.com"

send_alert() {
    local message="$1"
    local severity="$2"
    
    # Slack
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"[$severity] SMARTLABS API: $message\"}" \
        $SLACK_WEBHOOK
    
    # Email
    echo "$message" | mail -s "[$severity] SMARTLABS API Alert" $EMAIL
}

# Verificar uso de CPU
cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | awk -F'%' '{print $1}')
if (( $(echo "$cpu_usage > 80" | bc -l) )); then
    send_alert "High CPU usage: ${cpu_usage}%" "WARNING"
fi

# Verificar uso de memoria
mem_usage=$(free | grep Mem | awk '{printf "%.2f", $3/$2 * 100.0}')
if (( $(echo "$mem_usage > 85" | bc -l) )); then
    send_alert "High memory usage: ${mem_usage}%" "WARNING"
fi

# Verificar espacio en disco
disk_usage=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $disk_usage -gt 85 ]; then
    send_alert "High disk usage: ${disk_usage}%" "WARNING"
fi
```

## Troubleshooting

### 1. Problemas Comunes

**API no responde:**
```bash
# Verificar proceso
pm2 status

# Ver logs
pm2 logs smartlabs-api --lines 100

# Verificar puerto
netstat -tlnp | grep 3000

# Reiniciar servicio
pm2 restart smartlabs-api
```

**Error de conexión a MySQL:**
```bash
# Verificar servicio MySQL
sudo systemctl status mysql

# Verificar conexión
mysql -u smartlabs_user -p -h localhost

# Ver logs de MySQL
sudo tail -f /var/log/mysql/error.log
```

**Error de conexión MQTT:**
```bash
# Verificar servicio EMQX
sudo systemctl status emqx

# Verificar puerto
netstat -tlnp | grep 1883

# Test de conexión
mosquitto_pub -h localhost -p 1883 -t "test" -m "hello"
```

### 2. Comandos de Diagnóstico

```bash
# Información del sistema
uname -a
df -h
free -h
top

# Procesos de Node.js
ps aux | grep node

# Conexiones de red
netstat -tlnp
ss -tlnp

# Logs del sistema
journalctl -u smartlabs-api -f
tail -f /var/log/syslog
```

### 3. Recovery Procedures

**Restaurar desde backup:**
```bash
# Detener servicios
pm2 stop smartlabs-api
sudo systemctl stop mysql

# Restaurar base de datos
mysql -u root -p smartlabs < /var/backups/mysql/smartlabs_backup_latest.sql

# Iniciar servicios
sudo systemctl start mysql
pm2 start smartlabs-api
```

**Reinicio completo del sistema:**
```bash
# Detener todos los servicios
pm2 stop all
sudo systemctl stop nginx
sudo systemctl stop mysql
sudo systemctl stop emqx

# Limpiar logs si es necesario
sudo truncate -s 0 /var/log/smartlabs/*.log

# Iniciar servicios en orden
sudo systemctl start mysql
sudo systemctl start emqx
pm2 start all
sudo systemctl start nginx

# Verificar estado
pm2 status
sudo systemctl status mysql emqx nginx
```

## Checklist de Despliegue

### Pre-despliegue
- [ ] Servidor configurado con requisitos mínimos
- [ ] Node.js 18+ instalado
- [ ] MySQL 8.0+ instalado y configurado
- [ ] EMQX instalado y configurado
- [ ] Firewall configurado
- [ ] SSL certificados obtenidos
- [ ] Variables de entorno configuradas
- [ ] Base de datos inicializada

### Despliegue
- [ ] Código clonado y dependencias instaladas
- [ ] PM2 configurado
- [ ] Nginx configurado
- [ ] Servicios iniciados
- [ ] Health checks pasando
- [ ] MQTT funcionando
- [ ] Logs configurados

### Post-despliegue
- [ ] Monitoreo configurado
- [ ] Backups programados
- [ ] Alertas configuradas
- [ ] Documentación actualizada
- [ ] Equipo notificado
- [ ] Tests de integración ejecutados

---

**Nota**: Esta guía debe adaptarse según el entorno específico de despliegue. Siempre realizar pruebas en un entorno de staging antes de desplegar en producción.