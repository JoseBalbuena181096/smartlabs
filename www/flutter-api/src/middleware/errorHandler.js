/**
 * Middleware de manejo de errores mejorado
 * Autor: José Ángel Balbuena Palma
 * Fecha: 2024
 */
const errorHandler = (err, req, res, next) => {
    // Calcular duración de la request
    const duration = req.startTime ? Date.now() - req.startTime : 0;
    
    // Log detallado del error
    const errorLog = {
        timestamp: new Date().toISOString(),
        error: err.message,
        stack: err.stack,
        path: req.path,
        method: req.method,
        statusCode: err.statusCode || 500,
        duration: duration,
        userAgent: req.get('User-Agent'),
        ip: req.ip,
        body: req.method !== 'GET' ? req.body : undefined,
        query: req.query,
        params: req.params
    };
    
    console.error('❌ Error capturado:', errorLog);
    
    // Ejecutar cleanup si existe
    if (req.cleanup && typeof req.cleanup === 'function') {
        try {
            req.cleanup();
        } catch (cleanupError) {
            console.error('❌ Error en cleanup durante manejo de error:', cleanupError.message);
        }
    }

    // Prevenir envío de respuesta duplicada
    if (res.headersSent) {
        console.warn('⚠️ Headers ya enviados, delegando al handler por defecto');
        return next(err);
    }

    // Error de validación Joi
    if (err.isJoi) {
        return res.status(400).json({
            success: false,
            error: 'VALIDATION_ERROR',
            message: 'Datos de entrada inválidos',
            details: err.details.map(detail => ({
                field: detail.path.join('.'),
                message: detail.message
            })),
            timestamp: new Date().toISOString(),
            duration: duration
        });
    }

    // Errores específicos de MySQL
    if (err.code) {
        switch (err.code) {
            case 'ER_NO_SUCH_TABLE':
                return res.status(500).json({
                    success: false,
                    error: 'DATABASE_ERROR',
                    message: 'Tabla de base de datos no encontrada',
                    timestamp: new Date().toISOString(),
                    duration: duration
                });
            
            case 'ER_ACCESS_DENIED_ERROR':
                return res.status(500).json({
                    success: false,
                    error: 'DATABASE_ERROR',
                    message: 'Error de acceso a la base de datos',
                    timestamp: new Date().toISOString(),
                    duration: duration
                });
            
            case 'ECONNREFUSED':
                return res.status(500).json({
                    success: false,
                    error: 'CONNECTION_ERROR',
                    message: 'No se pudo conectar al servicio',
                    timestamp: new Date().toISOString(),
                    duration: duration
                });
                
            case 'MQTT_TIMEOUT':
                return res.status(408).json({
                    success: false,
                    error: 'MQTT_TIMEOUT',
                    message: 'Timeout en operación MQTT',
                    timestamp: new Date().toISOString(),
                    duration: duration
                });
                
            case 'RESOURCE_UNAVAILABLE':
                return res.status(503).json({
                    success: false,
                    error: 'RESOURCE_UNAVAILABLE',
                    message: 'Recurso no disponible temporalmente',
                    timestamp: new Date().toISOString(),
                    duration: duration
                });
            default:
                return res.status(500).json({
                    success: false,
                    error: 'DATABASE_ERROR',
                    message: 'Error de base de datos',
                    timestamp: new Date().toISOString(),
                    duration: duration,
                    ...(process.env.NODE_ENV !== 'production' && { details: err.message })
                });
        }
    }

    // Errores de timeout
    if (err.message && err.message.includes('timeout')) {
        return res.status(408).json({
            success: false,
            error: 'REQUEST_TIMEOUT',
            message: 'La operación excedió el tiempo límite',
            timestamp: new Date().toISOString(),
            duration: duration
        });
    }

    // Error genérico del servidor
    const statusCode = err.statusCode || 500;
    const message = process.env.NODE_ENV === 'production' 
        ? 'Error interno del servidor' 
        : err.message;

    res.status(statusCode).json({
        success: false,
        error: 'INTERNAL_ERROR',
        message: message,
        timestamp: new Date().toISOString(),
        duration: duration,
        ...(process.env.NODE_ENV !== 'production' && { 
            stack: err.stack,
            details: errorLog 
        })
    });
};

/**
 * Middleware para rutas no encontradas
 */
const notFoundHandler = (req, res) => {
    res.status(404).json({
        success: false,
        message: 'Ruta no encontrada',
        error: `La ruta ${req.method} ${req.path} no existe`
    });
};

/**
 * Middleware de logging de requests
 */
const requestLogger = (req, res, next) => {
    const timestamp = new Date().toISOString();
    const method = req.method;
    const url = req.url;
    const ip = req.ip || req.connection.remoteAddress;
    
    console.log(`📝 [${timestamp}] ${method} ${url} - IP: ${ip}`);
    
    // Log del body en desarrollo (sin passwords)
    if (process.env.NODE_ENV === 'development' && req.body && Object.keys(req.body).length > 0) {
        const sanitizedBody = { ...req.body };
        if (sanitizedBody.password) sanitizedBody.password = '[HIDDEN]';
        console.log('📦 Body:', JSON.stringify(sanitizedBody, null, 2));
    }
    
    next();
};

module.exports = {
    errorHandler,
    notFoundHandler,
    requestLogger
};