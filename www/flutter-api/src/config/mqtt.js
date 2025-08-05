const mqtt = require('mqtt');
require('dotenv').config();

/**
 * Configuración y cliente MQTT para SMARTLABS Flutter API
 */
class MQTTConfig {
    constructor() {
        if (MQTTConfig.instance) {
            return MQTTConfig.instance;
        }
        
        this.client = null;
        this.isConnecting = false;
        this.messageHandlers = new Map(); // Para manejar múltiples callbacks
        this.connectionAttempts = 0;
        this.maxConnectionAttempts = 5;
        this.lastConnectionTime = null;
        
        this.options = {
            host: process.env.MQTT_HOST || 'localhost',
            port: parseInt(process.env.MQTT_PORT) || 1883,
            username: process.env.MQTT_USERNAME || '',
            password: process.env.MQTT_PASSWORD || '',
            clientId: `smartlabs_api_${Date.now()}_${Math.random().toString(16).substr(2, 8)}`,
            clean: true,
            connectTimeout: parseInt(process.env.MQTT_CONNECT_TIMEOUT) || 30000,
            reconnectPeriod: parseInt(process.env.MQTT_RECONNECT_PERIOD) || 5000,
            keepalive: parseInt(process.env.MQTT_KEEPALIVE) || 60
        };
        
        MQTTConfig.instance = this;
    }
    
    /**
     * Obtiene la instancia singleton
     */
    static getInstance() {
        if (!MQTTConfig.instance) {
            MQTTConfig.instance = new MQTTConfig();
        }
        return MQTTConfig.instance;
    }

    /**
     * Conecta al broker MQTT con manejo mejorado de reconexiones
     */
    async connect() {
        return new Promise((resolve, reject) => {
            try {
                // Evitar múltiples conexiones simultáneas
                if (this.isConnecting) {
                    console.log('⏳ Conexión MQTT ya en progreso...');
                    return resolve(this.client);
                }
                
                if (this.client && this.client.connected) {
                    console.log('✅ MQTT ya conectado');
                    return resolve(this.client);
                }
                
                // Verificar límite de intentos de conexión
                if (this.connectionAttempts >= this.maxConnectionAttempts) {
                    const error = new Error(`Máximo de intentos de conexión MQTT alcanzado (${this.maxConnectionAttempts})`);
                    console.error('❌', error.message);
                    return reject(error);
                }
                
                this.isConnecting = true;
                this.connectionAttempts++;
                
                console.log(`🔌 Conectando a MQTT broker (intento ${this.connectionAttempts}/${this.maxConnectionAttempts})...`);
                
                // Cerrar conexión anterior si existe
                if (this.client) {
                    this.client.removeAllListeners();
                    this.client.end(true);
                }
                
                this.client = mqtt.connect(`mqtt://${this.options.host}`, this.options);
                
                // Timeout para la conexión
                const connectionTimeout = setTimeout(() => {
                    this.isConnecting = false;
                    if (this.client) {
                        this.client.end(true);
                    }
                    reject(new Error('Timeout de conexión MQTT'));
                }, this.options.connectTimeout);

                this.client.on('connect', () => {
                    clearTimeout(connectionTimeout);
                    this.isConnecting = false;
                    this.connectionAttempts = 0; // Reset en conexión exitosa
                    this.lastConnectionTime = new Date();
                    console.log('✅ Conectado a MQTT broker');
                    
                    // Configurar manejador centralizado de mensajes
                    this.client.on('message', (topic, message) => {
                        // Buscar handlers que coincidan con el tópico
                        for (const [topicPattern, callback] of this.messageHandlers) {
                            if (this.topicMatches(topic, topicPattern)) {
                                try {
                                    callback(topic, message);
                                } catch (error) {
                                    console.error(`❌ Error en callback para ${topic}:`, error);
                                }
                            }
                        }
                    });
                    
                    resolve(this.client);
                });

                this.client.on('error', (error) => {
                    clearTimeout(connectionTimeout);
                    this.isConnecting = false;
                    console.error('❌ Error MQTT:', error.message);
                    reject(error);
                });

                this.client.on('close', () => {
                    this.isConnecting = false;
                    console.log('🔌 Conexión MQTT cerrada');
                });

                this.client.on('reconnect', () => {
                    console.log('🔄 Reconectando a MQTT...');
                });
                
                this.client.on('offline', () => {
                    console.log('📴 MQTT offline');
                });

            } catch (error) {
                this.isConnecting = false;
                console.error('❌ Error al conectar MQTT:', error);
                reject(error);
            }
        });
    }

