/**
 * Sistema de Keep-Alive para Sesiones PHP
 * Mantiene la sesión activa automáticamente y maneja reconexiones AJAX
 * 
 * Características:
 * - Keep-alive automático cada 5 minutos
 * - Detección de sesión expirada
 * - Reintentos automáticos para AJAX fallidos
 * - Indicador visual de estado de sesión
 * - Manejo de errores de conectividad
 */

class SessionKeepAlive {
    constructor(options = {}) {
        this.options = {
            interval: options.interval || 60000, // 1 minuto para sesión permanente
            endpoint: options.endpoint || '/Auth/keepalive',
            maxRetries: options.maxRetries || 5, // Más reintentos para sesión permanente
            retryDelay: options.retryDelay || 3000, // 3 segundos entre reintentos
            showIndicator: options.showIndicator !== false,
            debug: options.debug || false,
            ...options
        };
        
        this.isActive = true;
        this.retryCount = 0;
        this.keepaliveTimer = null;
        this.lastActivity = Date.now();
        this.sessionValid = true;
        
        this.init();
    }
    
    init() {
        this.log('🚀 Iniciando sistema de keep-alive de sesión');
        
        // Crear indicador visual si está habilitado
        if (this.options.showIndicator) {
            this.createSessionIndicator();
        }
        
        // Iniciar keep-alive automático
        this.startKeepAlive();
        
        // Detectar actividad del usuario
        this.setupActivityDetection();
        
        // Interceptar llamadas AJAX para agregar manejo de errores
        this.setupAjaxInterceptor();
        
        // Manejar visibilidad de la página
        this.setupVisibilityHandler();
    }
    
