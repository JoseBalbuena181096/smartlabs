<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
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
        $selectedDevice = isset($_GET['serie_device']) ? $this->sanitize($_GET['serie_device']) : '';
        
        // Obtener dispositivos del usuario
        $devices = $this->db->query("SELECT * FROM `devices` WHERE `devices_user_id` = ?", [$userId]);
        
        // Determinar dispositivo por defecto si no se especifica uno
        if (!$selectedDevice && !empty($devices)) {
            $selectedDevice = $devices[0]['devices_serie']; // Seleccionar primer dispositivo por defecto
        }
        
        // Almacenar dispositivos en la sesión para JavaScript (como en legacy)
        $_SESSION['devices'] = $devices;
        
        // Obtener estado inicial del dispositivo seleccionado
        $deviceInitialStatus = null;
        if ($selectedDevice) {
            $deviceInitialStatus = $this->getDeviceStatusData($selectedDevice);
        }
        
        // Obtener tráfico filtrado (como dashboard.php legacy)
        $usersTrafficDevice = [];
        $totalAccess = 0;
        $todayAccess = 0;
        $thisWeekAccess = 0;
        
        if ($selectedDevice) {
            // Conectar a base de datos externa (como en legacy dashboard.php)
            try {
                $config = include __DIR__ . '/../../config/app.php';
                $external_db = new mysqli($config['server_host'], 'root', 'emqxpass', 'emqx', 4000);
                if ($external_db->connect_error) {
                    throw new Exception("Conexión externa fallida: " . $external_db->connect_error);
                }
                
                // Consulta SQL como en legacy (últimos 12 registros)
                $sql = "SELECT * FROM traffic_devices WHERE traffic_device = ? ORDER BY traffic_date DESC LIMIT 12";
                $stmt = $external_db->prepare($sql);
                $stmt->bind_param("s", $selectedDevice);
                $stmt->execute();
                $result = $stmt->get_result();
                $usersTrafficDevice = $result->fetch_all(MYSQLI_ASSOC);
                $external_db->close();
                
            } catch (Exception $e) {
                // Fallback a base de datos local
                $usersTrafficDevice = $this->db->query("
                    SELECT t.*, h.hab_name, h.hab_registration, h.hab_email
                    FROM `traffic` t 
                    LEFT JOIN `habintants` h ON t.traffic_hab_id = h.hab_id 
                    WHERE t.traffic_device = ? 
                    ORDER BY t.traffic_date DESC 
                    LIMIT 12
                ", [$selectedDevice]);
            }
            
            // Estadísticas para el dispositivo seleccionado
            $totalResult = $this->db->query("SELECT COUNT(*) as total FROM `traffic` WHERE `traffic_device` = ?", [$selectedDevice]);
            $totalAccess = $totalResult[0]['total'];
            
            $todayResult = $this->db->query("SELECT COUNT(*) as today FROM `traffic` WHERE `traffic_device` = ? AND DATE(traffic_date) = CURDATE()", [$selectedDevice]);
            $todayAccess = $todayResult[0]['today'];
            
            $weekResult = $this->db->query("SELECT COUNT(*) as week FROM `traffic` WHERE `traffic_device` = ? AND YEARWEEK(traffic_date) = YEARWEEK(NOW())", [$selectedDevice]);
            $thisWeekAccess = $weekResult[0]['week'];
        }
        
        // Estadísticas adicionales
        $uniqueUsers = 0;
        $deviceCount = count($devices);
        
        if ($selectedDevice) {
            // Usuarios únicos para el dispositivo seleccionado
            $uniqueResult = $this->db->query("SELECT COUNT(DISTINCT traffic_hab_id) as unique_users FROM `traffic` WHERE `traffic_device` = ?", [$selectedDevice]);
            $uniqueUsers = $uniqueResult[0]['unique_users'];
        } else if (!empty($devices)) {
            // Usuarios únicos para todos los dispositivos del usuario
            $deviceSerials = array_column($devices, 'devices_serie');
            $placeholders = str_repeat('?,', count($deviceSerials) - 1) . '?';
            
            $uniqueResult = $this->db->query("SELECT COUNT(DISTINCT traffic_hab_id) as unique_users FROM `traffic` WHERE `traffic_device` IN ($placeholders)", $deviceSerials);
            $uniqueUsers = $uniqueResult[0]['unique_users'];
        }
        
        $this->view('dashboard/index', [
            'devices' => $devices,
            'usersTrafficDevice' => $usersTrafficDevice,
            'selectedDevice' => $selectedDevice,
            'deviceInitialStatus' => $deviceInitialStatus,
            'stats' => [
                'totalAccess' => $totalAccess,
                'todayAccess' => $todayAccess,
                'thisWeekAccess' => $thisWeekAccess,
                'uniqueUsers' => $uniqueUsers,
                'deviceCount' => $deviceCount
            ]
        ]);
    }
    
    public function device($deviceSerial = null) {
        if (!$deviceSerial) {
            $this->redirect('Dashboard');
            return;
        }
        
        $deviceSerial = $this->sanitize($deviceSerial);
        $this->redirect("Dashboard?device={$deviceSerial}");
    }
    
    public function refresh() {
        // Endpoint para AJAX refresh (como en dashboard.php con auto-refresh)
        header('Content-Type: application/json');
        
        $userId = $_SESSION['user_id'];
        $selectedDevice = isset($_GET['serie_device']) ? $this->sanitize($_GET['serie_device']) : '';
        
        if ($selectedDevice) {
            $traffic = $this->db->query("
                SELECT t.*, h.hab_name, h.hab_registration, h.hab_email
                FROM `traffic` t 
                LEFT JOIN `habintants` h ON t.traffic_hab_id = h.hab_id 
                WHERE t.traffic_device = ? 
                ORDER BY t.traffic_date DESC 
                LIMIT 12
            ", [$selectedDevice]);
        } else {
            // Si no hay dispositivo seleccionado, retornar vacío
            echo json_encode(['traffic' => []]);
            exit();
        }
        
        echo json_encode([
            'traffic' => $traffic,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
    
    public function stats() {
        // Endpoint para estadísticas en tiempo real
        header('Content-Type: application/json');
        
        $userId = $_SESSION['user_id'];
        $selectedDevice = isset($_GET['serie_device']) ? $this->sanitize($_GET['serie_device']) : '';
        
        $userDevices = $this->db->query("SELECT devices_serie FROM `devices` WHERE `devices_user_id` = ?", [$userId]);
        $deviceSerials = array_column($userDevices, 'devices_serie');
        
        if (empty($deviceSerials)) {
            echo json_encode(['error' => 'No devices found']);
            exit();
        }
        
        $deviceFilter = $selectedDevice ? [$selectedDevice] : $deviceSerials;
        $placeholders = str_repeat('?,', count($deviceFilter) - 1) . '?';
        
        // Total accesos
        $totalResult = $this->db->query("SELECT COUNT(*) as total FROM `traffic` WHERE `traffic_device` IN ($placeholders)", $deviceFilter);
        $totalAccess = $totalResult[0]['total'];
        
        // Accesos hoy
        $todayResult = $this->db->query("SELECT COUNT(*) as today FROM `traffic` WHERE `traffic_device` IN ($placeholders) AND DATE(traffic_date) = CURDATE()", $deviceFilter);
        $todayAccess = $todayResult[0]['today'];
        
        // Accesos esta semana
        $weekResult = $this->db->query("SELECT COUNT(*) as week FROM `traffic` WHERE `traffic_device` IN ($placeholders) AND YEARWEEK(traffic_date) = YEARWEEK(NOW())", $deviceFilter);
        $thisWeekAccess = $weekResult[0]['week'];
        
        // Usuarios únicos
        $uniqueResult = $this->db->query("SELECT COUNT(DISTINCT traffic_hab_id) as unique_users FROM `traffic` WHERE `traffic_device` IN ($placeholders)", $deviceFilter);
        $uniqueUsers = $uniqueResult[0]['unique_users'];
        
        echo json_encode([
            'totalAccess' => $totalAccess,
            'todayAccess' => $todayAccess,
            'thisWeekAccess' => $thisWeekAccess,
            'uniqueUsers' => $uniqueUsers,
            'deviceCount' => count($deviceSerials),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
    
    public function command() {
        // Endpoint para controlar dispositivos via MQTT (como en legacy)
        header('Content-Type: application/json');
        
        if (!$_POST || !isset($_POST['action']) || !isset($_POST['device_serie'])) {
            echo json_encode(['error' => 'Parámetros requeridos: action, device_serie']);
            exit();
        }
        
        $action = $this->sanitize($_POST['action']);
        $deviceSerie = $this->sanitize($_POST['device_serie']);
        $userId = $_SESSION['user_id'];
        
        // Verificar que el usuario tiene acceso al dispositivo
        $userDevice = $this->db->query("SELECT * FROM `devices` WHERE `devices_serie` = ? AND `devices_user_id` = ?", [$deviceSerie, $userId]);
        
        if (empty($userDevice)) {
            echo json_encode(['error' => 'Dispositivo no encontrado o sin permisos']);
            exit();
        }
        
        // Validar acción
        if (!in_array($action, ['open', 'close'])) {
            echo json_encode(['error' => 'Acción no válida']);
            exit();
        }
        
        // Aquí se enviaría el comando MQTT al dispositivo
        // Por ahora retornamos éxito ya que el cliente JavaScript manejará MQTT
        echo json_encode([
            'success' => true,
            'message' => "Comando '{$action}' enviado al dispositivo {$deviceSerie}",
            'action' => $action,
            'device' => $deviceSerie
        ]);
        exit();
    }
    
    public function temperature() {
        // Endpoint para obtener temperatura actual (se usará con MQTT)
        header('Content-Type: application/json');
        
        $deviceSerie = isset($_GET['serie_device']) ? $this->sanitize($_GET['serie_device']) : '';
        $userId = $_SESSION['user_id'];
        
        if (empty($deviceSerie)) {
            echo json_encode(['error' => 'Dispositivo requerido']);
            exit();
        }
        
        // Verificar que el usuario tiene acceso al dispositivo
        $userDevice = $this->db->query("SELECT * FROM `devices` WHERE `devices_serie` = ? AND `devices_user_id` = ?", [$deviceSerie, $userId]);
        
        if (empty($userDevice)) {
            echo json_encode(['error' => 'Dispositivo no encontrado o sin permisos']);
            exit();
        }
        
        // La temperatura se actualizará vía MQTT en tiempo real
        // Por ahora retornamos un valor por defecto
        echo json_encode([
            'temperature' => '--',
            'device' => $deviceSerie,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
    
    public function updateDeviceState() {
        // Endpoint para actualizar estado del dispositivo
        header('Content-Type: application/json');
        
        if (!$_POST || !isset($_POST['device_serie']) || !isset($_POST['state'])) {
            echo json_encode(['error' => 'Parámetros requeridos: device_serie, state']);
            exit();
        }
        
        $deviceSerie = $this->sanitize($_POST['device_serie']);
        $state = $this->sanitize($_POST['state']);
        $userId = $_SESSION['user_id'];
        
        // Verificar que el usuario tiene acceso al dispositivo
        $userDevice = $this->db->query("SELECT * FROM `devices` WHERE `devices_serie` = ? AND `devices_user_id` = ?", [$deviceSerie, $userId]);
        
        if (empty($userDevice)) {
            echo json_encode(['error' => 'Dispositivo no encontrado o sin permisos']);
            exit();
        }
        
        // Convertir estado a booleano (1=on, 0=off)
        $stateValue = ($state === 'on') ? 1 : 0;
        
        try {
            // Insertar nuevo registro de estado en la base de datos
            $this->db->query("
                INSERT INTO `traffic` (traffic_date, traffic_hab_id, traffic_device, traffic_state) 
                VALUES (NOW(), 1, ?, ?)
            ", [$deviceSerie, $stateValue]);
            
            // También intentar actualizar en la base de datos externa
            try {
                $config = include __DIR__ . '/../../config/app.php';
                $external_db = new mysqli($config['server_host'], 'root', 'emqxpass', 'emqx', 4000);
                if (!$external_db->connect_error) {
                    $stmt = $external_db->prepare("INSERT INTO traffic (traffic_date, traffic_hab_id, traffic_device, traffic_state) VALUES (NOW(), 1, ?, ?)");
                    $stmt->bind_param("si", $deviceSerie, $stateValue);
                    $stmt->execute();
                    $external_db->close();
                }
            } catch (Exception $e) {
                // Si falla la externa, continuar con la local
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Estado del dispositivo {$deviceSerie} actualizado a {$state}",
                'device' => $deviceSerie,
                'state' => $state,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error actualizando estado: ' . $e->getMessage()]);
        }
        
        exit();
    }
    
    /**
     * Obtiene los datos de estado de un dispositivo específico
     * @param string $deviceSerie - Serie del dispositivo
     * @return array|null - Datos del estado del dispositivo
     */
    private function getDeviceStatusData($deviceSerie) {
        // Verificar que el dispositivo pertenece al usuario
        $userId = $_SESSION['user_id'];
        $userDevice = $this->db->query("SELECT * FROM `devices` WHERE `devices_serie` = ? AND `devices_user_id` = ?", [$deviceSerie, $userId]);
        
        if (empty($userDevice)) {
            return null;
        }
        
        $device = $userDevice[0];
        
        // Obtener último estado del tráfico (si existe)
        $lastTraffic = [];
        
        // Intentar consultar base de datos externa primero (como en legacy)
        try {
            $config = include __DIR__ . '/../../config/app.php';
            $external_db = new mysqli($config['server_host'], 'root', 'emqxpass', 'emqx', 4000);
            if (!$external_db->connect_error) {
                // Consultar específicamente en traffic_devices para ver estado (1=on, 0=off)
                $sql = "SELECT traffic_id, traffic_date, traffic_state FROM traffic_devices WHERE traffic_device = ? ORDER BY traffic_date DESC LIMIT 1";
                $stmt = $external_db->prepare($sql);
                $stmt->bind_param("s", $deviceSerie);
                $stmt->execute();
                $result = $stmt->get_result();
                $lastTraffic = $result->fetch_all(MYSQLI_ASSOC);
                $external_db->close();
            }
        } catch (Exception $e) {
            // Si falla la externa, usar local
        }
        
        // Si no hay datos de la DB externa, intentar con la local
        if (empty($lastTraffic)) {
            try {
                $lastTraffic = $this->db->query("
                    SELECT traffic_id, traffic_date, traffic_state 
                    FROM `traffic` 
                    WHERE `traffic_device` = ? 
                    ORDER BY traffic_date DESC 
                    LIMIT 1
                ", [$deviceSerie]);
            } catch (Exception $e) {
                // Si también falla la local, retornar estado desconocido
            }
        }
        
        // Determinar estado actual basado en traffic_state (1=on, 0=off)
        $currentState = 'unknown';
        $lastActivity = null;
        
        if (!empty($lastTraffic)) {
            // Verificar si traffic_state es 1 (encendido) o 0 (apagado)
            $currentState = $lastTraffic[0]['traffic_state'] == 1 ? 'on' : 'off';
            $lastActivity = $lastTraffic[0]['traffic_date'];
        }
        
        // Verificar si el dispositivo está online (activo en los últimos 5 minutos)
        $isOnline = false;
        if ($lastActivity) {
            $lastActivityTime = strtotime($lastActivity);
            $isOnline = (time() - $lastActivityTime) < 300; // 5 minutos
        }
        
        return [
            'device' => $deviceSerie,
            'alias' => $device['devices_alias'] ?? 'Dispositivo',
            'state' => $currentState,
            'online' => $isOnline,
            'last_activity' => $lastActivity,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    public function status() {
        // Endpoint para obtener estado actual del dispositivo
        header('Content-Type: application/json');
        
        $deviceSerie = isset($_GET['serie_device']) ? $this->sanitize($_GET['serie_device']) : '';
        $userId = $_SESSION['user_id'];
        
        if (empty($deviceSerie)) {
            echo json_encode(['error' => 'Dispositivo requerido']);
            exit();
        }
        
        // Verificar que el usuario tiene acceso al dispositivo
        $userDevice = $this->db->query("SELECT * FROM `devices` WHERE `devices_serie` = ? AND `devices_user_id` = ?", [$deviceSerie, $userId]);
        
        if (empty($userDevice)) {
            echo json_encode(['error' => 'Dispositivo no encontrado o sin permisos']);
            exit();
        }
        
        // Consultar estado en base de datos local
        $deviceStatus = $this->db->query("SELECT * FROM `devices` WHERE `devices_serie` = ?", [$deviceSerie]);
        
        if (empty($deviceStatus)) {
            echo json_encode([
                'error' => 'Dispositivo no encontrado',
                'device' => $deviceSerie,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit();
        }
        
        $device = $deviceStatus[0];
        
        // Obtener último estado del tráfico (si existe)
        $lastTraffic = [];
        
        // Intentar consultar base de datos externa primero (como en legacy)
        try {
            $config = include __DIR__ . '/../../config/app.php';
            $external_db = new mysqli($config['server_host'], 'root', 'emqxpass', 'emqx', 4000);
            if (!$external_db->connect_error) {
                // Consultar específicamente en traffic_devices para ver estado (1=on, 0=off)
                $sql = "SELECT traffic_id, traffic_date, traffic_state FROM traffic_devices WHERE traffic_device = ? ORDER BY traffic_date DESC LIMIT 1";
                $stmt = $external_db->prepare($sql);
                $stmt->bind_param("s", $deviceSerie);
                $stmt->execute();
                $result = $stmt->get_result();
                $lastTraffic = $result->fetch_all(MYSQLI_ASSOC);
                $external_db->close();
                
                if (!empty($lastTraffic)) {
                    error_log("Estado desde DB externa: " . json_encode($lastTraffic[0]));
                }
            }
        } catch (Exception $e) {
            error_log("Error consultando DB externa: " . $e->getMessage());
            // Si falla la externa, usar local
        }
        
        // Si no hay datos de la DB externa, intentar con la local
        if (empty($lastTraffic)) {
            try {
                $lastTraffic = $this->db->query("
                    SELECT traffic_id, traffic_date, traffic_state 
                    FROM `traffic` 
                    WHERE `traffic_device` = ? 
                    ORDER BY traffic_date DESC 
                    LIMIT 1
                ", [$deviceSerie]);
                
                if (!empty($lastTraffic)) {
                    error_log("Estado desde DB local: " . json_encode($lastTraffic[0]));
                }
            } catch (Exception $e) {
                error_log("Error consultando DB local: " . $e->getMessage());
            }
        }
        
        // Determinar estado actual basado en traffic_state (1=on, 0=off)
        $currentState = 'unknown';
        $lastActivity = null;
        
        if (!empty($lastTraffic)) {
            // Verificar si traffic_state es 1 (encendido) o 0 (apagado)
            $currentState = $lastTraffic[0]['traffic_state'] == 1 ? 'on' : 'off';
            $lastActivity = $lastTraffic[0]['traffic_date'];
            
            error_log("Estado determinado: " . $currentState . " basado en traffic_state=" . $lastTraffic[0]['traffic_state']);
        } else {
            error_log("No se encontraron registros de tráfico para el dispositivo: " . $deviceSerie);
        }
        
        // Verificar si el dispositivo está online (activo en los últimos 5 minutos)
        $isOnline = false;
        if ($lastActivity) {
            $lastActivityTime = strtotime($lastActivity);
            $isOnline = (time() - $lastActivityTime) < 300; // 5 minutos
        }
        
        echo json_encode([
            'device' => $deviceSerie,
            'alias' => $device['devices_alias'] ?? 'Dispositivo',
            'state' => $currentState,
            'online' => $isOnline,
            'last_activity' => $lastActivity,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
}