<?php
// SMARTLABS MVC - Front Controller Principal
// Sistema funcionando desde la raíz del proyecto

// Definir la ruta base de la aplicación
define('BASE_PATH', __DIR__);

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