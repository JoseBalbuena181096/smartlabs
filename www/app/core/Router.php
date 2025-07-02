<?php
class Router {
    private $routes = [];
    private $defaultController;
    private $defaultAction;
    
    public function __construct() {
        $config = require_once __DIR__ . '/../../config/app.php';
        $this->defaultController = $config['default_controller'];
        $this->defaultAction = $config['default_action'];
    }
    
    public function route($url) {
        // Limpiar la URL
        $url = trim($url, '/');
        
        // Si está vacía, usar valores por defecto
        if (empty($url)) {
            $controller = $this->defaultController;
            $action = $this->defaultAction;
            $params = [];
        } else {
            // Dividir la URL en partes
            $urlParts = explode('/', $url);
            $controller = ucfirst($urlParts[0]);
            $action = isset($urlParts[1]) ? $urlParts[1] : $this->defaultAction;
            $params = array_slice($urlParts, 2);
        }
        
        // Incluir el archivo del controlador
        $controllerFile = __DIR__ . "/../controllers/{$controller}Controller.php";
        
        if (!file_exists($controllerFile)) {
            $this->show404();
            return;
        }
        
        require_once $controllerFile;
        
        $controllerClass = $controller . 'Controller';
        
        if (!class_exists($controllerClass)) {
            $this->show404();
            return;
        }
        
        $controllerInstance = new $controllerClass();
        
        if (!method_exists($controllerInstance, $action)) {
            $this->show404();
            return;
        }
        
        // Ejecutar la acción del controlador
        call_user_func_array([$controllerInstance, $action], $params);
    }
    
    private function show404() {
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 - Página no encontrada</h1>";
        exit();
    }
} 