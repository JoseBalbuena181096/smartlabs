const userService = require('../services/userService');
const Joi = require('joi');

/**
 * Controlador para manejo de usuarios
 */
class UserController {
    /**
     * Busca un usuario por matrícula
     */
    async getUserByRegistration(req, res) {
        try {
            // Validar entrada
            const schema = Joi.object({
                registration: Joi.string().required().min(1).max(50)
            });

            const { error, value } = schema.validate(req.params);
            if (error) {
                return res.status(400).json({
                    success: false,
                    message: 'Matrícula inválida',
                    error: error.details[0].message
                });
            }

            const { registration } = value;
            const user = await userService.getUserByRegistration(registration);

            if (!user) {
                return res.status(404).json({
                    success: false,
                    message: 'Usuario no encontrado',
                    data: null
                });
            }

            res.json({
                success: true,
                message: 'Usuario encontrado exitosamente',
                data: {
                    id: user.id,
                    name: user.name,
                    registration: user.registration,
                    email: user.email,
                    cards_number: user.cards_number,
                    device_id: user.device_id
                }
            });
        } catch (error) {
            console.error('❌ Error en getUserByRegistration:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: process.env.NODE_ENV === 'development' ? error.message : undefined
            });
        }
    }

    /**
     * Busca un usuario por RFID
     */
    async getUserByRFID(req, res) {
        try {
            // Validar entrada
            const schema = Joi.object({
                rfid: Joi.string().required().min(1).max(50)
            });

            const { error, value } = schema.validate(req.params);
            if (error) {
                return res.status(400).json({
                    success: false,
                    message: 'RFID inválido',
                    error: error.details[0].message
                });
            }

            const { rfid } = value;
            const user = await userService.getUserByRFID(rfid);

            if (!user) {
                return res.status(404).json({
                    success: false,
                    message: 'Usuario no encontrado',
                    data: null
                });
            }

            res.json({
                success: true,
                message: 'Usuario encontrado exitosamente',
                data: {
                    id: user.id,
                    name: user.name,
                    registration: user.registration,
                    email: user.email,
                    cards_number: user.cards_number,
                    device_id: user.device_id
                }
            });
        } catch (error) {
            console.error('❌ Error en getUserByRFID:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: process.env.NODE_ENV === 'development' ? error.message : undefined
            });
        }
    }

    /**
     * Obtiene el historial de acceso de un usuario
     */
    async getUserHistory(req, res) {
        try {
            // Validar entrada
            const schema = Joi.object({
                registration: Joi.string().required().min(1).max(50),
                limit: Joi.number().integer().min(1).max(100).default(10)
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

            const { registration, limit } = value;
            
            // Primero obtener el usuario
            const user = await userService.getUserByRegistration(registration);
            if (!user) {
                return res.status(404).json({
                    success: false,
                    message: 'Usuario no encontrado',
                    data: null
                });
            }

            // Obtener historial
            const history = await userService.getUserAccessHistory(user.id, limit);

            res.json({
                success: true,
                message: 'Historial obtenido exitosamente',
                data: {
                    user: {
                        id: user.id,
                        name: user.name,
                        registration: user.registration
                    },
                    history: history,
                    total: history.length
                }
            });
        } catch (error) {
            console.error('❌ Error en getUserHistory:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: process.env.NODE_ENV === 'development' ? error.message : undefined
            });
        }
    }

    /**
     * Valida si un usuario existe
     */
    async validateUser(req, res) {
        try {
            // Validar entrada
            const schema = Joi.object({
                registration: Joi.string().required().min(1).max(50)
            });

            const { error, value } = schema.validate(req.params);
            if (error) {
                return res.status(400).json({
                    success: false,
                    message: 'Matrícula inválida',
                    error: error.details[0].message
                });
            }

            const { registration } = value;
            const isValid = await userService.validateUser(registration);

            res.json({
                success: true,
                message: isValid ? 'Usuario válido' : 'Usuario no encontrado',
                data: {
                    registration: registration,
                    isValid: isValid
                }
            });
        } catch (error) {
            console.error('❌ Error en validateUser:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor',
                error: process.env.NODE_ENV === 'development' ? error.message : undefined
            });
        }
    }
}

module.exports = new UserController();