/**
 * Configuración del Sistema de Estado de Dispositivos - SMARTLABS
 * Centraliza la configuración para WebSocket, MQTT y monitoreo de estado
 */

window.DeviceStatusConfig = {
    // Configuración WebSocket
    websocket: {
        enabled: true,
        url: null, // Se determinará automáticamente
        reconnectAttempts: 5,
        reconnectInterval: 5000,
        
        // Configuración automática de URL
        getUrl: function() {
            if (this.url) return this.url;
            
            // Forzar localhost para WebSocket durante desarrollo
            return 'ws://localhost:3000';
        }
    },
    
    // Configuración MQTT
    mqtt: {
        enabled: true,
        url: null, // Se determinará automáticamente
        clientIdPrefix: 'device-status-',
        reconnectPeriod: 5000,
        keepalive: 60,
        
        // Configuración automática de URL
        getUrl: function() {
            if (this.url) return this.url;
            
            // Determinar protocolo y host correctos
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            let hostname = window.location.hostname;
            
            // Si estamos en la IP externa, usar localhost para MQTT
            if (hostname === '192.168.0.100') {
                hostname = 'localhost';
            }
            
            return `${protocol}//${hostname}:8083/mqtt`;
        },
        
        // Tópicos de estado
        statusTopics: {
            state: '/status/state',       // Estado on/off del dispositivo
            online: '/status/online',     // Estado online/offline
            activity: '/status/activity', // Última actividad
            command: '/command'           // Comandos enviados/recibidos
        }
    },
    
    // Configuración del monitor de estado (polling)
    monitor: {
        enabled: true,
        pollingInterval: 10000, // 10 segundos
        maxRetries: 3,
        
        // Endpoints de la API
        endpoints: {
            status: '/Dashboard/status',
            command: '/Dashboard/command',
            stats: '/Dashboard/stats'
        }
    },
    
    // Configuración de la UI
    ui: {
        // Elementos DOM
        elements: {
            deviceSelect: 'device_id',
            statusIcon: 'device-status-icon',
            statusText: 'device-status-text',
            statusIndicator: 'device-status-indicator',
            connectionIcon: 'device-connection-icon',
            connectionText: 'device-connection-text',
            connectionIndicator: 'device-connection-indicator',
            lastActivity: 'device-last-activity'
        },
        
        // Clases CSS para diferentes estados
        statusClasses: {
            on: 'status-on',
            off: 'status-off',
            unknown: 'status-unknown',
            online: 'status-online',
            offline: 'status-offline'
        },
        
        // Textos para mostrar en la UI
        statusTexts: {
            on: 'Encendido',
            off: 'Apagado',
            unknown: 'Estado desconocido',
            online: 'Conectado',
            offline: 'Desconectado',
            loading: 'Cargando...'
        }
    },
    
    // Configuración de logging
    logging: {
        enabled: true,
        level: 'info', // debug, info, warn, error
        prefix: '🔧 SMARTLABS Device Status'
    },
    
    // Configuración de notificaciones
    notifications: {
        enabled: true,
        duration: 3000, // 3 segundos
        showOnStateChange: true,
        showOnConnection: true
    }
};

/**
 * Función para inicializar toda la configuración
 */
window.DeviceStatusConfig.init = function() {
    console.log(this.logging.prefix + ': Inicializando configuración del sistema');
    
    // Validar elementos DOM
    Object.keys(this.ui.elements).forEach(key => {
        const elementId = this.ui.elements[key];
        const element = document.getElementById(elementId);
        
        if (!element) {
            console.warn(`⚠️ Elemento DOM no encontrado: ${elementId}`);
        } else {
            console.log(`✓ Elemento DOM encontrado: ${elementId}`);
        }
    });
    
    // Validar URLs
    console.log('✓ WebSocket URL:', this.websocket.getUrl());
    console.log('✓ MQTT URL:', this.mqtt.getUrl());
    
    console.log(this.logging.prefix + ': Configuración inicializada');
};

/**
 * Función para obtener configuración específica
 */
window.DeviceStatusConfig.get = function(path) {
    const keys = path.split('.');
    let current = this;
    
    for (const key of keys) {
        if (current[key] === undefined) {
            return undefined;
        }
        current = current[key];
    }
    
    return current;
};

/**
 * Función para establecer configuración específica
 */
window.DeviceStatusConfig.set = function(path, value) {
    const keys = path.split('.');
    const lastKey = keys.pop();
    let current = this;
    
    for (const key of keys) {
        if (current[key] === undefined) {
            current[key] = {};
        }
        current = current[key];
    }
    
    current[lastKey] = value;
};

/**
 * Función para verificar si una funcionalidad está habilitada
 */
window.DeviceStatusConfig.isEnabled = function(feature) {
    return this.get(feature + '.enabled') === true;
};

// Inicializar configuración cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.DeviceStatusConfig.init();
});

console.log('🔧 device-status-config.js cargado completamente'); 