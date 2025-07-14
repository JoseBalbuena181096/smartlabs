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
        
        // Almacenar dispositivos en la sesión para JavaScript (como en legacy)
        $_SESSION['devices'] = $devices;
        
        // Obtener tráfico filtrado (como dashboard.php legacy)
        $usersTrafficDevice = [];
        $totalAccess = 0;
        $todayAccess = 0;
        $thisWeekAccess = 0;
        
        if ($selectedDevice) {
            // Conectar a base de datos externa (como en legacy dashboard.php)
            try {
                $external_db = new mysqli('192.168.0.100', 'root', 'emqxpass', 'emqx', 4000);
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
} 