# Ejemplos de Uso - SMARTLABS

## Configuración Inicial

### 1. Configuración Básica

```php
// config/app.php
return [
    'app_name' => 'SMARTLABS',
    'app_url' => 'http://localhost',
    'default_controller' => 'Dashboard',
    'default_action' => 'index',
    'assets_path' => '/public',
    'session_timeout' => 3600
];

// config/database.php
return [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'smartlabs',
    'port' => 3306,
    'charset' => 'utf8mb4'
];
```

### 2. Estructura de Base de Datos

```sql
-- Crear base de datos
CREATE DATABASE smartlabs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartlabs;

-- Tabla de usuarios
CREATE TABLE users (
    users_id INT AUTO_INCREMENT PRIMARY KEY,
    users_email VARCHAR(255) UNIQUE NOT NULL,
    users_password VARCHAR(255) NOT NULL,
    users_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de dispositivos
CREATE TABLE devices (
    devices_id INT AUTO_INCREMENT PRIMARY KEY,
    devices_alias VARCHAR(255) NOT NULL,
    devices_serie VARCHAR(255) UNIQUE NOT NULL,
    devices_user_id INT NOT NULL,
    devices_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (devices_user_id) REFERENCES users(users_id) ON DELETE CASCADE
);

-- Tabla de tráfico
CREATE TABLE traffic (
    traffic_id INT AUTO_INCREMENT PRIMARY KEY,
    traffic_device VARCHAR(255) NOT NULL,
    traffic_hab_id INT,
    traffic_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    traffic_type ENUM('entry', 'exit') DEFAULT 'entry'
);

-- Tabla de habitantes
CREATE TABLE habintants (
    hab_id INT AUTO_INCREMENT PRIMARY KEY,
    hab_name VARCHAR(255) NOT NULL,
    hab_registration VARCHAR(100) UNIQUE,
    hab_email VARCHAR(255),
    hab_phone VARCHAR(20),
    hab_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Ejemplos de Controladores

### 1. Controlador de Autenticación

```php
<?php
// app/controllers/AuthController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends Controller {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }
    
    public function login() {
        $msg = "";
        $email = "";
        
        if ($_POST) {
            $email = $this->sanitize($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($email) || empty($password)) {
                $msg = "Todos los campos son obligatorios";
            } elseif (!$this->validateEmail($email)) {
                $msg = "Email inválido";
            } else {
                $user = $this->userModel->authenticate($email, $password);
                
                if ($user) {
                    $_SESSION['logged'] = true;
                    $_SESSION['user_id'] = $user['users_id'];
                    $_SESSION['users_email'] = $user['users_email'];
                    
                    $this->redirect('Dashboard');
                } else {
                    $msg = "Credenciales incorrectas";
                }
            }
        }
        
        $this->view('auth/login', [
            'msg' => $msg,
            'email' => $email
        ]);
    }
    
    public function register() {
        $msg = "";
        $email = "";
        
        if ($_POST) {
            $email = $this->sanitize($_POST['email']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (empty($email) || empty($password) || empty($confirmPassword)) {
                $msg = "Todos los campos son obligatorios";
            } elseif (!$this->validateEmail($email)) {
                $msg = "Email inválido";
            } elseif ($password !== $confirmPassword) {
                $msg = "Las contraseñas no coinciden";
            } else {
                $existingUser = $this->userModel->findByEmail($email);
                if ($existingUser) {
                    $msg = "El email ya está registrado";
                } else {
                    if ($this->userModel->create($email, $password)) {
                        $this->redirect('Auth/login?success=1');
                    } else {
                        $msg = "Error al registrar usuario";
                    }
                }
            }
        }
        
        $this->view('auth/register', [
            'msg' => $msg,
            'email' => $email
        ]);
    }
    
    public function logout() {
        session_destroy();
        $this->redirect('Auth/login');
    }
}
```

### 2. Controlador de Dashboard

```php
<?php
// app/controllers/DashboardController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Device.php';
require_once __DIR__ . '/../models/Traffic.php';

class DashboardController extends Controller {
    private $deviceModel;
    private $trafficModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->deviceModel = new Device();
        $this->trafficModel = new Traffic();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        $selectedDevice = $_GET['device'] ?? '';
        
        // Obtener dispositivos del usuario
        $devices = $this->deviceModel->findByUserId($userId);
        
