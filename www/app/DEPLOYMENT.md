# Guía de Despliegue - SMARTLABS

## Requisitos del Sistema

### Requisitos Mínimos

- **PHP**: 7.4 o superior (recomendado 8.0+)
- **Servidor Web**: Apache 2.4+ con mod_rewrite
- **Base de Datos**: MySQL 5.7+ o MariaDB 10.3+
- **Memoria RAM**: 512 MB mínimo (2 GB recomendado)
- **Espacio en Disco**: 1 GB mínimo
- **Sistema Operativo**: Windows 10+, Linux (Ubuntu 18.04+), macOS 10.15+

### Extensiones PHP Requeridas

```ini
; Extensiones obligatorias
extension=mysqli
extension=session
extension=json
extension=curl
extension=mbstring
extension=openssl

; Extensiones recomendadas
extension=gd
extension=zip
extension=xml
extension=fileinfo
```

### Configuración PHP Recomendada

```ini
; php.ini
memory_limit = 256M
max_execution_time = 300
max_input_vars = 3000
upload_max_filesize = 64M
post_max_size = 64M
session.gc_maxlifetime = 3600
session.cookie_httponly = 1
session.cookie_secure = 1
date.timezone = America/Mexico_City
```

## Configuración del Entorno

### 1. Entorno de Desarrollo (Laragon/XAMPP)

#### Laragon (Windows)

```bash
# Descargar e instalar Laragon
# https://laragon.org/download/

# Clonar proyecto
git clone <repository-url> c:\laragon\www\smartlabs

# Configurar base de datos
# Abrir HeidiSQL desde Laragon
# Crear base de datos 'smartlabs'
```

#### XAMPP (Multiplataforma)

```bash
# Descargar e instalar XAMPP
# https://www.apachefriends.org/

# Clonar proyecto
git clone <repository-url> /opt/lampp/htdocs/smartlabs

# Iniciar servicios
sudo /opt/lampp/lampp start
```

### 2. Configuración de Base de Datos

#### Crear Base de Datos

```sql
-- Conectar como root
mysql -u root -p

-- Crear base de datos
CREATE DATABASE smartlabs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario específico
CREATE USER 'smartlabs_user'@'localhost' IDENTIFIED BY 'secure_password_123';
GRANT ALL PRIVILEGES ON smartlabs.* TO 'smartlabs_user'@'localhost';
FLUSH PRIVILEGES;

-- Usar la base de datos
USE smartlabs;
```

#### Esquema de Base de Datos

