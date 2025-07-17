/**
 * Script para iniciar todos los servicios del sistema SMARTLABS
 */

const SmartLabsSystem = require('../src/index');

async function startAllServices() {
    try {
        console.log('🚀 Iniciando todos los servicios de SMARTLABS...');
        
        const system = new SmartLabsSystem();
        
        // Configurar cierre limpio
        system.setupGracefulShutdown();
        
        // Inicializar todos los servicios
        await system.init();
        
        console.log('✅ Todos los servicios de SMARTLABS están activos');
        console.log('📊 Para detener el sistema, presiona Ctrl+C');
        
    } catch (error) {
        console.error('❌ Error iniciando servicios:', error);
        process.exit(1);
    }
}

startAllServices();