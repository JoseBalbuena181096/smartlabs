# Arquitectura del Sistema SMARTLABS

## Visión General

SMARTLABS implementa una arquitectura MVC (Modelo-Vista-Controlador) personalizada en PHP, diseñada para la gestión integral de laboratorios con dispositivos IoT. El sistema está optimizado para el monitoreo en tiempo real, gestión de accesos y administración de recursos.

## Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    CLIENTE (Navegador)                     │
├─────────────────────────────────────────────────────────────┤
│  HTML5 + CSS3 + JavaScript + Bootstrap + AJAX              │
└─────────────────┬───────────────────────────────────────────┘
                  │ HTTP/HTTPS
┌─────────────────▼───────────────────────────────────────────┐
│                 SERVIDOR WEB (Apache)                      │
├─────────────────────────────────────────────────────────────┤
│  .htaccess (URL Rewriting) + mod_rewrite                   │
└─────────────────┬───────────────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────────────┐
│                CONTROLADOR FRONTAL                         │
├─────────────────────────────────────────────────────────────┤
│  index.php (Router + Autoloader)                           │
└─────────────────┬───────────────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────────────┐
│                   CAPA MVC                                  │
├─────────────────┬───────────────┬───────────────────────────┤
│   CONTROLADORES │    MODELOS    │         VISTAS            │
│                 │               │                           │
│ • AuthController│ • User        │ • auth/login.php          │
│ • Dashboard     │ • Device      │ • dashboard/index.php     │
│ • Device        │ • Traffic     │ • device/index.php        │
│ • Habitant      │ • Habitant    │ • layout/header.php       │
│ • Loan          │ • Loan        │ • layout/footer.php       │
│ • Stats         │ • Equipment   │ • habitant/index.php      │
└─────────────────┼───────────────┼───────────────────────────┘
                  │               │
┌─────────────────▼───────────────▼───────────────────────────┐
│                  CAPA DE DATOS                             │
├─────────────────────────────────────────────────────────────┤
│  Database.php (Singleton Pattern + MySQLi)                 │
└─────────────────┬───────────────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────────────┐
│                BASE DE DATOS MySQL                         │
├─────────────────────────────────────────────────────────────┤
│ • users         • devices       • traffic                  │
│ • habintants    • equipment      • loans                   │
│ • becarios      • cards                                    │
└─────────────────────────────────────────────────────────────┘
```

## Componentes del Sistema

### 1. Controlador Frontal (Front Controller)

**Archivo**: `index.php`

```php
// Punto de entrada único
define('BASE_PATH', __DIR__);
require_once 'app/core/autoload.php';
$router = new Router();
$router->route($_GET['url'] ?? '');
```

**Responsabilidades**:
- Punto de entrada único para todas las peticiones
- Carga del autoloader y configuración
- Inicialización del router
- Manejo de errores globales

### 2. Sistema de Enrutamiento

**Archivo**: `app/core/Router.php`

```php
class Router {
    public function route($url) {
        // Parsear URL: /Controller/Action/Params
        $segments = explode('/', trim($url, '/'));
        $controller = $segments[0] ?: $this->defaultController;
        $action = $segments[1] ?: $this->defaultAction;
        $params = array_slice($segments, 2);
        
        // Instanciar y ejecutar
        $controllerClass = $controller . 'Controller';
        $instance = new $controllerClass();
        call_user_func_array([$instance, $action], $params);
    }
}
```

**Características**:
- Enrutamiento basado en convenciones
- Soporte para parámetros dinámicos
- Controlador y acción por defecto
- Manejo de errores 404

### 3. Controlador Base

**Archivo**: `app/core/Controller.php`

```php
abstract class Controller {
    protected $db;
    
    public function __construct() {
        session_start();
        $this->db = Database::getInstance();
    }
    
    protected function requireAuth() {
        if (!isset($_SESSION['logged']) || !$_SESSION['logged']) {
            $this->redirect('Auth/login');
        }
    }
    
