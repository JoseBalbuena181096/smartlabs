<?php
// Archivo de prueba para debuggear búsqueda de usuarios
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir el autoloader y configuración
require_once __DIR__ . '/app/core/autoload.php';
require_once __DIR__ . '/app/core/Database.php';

// Probar conexión a base de datos local
try {
    $db = new Database();
    echo "<h2>✅ Conexión local exitosa</h2>";
    
    // Probar consulta básica
    $users = $db->query("SELECT h.hab_name, h.hab_registration, h.hab_email, c.cards_number 
                         FROM habintants h 
                         JOIN cards c ON h.hab_card_id = c.cards_id 
                         LIMIT 5");
    
    echo "<h3>Usuarios en BD local (primeros 5):</h3>";
    echo "<pre>" . print_r($users, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error en BD local: " . $e->getMessage() . "</h2>";
}

// Probar conexión a base de datos externa
try {
    $externalDb = new PDO('mysql:host=192.168.0.100:4000;dbname=emqx', 'emqx', 'emqx123');
    $externalDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>✅ Conexión externa exitosa</h2>";
    
    // Probar consulta básica
    $stmt = $externalDb->prepare("SELECT hab_name, hab_registration, hab_email, hab_rfid 
                                  FROM habitants 
                                  LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Usuarios en BD externa (primeros 5):</h3>";
    echo "<pre>" . print_r($users, true) . "</pre>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error en BD externa: " . $e->getMessage() . "</h2>";
}

// Probar búsqueda específica
if (isset($_GET['q'])) {
    $query = $_GET['q'];
    echo "<h2>🔍 Buscando: '$query'</h2>";
    
    // Búsqueda en BD externa
    try {
        $externalDb = new PDO('mysql:host=192.168.0.100:4000;dbname=emqx', 'emqx', 'emqx123');
        $externalDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
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
        $stmt = $externalDb->prepare($sql);
        $stmt->execute([
            $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm, $searchTerm
        ]);
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Resultados BD externa:</h3>";
        echo "<pre>" . print_r($users, true) . "</pre>";
        
    } catch (PDOException $e) {
        echo "<h3>❌ Error en BD externa para búsqueda: " . $e->getMessage() . "</h3>";
        
        // Fallback a BD local
        try {
            $db = new Database();
            $sql = "SELECT h.hab_name, h.hab_registration, h.hab_email, c.cards_number as hab_rfid,
                    CASE 
                        WHEN h.hab_registration LIKE ? THEN 'matricula'
                        WHEN h.hab_name LIKE ? THEN 'nombre'
                        WHEN h.hab_email LIKE ? THEN 'email'
                        ELSE 'multiple'
                    END as match_type
                    FROM habintants h 
                    JOIN cards c ON h.hab_card_id = c.cards_id 
                    WHERE h.hab_registration LIKE ? 
                       OR h.hab_name LIKE ? 
                       OR h.hab_email LIKE ?
                    ORDER BY h.hab_name 
                    LIMIT 10";
            
            $searchTerm = "%{$query}%";
            $users = $db->query($sql, [
                $searchTerm, $searchTerm, $searchTerm,
                $searchTerm, $searchTerm, $searchTerm
            ]);
            
            echo "<h3>Resultados BD local:</h3>";
            echo "<pre>" . print_r($users, true) . "</pre>";
            
        } catch (Exception $localE) {
            echo "<h3>❌ Error en BD local para búsqueda: " . $localE->getMessage() . "</h3>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Búsqueda Usuarios</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #333; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .test-form { background: #e8f4f8; padding: 20px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🧪 Test Búsqueda de Usuarios</h1>
    
    <div class="test-form">
        <h3>Probar búsqueda:</h3>
        <form method="GET">
            <input type="text" name="q" placeholder="Escribe matrícula, nombre o email..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            <button type="submit">Buscar</button>
        </form>
    </div>
    
    <p><strong>Instrucciones:</strong></p>
    <ul>
        <li>Verifica que las conexiones a BD funcionen</li>
        <li>Prueba búsquedas con diferentes términos</li>
        <li>Revisa que los datos estén en el formato correcto</li>
    </ul>
</body>
</html> 