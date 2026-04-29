/**
 * Cliente MQTT mejorado para la vista de Préstamos (Loan)
 * Soluciona problemas de desconexión con reconexión automática y heartbeat
 */

class LoanMQTTClient {
    constructor() {
        this.client = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 10;
        this.reconnectInterval = 5000; // 5 segundos
        this.heartbeatInterval = null;
        this.heartbeatTimer = 30000; // 30 segundos
        this.connectionTimeout = null;
        this.lastHeartbeat = null;
        
        // Configuración dinámica de URL
        this.brokerUrl = this.getBrokerUrl();
        
        // Opciones de conexión mejoradas
        this.options = {
            clientId: 'loan_client_' + this.generateRandomId(),
            username: 'jose',
            password: 'public',
            keepalive: 30, // Reducido para detectar desconexiones más rápido
            clean: true,
            connectTimeout: 8000, // Aumentado
            reconnectPeriod: this.reconnectInterval,
            will: {
                topic: 'smartlabs/loans/status',
                payload: JSON.stringify({
                    client: 'loan_view',
                    status: 'offline',
                    timestamp: Date.now()
                }),
                qos: 1,
                retain: false
            }
        };
        
        // Topics específicos para préstamos
        this.topics = {
            loanQueryU: '+/loan_queryu',
            loanQueryE: '+/loan_querye',
            heartbeat: 'smartlabs/loans/heartbeat',
            status: 'smartlabs/loans/status'
        };
        
        this.init();
    }
    
    /**
     * Genera ID aleatorio para el cliente
     */
    generateRandomId() {
        return Math.random().toString(16).substr(2, 8);
    }
    
    /**
     * Obtiene la URL del broker MQTT según el hostname
     */
    getBrokerUrl() {
        const hostname = window.location.hostname;
        console.log('🔧 Detectando configuración MQTT para hostname:', hostname);
        
        let url;
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            url = 'ws://localhost:8083/mqtt';
            console.log('📡 Configuración MQTT: Acceso local detectado');
        } else {
            // Para acceso desde red (clientes), siempre usar la IP del servidor
            url = window.EnvConfig ? window.EnvConfig.getMqttWsUrl() : 'ws://192.168.0.100:8083/mqtt';
            console.log('📡 Configuración MQTT: Acceso desde red local/servidor detectado');
        }
        