    createSessionIndicator() {
        // Crear indicador de estado de sesión
        const indicator = document.createElement('div');
        indicator.id = 'session-status-indicator';
        indicator.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 9999;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            background: #28a745;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            opacity: 0.8;
        `;
        indicator.innerHTML = '<i class="fa fa-check-circle"></i> Sesión Activa';
        
        document.body.appendChild(indicator);
        this.indicator = indicator;
        
        // Ocultar después de 3 segundos
        setTimeout(() => {
            if (this.indicator) {
                this.indicator.style.opacity = '0.3';
            }
        }, 3000);
    }
    
    updateIndicator(status, message) {
        if (!this.indicator) return;
        
        const colors = {
            permanent: '#17a2b8', // Azul para sesión permanente
            active: '#28a745',
            warning: '#ffc107',
            error: '#dc3545',
            reconnecting: '#6c757d'
        };
        
        const icons = {
            permanent: 'fa-shield-alt', // Icono de escudo para sesión permanente
            active: 'fa-check-circle',
            warning: 'fa-exclamation-triangle',
            error: 'fa-times-circle',
            reconnecting: 'fa-sync fa-spin'
        };
        
        this.indicator.style.background = colors[status] || colors.active;
        this.indicator.innerHTML = `<i class="fa ${icons[status] || icons.active}"></i> ${message}`;
        this.indicator.style.opacity = '1';
        
        // Auto-ocultar para estados normales
        if (status === 'active') {
            setTimeout(() => {
                if (this.indicator) {
                    this.indicator.style.opacity = '0.3';
                }
            }, 3000);
        }
    }
    
    startKeepAlive() {
        this.log('⏰ Iniciando timer de keep-alive');
        
        this.keepaliveTimer = setInterval(() => {
            if (this.isActive && this.sessionValid) {
                this.sendKeepAlive();
            }
        }, this.options.interval);
        
        // Enviar keep-alive inicial después de 30 segundos
        setTimeout(() => {
            if (this.isActive && this.sessionValid) {
                this.sendKeepAlive();
            }
        }, 30000);
    }
    
    sendKeepAlive() {
        this.log('💓 Enviando keep-alive permanente');
        
        if (this.options.showIndicator) {
            this.updateIndicator('reconnecting', 'Verificando Sesión...');
        }
        
        $.ajax({
            url: this.options.endpoint,
            method: 'POST',
            timeout: 15000, // 15 segundos timeout
            cache: false,
            success: (response) => {
                this.handleKeepAliveSuccess(response);
            },
            error: (xhr, status, error) => {
                this.handleKeepAliveError(xhr, status, error);
            }
        });
    }
    
    handleKeepAliveSuccess(response) {
        this.log('✅ Keep-alive permanente exitoso:', response);
        
        if (response.success) {
            this.retryCount = 0;
            this.sessionValid = true;
            this.lastActivity = Date.now();
            
            if (this.options.showIndicator) {
                if (response.permanent) {
                    this.updateIndicator('permanent', 'Sesión Permanente Activa');
                } else {
                    this.updateIndicator('active', 'Sesión Activa');
                }
            }
        } else {
            this.log('❌ Sesión inválida, redirigiendo al login');
            this.handleSessionExpired(response);
        }
    }
    
    handleKeepAliveError(xhr, status, error) {
        this.log('❌ Error en keep-alive:', { xhr, status, error });
        
        this.retryCount++;
        
        if (this.retryCount <= this.options.maxRetries) {
            this.log(`🔄 Reintentando keep-alive (${this.retryCount}/${this.options.maxRetries})`);
            
            if (this.options.showIndicator) {
                this.updateIndicator('warning', `Reintentando... (${this.retryCount}/${this.options.maxRetries})`);
            }
            
            setTimeout(() => {
                this.sendKeepAlive();
            }, this.options.retryDelay);
        } else {
            this.log('💀 Máximo de reintentos alcanzado');
            
            if (this.options.showIndicator) {
                this.updateIndicator('error', 'Error de Conexión');
            }
            
            // Verificar si es un error de sesión
            if (xhr.status === 401 || xhr.status === 403) {
                this.handleSessionExpired();
            }
        }
    }
    
    handleSessionExpired(response = null) {
        this.log('🚪 Sesión expirada, redirigiendo al login');
        
        this.sessionValid = false;
        this.stop();
        
        if (this.options.showIndicator) {
            this.updateIndicator('error', 'Sesión Expirada');
        }
        
        // Mostrar mensaje al usuario
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Sesión Expirada',
                text: 'Tu sesión ha expirado. Serás redirigido al login.',
                icon: 'warning',
                timer: 3000,
                showConfirmButton: false
            }).then(() => {
                this.redirectToLogin(response);
            });
        } else {
            alert('Tu sesión ha expirado. Serás redirigido al login.');
            this.redirectToLogin(response);
        }
    }
    
    redirectToLogin(response = null) {
        const loginUrl = (response && response.redirect) || '/Auth/login';
        window.location.href = loginUrl;
    }
    
    setupActivityDetection() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        const updateActivity = () => {
            this.lastActivity = Date.now();
        };
        
        events.forEach(event => {
            document.addEventListener(event, updateActivity, true);
        });
    }
    
    setupAjaxInterceptor() {
        // Interceptar jQuery AJAX para agregar manejo de errores de sesión
        const originalAjax = $.ajax;
        const self = this;
        
        $.ajax = function(options) {
            const originalError = options.error;
            const originalSuccess = options.success;
            
            // Agregar timeout por defecto si no está especificado
            if (!options.timeout) {
                options.timeout = 15000; // 15 segundos por defecto
            }
            
            // Interceptar errores
            options.error = function(xhr, status, error) {
                // Verificar si es un error de sesión
                if (xhr.status === 401 || xhr.status === 403) {
                    self.log('🔒 Error de autenticación detectado en AJAX');
                    self.handleSessionExpired();
                    return;
                }
                
                // Llamar al error handler original si existe
                if (originalError) {
                    originalError.call(this, xhr, status, error);
                }
            };
            
            // Interceptar éxito para detectar respuestas de sesión expirada
            options.success = function(data, textStatus, xhr) {
                // Verificar si la respuesta indica sesión expirada
                if (data && typeof data === 'object' && data.session_expired) {
                    self.log('🔒 Sesión expirada detectada en respuesta AJAX');
                    self.handleSessionExpired(data);
                    return;
                }
                
                // Llamar al success handler original
                if (originalSuccess) {
                    originalSuccess.call(this, data, textStatus, xhr);
                }
            };
            
            return originalAjax.call(this, options);
        };
    }
    
    setupVisibilityHandler() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.log('📱 Página oculta, pausando keep-alive');
                this.pause();
            } else {
                this.log('📱 Página visible, reanudando keep-alive');
                this.resume();
                
                // Enviar keep-alive inmediato si ha pasado mucho tiempo
                const timeSinceLastActivity = Date.now() - this.lastActivity;
                if (timeSinceLastActivity > this.options.interval) {
                    setTimeout(() => {
                        if (this.isActive && this.sessionValid) {
                            this.sendKeepAlive();
                        }
                    }, 1000);
                }
            }
        });
    }
    
    pause() {
        this.isActive = false;
        this.log('⏸️ Keep-alive pausado');
    }
    
    resume() {
        this.isActive = true;
        this.log('▶️ Keep-alive reanudado');
    }
    
    stop() {
        this.isActive = false;
        
        if (this.keepaliveTimer) {
            clearInterval(this.keepaliveTimer);
            this.keepaliveTimer = null;
        }
        
        this.log('🛑 Keep-alive detenido');
    }
    
    log(message, data = null) {
        if (this.options.debug) {
            if (data) {
                console.log(`[SessionKeepAlive] ${message}`, data);
            } else {
                console.log(`[SessionKeepAlive] ${message}`);
            }
        }
    }
    
    // Método público para verificar estado de sesión
    checkSession() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.options.endpoint,
                method: 'POST',
                timeout: 5000,
                success: (response) => {
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(response);
                    }
                },
                error: (xhr, status, error) => {
                    reject({ xhr, status, error });
                }
            });
        });
    }
}

// Inicializar automáticamente cuando el DOM esté listo
$(document).ready(function() {
    // Solo inicializar si no estamos en la página de login
    if (!window.location.pathname.includes('/Auth/login')) {
        console.log('🚀 Inicializando sistema de sesión permanente...');
        console.log('🔒 SMARTLABS - Sesión configurada para NUNCA expirar');
        console.log('⏰ Keep-alive cada 1 minuto para mantener conexión activa');
        
        window.sessionKeepAlive = new SessionKeepAlive({
            debug: false, // Cambiar a true para debugging
            showIndicator: true
        });
        
        console.log('✅ Sistema de sesión permanente iniciado');
        console.log('🛡️ La sesión permanecerá activa indefinidamente');
    }
});

// Exponer funciones globales para compatibilidad
window.checkSessionStatus = function() {
    if (window.sessionKeepAlive) {
        return window.sessionKeepAlive.checkSession();
    }
    return Promise.reject('Keep-alive no inicializado');
};

window.forceKeepAlive = function() {
    if (window.sessionKeepAlive) {
        window.sessionKeepAlive.sendKeepAlive();
    }
};