/**
 * Device Status WebSocket Client
 * Cliente WebSocket para monitoreo en tiempo real del estado de dispositivos
 */

// Variables globales para WebSocket
window.deviceStatusWS = {
    connection: null,
    isConnected: false,
    reconnectAttempts: 0,
    maxReconnectAttempts: 5,
    reconnectInterval: 5000,
    subscribedDevices: [],
    lastStatus: {},
    callbacks: {
        onStatusUpdate: [],
        onConnect: [],
        onDisconnect: []
    }
};

/**
 * Inicializa la conexión WebSocket al servidor de estado de dispositivos
 * @param {string} serverUrl - URL del servidor WebSocket (opcional)
 */
function initDeviceStatusWS(serverUrl) {
    // URL por defecto usando configuración automática
    const wsUrl = serverUrl || (window.DeviceStatusConfig ? window.DeviceStatusConfig.websocket.getUrl() : 'ws://localhost:8086');
    
    console.log('Inicializando conexión WebSocket a:', wsUrl);
    
    // Cerrar conexión existente si hay
    if (window.deviceStatusWS.connection) {
        window.deviceStatusWS.connection.close();
    }
    
    try {
        // Crear nueva conexión
        window.deviceStatusWS.connection = new WebSocket(wsUrl);
        
        // Configurar eventos
        window.deviceStatusWS.connection.onopen = function() {
            console.log('Conexión WebSocket establecida');
            window.deviceStatusWS.isConnected = true;
            window.deviceStatusWS.reconnectAttempts = 0;
            
            // Suscribirse a dispositivos
            subscribeToDevices();
            
            // Ejecutar callbacks de conexión
            window.deviceStatusWS.callbacks.onConnect.forEach(callback => {
                try {
                    callback();
                } catch (e) {
                    console.error('Error en callback onConnect:', e);
                }
            });
        };
        
        window.deviceStatusWS.connection.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                console.log('Mensaje WebSocket recibido:', data);
                
                // Procesar mensaje según tipo
                if (data.type === 'device_status') {
                    processDeviceStatus(data.device, data.data);
                } else if (data.type === 'welcome') {
                    console.log('Bienvenida del servidor:', data.message);
                    console.log('Dispositivos disponibles:', data.devices);
                }
            } catch (e) {
                console.error('Error procesando mensaje WebSocket:', e);
            }
        };
        
        window.deviceStatusWS.connection.onclose = function() {
            console.log('Conexión WebSocket cerrada');
            window.deviceStatusWS.isConnected = false;
            
            // Ejecutar callbacks de desconexión
            window.deviceStatusWS.callbacks.onDisconnect.forEach(callback => {
                try {
                    callback();
                } catch (e) {
                    console.error('Error en callback onDisconnect:', e);
                }
            });
            
            // Intentar reconectar
            if (window.deviceStatusWS.reconnectAttempts < window.deviceStatusWS.maxReconnectAttempts) {
                window.deviceStatusWS.reconnectAttempts++;
                console.log(`Reintentando conexión (${window.deviceStatusWS.reconnectAttempts}/${window.deviceStatusWS.maxReconnectAttempts})...`);
                
                setTimeout(function() {
                    // Usar configuración automática para la reconexión
                    const reconnectUrl = window.DeviceStatusConfig ? window.DeviceStatusConfig.websocket.getUrl() : 'ws://localhost:8086';
                    initDeviceStatusWS(reconnectUrl);
                }, window.deviceStatusWS.reconnectInterval);
            } else {
                console.error('Máximo número de intentos de reconexión alcanzado');
                
                // Comentado: No mostrar notificación automática de error de conexión
                // if (typeof showNotification === 'function') {
                //     showNotification('No se pudo establecer conexión con el servidor de estado', 'error');
                // }
            }
        };
        
        window.deviceStatusWS.connection.onerror = function(error) {
            console.error('Error en conexión WebSocket:', error);
            
            // Comentado: No mostrar notificación automática de error de conexión
            // if (typeof showNotification === 'function') {
            //     showNotification('Error en la conexión con el servidor de estado', 'error');
            // }
        };
        
    } catch (e) {
        console.error('Error inicializando WebSocket:', e);
        
        // Comentado: No mostrar notificación automática de error de inicialización
        // if (typeof showNotification === 'function') {
        //     showNotification('Error inicializando conexión WebSocket', 'error');
        // }
    }
}

