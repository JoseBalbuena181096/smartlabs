<?php
/**
 * Configuración de sesión permanente
 * Este archivo configura PHP para mantener sesiones activas indefinidamente
 */

// Deshabilitar garbage collection automático de sesiones
ini_set('session.gc_probability', 0);
ini_set('session.gc_divisor', 1000);

// Establecer tiempo de vida máximo de sesión (valor muy alto)
ini_set('session.gc_maxlifetime', 2147483647); // Máximo valor de 32-bit

// Configurar cookies de sesión para que no expiren
ini_set('session.cookie_lifetime', 0); // 0 = hasta que se cierre el navegador

// Configurar el tiempo de vida de la cache de sesión
ini_set('session.cache_expire', 0); // Sin límite

// Configurar el limitador de cache
ini_set('session.cache_limiter', 'nocache');

// Configurar el nombre de la sesión
ini_set('session.name', 'SMARTLABS_PERMANENT_SESSION');

// Configurar el directorio de almacenamiento de sesiones
// ini_set('session.save_path', '/tmp/smartlabs_sessions');

// Configurar el manejador de sesión
ini_set('session.save_handler', 'files');

// Configurar la serialización de sesión
ini_set('session.serialize_handler', 'php');

// Configurar el uso de cookies estrictas
ini_set('session.use_strict_mode', 1);

// Configurar el uso de cookies solamente
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);

// Configurar la seguridad de cookies
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Log de configuración
error_log('SMARTLABS: Configuración de sesión permanente cargada');

return [
    'permanent' => true,
    'gc_disabled' => true,
    'max_lifetime' => 2147483647,
    'cookie_lifetime' => 0,
    'session_name' => 'SMARTLABS_PERMANENT_SESSION'
];