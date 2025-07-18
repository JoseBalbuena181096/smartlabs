const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
require('dotenv').config();

// Importar configuraciones
const dbConfig = require('./config/database');
const mqttConfig = require('./config/mqtt');

// Importar servicios
// const mqttListenerService = require('./services/mqttListenerService'); // ELIMINADO - Listener MQTT removido

// Importar rutas
const userRoutes = require('./routes/userRoutes');
const deviceRoutes = require('./routes/deviceRoutes');
const prestamoRoutes = require('./routes/prestamoRoutes');
// const mqttRoutes = require('./routes/mqttRoutes'); // ELIMINADO - Rutas MQTT removidas
const { router: internalRoutes, setIoTServerInstance } = require('./routes/internalRoutes');

// Importar middleware
const { optionalAuth } = require('./middleware/auth');
const { errorHandler, notFoundHandler, requestLogger } = require('./middleware/errorHandler');

/**
 * Aplicación principal SMARTLABS Flutter API
 */
class SmartLabsFlutterAPI {
    constructor() {
        this.app = express();
        this.port = process.env.PORT || 3000;
        this.isRunning = false;
    }

    /**
     * Configura middlewares de la aplicación
     */
    setupMiddlewares() {
        // Seguridad
        this.app.use(helmet({
            crossOriginResourcePolicy: { policy: "cross-origin" }
        }));
        
        // CORS - Permitir acceso desde Flutter
        this.app.use(cors({
            origin: ['http://localhost:3000', 'http://127.0.0.1:3000'],
            methods: ['GET', 'POST', 'PUT', 'DELETE'],
            allowedHeaders: ['Content-Type', 'Authorization', 'x-api-key'],
            credentials: true
        }));
        
        // Rate limiting
        const limiter = rateLimit({
            windowMs: 15 * 60 * 1000, // 15 minutos
            max: 100, // máximo 100 requests por ventana
            message: {
                success: false,
                message: 'Demasiadas solicitudes, intenta de nuevo más tarde',
                error: 'Rate limit excedido'
            },
            standardHeaders: true,
            legacyHeaders: false
        });
        this.app.use('/api/', limiter);
        
        // Parsing
        this.app.use(express.json({ limit: '10mb' }));
        this.app.use(express.urlencoded({ extended: true, limit: '10mb' }));
        
        // Logging
        this.app.use(requestLogger);
    }

    /**
     * Configura las rutas de la API
     */
    setupRoutes() {
        // Ruta de salud
        this.app.get('/health', (req, res) => {
            res.json({
                success: true,
                message: 'SMARTLABS Flutter API funcionando correctamente',
                data: {
                    status: 'healthy',
                    timestamp: new Date().toISOString(),
                    version: '1.0.0',
                    environment: process.env.NODE_ENV || 'development'
                }
            });
        });

        // Ruta de información de la API
        this.app.get('/api', (req, res) => {
            res.json({
                success: true,
                message: 'SMARTLABS Flutter API',
                data: {
                    name: 'SMARTLABS Flutter API',
                    version: '1.0.0',
                    description: 'API REST para aplicación Flutter de control de equipos SMARTLABS',
                    endpoints: {
                        users: '/api/users',
                        devices: '/api/devices'
                    },
                    documentation: {
                        users: {
                            'GET /api/users/registration/:registration': 'Obtiene usuario por matrícula',
                            'GET /api/users/rfid/:rfid': 'Obtiene usuario por RFID',
                            'GET /api/users/registration/:registration/history': 'Historial de acceso del usuario',
                            'GET /api/users/validate/:registration': 'Valida si un usuario existe'
                        },
                        devices: {
                            'POST /api/devices/control': 'Controla dispositivo (body: {registration, device_serie, action})',
                            'GET /api/devices': 'Lista todos los dispositivos',
                            'GET /api/devices/:device_serie': 'Información del dispositivo',
                            'GET /api/devices/:device_serie/status': 'Estado actual del dispositivo',
                            'GET /api/devices/:device_serie/history': 'Historial de uso del dispositivo'
                        },
                        prestamo: {
                             'POST /api/prestamo/control/': 'Controla préstamo de dispositivo manualmente'
                         },
                        internal: {
                            'POST /api/internal/loan-session': 'Notifica sesión de préstamo (interno)',
                            'GET /api/internal/status': 'Estado del sistema interno'
                        }
                    }
                }
            });
        });

        // Rutas principales con autenticación opcional
        this.app.use('/api/users', optionalAuth, userRoutes);
        this.app.use('/api/devices', optionalAuth, deviceRoutes);
        this.app.use('/api/prestamo', optionalAuth, prestamoRoutes);
        // this.app.use('/api/mqtt', optionalAuth, mqttRoutes); // ELIMINADO - Rutas MQTT removidas
        this.app.use('/api/internal', internalRoutes); // Sin autenticación para comunicación interna
        
        // Intentar conectar con el servidor IoT Node.js para sincronización
        try {
            const IoTMQTTServer = require('../../node/src/services/iot/IoTMQTTServer');
            const iotServer = new IoTMQTTServer();
            iotServer.init().then(() => {
                setIoTServerInstance(iotServer);
                console.log('✅ Conexión con servidor IoT Node.js establecida');
            }).catch(error => {
                console.warn('⚠️ No se pudo conectar con servidor IoT Node.js:', error.message);
                console.warn('   Las notificaciones internas funcionarán sin sincronización de estado');
            });
        } catch (error) {
            console.warn('⚠️ Servidor IoT Node.js no disponible:', error.message);
            console.warn('   Las notificaciones internas funcionarán sin sincronización de estado');
        }
        
        // Middleware de rutas no encontradas
        this.app.use(notFoundHandler);
        
        // Middleware de manejo de errores
        this.app.use(errorHandler);
    }

