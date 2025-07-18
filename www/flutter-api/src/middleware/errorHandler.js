/**
 * Middleware de manejo de errores global
 */
const errorHandler = (err, req, res, next) => {
    console.error('❌ Error no manejado:', err);
    
    // Error de validación de Joi
    if (err.isJoi) {
        return res.status(400).json({
            success: false,
            message: 'Datos de entrada inválidos',
            error: err.details[0].message
        });
    }
    
    // Error de base de datos MySQL
    if (err.code) {
        switch (err.code) {
            case 'ER_NO_SUCH_TABLE':
                return res.status(500).json({
                    success: false,
                    message: 'Error de configuración de base de datos',
                    error: 'Tabla no encontrada'
                });
            case 'ER_ACCESS_DENIED_ERROR':
                return res.status(500).json({
                    success: false,
                    message: 'Error de conexión a base de datos',
                    error: 'Acceso denegado'
                });
            case 'ECONNREFUSED':
                return res.status(500).json({
                    success: false,
                    message: 'Error de conexión',
                    error: 'No se pudo conectar al servidor de base de datos'
                });
            default:
                return res.status(500).json({
                    success: false,
                    message: 'Error de base de datos',
                    error: process.env.NODE_ENV === 'development' ? err.message : 'Error interno'
                });
        }
    }
    
    // Error genérico
    res.status(500).json({
        success: false,
        message: 'Error interno del servidor',
        error: process.env.NODE_ENV === 'development' ? err.message : 'Error interno'
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