const express = require('express');
const userController = require('../controllers/userController');
const router = express.Router();

/**
 * @route GET /api/users/registration/:registration
 * @desc Obtiene un usuario por matrícula
 * @access Public
 */
router.get('/registration/:registration', userController.getUserByRegistration);

/**
 * @route GET /api/users/rfid/:rfid
 * @desc Obtiene un usuario por RFID
 * @access Public
 */
router.get('/rfid/:rfid', userController.getUserByRFID);

/**
 * @route GET /api/users/registration/:registration/history
 * @desc Obtiene el historial de acceso de un usuario
 * @access Public
 * @query limit - Límite de registros (default: 10, max: 100)
 */
router.get('/registration/:registration/history', userController.getUserHistory);

/**
 * @route GET /api/users/validate/:registration
 * @desc Valida si un usuario existe
 * @access Public
 */
router.get('/validate/:registration', userController.validateUser);

module.exports = router;