/**
 * Script de prueba para verificar la sincronización entre
 * prestamoService y mqttListenerService
 */

const prestamoService = require('./src/services/prestamoService');
const MQTTListenerService = require('./src/services/mqttListenerService');

async function testSincronizacion() {
    console.log('🧪 Iniciando prueba de sincronización...');
    
    // Crear instancia del MQTT Listener
    const mqttListener = new MQTTListenerService();
    
    console.log('\n📊 Estado inicial:');
    console.log('PrestamoService:', prestamoService.getSessionState());
    console.log('MQTTListener:', mqttListener.getSessionState());
    
    // Simular cambio de estado en prestamoService
    console.log('\n🔄 Simulando cambio de estado en prestamoService...');
    prestamoService.countLoanCard = 1;
    prestamoService.serialLoanUser = [{ hab_name: 'Usuario Test' }];
    
    console.log('\n📊 Estado después del cambio:');
    console.log('PrestamoService:', prestamoService.getSessionState());
    console.log('MQTTListener (sincronizado):', mqttListener.getSessionState());
    
    // Verificar que están sincronizados
    const prestamoState = prestamoService.getSessionState();
    const mqttState = mqttListener.getSessionState();
    
    const sincronizado = (
        prestamoState.active === mqttState.active &&
        prestamoState.user === mqttState.user &&
        prestamoState.count === mqttState.count
    );
    
    console.log('\n✅ Resultado de sincronización:', sincronizado ? 'EXITOSO' : 'FALLIDO');
    
    // Limpiar estado
    prestamoService.countLoanCard = 0;
    prestamoService.serialLoanUser = null;
    
    console.log('\n📊 Estado final (limpiado):');
    console.log('PrestamoService:', prestamoService.getSessionState());
    console.log('MQTTListener:', mqttListener.getSessionState());
}

// Ejecutar prueba si se llama directamente
if (require.main === module) {
    testSincronizacion().catch(console.error);
}

module.exports = { testSincronizacion };