
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
require('dotenv').config();

// Importar configuraciones
const dbConfig = require('./config/database');
const mqttConfig = require('./config/mqtt');

// Importar servicios
const mqttListenerService = require('./services/mqttListenerService'); // REINTEGRADO - Listener MQTT para hardware

// Importar rutas
const userRoutes = require('./routes/userRoutes');
const deviceRoutes = require('./routes/deviceRoutes');
const prestamoRoutes = require('./routes/prestamoRoutes');
// const mqttRoutes = require('./routes/mqttRoutes'); // ELIMINADO - Rutas MQTT removidas
const { router: internalRoutes } = require('./routes/internalRoutes');

// Importar middleware
const { optionalAuth } = require('./middleware/auth');
const { errorHandler, notFoundHandler, requestLogger } = require('./middleware/errorHandler');
const TimeoutMiddleware = require('./middleware/timeoutMiddleware');
const { asyncHandler } = require('./utils/asyncHandler');

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
        // Middleware de logging de requests
        this.app.use(requestLogger);
        
        // Timeout global para todas las requests
        this.app.use(TimeoutMiddleware.api(parseInt(process.env.API_TIMEOUT) || 30000));
        
        // Seguridad
        this.app.use(helmet({
            contentSecurityPolicy: {
                directives: {
                    defaultSrc: ["'self'"],
                    styleSrc: ["'self'", "'unsafe-inline'"],
                    scriptSrc: ["'self'"],
                    imgSrc: ["'self'", "data:", "https:"],
                },
            },
            crossOriginEmbedderPolicy: false,
            crossOriginResourcePolicy: { policy: "cross-origin" }
        }));
        
        // CORS mejorado - Permitir acceso desde Flutter
        this.app.use(cors({
            origin: [
                'http://localhost:3000', 
                'http://127.0.0.1:3000',
                'http://localhost:3001',
                'http://127.0.0.1:3001',
                'http://localhost:8080',
                'http://127.0.0.1:8080',
                'http://localhost:5000',
                'http://127.0.0.1:5000',
                `http://${process.env.SERVER_HOST || '192.168.0.100'}`,
                `http://${process.env.SERVER_HOST || '192.168.0.100'}:80`,
                `http://${process.env.API_HOST || '192.168.0.100'}:3000`,
                `http://${process.env.API_HOST || '192.168.0.100'}:3001`,
                `http://${process.env.SERVER_HOST || '192.168.0.100'}:8080`,
                `http://${process.env.SERVER_HOST || '192.168.0.100'}:5000`,
                /^http:\/\/localhost:\d+$/,
                /^http:\/\/127\.0\.0\.1:\d+$/,
                /^http:\/\/192\.168\.0\.100:\d+$/
            ],
            methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            allowedHeaders: ['Content-Type', 'Authorization', 'x-api-key', 'X-Requested-With'],
            exposedHeaders: ['X-Total-Count', 'X-Response-Time'],
            credentials: true
        }));
        
        // Rate limiting mejorado
        const limiter = rateLimit({
            windowMs: parseInt(process.env.RATE_LIMIT_WINDOW) || 15 * 60 * 1000, // 15 minutos
            max: parseInt(process.env.RATE_LIMIT_MAX) || 100, // máximo requests por ventana
            message: {
                success: false,
                error: 'RATE_LIMIT_EXCEEDED',
                message: 'Demasiadas solicitudes, intenta de nuevo más tarde',
                retryAfter: Math.ceil((parseInt(process.env.RATE_LIMIT_WINDOW) || 15 * 60 * 1000) / 1000)
            },
            standardHeaders: true,
            legacyHeaders: false,
            skip: (req) => {
                // Saltar rate limiting para health check
                return req.path === '/health';
            }
        });
        this.app.use('/api/', limiter);
        
        // Parsing JSON con límites configurables
        this.app.use(express.json({ 
            limit: process.env.JSON_LIMIT || '10mb',
            verify: (req, res, buf) => {
                // Agregar raw body para verificaciones si es necesario
                req.rawBody = buf;
            }
        }));
        this.app.use(express.urlencoded({ 
            extended: true, 
            limit: process.env.URL_ENCODED_LIMIT || '10mb' 
        }));
        
        // Middleware para agregar información de timing
        this.app.use((req, res, next) => {
            req.startTime = Date.now();
            
            // Agregar header de response time al finalizar
            const originalSend = res.send;
            res.send = function(data) {
                const responseTime = Date.now() - req.startTime;
                res.set('X-Response-Time', `${responseTime}ms`);
                originalSend.call(this, data);
            };
            
            next();
        });
    }

    /**
     * Configura las rutas de la API
     */
    setupRoutes() {
        // Health check endpoint mejorado
        this.app.get('/health', async (req, res) => {
            const startTime = Date.now();
            
            try {
                // Verificar estado de la base de datos
                let dbStatus = 'disconnected';
                let dbStats = {};
                try {
                    if (this.db && this.db.getPoolStats) {
                        dbStats = this.db.getPoolStats();
                        dbStatus = dbStats.connectionCount > 0 ? 'connected' : 'disconnected';
                    }
                } catch (dbError) {
                    console.warn('⚠️ Error verificando DB en health check:', dbError.message);
                }
                
                // Verificar estado MQTT
                let mqttStatus = 'disconnected';
                let mqttStats = {};
                try {
                    if (this.mqtt && this.mqtt.getConnectionStats) {
                        mqttStats = this.mqtt.getConnectionStats();
                        mqttStatus = mqttStats.connected ? 'connected' : 'disconnected';
                    }
                } catch (mqttError) {
                    console.warn('⚠️ Error verificando MQTT en health check:', mqttError.message);
                }
                
                // Estadísticas del sistema
                const memUsage = process.memoryUsage();
                const cpuUsage = process.cpuUsage();
                
                const healthData = {
                    status: 'OK',
                    timestamp: new Date().toISOString(),
                    uptime: process.uptime(),
                    environment: process.env.NODE_ENV || 'development',
                    version: process.env.npm_package_version || '1.0.0',
                    responseTime: Date.now() - startTime,
                    services: {
                        database: {
                            status: dbStatus,
                            ...dbStats
                        },
                        mqtt: {
                            status: mqttStatus,
                            ...mqttStats
                        }
                    },
                    system: {
                        memory: {
                            used: Math.round(memUsage.heapUsed / 1024 / 1024),
                            total: Math.round(memUsage.heapTotal / 1024 / 1024),
                            external: Math.round(memUsage.external / 1024 / 1024),
                            rss: Math.round(memUsage.rss / 1024 / 1024)
                        },
                        cpu: {
                            user: cpuUsage.user,
                            system: cpuUsage.system
                        },
                        nodeVersion: process.version,
                        platform: process.platform,
                        arch: process.arch
                    }
                };
                
                // Determinar estado general
                const overallHealthy = dbStatus === 'connected' && mqttStatus === 'connected';
                const statusCode = overallHealthy ? 200 : 503;
                
                if (!overallHealthy) {
                    healthData.status = 'DEGRADED';
                    healthData.issues = [];
                    
                    if (dbStatus !== 'connected') {
                        healthData.issues.push('Database connection issue');
                    }
                    if (mqttStatus !== 'connected') {
                        healthData.issues.push('MQTT connection issue');
                    }
                }
                
                res.status(statusCode).json(healthData);
                
            } catch (error) {
                console.error('❌ Error en health check:', error);
                res.status(500).json({
                    status: 'ERROR',
                    timestamp: new Date().toISOString(),
                    error: error.message,
                    responseTime: Date.now() - startTime
                });
            }
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
                        mqtt: {
                            'GET /api/mqtt/status': 'Estado del MQTT Listener para hardware',
                            'POST /api/mqtt/control': 'Controla MQTT Listener (body: {action: "start"|"stop"})'
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
        
        // Rutas MQTT para control del listener de hardware
        this.app.get('/api/mqtt/status', optionalAuth, (req, res) => {
            try {
                const isActive = mqttListenerService.isActive();
                const sessionState = mqttListenerService.getSessionState();
                
                res.json({
                    success: true,
                    data: {
                        mqtt_listener: {
                            active: isActive,
                            session: sessionState
                        }
                    }
                });
            } catch (error) {
                res.status(500).json({
                    success: false,
                    message: 'Error obteniendo estado del MQTT Listener',
                    error: error.message
                });
            }
        });
        
        this.app.post('/api/mqtt/control', optionalAuth, async (req, res) => {
            try {
                const { action } = req.body;
                
                if (action === 'start') {
                    const started = await mqttListenerService.startListening();
                    res.json({
                        success: started,
                        message: started ? 'MQTT Listener iniciado correctamente' : 'Error iniciando MQTT Listener',
                        data: {
                            active: started
                        }
                    });
                } else if (action === 'stop') {
                    await mqttListenerService.stopListening();
                    res.json({
                        success: true,
                        message: 'MQTT Listener detenido correctamente',
                        data: {
                            active: false
                        }
                    });
                } else {
                    res.status(400).json({
                        success: false,
                        message: 'Acción no válida. Use "start" o "stop"'
                    });
                }
            } catch (error) {
                res.status(500).json({
                    success: false,
                    message: 'Error controlando MQTT Listener',
                    error: error.message
                });
            }
        });
        
        this.app.use('/api/internal', internalRoutes); // Sin autenticación para comunicación interna
        
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
            
            // REINTEGRADO - Listener MQTT para consultas RFID del hardware
            const listenerStarted = await mqttListenerService.startListening();
            if (listenerStarted) {
                console.log('🎧 Listener MQTT para consultas RFID iniciado correctamente');
            } else {
                console.warn('⚠️ No se pudo iniciar el listener MQTT para consultas RFID');
            }
            
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
                console.log('🎧 MQTT Listener para hardware:');
                console.log(`   - GET  http://localhost:${this.port}/api/mqtt/status`);
                console.log(`   - POST http://localhost:${this.port}/api/mqtt/control`);
                console.log('📡 Respondiendo automáticamente a peticiones MQTT del hardware main_usuariosLV2.cpp');
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
            
            // Detener MQTT Listener
            await mqttListenerService.stopListening();
            
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

