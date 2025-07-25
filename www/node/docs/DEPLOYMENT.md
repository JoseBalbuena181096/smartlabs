# Guía de Despliegue - SMARTLABS Device Status Server

## Tabla de Contenidos

1. [Requisitos del Sistema](#requisitos-del-sistema)
2. [Configuración del Entorno](#configuración-del-entorno)
3. [Despliegue Local](#despliegue-local)
4. [Despliegue con Docker](#despliegue-con-docker)
5. [Despliegue en Producción](#despliegue-en-producción)
6. [Configuración de Base de Datos](#configuración-de-base-de-datos)
7. [Monitoreo y Mantenimiento](#monitoreo-y-mantenimiento)
8. [Troubleshooting](#troubleshooting)
9. [Checklist de Despliegue](#checklist-de-despliegue)

## Requisitos del Sistema

### Requisitos Mínimos

- **Node.js**: 16.x o superior
- **MySQL**: 5.7 o superior / MariaDB 10.3 o superior
- **RAM**: 512 MB mínimo, 1 GB recomendado
- **CPU**: 1 core mínimo, 2 cores recomendado
- **Almacenamiento**: 1 GB disponible
- **Red**: Puerto 3000 disponible (configurable)

### Requisitos Recomendados para Producción

- **Node.js**: 18.x LTS
- **MySQL**: 8.0 o superior
- **RAM**: 2 GB o más
- **CPU**: 2+ cores
- **Almacenamiento**: SSD con 10 GB disponible
- **Red**: Balanceador de carga (Nginx/Apache)

### Dependencias del Sistema

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y curl git build-essential

# CentOS/RHEL
sudo yum update
sudo yum install -y curl git gcc-c++ make

# Windows (con Chocolatey)
choco install nodejs git
```

## Configuración del Entorno

### Variables de Entorno

Crea un archivo `.env` en el directorio raíz:

```bash
# Configuración del Servidor
PORT=3000
HOST=0.0.0.0
NODE_ENV=production

# Base de Datos Principal
DB_HOST=localhost
DB_USER=smartlabs_user
DB_PASSWORD=secure_password_123
DB_NAME=smartlabs_db
DB_PORT=3306

# Base de Datos de Respaldo (Opcional)
DB_HOST_LOCAL=localhost
DB_USER_LOCAL=smartlabs_backup
DB_PASSWORD_LOCAL=backup_password_456
DB_NAME_LOCAL=smartlabs_backup_db
DB_PORT_LOCAL=3307

# Configuración de WebSocket
WS_PING_INTERVAL=30000
WS_PONG_TIMEOUT=5000

# Configuración de Monitoreo
POLLING_INTERVAL=5000
MAX_RETRIES=3
RETRY_DELAY=1000

# Logging
LOG_LEVEL=info
LOG_FILE=./logs/device-server.log

# Seguridad
CORS_ORIGIN=*
RATE_LIMIT_WINDOW=900000
RATE_LIMIT_MAX=100
```

### Configuración de Seguridad

```bash
# Crear usuario específico para la aplicación
sudo useradd -r -s /bin/false smartlabs
sudo mkdir -p /opt/smartlabs
sudo chown smartlabs:smartlabs /opt/smartlabs

# Configurar permisos de archivos
chmod 600 .env
chmod 755 scripts/
chmod 644 package.json
```

## Despliegue Local

### Instalación Paso a Paso

```bash
# 1. Clonar o navegar al directorio
cd c:\laragon\www\node

# 2. Instalar dependencias
npm install

# 3. Configurar variables de entorno
cp .env.example .env
# Editar .env con tus configuraciones

# 4. Verificar configuración de base de datos
npm run test:db

# 5. Iniciar en modo desarrollo
npm run dev

# 6. Verificar funcionamiento
curl http://localhost:3000/health
```

### Scripts de NPM Disponibles

```json
{
  "scripts": {
    "start": "node scripts/start-device-server.js",
    "dev": "nodemon scripts/start-device-server.js",
    "test": "jest",
    "test:db": "node scripts/test-database.js",
    "test:ws": "node scripts/test-websocket.js",
    "lint": "eslint src/",
    "logs": "tail -f logs/device-server.log"
  }
}
```

### Verificación de Instalación

```bash
# Verificar estado del servidor
curl -s http://localhost:3000/health | jq

# Probar conexión WebSocket
node scripts/test-websocket.js

# Verificar logs
npm run logs
```

## Despliegue con Docker

### Dockerfile

```dockerfile
# Dockerfile
FROM node:18-alpine

# Crear directorio de aplicación
WORKDIR /app

# Copiar archivos de dependencias
COPY package*.json ./

# Instalar dependencias
RUN npm ci --only=production

# Copiar código fuente
COPY . .

# Crear usuario no-root
RUN addgroup -g 1001 -S smartlabs && \
    adduser -S smartlabs -u 1001

# Crear directorio de logs
RUN mkdir -p logs && chown -R smartlabs:smartlabs logs

# Cambiar a usuario no-root
USER smartlabs

# Exponer puerto
EXPOSE 3000

# Comando de inicio
CMD ["npm", "start"]
```

### Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  device-server:
    build: .
    container_name: smartlabs-device-server
    restart: unless-stopped
    ports:
      - "3000:3000"
    environment:
      - NODE_ENV=production
      - DB_HOST=mysql
      - DB_USER=smartlabs_user
      - DB_PASSWORD=secure_password_123
      - DB_NAME=smartlabs_db
    depends_on:
      - mysql
    volumes:
      - ./logs:/app/logs
    networks:
      - smartlabs-network
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:3000/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  mysql:
    image: mysql:8.0
    container_name: smartlabs-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: root_password_456
      MYSQL_DATABASE: smartlabs_db
      MYSQL_USER: smartlabs_user
      MYSQL_PASSWORD: secure_password_123
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database/init.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - smartlabs-network
    command: --default-authentication-plugin=mysql_native_password

  nginx:
    image: nginx:alpine
    container_name: smartlabs-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./nginx/ssl:/etc/nginx/ssl
    depends_on:
      - device-server
    networks:
      - smartlabs-network

volumes:
  mysql_data:

networks:
  smartlabs-network:
    driver: bridge
```

### Comandos Docker

```bash
# Construir y ejecutar
docker-compose up -d

# Ver logs
docker-compose logs -f device-server

# Escalar servicio
docker-compose up -d --scale device-server=3

# Actualizar servicio
docker-compose pull
docker-compose up -d

# Backup de base de datos
docker-compose exec mysql mysqldump -u root -p smartlabs_db > backup.sql

# Detener servicios
docker-compose down
```

## Despliegue en Producción

### Con PM2 (Process Manager)

#### Instalación de PM2

```bash
# Instalar PM2 globalmente
npm install -g pm2

# Verificar instalación
pm2 --version
```

#### Configuración PM2

```javascript
// ecosystem.config.js
module.exports = {
  apps: [{
    name: 'smartlabs-device-server',
    script: 'scripts/start-device-server.js',
    cwd: '/opt/smartlabs',
    instances: 'max', // Usar todos los cores disponibles
    exec_mode: 'cluster',
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    env_production: {
      NODE_ENV: 'production',
      PORT: 3000,
      DB_HOST: 'localhost',
      DB_USER: 'smartlabs_user',
      DB_PASSWORD: 'secure_password_123',
      DB_NAME: 'smartlabs_db'
    },
    log_file: '/var/log/smartlabs/combined.log',
    out_file: '/var/log/smartlabs/out.log',
    error_file: '/var/log/smartlabs/error.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    autorestart: true,
    max_restarts: 10,
    min_uptime: '10s'
  }]
};
```

#### Comandos PM2

```bash
# Iniciar aplicación
pm2 start ecosystem.config.js --env production

# Ver estado
pm2 status
pm2 monit

# Ver logs
pm2 logs smartlabs-device-server

# Reiniciar
pm2 restart smartlabs-device-server

# Recargar (zero-downtime)
pm2 reload smartlabs-device-server

# Detener
pm2 stop smartlabs-device-server

# Eliminar
pm2 delete smartlabs-device-server

# Guardar configuración
pm2 save
pm2 startup
```

### Configuración de Nginx

```nginx
# /etc/nginx/sites-available/smartlabs-device-server
upstream device_server {
    least_conn;
    server 127.0.0.1:3000 max_fails=3 fail_timeout=30s;
    server 127.0.0.1:3001 max_fails=3 fail_timeout=30s backup;
}

server {
    listen 80;
    server_name device-monitor.smartlabs.com;
    
    # Redirigir HTTP a HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name device-monitor.smartlabs.com;
    
    # Certificados SSL
    ssl_certificate /etc/nginx/ssl/smartlabs.crt;
    ssl_certificate_key /etc/nginx/ssl/smartlabs.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Headers de seguridad
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Configuración de WebSocket
    location / {
        proxy_pass http://device_server;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts para WebSocket
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Buffer settings
        proxy_buffering off;
        proxy_cache off;
    }
    
    # Health check endpoint
    location /health {
        proxy_pass http://device_server/health;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Cache health checks
        proxy_cache_valid 200 1m;
    }
    
    # Logs
    access_log /var/log/nginx/smartlabs-device-server.access.log;
    error_log /var/log/nginx/smartlabs-device-server.error.log;
}
```

```bash
# Habilitar sitio
sudo ln -s /etc/nginx/sites-available/smartlabs-device-server /etc/nginx/sites-enabled/

# Verificar configuración
sudo nginx -t

# Recargar Nginx
sudo systemctl reload nginx
```

### Configuración de Systemd (Alternativa a PM2)

```ini
# /etc/systemd/system/smartlabs-device-server.service
[Unit]
Description=SMARTLABS Device Status Server
After=network.target mysql.service
Wants=mysql.service

[Service]
Type=simple
User=smartlabs
Group=smartlabs
WorkingDirectory=/opt/smartlabs
ExecStart=/usr/bin/node scripts/start-device-server.js
Restart=always
RestartSec=10
StandardOutput=syslog
StandardError=syslog
SyslogIdentifier=smartlabs-device-server
Environment=NODE_ENV=production
EnvironmentFile=/opt/smartlabs/.env

# Límites de recursos
LimitNOFILE=65536
LimitNPROC=4096

# Seguridad
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/opt/smartlabs/logs

[Install]
WantedBy=multi-user.target
```

```bash
# Habilitar y iniciar servicio
sudo systemctl enable smartlabs-device-server
sudo systemctl start smartlabs-device-server

# Ver estado
sudo systemctl status smartlabs-device-server

# Ver logs
sudo journalctl -u smartlabs-device-server -f
```

## Configuración de Base de Datos

### Esquema de Base de Datos

```sql
-- database/schema.sql
CREATE DATABASE IF NOT EXISTS smartlabs_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartlabs_db;

-- Tabla de tráfico de dispositivos
CREATE TABLE IF NOT EXISTS traffic (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_serie VARCHAR(50) NOT NULL,
    state ENUM('on', 'off', 'unknown') DEFAULT 'unknown',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_device_serie (device_serie),
    INDEX idx_timestamp (timestamp),
    INDEX idx_state (state)
) ENGINE=InnoDB;

-- Tabla de habitantes/usuarios
CREATE TABLE IF NOT EXISTS habintants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    rfid VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_registration (registration),
    INDEX idx_rfid (rfid),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Tabla de dispositivos
CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_serie VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    type VARCHAR(50),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_device_serie (device_serie),
    INDEX idx_status (status),
    INDEX idx_location (location)
) ENGINE=InnoDB;

-- Datos de ejemplo
INSERT INTO devices (device_serie, name, location, type) VALUES
('SMART001', 'Computadora Lab 1-A', 'Laboratorio 1', 'computer'),
('SMART002', 'Computadora Lab 1-B', 'Laboratorio 1', 'computer'),
('SMART003', 'Impresora 3D Lab 2', 'Laboratorio 2', '3d_printer'),
('SMART004', 'Microscopio Lab 3', 'Laboratorio 3', 'microscope');

INSERT INTO habintants (registration, name, email, rfid) VALUES
('2021001', 'Juan Pérez', 'juan.perez@smartlabs.com', 'RFID001'),
('2021002', 'María García', 'maria.garcia@smartlabs.com', 'RFID002'),
('2021003', 'Carlos López', 'carlos.lopez@smartlabs.com', 'RFID003');
```

### Configuración de Usuario de Base de Datos

```sql
-- Crear usuario específico para la aplicación
CREATE USER 'smartlabs_user'@'localhost' IDENTIFIED BY 'secure_password_123';
GRANT SELECT, INSERT, UPDATE, DELETE ON smartlabs_db.* TO 'smartlabs_user'@'localhost';

-- Usuario de solo lectura para respaldo
CREATE USER 'smartlabs_backup'@'localhost' IDENTIFIED BY 'backup_password_456';
GRANT SELECT ON smartlabs_db.* TO 'smartlabs_backup'@'localhost';

FLUSH PRIVILEGES;
```

### Backup y Restauración

```bash
#!/bin/bash
# scripts/backup-database.sh

DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/opt/smartlabs/backups"
DB_NAME="smartlabs_db"
DB_USER="smartlabs_backup"
DB_PASSWORD="backup_password_456"

# Crear directorio de backup
mkdir -p $BACKUP_DIR

# Realizar backup
mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME > $BACKUP_DIR/smartlabs_db_$DATE.sql

# Comprimir backup
gzip $BACKUP_DIR/smartlabs_db_$DATE.sql

# Eliminar backups antiguos (mantener últimos 7 días)
find $BACKUP_DIR -name "smartlabs_db_*.sql.gz" -mtime +7 -delete

echo "Backup completado: smartlabs_db_$DATE.sql.gz"
```

```bash
# Configurar cron para backup automático
# crontab -e
0 2 * * * /opt/smartlabs/scripts/backup-database.sh
```

## Monitoreo y Mantenimiento

### Monitoreo de Aplicación

```javascript
// scripts/health-check.js
const http = require('http');
const WebSocket = require('ws');

class HealthChecker {
    constructor(options = {}) {
        this.options = {
            httpPort: 3000,
            wsPort: 3000,
            timeout: 5000,
            ...options
        };
    }
    
    async checkHTTP() {
        return new Promise((resolve, reject) => {
            const req = http.get(`http://localhost:${this.options.httpPort}/health`, (res) => {
                let data = '';
                res.on('data', chunk => data += chunk);
                res.on('end', () => {
                    try {
                        const health = JSON.parse(data);
                        resolve({
                            status: 'ok',
                            http: health
                        });
                    } catch (error) {
                        reject(new Error('Invalid health response'));
                    }
                });
            });
            
            req.on('error', reject);
            req.setTimeout(this.options.timeout, () => {
                req.destroy();
                reject(new Error('HTTP health check timeout'));
            });
        });
    }
    
    async checkWebSocket() {
        return new Promise((resolve, reject) => {
            const ws = new WebSocket(`ws://localhost:${this.options.wsPort}`);
            
            const timeout = setTimeout(() => {
                ws.close();
                reject(new Error('WebSocket health check timeout'));
            }, this.options.timeout);
            
            ws.on('open', () => {
                clearTimeout(timeout);
                ws.close();
                resolve({ status: 'ok', websocket: 'connected' });
            });
            
            ws.on('error', (error) => {
                clearTimeout(timeout);
                reject(error);
            });
        });
    }
    
    async checkAll() {
        try {
            const [httpHealth, wsHealth] = await Promise.all([
                this.checkHTTP(),
                this.checkWebSocket()
            ]);
            
            return {
                status: 'healthy',
                timestamp: new Date().toISOString(),
                checks: {
                    http: httpHealth,
                    websocket: wsHealth
                }
            };
        } catch (error) {
            return {
                status: 'unhealthy',
                timestamp: new Date().toISOString(),
                error: error.message
            };
        }
    }
}

// Ejecutar health check
if (require.main === module) {
    const checker = new HealthChecker();
    checker.checkAll()
        .then(result => {
            console.log(JSON.stringify(result, null, 2));
            process.exit(result.status === 'healthy' ? 0 : 1);
        })
        .catch(error => {
            console.error('Health check failed:', error);
            process.exit(1);
        });
}

module.exports = HealthChecker;
```

### Configuración de Logs

```javascript
// src/utils/logger.js
const winston = require('winston');
const path = require('path');

const logger = winston.createLogger({
    level: process.env.LOG_LEVEL || 'info',
    format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.errors({ stack: true }),
        winston.format.json()
    ),
    defaultMeta: { service: 'device-status-server' },
    transports: [
        // Archivo de logs
        new winston.transports.File({
            filename: path.join(__dirname, '../../logs/error.log'),
            level: 'error'
        }),
        new winston.transports.File({
            filename: path.join(__dirname, '../../logs/combined.log')
        })
    ]
});

// En desarrollo, también log a consola
if (process.env.NODE_ENV !== 'production') {
    logger.add(new winston.transports.Console({
        format: winston.format.simple()
    }));
}

module.exports = logger;
```

### Rotación de Logs

```bash
# /etc/logrotate.d/smartlabs-device-server
/opt/smartlabs/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 smartlabs smartlabs
    postrotate
        /bin/kill -USR1 $(cat /var/run/smartlabs-device-server.pid 2>/dev/null) 2>/dev/null || true
    endscript
}
```

### Alertas y Notificaciones

```bash
#!/bin/bash
# scripts/monitor.sh

SERVICE_NAME="smartlabs-device-server"
HEALTH_URL="http://localhost:3000/health"
SLACK_WEBHOOK="https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK"
EMAIL="admin@smartlabs.com"

# Verificar estado del servicio
if ! systemctl is-active --quiet $SERVICE_NAME; then
    echo "❌ Servicio $SERVICE_NAME no está ejecutándose"
    
    # Enviar alerta a Slack
    curl -X POST -H 'Content-type: application/json' \
        --data '{"text":"🚨 ALERTA: Servicio '$SERVICE_NAME' no está ejecutándose"}' \
        $SLACK_WEBHOOK
    
    # Intentar reiniciar
    systemctl restart $SERVICE_NAME
    sleep 10
    
    if systemctl is-active --quiet $SERVICE_NAME; then
        curl -X POST -H 'Content-type: application/json' \
            --data '{"text":"✅ Servicio '$SERVICE_NAME' reiniciado exitosamente"}' \
            $SLACK_WEBHOOK
    fi
fi

# Verificar health endpoint
if ! curl -f -s $HEALTH_URL > /dev/null; then
    echo "❌ Health check falló para $SERVICE_NAME"
    
    curl -X POST -H 'Content-type: application/json' \
        --data '{"text":"🚨 ALERTA: Health check falló para '$SERVICE_NAME'"}' \
        $SLACK_WEBHOOK
fi

# Verificar uso de memoria
MEM_USAGE=$(ps -o pid,ppid,cmd,%mem --sort=-%mem | grep $SERVICE_NAME | head -1 | awk '{print $4}')
if (( $(echo "$MEM_USAGE > 80" | bc -l) )); then
    echo "⚠️ Alto uso de memoria: $MEM_USAGE%"
    
    curl -X POST -H 'Content-type: application/json' \
        --data '{"text":"⚠️ ADVERTENCIA: Alto uso de memoria en '$SERVICE_NAME': '$MEM_USAGE'%"}' \
        $SLACK_WEBHOOK
fi
```

```bash
# Configurar monitoreo cada 5 minutos
# crontab -e
*/5 * * * * /opt/smartlabs/scripts/monitor.sh
```

## Troubleshooting

### Problemas Comunes

#### 1. Error de Conexión a Base de Datos

```bash
# Verificar estado de MySQL
sudo systemctl status mysql

# Verificar conectividad
mysql -u smartlabs_user -p -h localhost smartlabs_db

# Verificar logs de MySQL
sudo tail -f /var/log/mysql/error.log

# Reiniciar MySQL si es necesario
sudo systemctl restart mysql
```

#### 2. Puerto en Uso

```bash
# Verificar qué proceso usa el puerto
sudo netstat -tlnp | grep :3000
sudo lsof -i :3000

# Matar proceso si es necesario
sudo kill -9 <PID>
```

#### 3. Problemas de WebSocket

```bash
# Verificar configuración de Nginx
sudo nginx -t

# Verificar logs de Nginx
sudo tail -f /var/log/nginx/error.log

# Probar conexión WebSocket directa
node scripts/test-websocket.js
```

#### 4. Alto Uso de Memoria

```bash
# Verificar uso de memoria
ps aux | grep node
top -p $(pgrep -f "smartlabs-device-server")

# Reiniciar aplicación
pm2 restart smartlabs-device-server

# Verificar memory leaks
node --inspect scripts/start-device-server.js
```

### Comandos de Diagnóstico

```bash
#!/bin/bash
# scripts/diagnostics.sh

echo "=== SMARTLABS Device Server Diagnostics ==="
echo "Fecha: $(date)"
echo

echo "--- Estado del Sistema ---"
uptime
free -h
df -h
echo

echo "--- Estado del Servicio ---"
systemctl status smartlabs-device-server
echo

echo "--- Procesos Node.js ---"
ps aux | grep node
echo

echo "--- Puertos en Uso ---"
netstat -tlnp | grep :3000
echo

echo "--- Estado de MySQL ---"
systemctl status mysql
mysql -u smartlabs_user -p -e "SELECT COUNT(*) as device_count FROM smartlabs_db.traffic;"
echo

echo "--- Logs Recientes ---"
tail -20 /opt/smartlabs/logs/combined.log
echo

echo "--- Health Check ---"
curl -s http://localhost:3000/health | jq
echo

echo "--- Uso de Disco ---"
du -sh /opt/smartlabs/
du -sh /opt/smartlabs/logs/
echo

echo "=== Fin del Diagnóstico ==="
```

## Checklist de Despliegue

### Pre-Despliegue

- [ ] Verificar requisitos del sistema
- [ ] Configurar variables de entorno
- [ ] Crear usuario de sistema dedicado
- [ ] Configurar base de datos
- [ ] Instalar dependencias
- [ ] Configurar SSL/TLS
- [ ] Configurar firewall
- [ ] Preparar scripts de backup

### Despliegue

- [ ] Clonar/copiar código fuente
- [ ] Instalar dependencias de Node.js
- [ ] Configurar PM2 o systemd
- [ ] Configurar Nginx/Apache
- [ ] Ejecutar migraciones de base de datos
- [ ] Verificar health checks
- [ ] Configurar monitoreo
- [ ] Configurar alertas

### Post-Despliegue

- [ ] Verificar funcionamiento completo
- [ ] Probar conexiones WebSocket
- [ ] Verificar logs
- [ ] Configurar backups automáticos
- [ ] Documentar configuración
- [ ] Entrenar al equipo de operaciones
- [ ] Configurar rotación de logs
- [ ] Establecer procedimientos de mantenimiento

### Verificación Final

```bash
# Script de verificación completa
#!/bin/bash
echo "🔍 Verificación final del despliegue..."

# 1. Health check HTTP
echo "📡 Verificando health check..."
curl -f http://localhost:3000/health || exit 1

# 2. Conexión WebSocket
echo "🔌 Verificando WebSocket..."
node scripts/test-websocket.js || exit 1

# 3. Base de datos
echo "🗄️ Verificando base de datos..."
mysql -u smartlabs_user -p -e "SELECT 1" smartlabs_db || exit 1

# 4. Logs
echo "📝 Verificando logs..."
test -f /opt/smartlabs/logs/combined.log || exit 1

# 5. Servicios
echo "⚙️ Verificando servicios..."
systemctl is-active smartlabs-device-server || exit 1
systemctl is-active nginx || exit 1
systemctl is-active mysql || exit 1

echo "✅ Despliegue verificado exitosamente"
```

---

**Nota**: Esta guía proporciona un marco completo para el despliegue del servicio. Adapta las configuraciones según tu entorno específico y requisitos de seguridad.