/**
 * Configuración global para SMARTLABS
 */

window.SmartLabsConfig = {
    // URLs base
    baseUrl: window.location.origin,
    apiUrl: window.location.origin + '/api',
    
    // Configuración MQTT
    mqtt: {
        brokerUrl: 'ws://localhost:8083/mqtt',
        username: 'jose',
        password: 'public',
        clientId: 'iotmc' + Math.random().toString(16).substr(2, 8),
        topics: {
            deviceStatus: 'smartlabs/devices/+/status',
            deviceRfid: 'smartlabs/devices/+/rfid',
            deviceControl: 'smartlabs/devices/+/control',
            loans: 'smartlabs/loans/+',
            system: 'smartlabs/system/+'
        }
    },
    
    // Configuración de la aplicación
    app: {
        name: 'SMARTLABS',
        version: '2.0.0',
        refreshInterval: 30000, // 30 segundos
        simulateMode: false, // Cambiar a true para modo simulación
        debug: true // Logs en consola
    },
    
    // Validaciones
    validation: {
        rfid: {
            minLength: 8,
            pattern: /^[0-9]+$/
        },
        registration: {
            minLength: 3,
            pattern: /^[A-Z0-9]+$/
        },
        email: {
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        }
    },
    
    // Mensajes del sistema
    messages: {
        loading: 'Cargando...',
        noData: 'No hay datos disponibles',
        error: 'Ha ocurrido un error',
        success: 'Operación exitosa',
        mqttConnected: 'MQTT Conectado',
        mqttDisconnected: 'MQTT Desconectado',
        mqttSimulated: 'MQTT Simulado'
    },
    
    // Iconos de FontAwesome
    icons: {
        loading: 'fa-spinner fa-spin',
        success: 'fa-check-circle',
        error: 'fa-exclamation-triangle',
        warning: 'fa-exclamation-circle',
        info: 'fa-info-circle',
        device: 'fa-microchip',
        user: 'fa-user',
        rfid: 'fa-credit-card',
        stats: 'fa-chart-line'
    },
    
    // Métodos de utilidad
    utils: {
        log: function(message, type = 'info') {
            if (window.SmartLabsConfig.app.debug) {
                console[type]('[SMARTLABS]', message);
            }
        },
        
        formatDate: function(date) {
            if (!date) return '';
            const d = new Date(date);
            return d.toLocaleString('es-ES');
        },
        
        validateRfid: function(rfid) {
            const config = window.SmartLabsConfig.validation.rfid;
            return rfid && 
                   rfid.length >= config.minLength && 
                   config.pattern.test(rfid);
        },
        
        validateRegistration: function(registration) {
            const config = window.SmartLabsConfig.validation.registration;
            return registration && 
                   registration.length >= config.minLength && 
                   config.pattern.test(registration.toUpperCase());
        },
        
        validateEmail: function(email) {
            return window.SmartLabsConfig.validation.email.pattern.test(email);
        },
        
        showNotification: function(message, type = 'info', container = null) {
            const alertClass = `alert-${type}`;
            const icon = window.SmartLabsConfig.icons[type] || window.SmartLabsConfig.icons.info;
            
            const html = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fa ${icon}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            if (container) {
                container.innerHTML = html;
            } else {
                // Crear notificación flotante
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    max-width: 350px;
                `;
                notification.innerHTML = html;
                document.body.appendChild(notification);
                
                // Auto-remove después de 5 segundos
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 5000);
            }
        },
        
        animateElement: function(element, animation = 'fadeInUp') {
            if (!element) return;
            
            element.classList.add('animated', animation);
            
            const handleAnimationEnd = () => {
                element.classList.remove('animated', animation);
                element.removeEventListener('animationend', handleAnimationEnd);
            };
            
            element.addEventListener('animationend', handleAnimationEnd);
        },
        
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        debounce: function(func, wait, immediate) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    }
};

// Configurar variables CSS personalizadas
document.documentElement.style.setProperty('--smartlabs-primary', '#007bff');
document.documentElement.style.setProperty('--smartlabs-secondary', '#6c757d');
document.documentElement.style.setProperty('--smartlabs-success', '#28a745');
document.documentElement.style.setProperty('--smartlabs-danger', '#dc3545');
document.documentElement.style.setProperty('--smartlabs-warning', '#ffc107');
document.documentElement.style.setProperty('--smartlabs-info', '#17a2b8');

// Log de inicialización
document.addEventListener('DOMContentLoaded', function() {
    window.SmartLabsConfig.utils.log(`Sistema ${window.SmartLabsConfig.app.name} v${window.SmartLabsConfig.app.version} inicializado`, 'info');
    
    // Detectar modo simulación
    if (window.SmartLabsConfig.app.simulateMode) {
        window.SmartLabsConfig.utils.log('Modo simulación activado', 'warning');
    }
});

// Exportar configuración para módulos ES6 si están disponibles
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.SmartLabsConfig;
}