<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Device.php';

class StatsController extends Controller {
    private $deviceModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->deviceModel = new Device();
    }
    
    public function index() {
        $registration_ = "";
        $usersTrafficDevice = [];
        $devices = [];
        $userInfo = "";
        
        // Manejar consulta AJAX de usuario por matrícula (como horas_uso.php)
        if ($_POST && isset($_POST['registration'])) {
            $registration_ = strip_tags($_POST['registration']);
            $registration_ = strtoupper($registration_);
            
            $result = $this->db->query("SELECT * FROM `habintants` WHERE `hab_registration` = ?", [$registration_]);
            
            if (!empty($result)) {
                $row = $result[0];
                $nameValue = $row["hab_name"];
                $regValue = $row["hab_registration"];
                echo "Nombre: " . $nameValue . " - Matricula: " . $regValue;
            } else {
                echo "No existe el usuario.";
            }
            exit(); // Terminar para AJAX
        }
        
        // Obtener dispositivos del usuario
        $userId = $_SESSION['user_id'];
        $devices = $this->db->query("SELECT * FROM `devices` WHERE `devices_user_id` = ?", [$userId]);
        
        // Manejar filtros de estadísticas (como horas_uso.php)
        if (isset($_GET['serie_device']) && isset($_GET['start_date']) && isset($_GET['end_date'])) {
            $device = $_GET['serie_device'];
            $start_date = $_GET['start_date'];
            $end_date = $_GET['end_date'];
            $matricula_ = isset($_GET['matricula']) ? strip_tags($_GET['matricula']) : '';
            $matricula_ = strtoupper($matricula_);
            
            // Construir consulta SQL como en horas_uso.php
            if ($matricula_ == "") {
                $sql = "SELECT *
                FROM traffic_devices
                WHERE traffic_device = ?
                AND traffic_date BETWEEN ? AND ?
                ORDER BY traffic_date ASC";
                $params = [$device, $start_date, $end_date];
            } else {
                $sql = "SELECT *
                FROM traffic_devices
                WHERE traffic_device = ?
                AND hab_registration = ?
                AND traffic_date BETWEEN ? AND ?
                ORDER BY traffic_date ASC";
                $params = [$device, $matricula_, $start_date, $end_date];
            }
            
            $usersTrafficDevice = $this->db->query($sql, $params);
        }
        
        $this->view('stats/index', [
            'devices' => $devices,
            'usersTrafficDevice' => $usersTrafficDevice,
            'registration_' => $registration_,
            'userInfo' => $userInfo
        ]);
    }
    
    public function deviceUsage() {
        $deviceStats = $this->getDeviceUsageStats();
        
        $this->view('stats/device-usage', [
            'deviceStats' => $deviceStats
        ]);
    }
    
    public function userActivity() {
        $userStats = $this->getUserActivityStats();
        
        $this->view('stats/user-activity', [
            'userStats' => $userStats
        ]);
    }
    
    public function timeAnalysis() {
        $timeStats = $this->getTimeAnalysisStats();
        
        $this->view('stats/time-analysis', [
            'timeStats' => $timeStats
        ]);
    }
    
    public function export() {
        $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
        $data = $this->getExportData();
        
        if ($format === 'excel') {
            $this->exportToExcel($data);
        } else {
            $this->exportToCSV($data);
        }
    }
    
    private function getDeviceUsageStats() {
        $sql = "
            SELECT 
                d.devices_alias,
                COUNT(t.traffic_id) as total_accesses,
                COUNT(DISTINCT t.traffic_hab_id) as unique_users,
                AVG(TIMESTAMPDIFF(MINUTE, 
                    (SELECT t2.traffic_date FROM traffic t2 WHERE t2.traffic_hab_id = t.traffic_hab_id AND t2.traffic_state = 1 AND t2.traffic_date <= t.traffic_date ORDER BY t2.traffic_date DESC LIMIT 1),
                    t.traffic_date
                )) as avg_session_minutes
            FROM devices d
            LEFT JOIN traffic t ON d.devices_serie = t.traffic_device
            WHERE d.devices_user_id = ?
            GROUP BY d.devices_id, d.devices_alias
            ORDER BY total_accesses DESC
        ";
        return $this->db->query($sql, [$_SESSION['user_id']]);
    }
    
    private function getUserActivityStats() {
        $sql = "
            SELECT 
                h.hab_name,
                h.hab_registration,
                COUNT(t.traffic_id) as total_accesses,
                COUNT(DISTINCT DATE(t.traffic_date)) as days_active,
                MAX(t.traffic_date) as last_access
            FROM habintants h
            LEFT JOIN traffic t ON h.hab_id = t.traffic_hab_id
            GROUP BY h.hab_id, h.hab_name, h.hab_registration
            ORDER BY total_accesses DESC
            LIMIT 20
        ";
        return $this->db->query($sql);
    }
    
    private function getTimeAnalysisStats() {
        $sql = "
            SELECT 
                DATE(traffic_date) as access_date,
                HOUR(traffic_date) as access_hour,
                COUNT(*) as access_count
            FROM traffic
            WHERE traffic_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(traffic_date), HOUR(traffic_date)
            ORDER BY access_date DESC, access_hour ASC
        ";
        return $this->db->query($sql);
    }
    
    private function getExportData() {
        $sql = "
            SELECT 
                t.traffic_date,
                h.hab_name,
                h.hab_registration,
                h.hab_email,
                t.traffic_device,
                CASE WHEN t.traffic_state = 1 THEN 'Entrada' ELSE 'Salida' END as action
            FROM traffic t
            JOIN habintants h ON t.traffic_hab_id = h.hab_id
            ORDER BY t.traffic_date DESC
            LIMIT 1000
        ";
        return $this->db->query($sql);
    }
    
    private function exportToCSV($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="estadisticas_smartlabs.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit();
    }
    
    private function exportToExcel($data) {
        // Implementación básica de Excel usando HTML table
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="estadisticas_smartlabs.xls"');
        
        echo '<table border="1">';
        
        if (!empty($data)) {
            echo '<tr>';
            foreach (array_keys($data[0]) as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';
            
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . htmlspecialchars($cell) . '</td>';
                }
                echo '</tr>';
            }
        }
        
        echo '</table>';
        exit();
    }
} 