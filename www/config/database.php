<?php
/**
 * Configuración de base de datos.
 * Lee del entorno (cargado por index.php desde /.env). Cae a defaults
 * razonables solo para desarrollo local. En producción todos los valores
 * deben venir del .env.
 */
return [
    'host'     => $_ENV['DB_HOST']     ?? 'mariadb',
    'username' => $_ENV['DB_USER']     ?? 'emqxuser',
    'password' => $_ENV['DB_PASSWORD'] ?? 'emqxpass',
    'database' => $_ENV['DB_NAME']     ?? 'emqx',
    'port'     => (int)($_ENV['DB_PORT'] ?? 3306),
    'charset'  => $_ENV['DB_CHARSET']  ?? 'utf8mb4',
];