```sql
-- Tabla de usuarios
CREATE TABLE users (
    users_id INT AUTO_INCREMENT PRIMARY KEY,
    users_email VARCHAR(255) UNIQUE NOT NULL,
    users_password VARCHAR(255) NOT NULL,
    users_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (users_email),
    INDEX idx_active (is_active)
);

-- Tabla de dispositivos
CREATE TABLE devices (
    devices_id INT AUTO_INCREMENT PRIMARY KEY,
    devices_alias VARCHAR(255) NOT NULL,
    devices_serie VARCHAR(255) UNIQUE NOT NULL,
    devices_user_id INT NOT NULL,
    devices_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (devices_user_id) REFERENCES users(users_id) ON DELETE CASCADE,
    INDEX idx_user_id (devices_user_id),
    INDEX idx_serie (devices_serie),
    INDEX idx_active (is_active)
);

-- Tabla de tráfico
CREATE TABLE traffic (
    traffic_id INT AUTO_INCREMENT PRIMARY KEY,
    traffic_device VARCHAR(255) NOT NULL,
    traffic_hab_id INT,
    traffic_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    traffic_type ENUM('entry', 'exit') DEFAULT 'entry',
    INDEX idx_device (traffic_device),
    INDEX idx_date (traffic_date),
    INDEX idx_hab_id (traffic_hab_id),
    INDEX idx_type (traffic_type)
);

-- Tabla de habitantes
CREATE TABLE habintants (
    hab_id INT AUTO_INCREMENT PRIMARY KEY,
    hab_name VARCHAR(255) NOT NULL,
    hab_registration VARCHAR(100) UNIQUE,
    hab_email VARCHAR(255),
    hab_phone VARCHAR(20),
    hab_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_registration (hab_registration),
    INDEX idx_email (hab_email),
    INDEX idx_active (is_active)
);

-- Tabla de equipos
CREATE TABLE equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_name VARCHAR(255) NOT NULL,
    equipment_code VARCHAR(100) UNIQUE NOT NULL,
    equipment_description TEXT,
    equipment_status ENUM('available', 'loaned', 'maintenance') DEFAULT 'available',
    equipment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (equipment_code),
    INDEX idx_status (equipment_status)
);

-- Tabla de préstamos
CREATE TABLE loans (
    loan_id INT AUTO_INCREMENT PRIMARY KEY,
    loan_equipment_id INT NOT NULL,
    loan_hab_id INT NOT NULL,
    loan_date_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    loan_date_end TIMESTAMP NULL,
    loan_status ENUM('active', 'returned', 'overdue') DEFAULT 'active',
    loan_notes TEXT,
    FOREIGN KEY (loan_equipment_id) REFERENCES equipment(equipment_id),
    FOREIGN KEY (loan_hab_id) REFERENCES habintants(hab_id),
    INDEX idx_equipment (loan_equipment_id),
    INDEX idx_habitant (loan_hab_id),
    INDEX idx_status (loan_status),
    INDEX idx_dates (loan_date_start, loan_date_end)
);

-- Tabla de becarios
CREATE TABLE becarios (
    becario_id INT AUTO_INCREMENT PRIMARY KEY,
    becario_name VARCHAR(255) NOT NULL,
    becario_email VARCHAR(255) UNIQUE,
    becario_phone VARCHAR(20),
    becario_program VARCHAR(255),
    becario_start_date DATE,
    becario_end_date DATE,
    becario_status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
    becario_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (becario_email),
    INDEX idx_status (becario_status),
    INDEX idx_program (becario_program)
);

-- Tabla de caché de estado de dispositivos
CREATE TABLE device_status_cache (
    device_id VARCHAR(255) PRIMARY KEY,
    status JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_updated (updated_at)
);
```

### 3. Configuración de la Aplicación

#### Archivo de Configuración Principal

```php
<?php
// config/app.php
return [
    'app_name' => 'SMARTLABS',
    'app_url' => getenv('APP_URL') ?: 'http://localhost',
    'app_env' => getenv('APP_ENV') ?: 'development',
    'app_debug' => getenv('APP_DEBUG') === 'true',
    'default_controller' => 'Dashboard',
    'default_action' => 'index',
    'assets_path' => '/public',
    'session_timeout' => (int)(getenv('SESSION_TIMEOUT') ?: 3600),
    'timezone' => getenv('APP_TIMEZONE') ?: 'America/Mexico_City',
    'locale' => getenv('APP_LOCALE') ?: 'es_MX'
];
```

#### Configuración de Base de Datos

```php
<?php
// config/database.php
return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'username' => getenv('DB_USERNAME') ?: 'smartlabs_user',
    'password' => getenv('DB_PASSWORD') ?: 'secure_password_123',
    'database' => getenv('DB_DATABASE') ?: 'smartlabs',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
```

#### Variables de Entorno (.env)

```bash
# .env (crear en la raíz del proyecto)

# Aplicación
APP_NAME=SMARTLABS
APP_URL=http://localhost
APP_ENV=development
APP_DEBUG=true
APP_TIMEZONE=America/Mexico_City
APP_LOCALE=es_MX

# Base de datos
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=smartlabs
DB_USERNAME=smartlabs_user
DB_PASSWORD=secure_password_123
DB_CHARSET=utf8mb4

# Sesiones
SESSION_TIMEOUT=3600
SESSION_SECURE=false
SESSION_HTTPONLY=true

# Dispositivos IoT
IOT_DB_HOST=192.168.0.100
IOT_DB_PORT=4000
IOT_DB_DATABASE=emqx
IOT_DB_USERNAME=root
IOT_DB_PASSWORD=emqxpass

# WebSocket
WS_HOST=localhost
WS_PORT=8080

# Logs
LOG_LEVEL=debug
LOG_PATH=/var/log/smartlabs
```

## Despliegue Local

### 1. Instalación Paso a Paso