/**
 * Suscribe al cliente a los dispositivos especificados
 * @param {Array} deviceIds - IDs de los dispositivos a suscribir (opcional)
 */
function subscribeToDevices(deviceIds) {
    // Si se proporcionan nuevos dispositivos, actualizar lista
    if (deviceIds) {
        window.deviceStatusWS.subscribedDevices = deviceIds;
    }
    
    // Verificar conexión
    if (!window.deviceStatusWS.isConnected || !window.deviceStatusWS.connection) {
        console.warn('No hay conexión WebSocket activa');
        return;
    }
    
    // Obtener dispositivo seleccionado si no hay lista
    if (!window.deviceStatusWS.subscribedDevices.length) {
        const selectedDevice = document.getElementById('device_id')?.value;
        if (selectedDevice) {
            window.deviceStatusWS.subscribedDevices = [selectedDevice];
        } else {
            window.deviceStatusWS.subscribedDevices = ['all']; // Suscribirse a todos
        }
    }
    
    // Enviar mensaje de suscripción
    window.deviceStatusWS.connection.send(JSON.stringify({
        type: 'subscribe',
        devices: window.deviceStatusWS.subscribedDevices
    }));
    
    console.log('Suscrito a dispositivos:', window.deviceStatusWS.subscribedDevices);
}

/**
 * Solicita el estado actual de un dispositivo específico
 * @param {string} deviceId - ID del dispositivo
 */
function requestDeviceStatus(deviceId) {
    // Verificar conexión
    if (!window.deviceStatusWS.isConnected || !window.deviceStatusWS.connection) {
        console.warn('No hay conexión WebSocket activa');
        return;
    }
    
    // Enviar solicitud
    window.deviceStatusWS.connection.send(JSON.stringify({
        type: 'get_status',
        device: deviceId
    }));
    
    console.log('Solicitando estado del dispositivo:', deviceId);
}

/**
 * Procesa la actualización de estado de un dispositivo
 * @param {string} deviceId - ID del dispositivo
 * @param {Object} status - Datos del estado del dispositivo
 */
function processDeviceStatus(deviceId, status) {
    // Guardar último estado
    window.deviceStatusWS.lastStatus[deviceId] = status;
    
    // Verificar si es el dispositivo seleccionado actualmente
    const selectedDevice = document.getElementById('device_id')?.value;
    
    if (selectedDevice === deviceId) {
        console.log('Actualizando UI para dispositivo seleccionado:', deviceId);
        
        // Actualizar UI si está disponible la función
        if (typeof updateStatusDisplay === 'function') {
            updateStatusDisplay(status);
        } else if (typeof updateDeviceStatusUI === 'function') {
            updateDeviceStatusUI(status);
        }
    }
    
    // Ejecutar callbacks de actualización
    window.deviceStatusWS.callbacks.onStatusUpdate.forEach(callback => {
        try {
            callback(deviceId, status);
        } catch (e) {
            console.error('Error en callback onStatusUpdate:', e);
        }
    });
}

/**
 * Registra un callback para eventos de estado
 * @param {string} event - Tipo de evento ('onStatusUpdate', 'onConnect', 'onDisconnect')
 * @param {Function} callback - Función a ejecutar
 */
function onDeviceStatusEvent(event, callback) {
    if (typeof callback !== 'function') {
        console.error('El callback debe ser una función');
        return;
    }
    
    if (window.deviceStatusWS.callbacks[event]) {
        window.deviceStatusWS.callbacks[event].push(callback);
    } else {
        console.error('Evento no soportado:', event);
    }
}

// Hacer funciones disponibles globalmente
window.initDeviceStatusWS = initDeviceStatusWS;
window.subscribeToDevices = subscribeToDevices;
window.requestDeviceStatus = requestDeviceStatus;
window.onDeviceStatusEvent = onDeviceStatusEvent;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando cliente WebSocket para estado de dispositivos...');
    
    // Configurar evento de cambio de dispositivo
    const deviceSelect = document.getElementById('device_id');
    if (deviceSelect) {
        deviceSelect.addEventListener('change', function() {
            const deviceId = this.value;
            
            // Actualizar suscripción
            subscribeToDevices([deviceId]);
            
            // Solicitar estado actual
            requestDeviceStatus(deviceId);
        });
    }
    
    // NO inicializar WebSocket aquí - se hará desde dashboard/index.php
    // usando la configuración correcta
    console.log('WebSocket se inicializará desde la configuración del dashboard');
});

