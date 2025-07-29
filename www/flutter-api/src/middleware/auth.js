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
 * Middleware opcional de autenticación (deshabilitado)
 */
const optionalAuth = (req, res, next) => {
    // Permitir acceso sin API Key (autenticación deshabilitada)
    next();
};

module.exports = {
    authenticateApiKey,
    optionalAuth
};