```bash
# 1. Clonar repositorio
git clone <repository-url> smartlabs
cd smartlabs

# 2. Configurar permisos (Linux/macOS)
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 777 storage/logs

# 3. Copiar archivo de configuración
cp .env.example .env

# 4. Editar configuración
nano .env

# 5. Importar base de datos
mysql -u root -p smartlabs < database/schema.sql
mysql -u root -p smartlabs < database/seeds.sql

# 6. Configurar Apache VirtualHost
sudo nano /etc/apache2/sites-available/smartlabs.conf
```

### 2. Configuración de Apache

```apache
# /etc/apache2/sites-available/smartlabs.conf
<VirtualHost *:80>
    ServerName smartlabs.local
    ServerAlias www.smartlabs.local
    DocumentRoot /var/www/smartlabs
    
    <Directory /var/www/smartlabs>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Seguridad adicional
        <Files ".env">
            Require all denied
        </Files>
        
        <Files "*.log">
            Require all denied
        </Files>
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/smartlabs_error.log
    CustomLog ${APACHE_LOG_DIR}/smartlabs_access.log combined
    
    # Compresión
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/plain
        AddOutputFilterByType DEFLATE text/html
        AddOutputFilterByType DEFLATE text/xml
        AddOutputFilterByType DEFLATE text/css
        AddOutputFilterByType DEFLATE application/xml
        AddOutputFilterByType DEFLATE application/xhtml+xml
        AddOutputFilterByType DEFLATE application/rss+xml
        AddOutputFilterByType DEFLATE application/javascript
        AddOutputFilterByType DEFLATE application/x-javascript
    </IfModule>
    
    # Cache para archivos estáticos
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType image/jpg "access plus 1 month"
        ExpiresByType image/jpeg "access plus 1 month"
        ExpiresByType image/gif "access plus 1 month"
        ExpiresByType image/png "access plus 1 month"
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/pdf "access plus 1 month"
        ExpiresByType text/javascript "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
    </IfModule>
</VirtualHost>
```

```bash
# Habilitar sitio
sudo a2ensite smartlabs.conf
sudo a2enmod rewrite
sudo a2enmod deflate
sudo a2enmod expires
sudo systemctl reload apache2

# Agregar al hosts
echo "127.0.0.1 smartlabs.local" | sudo tee -a /etc/hosts
```

## Despliegue con Docker

### 1. Dockerfile

```dockerfile
# Dockerfile
FROM php:8.0-apache

# Instalar extensiones PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar PHP
COPY docker/php.ini /usr/local/etc/php/

# Copiar código fuente
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Configurar Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
```

### 2. Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "80:80"
    volumes:
      - .:/var/www/html
      - ./storage/logs:/var/log/smartlabs
    environment:
      - APP_ENV=production
      - DB_HOST=db
      - DB_DATABASE=smartlabs
      - DB_USERNAME=smartlabs_user
      - DB_PASSWORD=secure_password_123
    depends_on:
      - db
    networks:
      - smartlabs-network

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root_password_123
      MYSQL_DATABASE: smartlabs
      MYSQL_USER: smartlabs_user
      MYSQL_PASSWORD: secure_password_123
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql
      - ./database/seeds.sql:/docker-entrypoint-initdb.d/02-seeds.sql
    ports:
      - "3306:3306"
    networks:
      - smartlabs-network

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    environment:
      PMA_HOST: db
      PMA_USER: smartlabs_user
      PMA_PASSWORD: secure_password_123
    ports:
      - "8080:80"
    depends_on:
      - db
    networks:
      - smartlabs-network

  websocket:
    build: ./node
    ports:
      - "8081:8080"
    environment:
      - DB_HOST=db
      - DB_DATABASE=smartlabs
      - DB_USERNAME=smartlabs_user
      - DB_PASSWORD=secure_password_123
    depends_on:
      - db
    networks:
      - smartlabs-network

volumes:
  mysql_data:

networks:
  smartlabs-network:
    driver: bridge
```

### 3. Comandos Docker

```bash
# Construir y ejecutar
docker-compose up -d --build

# Ver logs
docker-compose logs -f web

# Acceder al contenedor
docker-compose exec web bash

# Detener servicios
docker-compose down