// Exportar funciones para uso global
window.DeviceStatusWebSocket = {
    init: initDeviceStatusWS,
    subscribe: subscribeToDevices,
    request: requestDeviceStatus,
    on: onDeviceStatusEvent,
    get isConnected() { return window.deviceStatusWS.isConnected; },
    get lastStatus() { return window.deviceStatusWS.lastStatus; }
};

/**
 * Integración MQTT para sincronizar estado del dispositivo
 */
window.deviceStatusMQTT = {
    client: null,
    isConnected: false,
    subscribedTopics: [],
    statusTopics: {
        state: '/status/state',    // Tópico para estado on/off
        online: '/status/online',  // Tópico para estado online/offline
        activity: '/status/activity' // Tópico para última actividad
    }
};

/**
 * Inicializa la conexión MQTT para monitoreo de estado
 * @param {string} brokerUrl - URL del broker MQTT (opcional)
 */
function initDeviceStatusMQTT(brokerUrl) {
    // Configuración dinámica de URL MQTT si no se especifica
    let mqttUrl = brokerUrl;
    
    if (!mqttUrl) {
        const hostname = window.location.hostname;
        console.log('🔧 Detectando configuración MQTT para hostname:', hostname);
        
        // Determinar URL correcta basada en el hostname
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            // Acceso desde localhost - usar localhost
            mqttUrl = 'ws://localhost:8083/mqtt';
            console.log('📡 Configuración MQTT: Acceso local detectado');
        } else {
            // Para acceso desde red (clientes), siempre usar la IP del servidor
            mqttUrl = 'ws://192.168.0.100:8083/mqtt';
            console.log('📡 Configuración MQTT: Acceso desde red local/servidor detectado');
        }
    }
    
    console.log('Inicializando conexión MQTT para estado de dispositivos:', mqttUrl);
    
    try {
        // Crear cliente MQTT con credenciales
        window.deviceStatusMQTT.client = mqtt.connect(mqttUrl, {
            clientId: 'device-status-' + Math.random().toString(16).substr(2, 8),
            username: 'jose',
            password: 'public',
            clean: true,
            reconnectPeriod: 5000,
            keepalive: 60
        });
        
        // Configurar eventos
        window.deviceStatusMQTT.client.on('connect', function() {
            console.log('✅ MQTT conectado para monitoreo de estado');
            window.deviceStatusMQTT.isConnected = true;
            
            // Suscribirse a tópicos de estado si hay dispositivo seleccionado
            if (window.selectedDevice) {
                subscribeToDeviceStatusTopics(window.selectedDevice);
            }
        });
        
        window.deviceStatusMQTT.client.on('message', function(topic, message) {
            try {
                const messageStr = message.toString();
                console.log('📡 Mensaje MQTT recibido:', topic, messageStr);
                
                // Procesar mensaje según el tópico
                processDeviceStatusMQTTMessage(topic, messageStr);
                
            } catch (e) {
                console.error('Error procesando mensaje MQTT:', e);
            }
        });
        
        window.deviceStatusMQTT.client.on('error', function(error) {
            console.error('❌ Error MQTT:', error);
            window.deviceStatusMQTT.isConnected = false;
        });
        
        window.deviceStatusMQTT.client.on('close', function() {
            console.log('⚠️ Conexión MQTT cerrada');
            window.deviceStatusMQTT.isConnected = false;
        });
        
    } catch (e) {
        console.error('Error inicializando MQTT:', e);
    }
}

/**
 * Suscribirse a tópicos de estado de un dispositivo específico
 * @param {string} deviceId - ID del dispositivo
 */
