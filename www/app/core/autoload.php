<?php
/**
 * Autoloader simple para cargar clases automáticamente
 */
spl_autoload_register(function ($className) {
    $basePath = __DIR__ . '/../';
    
    // Mapeo de clases a directorios
    $classMap = [
        'Controller' => 'core/',
        'Database' => 'core/',
        'Router' => 'core/',
    ];
    
    // Si la clase está en el mapeo, cargar desde ahí
    if (isset($classMap[$className])) {
        $file = $basePath . $classMap[$className] . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Si termina en Controller, buscar en controllers/
    if (substr($className, -10) === 'Controller') {
        $file = $basePath . 'controllers/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Si no, buscar en models/
    $file = $basePath . 'models/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
}); 