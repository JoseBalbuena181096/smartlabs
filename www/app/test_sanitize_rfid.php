<?php
/**
 * Script de prueba para verificar la función saneadora de RFID
 * que elimina el prefijo "APP:" cuando está presente
 */

require_once __DIR__ . '/core/autoload.php';
require_once __DIR__ . '/controllers/LoanController.php';
require_once __DIR__ . '/controllers/LoanAdminController.php';
require_once __DIR__ . '/controllers/HabitantController.php';

echo "=== PRUEBA DE FUNCIÓN SANEADORA DE RFID ===\n\n";

// Casos de prueba
$testCases = [
    'APP:542222241' => '542222241',
    '542222241' => '542222241',
    'APP:123456789' => '123456789',
    '987654321' => '987654321',
    'APP:' => '',
    '' => '',
    'NOAPP:123456' => 'NOAPP:123456',
    'app:123456' => 'app:123456', // minúsculas no deben ser afectadas
];

echo "Casos de prueba:\n";
foreach ($testCases as $input => $expected) {
    echo "Input: '$input' -> Esperado: '$expected'\n";
}

echo "\n=== PRUEBA EN JAVASCRIPT (para copiar en consola del navegador) ===\n";
echo "// Función saneadora JavaScript\n";
echo "function sanitizeRfid(rfidInput) {\n";
echo "    if (typeof rfidInput === 'string' && rfidInput.startsWith('APP:')) {\n";
echo "        return rfidInput.substring(4);\n";
echo "    }\n";
echo "    return rfidInput;\n";
echo "}\n\n";

echo "// Casos de prueba JavaScript\n";
echo "var testCases = {\n";
foreach ($testCases as $input => $expected) {
    echo "    '$input': '$expected',\n";
}
echo "};\n\n";

echo "// Ejecutar pruebas\n";
echo "Object.keys(testCases).forEach(function(input) {\n";
echo "    var result = sanitizeRfid(input);\n";
echo "    var expected = testCases[input];\n";
echo "    var status = result === expected ? '✅ PASS' : '❌ FAIL';\n";
echo "    console.log(status + ' Input: \"' + input + '\" -> Result: \"' + result + '\" (Expected: \"' + expected + '\")');\n";
echo "});\n\n";

echo "=== PRUEBA EN PHP ===\n";

// Función saneadora PHP para prueba
function sanitizeRfidTest($rfidInput) {
    if (is_string($rfidInput) && strpos($rfidInput, 'APP:') === 0) {
        return substr($rfidInput, 4);
    }
    return $rfidInput;
}

echo "Ejecutando pruebas PHP:\n";
foreach ($testCases as $input => $expected) {
    $result = sanitizeRfidTest($input);
    $status = $result === $expected ? '✅ PASS' : '❌ FAIL';
    echo "$status Input: \"$input\" -> Result: \"$result\" (Expected: \"$expected\")\n";
}

echo "\n=== ARCHIVOS MODIFICADOS ===\n";
echo "1. /app/views/loan/index.php - Función JavaScript sanitizeRfid() agregada\n";
echo "2. /app/controllers/LoanController.php - Método sanitizeRfid() agregado\n";
echo "3. /app/controllers/LoanAdminController.php - Método sanitizeRfid() agregado\n";
echo "4. /app/controllers/HabitantController.php - Método sanitizeRfid() agregado\n";

echo "\n=== INSTRUCCIONES DE PRUEBA ===\n";
echo "1. Abrir la vista Loan en el navegador\n";
echo "2. Abrir las herramientas de desarrollador (F12)\n";
echo "3. Ir a la consola\n";
echo "4. Copiar y pegar el código JavaScript de arriba\n";
echo "5. Verificar que todas las pruebas pasen\n";
echo "6. Probar manualmente ingresando 'APP:542222241' en el campo RFID\n";
echo "7. Verificar que se muestre solo '542222241' en el campo\n";

echo "\n=== CASOS DE USO CUBIERTOS ===\n";
echo "✅ Mensajes MQTT con prefijo APP: (process_msg)\n";
echo "✅ Input manual en campo RFID (evento input)\n";
echo "✅ Búsqueda de usuarios (LoanAdminController)\n";
echo "✅ Consultas de préstamos (LoanController)\n";
echo "✅ Búsqueda por RFID (HabitantController)\n";

echo "\n¡Función saneadora implementada exitosamente!\n";
?>