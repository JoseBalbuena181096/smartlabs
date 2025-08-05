/**
 * Wrapper para manejo asíncrono de errores
 * Previene promesas no manejadas que pueden colgar la aplicación
 * Autor: José Ángel Balbuena Palma
 * Fecha: 2024
 */

/**
 * Wrapper para funciones asíncronas que maneja automáticamente los errores
 * @param {Function} fn - Función asíncrona a envolver
 * @returns {Function} - Función envuelta con manejo de errores
 */
const asyncHandler = (fn) => {
    return (req, res, next) => {
        // Agregar información de timing
        req.startTime = req.startTime || Date.now();
        
        // Ejecutar la función y manejar errores
        Promise.resolve(fn(req, res, next))
            .catch((error) => {
                // Log del error con contexto
                console.error('❌ Error en handler asíncrono:', {
                    error: error.message,
                    stack: error.stack,
                    path: req.path,
                    method: req.method,
                    timestamp: new Date().toISOString(),
                    duration: Date.now() - req.startTime,
                    userAgent: req.get('User-Agent'),
                    ip: req.ip
                });
                
                // Pasar el error al middleware de manejo de errores
                next(error);
            })
            .finally(() => {
                // Cleanup de recursos si es necesario
                if (req.cleanup && typeof req.cleanup === 'function') {
                    try {
                        req.cleanup();
                    } catch (cleanupError) {
                        console.error('❌ Error en cleanup:', cleanupError.message);
                    }
                }
            });
    };
};

/**
 * Wrapper específico para operaciones de base de datos
 * Incluye manejo automático de conexiones
 */
const dbAsyncHandler = (fn) => {
    return asyncHandler(async (req, res, next) => {
        let connection = null;
        
        try {
            // Agregar función de cleanup para la conexión
            req.cleanup = () => {
                if (connection && typeof connection.release === 'function') {
                    connection.release();
                    console.log('🔌 Conexión DB liberada automáticamente');
                }
            };
            
            await fn(req, res, next);
        } catch (error) {
            // Asegurar que la conexión se libere en caso de error
            if (connection && typeof connection.release === 'function') {
                connection.release();
            }
            throw error;
        }
    });
};

/**
 * Wrapper específico para operaciones MQTT
 * Incluye timeout automático para operaciones MQTT
 */
const mqttAsyncHandler = (fn, timeout = 10000) => {
    return asyncHandler(async (req, res, next) => {
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => {
                reject(new Error(`MQTT operation timeout after ${timeout}ms`));
            }, timeout);
        });
        
        try {
            await Promise.race([
                fn(req, res, next),
                timeoutPromise
            ]);
        } catch (error) {
            if (error.message.includes('timeout')) {
                error.statusCode = 408;
                error.code = 'MQTT_TIMEOUT';
            }
            throw error;
        }
    });
};

/**
 * Wrapper para operaciones que requieren validación de recursos
 */
const resourceAsyncHandler = (fn, resources = []) => {
    return asyncHandler(async (req, res, next) => {
        // Verificar disponibilidad de recursos antes de ejecutar
        for (const resource of resources) {
            if (!resource.isAvailable()) {
                const error = new Error(`Resource ${resource.name} is not available`);
                error.statusCode = 503;
                error.code = 'RESOURCE_UNAVAILABLE';
                throw error;
            }
        }
        
        await fn(req, res, next);
    });
};

/**
 * Wrapper con retry automático para operaciones que pueden fallar temporalmente
 */
const retryAsyncHandler = (fn, maxRetries = 3, delay = 1000) => {
    return asyncHandler(async (req, res, next) => {
        let lastError;
        
        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                await fn(req, res, next);
                return; // Éxito, salir del loop
            } catch (error) {
                lastError = error;
                
                // No reintentar para ciertos tipos de errores
                if (error.statusCode === 400 || error.statusCode === 401 || error.statusCode === 403) {
                    throw error;
                }
                
                if (attempt < maxRetries) {
                    console.warn(`⚠️ Intento ${attempt} falló, reintentando en ${delay}ms:`, error.message);
                    await new Promise(resolve => setTimeout(resolve, delay));
                    delay *= 2; // Backoff exponencial
                } else {
                    console.error(`❌ Todos los intentos fallaron después de ${maxRetries} intentos`);
                }
            }
        }
        
        throw lastError;
    });
};

module.exports = {
    asyncHandler,
    dbAsyncHandler,
    mqttAsyncHandler,
    resourceAsyncHandler,
    retryAsyncHandler
};