const Joi = require('joi');
const prestamoService = require('../services/prestamoService');

/**
 * Controlador para manejo de préstamos de dispositivos
 * Sustituye la funcionalidad del dispositivo físico main_usuariosLV2.cpp
 */
class PrestamoController {
    /**
     * Controla un dispositivo de préstamo (replicando loan_queryu)
     * @param {Object} req - Request object
     * @param {Object} res - Response object
     */
    async controlPrestamo(req, res) {
        try {
            // Esquema de validación
            const schema = Joi.object({
                registration: Joi.string().required().messages({
                    'string.empty': 'La matrícula es requerida',
                    'any.required': 'La matrícula es requerida'
                }),
                device_serie: Joi.string().required().messages({
                    'string.empty': 'La serie del dispositivo es requerida',
                    'any.required': 'La serie del dispositivo es requerida'
                }),
                action: Joi.number().integer().valid(0, 1).required().messages({
                    'number.base': 'La acción debe ser un número',
                    'any.only': 'La acción debe ser 0 (apagar) o 1 (encender)',
                    'any.required': 'La acción es requerida'
                })
            });

            // Validar datos de entrada
            const { error, value } = schema.validate(req.body);
            if (error) {
                return res.status(400).json({
                    success: false,
                    message: 'Datos de entrada inválidos',
                    error: error.details[0].message
                });
            }

            const { registration, device_serie, action } = value;

            // Convertir acción numérica a string para el servicio
            const actionString = action === 1 ? 'on' : 'off';

            // Llamar al servicio para procesar el préstamo (replicando loan_queryu)
            const result = await prestamoService.procesarPrestamo(
                registration,
                device_serie,
                actionString
            );

            if (result.success) {
                res.status(200).json({
                    success: true,
                    message: result.message,
                    data: {
                        device_serie: device_serie,
                        device_name: result.data.dispositivo,
                        user: {
                            name: result.data.usuario
                        },
                        action: actionString,
                        state: result.data.estado,
                        timestamp: new Date().toISOString()
                    }
                });
            } else {
                res.status(400).json({
                    success: false,
                    message: result.message,
                    error: 'Error procesando préstamo'
                });
            }

        } catch (error) {
            console.error('❌ Error en controlPrestamo:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: 'Error procesando la solicitud de préstamo'
            });
        }
    }

    /**
     * Maneja préstamo de equipos (replicando loan_querye del dispositivo físico)
     * @param {Object} req - Request object
     * @param {Object} res - Response object
     */
    async prestarEquipo(req, res) {
        try {
            // Esquema de validación
            const schema = Joi.object({
                device_serie: Joi.string().required().messages({
                    'string.empty': 'La serie del dispositivo es requerida',
                    'any.required': 'La serie del dispositivo es requerida'
                }),
                equip_rfid: Joi.string().required().messages({
                    'string.empty': 'El RFID del equipo es requerido',
                    'any.required': 'El RFID del equipo es requerido'
                })
            });

            // Validar datos de entrada
            const { error, value } = schema.validate(req.body);
            if (error) {
                return res.status(400).json({
                    success: false,
                    message: 'Datos de entrada inválidos',
                    error: error.details[0].message
                });
            }

            const { device_serie, equip_rfid } = value;

            // Llamar al servicio para procesar el préstamo de equipo
            const result = await prestamoService.handleLoanEquipmentQuery(
                device_serie,
                equip_rfid
            );

            if (result.success) {
                res.status(200).json({
                    success: true,
                    message: result.message,
                    data: {
                        device_serie: device_serie,
                        equipment: result.equipment,
                        user: result.user,
                        action: result.action,
                        state: result.state,
                        timestamp: new Date().toISOString()
                    }
                });
            } else {
                const statusCode = result.action === 'no_login' ? 401 : 400;
                res.status(statusCode).json({
                    success: false,
                    message: result.message,
                    error: result.error || 'Error procesando préstamo de equipo',
                    action: result.action
                });
            }

        } catch (error) {
            console.error('❌ Error en prestarEquipo:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: 'Error procesando la solicitud de préstamo de equipo'
            });
        }
    }

    /**
     * Simula el dispositivo físico RFID: busca usuario por matrícula y obtiene su RFID automáticamente
     * Replica exactamente el comportamiento del hardware main_usuariosLV2.cpp
     * @param {Object} req - Request object
     * @param {Object} res - Response object
     */
    async simularDispositivoFisico(req, res) {
        try {
            // Esquema de validación
            const schema = Joi.object({
                registration: Joi.string().required().messages({
                    'string.empty': 'La matrícula es requerida',
                    'any.required': 'La matrícula es requerida'
                }),
                device_serie: Joi.string().required().messages({
                    'string.empty': 'La serie del dispositivo es requerida',
                    'any.required': 'La serie del dispositivo es requerida'
                })
            });

            // Validar datos de entrada
            const { error, value } = schema.validate(req.body);
            if (error) {
                return res.status(400).json({
                    success: false,
                    message: 'Datos de entrada inválidos',
                    error: error.details[0].message
                });
            }

            const { registration, device_serie } = value;

            // Llamar al servicio para simular el dispositivo físico
            const result = await prestamoService.simularDispositivoFisico(
                registration,
                device_serie
            );

            if (result.success) {
                res.status(200).json({
                    success: true,
                    message: result.message,
                    data: {
                        matricula: result.data.matricula,
                        usuario: result.data.usuario,
                        rfid: result.data.rfid,
                        dispositivo: result.data.dispositivo,
                        estado: result.data.estado,
                        device_serie: device_serie,
                        timestamp: new Date().toISOString(),
                        simulation: true // Indica que es una simulación del hardware
                    }
                });
            } else {
                res.status(400).json({
                    success: false,
                    message: result.message,
                    data: result.data,
                    error: 'Error simulando dispositivo físico'
                });
            }

        } catch (error) {
            console.error('❌ Error en simularDispositivoFisico:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: 'Error procesando la simulación del dispositivo físico'
            });
        }
    }

    /**
     * Obtiene el estado actual de la sesión de préstamo
     * @param {Object} req - Request object
     * @param {Object} res - Response object
     */
    async obtenerEstadoSesion(req, res) {
        try {
            const estadoSesion = prestamoService.getSessionState();
            
            res.status(200).json({
                success: true,
                message: 'Estado de sesión obtenido',
                data: {
                    session_active: estadoSesion.active,
                    user: estadoSesion.user,
                    login_count: estadoSesion.count,
                    timestamp: new Date().toISOString()
                }
            });
        } catch (error) {
            console.error('❌ Error obteniendo estado de sesión:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: 'Error obteniendo estado de sesión'
            });
        }
    }
}

module.exports = new PrestamoController();