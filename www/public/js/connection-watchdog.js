/**
 * Sistema de Watchdog para Conexiones MQTT y AJAX
 * Detecta y resuelve problemas de conectividad después de varias horas
 */

class ConnectionWatchdog {
    constructor(options = {}) {
        this.options = {
            checkInterval: 60000, // 1 minuto
            mqttTimeout: 120000, // 2 minutos sin actividad MQTT
            ajaxTimeout: 30000, // 30 segundos para AJAX
            maxFailures: 3, // Máximo fallos antes de reconexión
            enablePreventiveReconnect: false, // Deshabilitado - solo reconectar en problemas
            debug: true,
            ...options
        };
        
        this.state = {
            mqttLastActivity: Date.now(),
            ajaxLastActivity: Date.now(),
            mqttFailures: 0,
            ajaxFailures: 0,
            lastForceReconnect: Date.now(),
            isActive: false
        };
        
        this.intervals = {
            watchdog: null
        };
        
        this.init();
    }
    
    init() {
        this.log('🐕 Inicializando Connection Watchdog...');
        this.setupAjaxInterceptor();
        this.setupMqttMonitoring();
        this.start();
    }
    
    start() {
        if (this.state.isActive) {
            this.log('⚠️ Watchdog ya está activo');
            return;
        }
        
        this.state.isActive = true;
        this.log('🚀 Iniciando Connection Watchdog...');
        
        // Verificación periódica solo para detectar problemas
        this.intervals.watchdog = setInterval(() => {
            this.checkConnections();
        }, this.options.checkInterval);
        
        this.log('✅ Connection Watchdog iniciado');
    }
    
    stop() {
        this.state.isActive = false;
        
        if (this.intervals.watchdog) {
            clearInterval(this.intervals.watchdog);
            this.intervals.watchdog = null;
        }
        
        // Solo detener el watchdog principal
        
        this.log('🛑 Connection Watchdog detenido');
    }
    
