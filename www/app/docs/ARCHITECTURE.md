# SMARTLABS Web Application - Arquitectura Técnica

## Resumen Ejecutivo

La **SMARTLABS Web Application** es una aplicación web PHP que implementa una arquitectura MVC (Model-View-Controller) para la gestión de laboratorios inteligentes. El sistema integra múltiples tecnologías incluyendo PHP, MySQL, MQTT, WebSocket y JavaScript para proporcionar una plataforma completa de monitoreo y gestión de dispositivos IoT.

### Características Clave de la Arquitectura

- **Patrón MVC**: Separación clara de responsabilidades
- **Singleton Database**: Gestión eficiente de conexiones
- **Dual Database**: Base de datos principal y externa para diferentes tipos de datos
- **Real-time Communication**: MQTT y WebSocket para comunicación en tiempo real
- **Session Management**: Sistema robusto de autenticación y sesiones
- **Modular Design**: Estructura modular y extensible

## Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              SMARTLABS Web Application                          │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                Frontend Layer                                   │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐                │
│  │   HTML/CSS/JS   │  │   Bootstrap UI  │  │  Font Awesome   │                │
│  │   Dashboard     │  │   Components    │  │     Icons       │                │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘                │
│                                    │                                            │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐                │
│  │  MQTT Client    │  │ WebSocket Client│  │   AJAX Calls    │                │
│  │   (IoT Comm)    │  │ (Real-time Data)│  │  (API Requests) │                │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘                │
├─────────────────────────────────────────────────────────────────────────────────┤
│                              Application Layer                                  │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                            Router (URL Routing)                        │   │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐      │   │
│  │  │    Auth     │ │  Dashboard  │ │   Device    │ │   Equipment │      │   │
│  │  │ Controller  │ │ Controller  │ │ Controller  │ │ Controller  │      │   │
│  │  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘      │   │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐      │   │
│  │  │  Habitant   │ │    Loan     │ │    Stats    │ │    Base     │      │   │
│  │  │ Controller  │ │ Controller  │ │ Controller  │ │ Controller  │      │   │
│  │  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘      │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                    │                                            │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                              Models Layer                              │   │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐      │   │
│  │  │    User     │ │   Device    │ │ Equipment   │ │  Habitant   │      │   │
│  │  │    Model    │ │    Model    │ │    Model    │ │    Model    │      │   │
│  │  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘      │   │
│  │  ┌─────────────┐ ┌─────────────┐                                      │   │
│  │  │    Loan     │ │   Traffic   │                                      │   │
│  │  │    Model    │ │    Model    │                                      │   │
│  │  └─────────────┘ └─────────────┘                                      │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────────────────────────┤
│                               Data Layer                                        │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                        Database Manager (Singleton)                    │   │
│  │  ┌─────────────────────────────────────────────────────────────────┐   │   │
│  │  │                      MySQLi Connection                         │   │   │
│  │  │  • Prepared Statements  • Parameter Binding  • Error Handling  │   │   │
│  │  └─────────────────────────────────────────────────────────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                    │                                            │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                           Database Connections                         │   │
│  │  ┌─────────────────────────────┐  ┌─────────────────────────────────┐   │   │
│  │  │      Primary Database       │  │      External Database          │   │   │
│  │  │     (localhost:3306)        │  │    (192.168.0.100:4000)        │   │   │
│  │  │                             │  │                                 │   │   │
│  │  │  • users                    │  │  • habintants                   │   │   │
│  │  │  • devices                  │  │  • traffic                      │   │   │
│  │  │  • equipment                │  │  • real-time data               │   │   │
│  │  │  • loans                    │  │                                 │   │   │
│  │  │  • configuration            │  │                                 │   │   │
│  │  └─────────────────────────────┘  └─────────────────────────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────────────────────────┤
│                            External Services Layer                              │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                           IoT Communication                            │   │
│  │  ┌─────────────────────────────┐  ┌─────────────────────────────────┐   │   │
│  │  │        MQTT Broker          │  │      WebSocket Server           │   │   │
│  │  │    (localhost:8083)         │  │     (Real-time Updates)         │   │   │
│  │  │                             │  │                                 │   │   │
│  │  │  • Device Status            │  │  • Live Device Data             │   │   │
│  │  │  • RFID Events              │  │  • User Notifications           │   │   │
│  │  │  • Device Control           │  │  • System Alerts               │   │   │
│  │  │  • Sensor Data              │  │                                 │   │   │
│  │  └─────────────────────────────┘  └─────────────────────────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────────┘
```

## Componentes Principales

### 1. Router (Enrutador)

**Ubicación**: `app/core/Router.php`

**Responsabilidades**:
- Análisis de URLs entrantes
- Mapeo de rutas a controladores y acciones
- Manejo de parámetros de URL
- Gestión de errores 404
- Carga dinámica de controladores

**Características**:
```php
class Router {
    public function route($url) {
        // Parsea URL: /Controller/Action/param1/param2
        $urlArray = explode('/', trim($url, '/'));
        
        $controller = $urlArray[0] ?? $this->defaultController;
        $action = $urlArray[1] ?? $this->defaultAction;
        $params = array_slice($urlArray, 2);
        
        // Carga y ejecuta controlador
        $this->loadController($controller, $action, $params);
    }
}
```

### 2. Base Controller (Controlador Base)

**Ubicación**: `app/core/Controller.php`

**Responsabilidades**:
- Funcionalidades comunes para todos los controladores
- Gestión de sesiones y autenticación
- Renderizado de vistas
- Respuestas JSON
- Sanitización de datos

**Métodos Principales**:
```php
class Controller {
    protected function requireAuth()          // Verificar autenticación
    protected function view($view, $data = []) // Renderizar vista
    protected function json($data)             // Respuesta JSON
    protected function redirect($url)          // Redirección
    protected function sanitize($data)         // Sanitizar datos
}
```

### 3. Database Manager (Gestor de Base de Datos)

**Ubicación**: `app/core/Database.php`

**Patrón**: Singleton

**Responsabilidades**:
- Gestión de conexiones MySQL
- Ejecución de consultas preparadas
- Manejo de errores de base de datos
- Binding de parámetros

**Implementación**:
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
        return $stmt->get_result();
    }
}
```

