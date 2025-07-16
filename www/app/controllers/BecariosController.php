<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Traffic.php';
require_once __DIR__ . '/../models/Habitant.php';

class BecariosController extends Controller {
    private $trafficModel;
    private $habitantModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->trafficModel = new Traffic();
        $this->habitantModel = new Habitant();
    }
    
    public function index() {
        $registration_ = "";
        $usersTrafficDevice = [];
        $timeFullHours = 0;
        $jobsCount = 0;
        $userInfo = "";
        
        // Manejar filtros de estadísticas
        if (isset($_GET['serie_device']) && isset($_GET['start_date']) && isset($_GET['end_date'])) {
            $device = $_GET['serie_device'];
            $start_date = $_GET['start_date'];
            $end_date = $_GET['end_date'];
            $matricula_ = isset($_GET['matricula']) ? strip_tags($_GET['matricula']) : '';
            $matricula_ = strtoupper($matricula_);
            
            // Construir consulta SQL
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
            
            // Calcular estadísticas como en el archivo original
            $timeStart = 0;
            $timeEnd = 0;
            $timeFull = 0;
            $validRecords = [];
            
            if (!empty($usersTrafficDevice)) {
                $currentStart = null;
                
                foreach ($usersTrafficDevice as $index => $traffic) {
                    // Inicio de sesión
                    if ($traffic['traffic_state'] == 1) {
                        $currentStart = $index;
                        $timeStart = strtotime($traffic['traffic_date']);
                        $jobsCount += 1;
                    } 
                    // Fin de sesión
                    else if ($traffic['traffic_state'] == 0 && $currentStart !== null) {
                        $timeEnd = strtotime($traffic['traffic_date']);
                        
                        // Verificar si la diferencia es menor o igual a 9 horas (32400 segundos)
                        if (($timeEnd - $timeStart <= 32400) && ($timeEnd > $timeStart)) {
                            $detalTime = $timeEnd - $timeStart;
                            $timeFull += $detalTime;
                            
                            // Agregar registros válidos
                            $validRecords[] = $usersTrafficDevice[$currentStart];
                            $validRecords[] = $traffic;
                        }
                        
                        // Reiniciar para el siguiente par
                        $currentStart = null;
                        $timeStart = 0;
                        $timeEnd = 0;
                    }
                }
                
                $usersTrafficDevice = $validRecords;
                $timeFullHours = round($timeFull / 3600, 4);
            }
        }
        
        // Obtener dispositivos para el formulario
        $devices = $this->db->query("SELECT DISTINCT traffic_device FROM traffic_devices ORDER BY traffic_device");
        
        $this->view('becarios/index', [
            'usersTrafficDevice' => $usersTrafficDevice,
            'timeFullHours' => $timeFullHours,
            'jobsCount' => $jobsCount,
            'userEmail' => $_SESSION['users_email'] ?? '',
            'devices' => $devices,
            'customScripts' => ['/public/js/becarios-search.js']
        ]);
    }
    
    public function buscarUsuario() {
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
        }
        exit();
    }
}
?>