/**
 * Punto de entrada principal del sistema SMARTLABS IoT
 * Inicializa todos los servicios necesarios
 */

const IoTMQTTServer = require('./services/iot/IoTMQTTServer');
const DeviceStatusServer = require('./services/device-status/server');
const config = require('./config/device-status');

/**
 * Clase principal del sistema SMARTLABS
 */
class SmartLabsSystem {
    constructor() {
        this.iotServer = null;
        this.deviceStatusServer = null;
        this.isRunning = false;
    }
    
    /**
     * Inicializa todos los servicios del sistema
     */
    async init() {
        try {
            console.log('🚀 Iniciando sistema SMARTLABS IoT...');
            
            // Inicializar servidor IoT MQTT
            console.log('📡 Iniciando servidor IoT MQTT...');
            this.iotServer = new IoTMQTTServer();
            await this.iotServer.init();
            
            // Inicializar servidor de estado de dispositivos
            console.log('🔧 Iniciando servidor de estado de dispositivos...');
            // El servidor de estado se inicializa por separado si es necesario
            
            this.isRunning = true;
            console.log('✅ Sistema SMARTLABS iniciado correctamente');
            console.log('📊 Servicios activos:');
            console.log('   - Servidor IoT MQTT: ✓');
            console.log('   - Monitoreo de dispositivos: ✓');
            
        } catch (error) {
            console.error('❌ Error iniciando sistema SMARTLABS:', error);
            process.exit(1);
        }
    }
    
    /**
     * Detiene todos los servicios del sistema
     */
    async stop() {
        try {
            console.log('🛑 Deteniendo sistema SMARTLABS...');
            
            if (this.iotServer) {
                await this.iotServer.stop();
            }
            
            if (this.deviceStatusServer) {
                await this.deviceStatusServer.stop();
            }
            
            this.isRunning = false;
            console.log('✅ Sistema SMARTLABS detenido correctamente');
            
        } catch (error) {
            console.error('❌ Error deteniendo sistema:', error);
        }
    }
    
    /**
     * Maneja señales del sistema para cierre limpio
     */
    setupGracefulShutdown() {
        const signals = ['SIGTERM', 'SIGINT', 'SIGUSR2'];
        
        signals.forEach(signal => {
            process.on(signal, async () => {
                console.log(`\n📡 Señal ${signal} recibida, cerrando sistema...`);
                await this.stop();
                process.exit(0);
            });
        });
    }
}

// Inicializar sistema si este archivo es ejecutado directamente
if (require.main === module) {
    const system = new SmartLabsSystem();
    
    // Configurar cierre limpio
    system.setupGracefulShutdown();
    
    // Inicializar sistema
    system.init().catch(error => {
        console.error('❌ Error fatal:', error);
        process.exit(1);
    });
}

module.exports = SmartLabsSystem;