# Limpiar volúmenes
docker-compose down -v
```

## Despliegue en Producción

### 1. Servidor Ubuntu 20.04 LTS

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar LAMP stack
sudo apt install apache2 mysql-server php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring php8.0-xml php8.0-zip -y

# Configurar MySQL
sudo mysql_secure_installation

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Git
sudo apt install git -y

# Configurar firewall
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### 2. Configuración SSL con Let's Encrypt

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtener certificado SSL
sudo certbot --apache -d smartlabs.com -d www.smartlabs.com

# Configurar renovación automática
sudo crontab -e
# Agregar: 0 12 * * * /usr/bin/certbot renew --quiet
```

### 3. Configuración de Producción

```apache
# /etc/apache2/sites-available/smartlabs-ssl.conf
<VirtualHost *:443>
    ServerName smartlabs.com
    ServerAlias www.smartlabs.com
    DocumentRoot /var/www/smartlabs
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/smartlabs.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/smartlabs.com/privkey.pem
    
    # Headers de seguridad
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
    
    <Directory /var/www/smartlabs>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Proteger archivos sensibles
        <Files ".env">
            Require all denied
        </Files>
        
        <Files "*.log">
            Require all denied
        </Files>
        
        <FilesMatch "\.(sql|md|json)$">
            Require all denied
        </FilesMatch>
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/smartlabs_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/smartlabs_ssl_access.log combined
</VirtualHost>

# Redirección HTTP a HTTPS
<VirtualHost *:80>
    ServerName smartlabs.com
    ServerAlias www.smartlabs.com
    Redirect permanent / https://smartlabs.com/
</VirtualHost>
```

### 4. Configuración con PM2 (Para WebSocket)

```bash
# Instalar Node.js y PM2
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt-get install -y nodejs
sudo npm install -g pm2

# Configurar PM2
cd /var/www/smartlabs/node
npm install

# ecosystem.config.js
module.exports = {
  apps: [{
    name: 'smartlabs-websocket',
    script: 'scripts/start-device-server.js',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 8080
    }
  }]
};

# Iniciar con PM2
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

### 5. Configuración de Nginx (Alternativa a Apache)

```nginx
# /etc/nginx/sites-available/smartlabs
server {
    listen 80;
    server_name smartlabs.com www.smartlabs.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name smartlabs.com www.smartlabs.com;
    
    root /var/www/smartlabs;
    index index.php index.html;
    
    # SSL
    ssl_certificate /etc/letsencrypt/live/smartlabs.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/smartlabs.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    
    # PHP handling
    location / {
        try_files $uri $uri/ /index.php?url=$uri&$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ \.(env|log|sql|md)$ {
        deny all;
    }
}
```

## Monitoreo y Mantenimiento

### 1. Configuración de Logs

```php
<?php
// app/core/Logger.php
class Logger {
    private static $logPath;
    
    public static function init() {
        self::$logPath = getenv('LOG_PATH') ?: '/var/log/smartlabs';
        
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }
    
    public static function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
        
        $filename = self::$logPath . '/app-' . date('Y-m-d') . '.log';
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    public static function debug($message, $context = []) {
        if (getenv('APP_DEBUG') === 'true') {
            self::log('DEBUG', $message, $context);
        }
    }
}
```

### 2. Health Check

```php
<?php
// app/controllers/HealthController.php
class HealthController extends Controller {
    public function check() {
        $checks = [
            'database' => $this->checkDatabase(),
            'disk_space' => $this->checkDiskSpace(),
            'memory' => $this->checkMemory(),
            'websocket' => $this->checkWebSocket()
        ];
        
        $status = array_reduce($checks, function($carry, $check) {
            return $carry && $check['status'];
        }, true);
        
        http_response_code($status ? 200 : 503);
        
        echo json_encode([
            'status' => $status ? 'healthy' : 'unhealthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => $checks
        ]);
        exit();
    }
    
    private function checkDatabase() {
        try {
            $this->db->query("SELECT 1");
            return ['status' => true, 'message' => 'Database connection OK'];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Database connection failed'];
        }
    }
    
    private function checkDiskSpace() {
        $freeBytes = disk_free_space('/');
        $totalBytes = disk_total_space('/');
        $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;
        
        return [
            'status' => $usedPercent < 90,
            'message' => sprintf('Disk usage: %.1f%%', $usedPercent)
        ];
    }
    
    private function checkMemory() {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->parseBytes($memoryLimit);
        $usedPercent = ($memoryUsage / $memoryLimitBytes) * 100;
        
        return [
            'status' => $usedPercent < 80,
            'message' => sprintf('Memory usage: %.1f%%', $usedPercent)
        ];
    }
    
    private function checkWebSocket() {
        $wsHost = getenv('WS_HOST') ?: 'localhost';
        $wsPort = getenv('WS_PORT') ?: 8080;
        
        $connection = @fsockopen($wsHost, $wsPort, $errno, $errstr, 5);
        
        if ($connection) {
            fclose($connection);
            return ['status' => true, 'message' => 'WebSocket server is running'];
        }
        
        return ['status' => false, 'message' => 'WebSocket server is not responding'];
    }
    
    private function parseBytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        
        return $val;
    }
}
```

### 3. Backup Automático

```bash
#!/bin/bash
# scripts/backup.sh