        // Seleccionar primer dispositivo si no hay uno seleccionado
        if (!$selectedDevice && !empty($devices)) {
            $selectedDevice = $devices[0]['devices_serie'];
        }
        
        // Obtener estadísticas
        $stats = $this->getStats($userId, $selectedDevice);
        
        // Obtener tráfico reciente
        $recentTraffic = $this->getRecentTraffic($selectedDevice);
        
        $this->view('dashboard/index', [
            'devices' => $devices,
            'selectedDevice' => $selectedDevice,
            'stats' => $stats,
            'recentTraffic' => $recentTraffic
        ]);
    }
    
    public function refresh() {
        header('Content-Type: application/json');
        
        $selectedDevice = $_GET['device'] ?? '';
        $traffic = $this->getRecentTraffic($selectedDevice);
        
        echo json_encode([
            'traffic' => $traffic,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
    
    private function getStats($userId, $selectedDevice = '') {
        $deviceFilter = $selectedDevice ? [$selectedDevice] : 
            array_column($this->deviceModel->findByUserId($userId), 'devices_serie');
        
        if (empty($deviceFilter)) {
            return [
                'totalAccess' => 0,
                'todayAccess' => 0,
                'thisWeekAccess' => 0,
                'uniqueUsers' => 0
            ];
        }
        
        $placeholders = str_repeat('?,', count($deviceFilter) - 1) . '?';
        
        // Total de accesos
        $totalResult = $this->db->query(
            "SELECT COUNT(*) as total FROM traffic WHERE traffic_device IN ($placeholders)",
            $deviceFilter
        );
        
        // Accesos de hoy
        $todayResult = $this->db->query(
            "SELECT COUNT(*) as today FROM traffic WHERE traffic_device IN ($placeholders) AND DATE(traffic_date) = CURDATE()",
            $deviceFilter
        );
        
        // Accesos de esta semana
        $weekResult = $this->db->query(
            "SELECT COUNT(*) as week FROM traffic WHERE traffic_device IN ($placeholders) AND YEARWEEK(traffic_date) = YEARWEEK(NOW())",
            $deviceFilter
        );
        
        // Usuarios únicos
        $uniqueResult = $this->db->query(
            "SELECT COUNT(DISTINCT traffic_hab_id) as unique_users FROM traffic WHERE traffic_device IN ($placeholders)",
            $deviceFilter
        );
        
        return [
            'totalAccess' => $totalResult[0]['total'],
            'todayAccess' => $todayResult[0]['today'],
            'thisWeekAccess' => $weekResult[0]['week'],
            'uniqueUsers' => $uniqueResult[0]['unique_users']
        ];
    }
    
    private function getRecentTraffic($selectedDevice) {
        if (!$selectedDevice) {
            return [];
        }
        
        return $this->db->query(
            "SELECT t.*, h.hab_name, h.hab_registration, h.hab_email 
             FROM traffic t 
             LEFT JOIN habintants h ON t.traffic_hab_id = h.hab_id 
             WHERE t.traffic_device = ? 
             ORDER BY t.traffic_date DESC 
             LIMIT 10",
            [$selectedDevice]
        );
    }
}
```

## Ejemplos de Modelos

### 1. Modelo de Usuario

```php
<?php
// app/models/User.php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($email, $password) {
        $hashedPassword = sha1($password); // Nota: Migrar a bcrypt
        $sql = "INSERT INTO users (users_email, users_password) VALUES (?, ?)";
        return $this->db->execute($sql, [$email, $hashedPassword]);
    }
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE users_email = ?";
        $result = $this->db->query($sql, [$email]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function authenticate($email, $password) {
        $hashedPassword = sha1($password);
        $sql = "SELECT * FROM users WHERE users_email = ? AND users_password = ?";
        $result = $this->db->query($sql, [$email, $hashedPassword]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE users_id = ?";
        return $this->db->execute($sql, [$userId]);
    }
}
```

### 2. Modelo de Dispositivo

```php
<?php
// app/models/Device.php
class Device {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($alias, $serie, $userId) {
        $sql = "INSERT INTO devices (devices_alias, devices_serie, devices_user_id) VALUES (?, ?, ?)";
        return $this->db->execute($sql, [$alias, $serie, $userId]);
    }
    
    public function findByUserId($userId) {
        $sql = "SELECT * FROM devices WHERE devices_user_id = ? ORDER BY devices_date DESC";
        return $this->db->query($sql, [$userId]);
    }
    
    public function findBySerie($serie) {
        $sql = "SELECT * FROM devices WHERE devices_serie = ?";
        $result = $this->db->query($sql, [$serie]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function update($id, $alias, $serie) {
        $sql = "UPDATE devices SET devices_alias = ?, devices_serie = ? WHERE devices_id = ?";
        return $this->db->execute($sql, [$alias, $serie, $id]);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM devices WHERE devices_id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    public function getDeviceStats($deviceSerie) {
        $sql = "SELECT 
                    COUNT(*) as total_access,
                    COUNT(DISTINCT traffic_hab_id) as unique_users,
                    MAX(traffic_date) as last_access
                FROM traffic 
                WHERE traffic_device = ?";
        $result = $this->db->query($sql, [$deviceSerie]);
        return !empty($result) ? $result[0] : null;
    }
}
```

## Ejemplos de Vistas

### 1. Vista de Login

```php
<?php
// app/views/auth/login.php
require_once __DIR__ . '/../layout/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="text-center">Iniciar Sesión - SMARTLABS</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($msg)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Usuario registrado exitosamente</div>
                    <?php endif; ?>
                    
                    <form method="POST" action="/Auth/login">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="/Auth/register">¿No tienes cuenta? Regístrate</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
```

### 2. Vista de Dashboard

```php
<?php
// app/views/dashboard/index.php
require_once __DIR__ . '/../layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <h2>Dashboard - SMARTLABS</h2>
            
            <!-- Selector de dispositivo -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <select class="form-select" id="deviceSelector" onchange="changeDevice()">
                        <option value="">Seleccionar dispositivo</option>
                        <?php foreach ($devices as $device): ?>
                            <option value="<?= $device['devices_serie'] ?>" 
                                    <?= $device['devices_serie'] === $selectedDevice ? 'selected' : '' ?>>
                                <?= htmlspecialchars($device['devices_alias']) ?> 
                                (<?= htmlspecialchars($device['devices_serie']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>Total Accesos</h5>
                            <h2 id="totalAccess"><?= $stats['totalAccess'] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Hoy</h5>
                            <h2 id="todayAccess"><?= $stats['todayAccess'] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>Esta Semana</h5>
                            <h2 id="weekAccess"><?= $stats['thisWeekAccess'] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5>Usuarios Únicos</h5>
                            <h2 id="uniqueUsers"><?= $stats['uniqueUsers'] ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tráfico reciente -->
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>Tráfico Reciente</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="refreshTraffic()">
                        <i class="fas fa-sync"></i> Actualizar
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="trafficTable">
                            <thead>
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Dispositivo</th>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTraffic as $traffic): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i:s', strtotime($traffic['traffic_date'])) ?></td>
                                        <td><?= htmlspecialchars($traffic['traffic_device']) ?></td>
                                        <td><?= htmlspecialchars($traffic['hab_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($traffic['hab_email'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $traffic['traffic_type'] === 'entry' ? 'success' : 'danger' ?>">
                                                <?= $traffic['traffic_type'] === 'entry' ? 'Entrada' : 'Salida' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function changeDevice() {
    const device = document.getElementById('deviceSelector').value;
    window.location.href = `/Dashboard?device=${device}`;
}

function refreshTraffic() {
    const device = document.getElementById('deviceSelector').value;
    
    fetch(`/Dashboard/refresh?device=${device}`)
        .then(response => response.json())
        .then(data => {
            updateTrafficTable(data.traffic);
            updateStats();
        })
        .catch(error => console.error('Error:', error));
}

function updateTrafficTable(traffic) {
    const tbody = document.querySelector('#trafficTable tbody');
    tbody.innerHTML = '';
    
    traffic.forEach(item => {
        const row = document.createElement('tr');
        const date = new Date(item.traffic_date).toLocaleString('es-ES');
        const badgeClass = item.traffic_type === 'entry' ? 'success' : 'danger';
        const badgeText = item.traffic_type === 'entry' ? 'Entrada' : 'Salida';
        
        row.innerHTML = `
            <td>${date}</td>
            <td>${item.traffic_device}</td>
            <td>${item.hab_name || 'N/A'}</td>
            <td>${item.hab_email || 'N/A'}</td>
            <td><span class="badge bg-${badgeClass}">${badgeText}</span></td>
        `;
        
        tbody.appendChild(row);
    });
}

// Auto-refresh cada 30 segundos
setInterval(refreshTraffic, 30000);
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
```

## Ejemplos de AJAX y JavaScript

### 1. Actualización en Tiempo Real

```javascript
// public/js/dashboard.js
class DashboardManager {
    constructor() {
        this.refreshInterval = 30000; // 30 segundos
        this.init();
    }
    
    init() {
        this.startAutoRefresh();
        this.bindEvents();
    }
    
    startAutoRefresh() {
        setInterval(() => {
            this.refreshData();
        }, this.refreshInterval);
    }
    
    bindEvents() {
        document.getElementById('refreshBtn')?.addEventListener('click', () => {
            this.refreshData();
        });
        
        document.getElementById('deviceSelector')?.addEventListener('change', (e) => {
            this.changeDevice(e.target.value);
        });
    }
    
    async refreshData() {
        try {
            const device = this.getSelectedDevice();
            const response = await fetch(`/Dashboard/refresh?device=${device}`);
            const data = await response.json();
            
            this.updateTrafficTable(data.traffic);
            this.updateTimestamp(data.timestamp);
            
        } catch (error) {
            console.error('Error refreshing data:', error);
            this.showError('Error al actualizar los datos');
        }
    }
    
    async updateStats() {
        try {
            const device = this.getSelectedDevice();
            const response = await fetch(`/Dashboard/stats?device=${device}`);
            const stats = await response.json();
            
            document.getElementById('totalAccess').textContent = stats.totalAccess;
            document.getElementById('todayAccess').textContent = stats.todayAccess;
            document.getElementById('weekAccess').textContent = stats.thisWeekAccess;
            document.getElementById('uniqueUsers').textContent = stats.uniqueUsers;
            
        } catch (error) {
            console.error('Error updating stats:', error);
        }
    }
    
    updateTrafficTable(traffic) {
        const tbody = document.querySelector('#trafficTable tbody');
        tbody.innerHTML = '';
        
        traffic.forEach(item => {
            const row = this.createTrafficRow(item);
            tbody.appendChild(row);
        });
    }
    
    createTrafficRow(item) {
        const row = document.createElement('tr');
        const date = new Date(item.traffic_date).toLocaleString('es-ES');
        const badgeClass = item.traffic_type === 'entry' ? 'success' : 'danger';
        const badgeText = item.traffic_type === 'entry' ? 'Entrada' : 'Salida';
        
        row.innerHTML = `
            <td>${date}</td>
            <td>${this.escapeHtml(item.traffic_device)}</td>
            <td>${this.escapeHtml(item.hab_name || 'N/A')}</td>
            <td>${this.escapeHtml(item.hab_email || 'N/A')}</td>
            <td><span class="badge bg-${badgeClass}">${badgeText}</span></td>
        `;
        
        return row;
    }
    
    getSelectedDevice() {
        return document.getElementById('deviceSelector')?.value || '';
    }
    
    changeDevice(device) {
        window.location.href = `/Dashboard?device=${encodeURIComponent(device)}`;
    }
    
    updateTimestamp(timestamp) {
        const element = document.getElementById('lastUpdate');
        if (element) {
            element.textContent = `Última actualización: ${timestamp}`;
        }
    }
    
    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.container-fluid').prepend(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new DashboardManager();
});
```

### 2. Validación de Formularios

```javascript
// public/js/forms.js
class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.init();
    }
    
    init() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => {
                if (!this.validate()) {
                    e.preventDefault();
                }
            });
        }
    }
    
    validate() {
        let isValid = true;
        const inputs = this.form.querySelectorAll('input[required], select[required]');
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';
        
        // Validación requerido
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'Este campo es obligatorio';
        }
        
        // Validación email
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Email inválido';
            }
        }
        
        // Validación contraseña
        if (field.type === 'password' && value) {
            if (value.length < 6) {
                isValid = false;
                message = 'La contraseña debe tener al menos 6 caracteres';
            }
        }
        
        // Confirmar contraseña
        if (field.name === 'confirm_password') {
            const password = this.form.querySelector('input[name="password"]').value;
            if (value !== password) {
                isValid = false;
                message = 'Las contraseñas no coinciden';
            }
        }
        
        this.showFieldValidation(field, isValid, message);
        return isValid;
    }
    
    showFieldValidation(field, isValid, message) {
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            if (feedback) feedback.remove();
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            
            if (!feedback) {
                const div = document.createElement('div');
                div.className = 'invalid-feedback';
                div.textContent = message;
                field.parentNode.appendChild(div);
            } else {
                feedback.textContent = message;
            }
        }
    }
}

