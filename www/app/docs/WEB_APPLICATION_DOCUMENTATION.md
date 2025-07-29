# SmartLabs Web Application - Documentación Técnica

## 📋 Índice

1. [Arquitectura MVC](#arquitectura-mvc)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Configuración](#configuración)
4. [Controladores](#controladores)
5. [Modelos](#modelos)
6. [Vistas](#vistas)
7. [Routing](#routing)
8. [Base de Datos](#base-de-datos)
9. [Autenticación y Sesiones](#autenticación-y-sesiones)
10. [Seguridad](#seguridad)
11. [APIs y Servicios](#apis-y-servicios)
12. [Deployment](#deployment)

## 🏗️ Arquitectura MVC

### Patrón Model-View-Controller
La aplicación web sigue el patrón MVC con separación clara de responsabilidades:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     Router      │───▶│   Controller    │───▶│     Model       │
│   (Routes)      │    │   (Logic)       │    │   (Data)        │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   index.php     │    │     Views       │    │   Database      │
│ (Entry Point)   │    │  (Templates)    │    │   (MySQL)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Flujo de Solicitudes
1. **Request** → `public/index.php` (Front Controller)
2. **Router** → Analiza URL y determina controlador/método
3. **Controller** → Procesa lógica de negocio
4. **Model** → Interactúa con base de datos
5. **View** → Renderiza respuesta HTML
6. **Response** → Envía al cliente

## 📁 Estructura del Proyecto

```
app/
├── controllers/
│   ├── AuthController.php       # Autenticación y login
│   ├── DashboardController.php  # Dashboard principal
│   ├── DeviceController.php     # Gestión de dispositivos
│   ├── EquipmentController.php  # Gestión de equipos
│   ├── HabitantController.php   # Gestión de usuarios/residentes
│   ├── LoanController.php       # Préstamos de usuarios
│   ├── LoanAdminController.php  # Administración de préstamos
│   ├── BecariosController.php   # Gestión de becarios
│   └── StatsController.php      # Estadísticas y reportes
├── core/
│   ├── Controller.php           # Controlador base
│   ├── Database.php             # Clase de base de datos
│   └── Router.php               # Sistema de routing
├── models/
│   ├── User.php                 # Modelo de usuario
│   ├── Device.php               # Modelo de dispositivo
│   ├── Equipment.php            # Modelo de equipo
│   ├── Loan.php                 # Modelo de préstamo
│   └── Card.php                 # Modelo de tarjeta RFID
├── views/
│   ├── layout/
│   │   ├── header.php           # Header común
│   │   ├── sidebar.php          # Sidebar de navegación
│   │   └── footer.php           # Footer común
│   ├── auth/
│   │   ├── login.php            # Página de login
│   │   └── register.php         # Página de registro
│   ├── dashboard/
│   │   └── index.php            # Dashboard principal
│   ├── device/
│   │   ├── index.php            # Lista de dispositivos
│   │   ├── create.php           # Crear dispositivo
│   │   └── edit.php             # Editar dispositivo
│   ├── equipment/
│   │   ├── index.php            # Lista de equipos
│   │   ├── create.php           # Crear equipo
│   │   └── edit.php             # Editar equipo
│   ├── habitant/
│   │   ├── index.php            # Gestión de usuarios
│   │   ├── create.php           # Registrar usuario
│   │   └── edit.php             # Editar usuario
│   ├── loan/
│   │   ├── index.php            # Préstamos de usuario
│   │   └── history.php          # Historial de préstamos
│   ├── loan_admin/
│   │   ├── index.php            # Administración de préstamos
│   │   ├── search.php           # Búsqueda de usuarios
│   │   └── return.php           # Devolución de equipos
│   ├── becarios/
│   │   └── index.php            # Gestión de becarios
│   └── stats/
│       ├── devices.php          # Estadísticas de dispositivos
│       └── users.php            # Estadísticas de usuarios
└── helpers/
    ├── functions.php            # Funciones auxiliares
    └── validators.php           # Validadores de datos
```

## ⚙️ Configuración

### Configuración Principal
```php
<?php
// config/app.php
return [
    'app_name' => 'SmartLabs',
    'app_version' => '1.0.0',
    'app_url' => 'http://localhost',
    'timezone' => 'America/Mexico_City',
    'charset' => 'UTF-8',
    'language' => 'es',
    
    // Configuración de sesiones
    'session' => [
        'name' => 'SMARTLABS_SESSION',
        'lifetime' => 7200, // 2 horas
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true
    ],
    
    // Configuración de seguridad
    'security' => [
        'csrf_protection' => true,
        'xss_protection' => true,
        'sql_injection_protection' => true,
        'password_hash_algo' => 'sha1' // Legacy, migrar a bcrypt
    ],
    
    // Configuración de uploads
    'uploads' => [
        'max_file_size' => '10M',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
        'upload_path' => 'uploads/'
    ]
];
```

### Configuración de Base de Datos
```php
<?php
// config/database.php
return [
    'default' => 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_NAME'] ?? 'emqx',
            'username' => $_ENV['DB_USER'] ?? 'emqxuser',
            'password' => $_ENV['DB_PASSWORD'] ?? 'emqxpass',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        ],
        
        // Base de datos externa para dashboard
        'external' => [
            'driver' => 'mysql',
            'host' => $_ENV['EXTERNAL_DB_HOST'] ?? 'external-db.com',
            'port' => $_ENV['EXTERNAL_DB_PORT'] ?? '3306',
            'database' => $_ENV['EXTERNAL_DB_NAME'] ?? 'external_db',
            'username' => $_ENV['EXTERNAL_DB_USER'] ?? 'external_user',
            'password' => $_ENV['EXTERNAL_DB_PASSWORD'] ?? 'external_pass',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci'
        ]
    ]
];
```

## 🎮 Controladores

### Controlador Base
```php
<?php
// app/core/Controller.php
class Controller {
    protected $db;
    protected $config;
    
    public function __construct() {
        $this->db = new Database();
        $this->config = require_once 'config/app.php';
        $this->startSession();
    }
    
    /**
     * Iniciar sesión si no está activa
     */
    protected function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Verificar autenticación
     */
    protected function requireAuth() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            $this->redirect('/Auth/login');
            exit;
        }
    }
    
    /**
     * Renderizar vista
     */
    protected function view($view, $data = []) {
        extract($data);
        
        // Incluir header
        include_once 'app/views/layout/header.php';
        
        // Incluir sidebar si el usuario está logueado
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
            include_once 'app/views/layout/sidebar.php';
        }
        
        // Incluir vista específica
        $viewFile = "app/views/{$view}.php";
        if (file_exists($viewFile)) {
            include_once $viewFile;
        } else {
            throw new Exception("Vista no encontrada: {$view}");
        }
        
        // Incluir footer
        include_once 'app/views/layout/footer.php';
    }
    
    /**
     * Redireccionar
     */
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Respuesta JSON
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Sanitizar datos de entrada
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar email
     */
    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Hash de contraseña (legacy SHA1)
     */
    protected function hashPassword($password) {
        return sha1($password); // TODO: Migrar a password_hash()
    }
    
    /**
     * Verificar contraseña
     */
    protected function verifyPassword($password, $hash) {
        return sha1($password) === $hash; // TODO: Migrar a password_verify()
    }
    
    /**
     * Obtener usuario actual
     */
    protected function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM habintants WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    /**
     * Verificar permisos de administrador
     */
    protected function requireAdmin() {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        if (!$user || $user['role'] !== 'admin') {
            $this->redirect('/Dashboard');
            exit;
        }
    }
    
    /**
     * Generar token CSRF
     */
    protected function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verificar token CSRF
     */
    protected function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
```

### AuthController
```php
<?php
// app/controllers/AuthController.php
class AuthController extends Controller {
    
    /**
     * Mostrar formulario de login
     */
    public function login() {
        // Si ya está logueado, redirigir al dashboard
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
            $this->redirect('/Dashboard');
        }
        
        $this->view('auth/login', [
            'csrf_token' => $this->generateCSRFToken()
        ]);
    }
    
    /**
     * Procesar login
     */
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/Auth/login');
        }
        
        // Verificar CSRF token
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/Auth/login');
        }
        
        $email = $this->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validar campos
        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Email y contraseña son requeridos';
            $this->redirect('/Auth/login');
        }
        
        if (!$this->validateEmail($email)) {
            $_SESSION['error'] = 'Email inválido';
            $this->redirect('/Auth/login');
        }
        
        try {
            // Buscar usuario
            $stmt = $this->db->prepare("SELECT * FROM habintants WHERE email = ? AND active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && $this->verifyPassword($password, $user['password'])) {
                // Login exitoso
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_registration'] = $user['registration'];
                $_SESSION['user_role'] = $user['role'] ?? 'user';
                
                // Obtener dispositivos del usuario
                $this->loadUserDevices($user['id']);
                
                // Registrar último login
                $this->updateLastLogin($user['id']);
                
                $this->redirect('/Dashboard');
            } else {
                $_SESSION['error'] = 'Credenciales inválidas';
                $this->redirect('/Auth/login');
            }
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $_SESSION['error'] = 'Error interno del servidor';
            $this->redirect('/Auth/login');
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        session_destroy();
        $this->redirect('/Auth/login');
    }
    
    /**
     * Cargar dispositivos del usuario en sesión
     */
    private function loadUserDevices($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.device_serie, t.device_name 
                FROM traffic t
                JOIN user_devices ud ON t.device_serie = ud.device_serie
                WHERE ud.user_id = ? AND t.active = 1 AND ud.active = 1
            ");
            $stmt->execute([$userId]);
            $_SESSION['user_devices'] = $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error cargando dispositivos: " . $e->getMessage());
            $_SESSION['user_devices'] = [];
        }
    }
    
    /**
     * Actualizar último login
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE habintants SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error actualizando último login: " . $e->getMessage());
        }
    }
}
```

### DashboardController
```php
<?php
// app/controllers/DashboardController.php
class DashboardController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        try {
            $data = [
                'user' => $this->getCurrentUser(),
                'devices' => $this->getUserDevices(),
                'traffic_stats' => $this->getTrafficStats(),
                'recent_activity' => $this->getRecentActivity(),
                'system_status' => $this->getSystemStatus()
            ];
            
            $this->view('dashboard/index', $data);
        } catch (Exception $e) {
            error_log("Error en dashboard: " . $e->getMessage());
            $_SESSION['error'] = 'Error cargando dashboard';
            $this->view('dashboard/index', ['error' => true]);
        }
    }
    
    /**
     * Obtener dispositivos del usuario
     */
    private function getUserDevices() {
        $stmt = $this->db->prepare("
            SELECT 
                t.device_serie,
                t.device_name,
                t.status,
                t.last_update,
                t.location
            FROM traffic t
            JOIN user_devices ud ON t.device_serie = ud.device_serie
            WHERE ud.user_id = ? AND t.active = 1 AND ud.active = 1
            ORDER BY t.device_name
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener estadísticas de tráfico
     */
    private function getTrafficStats() {
        // Conectar a base de datos externa
        $externalDb = new Database('external');
        
        $stmt = $externalDb->prepare("
            SELECT 
                COUNT(*) as total_devices,
                SUM(CASE WHEN status = 'on' THEN 1 ELSE 0 END) as active_devices,
                SUM(CASE WHEN status = 'off' THEN 1 ELSE 0 END) as inactive_devices
            FROM traffic 
            WHERE active = 1
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Obtener actividad reciente
     */
    private function getRecentActivity() {
        $stmt = $this->db->prepare("
            SELECT 
                'device_control' as type,
                CONCAT('Dispositivo ', device_serie, ' ', action) as description,
                created_at as timestamp
            FROM device_logs 
            WHERE user_id = ?
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener estado del sistema
     */
    private function getSystemStatus() {
        return [
            'api_status' => $this->checkAPIStatus(),
            'mqtt_status' => $this->checkMQTTStatus(),
            'database_status' => true // Siempre true si llegamos aquí
        ];
    }
    
    /**
     * Verificar estado de la API
     */
    private function checkAPIStatus() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    /**
     * Verificar estado de MQTT
     */
    private function checkMQTTStatus() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/api/mqtt/status');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($result, true);
            return $data['data']['mqtt_connected'] ?? false;
        }
        
        return false;
    }
}
```

## 📊 Modelos

### Modelo Base
```php
<?php
// app/models/Model.php
abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Encontrar por ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener todos los registros
     */
    public function all($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Crear nuevo registro
     */
    public function create($data) {
        $data = $this->filterFillable($data);
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar registro
     */
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        $fields = [];
        
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = ?";
        
        $params = array_values($data);
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Eliminar registro
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Filtrar campos permitidos
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Ocultar campos sensibles
     */
    protected function hideFields($data) {
        if (empty($this->hidden)) {
            return $data;
        }
        
        return array_diff_key($data, array_flip($this->hidden));
    }
}
```

### User Model
```php
<?php
// app/models/User.php
class User extends Model {
    protected $table = 'habintants';
    protected $fillable = ['registration', 'name', 'email', 'password', 'role', 'active'];
    protected $hidden = ['password'];
    
    /**
     * Encontrar usuario por email
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Encontrar usuario por matrícula
     */
    public function findByRegistration($registration) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE registration = ?");
        $stmt->execute([$registration]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener dispositivos del usuario
     */
    public function getDevices($userId) {
        $stmt = $this->db->prepare("
            SELECT t.*, ud.assigned_at
            FROM traffic t
            JOIN user_devices ud ON t.device_serie = ud.device_serie
            WHERE ud.user_id = ? AND t.active = 1 AND ud.active = 1
            ORDER BY t.device_name
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Asignar dispositivo al usuario
     */
    public function assignDevice($userId, $deviceSerie) {
        $stmt = $this->db->prepare("
            INSERT INTO user_devices (user_id, device_serie, assigned_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE active = 1, assigned_at = NOW()
        ");
        return $stmt->execute([$userId, $deviceSerie]);
    }
    
    /**
     * Desasignar dispositivo del usuario
     */
    public function unassignDevice($userId, $deviceSerie) {
        $stmt = $this->db->prepare("
            UPDATE user_devices 
            SET active = 0 
            WHERE user_id = ? AND device_serie = ?
        ");
        return $stmt->execute([$userId, $deviceSerie]);
    }
    
    /**
     * Obtener préstamos activos
     */
    public function getActiveLoans($userId) {
        $stmt = $this->db->prepare("
            SELECT l.*, e.name as equipment_name, e.description
            FROM loans l
            JOIN equipment e ON l.equipment_id = e.id
            WHERE l.user_id = ? AND l.status = 'active'
            ORDER BY l.borrowed_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Crear usuario con hash de contraseña
     */
    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password'] = sha1($data['password']); // TODO: Migrar a password_hash
        }
        
        return $this->create($data);
    }
    
    /**
     * Actualizar contraseña
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = sha1($newPassword); // TODO: Migrar a password_hash
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }
}
```

## 🎨 Vistas

### Layout Principal
```php
<?php
// app/views/layout/header.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'SmartLabs' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="/assets/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/styles/app.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .device-card {
            transition: transform 0.2s;
        }
        
        .device-card:hover {
            transform: translateY(-2px);
        }
        
        .status-on {
            color: #28a745;
        }
        
        .status-off {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="/Dashboard">
                    <i class="fa fa-flask"></i> SmartLabs
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fa fa-user"></i> <?= $_SESSION['user_name'] ?? 'Usuario' ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/Profile"><i class="fa fa-user"></i> Perfil</a></li>
                                <li><a class="dropdown-item" href="/Settings"><i class="fa fa-cog"></i> Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/Auth/logout"><i class="fa fa-sign-out"></i> Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <div style="margin-top: 56px;"> <!-- Offset for fixed navbar -->
    <?php endif; ?>
    
    <!-- Mensajes Flash -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?= $_SESSION['warning'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>
```

### Sidebar
```php
<?php
// app/views/layout/sidebar.php
$currentPath = $_SERVER['REQUEST_URI'];
?>
<div class="sidebar position-fixed top-0 start-0 bg-primary" style="width: 250px; height: 100vh; margin-top: 56px; overflow-y: auto;">
    <div class="p-3">
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a class="nav-link text-white <?= strpos($currentPath, '/Dashboard') === 0 ? 'active' : '' ?>" href="/Dashboard">
                    <i class="fa fa-dashboard"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white <?= strpos($currentPath, '/Device') === 0 ? 'active' : '' ?>" href="/Device">
                    <i class="fa fa-microchip"></i> Dispositivos
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white <?= strpos($currentPath, '/Equipment') === 0 ? 'active' : '' ?>" href="/Equipment">
                    <i class="fa fa-wrench"></i> Equipos
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white <?= strpos($currentPath, '/Loan') === 0 ? 'active' : '' ?>" href="/Loan">
                    <i class="fa fa-exchange"></i> Mis Préstamos
                </a>
            </li>
            
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <li class="nav-item mt-3">
                    <h6 class="text-white-50 text-uppercase">Administración</h6>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link text-white <?= strpos($currentPath, '/Habitant') === 0 ? 'active' : '' ?>" href="/Habitant">
                        <i class="fa fa-users"></i> Usuarios
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link text-white <?= strpos($currentPath, '/LoanAdmin') === 0 ? 'active' : '' ?>" href="/LoanAdmin">
                        <i class="fa fa-list-alt"></i> Gestión Préstamos
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link text-white <?= strpos($currentPath, '/Becarios') === 0 ? 'active' : '' ?>" href="/Becarios">
                        <i class="fa fa-graduation-cap"></i> Becarios
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link text-white <?= strpos($currentPath, '/Stats') === 0 ? 'active' : '' ?>" href="/Stats">
                        <i class="fa fa-bar-chart"></i> Estadísticas
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="nav-item mt-3">
                <h6 class="text-white-50 text-uppercase">Sistema</h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white" href="#" onclick="checkSystemStatus()">
                    <i class="fa fa-heartbeat"></i> Estado del Sistema
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white" href="/Help">
                    <i class="fa fa-question-circle"></i> Ayuda
                </a>
            </li>
        </ul>
    </div>
</div>

<script>
function checkSystemStatus() {
    // Verificar estado de servicios
    fetch('/api/system/status')
        .then(response => response.json())
        .then(data => {
            let message = 'Estado del Sistema:\n';
            message += `API: ${data.api ? '✅ Activo' : '❌ Inactivo'}\n`;
            message += `MQTT: ${data.mqtt ? '✅ Activo' : '❌ Inactivo'}\n`;
            message += `Base de Datos: ${data.database ? '✅ Activo' : '❌ Inactivo'}`;
            alert(message);
        })
        .catch(error => {
            alert('Error verificando estado del sistema');
        });
}
</script>
```

## 🔐 Seguridad

### Validación y Sanitización
```php
<?php
// app/helpers/validators.php
class Validators {
    
    /**
     * Validar matrícula
     */
    public static function validateRegistration($registration) {
        return preg_match('/^[A-Z]\d{8}$/', $registration);
    }
    
    /**
     * Validar email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validar contraseña
     */
    public static function validatePassword($password) {
        // Mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
    }
    
    /**
     * Sanitizar entrada
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar CSRF token
     */
    public static function validateCSRF($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Prevenir inyección SQL
     */
    public static function preventSQLInjection($input) {
        // Remover caracteres peligrosos
        $dangerous = ['--', ';', '/*', '*/', 'xp_', 'sp_', 'EXEC', 'EXECUTE', 'DROP', 'DELETE', 'INSERT', 'UPDATE'];
        return str_ireplace($dangerous, '', $input);
    }
}
```

### Middleware de Seguridad
```php
<?php
// app/middleware/SecurityMiddleware.php
class SecurityMiddleware {
    
    /**
     * Headers de seguridad
     */
    public static function setSecurityHeaders() {
        // Prevenir XSS
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevenir clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevenir MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Política de referrer
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");
        
        // HSTS (solo en HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    /**
     * Rate limiting básico
     */
    public static function rateLimit($identifier, $maxRequests = 60, $timeWindow = 3600) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 0,
                'reset_time' => time() + $timeWindow
            ];
        }
        
        $rateData = $_SESSION[$key];
        
        // Reset si ha pasado el tiempo
        if (time() > $rateData['reset_time']) {
            $_SESSION[$key] = [
                'count' => 1,
                'reset_time' => time() + $timeWindow
            ];
            return true;
        }
        
        // Incrementar contador
        $_SESSION[$key]['count']++;
        
        // Verificar límite
        if ($_SESSION[$key]['count'] > $maxRequests) {
            http_response_code(429);
            die('Too Many Requests');
        }
        
        return true;
    }
    
    /**
     * Validar origen de la solicitud
     */
    public static function validateOrigin() {
        $allowedOrigins = [
            'http://localhost',
            'https://smartlabs.com',
            'https://www.smartlabs.com'
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        
        if (!empty($origin)) {
            $parsedOrigin = parse_url($origin);
            $originHost = $parsedOrigin['scheme'] . '://' . $parsedOrigin['host'];
            
            if (!in_array($originHost, $allowedOrigins)) {
                http_response_code(403);
                die('Forbidden Origin');
            }
        }
    }
}
```

## 🚀 Deployment

### Configuración de Apache
```apache
# .htaccess
RewriteEngine On

# Redirigir todo a index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Headers de seguridad
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options nosniff
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Comprimir archivos
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

# Cache de archivos estáticos
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

# Proteger archivos sensibles
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>
```

### Docker para Producción
```dockerfile
# Dockerfile
FROM php:8.2-apache

# Instalar extensiones PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar módulos Apache
RUN a2enmod rewrite headers

# Copiar configuración Apache
COPY docker/web/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/web/php.ini /usr/local/etc/php/

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar código fuente
COPY . .

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN mkdir -p logs && chown www-data:www-data logs

# Exponer puerto
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

CMD ["apache2-foreground"]
```

---

**Versión**: 1.0  
**Última actualización**: Enero 2024  
**Mantenido por**: Equipo SmartLabs