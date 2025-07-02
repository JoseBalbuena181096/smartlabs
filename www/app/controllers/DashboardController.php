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
        $selectedDevice = isset($_GET['device']) ? $this->sanitize($_GET['device']) : '';
        
        // Obtener dispositivos del usuario
        $devices = $this->db->query("SELECT * FROM `devices` WHERE `devices_user_id` = ?", [$userId]);
        
        // Obtener tráfico filtrado (como dashboard.php)
        $traffic = [];
        $totalAccess = 0;
        $todayAccess = 0;
        $thisWeekAccess = 0;
        
        if ($selectedDevice) {
            // Filtrar por dispositivo específico (últimos 12 registros como en legacy)
            $traffic = $this->db->query("
                SELECT t.*, h.hab_name, h.hab_registration 
                FROM `traffic` t 
                LEFT JOIN `habintants` h ON t.traffic_hab_id = h.hab_id 
                WHERE t.traffic_device = ? 
                ORDER BY t.traffic_date DESC 
                LIMIT 12
            ", [$selectedDevice]);
            
            // Estadísticas para el dispositivo seleccionado
            $totalResult = $this->db->query("SELECT COUNT(*) as total FROM `traffic` WHERE `traffic_device` = ?", [$selectedDevice]);
            $totalAccess = $totalResult[0]['total'];
            
            $todayResult = $this->db->query("SELECT COUNT(*) as today FROM `traffic` WHERE `traffic_device` = ? AND DATE(traffic_date) = CURDATE()", [$selectedDevice]);
            $todayAccess = $todayResult[0]['today'];
            
            $weekResult = $this->db->query("SELECT COUNT(*) as week FROM `traffic` WHERE `traffic_device` = ? AND YEARWEEK(traffic_date) = YEARWEEK(NOW())", [$selectedDevice]);
            $thisWeekAccess = $weekResult[0]['week'];
        } else {
            // Mostrar todos los dispositivos del usuario (últimos 12 registros total)
            if (!empty($devices)) {
                $deviceSerials = array_column($devices, 'devices_serie');
                $placeholders = str_repeat('?,', count($deviceSerials) - 1) . '?';
                
                $traffic = $this->db->query("
                    SELECT t.*, h.hab_name, h.hab_registration 
                    FROM `traffic` t 
                    LEFT JOIN `habintants` h ON t.traffic_hab_id = h.hab_id 
                    WHERE t.traffic_device IN ($placeholders) 
                    ORDER BY t.traffic_date DESC 
                    LIMIT 12
                ", $deviceSerials);
                
                // Estadísticas generales
                $totalResult = $this->db->query("SELECT COUNT(*) as total FROM `traffic` WHERE `traffic_device` IN ($placeholders)", $deviceSerials);
                $totalAccess = $totalResult[0]['total'];
                
                $todayResult = $this->db->query("SELECT COUNT(*) as today FROM `traffic` WHERE `traffic_device` IN ($placeholders) AND DATE(traffic_date) = CURDATE()", $deviceSerials);
                $todayAccess = $todayResult[0]['today'];
                
                $weekResult = $this->db->query("SELECT COUNT(*) as week FROM `traffic` WHERE `traffic_device` IN ($placeholders) AND YEARWEEK(traffic_date) = YEARWEEK(NOW())", $deviceSerials);
                $thisWeekAccess = $weekResult[0]['week'];
            }
        }
        
        // Estadísticas adicionales
        $uniqueUsers = 0;
        $deviceCount = count($devices);
        
        if (!empty($devices)) {
            $deviceSerials = array_column($devices, 'devices_serie');
            $placeholders = str_repeat('?,', count($deviceSerials) - 1) . '?';
            
            $uniqueResult = $this->db->query("SELECT COUNT(DISTINCT traffic_hab_id) as unique_users FROM `traffic` WHERE `traffic_device` IN ($placeholders)", $deviceSerials);
            $uniqueUsers = $uniqueResult[0]['unique_users'];
        }
        
        $this->view('dashboard/index', [
            'devices' => $devices,
            'traffic' => $traffic,
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
        $selectedDevice = isset($_GET['device']) ? $this->sanitize($_GET['device']) : '';
        
        if ($selectedDevice) {
            $traffic = $this->db->query("
                SELECT t.*, h.hab_name, h.hab_registration 
                FROM `traffic` t 
                LEFT JOIN `habintants` h ON t.traffic_hab_id = h.hab_id 
                WHERE t.traffic_device = ? 
                ORDER BY t.traffic_date DESC 
                LIMIT 12
            ", [$selectedDevice]);
        } else {
            // Obtener dispositivos del usuario
            $userDevices = $this->db->query("SELECT devices_serie FROM `devices` WHERE `devices_user_id` = ?", [$userId]);
            $deviceSerials = array_column($userDevices, 'devices_serie');
            
            if (!empty($deviceSerials)) {
                $placeholders = str_repeat('?,', count($deviceSerials) - 1) . '?';
                $traffic = $this->db->query("
                    SELECT t.*, h.hab_name, h.hab_registration 
                    FROM `traffic` t 
                    LEFT JOIN `habintants` h ON t.traffic_hab_id = h.hab_id 
                    WHERE t.traffic_device IN ($placeholders) 
                    ORDER BY t.traffic_date DESC 
                    LIMIT 12
                ", $deviceSerials);
            } else {
                echo json_encode(['traffic' => []]);
                exit();
            }
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
        $selectedDevice = isset($_GET['device']) ? $this->sanitize($_GET['device']) : '';
        
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
} 