### 4. Models (Modelos)

**Ubicación**: `app/models/`

**Responsabilidades**:
- Representación de entidades de datos
- Operaciones CRUD
- Validación de datos
- Lógica de negocio específica

**Estructura Común**:
```php
class ModelName {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) { /* ... */ }
    public function find($id) { /* ... */ }
    public function update($id, $data) { /* ... */ }
    public function delete($id) { /* ... */ }
    public function getAll() { /* ... */ }
}
```

### 5. Controllers (Controladores)

**Ubicación**: `app/controllers/`

**Tipos de Controladores**:

#### AuthController
- Gestión de autenticación
- Login/logout de usuarios
- Validación de credenciales
- Gestión de sesiones

#### DashboardController
- Panel principal de la aplicación
- Agregación de datos de múltiples fuentes
- Conexión a base de datos externa
- Gestión de dispositivos del usuario

#### DeviceController
- CRUD de dispositivos
- Monitoreo de estado
- Control remoto
- Historial de actividad

#### EquipmentController
- Gestión de equipos del laboratorio
- Inventario
- Mantenimiento

#### HabitantController
- Gestión de usuarios/habitantes
- Perfiles y permisos
- Actividad de usuarios

#### LoanController
- Sistema de préstamos
- Control de inventario
- Historial de préstamos
- Notificaciones

#### StatsController
- Generación de reportes
- Análisis de datos
- Métricas de rendimiento
- Exportación de datos

## Patrones de Diseño Implementados

### 1. Model-View-Controller (MVC)

**Implementación**:
- **Models**: Lógica de datos y base de datos
- **Views**: Presentación e interfaz de usuario
- **Controllers**: Lógica de negocio y coordinación

