<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Equipment.php';

class EquipmentController extends Controller {
    private $equipmentModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->equipmentModel = new Equipment();
    }
    
    public function index() {
        $name_ = "";
        $brand_ = "";
        $rfid_ = "";
        $message = "";
        $equipments = [];
        
        // Manejar creación de equipo (como register_equipment_lab.php)
        if ($_POST && isset($_POST['name']) && isset($_POST['brand']) && isset($_POST['rfid'])) {
            $name_ = strip_tags($_POST['name']);
            $name_ = strtoupper($name_);
            $brand_ = strip_tags($_POST['brand']);
            $brand_ = strtoupper($brand_);
            $rfid_ = strip_tags($_POST['rfid']);
            
            // Verificar si el equipo ya existe
            $equipments_check = $this->db->query("SELECT * FROM `equipments` WHERE `equipments_rfid` = ?", [$rfid_]);
            
            $count = count($equipments_check);
            
            if ($count == 0) {
                $this->db->execute("INSERT INTO `equipments` (`equipments_name`, `equipments_rfid`, `equipments_brand`) VALUES (?, ?, ?)", [$name_, $rfid_, $brand_]);
                $message .= "equipo creado <br>";
                
                // Limpiar variables después de crear
                $name_ = "";
                $brand_ = "";
                $rfid_ = "";
            } else {
                $message .= "equipo existente <br>";
            }
            
            // Obtener equipos actualizados
            $equipments = $this->db->query("SELECT * FROM `equipments` ORDER BY `equipments_id` DESC");
        } else {
            $message = "Complete el formulario";
            
            // Obtener todos los equipos
            $equipments = $this->db->query("SELECT * FROM `equipments` ORDER BY `equipments_id` DESC");
        }
        
        // Manejar eliminación
        if ($_POST && isset($_POST['id_to_delete']) && !empty($_POST['id_to_delete'])) {
            $idToDelete = (int)$_POST['id_to_delete'];
            $this->db->execute("DELETE FROM `equipments` WHERE `equipments_id` = ?", [$idToDelete]);
            $this->redirect('Equipment');
        }
        
        $this->view('equipment/index', [
            'equipments' => $equipments,
            'message' => $message,
            'name_' => $name_,
            'brand_' => $brand_,
            'rfid_' => $rfid_
        ]);
    }
    
    public function create() {
        if ($_POST) {
            $name = $this->sanitize($_POST['name']);
            $rfid = $this->sanitize($_POST['rfid']);
            $brand = $this->sanitize($_POST['brand']);
            
            if (empty($name) || empty($rfid) || empty($brand)) {
                $this->view('equipment/create', [
                    'error' => 'Todos los campos son requeridos',
                    'name' => $name,
                    'rfid' => $rfid,
                    'brand' => $brand
                ]);
                return;
            }
            
            // Verificar si el RFID ya existe
            $existingEquipment = $this->equipmentModel->findByRfid($rfid);
            if ($existingEquipment) {
                $this->view('equipment/create', [
                    'error' => 'El RFID del equipo ya existe',
                    'name' => $name,
                    'rfid' => $rfid,
                    'brand' => $brand
                ]);
                return;
            }
            
            if ($this->equipmentModel->create($name, $rfid, $brand)) {
                $this->redirect('Equipment');
            } else {
                $this->view('equipment/create', [
                    'error' => 'Error al crear el equipo',
                    'name' => $name,
                    'rfid' => $rfid,
                    'brand' => $brand
                ]);
            }
        } else {
            $this->view('equipment/create');
        }
    }
    
    public function edit($id) {
        $equipment = $this->equipmentModel->findById($id);
        
        if (!$equipment) {
            $this->redirect('Equipment');
            return;
        }
        
        if ($_POST) {
            $name = $this->sanitize($_POST['name']);
            $rfid = $this->sanitize($_POST['rfid']);
            $brand = $this->sanitize($_POST['brand']);
            
            if (!empty($name) && !empty($rfid) && !empty($brand)) {
                $this->equipmentModel->update($id, $name, $rfid, $brand);
                $this->redirect('Equipment');
            }
        }
        
        $this->view('equipment/edit', [
            'equipment' => $equipment
        ]);
    }
    
    public function delete($id) {
        $this->equipmentModel->delete($id);
        $this->redirect('Equipment');
    }
    
    public function search() {
        $searchTerm = '';
        $searchType = 'name';
        $results = [];
        
        if ($_GET && isset($_GET['term'])) {
            $searchTerm = $this->sanitize($_GET['term']);
            $searchType = isset($_GET['type']) ? $this->sanitize($_GET['type']) : 'name';
            
            if (!empty($searchTerm)) {
                if ($searchType === 'brand') {
                    $results = $this->db->query("SELECT * FROM `equipments` WHERE `equipments_brand` LIKE ?", ["%{$searchTerm}%"]);
                } else {
                    $results = $this->db->query("SELECT * FROM `equipments` WHERE `equipments_name` LIKE ?", ["%{$searchTerm}%"]);
                }
            }
        }
        
        $this->view('equipment/search', [
            'searchTerm' => $searchTerm,
            'searchType' => $searchType,
            'results' => $results
        ]);
    }
} 