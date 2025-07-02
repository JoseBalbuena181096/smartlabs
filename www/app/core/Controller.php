<?php
class Controller {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->checkSession();
    }
    
    protected function checkSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    protected function requireAuth() {
        if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
            $this->redirect('Auth/login');
            exit();
        }
    }
    
    protected function view($viewName, $data = []) {
        // Extraer las variables para la vista
        extract($data);
        
        // Incluir el archivo de vista
        $viewFile = __DIR__ . "/../views/{$viewName}.php";
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            die("Vista no encontrada: {$viewName}");
        }
    }
    
    protected function redirect($url) {
        header("Location: /{$url}");
        exit();
    }
    
    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    protected function hashPassword($password) {
        return sha1($password); // Mantengo SHA1 para compatibilidad con el sistema existente
    }
} 