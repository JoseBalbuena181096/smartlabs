const express = require('express');
const router = express.Router();

// Importar el servidor IoT para acceder al estado de sesiones
let iotServerInstance = null;

// Función para establecer la instancia del servidor IoT
function setIoTServerInstance(instance) {
    iotServerInstance = instance;
}

// Función para obtener la instancia del servidor IoT
function getIoTServerInstance() {
    return iotServerInstance;
}

/**
 * @route POST /api/internal/loan-session
 * @desc Endpoint interno para notificar sesiones de préstamo desde la API Flutter al backend Node.js
 * @access Internal (solo para comunicación entre servicios)
 */
router.post('/loan-session', async (req, res) => {
    try {
        const { device_serie, user_rfid, action } = req.body;
        
        // Validar parámetros requeridos
        if (!device_serie || !user_rfid || !action) {
            return res.status(400).json({
                success: false,
                message: 'Parámetros requeridos: device_serie, user_rfid, action',
                error: 'Parámetros faltantes'
            });
        }
        
        // Validar que action sea 'on' o 'off'
        if (!['on', 'off'].includes(action)) {
            return res.status(400).json({
                success: false,
                message: 'El parámetro action debe ser "on" o "off"',
                error: 'Valor de action inválido'
            });
        }
        
        console.log(`🔄 [INTERNAL] Notificación de sesión recibida:`);
        console.log(`   📱 Dispositivo: ${device_serie}`);
        console.log(`   👤 Usuario RFID: ${user_rfid}`);
        console.log(`   🎯 Acción: ${action}`);
        
        // Acceder al servidor IoT para sincronizar el estado de sesión
        const iotServer = getIoTServerInstance();
        
        if (!iotServer) {
            console.warn('⚠️ [INTERNAL] Servidor IoT no disponible, solo registrando operación');
        } else {
            // Sincronizar el estado con el servidor IoT
            if (action === 'on') {
                // Simular inicio de sesión
                if (iotServer.countLoanCard === 0) {
                    // Obtener información del usuario para establecer la sesión
                    try {
                        const [cards] = await iotServer.dbConnection.execute(
                            'SELECT * FROM cards_habs WHERE cards_number = ?',
                            [user_rfid]
                        );
                        
                        if (cards.length === 1) {
                            iotServer.serialLoanUser = cards;
                            iotServer.countLoanCard = 1;
                            console.log(`✅ [INTERNAL] Sesión iniciada en backend Node.js para: ${cards[0].hab_name}`);
                        }
                    } catch (dbError) {
                        console.error('❌ [INTERNAL] Error consultando usuario:', dbError);
                    }
                } else {
                    console.log('⚠️ [INTERNAL] Ya hay una sesión activa en backend Node.js');
                }
            } else {
                // Simular cierre de sesión
                iotServer.countLoanCard = 0;
                iotServer.serialLoanUser = null;
                console.log('✅ [INTERNAL] Sesión finalizada en backend Node.js');
            }
        }
        
        const sessionData = {
            device_serie,
            user_rfid,
            action,
            timestamp: new Date().toISOString(),
            source: 'flutter-api',
            backend_synced: iotServer ? true : false
        };
        
        console.log(`✅ [INTERNAL] Sesión ${action === 'on' ? 'iniciada' : 'finalizada'} correctamente`);
        
        res.json({
            success: true,
            message: `Sesión ${action === 'on' ? 'iniciada' : 'finalizada'} correctamente`,
            data: sessionData
        });
        
    } catch (error) {
        console.error('❌ [INTERNAL] Error procesando notificación de sesión:', error);
        res.status(500).json({
            success: false,
            message: 'Error interno del servidor',
            error: error.message
        });
    }
});

/**
 * @route GET /api/internal/status
 * @desc Estado del sistema interno
 * @access Internal
 */
router.get('/status', (req, res) => {
    res.json({
        success: true,
        message: 'Sistema interno funcionando correctamente',
        data: {
            service: 'flutter-api-internal',
            status: 'active',
            timestamp: new Date().toISOString()
        }
    });
});

module.exports = {
    router,
    setIoTServerInstance,
    getIoTServerInstance
};