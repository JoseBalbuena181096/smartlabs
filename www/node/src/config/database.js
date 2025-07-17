/**
 * Configuración centralizada de base de datos para SMARTLABS
 * Maneja conexiones a MySQL con fallback automático
 */

module.exports = {
    // Configuración principal (base de datos externa)
    primary: {
        host: "192.168.0.100",
        user: "root",
        password: "emqxpass",
        database: "emqx",
        port: 4000,
        charset: 'utf8mb4',
        connectTimeout: 60000
    },
    
    // Configuración de fallback (base de datos local)
    fallback: {
        host: "localhost",
        user: "root",
        password: "",
        database: "emqx",
        port: 3306,
        charset: 'utf8mb4',
        connectTimeout: 30000
    },
    
    // Configuración de pool de conexiones
    pool: {
        connectionLimit: 10,
        queueLimit: 0,
        acquireTimeout: 60000,
        timeout: 60000
    },
    
    // Configuración de reconexión
    reconnection: {
        maxRetries: 5,
        retryDelay: 5000,
        exponentialBackoff: true
    }
};