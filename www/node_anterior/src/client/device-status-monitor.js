/**
 * Device Status Monitor - SMARTLABS
 * Monitorea constantemente el estado de los dispositivos consultando la base de datos
 */

// Variables globales para el monitor de estado
window.deviceStatusMonitor = {
    running: false,
    intervalId: null,
    pollingInterval: 5000, // 5 segundos por defecto
    selectedDevice: null,
    lastStatus: null,
    connectionAttempts: 0,
    maxRetries: 3
};

/**
 * Inicializa el monitor de estado del dispositivo
 * @param {number} interval - Intervalo de consulta en milisegundos (opcional)
 */
function initDeviceStatusMonitor(interval) {
    console.log('Inicializando monitor de estado de dispositivos...');
    
    // Configurar intervalo personalizado si se proporciona
    if (interval && typeof interval === 'number' && interval > 1000) {
        window.deviceStatusMonitor.pollingInterval = interval;
    }
    
    // Obtener dispositivo seleccionado
    const deviceSelect = document.getElementById('device_id');
    if (deviceSelect && deviceSelect.value) {
        window.deviceStatusMonitor.selectedDevice = deviceSelect.value;
    }
    
    // Configurar evento de cambio de dispositivo
    if (deviceSelect) {
        deviceSelect.addEventListener('change', function() {
            window.deviceStatusMonitor.selectedDevice = this.value;
            checkDeviceStatus(); // Verificar inmediatamente al cambiar
        });
    }
    
    // Iniciar monitoreo si no está corriendo ya
    if (!window.deviceStatusMonitor.running) {
        window.deviceStatusMonitor.running = true;
        
        // Verificar estado inmediatamente
        checkDeviceStatus();
        
        // Configurar intervalo de verificación
        window.deviceStatusMonitor.intervalId = setInterval(
            checkDeviceStatus, 
            window.deviceStatusMonitor.pollingInterval
        );
        
        console.log(`Monitor de estado iniciado - consultando cada ${window.deviceStatusMonitor.pollingInterval}ms`);
    } else {
        console.log('El monitor de estado ya está corriendo');
    }
}

/**
 * Detiene el monitor de estado
 */
function stopDeviceStatusMonitor() {
    if (window.deviceStatusMonitor.intervalId) {
        clearInterval(window.deviceStatusMonitor.intervalId);
        window.deviceStatusMonitor.running = false;
        window.deviceStatusMonitor.intervalId = null;
        console.log('Monitor de estado detenido');
    }
}

/**
 * Consulta el estado del dispositivo directamente en la base de datos
 * a través del endpoint en DashboardController
 */
