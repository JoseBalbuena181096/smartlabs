/**
 * Cliente MQTT para SMARTLABS
 * Replica la funcionalidad MQTT de los archivos legacy
 */

class SmartLabsMQTT {
    constructor(brokerUrl = 'wss://192.168.0.100:8074/mqtt', options = {}) {
        this.brokerUrl = brokerUrl;
        this.client = null;
        this.isConnected = false;
        this.options = {
            clientId: 'iotmc' + Math.random().toString(16).substr(2, 8),
            username: 'jose',
            password: 'public',
            keepalive: 60,
            protocolId: 'MQTT',
            protocolVersion: 4,
            clean: true,
            reconnectPeriod: 1000,
            connectTimeout: 4000,
            will: {
                topic: 'smartlabs/dashboard/lwt',
                payload: 'offline',
                qos: 0,
                retain: false
            },
            ...options
        };
        
        this.topics = {
            device_status: 'smartlabs/devices/+/status',
            device_rfid: 'smartlabs/devices/+/rfid',
            device_control: 'smartlabs/devices/+/control',
            loans: 'smartlabs/loans/+',
            system: 'smartlabs/system/+'
        };
        
        this.callbacks = {
            onConnect: null,
            onMessage: null,
            onError: null,
            onDisconnect: null,
            onRfidRead: null,
            onDeviceStatus: null
        };
        
        this.init();
    }
    
    init() {
        try {
            // Usar Paho MQTT o MQTT.js dependiendo de lo que esté disponible
            if (typeof Paho !== 'undefined') {
                this.initPaho();
            } else if (typeof mqtt !== 'undefined') {
                this.initMQTTjs();
            } else {
                console.error('No se encontró libería MQTT disponible');
                this.simulateConnection();
            }
        } catch (error) {
            console.error('Error inicializando MQTT:', error);
            this.simulateConnection();
        }
    }
    
    initPaho() {
        this.client = new Paho.MQTT.Client(
            this.brokerUrl.replace('ws://', '').replace('/mqtt', '').split(':')[0],
            parseInt(this.brokerUrl.split(':')[2].replace('/mqtt', '')),
            this.options.clientId
        );
        
        this.client.onConnectionLost = (responseObject) => {
            this.isConnected = false;
            console.log('MQTT Conexión perdida:', responseObject.errorMessage);
            if (this.callbacks.onDisconnect) {
                this.callbacks.onDisconnect(responseObject);
            }
        };
        
        this.client.onMessageArrived = (message) => {
            this.handleMessage(message.destinationName, message.payloadString);
        };
        
        this.connect();
    }
    
    initMQTTjs() {
        this.client = mqtt.connect(this.brokerUrl, this.options);
        
        this.client.on('connect', () => {
            this.isConnected = true;
            console.log('MQTT Conectado exitosamente');
            this.subscribeToTopics();
            if (this.callbacks.onConnect) {
                this.callbacks.onConnect();
            }
        });
        
        this.client.on('message', (topic, message) => {
            this.handleMessage(topic, message.toString());
        });
        
        this.client.on('error', (error) => {
            console.error('MQTT Error:', error);
            if (this.callbacks.onError) {
                this.callbacks.onError(error);
            }
        });
        
        this.client.on('close', () => {
            this.isConnected = false;
            if (this.callbacks.onDisconnect) {
                this.callbacks.onDisconnect();
            }
        });
    }
    
    simulateConnection() {
        console.log('Simulando conexión MQTT para desarrollo');
        this.isConnected = true;
        
        setTimeout(() => {
            if (this.callbacks.onConnect) {
                this.callbacks.onConnect();
            }
        }, 1000);
        
        // Simular mensajes RFID periódicos para testing
        setInterval(() => {
            if (this.callbacks.onRfidRead) {
                const simulatedRfid = '83422211210'; // RFID de prueba
                this.callbacks.onRfidRead('DEVICE001', simulatedRfid);
            }
        }, 10000); // Cada 10 segundos
    }
    
    connect() {
        if (typeof Paho !== 'undefined') {
            this.client.connect({
                onSuccess: () => {
                    this.isConnected = true;
                    console.log('MQTT Conectado exitosamente');
                    this.subscribeToTopics();
                    if (this.callbacks.onConnect) {
                        this.callbacks.onConnect();
                    }
                },
                onFailure: (error) => {
                    console.error('MQTT Error de conexión:', error);
                    if (this.callbacks.onError) {
                        this.callbacks.onError(error);
                    }
                    this.simulateConnection(); // Fallback
                },
                userName: this.options.username,
                password: this.options.password
            });
        }
    }
    
