/**
 * Middleware de timeout para prevenir requests colgadas
 * Autor: José Ángel Balbuena Palma
 * Fecha: 2024
 */

class TimeoutMiddleware {
    /**
     * Crea middleware de timeout con configuración personalizable
     * @param {number} timeout - Tiempo límite en milisegundos
     * @param {string} message - Mensaje de error personalizado
     */
    static create(timeout = 30000, message = 'Request timeout') {
        return (req, res, next) => {
            // Configurar timeout para la request
            const timeoutId = setTimeout(() => {
                if (!res.headersSent) {
                    console.warn(`⏰ Timeout en ${req.method} ${req.path} después de ${timeout}ms`);
                    
                    res.status(408).json({
                        success: false,
                        error: 'REQUEST_TIMEOUT',
                        message: message,
                        timestamp: new Date().toISOString(),
                        path: req.path,
                        method: req.method,
                        timeout: timeout
                    });
                }
            }, timeout);

            // Limpiar timeout cuando la response termine
            const originalEnd = res.end;
            res.end = function(...args) {
                clearTimeout(timeoutId);
                originalEnd.apply(this, args);
            };

            // Limpiar timeout si hay error
            res.on('error', () => {
                clearTimeout(timeoutId);
            });

            // Agregar información de timeout a la request
            req.timeout = timeout;
            req.startTime = Date.now();

            next();
        };
    }

    /**
     * Middleware específico para operaciones de base de datos
     */
    static database(timeout = 15000) {
        return TimeoutMiddleware.create(timeout, 'Database operation timeout');
    }

    /**
     * Middleware específico para operaciones MQTT
     */
    static mqtt(timeout = 10000) {
        return TimeoutMiddleware.create(timeout, 'MQTT operation timeout');
    }

    /**
     * Middleware específico para operaciones de autenticación
     */
    static auth(timeout = 5000) {
        return TimeoutMiddleware.create(timeout, 'Authentication timeout');
    }

    /**
     * Middleware para requests generales de API
     */
    static api(timeout = 30000) {
        return TimeoutMiddleware.create(timeout, 'API request timeout');
    }

    /**
     * Middleware para operaciones de archivo/upload
     */
    static upload(timeout = 60000) {
        return TimeoutMiddleware.create(timeout, 'File upload timeout');
    }
}

module.exports = TimeoutMiddleware;