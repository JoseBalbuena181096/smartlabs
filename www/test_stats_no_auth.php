<?php
// Controlador de prueba sin autenticación para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir archivos necesarios
require_once 'app/core/Database.php';
require_once 'config/database.php';

// Configurar headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Función para log de debug
function debugLog($message) {
    error_log("[DEBUG] " . $message);
}

debugLog("=== INICIANDO TEST STATS NO AUTH ===");
debugLog("Método: " . $_SERVER['REQUEST_METHOD']);
debugLog("POST: " . json_encode($_POST));
debugLog("GET: " . json_encode($_GET));

// Manejar CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    debugLog("Manejando OPTIONS request");
    exit(0);
}

// Verificar si es una búsqueda AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_user'])) {
    debugLog("Procesando búsqueda AJAX");
    
    $query = trim($_POST['search_user']);
    debugLog("Query: " . $query);
    
    $users = [];
    
    // Intentar buscar en BD externa
    try {
        $config = include 'config/database.php';
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        debugLog("DSN: " . $dsn);
        
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        debugLog("Conexión BD externa exitosa");
        
        $sql = "SELECT hab_name, hab_registration, hab_email, hab_rfid,
                CASE 
                    WHEN hab_registration LIKE ? THEN 'matricula'
                    WHEN hab_name LIKE ? THEN 'nombre'
                    WHEN hab_email LIKE ? THEN 'email'
                    ELSE 'multiple'
                END as match_type
                FROM habitants 
                WHERE hab_registration LIKE ? 
                   OR hab_name LIKE ? 
                   OR hab_email LIKE ?
                ORDER BY hab_name 
                LIMIT 10";
        
        $searchTerm = "%{$query}%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm, $searchTerm
        ]);
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        debugLog("Usuarios encontrados en BD externa: " . count($users));
        
    } catch (Exception $e) {
        debugLog("Error BD externa: " . $e->getMessage());
        
        // Usar datos de prueba si falla
        $users = [
            [
                'hab_name' => 'Jose Test User',
                'hab_registration' => 'JTU2024',
                'hab_email' => 'jose.test@smartlabs.com',
                'hab_rfid' => '123456789',
                'match_type' => 'nombre'
            ],
            [
                'hab_name' => 'Maria Test User',
                'hab_registration' => 'MTU2024',
                'hab_email' => 'maria.test@smartlabs.com',
                'hab_rfid' => '987654321',
                'match_type' => 'nombre'
            ]
        ];
        debugLog("Usando datos de prueba: " . count($users) . " usuarios");
    }
    
    // Devolver respuesta JSON
    debugLog("Enviando respuesta JSON con " . count($users) . " usuarios");
    echo json_encode($users);
    exit();
}

// Si no es una búsqueda AJAX, mostrar página de prueba
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Stats - Sin Autenticación</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Test Stats - Sin Autenticación</h1>
    <p>Este archivo permite probar la búsqueda de usuarios sin autenticación.</p>
    
    <div>
        <input type="text" id="searchInput" placeholder="Buscar usuario..." />
        <button id="searchBtn">Buscar</button>
    </div>
    
    <div id="results" style="border: 1px solid #ccc; padding: 10px; margin-top: 10px; min-height: 100px;">
        <p>Escriba algo para buscar...</p>
    </div>
    
    <div id="logs" style="background: #f0f0f0; padding: 10px; margin-top: 10px; max-height: 200px; overflow-y: auto;">
        <h3>Logs:</h3>
        <div id="logContent"></div>
    </div>
    
    <script>
        function log(message) {
            const timestamp = new Date().toLocaleTimeString();
            $('#logContent').append('<div>[' + timestamp + '] ' + message + '</div>');
            console.log(message);
        }
        
        function buscarUsuarios(query) {
            log('Iniciando búsqueda: ' + query);
            
            $('#results').html('<div>🔄 Buscando...</div>');
            
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    search_user: query
                },
                success: function(response) {
                    log('✅ Respuesta recibida');
                    log('Response: ' + JSON.stringify(response));
                    
                    if (response.length > 0) {
                        let html = '<h3>Usuarios encontrados (' + response.length + '):</h3><ul>';
                        response.forEach(function(user) {
                            html += '<li><strong>' + user.hab_name + '</strong> (' + user.hab_registration + ') - ' + user.hab_email + ' - <em>' + user.match_type + '</em></li>';
                        });
                        html += '</ul>';
                        $('#results').html(html);
                    } else {
                        $('#results').html('<p>No se encontraron usuarios</p>');
                    }
                },
                error: function(xhr, status, error) {
                    log('❌ Error AJAX: ' + status + ' - ' + error);
                    log('Status Code: ' + xhr.status);
                    log('Response: ' + xhr.responseText);
                    $('#results').html('<p style="color: red;">Error: ' + error + '</p>');
                }
            });
        }
        
        $(document).ready(function() {
            log('Página cargada');
            
            $('#searchInput').on('input', function() {
                const query = $(this).val().trim();
                if (query.length >= 1) {
                    buscarUsuarios(query);
                } else {
                    $('#results').html('<p>Escriba algo para buscar...</p>');
                }
            });
            
            $('#searchBtn').click(function() {
                const query = $('#searchInput').val().trim();
                if (query.length >= 1) {
                    buscarUsuarios(query);
                }
            });
            
            // Prueba automática
            setTimeout(function() {
                log('Ejecutando prueba automática');
                $('#searchInput').val('jose');
                buscarUsuarios('jose');
            }, 1000);
        });
    </script>
    
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        #searchInput { width: 300px; padding: 8px; margin-right: 10px; }
        #searchBtn { padding: 8px 16px; background: #007bff; color: white; border: none; cursor: pointer; }
        #results { background: white; border-radius: 4px; }
        #logs { font-size: 12px; color: #666; }
        #logContent div { margin: 2px 0; }
    </style>
</body>
</html> 