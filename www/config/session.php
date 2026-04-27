<?php
/**
 * Configuración de sesión SMARTLABS
 *
 * Hasta ahora la sesión era "permanente" (gc_probability=0, lifetime=2147483647)
 * lo que combinado con SHA1 sin sal hacía que una credencial robada valiera
 * para siempre. Ajustamos a algo razonable (12 h por default), reactivamos el
 * garbage collector y dejamos cookie de sesión solo hasta cerrar el navegador.
 *
 * Las constantes pueden sobrescribirse desde .env con SESSION_LIFETIME (en
 * segundos). Si la app corre detrás de HTTPS, fija SESSION_COOKIE_SECURE=1.
 */

$lifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 43200); // 12 h por default
$secureCookie = (int)($_ENV['SESSION_COOKIE_SECURE'] ?? 0);

ini_set('session.gc_maxlifetime', (string)$lifetime);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor',     '100');

ini_set('session.cookie_lifetime', '0'); // hasta cerrar el navegador
ini_set('session.cache_expire',    (string)max(180, (int)($lifetime / 60))); // minutos
ini_set('session.cache_limiter',   'nocache');

ini_set('session.name',                'SMARTLABS_SESSION');
ini_set('session.save_handler',        'files');
ini_set('session.serialize_handler',   'php');
ini_set('session.use_strict_mode',     '1');
ini_set('session.use_cookies',         '1');
ini_set('session.use_only_cookies',    '1');
ini_set('session.cookie_httponly',     '1');
ini_set('session.cookie_secure',       (string)$secureCookie);
ini_set('session.cookie_samesite',     'Lax');

return [
    'permanent'        => false,
    'max_lifetime'     => $lifetime,
    'cookie_lifetime'  => 0,
    'cookie_secure'    => (bool)$secureCookie,
    'session_name'     => 'SMARTLABS_SESSION',
];
