const express = require('express');
const router = express.Router();

// Estado de sesiones simplificado (sin dependencia del servidor IoT)
let currentSession = null;

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
        
        // Manejar estado de sesión simplificado
        if (action === 'on') {
            if (!currentSession) {
                currentSession = {
                    device_serie,
                    user_rfid,
                    started_at: new Date().toISOString()
                };
                console.log(`✅ [INTERNAL] Sesión iniciada para dispositivo: ${device_serie}`);
            } else {
                console.log('⚠️ [INTERNAL] Ya hay una sesión activa');
            }
        } else {
            if (currentSession) {
                currentSession = null;
                console.log('✅ [INTERNAL] Sesión finalizada');
            } else {
                console.log('⚠️ [INTERNAL] No hay sesión activa para finalizar');
            }
        }
        
        const sessionData = {
            device_serie,
            user_rfid,
            action,
            timestamp: new Date().toISOString(),
            source: 'flutter-api',
            current_session: currentSession
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
    router
};