/**
 * Configuración centralizada de MQTT para SMARTLABS
 * Maneja conexiones al broker MQTT con configuraciones optimizadas
 */

module.exports = {
    // Configuración del broker MQTT
    broker: {
        host: '192.168.0.100',
        port: 1883,
        protocol: 'mqtt',
        username: 'jose',
        password: 'public'
    },
    
    // Configuración del cliente
    client: {
        clientIdPrefix: 'smartlabs_iot_',
        keepalive: 60,
        reconnectPeriod: 1000,
        protocolId: 'MQIsdp',
        protocolVersion: 3,
        clean: true,
        encoding: 'utf8',
        qos: 0
    },
    
    // Configuración de WebSocket (para clientes web)
    websocket: {
        port: 8083,
        path: '/mqtt',
        url: 'ws://192.168.0.100:8083/mqtt'
    },
    
    // Tópicos del sistema
    topics: {
        // Control de acceso
        access: {
            query: '+/access_query',
            response: '+/user_name',
            traffic: '+/traffic_state'
        },
        
        // Consultas de becarios
        scholar: {
            query: '+/scholar_query',
            response: '+/scholar_response'
        },
        
        // Préstamos de equipos
        loans: {
            userQuery: '+/loan_queryu',
            equipQuery: '+/loan_querye',
            userResponse: '+/loan_user',
            equipResponse: '+/loan_equip',
            state: '+/loan_state'
        },
        
        // Datos de sensores
        sensors: {
            values: 'values',
            status: '+/status',
            command: '+/command'
        },
        
        // Estado de dispositivos
        devices: {
            status: 'smartlabs/devices/+/status',
            online: 'smartlabs/devices/+/online',
            command: 'smartlabs/devices/+/command'
        }
    },
    
    // Configuración de reconexión
    reconnection: {
        maxRetries: 10,
        retryDelay: 2000,
        exponentialBackoff: true
    }
};