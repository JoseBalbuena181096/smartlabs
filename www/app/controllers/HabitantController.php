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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            // Eliminación
            if (!empty($_POST['id_to_delete'])) {
                $idToDelete = (int)$_POST['id_to_delete'];
                $this->db->execute("DELETE FROM `habintants` WHERE `hab_id` = ?", [$idToDelete]);
                $this->redirect('Habitant');
            }

            // Creación: POST-redirect-GET para evitar duplicados al refrescar
            if (isset($_POST['name'], $_POST['email'], $_POST['registration'], $_POST['rfid'])) {
                $result = $this->createHabitant($_POST);
                $_SESSION['_habitant_flash'] = $result;
                $this->redirect('Habitant');
            }
        }

        // Mensaje flash de la operación previa (post-redirect-GET)
        $flash = $_SESSION['_habitant_flash'] ?? null;
        unset($_SESSION['_habitant_flash']);

        $devices = $_SESSION['devices'] ?? [];
        if (empty($devices)) {
            $devices = $this->db->query("SELECT * FROM `devices`");
        }

        $residents = $this->db->query("SELECT * FROM `habintants` ORDER BY `hab_id` DESC LIMIT 20");

        $this->view('habitant/index', [
            'residents'     => $residents,
            'message'       => $flash['message'] ?? "Complete el formulario",
            'name_'         => $flash['name'] ?? '',
            'email_'        => $flash['email'] ?? '',
            'registration_' => $flash['registration'] ?? '',
            'rfid_'         => $flash['rfid'] ?? '',
            'devices'       => $devices,
            'csrf'          => self::csrfToken(),
        ]);
    }

    /**
     * Crea o reusa una card y crea el habitante asociado.
     * @return array{message:string,name?:string,email?:string,registration?:string,rfid?:string}
     *   Si éxito devuelve solo message; si error devuelve los datos para repintar el form.
     */
    private function createHabitant(array $post) {
        $name_         = strtoupper(strip_tags($post['name']));
        $email_        = strtolower(strip_tags($post['email']));
        $registration_ = strtoupper(strip_tags($post['registration']));
        $rfid_         = strip_tags($post['rfid']);
        $device_id     = isset($post['device_id']) ? (int)$post['device_id'] : 0;

        $message = "";

        // Validar el dispositivo seleccionado (debe existir, antes era hardcoded a 1).
        if ($device_id > 0) {
            $deviceCheck = $this->db->query("SELECT devices_id FROM `devices` WHERE devices_id = ?", [$device_id]);
            if (empty($deviceCheck)) {
                $device_id = 0;
            }
        }
        if ($device_id <= 0) {
            return [
                'message'      => "Selecciona un dispositivo válido para el usuario.<br>",
                'name'         => $name_,
                'email'        => $email_,
                'registration' => $registration_,
                'rfid'         => $rfid_,
            ];
        }

        // Tarjeta: crear si no existe.
        $cards_check = $this->db->query("SELECT * FROM `cards` WHERE `cards_number` = ?", [$rfid_]);
        if (empty($cards_check)) {
            $this->db->execute("INSERT INTO `cards` (`cards_number`, `cards_assigned`) VALUES (?, '1')", [$rfid_]);
            $message .= "Tarjeta creada<br>";
            $cards_check = $this->db->query("SELECT * FROM `cards` WHERE `cards_number` = ?", [$rfid_]);
        } else {
            $message .= "Tarjeta ya existente<br>";
        }

        if (empty($cards_check)) {
            return [
                'message'      => $message . "No se pudo crear la tarjeta.<br>",
                'name'         => $name_,
                'email'        => $email_,
                'registration' => $registration_,
                'rfid'         => $rfid_,
            ];
        }

        $card_id = $cards_check[0]['cards_id'];

        // Habitante: validar matrícula única.
        $existing = $this->db->query("SELECT hab_id FROM `habintants` WHERE `hab_registration` = ?", [$registration_]);
        if (!empty($existing)) {
            return [
                'message'      => $message . "Ya existe un usuario con esa matrícula.<br>",
                'name'         => $name_,
                'email'        => $email_,
                'registration' => $registration_,
                'rfid'         => $rfid_,
            ];
        }

        $this->db->execute(
            "INSERT INTO `habintants` (`hab_name`, `hab_registration`, `hab_email`, `hab_card_id`, `hab_device_id`) VALUES (?, ?, ?, ?, ?)",
            [$name_, $registration_, $email_, $card_id, $device_id]
        );
        $message .= "Usuario creado.<br>";
        return ['message' => $message];
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            $name = $this->sanitize($_POST['name'] ?? '');
            $email = $this->sanitize($_POST['email'] ?? '');
            $registration = $this->sanitize($_POST['registration'] ?? '');
            
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
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            $name = $this->sanitize($_POST['name'] ?? '');
            $email = $this->sanitize($_POST['email'] ?? '');
            $registration = $this->sanitize($_POST['registration'] ?? '');

            if (!empty($name) && !empty($email) && !empty($registration)) {
                if ($this->validateEmail($email)) {
                    $this->db->execute("UPDATE `habintants` SET `hab_name` = ?, `hab_registration` = ?, `hab_email` = ? WHERE `hab_id` = ?", [strtoupper($name), strtoupper($registration), strtolower($email), $id]);
                    $this->redirect('Habitant');
                }
            }
        }

        $this->view('habitant/edit', [
            'habitant' => $habitant,
            'csrf'     => self::csrfToken(),
        ]);
    }

    public function delete($id) {
        // Solo aceptar DELETE vía POST con CSRF para no romper con CSRF GET.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
        }
        $this->habitantModel->delete($id);
        $this->redirect('Habitant');
    }

    public function search() {
        // Manejar peticiones AJAX de búsqueda
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_users'])) {
            $this->verifyCsrf();
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
        // Manejar búsqueda por RFID desde MQTT/AJAX
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_rfid'])) {
            $this->verifyCsrf();

            $rfid = isset($_POST['rfid']) ? $this->sanitize($_POST['rfid']) : '';
            $rfid = $this->sanitizeRfid($rfid);

            $results = [];
            if (!empty($rfid)) {
                // Solo buscar el RFID exacto en cards. El fallback anterior hacía
                // LIKE %rfid% en hab_name/registration/email lo que producía
                // falsos positivos arbitrarios.
                $results = $this->db->query(
                    "SELECT h.* FROM `habintants` h
                     INNER JOIN `cards` c ON h.hab_card_id = c.cards_id
                     WHERE c.cards_number = ?
                     ORDER BY h.hab_id DESC LIMIT 5",
                    [$rfid]
                );
            }

            header('Content-Type: application/json');
            echo json_encode($results);
            return;
        }

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