    /**
     * Inicializa las conexiones necesarias
     */
    async initializeConnections() {
        try {
            console.log('🚀 Inicializando conexiones...');
            
            // Conectar a base de datos
            await dbConfig.connect();
            
            // Conectar a MQTT
            await mqttConfig.connect();
            
            // ELIMINADO - Listener MQTT para consultas RFID del hardware removido
            // const listenerStarted = await mqttListenerService.startListening();
            // if (listenerStarted) {
            //     console.log('🎧 Listener MQTT para consultas RFID iniciado correctamente');
            // } else {
            //     console.warn('⚠️ No se pudo iniciar el listener MQTT para consultas RFID');
            // }
            
            console.log('✅ Todas las conexiones inicializadas correctamente');
        } catch (error) {
            console.error('❌ Error inicializando conexiones:', error);
            throw error;
        }
    }

    /**
     * Inicia el servidor
     */
    async start() {
        try {
            console.log('🚀 Iniciando SMARTLABS Flutter API...');
            
            // Configurar middlewares y rutas
            this.setupMiddlewares();
            this.setupRoutes();
            
            // Inicializar conexiones
            await this.initializeConnections();
            
            // Iniciar servidor HTTP
            this.server = this.app.listen(this.port, () => {
                console.log('✅ SMARTLABS Flutter API iniciada correctamente');
                console.log(`🌐 Servidor ejecutándose en http://localhost:${this.port}`);
                console.log(`📚 Documentación disponible en http://localhost:${this.port}/api`);
                console.log(`💚 Health check en http://localhost:${this.port}/health`);
                console.log('📊 Endpoints principales:');
                console.log(`   - POST http://localhost:${this.port}/api/devices/control`);
                console.log(`   - POST http://localhost:${this.port}/api/prestamo/control/`);
                console.log(`   - GET  http://localhost:${this.port}/api/users/registration/:registration`);
                console.log(`   - GET  http://localhost:${this.port}/api/devices/:device_serie/status`);
            });
            
            this.isRunning = true;
            
        } catch (error) {
            console.error('❌ Error iniciando servidor:', error);
            process.exit(1);
        }
    }

    /**
     * Detiene el servidor
     */
    async stop() {
        try {
            console.log('🛑 Deteniendo SMARTLABS Flutter API...');
            
            if (this.server) {
                this.server.close();
            }
            
            // Cerrar conexiones
            await dbConfig.close();
            await mqttConfig.close();
            
            this.isRunning = false;
            console.log('✅ Servidor detenido correctamente');
            
        } catch (error) {
            console.error('❌ Error deteniendo servidor:', error);
        }
    }

    /**
     * Configura el cierre limpio del servidor
     */
    setupGracefulShutdown() {
        const signals = ['SIGTERM', 'SIGINT', 'SIGUSR2'];
        
        signals.forEach(signal => {
            process.on(signal, async () => {
                console.log(`\n📡 Señal ${signal} recibida, cerrando servidor...`);
                await this.stop();
                process.exit(0);
            });
        });
        
        process.on('uncaughtException', (error) => {
            console.error('❌ Excepción no capturada:', error);
            process.exit(1);
        });
        
        process.on('unhandledRejection', (reason, promise) => {
            console.error('❌ Promesa rechazada no manejada:', reason);
            process.exit(1);
        });
    }
}

// Inicializar y ejecutar la aplicación
if (require.main === module) {
    const api = new SmartLabsFlutterAPI();
    
    // Configurar cierre limpio
    api.setupGracefulShutdown();
    
    // Iniciar servidor
    api.start().catch(error => {
        console.error('❌ Error fatal:', error);
        process.exit(1);
    });
}

module.exports = SmartLabsFlutterAPI;