// Inicializar validadores
document.addEventListener('DOMContentLoaded', () => {
    new FormValidator('loginForm');
    new FormValidator('registerForm');
    new FormValidator('deviceForm');
});
```

## Ejemplos de Integración con APIs Externas

### 1. Conexión con Dispositivos IoT

```php
<?php
// app/services/DeviceStatusService.php
class DeviceStatusService {
    private $externalDb;
    private $localDb;
    
    public function __construct() {
        $this->localDb = Database::getInstance();
    }
    
    public function getDeviceStatus($deviceId) {
        try {
            // Intentar conexión a base de datos externa
            $this->externalDb = new mysqli('192.168.0.100', 'root', 'emqxpass', 'emqx', 4000);
            
            if ($this->externalDb->connect_error) {
                throw new Exception("Conexión externa fallida: " . $this->externalDb->connect_error);
            }
            
            $stmt = $this->externalDb->prepare(
                "SELECT * FROM device_status WHERE device_id = ? ORDER BY timestamp DESC LIMIT 1"
            );
            $stmt->bind_param("s", $deviceId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            $this->externalDb->close();
            
            return $result;
            
        } catch (Exception $e) {
            // Fallback a base de datos local
            error_log("Error conectando a BD externa: " . $e->getMessage());
            return $this->getLocalDeviceStatus($deviceId);
        }
    }
    
    private function getLocalDeviceStatus($deviceId) {
        $result = $this->localDb->query(
            "SELECT * FROM device_status_cache WHERE device_id = ? ORDER BY updated_at DESC LIMIT 1",
            [$deviceId]
        );
        
        return !empty($result) ? $result[0] : null;
    }
    
    public function updateDeviceStatus($deviceId, $status) {
        // Actualizar caché local
        $this->localDb->execute(
            "INSERT INTO device_status_cache (device_id, status, updated_at) 
             VALUES (?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE status = ?, updated_at = NOW()",
            [$deviceId, $status, $status]
        );
        
        // Notificar a clientes WebSocket si está disponible
        $this->notifyWebSocketClients($deviceId, $status);
    }
    
    private function notifyWebSocketClients($deviceId, $status) {
        // Enviar notificación al servidor WebSocket
        $data = json_encode([
            'type' => 'device_status_update',
            'device_id' => $deviceId,
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // Usar cURL para notificar al servidor WebSocket
        $ch = curl_init('http://localhost:8080/notify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        curl_exec($ch);
        curl_close($ch);
    }
}
```

### 2. API REST para Dispositivos

```php
<?php
// app/controllers/ApiController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/DeviceStatusService.php';

class ApiController extends Controller {
    private $deviceStatusService;
    
    public function __construct() {
        parent::__construct();
        $this->deviceStatusService = new DeviceStatusService();
        
        // Configurar headers para API
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
    
    public function deviceStatus($deviceId = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!$deviceId) {
                $this->jsonResponse(['error' => 'Device ID required'], 400);
                return;
            }
            
            $status = $this->deviceStatusService->getDeviceStatus($deviceId);
            
            if ($status) {
                $this->jsonResponse($status);
            } else {
                $this->jsonResponse(['error' => 'Device not found'], 404);
            }
            
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['device_id']) || !isset($input['status'])) {
                $this->jsonResponse(['error' => 'Missing required fields'], 400);
                return;
            }
            
            $this->deviceStatusService->updateDeviceStatus(
                $input['device_id'], 
                $input['status']
            );
            
            $this->jsonResponse(['success' => true]);
        }
    }
    
    public function traffic() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['device_id']) || !isset($input['user_id'])) {
                $this->jsonResponse(['error' => 'Missing required fields'], 400);
                return;
            }
            
            $result = $this->db->execute(
                "INSERT INTO traffic (traffic_device, traffic_hab_id, traffic_type) VALUES (?, ?, ?)",
                [
                    $input['device_id'],
                    $input['user_id'],
                    $input['type'] ?? 'entry'
                ]
            );
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'id' => $this->db->lastInsertId()]);
            } else {
                $this->jsonResponse(['error' => 'Failed to record traffic'], 500);
            }
        }
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}
```

---

**Versión**: 2.0.0  
**Última actualización**: Diciembre 2024  
**Mantenido por**: Equipo SMARTLABS