    protected function view($view, $data = []) {
        extract($data);
        require_once "app/views/{$view}.php";
    }
}
```

**Funcionalidades**:
- Gestión de sesiones
- Autenticación y autorización
- Renderizado de vistas
- Sanitización de datos
- Redirecciones y respuestas JSON

### 4. Capa de Acceso a Datos

**Archivo**: `app/core/Database.php`

```php
class Database {
    private static $instance = null;
    private $connection;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        if ($params) {
            $stmt->bind_param($this->getTypes($params), ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
```

**Características**:
- Patrón Singleton para conexión única
- Prepared statements para seguridad
- Binding automático de parámetros
- Manejo de errores de conexión

## Patrones de Diseño Implementados

### 1. MVC (Model-View-Controller)

**Separación de responsabilidades**:
- **Modelo**: Lógica de negocio y acceso a datos
- **Vista**: Presentación e interfaz de usuario
- **Controlador**: Coordinación entre modelo y vista

### 2. Singleton

**Implementado en**:
- `Database.php`: Una sola conexión a BD
- Configuración global de la aplicación

### 3. Front Controller

**Beneficios**:
- Punto de entrada centralizado
- Manejo uniforme de peticiones
- Aplicación de filtros globales

### 4. Active Record (Simplificado)

**En los modelos**:
```php
class User {
    public function findByEmail($email) {
        return $this->db->query(
            "SELECT * FROM users WHERE users_email = ?", 
            [$email]
        );
    }
}
```

## Flujo de Datos

### 1. Petición HTTP

```
Cliente → Apache → .htaccess → index.php → Router
```

### 2. Procesamiento MVC

```
Router → Controller → Model → Database
                ↓
            View ← Controller ← Model
```

### 3. Respuesta

```
View → Controller → Router → index.php → Apache → Cliente
```

## Configuración del Sistema

### 1. Configuración de Aplicación

**Archivo**: `config/app.php`

```php
return [
    'app_name' => 'SMARTLABS',
    'app_url' => 'http://localhost',
    'default_controller' => 'Dashboard',
    'default_action' => 'index',
    'session_timeout' => 3600,
    'assets_path' => '/public'
];
```

### 2. Configuración de Base de Datos

**Archivo**: `config/database.php`

```php
return [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'smartlabs',
    'port' => 3306,
    'charset' => 'utf8mb4'
];
```

### 3. Configuración de Apache

**Archivo**: `.htaccess`

```apache
RewriteEngine On

# Servir archivos estáticos
RewriteCond %{REQUEST_URI} ^/(js|css|images|assets)/
RewriteRule ^(.*)$ public/$1 [L]

# Redireccionar a index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [L,QSA]
```

## Seguridad

### 1. Autenticación

```php
// En Controller.php
protected function requireAuth() {
    if (!isset($_SESSION['logged']) || !$_SESSION['logged']) {
        $this->redirect('Auth/login');
        exit();
    }
}
```

### 2. Sanitización de Datos

```php
protected function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
```

### 3. Prepared Statements

```php
public function query($sql, $params = []) {
    $stmt = $this->connection->prepare($sql);
    if ($params) {
        $stmt->bind_param($this->getTypes($params), ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
```

## Escalabilidad

### 1. Optimizaciones de Base de Datos

- **Índices**: En campos de búsqueda frecuente
- **Conexión persistente**: Para reducir overhead
- **Query optimization**: Uso de EXPLAIN para análisis

### 2. Caché

```php
// Implementación futura de caché
class Cache {
    public static function get($key) {
        return $_SESSION['cache'][$key] ?? null;
    }
    
    public static function set($key, $value, $ttl = 3600) {
        $_SESSION['cache'][$key] = [
            'data' => $value,
            'expires' => time() + $ttl
        ];
    }
}
```

### 3. Separación de Servicios

- **API REST**: Para comunicación con dispositivos IoT
- **WebSocket Server**: Para actualizaciones en tiempo real
- **Queue System**: Para procesamiento asíncrono

## Monitoreo y Observabilidad

### 1. Logging

```php
class Logger {
    public static function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $log = "[{$timestamp}] {$level}: {$message}";
        
        if ($context) {
            $log .= ' ' . json_encode($context);
        }
        
        error_log($log, 3, '/var/log/smartlabs.log');
    }
}
```

### 2. Métricas

- Tiempo de respuesta por endpoint
- Número de usuarios activos
- Errores de base de datos
- Uso de memoria y CPU

### 3. Health Checks

```php
class HealthCheck {
    public static function database() {
        try {
            $db = Database::getInstance();
            $db->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
```

## Integración con Sistemas Externos

### 1. Dispositivos IoT

```php
class DeviceAPI {
    public function getStatus($deviceId) {
        // Conexión a base de datos externa
        $external_db = new mysqli('192.168.0.100', 'root', 'pass', 'emqx', 4000);
        
        $result = $external_db->query(
            "SELECT * FROM device_status WHERE device_id = '{$deviceId}'"
        );
        
        return $result->fetch_assoc();
    }
}
```

### 2. WebSocket para Tiempo Real

```javascript
// Cliente JavaScript
const ws = new WebSocket('ws://localhost:8080');
ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    updateDashboard(data);
};
```

## Consideraciones de Deployment

### 1. Entorno de Desarrollo

```bash
# Laragon (Windows)
c:\laragon\www\smartlabs

# XAMPP
c:\xampp\htdocs\smartlabs

# Docker
docker-compose up -d
```

### 2. Entorno de Producción

```apache
# VirtualHost Apache
<VirtualHost *:80>
    ServerName smartlabs.com
    DocumentRoot /var/www/smartlabs
    
    <Directory /var/www/smartlabs>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 3. Optimizaciones de Producción

- **OPcache**: Para acelerar PHP
- **Gzip compression**: Para reducir transferencia
- **CDN**: Para archivos estáticos
- **Load balancer**: Para múltiples instancias

## Roadmap Técnico

### Corto Plazo

- [ ] Migrar de SHA1 a bcrypt
- [ ] Implementar CSRF protection
- [ ] Agregar validación de formularios
- [ ] Optimizar queries de base de datos

### Mediano Plazo

- [ ] Implementar API REST completa
- [ ] Agregar sistema de caché
- [ ] Implementar logging estructurado
- [ ] Agregar tests unitarios

### Largo Plazo

- [ ] Migrar a framework moderno (Laravel/Symfony)
- [ ] Implementar microservicios
- [ ] Agregar CI/CD pipeline
- [ ] Implementar monitoring avanzado

---

**Versión**: 2.0.0  
**Última actualización**: Diciembre 2024  
**Arquitecto**: Equipo SMARTLABS