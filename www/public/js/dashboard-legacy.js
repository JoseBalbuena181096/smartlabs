/**
 * Funcionalidades del Dashboard para SMARTLABS
 * Replica las funcionalidades MQTT y AJAX del archivo legacy dashboard.php
 */

// Variables globales para MQTT - evitar conflictos
window.dashboardMqttClient = window.dashboardMqttClient || null;
window.dashboardMqttConnected = window.dashboardMqttConnected || false;
window.dashboardAudioNotification = window.dashboardAudioNotification || null;

// Inicializar audio de notificación
function initAudio() {
    try {
        window.dashboardAudioNotification = new Audio('/public/audio/audio.mp3');
        window.dashboardAudioNotification.preload = 'auto';
        console.log('Audio de notificación inicializado');
    } catch (error) {
        console.log('No se pudo cargar el audio:', error);
    }
}

// Hacer funciones disponibles globalmente
window.initAudio = initAudio;

// Función para generar cadena aleatoria (del legacy)
function generarCadenaAleatoria(longitud) {
    const caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    let cadenaAleatoria = '';
    for (let i = 0; i < longitud; i++) {
        const indiceAleatorio = Math.floor(Math.random() * caracteres.length);
        cadenaAleatoria += caracteres.charAt(indiceAleatorio);
    }
    return cadenaAleatoria;
}

// Hacer funciones disponibles globalmente
window.generarCadenaAleatoria = generarCadenaAleatoria;

// Función para controlar dispositivos (del legacy)
function command(action) {
    const deviceSerie = document.getElementById("device_id").value;
    console.log('Controlando dispositivo:', deviceSerie, 'Acción:', action);
    
    if (!deviceSerie) {
        alert('Por favor seleccione un dispositivo');
        return;
    }
    
    if (!window.dashboardMqttConnected || !window.dashboardMqttClient) {
        alert('MQTT no está conectado. Intentando conectar...');
        initializeMQTT();
        return;
    }
    
    if (action === "open") {
        window.dashboardMqttClient.publish(deviceSerie + "/command", 'open', (error) => {
            console.log(error || 'Abriendo dispositivo!!!');
            if (error) {
                alert('Error enviando comando: ' + error);
            } else {
                showNotification('Comando de encendido enviado al dispositivo ' + deviceSerie, 'success');
            }
        });
    } else if (action === "close") {
        window.dashboardMqttClient.publish(deviceSerie + "/command", 'close', (error) => {
            console.log(error || 'Cerrando dispositivo!!!');
            if (error) {
                alert('Error enviando comando: ' + error);
            } else {
                showNotification('Comando de apagado enviado al dispositivo ' + deviceSerie, 'success');
            }
        });
    }
}

// Hacer la función command disponible globalmente
window.command = command;

// También hacer disponible sin window namespace para onclick
globalThis.command = command;

// Verificar que esté disponible globalmente
if (typeof window.command === 'function') {
    console.log('✅ window.command disponible globalmente');
} else {
    console.error('❌ window.command NO disponible globalmente');
}

if (typeof globalThis.command === 'function') {
    console.log('✅ globalThis.command disponible globalmente');
} else {
    console.error('❌ globalThis.command NO disponible globalmente');
}

// Función para procesar mensajes MQTT (del legacy)
function process_msg(topic, message) {
    const msg = message.toString();
    const splittedTopic = topic.split("/");
    const serialNumber = splittedTopic[0];
    const query = splittedTopic[1];
    const deviceSerie = document.getElementById("device_id").value;
    
    console.log('Procesando mensaje MQTT:', topic, '->', msg);
    
    // Actualizar temperatura
    if (query === "temp" && deviceSerie === serialNumber) {
        const tempElement = document.getElementById("display_temp1");
        if (tempElement) {
            tempElement.textContent = msg;
            console.log('Temperatura actualizada de:', serialNumber, '=', msg);
        }
    }

    // Procesar notificaciones de acceso (como en legacy)
    if ((query === "access_query" || query === "scholar_query") && deviceSerie === serialNumber) {
        console.log('Acceso detectado en:', serialNumber);
        console.log('Mensaje:', msg);
        
        const accessElement = document.getElementById("display_new_access");
        if (accessElement) {
            accessElement.innerHTML = "Nuevo acceso: " + msg;
        }
        
        // Reproducir audio de notificación
        if (window.dashboardAudioNotification) {
            window.dashboardAudioNotification.play().catch(e => console.log('Error reproduciendo audio:', e));
        }
        
        // Mostrar notificación visual
        showNotification('Nuevo acceso detectado: ' + msg, 'info');
        
        // Limpiar mensaje después de 3 segundos (como en legacy)
        setTimeout(function() {
            if (accessElement) {
                accessElement.innerHTML = "";
            }
        }, 3000);
        
        // Recargar tabla de tráfico después de 2 segundos para mostrar nuevo acceso
        setTimeout(function() {
            if (window.location.href.includes('serie_device=')) {
                window.location.reload();
            }
        }, 2000);
    }
}