**Beneficios**:
- Separación clara de responsabilidades
- Mantenibilidad mejorada
- Reutilización de código
- Testabilidad

### 2. Singleton

**Aplicado en**: Database class

**Propósito**:
- Una única instancia de conexión a base de datos
- Gestión eficiente de recursos
- Consistencia en el acceso a datos

### 3. Front Controller

**Implementación**: Router class

**Propósito**:
- Punto único de entrada
- Centralización del enrutamiento
- Manejo consistente de requests

### 4. Active Record (Parcial)

**Implementación**: Models

**Características**:
- Métodos CRUD en cada modelo
- Representación de tablas como clases
- Simplificación de operaciones de base de datos

## Flujo de Datos

### 1. Request Flow (Flujo de Peticiones)

```
1. HTTP Request → index.php
2. index.php → Router::route()
3. Router → Controller::action()
4. Controller → Model::method()
5. Model → Database::query()
6. Database → MySQL
7. MySQL → Database (result)
8. Database → Model (data)
9. Model → Controller (processed data)
10. Controller → View (render)
11. View → HTTP Response
```

### 2. Authentication Flow (Flujo de Autenticación)

```
1. User submits login form
2. AuthController::login()
3. Input sanitization
4. User::authenticate(email, password)
5. SHA1 password verification
6. Session creation
7. Device loading into session
8. Redirect to Dashboard
```

### 3. Real-time Data Flow (Flujo de Datos en Tiempo Real)

```
1. IoT Device → MQTT Broker
2. MQTT Broker → JavaScript MQTT Client
3. JavaScript Client → DOM Updates
4. Parallel: WebSocket Server → Client
5. Client → Real-time UI Updates
```

### 4. Database Integration Flow (Flujo de Integración de Base de Datos)

```
1. Primary DB (localhost:3306)
   ├── User authentication
   ├── Device management
   └── Application configuration

2. External DB (192.168.0.100:4000)
   ├── Traffic data
   ├── Habitant information
   └── Real-time IoT data
```

## Protocolos de Comunicación

### 1. HTTP/HTTPS

**Uso**:
- Comunicación web estándar
- Requests AJAX
- API endpoints
- File uploads

**Configuración**:
```apache
# .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### 2. MQTT (Message Queuing Telemetry Transport)

**Configuración**:
```javascript
// public/js/config.js
mqtt: {
    brokerUrl: 'ws://localhost:8083/mqtt',
    username: 'jose',
    password: 'public',
    topics: {
        deviceStatus: 'smartlabs/devices/+/status',
        deviceRfid: 'smartlabs/devices/+/rfid',
        deviceControl: 'smartlabs/devices/+/control'
    }
}
```

**Topics Structure**:
- `smartlabs/devices/{device_id}/status` - Estado de dispositivos
- `smartlabs/devices/{device_id}/rfid` - Eventos RFID
- `smartlabs/devices/{device_id}/control` - Control de dispositivos

### 3. WebSocket

**Uso**:
- Comunicación bidireccional en tiempo real
- Notificaciones push
- Updates de estado en vivo
- Sincronización de datos

### 4. MySQL Protocol

**Conexiones**:
- **Primary**: localhost:3306 (aplicación principal)
- **External**: 192.168.0.100:4000 (datos IoT)

**Características**:
- Prepared statements
- Connection pooling (Singleton)
- Error handling
- Transaction support

## Seguridad

### 1. Autenticación y Autorización

**Implementación**:
```php
// Base Controller
protected function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        $this->redirect('/Auth/login');
        exit;
    }
}