function checkDeviceStatus() {
    const selectedDevice = window.deviceStatusMonitor.selectedDevice || 
                          document.getElementById('device_id')?.value;
    
    if (!selectedDevice) {
        updateStatusDisplay({
            device: null,
            state: 'unknown',
            online: false,
            error: 'No hay dispositivo seleccionado'
        });
        return;
    }
    
    console.log(`Consultando estado del dispositivo: ${selectedDevice}`);
    
    // Usar el endpoint existente en DashboardController
    fetch(`/Dashboard/status?serie_device=${selectedDevice}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Estado del dispositivo recibido:', data);
            
            // Resetear contador de intentos si hay éxito
            window.deviceStatusMonitor.connectionAttempts = 0;
            
            // Guardar último estado
            window.deviceStatusMonitor.lastStatus = data;
            
            // Actualizar UI
            updateStatusDisplay(data);
        })
        .catch(error => {
            console.error('Error consultando estado:', error);
            
            // Incrementar contador de intentos
            window.deviceStatusMonitor.connectionAttempts++;
            
            // Si hay demasiados errores consecutivos, mostrar error
            if (window.deviceStatusMonitor.connectionAttempts >= window.deviceStatusMonitor.maxRetries) {
                updateStatusDisplay({
                    device: selectedDevice,
                    state: 'unknown',
                    online: false,
                    error: 'Error de conexión con la base de datos'
                });
                
                // Mostrar notificación de error
                if (typeof showNotification === 'function') {
                    showNotification('Error de conexión al verificar estado del dispositivo', 'error');
                }
            } else {
                // Usar último estado conocido si está disponible
                if (window.deviceStatusMonitor.lastStatus) {
                    updateStatusDisplay({
                        ...window.deviceStatusMonitor.lastStatus,
                        warning: 'Usando datos en caché'
                    });
                }
            }
        });
}

/**
 * Actualiza la interfaz de usuario con el estado del dispositivo
 * @param {Object} data - Datos del estado del dispositivo
 */
function updateStatusDisplay(data) {
    // Elementos de UI para el estado
    const statusText = document.getElementById('device-status-text');
    const statusIndicator = document.getElementById('device-status-indicator');
    const statusIcon = document.getElementById('device-status-icon');
    const lastActivity = document.getElementById('device-last-activity');
    
    // Elementos de UI para la conexión
    const connectionText = document.getElementById('device-connection-text');
    const connectionIndicator = document.getElementById('device-connection-indicator');
    const connectionIcon = document.getElementById('device-connection-icon');
    
    // Actualizar estado del dispositivo
    if (statusText && statusIndicator && statusIcon) {
        if (data.device) {
            switch (data.state) {
                case 'on':
                    statusText.textContent = 'Encendido';
                    statusIndicator.className = 'device-status-indicator status-on';
                    statusIcon.className = 'w-48 rounded status-on';
                    statusIndicator.innerHTML = '<i class="fa fa-circle"></i>';
                    break;
                case 'off':
                    statusText.textContent = 'Apagado';
                    statusIndicator.className = 'device-status-indicator status-off';
                    statusIcon.className = 'w-48 rounded status-off';
                    statusIndicator.innerHTML = '<i class="fa fa-circle"></i>';
                    break;
                default:
                    statusText.textContent = 'Desconocido';
                    statusIndicator.className = 'device-status-indicator status-unknown';
                    statusIcon.className = 'w-48 rounded status-unknown';
                    statusIndicator.innerHTML = '<i class="fa fa-circle"></i>';
                    break;
            }
        } else {
            statusText.textContent = data.alias || 'Sin seleccionar';
            statusIndicator.className = 'device-status-indicator';
            statusIcon.className = 'w-48 rounded';
            statusIndicator.innerHTML = '<i class="fa fa-circle text-muted"></i>';
        }
        
        // Actualizar última actividad
        if (lastActivity) {
            if (data.last_activity) {
                const activityDate = new Date(data.last_activity);
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
    }
    
    // Actualizar estado de conexión
    if (connectionText && connectionIndicator && connectionIcon) {
        if (data.online) {
            connectionText.textContent = 'Conectado';
            connectionIndicator.className = 'device-connection-indicator status-online';
            connectionIcon.className = 'w-48 rounded status-online';
            connectionIndicator.innerHTML = '<i class="fa fa-circle"></i>';
        } else {
            connectionText.textContent = 'Desconectado';
            connectionIndicator.className = 'device-connection-indicator status-offline';
            connectionIcon.className = 'w-48 rounded status-offline';
            connectionIndicator.innerHTML = '<i class="fa fa-circle"></i>';
        }
    }
    
    // Si hay advertencia, mostrarla
    if (data.warning && typeof showNotification === 'function') {
        showNotification(data.warning, 'warning');
    }
    
    // Si hay error, mostrar en consola
    if (data.error) {
        console.error('Error en monitor de estado:', data.error);
    }
}

/**
 * Función para pruebas - simula diferentes estados del dispositivo
 * @param {string} state - Estado a simular ('on', 'off', 'unknown')
 * @param {boolean} online - Estado de conexión a simular
 */
function testDeviceStatusMonitor(state, online) {
    const selectedDevice = window.deviceStatusMonitor.selectedDevice || 
                          document.getElementById('device_id')?.value || 
                          'TEST_DEVICE';
    
    updateStatusDisplay({
        device: selectedDevice,
        alias: 'Dispositivo de Prueba',
        state: state || 'on',
        online: online !== undefined ? online : true,
        last_activity: new Date().toISOString()
    });
    
    console.log(`Test: Estado del dispositivo simulado - ${state}, online: ${online}`);
}

// Hacer funciones disponibles globalmente
window.initDeviceStatusMonitor = initDeviceStatusMonitor;
window.stopDeviceStatusMonitor = stopDeviceStatusMonitor;
window.checkDeviceStatus = checkDeviceStatus;
window.testDeviceStatusMonitor = testDeviceStatusMonitor;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando monitor de estado de dispositivos...');
    initDeviceStatusMonitor();
});

// Exportar funciones para uso global
window.DeviceStatusMonitor = {
    init: initDeviceStatusMonitor,
    stop: stopDeviceStatusMonitor,
    check: checkDeviceStatus,
    test: testDeviceStatusMonitor,
    config: window.deviceStatusMonitor
};

console.log('🔧 device-status-monitor.js cargado completamente');