<?php
class Controller {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->checkSession();
    }
    
    protected function checkSession() {
        if (session_status() == PHP_SESSION_NONE) {
            // Cargar configuración de sesión permanente
            require_once __DIR__ . '/../../config/session.php';
            
            // Cargar configuración de sesión desde app.php
            $config = require_once __DIR__ . '/../../config/app.php';
            
            // Configurar parámetros de sesión permanente
            if (isset($config['session_config'])) {
                $sessionConfig = $config['session_config'];
                
                if ($sessionConfig['gc_maxlifetime'] > 0) {
                    ini_set('session.gc_maxlifetime', $sessionConfig['gc_maxlifetime']);
                } else {
                    // Sesión permanente: establecer un valor muy alto
                    ini_set('session.gc_maxlifetime', 2147483647); // Máximo valor de 32-bit
                }
                
                if ($sessionConfig['cookie_lifetime'] > 0) {
                    ini_set('session.cookie_lifetime', $sessionConfig['cookie_lifetime']);
                } else {
                    // Cookie permanente hasta que se cierre el navegador
                    ini_set('session.cookie_lifetime', 0);
                }
                
                ini_set('session.gc_probability', 0); // Deshabilitar garbage collection automático
                ini_set('session.gc_divisor', 1000);
            }
            
            // Configurar parámetros de cookie de sesión permanente
            $cookieLifetime = isset($config['session_config']['cookie_lifetime']) && $config['session_config']['cookie_lifetime'] > 0 
                ? $config['session_config']['cookie_lifetime'] 
                : 0;
                
            session_set_cookie_params([
                'lifetime' => $cookieLifetime,
                'path' => '/',
                'domain' => '',
                'secure' => false, // Cambiar a true en HTTPS
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_start();
            
            // Regenerar ID de sesión cada 24 horas por seguridad (sesión permanente)
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 86400) { // 24 horas
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
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