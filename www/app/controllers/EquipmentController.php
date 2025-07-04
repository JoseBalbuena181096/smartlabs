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
        $devices = [];
        $equipment_exists = false;
        
        // Obtener dispositivos de la sesión como en register_equipment_lab.php
        if (isset($_SESSION['devices'])) {
            $devices = $_SESSION['devices'];
        }
        
        // Manejar actualización de equipo existente
        if ($_POST && isset($_POST['update_equipment']) && isset($_POST['rfid'])) {
            $name_ = strip_tags($_POST['name']);
            $name_ = strtoupper($name_);
            $brand_ = strip_tags($_POST['brand']);
            $brand_ = strtoupper($brand_);
            $rfid_ = strip_tags($_POST['rfid']);
            
            // Actualizar equipo existente
            $this->db->execute("UPDATE `equipments` SET `equipments_name` = ?, `equipments_brand` = ? WHERE `equipments_rfid` = ?", [$name_, $brand_, $rfid_]);
            $message .= "¡Equipo actualizado exitosamente!";
            
            // Limpiar variables después de actualizar
            $name_ = "";
            $brand_ = "";
            $rfid_ = "";
        }
        // Manejar creación de equipo (como register_equipment_lab.php)
        else if ($_POST && isset($_POST['name']) && isset($_POST['brand']) && isset($_POST['rfid'])) {
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
                $message .= "¡Equipo creado exitosamente!";
                
                // Limpiar variables después de crear
                $name_ = "";
                $brand_ = "";
                $rfid_ = "";
            } else {
                // El equipo ya existe, mantener datos para actualización
                $existing_equipment = $equipments_check[0];
                $name_ = $existing_equipment['equipments_name'];
                $brand_ = $existing_equipment['equipments_brand'];
                $equipment_exists = true;
                $message .= "⚠️ EQUIPO YA EXISTE - Puedes actualizar sus datos";
            }
        }
        
        // Manejar eliminación
        if ($_POST && isset($_POST['id_to_delete']) && !empty($_POST['id_to_delete'])) {
            $idToDelete = (int)$_POST['id_to_delete'];
            $this->db->execute("DELETE FROM `equipments` WHERE `equipments_id` = ?", [$idToDelete]);
            $this->redirect('Equipment');
        }
        
        // Obtener equipos actualizados
        $equipments = $this->db->query("SELECT * FROM `equipments` ORDER BY `equipments_id` DESC");
        
        $this->view('equipment/index', [
            'equipments' => $equipments,
            'message' => $message,
            'name_' => $name_,
            'brand_' => $brand_,
            'rfid_' => $rfid_,
            'devices' => $devices,
            'equipment_exists' => $equipment_exists
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