        console.log('📡 URL MQTT WebSocket:', url);
        return url;
    }
    
    /**
     * Inicializa la conexión MQTT
     */
    init() {
        try {
            console.log('🚀 Inicializando cliente MQTT mejorado para préstamos...');
            this.connect();
        } catch (error) {
            console.error('❌ Error inicializando MQTT:', error);
            this.scheduleReconnect();
        }
    }
    
    /**
     * Establece la conexión MQTT
     */
    connect() {
        try {
            // Limpiar conexión anterior si existe
            if (this.client) {
                this.client.end(true);
            }
            
            console.log('🔌 Conectando a MQTT:', this.brokerUrl);
            this.client = mqtt.connect(this.brokerUrl, this.options);
            
            // Timeout para la conexión
            this.connectionTimeout = setTimeout(() => {
                if (!this.isConnected) {
                    console.log('⏰ Timeout de conexión MQTT');
                    this.handleConnectionError('Timeout de conexión');
                }
            }, this.options.connectTimeout);
            
            this.setupEventHandlers();
            
        } catch (error) {
            console.error('❌ Error conectando MQTT:', error);
            this.handleConnectionError(error.message);
        }
    }
    
    /**
     * Configura los manejadores de eventos MQTT
     */
    setupEventHandlers() {
        this.client.on('connect', () => {
            console.log('✅ MQTT conectado exitosamente!');
            this.isConnected = true;
            this.reconnectAttempts = 0;
            
            // Limpiar timeout de conexión
            if (this.connectionTimeout) {
                clearTimeout(this.connectionTimeout);
                this.connectionTimeout = null;
            }
            
            // Suscribirse a topics
            this.subscribeToTopics();
            
            // Iniciar heartbeat
            this.startHeartbeat();
            
            // Publicar estado de conexión
            this.publishConnectionStatus('online');
            
            // Actualizar UI
            this.updateConnectionStatus(true);
            
            // Notificar al watchdog de conexión exitosa
            if (window.connectionWatchdog) {
                window.connectionWatchdog.state.mqttLastActivity = Date.now();
                window.connectionWatchdog.state.mqttFailures = 0;
            }
        });
        
        this.client.on('message', (topic, message) => {
            try {
                const messageStr = message.toString();
                console.log('📨 Mensaje MQTT recibido:', topic, '->', messageStr);
                this.handleMessage(topic, messageStr);
            } catch (error) {
                console.error('❌ Error procesando mensaje MQTT:', error);
            }
        });
        
        this.client.on('error', (error) => {
            console.error('❌ Error MQTT:', error);
            this.handleConnectionError(error.message);
        });
        
        this.client.on('close', () => {
            console.log('🔌 Conexión MQTT cerrada');
            this.handleDisconnection();
        });
        
        this.client.on('offline', () => {
            console.log('📴 Cliente MQTT offline');
            this.handleDisconnection();
        });
        
        this.client.on('reconnect', () => {
            console.log('🔄 Intentando reconectar MQTT...');
            this.updateConnectionStatus(false, 'Reconectando...');
        });
    }
    
    /**
     * Suscribe a los topics necesarios
     */
    subscribeToTopics() {
        const topicsToSubscribe = [
            this.topics.loanQueryU,
            this.topics.loanQueryE
        ];
        
        topicsToSubscribe.forEach(topic => {
            this.client.subscribe(topic, { qos: 0 }, (error) => {
                if (error) {
                    console.error(`❌ Error suscribiendo a ${topic}:`, error);
                } else {
                    console.log(`✅ Suscrito a ${topic}`);
                }
            });
        });
    }
    
    /**
     * Maneja los mensajes MQTT recibidos
     */
    handleMessage(topic, message) {
        const splittedTopic = topic.split('/');
        const serialNumber = splittedTopic[0];
        const query = splittedTopic[1];
        
        // Actualizar último heartbeat
        this.lastHeartbeat = Date.now();
        
        // Notificar al watchdog de actividad MQTT
        if (window.connectionWatchdog) {
            window.connectionWatchdog.state.mqttLastActivity = Date.now();
            window.connectionWatchdog.state.mqttFailures = 0;
        }
        
        if (query === 'loan_queryu') {
            this.handleLoanQueryU(message, serialNumber);
        } else if (query === 'loan_querye') {
            this.handleLoanQueryE(message, serialNumber);
        }
    }
    
    /**
     * Maneja mensajes de consulta de usuario (loan_queryu)
     */
    handleLoanQueryU(message, serialNumber) {
        // Sanear el RFID eliminando prefijo "APP:" si existe
        const sanitizedRfid = this.sanitizeRfid(message);

        // Reproducir audio de notificación
        if (window.audio) {
            window.audio.play().catch(error => {
                console.log('Error al reproducir audio:', error);
            });
        }

        // Mostrar en display de nuevo acceso
        const displayElement = document.getElementById('display_new_access');
        if (displayElement) {
            displayElement.innerHTML = 'Procesando RFID: ' + sanitizedRfid;
        }

        // Pequeño delay para que el flutter-api procese su escritura en loan_sessions
        // antes de que el browser pregunte por el estado.
        setTimeout(() => {
            if (typeof processRfidWithSessionLogic === 'function') {
                processRfidWithSessionLogic(sanitizedRfid, serialNumber);
            } else {
                console.warn('⚠️ Función processRfidWithSessionLogic no disponible');
            }
        }, 600);
    }
    
    /**
     * Maneja mensajes de consulta de equipo (loan_querye)
     */
    handleLoanQueryE(message, serialNumber) {
        const sanitizedRfid = this.sanitizeRfid(message);

        if (window.audio) {
            window.audio.play().catch(error => {
                console.log('Error al reproducir audio:', error);
            });
        }

        // El firmware unificado publica en loan_querye TANTO los UID de equipo
        // (préstamo/devolución) como el UID del propio usuario para cerrar
        // sesión. El backend resuelve ambos casos. La vista solo necesita
        // re-preguntar el estado de sesión al backend para reflejarlo:
        //  - si la sesión sigue abierta -> refrescar la lista de préstamos
        //  - si el backend cerró la sesión -> limpiar la pantalla
        setTimeout(() => {
            if (typeof processRfidWithSessionLogic === 'function') {
                // sanitizedRfid puede ser UID de equipo o de usuario, pero
                // processRfidWithSessionLogic ignora el rfid si la sesión
                // está activa y solo refresca; si está cerrada, limpia.
                processRfidWithSessionLogic(sanitizedRfid, serialNumber);
            } else if (window.currentRfid) {
                // Fallback antiguo
                $.ajax({
                    url: '/Loan/index',
                    method: 'POST',
                    data: { consult_loan: window.currentRfid },
                    timeout: 10000,
                    success: function(data) {
                        $('#resultado_').html('');
                        const processedData = window.cortarDespuesDeDoctype ?
                            window.cortarDespuesDeDoctype(data) : data;
                        $('#resultado_').html(processedData);
                    },
                });
            }
        }, 600);
    }
    
    /**
     * Sanea el RFID eliminando prefijos
     */
    sanitizeRfid(rfidInput) {
        if (typeof rfidInput === 'string' && rfidInput.startsWith('APP:')) {
            return rfidInput.substring(4);
        }
        return rfidInput;
    }
    
    /**
     * Inicia el sistema de heartbeat
     */
    startHeartbeat() {
        this.stopHeartbeat(); // Limpiar heartbeat anterior
        
        this.heartbeatInterval = setInterval(() => {
            if (this.isConnected) {
                const heartbeatData = {
                    client: 'loan_view',
                    timestamp: Date.now(),
                    status: 'alive'
                };
                
                this.publish(this.topics.heartbeat, JSON.stringify(heartbeatData));
                
                // Notificar al watchdog de actividad de heartbeat
                if (window.connectionWatchdog) {
                    window.connectionWatchdog.state.mqttLastActivity = Date.now();
                }
                
                // Verificar si hemos recibido mensajes recientemente
                const timeSinceLastMessage = Date.now() - (this.lastHeartbeat || Date.now());
                if (timeSinceLastMessage > this.heartbeatTimer * 2) {
                    console.log('⚠️ No se han recibido mensajes recientemente, verificando conexión...');
                    this.checkConnection();
                }
            }
        }, this.heartbeatTimer);
    }
    
    /**
     * Detiene el sistema de heartbeat
     */
    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    }
    
    /**
     * Verifica el estado de la conexión
     */
    checkConnection() {
        if (this.client && this.client.connected) {
            console.log('✅ Conexión MQTT verificada');
            this.lastHeartbeat = Date.now();
        } else {
            console.log('❌ Conexión MQTT perdida, intentando reconectar...');
            this.handleDisconnection();
        }
    }
    
    /**
     * Publica un mensaje MQTT
     */
    publish(topic, payload, qos = 0) {
        if (this.isConnected && this.client) {
            try {
                this.client.publish(topic, payload, { qos }, (error) => {
                    if (error) {
                        console.error('❌ Error publicando mensaje:', error);
                    } else {
                        console.log('📤 Mensaje publicado:', topic, payload);
                    }
                });
            } catch (error) {
                console.error('❌ Error en publish:', error);
            }
        } else {
            console.warn('⚠️ No se puede publicar, MQTT no conectado');
        }
    }
    
    /**
     * Publica el estado de conexión
     */
    publishConnectionStatus(status) {
        const statusData = {
            client: 'loan_view',
            status: status,
            timestamp: Date.now(),
            url: window.location.href
        };
        
        this.publish(this.topics.status, JSON.stringify(statusData), 1);
    }
    
    /**
     * Maneja errores de conexión
     */
    handleConnectionError(errorMessage) {
        console.error('❌ Error de conexión MQTT:', errorMessage);
        this.isConnected = false;
        this.updateConnectionStatus(false, 'Error: ' + errorMessage);
        
        // Notificar al watchdog de error de conexión
        if (window.connectionWatchdog) {
            window.connectionWatchdog.state.mqttFailures++;
        }
        
        this.scheduleReconnect();
    }
    
    /**
     * Maneja desconexiones
     */
    handleDisconnection() {
        this.isConnected = false;
        this.stopHeartbeat();
        this.updateConnectionStatus(false, 'Desconectado');
        
        // Notificar al watchdog de desconexión
        if (window.connectionWatchdog) {
            window.connectionWatchdog.state.mqttFailures++;
        }
        
        this.scheduleReconnect();
    }
    
    /**
     * Programa una reconexión
     */
    scheduleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('❌ Máximo número de intentos de reconexión alcanzado');
            this.updateConnectionStatus(false, 'Reconexión fallida');
            return;
        }
        
        this.reconnectAttempts++;
        const delay = Math.min(this.reconnectInterval * this.reconnectAttempts, 30000); // Max 30 segundos
        
        console.log(`🔄 Programando reconexión en ${delay}ms (intento ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
        
        setTimeout(() => {
            if (!this.isConnected) {
                this.connect();
            }
        }, delay);
    }
    
    /**
     * Actualiza el estado de conexión en la UI
     */
    updateConnectionStatus(connected, message = null) {
        const statusElement = document.getElementById('mqtt_status');
        if (statusElement) {
            if (connected) {
                statusElement.innerHTML = '<span class="badge badge-success">MQTT Conectado</span>';
            } else {
                const statusText = message || 'Desconectado';
                statusElement.innerHTML = `<span class="badge badge-warning">MQTT ${statusText}</span>`;
            }
        }
        
        // Actualizar variable global para compatibilidad
        window.connected = connected;
    }
    
    /**
     * Desconecta el cliente MQTT
     */
    disconnect() {
        console.log('🔌 Desconectando cliente MQTT...');
        
        this.stopHeartbeat();
        
        if (this.client && this.isConnected) {
            this.publishConnectionStatus('offline');
            this.client.end(true);
        }
        
        this.isConnected = false;
        this.updateConnectionStatus(false, 'Desconectado manualmente');
    }
    
    /**
     * Obtiene el estado de la conexión
     */
    getConnectionStatus() {
        return {
            connected: this.isConnected,
            reconnectAttempts: this.reconnectAttempts,
            lastHeartbeat: this.lastHeartbeat,
            brokerUrl: this.brokerUrl
        };
    }
}

// Inicializar cliente MQTT mejorado cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar en la página de préstamos
    if (window.location.pathname.includes('/Loan') && !window.location.pathname.includes('/Dashboard')) {
        console.log('🚀 Inicializando cliente MQTT mejorado para préstamos...');
        
        // Desconectar cliente anterior si existe
        if (window.client && typeof window.client.end === 'function') {
            window.client.end(true);
        }
        
        // Crear nueva instancia del cliente mejorado
        window.loanMqttClient = new LoanMQTTClient();
        
        // Mantener compatibilidad con código existente
        window.client = window.loanMqttClient.client;
        window.connected = false;
        
        // Función para obtener estado del cliente
        window.getMqttStatus = function() {
            return window.loanMqttClient.getConnectionStatus();
        };
        
        // Función para reconectar manualmente
        window.reconnectMqtt = function() {
            console.log('🔄 Reconexión manual solicitada...');
            window.loanMqttClient.connect();
        };
        
        console.log('✅ Cliente MQTT mejorado inicializado');
    }
});

// Limpiar al salir de la página
window.addEventListener('beforeunload', function() {
    if (window.loanMqttClient) {
        window.loanMqttClient.disconnect();
    }
});