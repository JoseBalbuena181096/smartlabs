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
    
    public function update() {
        // Establecer header JSON
        header('Content-Type: application/json');
        
        // Verificar que sea una petición POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }
        
        // Obtener datos del POST
        $deviceId = isset($_POST['device_id']) ? (int)$_POST['device_id'] : 0;
        $alias = isset($_POST['alias']) ? $this->sanitize($_POST['alias']) : '';
        $serie = isset($_POST['serie']) ? $this->sanitize($_POST['serie']) : '';
        
        // Validaciones
        if (empty($deviceId) || empty($alias) || empty($serie)) {
            echo json_encode([
                'success' => false,
                'message' => 'Todos los campos son requeridos'
            ]);
            return;
        }
        
        if (strlen($alias) < 3) {
            echo json_encode([
                'success' => false,
                'message' => 'El alias debe tener al menos 3 caracteres'
            ]);
            return;
        }
        
        if (strlen($serie) < 3) {
            echo json_encode([
                'success' => false,
                'message' => 'El número de serie debe tener al menos 3 caracteres'
            ]);
            return;
        }
        
        // Verificar que el dispositivo existe y pertenece al usuario
        $device = $this->deviceModel->findById($deviceId);
        if (!$device || $device['devices_user_id'] != $_SESSION['user_id']) {
            echo json_encode([
                'success' => false,
                'message' => 'Dispositivo no encontrado o no tienes permisos para editarlo'
            ]);
            return;
        }
        
        // Verificar que la serie no esté siendo usada por otro dispositivo
        $existingDevice = $this->deviceModel->findBySerie($serie);
        if ($existingDevice && $existingDevice['devices_id'] != $deviceId) {
            echo json_encode([
                'success' => false,
                'message' => 'El número de serie ya está siendo usado por otro dispositivo'
            ]);
            return;
        }
        
        // Intentar actualizar el dispositivo
        try {
            $result = $this->deviceModel->update($deviceId, $alias, $serie);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Dispositivo actualizado correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar el dispositivo'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ]);
        }
    }
} 