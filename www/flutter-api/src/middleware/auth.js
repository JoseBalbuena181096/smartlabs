require('dotenv').config();

/**
 * Middleware de autenticación simple por API Key
 */
const authenticateApiKey = (req, res, next) => {
    const apiKey = req.headers['x-api-key'] || req.query.api_key;
    
    if (!apiKey) {
        return res.status(401).json({
            success: false,
            message: 'API Key requerida',
            error: 'Falta header x-api-key o query parameter api_key'
        });
    }
    
    if (apiKey !== process.env.API_KEY) {
        return res.status(403).json({
            success: false,
            message: 'API Key inválida',
            error: 'La API Key proporcionada no es válida'
        });
    }
    
    next();
};

/**
 * Middleware opcional de autenticación (para desarrollo)
 */
const optionalAuth = (req, res, next) => {
    if (process.env.NODE_ENV === 'development') {
        // En desarrollo, permitir acceso sin API Key
        next();
    } else {
        // En producción, requerir API Key
        authenticateApiKey(req, res, next);
    }
};

module.exports = {
    authenticateApiKey,
    optionalAuth
};