# Configuración
DB_NAME="smartlabs"
DB_USER="smartlabs_user"
DB_PASS="secure_password_123"
BACKUP_DIR="/var/backups/smartlabs"
APP_DIR="/var/www/smartlabs"
DATE=$(date +"%Y%m%d_%H%M%S")

# Crear directorio de backup
mkdir -p $BACKUP_DIR

# Backup de base de datos
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql
gzip $BACKUP_DIR/db_$DATE.sql

# Backup de archivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C $APP_DIR .

# Limpiar backups antiguos (mantener 7 días)
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# Configurar cron para backup diario
sudo crontab -e
# Agregar: 0 2 * * * /var/www/smartlabs/scripts/backup.sh
```

### 4. Monitoreo con Systemd

```ini
# /etc/systemd/system/smartlabs-websocket.service
[Unit]
Description=SMARTLABS WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/smartlabs/node
ExecStart=/usr/bin/node scripts/start-device-server.js
Restart=always
RestartSec=10
Environment=NODE_ENV=production
Environment=PORT=8080

[Install]
WantedBy=multi-user.target
```

```bash
# Habilitar y iniciar servicio
sudo systemctl enable smartlabs-websocket
sudo systemctl start smartlabs-websocket
sudo systemctl status smartlabs-websocket
```

## Troubleshooting

### Problemas Comunes

#### 1. Error 500 - Internal Server Error

```bash
# Verificar logs de Apache
sudo tail -f /var/log/apache2/error.log

# Verificar logs de PHP
sudo tail -f /var/log/php8.0-fpm.log

# Verificar permisos
sudo chown -R www-data:www-data /var/www/smartlabs
sudo chmod -R 755 /var/www/smartlabs
```

#### 2. Problemas de Base de Datos

```sql
-- Verificar conexión
mysql -u smartlabs_user -p smartlabs

-- Verificar tablas
SHOW TABLES;

-- Verificar permisos
SHOW GRANTS FOR 'smartlabs_user'@'localhost';
```

#### 3. Problemas de WebSocket

```bash
# Verificar si el puerto está en uso
sudo netstat -tlnp | grep :8080

# Verificar logs del servicio
sudo journalctl -u smartlabs-websocket -f

# Reiniciar servicio
sudo systemctl restart smartlabs-websocket
```

### Comandos Útiles

```bash
# Verificar estado de servicios
sudo systemctl status apache2
sudo systemctl status mysql
sudo systemctl status smartlabs-websocket

# Verificar logs en tiempo real
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/smartlabs/app-$(date +%Y-%m-%d).log

# Verificar uso de recursos
htop
df -h
free -h

# Verificar conexiones de red
sudo netstat -tlnp
sudo ss -tlnp
```

## Checklist de Despliegue

### Pre-despliegue

- [ ] Servidor configurado con requisitos mínimos
- [ ] Base de datos creada y configurada
- [ ] Certificados SSL instalados (producción)
- [ ] Firewall configurado
- [ ] Backups configurados

### Despliegue

- [ ] Código fuente desplegado
- [ ] Configuración de entorno aplicada
- [ ] Base de datos migrada
- [ ] Permisos de archivos configurados
- [ ] Servidor web configurado
- [ ] WebSocket server iniciado

### Post-despliegue

- [ ] Health check funcionando
- [ ] Logs configurados
- [ ] Monitoreo activo
- [ ] Backup funcionando
- [ ] Performance optimizado
- [ ] Documentación actualizada

---

**Versión**: 2.0.0  
**Última actualización**: Diciembre 2024  
**Mantenido por**: Equipo SMARTLABS