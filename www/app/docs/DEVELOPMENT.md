# SMARTLABS PHP Web Application - Guía de Desarrollo

## Tabla de Contenidos

1. [Prerrequisitos](#prerrequisitos)
2. [Instalación del Entorno](#instalación-del-entorno)
3. [Configuración de la Base de Datos](#configuración-de-la-base-de-datos)
4. [Scripts NPM Disponibles](#scripts-npm-disponibles)
5. [Estructura del Proyecto](#estructura-del-proyecto)
6. [Configuraciones](#configuraciones)
7. [Testing](#testing)
8. [Docker](#docker)
9. [Troubleshooting](#troubleshooting)
10. [Mejores Prácticas](#mejores-prácticas)
11. [Contribución](#contribución)
12. [Soporte](#soporte)

## Prerrequisitos

### Software Requerido

- **PHP**: >= 7.4 (recomendado 8.0+)
- **MySQL**: >= 5.7 o MariaDB >= 10.3
- **Servidor Web**: Apache 2.4+ o Nginx 1.18+
- **Composer**: Para gestión de dependencias PHP
- **Node.js**: >= 14.x (para herramientas de desarrollo frontend)
- **Git**: Para control de versiones

### Extensiones PHP Requeridas

```bash
# Verificar extensiones instaladas
php -m | grep -E "mysqli|json|session|mbstring|openssl|curl"
```

Extensiones necesarias:
- `mysqli` - Conexión a MySQL
- `json` - Manejo de JSON
- `session` - Gestión de sesiones
- `mbstring` - Manejo de strings multibyte
- `openssl` - Funciones de encriptación
- `curl` - Cliente HTTP

### Herramientas de Desarrollo

- **IDE**: VS Code, PhpStorm, o similar
- **Postman/Insomnia**: Para testing de APIs
- **MySQL Workbench**: Para gestión de base de datos
- **MQTT Explorer**: Para debugging MQTT

## Instalación del Entorno

### 1. Clonar el Repositorio

```bash
git clone https://github.com/smartlabs/php-web-app.git
cd php-web-app
```

### 2. Configurar Servidor Web

#### Apache (Laragon/XAMPP)

```apache
# .htaccess en la raíz del proyecto
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Configuración de seguridad
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx

```nginx
server {
    listen 80;
    server_name smartlabs.local;
    root /path/to/smartlabs/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

### 3. Configurar Hosts (Opcional)

```bash
# Windows: C:\Windows\System32\drivers\etc\hosts
# Linux/Mac: /etc/hosts
127.0.0.1 smartlabs.local
```

### 4. Instalar Dependencias

```bash
# Dependencias PHP (si usa Composer)
composer install

# Dependencias Node.js (para herramientas de desarrollo)
npm install
```

## Configuración de la Base de Datos

### 1. Crear Base de Datos

```sql
CREATE DATABASE smartlabs_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smartlabs_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON smartlabs_db.* TO 'smartlabs_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Configurar Conexión

```php
// config/database.php
return [
    'host' => 'localhost',
    'username' => 'smartlabs_user',
    'password' => 'secure_password',
    'database' => 'smartlabs_db',
    'port' => 3306,
    'charset' => 'utf8mb4'
];
```

### 3. Ejecutar Migraciones

```sql
-- Crear tabla de usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Crear tabla de dispositivos
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    serial VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    last_seen TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Crear tabla de habitantes
CREATE TABLE habintants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    rfid VARCHAR(20) NOT NULL,
    registration VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_access TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    UNIQUE KEY unique_device_rfid (device_id, rfid)
);

-- Crear tabla de tráfico
CREATE TABLE traffic (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    rfid VARCHAR(20) NOT NULL,
    action ENUM('entry', 'exit') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    INDEX idx_device_timestamp (device_id, timestamp),
    INDEX idx_rfid_timestamp (rfid, timestamp)
);

-- Insertar usuario administrador por defecto
INSERT INTO users (email, password, name, role) VALUES 
('admin@smartlabs.com', SHA1('admin123'), 'Administrador', 'admin');
```

### 4. Datos de Prueba

```sql
-- Insertar dispositivos de prueba
INSERT INTO devices (user_id, serial, name, type) VALUES 
(1, 'SL001', 'Laboratorio Principal', 'access_control'),
(1, 'SL002', 'Sala de Servidores', 'access_control');

-- Insertar habitantes de prueba
INSERT INTO habintants (device_id, rfid, registration, name) VALUES 
(1, '12345678', '20210001', 'Juan Pérez'),
(1, '87654321', '20210002', 'María García'),
(2, '11223344', '20210003', 'Carlos López');
```

## Scripts NPM Disponibles

```json
{
  "scripts": {
    "dev": "concurrently \"npm run watch-css\" \"npm run watch-js\"",
    "build": "npm run build-css && npm run build-js",
    "watch-css": "sass --watch public/scss:public/css",
    "watch-js": "webpack --mode development --watch",
    "build-css": "sass public/scss:public/css --style compressed",
    "build-js": "webpack --mode production",
    "lint": "eslint public/js/**/*.js",
    "lint:fix": "eslint public/js/**/*.js --fix",
    "test": "jest",
    "test:watch": "jest --watch",
    "serve": "php -S localhost:8000 -t public",
    "docker:build": "docker build -t smartlabs-php .",
    "docker:run": "docker-compose up -d",
    "docker:stop": "docker-compose down"
  }
}
```

### Uso de Scripts

```bash
# Desarrollo con watch automático
npm run dev

# Construir para producción
npm run build

# Linting de código JavaScript
npm run lint
npm run lint:fix

# Ejecutar tests
npm test
npm run test:watch

# Servidor de desarrollo PHP
npm run serve
```

## Estructura del Proyecto

```
smarllabs-php/
├── app/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   └── DeviceController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Device.php
│   │   └── Traffic.php
│   ├── views/
│   │   ├── layouts/
│   │   ├── auth/
│   │   └── dashboard/
│   └── core/
│       ├── autoload.php
│       ├── Controller.php
│       ├── Database.php
│       └── Router.php
├── config/
│   ├── app.php
│   └── database.php
├── public/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── index.php
├── tests/
│   ├── unit/
│   └── integration/
├── docker/
│   ├── Dockerfile
│   └── docker-compose.yml
├── .htaccess
├── composer.json
├── package.json
└── README.md
```

## Configuraciones

### 1. Configuración de Aplicación

```php
// config/app.php
return [
    'name' => 'SMARTLABS',
    'url' => 'http://localhost',
    'debug' => true, // false en producción
    'timezone' => 'America/Mexico_City',
    'locale' => 'es',
    'session' => [
        'timeout' => 3600, // 1 hora
        'name' => 'SMARTLABS_SESSION',
        'secure' => false, // true en HTTPS
        'httponly' => true
    ],
    'security' => [
        'csrf_protection' => true,
        'password_hash' => 'SHA1', // Cambiar a PASSWORD_DEFAULT
        'max_login_attempts' => 5,
        'lockout_duration' => 900 // 15 minutos
    ]
];
```

### 2. Configuración de Logging

```php
// app/core/Logger.php
class Logger {
    private static $logFile = 'logs/app.log';
    
    public static function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message} {$contextStr}" . PHP_EOL;
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    public static function debug($message, $context = []) {
        if (config('app.debug')) {
            self::log('DEBUG', $message, $context);
        }
    }
}
```

### 3. Variables de Entorno

```bash
# .env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=smartlabs_db
DB_USERNAME=smartlabs_user
DB_PASSWORD=secure_password

MQTT_HOST=192.168.0.100
MQTT_PORT=1883
MQTT_USERNAME=smartlabs
MQTT_PASSWORD=mqtt_password

SESSION_TIMEOUT=3600
CSRF_PROTECTION=true
```

## Testing

### 1. Configuración de PHPUnit

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### 2. Test de Ejemplo

```php
// tests/unit/UserTest.php
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase {
    private $user;
    
    protected function setUp(): void {
        $this->user = new User();
    }
    
    public function testUserCreation() {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'Test User'
        ];
        
        $result = $this->user->create($userData);
        $this->assertTrue($result);
    }
    
    public function testUserAuthentication() {
        $email = 'test@example.com';
        $password = 'password123';
        
        $user = $this->user->authenticate($email, $password);
        $this->assertNotNull($user);
        $this->assertEquals($email, $user['email']);
    }
    
    public function testInvalidEmailValidation() {
        $this->expectException(InvalidArgumentException::class);
        
        $userData = [
            'email' => 'invalid-email',
            'password' => 'password123',
            'name' => 'Test User'
        ];
        
        $this->user->create($userData);
    }
}
```

### 3. Ejecutar Tests

```bash
# Ejecutar todos los tests
vendor/bin/phpunit

# Ejecutar tests específicos
vendor/bin/phpunit tests/unit/UserTest.php

# Ejecutar con coverage
vendor/bin/phpunit --coverage-html coverage/
```

## Docker

### 1. Dockerfile

```dockerfile
# Dockerfile
FROM php:8.0-apache

# Instalar extensiones PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Copiar código fuente
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

# Crear directorio de logs
RUN mkdir -p /var/www/html/logs && chown www-data:www-data /var/www/html/logs

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
      - "8080:80"
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=development
    depends_on:
      - db
    networks:
      - smartlabs

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: smartlabs_db
      MYSQL_USER: smartlabs_user
      MYSQL_PASSWORD: secure_password
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql
      - ./docker/init.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - smartlabs

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    environment:
      PMA_HOST: db
      PMA_USER: smartlabs_user
      PMA_PASSWORD: secure_password
    ports:
      - "8081:80"
    depends_on:
      - db
    networks:
      - smartlabs

volumes:
  db_data:

networks:
  smartlabs:
    driver: bridge
```

### 3. Comandos Docker

```bash
# Construir y ejecutar
docker-compose up -d

# Ver logs
docker-compose logs -f web

# Ejecutar comandos en el contenedor
docker-compose exec web bash

# Parar servicios
docker-compose down

# Reconstruir
docker-compose up -d --build
```

## Troubleshooting

### 1. Problemas Comunes

#### Error de Conexión a Base de Datos

```bash
# Verificar conexión
mysql -h localhost -u smartlabs_user -p smartlabs_db

# Verificar configuración PHP
php -r "echo 'MySQL: ' . (extension_loaded('mysqli') ? 'OK' : 'NO') . PHP_EOL;"
```

#### Problemas de Permisos

```bash
# Linux/Mac
sudo chown -R www-data:www-data /path/to/smartlabs
sudo chmod -R 755 /path/to/smartlabs
sudo chmod -R 777 /path/to/smartlabs/logs

# Windows (ejecutar como administrador)
icacls "C:\laragon\www\smartlabs" /grant Everyone:F /T
```

#### Sesiones No Funcionan

```php
// Verificar configuración de sesiones
echo 'Session Save Path: ' . session_save_path() . PHP_EOL;
echo 'Session Name: ' . session_name() . PHP_EOL;
echo 'Session ID: ' . session_id() . PHP_EOL;
```

### 2. Debugging

#### Habilitar Error Reporting

```php
// En desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Logging de errores
ini_set('log_errors', 1);
ini_set('error_log', 'logs/php_errors.log');
```

#### Debug de Base de Datos

```php
// En Database.php
public function query($sql, $params = []) {
    if (config('app.debug')) {
        Logger::debug('SQL Query', ['sql' => $sql, 'params' => $params]);
    }
    
    // ... resto del código
}
```

### 3. Monitoreo de Performance

```php
// app/core/Profiler.php
class Profiler {
    private static $startTime;
    private static $queries = [];
    
    public static function start() {
        self::$startTime = microtime(true);
    }
    
    public static function end() {
        $endTime = microtime(true);
        $executionTime = $endTime - self::$startTime;
        
        Logger::info('Request completed', [
            'execution_time' => $executionTime,
            'memory_usage' => memory_get_peak_usage(true),
            'query_count' => count(self::$queries)
        ]);
    }
    
    public static function addQuery($sql, $time) {
        self::$queries[] = ['sql' => $sql, 'time' => $time];
    }
}
```

## Mejores Prácticas

### 1. Desarrollo

#### Estructura de Código

```php
// Usar namespaces
namespace App\Controllers;

// Documentar funciones
/**
 * Autentica un usuario
 * @param string $email Email del usuario
 * @param string $password Contraseña
 * @return array|null Datos del usuario o null si falla
 */
public function authenticate($email, $password) {
    // Implementación
}

// Validar entrada
public function sanitize($data) {
    if (is_array($data)) {
        return array_map([$this, 'sanitize'], $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
```

#### Manejo de Errores

```php
try {
    $result = $this->database->query($sql, $params);
} catch (Exception $e) {
    Logger::error('Database error', [
        'message' => $e->getMessage(),
        'sql' => $sql,
        'params' => $params
    ]);
    throw new DatabaseException('Error en la consulta');
}
```

### 2. Seguridad

#### Validación de Entrada

```php
class Validator {
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function required($value) {
        return !empty(trim($value));
    }
    
    public static function length($value, $min, $max) {
        $len = strlen($value);
        return $len >= $min && $len <= $max;
    }
}
```

#### Protección CSRF

```php
class CSRF {
    public static function generateToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
    
    public static function validateToken($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
}
```

### 3. Performance

#### Caché de Consultas

```php
class QueryCache {
    private static $cache = [];
    
    public static function get($key) {
        return isset(self::$cache[$key]) ? self::$cache[$key] : null;
    }
    
    public static function set($key, $value, $ttl = 300) {
        self::$cache[$key] = [
            'data' => $value,
            'expires' => time() + $ttl
        ];
    }
    
    public static function isValid($key) {
        return isset(self::$cache[$key]) && 
               self::$cache[$key]['expires'] > time();
    }
}
```

## Contribución

### 1. Flujo de Trabajo

```bash
# Crear rama para nueva funcionalidad
git checkout -b feature/nueva-funcionalidad

# Hacer cambios y commits
git add .
git commit -m "feat: agregar nueva funcionalidad"

# Push y crear Pull Request
git push origin feature/nueva-funcionalidad
```

### 2. Estándares de Código

#### PSR-12 para PHP

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Core\Controller;

class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function index(): void
    {
        $users = $this->userModel->getAll();
        $this->view('users/index', ['users' => $users]);
    }
}
```

#### ESLint para JavaScript

```json
{
  "extends": ["eslint:recommended"],
  "env": {
    "browser": true,
    "es6": true
  },
  "rules": {
    "indent": ["error", 2],
    "quotes": ["error", "single"],
    "semi": ["error", "always"]
  }
}
```

### 3. Mensajes de Commit

```
feat: agregar autenticación de dos factores
fix: corregir error en validación de email
docs: actualizar documentación de API
style: formatear código según PSR-12
refactor: optimizar consultas de base de datos
test: agregar tests para UserController
chore: actualizar dependencias
```

## Soporte

### Contacto

- **Email**: dev@smartlabs.com
- **Slack**: #smartlabs-dev
- **Issues**: [GitHub Issues](https://github.com/smartlabs/php-web-app/issues)

### Recursos

- [Documentación PHP](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [PSR Standards](https://www.php-fig.org/psr/)
- [MQTT Protocol](https://mqtt.org/)

---

**SMARTLABS PHP Web Application** - Desarrollo robusto y escalable para el futuro de los laboratorios inteligentes.