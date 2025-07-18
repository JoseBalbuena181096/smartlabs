const deviceService = require('../services/deviceService');
const userService = require('../services/userService');
const Joi = require('joi');

/**
 * Controlador para manejo de dispositivos
 */
class DeviceController {
    /**
     * Controla un dispositivo (encender/apagar) usando matrícula y QR
     */
    async controlDevice(req, res) {
        try {
            // Validar entrada
            const schema = Joi.object({
                registration: Joi.string().required().min(1).max(50),
                device_serie: Joi.string().required().min(1).max(50),
                action: Joi.number().integer().valid(0, 1).required()
            });

            const { error, value } = schema.validate(req.body);
            if (error) {
                return res.status(400).json({
                    success: false,
                    message: 'Datos inválidos',
                    error: error.details[0].message
                });
            }

            const { registration, device_serie, action } = value;

            // Verificar que el usuario existe
            const user = await userService.getUserByRegistration(registration);
            if (!user) {
                return res.status(404).json({
                    success: false,
                    message: 'Usuario no encontrado',
                    data: null
                });
            }

            // Verificar que el dispositivo existe
            const device = await deviceService.getDeviceBySerial(device_serie);
            if (!device) {
                return res.status(404).json({
                    success: false,
                    message: 'Dispositivo no encontrado',
                    data: null
                });
            }

            // Controlar el dispositivo
            const result = await deviceService.controlDevice(
                device_serie,
                user.id,
                user.name,
                action
            );

            if (!result.success) {
                return res.status(500).json({
                    success: false,
                    message: result.message,
                    error: result.error
                });
            }

            res.json({
                success: true,
                message: result.message,
                data: {
                    action: result.action,
                    state: result.state,
                    device: result.device,
                    user: result.user,
                    timestamp: result.timestamp
                }
            });
        } catch (error) {
            console.error('❌ Error en controlDevice:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: process.env.NODE_ENV === 'development' ? error.message : undefined
            });
        }
    }

    /**
     * Obtiene información de un dispositivo por su serie
     */
    async getDeviceInfo(req, res) {
        try {
            // Validar entrada
            const schema = Joi.object({
                device_serie: Joi.string().required().min(1).max(50)
            });

            const { error, value } = schema.validate(req.params);
            if (error) {
                return res.status(400).json({
                    success: false,
                    message: 'Serie de dispositivo inválida',
                    error: error.details[0].message
                });
            }

            const { device_serie } = value;
            const device = await deviceService.getDeviceBySerial(device_serie);

            if (!device) {
                return res.status(404).json({
                    success: false,
                    message: 'Dispositivo no encontrado',
                    data: null
                });
            }

            // Obtener estado actual
            const currentState = await deviceService.getDeviceLastState(device_serie);

            res.json({
                success: true,
                message: 'Información del dispositivo obtenida exitosamente',
                data: {
                    device: {
                        id: device.id,
                        alias: device.alias,
                        serie: device.serie,
                        date: device.date
                    },
                    currentState: {
                        state: currentState.state,
                        lastUpdate: currentState.date,
                        isFirstUse: currentState.isFirstUse
                    }
                }
            });
        } catch (error) {
            console.error('❌ Error en getDeviceInfo:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: process.env.NODE_ENV === 'development' ? error.message : undefined
            });
        }
    }

    /**
     * Obtiene el historial de uso de un dispositivo
     */
    async getDeviceHistory(req, res) {
        try {
            // Validar entrada
            const schema = Joi.object({
                device_serie: Joi.string().required().min(1).max(50),
                limit: Joi.number().integer().min(1).max(100).default(20)
            });

            const { error, value } = schema.validate({
                ...req.params,
                ...req.query
            });

            if (error) {
                return res.status(400).json({
                    success: false,
                    message: 'Parámetros inválidos',
                    error: error.details[0].message
                });
            }

            const { device_serie, limit } = value;
            
            // Verificar que el dispositivo existe
            const device = await deviceService.getDeviceBySerial(device_serie);
            if (!device) {
                return res.status(404).json({
                    success: false,
                    message: 'Dispositivo no encontrado',
                    data: null
                });
            }

            // Obtener historial
            const history = await deviceService.getDeviceHistory(device_serie, limit);

            res.json({
                success: true,
                message: 'Historial obtenido exitosamente',
                data: {
                    device: {
                        id: device.id,
                        alias: device.alias,
                        serie: device.serie
                    },
                    history: history,
                    total: history.length
                }
            });
        } catch (error) {
            console.error('❌ Error en getDeviceHistory:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: process.env.NODE_ENV === 'development' ? error.message : undefined
            });
        }
    }

    /**
     * Lista todos los dispositivos disponibles
     */
    async getAllDevices(req, res) {
        try {
            const devices = await deviceService.getAllDevices();

            res.json({
                success: true,
                message: 'Lista de dispositivos obtenida exitosamente',
                data: {
                    devices: devices,
                    total: devices.length
                }
            });
        } catch (error) {
            console.error('❌ Error en getAllDevices:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: process.env.NODE_ENV === 'development' ? error.message : undefined
            });
        }
    }

    /**
     * Obtiene el estado actual de un dispositivo
     */
    async getDeviceStatus(req, res) {
        try {
            // Validar entrada
            const schema = Joi.object({
                device_serie: Joi.string().required().min(1).max(50)
            });

            const { error, value } = schema.validate(req.params);
            if (error) {
                return res.status(400).json({
                    success: false,
                    message: 'Serie de dispositivo inválida',
                    error: error.details[0].message
                });
            }

            const { device_serie } = value;
            
            // Verificar que el dispositivo existe
            const device = await deviceService.getDeviceBySerial(device_serie);
            if (!device) {
                return res.status(404).json({
                    success: false,
                    message: 'Dispositivo no encontrado',
                    data: null
                });
            }

            // Obtener estado actual
            const currentState = await deviceService.getDeviceLastState(device_serie);

            res.json({
                success: true,
                message: 'Estado del dispositivo obtenido exitosamente',
                data: {
                    device_serie: device_serie,
                    device_alias: device.alias,
                    state: currentState.state,
                    status: currentState.state ? 'Encendido' : 'Apagado',
                    lastUpdate: currentState.date,
                    isFirstUse: currentState.isFirstUse,
                    timestamp: new Date().toISOString()
                }
            });
        } catch (error) {
            console.error('❌ Error en getDeviceStatus:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: process.env.NODE_ENV === 'development' ? error.message : undefined
            });
        }
    }
}

module.exports = new DeviceController();