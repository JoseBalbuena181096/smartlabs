<?php
// SMARTLABS MVC - Front Controller Principal
// Sistema funcionando desde la raíz del proyecto

// Definir la ruta base de la aplicación
define('BASE_PATH', __DIR__);

// Cargar variables de ambiente desde .env
if (file_exists(BASE_PATH . '/.env')) {
    $envFile = file_get_contents(BASE_PATH . '/.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }
        }
    }
}

// Incluir el autoloader
require_once BASE_PATH . '/app/core/autoload.php';

// Incluir archivos del núcleo principales
require_once BASE_PATH . '/app/core/Database.php';
require_once BASE_PATH . '/app/core/Controller.php';
require_once BASE_PATH . '/app/core/Router.php';

// Obtener la URL solicitada
$url = isset($_GET['url']) ? $_GET['url'] : '';

// Crear una instancia del router y procesar la ruta
$router = new Router();
$router->route($url);
?>