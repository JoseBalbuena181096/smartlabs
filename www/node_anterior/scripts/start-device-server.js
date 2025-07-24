/**
 * Script para iniciar únicamente el servidor de estado de dispositivos
 */

const path = require('path');

// Importar el servidor de estado de dispositivos
const serverPath = path.join(__dirname, '../src/services/device-status/server.js');

try {
    console.log('🔧 Iniciando servidor de estado de dispositivos...');
    console.log('📁 Cargando desde:', serverPath);
    
    // Ejecutar el servidor de estado de dispositivos
    require(serverPath);
    
    console.log('✅ Servidor de estado de dispositivos iniciado correctamente');
    
} catch (error) {
    console.error('❌ Error iniciando servidor de estado de dispositivos:', error);
    process.exit(1);
}