    setupAjaxInterceptor() {
        const self = this;
        
        // Interceptar jQuery AJAX
        if (typeof $ !== 'undefined') {
            $(document).ajaxSend(function() {
                self.state.ajaxLastActivity = Date.now();
            });
            
            $(document).ajaxComplete(function() {
                self.state.ajaxLastActivity = Date.now();
                self.state.ajaxFailures = 0; // Reset en éxito
            });
            
            $(document).ajaxError(function(event, xhr, settings, error) {
                self.state.ajaxFailures++;
                self.log(`❌ AJAX Error detectado: ${error} (Fallos: ${self.state.ajaxFailures})`);
                
                if (self.state.ajaxFailures >= self.options.maxFailures) {
                    self.handleAjaxFailure();
                }
            });
        }
        
        // Interceptar fetch API
        if (typeof window.fetch !== 'undefined') {
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                self.state.ajaxLastActivity = Date.now();
                
                return originalFetch.apply(this, args)
                    .then(response => {
                        self.state.ajaxLastActivity = Date.now();
                        self.state.ajaxFailures = 0;
                        return response;
                    })
                    .catch(error => {
                        self.state.ajaxFailures++;
                        self.log(`❌ Fetch Error detectado: ${error} (Fallos: ${self.state.ajaxFailures})`);
                        
                        if (self.state.ajaxFailures >= self.options.maxFailures) {
                            self.handleAjaxFailure();
                        }
                        
                        throw error;
                    });
            };
        }
    }
    
    setupMqttMonitoring() {
        // Monitorear cliente MQTT mejorado
        if (typeof window !== 'undefined') {
            const self = this;
            
            // Hook para detectar actividad MQTT
            const originalConsoleLog = console.log;
            console.log = function(...args) {
                const message = args.join(' ');
                if (message.includes('📨 Mensaje MQTT recibido') || 
                    message.includes('✅ MQTT conectado') ||
                    message.includes('💓 Enviando keep-alive')) {
                    self.state.mqttLastActivity = Date.now();
                    self.state.mqttFailures = 0;
                }
                return originalConsoleLog.apply(console, args);
            };
        }
    }
    
    checkConnections() {
        const now = Date.now();
        
        // Verificar MQTT
        const mqttInactive = now - this.state.mqttLastActivity > this.options.mqttTimeout;
        if (mqttInactive) {
            this.state.mqttFailures++;
            this.log(`⚠️ MQTT inactivo por ${Math.round((now - this.state.mqttLastActivity) / 1000)}s (Fallos: ${this.state.mqttFailures})`);
            
            if (this.state.mqttFailures >= this.options.maxFailures) {
                this.handleMqttFailure();
            }
        }
        
        // Verificar AJAX
        const ajaxInactive = now - this.state.ajaxLastActivity > this.options.ajaxTimeout * 10; // Solo si no hay actividad por mucho tiempo
        if (ajaxInactive && this.state.ajaxFailures > 0) {
            this.log(`⚠️ AJAX con problemas detectados`);
            this.handleAjaxFailure();
        }
        
        // Log de estado cada 5 minutos
        if (now % 300000 < this.options.checkInterval) {
            this.logStatus();
        }
    }
    
    handleMqttFailure() {
        this.log('🔧 Intentando reparar conexión MQTT...');
        
        // Intentar reconectar MQTT
        if (window.loanMqttClient) {
            try {
                window.loanMqttClient.disconnect();
                setTimeout(() => {
                    window.loanMqttClient.connect();
                    this.state.mqttLastActivity = Date.now();
                    this.state.mqttFailures = 0;
                }, 2000);
            } catch (error) {
                this.log(`❌ Error reconectando MQTT: ${error}`);
            }
        }
        
        // Intentar reconectar cliente alternativo
        if (window.mqttClient) {
            try {
                window.mqttClient.disconnect();
                setTimeout(() => {
                    window.mqttClient = new SmartLabsMQTT();
                    this.state.mqttLastActivity = Date.now();
                    this.state.mqttFailures = 0;
                }, 3000);
            } catch (error) {
                this.log(`❌ Error reconectando cliente alternativo: ${error}`);
            }
        }
    }
    
    handleAjaxFailure() {
        this.log('🔧 Intentando reparar conexiones AJAX...');
        
        // Verificar sesión
        if (window.sessionKeepAlive) {
            window.sessionKeepAlive.sendKeepAlive();
        }
        
        // Recargar configuraciones críticas
        this.reloadCriticalComponents();
        
        // Reset contadores
        this.state.ajaxFailures = 0;
        this.state.ajaxLastActivity = Date.now();
    }
    
    forceReconnect() {
        this.log('🔄 Reconexión manual solicitada...');
        
        // Reconectar MQTT
        this.handleMqttFailure();
        
        // Enviar keep-alive
        if (window.sessionKeepAlive) {
            window.sessionKeepAlive.sendKeepAlive();
        }
        
        // Reset contadores
        this.state.mqttFailures = 0;
        this.state.ajaxFailures = 0;
        this.state.mqttLastActivity = Date.now();
        this.state.ajaxLastActivity = Date.now();
        
        this.log('✅ Reconexión manual completada');
    }
    
    reloadCriticalComponents() {
        // Recargar funciones críticas de la vista Loan
        if (window.location.pathname.includes('/Loan')) {
            try {
                // Verificar funciones críticas
                if (typeof validateRfidWithApi === 'function') {
                    this.log('✅ validateRfidWithApi disponible');
                }
                
                if (typeof checkSessionState === 'function') {
                    this.log('✅ checkSessionState disponible');
                }
                
                if (typeof consultarPrestamosUsuario === 'function') {
                    this.log('✅ consultarPrestamosUsuario disponible');
                }
                
            } catch (error) {
                this.log(`❌ Error verificando componentes: ${error}`);
            }
        }
    }
    
    logStatus() {
        const now = Date.now();
        const mqttAge = Math.round((now - this.state.mqttLastActivity) / 1000);
        const ajaxAge = Math.round((now - this.state.ajaxLastActivity) / 1000);
        
        this.log(`📊 Estado Watchdog - MQTT: ${mqttAge}s, AJAX: ${ajaxAge}s, Fallos MQTT: ${this.state.mqttFailures}, Fallos AJAX: ${this.state.ajaxFailures}`);
    }
    
    getStatus() {
        return {
            ...this.state,
            uptime: Date.now() - this.state.lastForceReconnect,
            mqttHealthy: this.state.mqttFailures < this.options.maxFailures,
            ajaxHealthy: this.state.ajaxFailures < this.options.maxFailures
        };
    }
    
    log(message) {
        if (this.options.debug) {
            console.log(`[Watchdog] ${message}`);
        }
    }
}

// Inicializar automáticamente en la vista de Loan
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('/Loan') && !window.location.pathname.includes('/Dashboard')) {
        console.log('🐕 Inicializando Connection Watchdog para vista Loan...');
        
        // Esperar un poco para que otros sistemas se inicialicen
        setTimeout(() => {
            window.connectionWatchdog = new ConnectionWatchdog({
            debug: true,
            checkInterval: 60000, // 1 minuto
            mqttTimeout: 120000, // 2 minutos
            enablePreventiveReconnect: false // Solo reconectar cuando hay problemas
        });
            
            // Función global para obtener estado
            window.getWatchdogStatus = function() {
                return window.connectionWatchdog ? window.connectionWatchdog.getStatus() : null;
            };
            
            // Función global para forzar reconexión
            window.forceReconnectAll = function() {
                if (window.connectionWatchdog) {
                    window.connectionWatchdog.forceReconnect();
                }
            };
            
            console.log('✅ Connection Watchdog inicializado');
        }, 5000); // 5 segundos de delay
    }
});

// Limpiar al salir
window.addEventListener('beforeunload', function() {
    if (window.connectionWatchdog) {
        window.connectionWatchdog.stop();
    }
});