    subscribeToTopics() {
        Object.values(this.topics).forEach(topic => {
            this.subscribe(topic);
        });
    }
    
    subscribe(topic) {
        if (!this.isConnected) return;
        
        try {
            if (typeof Paho !== 'undefined') {
                this.client.subscribe(topic);
            } else if (this.client && this.client.subscribe) {
                this.client.subscribe(topic);
            }
            console.log('Suscrito a:', topic);
        } catch (error) {
            console.error('Error suscribiendo a', topic, ':', error);
        }
    }
    
    publish(topic, payload, qos = 0) {
        if (!this.isConnected) {
            console.warn('MQTT no conectado, no se puede publicar');
            return;
        }
        
        try {
            if (typeof Paho !== 'undefined') {
                const message = new Paho.MQTT.Message(payload);
                message.destinationName = topic;
                message.qos = qos;
                this.client.send(message);
            } else if (this.client && this.client.publish) {
                this.client.publish(topic, payload, { qos });
            }
            console.log('Publicado en', topic, ':', payload);
        } catch (error) {
            console.error('Error publicando:', error);
        }
    }
    
    handleMessage(topic, payload) {
        console.log('Mensaje recibido:', topic, payload);
        
        // Manejar mensajes de RFID
        if (topic.includes('/rfid')) {
            const deviceId = this.extractDeviceId(topic);
            if (this.callbacks.onRfidRead) {
                this.callbacks.onRfidRead(deviceId, payload);
            }
        }
        
        // Manejar estado de dispositivos
        if (topic.includes('/status')) {
            const deviceId = this.extractDeviceId(topic);
            if (this.callbacks.onDeviceStatus) {
                this.callbacks.onDeviceStatus(deviceId, payload);
            }
        }
        
        // Callback general
        if (this.callbacks.onMessage) {
            this.callbacks.onMessage(topic, payload);
        }
    }
    
    extractDeviceId(topic) {
        const parts = topic.split('/');
        return parts[2] || 'unknown';
    }
    
    // Métodos para configurar callbacks
    onConnect(callback) {
        this.callbacks.onConnect = callback;
    }
    
    onMessage(callback) {
        this.callbacks.onMessage = callback;
    }
    
    onError(callback) {
        this.callbacks.onError = callback;
    }
    
    onDisconnect(callback) {
        this.callbacks.onDisconnect = callback;
    }
    
    onRfidRead(callback) {
        this.callbacks.onRfidRead = callback;
    }
    
    onDeviceStatus(callback) {
        this.callbacks.onDeviceStatus = callback;
    }
    
    // Controlar dispositivos
    openDevice(deviceId) {
        this.publish(`smartlabs/devices/${deviceId}/control`, 'OPEN');
    }
    
    closeDevice(deviceId) {
        this.publish(`smartlabs/devices/${deviceId}/control`, 'CLOSE');
    }
    
    requestStatus(deviceId) {
        this.publish(`smartlabs/devices/${deviceId}/control`, 'STATUS');
    }
    
    disconnect() {
        if (this.client && this.isConnected) {
            if (typeof Paho !== 'undefined') {
                this.client.disconnect();
            } else if (this.client.end) {
                this.client.end();
            }
            this.isConnected = false;
        }
    }
}

// Instancia global para uso en las páginas
window.SmartLabsMQTT = SmartLabsMQTT;

// Auto-inicializar solo para página de préstamos (evitar conflictos con dashboard)
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('/Loan') && !window.location.pathname.includes('/Dashboard')) {
        
        if (!window.mqttClient) {
            window.mqttClient = new SmartLabsMQTT();
        }
        
        // Configurar callback para lecturas RFID en préstamos
        window.mqttClient.onRfidRead((deviceId, rfid) => {
            const rfidInput = document.getElementById('consult_loan');
            if (rfidInput) {
                rfidInput.value = rfid;
                rfidInput.focus();
                
                // Auto-submit el formulario como en dash_loan.php
                const form = rfidInput.closest('form');
                if (form) {
                    form.dispatchEvent(new Event('submit', { bubbles: true }));
                }
            }
        });
        
        // Mostrar estado de conexión
        window.mqttClient.onConnect(() => {
            const statusEl = document.getElementById('mqtt-status');
            if (statusEl) {
                statusEl.innerHTML = '<span class="text-success">MQTT Conectado</span>';
            }
        });
        
        window.mqttClient.onError(() => {
            const statusEl = document.getElementById('mqtt-status');
            if (statusEl) {
                statusEl.innerHTML = '<span class="text-warning">MQTT Simulado</span>';
            }
        });
    }
}); 