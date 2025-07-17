/**
 * Configuración centralizada para el sistema de monitoreo de dispositivos
 * Incluye configuraciones para WebSocket, polling y UI
 */

module.exports = {
    // Configuración del servidor WebSocket
    websocket: {
        port: process.env.PORT || 3000,
        host: '0.0.0.0',
        pingInterval: 30000,
        pingTimeout: 5000,
        maxConnections: 100
    },
    
    // Configuración de monitoreo
    monitoring: {
        pollingInterval: 5000, // 5 segundos
        maxRetries: 3,
        retryDelay: 2000,
        batchSize: 50 // Máximo de dispositivos por consulta
    },
    
    // Configuración de la base de datos para monitoreo
    database: {
        queryTimeout: 10000,
        maxConnections: 5,
        idleTimeout: 300000 // 5 minutos
    },
    
    // Configuración de logs
    logging: {
        level: process.env.NODE_ENV === 'production' ? 'info' : 'debug',
        directory: './logs',
        maxFiles: 10,
        maxSize: '10m'
    },
    
    // Configuración de notificaciones
    notifications: {
        enabled: true,
        types: {
            stateChange: true,
            connectionLost: true,
            error: true
        }
    },
    
    // Configuración de la UI (para el cliente)
    ui: {
        elements: {
            deviceSelect: 'device_id',
            statusIcon: 'device-status-icon',
            statusText: 'device-status-text',
            statusIndicator: 'device-status-indicator',
            connectionIcon: 'device-connection-icon',
            connectionText: 'device-connection-text',
            lastActivity: 'device-last-activity'
        },
        
        statusClasses: {
            on: 'status-on',
            off: 'status-off',
            unknown: 'status-unknown',
            online: 'status-online',
            offline: 'status-offline'
        },
        
        statusTexts: {
            on: 'Encendido',
            off: 'Apagado',
            unknown: 'Estado desconocido',
            online: 'Conectado',
            offline: 'Desconectado',
            loading: 'Cargando...'
        }
    }
};