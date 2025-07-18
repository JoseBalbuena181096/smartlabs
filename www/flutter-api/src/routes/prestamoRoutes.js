const express = require('express');
const prestamoController = require('../controllers/prestamoController');
const router = express.Router();

/**
 * @route POST /api/prestamo/control/
 * @desc Controla un dispositivo de préstamo (sustituye funcionalidad de main_usuariosLV2.cpp)
 * @access Public
 * @body { registration: string, device_serie: string, action: number }
 */
router.post('/control/', prestamoController.controlPrestamo);

/**
 * @route POST /api/prestamo/equipo/
 * @desc Procesa préstamo de equipo (replica loan_querye del dispositivo físico)
 * @access Public
 * @body { device_serie: string, rfid_equipo: string }
 */
router.post('/equipo/', prestamoController.prestarEquipo);

/**
 * @route POST /api/prestamo/simular/
 * @desc Simula el dispositivo físico RFID: busca usuario por matrícula y obtiene su RFID automáticamente
 * @access Public
 * @body { registration: string, device_serie: string }
 */
router.post('/simular/', prestamoController.simularDispositivoFisico);

/**
 * @route GET /api/prestamo/estado/
 * @desc Obtiene el estado actual de la sesión de préstamo
 * @access Public
 */
router.get('/estado/', prestamoController.obtenerEstadoSesion);

module.exports = router;