    /**
     * Publica un mensaje en un tópico
     */
    publish(topic, message) {
        if (!this.client || !this.client.connected) {
            throw new Error('Cliente MQTT no conectado');
        }
        
        return new Promise((resolve, reject) => {
            this.client.publish(topic, message, (error) => {
                if (error) {
                    console.error(`❌ Error publicando en ${topic}:`, error);
                    reject(error);
                } else {
                    console.log(`📤 Mensaje publicado en ${topic}: ${message}`);
                    resolve();
                }
            });
        });
    }

    /**
     * Se suscribe a un tópico
     */
    subscribe(topic, callback) {
        if (!this.client || !this.client.connected) {
            throw new Error('Cliente MQTT no conectado');
        }
        
        this.client.subscribe(topic, (error) => {
            if (error) {
                console.error(`❌ Error suscribiéndose a ${topic}:`, error);
            } else {
                console.log(`📥 Suscrito a ${topic}`);
            }
        });
        
        if (callback) {
            // Registrar el callback para este tópico
            this.messageHandlers.set(topic, callback);
        }
    }
    
    /**
     * Verifica si un tópico coincide con un patrón (soporta wildcards + y #)
     */
    topicMatches(topic, pattern) {
        // Convertir patrón MQTT a regex
        const regexPattern = pattern
            .replace(/\+/g, '[^/]+')  // + coincide con cualquier cosa excepto /
            .replace(/#/g, '.*');     // # coincide con cualquier cosa
        
        const regex = new RegExp(`^${regexPattern}$`);
        return regex.test(topic);
    }

    /**
     * Obtiene el cliente MQTT
     */
    getClient() {
        return this.client;
    }

    /**
     * Cierra la conexión MQTT de forma segura
     */
    async close() {
        return new Promise((resolve) => {
            if (this.client) {
                console.log('🔌 Cerrando conexión MQTT...');
                
                // Remover todos los listeners para evitar memory leaks
                this.client.removeAllListeners();
                
                // Cerrar conexión con timeout
                const closeTimeout = setTimeout(() => {
                    console.log('⚠️ Timeout al cerrar MQTT, forzando cierre');
                    this.client = null;
                    this.isConnecting = false;
                    resolve();
                }, 5000);
                
                this.client.end(false, {}, () => {
                    clearTimeout(closeTimeout);
                    this.client = null;
                    this.isConnecting = false;
                    this.connectionAttempts = 0;
                    console.log('✅ Conexión MQTT cerrada correctamente');
                    resolve();
                });
            } else {
                resolve();
            }
        });
    }

    /**
     * Verifica si está conectado
     */
    isConnected() {
        return this.client && this.client.connected && !this.isConnecting;
    }
    
    /**
     * Obtiene estadísticas de la conexión MQTT
     */
    getConnectionStats() {
        return {
            connected: this.isConnected(),
            connecting: this.isConnecting,
            connectionAttempts: this.connectionAttempts,
            maxConnectionAttempts: this.maxConnectionAttempts,
            lastConnectionTime: this.lastConnectionTime,
            clientId: this.options.clientId,
            host: this.options.host,
            port: this.options.port,
            activeHandlers: this.messageHandlers.size
        };
    }
    
    /**
     * Resetea los intentos de conexión
     */
    resetConnectionAttempts() {
        this.connectionAttempts = 0;
        console.log('🔄 Intentos de conexión MQTT reseteados');
    }
}

module.exports = new MQTTConfig();