// User Model
public function authenticate($email, $password) {
    $hashedPassword = sha1($password); // TODO: Migrar a bcrypt
    $sql = "SELECT * FROM users WHERE users_email = ? AND users_password = ?";
    return $this->db->query($sql, [$email, $hashedPassword]);
}
```

**Características**:
- Session-based authentication
- Password hashing (SHA1 - necesita actualización)
- Automatic session timeout
- Route protection

### 2. Validación de Datos

**Sanitización**:
```php
protected function sanitize($data) {
    if (is_array($data)) {
        return array_map([$this, 'sanitize'], $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
```

**Prepared Statements**:
```php
public function query($sql, $params = []) {
    $stmt = $this->connection->prepare($sql);
    if ($params) {
        $types = $this->getTypes($params);
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}
```

### 3. Medidas de Seguridad Adicionales

**Recomendaciones Implementadas**:
- Input sanitization
- SQL injection prevention
- XSS protection básica
- Session management

**Mejoras Recomendadas**:
- Migrar de SHA1 a bcrypt/Argon2
- Implementar CSRF protection
- Agregar rate limiting
- Configurar security headers
- Implementar 2FA
- Usar HTTPS en producción

## Escalabilidad

### 1. Escalabilidad Horizontal

**Estrategias**:
- Load balancing con múltiples instancias PHP
- Database replication (master-slave)
- CDN para assets estáticos
- Session storage en Redis/Memcached

### 2. Escalabilidad Vertical

**Optimizaciones**:
- PHP OPcache habilitado
- MySQL query optimization
- Connection pooling
- Caching de resultados frecuentes

### 3. Optimización de Performance

**Database**:
```sql
-- Índices recomendados
CREATE INDEX idx_users_email ON users(users_email);
CREATE INDEX idx_devices_user ON devices(devices_user_id);
CREATE INDEX idx_devices_serie ON devices(devices_serie);
CREATE INDEX idx_traffic_device ON traffic(traffic_device);
CREATE INDEX idx_traffic_date ON traffic(traffic_date);
```

**PHP**:
```php
// Configuración recomendada
ini_set('opcache.enable', 1);
ini_set('opcache.memory_consumption', 128);
ini_set('opcache.max_accelerated_files', 4000);
ini_set('opcache.validate_timestamps', 0); // En producción
```

## Monitoreo y Observabilidad

### 1. Logging

**Implementación Actual**:
- Error logs de PHP
- MySQL error logs
- Apache/Nginx access logs

**Mejoras Recomendadas**:
```php
// Logger personalizado
class Logger {
    public static function info($message, $context = []) {
        error_log("[INFO] " . $message . " " . json_encode($context));
    }
    
    public static function error($message, $context = []) {
        error_log("[ERROR] " . $message . " " . json_encode($context));
    }
}
```

### 2. Health Checks

**Endpoint Recomendado**:
```php
// HealthController
public function check() {
    $health = [
        'status' => 'ok',
        'timestamp' => date('c'),
        'database' => $this->checkDatabase(),
        'external_db' => $this->checkExternalDatabase(),
        'mqtt' => $this->checkMqtt(),
        'memory_usage' => memory_get_usage(true),
        'uptime' => $this->getUptime()
    ];
    
    $this->json($health);
}
```

### 3. Métricas

**KPIs Recomendados**:
- Response time promedio
- Throughput (requests/second)
- Error rate
- Database connection pool usage
- Memory usage
- Active sessions
- MQTT message rate

## Configuración de Entornos

### 1. Desarrollo

```php
// config/app.php (desarrollo)
return [
    'debug' => true,
    'display_errors' => true,
    'log_level' => 'debug',
    'cache_enabled' => false,
    'session_timeout' => 7200 // 2 horas
];
```

### 2. Producción

```php
// config/app.php (producción)
return [
    'debug' => false,
    'display_errors' => false,
    'log_level' => 'error',
    'cache_enabled' => true,
    'session_timeout' => 3600 // 1 hora
];
```

### 3. Variables de Entorno

```bash
# .env (recomendado)
APP_ENV=production
DB_HOST=localhost
DB_USER=smartlabs
DB_PASS=secure_password
DB_NAME=emqx
MQTT_BROKER=localhost:8083
MQTT_USER=jose
MQTT_PASS=public
```

## Manejo de Errores

### 1. Estrategias de Recuperación

**Database Fallback**:
```php
public function getConnection() {
    try {
        return $this->primaryConnection;
    } catch (Exception $e) {
        Logger::error('Primary DB failed, using fallback', ['error' => $e->getMessage()]);
        return $this->fallbackConnection;
    }
}
```

**MQTT Reconnection**:
```javascript
// Auto-reconnect MQTT
function connectMQTT() {
    client = mqtt.connect(config.mqtt.brokerUrl, {
        username: config.mqtt.username,
        password: config.mqtt.password,
        reconnectPeriod: 5000,
        connectTimeout: 30000
    });
    
    client.on('error', (error) => {
        console.error('MQTT Error:', error);
        setTimeout(connectMQTT, 10000); // Retry after 10s
    });
}
```

### 2. Graceful Shutdown

```php
// Signal handling
function gracefulShutdown() {
    // Close database connections
    Database::getInstance()->close();
    
    // Save session data
    session_write_close();
    
    // Log shutdown
    Logger::info('Application shutdown completed');
    
    exit(0);
}

register_shutdown_function('gracefulShutdown');
```

## Estrategia de Testing

### 1. Unit Tests

```php
// tests/Unit/UserModelTest.php
class UserModelTest extends PHPUnit\Framework\TestCase {
    public function testUserCreation() {
        $user = new User();
        $result = $user->create([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $this->assertTrue($result);
    }
}
```

### 2. Integration Tests

```php
// tests/Integration/AuthControllerTest.php
class AuthControllerTest extends PHPUnit\Framework\TestCase {
    public function testLoginFlow() {
        $_POST = [
            'email' => 'admin@smartlabs.com',
            'password' => 'admin123'
        ];
        
        $controller = new AuthController();
        $result = $controller->login();
        
        $this->assertArrayHasKey('success', $result);
    }
}
```

### 3. Load Tests

```bash
# Apache Bench
ab -n 1000 -c 10 http://localhost/Dashboard

# Artillery.js
artillery quick --count 100 --num 10 http://localhost/
```

## Deployment

### 1. Containerización

```dockerfile
# Dockerfile
FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

COPY . /var/www/html/
COPY .htaccess /var/www/html/

RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
```

```yaml
# docker-compose.yml
version: '3.8'
services:
  web:
    build: .
    ports:
      - "80:80"
    environment:
      - DB_HOST=db
      - DB_USER=smartlabs
      - DB_PASS=password
    depends_on:
      - db
      
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: emqx
      MYSQL_USER: smartlabs
      MYSQL_PASSWORD: password
    volumes:
      - mysql_data:/var/lib/mysql
      
volumes:
  mysql_data:
```

### 2. CI/CD Pipeline

```yaml
# .github/workflows/deploy.yml
name: Deploy SMARTLABS

on:
  push:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: vendor/bin/phpunit
        
  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to server
        run: |
          rsync -avz --delete ./ user@server:/var/www/html/
          ssh user@server 'sudo systemctl reload apache2'
```

## Roadmap de Mejoras

### Corto Plazo (1-3 meses)

1. **Seguridad**
   - Migrar de SHA1 a bcrypt
   - Implementar CSRF protection
   - Agregar rate limiting

2. **Performance**
   - Implementar caching (Redis)
   - Optimizar consultas SQL
   - Comprimir assets

3. **Monitoring**
   - Logging estructurado
   - Health check endpoints
   - Error tracking (Sentry)

### Mediano Plazo (3-6 meses)

1. **Arquitectura**
   - API REST completa
   - Microservicios para IoT
   - Message queues (RabbitMQ)

2. **Frontend**
   - SPA con React/Vue
   - PWA capabilities
   - Mobile app

3. **DevOps**
   - Kubernetes deployment
   - Automated testing
   - Blue-green deployment

### Largo Plazo (6+ meses)

1. **Escalabilidad**
   - Multi-tenant architecture
   - Global CDN
   - Edge computing

2. **AI/ML**
   - Predictive analytics
   - Anomaly detection
   - Smart recommendations

3. **Integration**
   - Third-party APIs
   - Enterprise systems
   - Cloud services

---

**SMARTLABS Web Application** - Arquitectura robusta y escalable para laboratorios inteligentes del futuro.