const mqtt = require('mqtt');
require('dotenv').config();

/**
 * Configuración y cliente MQTT para SMARTLABS Flutter API
 */
class MQTTConfig {
    constructor() {
        this.client = null;
        this.messageHandlers = new Map(); // Para manejar múltiples callbacks
        this.options = {
            host: process.env.MQTT_HOST,
            port: process.env.MQTT_PORT,
            username: process.env.MQTT_USERNAME,
            password: process.env.MQTT_PASSWORD,
            clientId: `flutter_api_${Math.round(Math.random() * 10000)}`,
            clean: true,
            connectTimeout: 4000,
            reconnectPeriod: 1000
        };
    }

    /**
     * Conecta al broker MQTT
     */
    async connect() {
        return new Promise((resolve, reject) => {
            console.log('📡 Conectando a broker MQTT...');
            
            this.client = mqtt.connect(`mqtt://${this.options.host}`, this.options);
            
            this.client.on('connect', () => {
                console.log('✅ Conexión MQTT exitosa');
                
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
                console.error('❌ Error MQTT:', error);
                reject(error);
            });
            
            this.client.on('close', () => {
                console.log('⚠️ Conexión MQTT cerrada');
            });
            
            this.client.on('reconnect', () => {
                console.log('🔄 Reconectando MQTT...');
            });
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
     * Cierra la conexión MQTT
     */
    async close() {
        if (this.client) {
            this.client.end();
            console.log('📡 Conexión MQTT cerrada');
        }
    }

    /**
     * Verifica si está conectado
     */
    isConnected() {
        return this.client && this.client.connected;
    }
}

module.exports = new MQTTConfig();