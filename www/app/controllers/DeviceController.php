<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Device.php';

class DeviceController extends Controller {
    private $deviceModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->deviceModel = new Device();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        $devices = $this->deviceModel->findByUserId($userId);
        
        // Manejar eliminación
        if ($_POST && isset($_POST['id_to_delete']) && !empty($_POST['id_to_delete'])) {
            $idToDelete = (int)$_POST['id_to_delete'];
            $this->deviceModel->delete($idToDelete);
            $this->redirect('Device');
        }
        
        // Manejar creación
        if ($_POST && isset($_POST['serie']) && isset($_POST['alias'])) {
            $alias = $this->sanitize($_POST['alias']);
            $serie = $this->sanitize($_POST['serie']);
            
            if (!empty($alias) && !empty($serie)) {
                $this->deviceModel->create($alias, $serie, $userId);
                $this->redirect('Device');
            }
        }
        
        $this->view('device/index', [
            'devices' => $devices
        ]);
    }
    
    public function create() {
        if ($_POST) {
            $alias = $this->sanitize($_POST['alias']);
            $serie = $this->sanitize($_POST['serie']);
            $userId = $_SESSION['user_id'];
            
            if (empty($alias) || empty($serie)) {
                $this->view('device/create', [
                    'error' => 'Todos los campos son requeridos',
                    'alias' => $alias,
                    'serie' => $serie
                ]);
                return;
            }
            
            // Verificar si la serie ya existe
            $existingDevice = $this->deviceModel->findBySerie($serie);
            if ($existingDevice) {
                $this->view('device/create', [
                    'error' => 'La serie del dispositivo ya existe',
                    'alias' => $alias,
                    'serie' => $serie
                ]);
                return;
            }
            
            if ($this->deviceModel->create($alias, $serie, $userId)) {
                $this->redirect('Device');
            } else {
                $this->view('device/create', [
                    'error' => 'Error al crear el dispositivo',
                    'alias' => $alias,
                    'serie' => $serie
                ]);
            }
        } else {
            $this->view('device/create');
        }
    }
    
    public function edit($id) {
        $device = $this->deviceModel->findById($id);
        
        if (!$device) {
            $this->redirect('Device');
            return;
        }
        
        if ($_POST) {
            $alias = $this->sanitize($_POST['alias']);
            $serie = $this->sanitize($_POST['serie']);
            
            if (!empty($alias) && !empty($serie)) {
                $this->deviceModel->update($id, $alias, $serie);
                $this->redirect('Device');
            }
        }
        
        $this->view('device/edit', [
            'device' => $device
        ]);
    }
    
    public function delete($id) {
        $this->deviceModel->delete($id);
        $this->redirect('Device');
    }
} 