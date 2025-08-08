/**
 * Funcionalidades del Dashboard para SMARTLABS
 * Replica las funcionalidades MQTT y AJAX del archivo legacy dashboard.php
 */

// Variables globales para MQTT - evitar conflictos
window.dashboardMqttClient = window.dashboardMqttClient || null;
window.dashboardMqttConnected = window.dashboardMqttConnected || false;
window.dashboardAudioNotification = window.dashboardAudioNotification || null;

// Pila de estados de usuario válidos para mantener datos consistentes
window.userStateStack = window.userStateStack || [];
const MAX_STACK_SIZE = 5; // Mantener máximo 5 estados válidos

// Funciones para manejar la pila de estados de usuario
function isValidUserData(userData) {
    // Verificar que los datos del usuario sean válidos y no estén vacíos
    if (!userData) return false;
    
    const name = userData.user || userData.user_name || userData.hab_name;
    const registration = userData.user_registration || userData.hab_registration;
    const email = userData.user_email || userData.hab_email;
    
    // Al menos debe tener nombre y no ser "Sin usuario"
    return name && 
           name.trim() !== '' && 
           name !== 'Sin usuario' && 
           name !== 'null' && 
           name !== 'undefined' &&
           (registration || email); // Al menos debe tener registro o email
}

function pushUserState(deviceId, userData) {
    // Solo agregar a la pila si los datos son válidos
    if (!isValidUserData(userData)) {
        console.log('📝 Datos de usuario no válidos, no se agregan a la pila:', userData);
        return;
    }
    
    const userState = {
        device: deviceId,
        user: userData.user || userData.user_name || userData.hab_name,
        user_name: userData.user_name || userData.user || userData.hab_name,
        user_registration: userData.user_registration || userData.hab_registration,
        user_email: userData.user_email || userData.hab_email,
        timestamp: new Date().toISOString()
    };
    
    // Buscar si ya existe un estado para este dispositivo
    const existingIndex = window.userStateStack.findIndex(state => state.device === deviceId);
    
    if (existingIndex !== -1) {
        // Actualizar el estado existente
        window.userStateStack[existingIndex] = userState;
        console.log('📝 Estado de usuario actualizado en la pila para:', deviceId);
    } else {
        // Agregar nuevo estado
        window.userStateStack.push(userState);
        console.log('📝 Nuevo estado de usuario agregado a la pila para:', deviceId);
    }
    
    // Mantener tamaño máximo de la pila
    if (window.userStateStack.length > MAX_STACK_SIZE) {
        window.userStateStack.shift(); // Remover el más antiguo
        console.log('📝 Pila de estados reducida a tamaño máximo:', MAX_STACK_SIZE);
    }
    
    console.log('📝 Estado actual de la pila:', window.userStateStack);
}

function getLastValidUserState(deviceId) {
    // Buscar el último estado válido para este dispositivo
    const state = window.userStateStack.find(state => state.device === deviceId);
    
    if (state) {
        console.log('📝 Estado válido encontrado en la pila para:', deviceId, state);
        return state;
    }
    
    // Si no hay estado específico para este dispositivo, usar el más reciente
    if (window.userStateStack.length > 0) {
        const lastState = window.userStateStack[window.userStateStack.length - 1];
        console.log('📝 Usando último estado válido general:', lastState);
        return lastState;
    }
    
    console.log('📝 No hay estados válidos en la pila para:', deviceId);
    return null;
}

function clearUserStateStack() {
    window.userStateStack = [];
    console.log('📝 Pila de estados de usuario limpiada');
}

// Hacer funciones disponibles globalmente
window.isValidUserData = isValidUserData;
window.pushUserState = pushUserState;
window.getLastValidUserState = getLastValidUserState;
window.clearUserStateStack = clearUserStateStack;

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

