const express = require('express');
const deviceController = require('../controllers/deviceController');
const router = express.Router();

/**
 * @route POST /api/devices/control
 * @desc Controla un dispositivo (encender/apagar)
 * @access Public
 * @body { registration: string, device_serie: string }
 */
router.post('/control', deviceController.controlDevice);

/**
 * @route GET /api/devices/:device_serie
 * @desc Obtiene información de un dispositivo por su serie
 * @access Public
 */
router.get('/:device_serie', deviceController.getDeviceInfo);

/**
 * @route GET /api/devices/:device_serie/history
 * @desc Obtiene el historial de uso de un dispositivo
 * @access Public
 * @query limit - Límite de registros (default: 20, max: 100)
 */
router.get('/:device_serie/history', deviceController.getDeviceHistory);

/**
 * @route GET /api/devices/:device_serie/status
 * @desc Obtiene el estado actual de un dispositivo
 * @access Public
 */
router.get('/:device_serie/status', deviceController.getDeviceStatus);

/**
 * @route GET /api/devices
 * @desc Lista todos los dispositivos disponibles
 * @access Public
 */
router.get('/', deviceController.getAllDevices);

module.exports = router;