function subscribeToDeviceStatusTopics(deviceId) {
    if (!window.deviceStatusMQTT.isConnected || !window.deviceStatusMQTT.client) {
        console.warn('MQTT no está conectado');
        return;
    }
    
    // Limpiar suscripciones anteriores
    window.deviceStatusMQTT.subscribedTopics.forEach(topic => {
        window.deviceStatusMQTT.client.unsubscribe(topic);
    });
    window.deviceStatusMQTT.subscribedTopics = [];
    
    // Suscribirse a tópicos de estado del dispositivo
    Object.values(window.deviceStatusMQTT.statusTopics).forEach(topicSuffix => {
        const fullTopic = deviceId + topicSuffix;
        
        window.deviceStatusMQTT.client.subscribe(fullTopic, function(err) {
            if (err) {
                console.error('Error suscribiéndose al tópico:', fullTopic, err);
            } else {
                console.log('✅ Suscrito al tópico:', fullTopic);
                window.deviceStatusMQTT.subscribedTopics.push(fullTopic);
            }
        });
    });
    
    // También suscribirse al tópico de comando para escuchar respuestas
    const commandTopic = deviceId + '/command';
    window.deviceStatusMQTT.client.subscribe(commandTopic, function(err) {
        if (err) {
            console.error('Error suscribiéndose al tópico de comando:', commandTopic, err);
        } else {
            console.log('✅ Suscrito al tópico de comando:', commandTopic);
            window.deviceStatusMQTT.subscribedTopics.push(commandTopic);
        }
    });
}

/**
 * Procesa mensajes MQTT relacionados con el estado del dispositivo
 * @param {string} topic - Tópico del mensaje
 * @param {string} message - Contenido del mensaje
 */
function processDeviceStatusMQTTMessage(topic, message) {
    // Extraer ID del dispositivo del tópico
    const deviceId = topic.split('/')[0];
    
    // Determinar tipo de mensaje por el sufijo del tópico
    let statusUpdate = {};
    
    if (topic.endsWith('/status/state')) {
        // Mensaje de estado (on/off)
        statusUpdate.state = message.toLowerCase() === 'on' ? 'on' : 'off';
        statusUpdate.device = deviceId;
        statusUpdate.timestamp = new Date().toISOString();
        
    } else if (topic.endsWith('/status/online')) {
        // Mensaje de conexión (online/offline)
        statusUpdate.online = message.toLowerCase() === 'online';
        statusUpdate.device = deviceId;
        statusUpdate.timestamp = new Date().toISOString();
        
    } else if (topic.endsWith('/status/activity')) {
        // Mensaje de actividad (timestamp)
        statusUpdate.last_activity = message;
        statusUpdate.device = deviceId;
        statusUpdate.timestamp = new Date().toISOString();
        
    } else if (topic.endsWith('/command')) {
        // Respuesta a comando enviado
        console.log('📡 Respuesta a comando:', deviceId, message);
        
        // Actualizar estado basado en el comando ejecutado
        if (message === 'open') {
            statusUpdate.state = 'on';
        } else if (message === 'close') {
            statusUpdate.state = 'off';
        }
        
        statusUpdate.device = deviceId;
        statusUpdate.timestamp = new Date().toISOString();
    }
    
    // Si hay actualización de estado, procesarla
    if (Object.keys(statusUpdate).length > 0) {
        // Actualizar estado local
        if (!window.deviceStatusWS.lastStatus[deviceId]) {
            window.deviceStatusWS.lastStatus[deviceId] = {};
        }
        
        Object.assign(window.deviceStatusWS.lastStatus[deviceId], statusUpdate);
        
        // Actualizar UI si es el dispositivo seleccionado
        if (deviceId === window.selectedDevice) {
            console.log('📡 Actualizando UI por mensaje MQTT:', statusUpdate);
            
            if (typeof updateDeviceStatusUI === 'function') {
                updateDeviceStatusUI(statusUpdate);
            }
        }
        
        // Ejecutar callbacks
        window.deviceStatusWS.callbacks.onStatusUpdate.forEach(callback => {
            try {
                callback(deviceId, statusUpdate);
            } catch (e) {
                console.error('Error en callback MQTT onStatusUpdate:', e);
            }
        });
    }
}

// Exportar funciones MQTT
window.initDeviceStatusMQTT = initDeviceStatusMQTT;
window.subscribeToDeviceStatusTopics = subscribeToDeviceStatusTopics;

console.log('🔧 device-status-websocket.js cargado completamente');