// Función para actualizar estado del dispositivo en la base de datos
function updateDeviceStateInDatabase(deviceSerie, state) {
    console.log('Actualizando estado en base de datos:', deviceSerie, '->', state);
    
    // Crear FormData para enviar vía POST
    const formData = new FormData();
    formData.append('device_serie', deviceSerie);
    formData.append('state', state);
    
    // Enviar petición AJAX
    fetch('/Dashboard/updateDeviceState', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Estado actualizado en base de datos:', data.message);
            
            // Actualizar UI inmediatamente
            updateDeviceStatusUI({
                device: deviceSerie,
                state: state,
                online: true,
                timestamp: data.timestamp
            });
            
            // Mostrar notificación
            showNotification('Estado actualizado: ' + state.toUpperCase(), 'success');
        } else {
            console.error('❌ Error actualizando estado:', data.error);
            showNotification('Error actualizando estado: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error en petición AJAX:', error);
        showNotification('Error de conexión al actualizar estado', 'error');
    });
}

// Hacer función disponible globalmente
window.updateDeviceStateInDatabase = updateDeviceStateInDatabase;

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
                // Actualizar estado inmediatamente en la base de datos
                updateDeviceStateInDatabase(deviceSerie, 'on');
                // También actualizar después de 2 segundos por si acaso
                setTimeout(updateDeviceStatus, 2000);
            }
        });
    } else if (action === "close") {
        window.dashboardMqttClient.publish(deviceSerie + "/command", 'close', (error) => {
            console.log(error || 'Cerrando dispositivo!!!');
            if (error) {
                alert('Error enviando comando: ' + error);
            } else {
                showNotification('Comando de apagado enviado al dispositivo ' + deviceSerie, 'success');
                // Actualizar estado inmediatamente en la base de datos
                updateDeviceStateInDatabase(deviceSerie, 'off');
                // También actualizar después de 2 segundos por si acaso
                setTimeout(updateDeviceStatus, 2000);
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
    
    // Procesar mensajes de estado del dispositivo
    if (query === "status" && deviceSerie === serialNumber) {
        console.log('Estado del dispositivo actualizado:', serialNumber, '=', msg);
        
        // Actualizar UI con el nuevo estado
        updateDeviceStatusUI({
            device: serialNumber,
            state: msg.toLowerCase() === 'on' ? 'on' : 'off',
            online: true,
            timestamp: new Date().toISOString()
        });
    }
    
    // Procesar confirmaciones de comandos
    if (query === "command" && deviceSerie === serialNumber) {
        console.log('Confirmación de comando recibida:', serialNumber, '=', msg);
        
        // Actualizar estado basado en el comando confirmado
        let newState = 'unknown';
        if (msg.toLowerCase() === 'open') {
            newState = 'on';
        } else if (msg.toLowerCase() === 'close') {
            newState = 'off';
        }
        
        if (newState !== 'unknown') {
            updateDeviceStatusUI({
                device: serialNumber,
                state: newState,
                online: true,
                timestamp: new Date().toISOString()
            });
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

    // Configuración dinámica de URL MQTT WebSocket
    let WebSocket_URL;
    const hostname = window.location.hostname;
    
    console.log('🔧 Detectando configuración MQTT para hostname:', hostname);
    
    // Determinar URL correcta basada en el hostname
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
        // Acceso desde localhost - usar WSS seguro
        WebSocket_URL = 'wss://localhost:8074/mqtt';
        console.log('📡 Configuración MQTT: Acceso local detectado (WSS)');
    } else {
        // Para acceso desde red (clientes), siempre usar la IP del servidor
        WebSocket_URL = 'ws://192.168.0.100:8083/mqtt';
        console.log('📡 Configuración MQTT: Acceso desde red local/servidor detectado (WS)');
    }
    
    console.log('📡 URL MQTT WebSocket:', WebSocket_URL);
    
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

// Función para consultar el estado del dispositivo
function updateDeviceStatus() {
    const selectedDevice = document.getElementById("device_id")?.value;
    
    if (!selectedDevice) {
        // Resetear indicadores si no hay dispositivo seleccionado
        updateDeviceStatusUI({
            device: null,
            alias: 'Sin seleccionar',
            state: 'unknown',
            online: false,
            last_activity: null
        });
        return;
    }
    
    fetch('/Dashboard/status?serie_device=' + selectedDevice)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error obteniendo estado:', data.error);
                updateDeviceStatusUI({
                    device: selectedDevice,
                    alias: 'Error',
                    state: 'unknown',
                    online: false,
                    last_activity: null
                });
            } else {
                console.log('Estado del dispositivo:', data);
                updateDeviceStatusUI(data);
            }
        })
        .catch(error => {
            console.error('Error consultando estado del dispositivo:', error);
            updateDeviceStatusUI({
                device: selectedDevice,
                alias: 'Error de conexión',
                state: 'unknown',
                online: false,
                last_activity: null
            });
        });
}

// Función para actualizar la interfaz de usuario del estado del dispositivo
function updateDeviceStatusUI(data) {
    console.log('🔧 updateDeviceStatusUI llamado con:', data);
    
    const statusText = document.getElementById('device-status-text');
    const statusIndicator = document.getElementById('device-status-indicator');
    const statusIcon = document.getElementById('device-status-icon');
    const lastActivity = document.getElementById('device-last-activity');
    
    console.log('🔧 Elementos DOM encontrados:', {
        statusText: !!statusText,
        statusIndicator: !!statusIndicator,
        statusIcon: !!statusIcon
    });
    
    // Verificar si tenemos los elementos necesarios
    if (!statusText || !statusIndicator || !statusIcon) {
        console.error('❌ No se encontraron elementos DOM necesarios para actualizar el estado');
        return;
    }
    
    // Procesar datos del usuario usando la pila de estados
    let finalUserData = null;
    
    if (data.device) {
        // Intentar agregar los datos actuales a la pila si son válidos
        if (isValidUserData(data)) {
            console.log('📝 Datos de usuario válidos, agregando a la pila');
            pushUserState(data.device, data);
            finalUserData = data;
        } else {
            console.log('📝 Datos de usuario no válidos, buscando en la pila');
            // Buscar último estado válido en la pila
            const lastValidState = getLastValidUserState(data.device);
            if (lastValidState) {
                console.log('📝 Usando estado válido de la pila:', lastValidState);
                // Combinar estado de dispositivo actual con datos de usuario de la pila
                finalUserData = {
                    ...data,
                    user: lastValidState.user,
                    user_name: lastValidState.user_name,
                    user_registration: lastValidState.user_registration,
                    user_email: lastValidState.user_email
                };
            } else {
                console.log('📝 No hay estados válidos en la pila, usando datos por defecto');
                finalUserData = data;
            }
        }
    } else {
        finalUserData = data;
    }
    
    if (statusText && statusIndicator && statusIcon) {
        // Actualizar texto del estado usando datos finales (con pila)
        if (finalUserData.device) {
            // Obtener nombre del usuario de los datos finales
            const userName = finalUserData.user || finalUserData.user_name || finalUserData.hab_name || null;
            
            switch (finalUserData.state) {
                case 'on':
                    const newTextOn = userName ? `Encendido - ${userName}` : 'Encendido';
                    console.log('🟢 Actualizando estado a ENCENDIDO');
                    console.log('🔧 Texto anterior:', statusText.textContent);
                    console.log('🔧 Nuevo texto:', newTextOn);
                    console.log('📝 Usuario desde pila:', userName);
                    statusText.textContent = newTextOn;
                    statusIndicator.className = 'device-status-indicator status-on';
                    statusIcon.className = 'w-48 rounded status-on';
                    statusIndicator.innerHTML = '<i class="fa fa-circle"></i>';
                    console.log('🔧 Texto después de actualizar:', statusText.textContent);
                    break;
                case 'off':
                    const newTextOff = userName ? `Apagado - ${userName}` : 'Apagado';
                    console.log('🔴 Actualizando estado a APAGADO');
                    console.log('🔧 Texto anterior:', statusText.textContent);
                    console.log('🔧 Nuevo texto:', newTextOff);
                    console.log('📝 Usuario desde pila:', userName);
                    statusText.textContent = newTextOff;
                    statusIndicator.className = 'device-status-indicator status-off';
                    statusIcon.className = 'w-48 rounded status-off';
                    statusIndicator.innerHTML = '<i class="fa fa-circle"></i>';
                    console.log('🔧 Texto después de actualizar:', statusText.textContent);
                    break;
                default:
                    const newTextUnknown = userName ? `Desconocido - ${userName}` : 'Desconocido';
                    console.log('🟡 Actualizando estado a DESCONOCIDO:', finalUserData.state);
                    console.log('🔧 Texto anterior:', statusText.textContent);
                    console.log('🔧 Nuevo texto:', newTextUnknown);
                    console.log('📝 Usuario desde pila:', userName);
                    statusText.textContent = newTextUnknown;
                    statusIndicator.className = 'device-status-indicator status-unknown';
                    statusIcon.className = 'w-48 rounded status-unknown';
                    statusIndicator.innerHTML = '<i class="fa fa-circle"></i>';
                    console.log('🔧 Texto después de actualizar:', statusText.textContent);
                    break;
            }
        } else {
            statusText.textContent = finalUserData.alias || 'Sin seleccionar';
            statusIndicator.className = 'device-status-indicator';
            statusIcon.className = 'w-48 rounded';
            statusIndicator.innerHTML = '<i class="fa fa-circle text-muted"></i>';
        }
        
        // Actualizar última actividad
        if (lastActivity) {
            if (finalUserData.last_activity) {
                const activityDate = new Date(finalUserData.last_activity);
                const now = new Date();
                const diffMinutes = Math.floor((now - activityDate) / (1000 * 60));
                
                if (diffMinutes < 1) {
                    lastActivity.textContent = '(Hace menos de 1 minuto)';
                } else if (diffMinutes < 60) {
                    lastActivity.textContent = `(Hace ${diffMinutes} minutos)`;
                } else if (diffMinutes < 1440) {
                    const hours = Math.floor(diffMinutes / 60);
                    lastActivity.textContent = `(Hace ${hours} horas)`;
                } else {
                    lastActivity.textContent = '(Hace más de 1 día)';
                }
            } else {
                lastActivity.textContent = '';
            }
        }
        
        // Actualizar información del usuario usando datos finales
        const userInfo = document.getElementById('device-user-info');
        const userName = document.getElementById('device-user-name');
        const userRegistration = document.getElementById('device-user-registration');
        const userEmail = document.getElementById('device-user-email');
        
        console.log('🔧 Datos del usuario recibidos (finales):', {
            user: finalUserData.user,
            user_name: finalUserData.user_name,
            hab_name: finalUserData.hab_name,
            user_registration: finalUserData.user_registration,
            hab_registration: finalUserData.hab_registration,
            user_email: finalUserData.user_email,
            hab_email: finalUserData.hab_email
        });
        
        console.log('🔧 Elementos DOM de usuario encontrados:', {
            userInfo: !!userInfo,
            userName: !!userName,
            userRegistration: !!userRegistration,
            userEmail: !!userEmail
        });
        
        if (userInfo && userName && userRegistration && userEmail) {
            // Usar datos finales (con pila) para mostrar información del usuario
            const displayName = finalUserData.user || finalUserData.user_name || finalUserData.hab_name || 'Sin usuario';
            const displayRegistration = finalUserData.user_registration || finalUserData.hab_registration || '---';
            const displayEmail = finalUserData.user_email || finalUserData.hab_email || '---';
            
            console.log('🔧 Valores a mostrar (desde pila):', {
                displayName,
                displayRegistration,
                displayEmail
            });
            
            // Actualizar los elementos DOM
            userName.textContent = displayName;
            userRegistration.textContent = displayRegistration;
            userEmail.textContent = displayEmail;
            userInfo.style.display = 'block';
            
            // Verificar que se actualizó correctamente
            console.log('🔧 Valores después de actualizar:', {
                userName: userName.textContent,
                userRegistration: userRegistration.textContent,
                userEmail: userEmail.textContent,
                userInfoVisible: userInfo.style.display
            });
            
            console.log('✅ Información del usuario actualizada con pila:', {
                nombre: displayName,
                matricula: displayRegistration,
                correo: displayEmail
            });
        } else {
            console.error('❌ No se encontraron elementos HTML para mostrar información del usuario');
            console.error('❌ Elementos faltantes:', {
                userInfo: !userInfo ? 'FALTA' : 'OK',
                userName: !userName ? 'FALTA' : 'OK',
                userRegistration: !userRegistration ? 'FALTA' : 'OK',
                userEmail: !userEmail ? 'FALTA' : 'OK'
            });
        }
    }
}

// Hacer funciones disponibles globalmente
window.updateDashboardStats = updateDashboardStats;
window.updateDeviceStatus = updateDeviceStatus;
window.updateDeviceStatusUI = updateDeviceStatusUI;

// Función de test para simular estados (útil para desarrollo)
function testDeviceStatus(state, online) {
    const selectedDevice = document.getElementById("device_id")?.value || 'TEST_DEVICE';
    updateDeviceStatusUI({
        device: selectedDevice,
        alias: 'Dispositivo de Prueba',
        state: state || 'on',
        online: online !== undefined ? online : true,
        last_activity: new Date().toISOString()
    });
    console.log(`Test: Estado del dispositivo simulado - ${state}, online: ${online}`);
}

window.testDeviceStatus = testDeviceStatus;

// Función para auto-refresh de datos del dashboard
function setupAutoRefresh() {
    // Actualizar estadísticas cada 30 segundos
    setInterval(updateDashboardStats, 30000);
    
    // Actualizar estado del dispositivo cada 5 segundos
    setInterval(updateDeviceStatus, 5000);
    
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
                updateDeviceStatus(); // Actualizar estado inmediatamente al cambiar dispositivo
            });
        }
        
        // Actualizar estado inicial del dispositivo
        updateDeviceStatus();
        
        // Agregar funciones de test para la pila
        window.testUserStack = function() {
            console.log('🧪 Probando sistema de pila de usuarios...');
            
            const testDevice = 'SMART10000';
            
            // Test 1: Datos válidos
            const validData = {
                user: 'Jose Angel Balbuena Palma',
                user_name: 'Jose Angel Balbuena Palma',
                user_registration: '123456789',
                user_email: 'jose.balbuena@example.com'
            };
            
            console.log('🧪 Test 1: Agregando datos válidos');
            pushUserState(testDevice, validData);
            
            // Test 2: Datos inválidos
            const invalidData = {
                user: '',
                user_name: 'Sin usuario',
                user_registration: '',
                user_email: ''
            };
            
            console.log('🧪 Test 2: Intentando agregar datos inválidos');
            pushUserState(testDevice, invalidData);
            
            // Test 3: Recuperar estado válido
            console.log('🧪 Test 3: Recuperando último estado válido');
            const lastValid = getLastValidUserState(testDevice);
            console.log('🧪 Resultado:', lastValid);
            
            // Test 4: Actualizar UI con datos inválidos (debería usar la pila)
            console.log('🧪 Test 4: Actualizando UI con datos inválidos');
            updateDeviceStatusUI({
                device: testDevice,
                state: 'on',
                online: true,
                user: '',
                user_name: 'Sin usuario',
                user_registration: '',
                user_email: '',
                timestamp: new Date().toISOString()
            });
            
            console.log('✅ Test completado - Revisa la pila:', window.userStateStack);
        };
        
        // Agregar función para limpiar la pila
        window.clearStack = function() {
            clearUserStateStack();
            console.log('🧹 Pila limpiada');
        };
        
        console.log('✅ Sistema de monitoreo del estado del dispositivo iniciado');
        console.log('ℹ️  Actualizaciones automáticas cada 5 segundos');
        console.log('🧪 Función de test disponible: testDeviceStatus("on", true)');
        console.log('📝 Funciones de pila disponibles: testUserStack(), clearStack()');
        
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
    updateDeviceStatus,
    updateDeviceStatusUI,
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
    updateDeviceStatus: typeof window.updateDeviceStatus,
    updateDeviceStatusUI: typeof window.updateDeviceStatusUI,
    showNotification: typeof window.showNotification,
    generarCadenaAleatoria: typeof window.generarCadenaAleatoria,
    initAudio: typeof window.initAudio,
    setupAutoRefresh: typeof window.setupAutoRefresh
});