// Hacer funciones disponibles globalmente
window.process_msg = process_msg;

// Función para mostrar notificaciones
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '70px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.maxWidth = '400px';
    
    notification.innerHTML = `
        <strong>${type === 'success' ? 'Éxito' : type === 'error' ? 'Error' : 'Información'}:</strong> ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// Hacer funciones disponibles globalmente
window.showNotification = showNotification;

// Función para inicializar MQTT (del legacy)
function initializeMQTT() {
    console.log('Inicializando conexión MQTT...');
    updateMqttStatus(false, 'Conectando...');
    
    const cadenaAleatoria = generarCadenaAleatoria(6);
    const options = {
        clientId: 'iotmc' + cadenaAleatoria,
        username: 'jose',
        password: 'public',
        keepalive: 60,
        clean: true,
        connectTimeout: 4000,
        protocolId: 'MQTT',
        protocolVersion: 4,
        reconnectPeriod: 1000,
        will: {
            topic: 'smartlabs/dashboard/lwt',
            payload: 'offline',
            qos: 0,
            retain: false
        }
    };

    // Conexión MQTT WebSocket (como en legacy)
    const WebSocket_URL = 'wss://192.168.0.100:8074/mqtt';
    
    try {
        window.dashboardMqttClient = mqtt.connect(WebSocket_URL, options);

        window.dashboardMqttClient.on('connect', () => {
            console.log('MQTT conectado por WebSocket! Éxito!');
            window.dashboardMqttConnected = true;
            updateMqttStatus(true);
            
            // Suscribirse a todos los dispositivos del usuario (como en legacy)
            const devices = window.userDevices || [];
            devices.forEach(device => {
                window.dashboardMqttClient.subscribe(device.devices_serie + '/access_query', { qos: 0 });
                window.dashboardMqttClient.subscribe(device.devices_serie + '/scholar_query', { qos: 0 });
                window.dashboardMqttClient.subscribe(device.devices_serie + '/temp', { qos: 0 });
                console.log('Suscrito a dispositivo:', device.devices_serie);
            });
            
            // Mensaje de confirmación (como en legacy)
            window.dashboardMqttClient.publish('fabrica', 'Dashboard MVC conectado exitosamente', (error) => {
                console.log(error || 'Mensaje de confirmación enviado');
            });
            
            showNotification('Conexión MQTT establecida exitosamente', 'success');
        });

        window.dashboardMqttClient.on('message', (topic, message) => {
            console.log('Mensaje MQTT recibido:', topic, '->', message.toString());
            process_msg(topic, message);
        });

        window.dashboardMqttClient.on('reconnect', () => {
            console.log('Intentando reconectar MQTT...');
            window.dashboardMqttConnected = false;
            updateMqttStatus(false, 'Reconectando...');
        });

        window.dashboardMqttClient.on('error', (error) => {
            console.log('Error de conexión MQTT:', error);
            window.dashboardMqttConnected = false;
            updateMqttStatus(false, 'Error de conexión');
            showNotification('Error de conexión MQTT: ' + error.message, 'error');
        });
        
        window.dashboardMqttClient.on('close', () => {
            console.log('Conexión MQTT cerrada');
            window.dashboardMqttConnected = false;
            updateMqttStatus(false, 'Desconectado');
        });
        
    } catch (error) {
        console.error('Error inicializando MQTT:', error);
        window.dashboardMqttConnected = false;
        updateMqttStatus(false, 'Error de inicialización');
        showNotification('Error inicializando MQTT: ' + error.message, 'error');
    }
}

// Hacer funciones disponibles globalmente
window.initializeMQTT = initializeMQTT;

// Función para actualizar el indicador de estado MQTT
function updateMqttStatus(connected, message = null) {
    const statusIndicator = document.getElementById('mqtt_status');
    const statusText = document.getElementById('mqtt_status_text');
    
    if (statusIndicator && statusText) {
        if (connected) {
            statusIndicator.className = 'status-indicator status-online';
            statusText.textContent = 'MQTT: Conectado';
        } else {
            statusIndicator.className = 'status-indicator status-offline';
            statusText.textContent = message ? `MQTT: ${message}` : 'MQTT: Desconectado';
        }
    }
}

// Hacer funciones disponibles globalmente
window.updateMqttStatus = updateMqttStatus;

// Función para sincronizar el input serie con el select (del legacy)
function syncSerieInput() {
    const inputSerie = document.getElementById("serie_device");
    const selectDevice = document.getElementById("device_id");
    
    if (inputSerie && selectDevice) {
        inputSerie.value = selectDevice.value;
    }
}

// Hacer funciones disponibles globalmente
window.syncSerieInput = syncSerieInput;

// Función para actualizar estadísticas en tiempo real
function updateDashboardStats() {
    const selectedDevice = document.getElementById("device_id")?.value;
    
    fetch('/Dashboard/stats?serie_device=' + (selectedDevice || ''))
        .then(response => response.json())
        .then(data => {
            console.log('Estadísticas actualizadas:', data);
            
            // Actualizar elementos de estadísticas
            const totalElement = document.querySelector('.box.bg-info h3');
            const todayElement = document.querySelector('.box.bg-success h3');
            const usersElement = document.querySelector('.box.bg-warning h3');
            
            if (totalElement) totalElement.textContent = data.totalAccess || 0;
            if (todayElement) todayElement.textContent = data.todayAccess || 0;
            if (usersElement) usersElement.textContent = data.uniqueUsers || 0;
        })
        .catch(error => console.error('Error actualizando estadísticas:', error));
}

// Hacer funciones disponibles globalmente
window.updateDashboardStats = updateDashboardStats;

// Función para auto-refresh de datos del dashboard
function setupAutoRefresh() {
    // Actualizar estadísticas cada 30 segundos
    setInterval(updateDashboardStats, 30000);
    
    // Auto-refresh completo cada 5 minutos (como en legacy)
    setInterval(() => {
        console.log('Auto-refresh ejecutado');
        window.location.reload();
    }, 300000);
}

// Hacer funciones disponibles globalmente
window.setupAutoRefresh = setupAutoRefresh;

// Inicializar todo cuando el DOM esté listo - evitar múltiples inicializaciones
if (!window.dashboardLegacyInitialized) {
    window.dashboardLegacyInitialized = true;
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Inicializando Dashboard Legacy...');
        
        // Inicializar audio
        initAudio();
        
        // Inicializar MQTT
        initializeMQTT();
        
        // Configurar auto-refresh
        setupAutoRefresh();
        
        // Sincronizar input serie con select cada 500ms (como en legacy)
        setInterval(syncSerieInput, 500);
        
        // Configurar eventos de cambio de dispositivo
        const deviceSelect = document.getElementById('device_id');
        if (deviceSelect) {
            deviceSelect.addEventListener('change', function() {
                syncSerieInput();
                updateDashboardStats();
            });
        }
        
        // Configurar formulario de filtro
        const filterForm = document.querySelector('form[method="GET"]');
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                const serieInput = document.getElementById('serie_device');
                if (serieInput && !serieInput.value.trim()) {
                    e.preventDefault();
                    alert('Por favor ingrese el número de serie del dispositivo');
                    serieInput.focus();
                }
            });
        }
        
        console.log('Dashboard Legacy inicializado completamente');
    });
}

// Exportar funciones para uso global
window.DashboardLegacy = {
    command,
    process_msg,
    initializeMQTT,
    updateMqttStatus,
    syncSerieInput,
    updateDashboardStats,
    showNotification,
    generarCadenaAleatoria,
    // Variables del estado
    get mqttClient() { return window.dashboardMqttClient; },
    get mqttConnected() { return window.dashboardMqttConnected; },
    get audioNotification() { return window.dashboardAudioNotification; }
};

// Debug: verificar que todas las funciones estén disponibles
console.log('🔧 dashboard-legacy.js cargado completamente');
console.log('🔧 Funciones globales disponibles:', {
    command: typeof window.command,
    process_msg: typeof window.process_msg,
    initializeMQTT: typeof window.initializeMQTT,
    updateMqttStatus: typeof window.updateMqttStatus,
    syncSerieInput: typeof window.syncSerieInput,
    updateDashboardStats: typeof window.updateDashboardStats,
    showNotification: typeof window.showNotification,
    generarCadenaAleatoria: typeof window.generarCadenaAleatoria,
    initAudio: typeof window.initAudio,
    setupAutoRefresh: typeof window.setupAutoRefresh
}); 