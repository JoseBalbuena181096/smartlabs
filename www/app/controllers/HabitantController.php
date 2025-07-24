<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Habitant.php';
require_once __DIR__ . '/../models/Card.php';

class HabitantController extends Controller {
    private $habitantModel;
    private $cardModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->habitantModel = new Habitant();
        $this->cardModel = new Card();
    }
    
    public function index() {
        $name_ = "";
        $email_ = "";
        $registration_ = "";
        $rfid_ = "";
        $message = "";
        $residents = [];
        
        // Obtener dispositivos de la sesión (como en register_lab.php)
        $devices = isset($_SESSION['devices']) ? $_SESSION['devices'] : [];
        
        // Si no hay dispositivos en sesión, obtener desde base de datos
        if (empty($devices)) {
            $devices = $this->db->query("SELECT * FROM `devices`");
        }
        
        // Manejar creación de habitante (como register_lab.php)
        if ($_POST && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['registration']) && isset($_POST['rfid'])) {
            $name_ = strip_tags($_POST['name']);
            $name_ = strtoupper($name_);
            $email_ = strip_tags($_POST['email']);
            $email_ = strtolower($email_);
            $registration_ = strip_tags($_POST['registration']);
            $registration_ = strtoupper($registration_);
            $rfid_ = strip_tags($_POST['rfid']);
            
            // PASO 1: Verificar/crear tarjeta (exactamente como en register_lab.php)
            $cards_check = $this->db->query("SELECT * FROM `cards` WHERE `cards_number` = ?", [$rfid_]);
            $cards_count = count($cards_check);
            
            // Solo si no hay una tarjeta con mismo RFID, crear nueva
            if ($cards_count == 0) {
                $this->db->execute("INSERT INTO `cards` (`cards_number`, `cards_assigned`) VALUES (?, '1')", [$rfid_]);
                $message .= "Targeta creada <br>";
            } else {
                $message .= "Targeta ya existente <br>";
            }
            
            // PASO 2: Obtener el ID de la tarjeta
            $cards_result = $this->db->query("SELECT * FROM `cards` WHERE `cards_number` = ?", [$rfid_]);
            $cards_count_final = count($cards_result);
            
            // PASO 3: Solo si la tarjeta existe, crear el habitante
            if ($cards_count_final > 0) {
                $card_id = $cards_result[0]['cards_id'];
                
                // Verificar si ya existe un usuario con la misma matrícula
                $existing_user = $this->db->query("SELECT * FROM `habintants` WHERE `hab_registration` = ?", [$registration_]);
                
                if (count($existing_user) == 0) {
                    // Insertar habitante con card_id y device_id (como en legacy)
                    $this->db->execute(
                        "INSERT INTO `habintants` (`hab_name`, `hab_registration`, `hab_email`, `hab_card_id`, `hab_device_id`) VALUES (?, ?, ?, ?, '1')", 
                        [$name_, $registration_, $email_, $card_id]
                    );
                    $message .= "Usuario creado <br>";
                    
                    // Limpiar variables después de crear exitosamente
                    $name_ = "";
                    $email_ = "";
                    $registration_ = "";
                    $rfid_ = "";
                } else {
                    $message .= "Ya existe un usuario con esa matrícula <br>";
                }
            } else {
                $message .= "No se pudo crear usuario - error con tarjeta <br>";
            }
            
            // Obtener los últimos 20 residentes (como register_lab.php)
            $residents = $this->db->query("SELECT * FROM `habintants` ORDER BY `hab_id` DESC LIMIT 20");
        } else {
            $message = "Complete el formulario";
            
            // Obtener los últimos 20 residentes por defecto
            $residents = $this->db->query("SELECT * FROM `habintants` ORDER BY `hab_id` DESC LIMIT 20");
        }
        
        // Manejar eliminación
        if ($_POST && isset($_POST['id_to_delete']) && !empty($_POST['id_to_delete'])) {
            $idToDelete = (int)$_POST['id_to_delete'];
            $this->db->execute("DELETE FROM `habintants` WHERE `hab_id` = ?", [$idToDelete]);
            $this->redirect('Habitant');
        }
        
        $this->view('habitant/index', [
            'residents' => $residents,
            'message' => $message,
            'name_' => $name_,
            'email_' => $email_,
            'registration_' => $registration_,
            'rfid_' => $rfid_,
            'devices' => $devices
        ]);
    }
    
    public function create() {
        if ($_POST) {
            $name = $this->sanitize($_POST['name']);
            $email = $this->sanitize($_POST['email']);
            $registration = $this->sanitize($_POST['registration']);
            
            if (empty($name) || empty($email) || empty($registration)) {
                $this->view('habitant/create', [
                    'error' => 'Todos los campos son requeridos',
                    'name' => $name,
                    'email' => $email,
                    'registration' => $registration
                ]);
                return;
            }
            
            if (!$this->validateEmail($email)) {
                $this->view('habitant/create', [
                    'error' => 'El email no es válido',
                    'name' => $name,
                    'email' => $email,
                    'registration' => $registration
                ]);
                return;
            }
            
            // Verificar si ya existe
            $existingHabitant = $this->habitantModel->findByRegistration($registration);
            if ($existingHabitant) {
                $this->view('habitant/create', [
                    'error' => 'La matrícula ya está registrada',
                    'name' => $name,
                    'email' => $email,
                    'registration' => $registration
                ]);
                return;
            }
            
            // Crear habitante directamente
            $success = $this->db->execute("INSERT INTO `habintants` (`hab_name`, `hab_registration`, `hab_email`) VALUES (?, ?, ?)", [strtoupper($name), strtoupper($registration), strtolower($email)]);
            
            if ($success) {
                $this->redirect('Habitant');
            } else {
                $this->view('habitant/create', [
                    'error' => 'Error al crear el habitante',
                    'name' => $name,
                    'email' => $email,
                    'registration' => $registration
                ]);
            }
        } else {
            $this->view('habitant/create');
        }
    }
    
    public function edit($id) {
        $habitant = $this->habitantModel->findById($id);
        
        if (!$habitant) {
            $this->redirect('Habitant');
            return;
        }
        
        if ($_POST) {
            $name = $this->sanitize($_POST['name']);
            $email = $this->sanitize($_POST['email']);
            $registration = $this->sanitize($_POST['registration']);
            
            if (!empty($name) && !empty($email) && !empty($registration)) {
                if ($this->validateEmail($email)) {
                    $this->db->execute("UPDATE `habintants` SET `hab_name` = ?, `hab_registration` = ?, `hab_email` = ? WHERE `hab_id` = ?", [strtoupper($name), strtoupper($registration), strtolower($email), $id]);
                    $this->redirect('Habitant');
                }
            }
        }
        
        $this->view('habitant/edit', [
            'habitant' => $habitant
        ]);
    }
    
    public function delete($id) {
        $this->habitantModel->delete($id);
        $this->redirect('Habitant');
    }
    
    public function search() {
        // Manejar peticiones AJAX de búsqueda
        if ($_POST && isset($_POST['search_users'])) {
            $searchTerm = isset($_POST['search_term']) ? $this->sanitize($_POST['search_term']) : '';
            $searchType = isset($_POST['search_type']) ? $this->sanitize($_POST['search_type']) : 'all';
            
            $results = [];
            
            if (!empty($searchTerm) && strlen($searchTerm) >= 2) {
                $searchTerm = strtoupper($searchTerm); // Convertir a mayúsculas para la búsqueda
                
                switch ($searchType) {
                    case 'name':
                        $results = $this->db->query(
                            "SELECT * FROM `habintants` WHERE `hab_name` LIKE ? ORDER BY `hab_name` ASC LIMIT 20", 
                            ["%{$searchTerm}%"]
                        );
                        break;
                        
                    case 'registration':
                        $results = $this->db->query(
                            "SELECT * FROM `habintants` WHERE `hab_registration` LIKE ? ORDER BY `hab_registration` ASC LIMIT 20", 
                            ["%{$searchTerm}%"]
                        );
                        break;
                        
                    case 'email':
                        $searchTermLower = strtolower($searchTerm);
                        $results = $this->db->query(
                            "SELECT * FROM `habintants` WHERE `hab_email` LIKE ? ORDER BY `hab_email` ASC LIMIT 20", 
                            ["%{$searchTermLower}%"]
                        );
                        break;
                        
                    case 'all':
                    default:
                        $searchTermLower = strtolower($searchTerm);
                        $results = $this->db->query(
                            "SELECT * FROM `habintants` WHERE 
                             `hab_name` LIKE ? OR 
                             `hab_registration` LIKE ? OR 
                             `hab_email` LIKE ? 
                             ORDER BY `hab_name` ASC LIMIT 20", 
                            ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTermLower}%"]
                        );
                        break;
                }
            }
            
            // Devolver JSON para AJAX
            header('Content-Type: application/json');
            echo json_encode($results);
            return;
        }
        
        // Manejo legacy para búsquedas GET
        $searchTerm = '';
        $searchType = 'name';
        $results = [];
        
        if ($_GET && isset($_GET['term'])) {
            $searchTerm = $this->sanitize($_GET['term']);
            $searchType = isset($_GET['type']) ? $this->sanitize($_GET['type']) : 'name';
            
            if (!empty($searchTerm)) {
                if ($searchType === 'registration') {
                    $results = $this->db->query("SELECT * FROM `habintants` WHERE `hab_registration` LIKE ?", ["%{$searchTerm}%"]);
                } elseif ($searchType === 'email') {
                    $results = $this->db->query("SELECT * FROM `habintants` WHERE `hab_email` LIKE ?", ["%{$searchTerm}%"]);
                } else {
                    $results = $this->db->query("SELECT * FROM `habintants` WHERE `hab_name` LIKE ?", ["%{$searchTerm}%"]);
                }
            }
        }
        
        $this->view('habitant/search', [
            'searchTerm' => $searchTerm,
            'searchType' => $searchType,
            'results' => $results
        ]);
    }
    
    public function searchByRFID() {
        // Manejar búsqueda por RFID desde MQTT
        if ($_POST && isset($_POST['search_rfid'])) {
            $rfid = isset($_POST['rfid']) ? $this->sanitize($_POST['rfid']) : '';
            
            // Sanear el RFID eliminando prefijo "APP:" si existe
            $rfid = $this->sanitizeRfid($rfid);
            
            $results = [];
            
            if (!empty($rfid)) {
                // Buscar en la tabla cards para encontrar usuarios por RFID (como en legacy)
                $results = $this->db->query(
                    "SELECT h.* FROM `habintants` h 
                     INNER JOIN `cards` c ON h.hab_card_id = c.cards_id 
                     WHERE c.cards_number = ? 
                     ORDER BY h.hab_id DESC LIMIT 5",
                    [$rfid]
                );
                
                // Si no se encuentra en cards_habs, buscar directamente en habintants si tiene campo RFID
                if (empty($results)) {
                    // Verificar si la tabla habintants tiene algún campo relacionado con RFID
                    $rfidResults = $this->db->query(
                        "SELECT * FROM `habintants` WHERE 
                         hab_name LIKE ? OR 
                         hab_registration LIKE ? OR 
                         hab_email LIKE ? 
                         LIMIT 5",
                        ["%{$rfid}%", "%{$rfid}%", "%{$rfid}%"]
                    );
                    
                    if (!empty($rfidResults)) {
                        $results = $rfidResults;
                    }
                }
            }
            
            // Devolver JSON
            header('Content-Type: application/json');
            echo json_encode($results);
            return;
        }
        
        // Redirect si no es petición AJAX
        $this->redirect('Habitant');
    }
    
    /**
     * Función saneadora para eliminar prefijo "APP:" del RFID
     */
    private function sanitizeRfid($rfidInput) {
        if (is_string($rfidInput) && strpos($rfidInput, 'APP:') === 0) {
            return substr($rfidInput, 4); // Eliminar los primeros 4 caracteres "APP:"
        }
        return $rfidInput;
    }
}