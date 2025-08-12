<?php
// Cargar configuración de la aplicación
require_once __DIR__ . '/../config/app.php';
$config = include __DIR__ . '/../config/app.php';

// Configurar headers para JavaScript
header('Content-Type: application/javascript');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Obtener variables de ambiente
$serverHost = $config['server_host'];
$apiHost = $config['api_host'];
$mqttHost = $config['mqtt_host'];

// Generar configuración JavaScript
echo "// Configuración de variables de ambiente generada dinámicamente\n";
echo "window.ENV_CONFIG = {\n";
echo "    SERVER_HOST: '{$serverHost}',\n";
echo "    API_HOST: '{$apiHost}',\n";
echo "    MQTT_HOST: '{$mqttHost}'\n";
echo "};\n\n";

// Incluir el archivo de configuración estático
include __DIR__ . '/js/env-config.js';
?>