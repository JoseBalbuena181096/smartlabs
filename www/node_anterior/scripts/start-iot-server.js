/**
 * Script para iniciar únicamente el servidor IoT MQTT
 */

const IoTMQTTServer = require('../src/services/iot/IoTMQTTServer');

async function startIoTServer() {
    try {
        console.log('🚀 Iniciando servidor IoT MQTT...');
        
        const server = new IoTMQTTServer();
        await server.init();
        
        console.log('✅ Servidor IoT MQTT iniciado correctamente');
        
        // Manejo de señales para cierre limpio
        process.on('SIGTERM', async () => {
            console.log('\n📡 Señal SIGTERM recibida, cerrando servidor...');
            if (server.stop) {
                await server.stop();
            }
            process.exit(0);
        });
        
        process.on('SIGINT', async () => {
            console.log('\n📡 Señal SIGINT recibida, cerrando servidor...');
            if (server.stop) {
                await server.stop();
            }
            process.exit(0);
        });
        
    } catch (error) {
        console.error('❌ Error iniciando servidor IoT:', error);
        process.exit(1);
    }
}

startIoTServer();