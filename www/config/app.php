<?php
return [
    'app_name' => 'SMARTLABS',
    'app_url' => 'http://localhost',
    'default_controller' => 'Dashboard',
    'default_action' => 'index',
    'assets_path' => '/assets/',
    'session_timeout' => 0, // Sin límite de tiempo
    
    // Configuración de sesión extendida
    'session_config' => [
        'gc_maxlifetime' => 0, // Sin garbage collection automático
        'cookie_lifetime' => 0, // Cookie de sesión permanente
        'gc_probability' => 1,
        'gc_divisor' => 100
    ],
    
    // Configuración de keep-alive
    'keepalive' => [
        'enabled' => true,
        'interval' => 60000, // 1 minuto en milisegundos
        'endpoint' => '/Auth/keepalive'
    ]
];