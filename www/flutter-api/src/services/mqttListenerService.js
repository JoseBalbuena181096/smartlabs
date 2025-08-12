const mqtt = require('mqtt');
const prestamoService = require('./prestamoService');
require('dotenv').config();

/**
 * Servicio MQTT Listener para manejar peticiones del hardware
 * Replica la funcionalidad del servidor IoT Node.js anterior
 * Responde a todas las peticiones MQTT del hardware main_usuariosLV2.cpp
 */
class MQTTListenerService {
    constructor() {
        this.mqttClient = null;
        this.isListening = false;
        // 🔄 SINCRONIZACIÓN: Estado local mantenido sincronizado con prestamoService
        this.serialLoanUser = null;
        this.countLoanCard = 0;
        
        // Configuración MQTT
        this.mqttOptions = {
            host: process.env.MQTT_HOST || process.env.SERVER_HOST || '192.168.0.100',
            port: process.env.MQTT_PORT || 1883,
            username: process.env.MQTT_USERNAME || 'jose',
            password: process.env.MQTT_PASSWORD || 'public',
            clientId: `flutter_api_mqtt_listener_${Math.round(Math.random() * 10000)}`,
            clean: true,
            connectTimeout: 4000,
            reconnectPeriod: 1000
        };
    }

    /**
     * Inicia el listener MQTT
     * @returns {Promise<boolean>} - True si se inició correctamente
     */
    async startListening() {
        try {
            console.log('🎧 Iniciando MQTT Listener para hardware...');
            
            // Conectar al broker MQTT
            this.mqttClient = mqtt.connect(`mqtt://${this.mqttOptions.host}`, this.mqttOptions);
            
            return new Promise((resolve, reject) => {
                this.mqttClient.on('connect', () => {
                    console.log('✅ MQTT Listener conectado al broker');
                    
                    // Suscribirse a todos los tópicos de consultas del hardware
                    // Patrón: SMART*/loan_queryu (consultas de usuario)
                    // Patrón: SMART*/loan_querye (consultas de equipo)
                    // Patrón: SMART*/access_query (consultas de acceso)
                    // Patrón: SMART*/scholar_query (consultas de becarios)
                    this.mqttClient.subscribe('+/loan_queryu', (err) => {
                        if (err) {
                            console.error('❌ Error suscribiéndose a loan_queryu:', err);
                            reject(err);
                        } else {
                            console.log('📥 Suscrito a +/loan_queryu');
                        }
                    });
                    
                    this.mqttClient.subscribe('+/loan_querye', (err) => {
                        if (err) {
                            console.error('❌ Error suscribiéndose a loan_querye:', err);
                            reject(err);
                        } else {
                            console.log('📥 Suscrito a +/loan_querye');
                        }
                    });
                    
                    this.mqttClient.subscribe('+/access_query', (err) => {
                        if (err) {
                            console.error('❌ Error suscribiéndose a access_query:', err);
                            reject(err);
                        } else {
                            console.log('📥 Suscrito a +/access_query');
                        }
                    });
                    
                    this.mqttClient.subscribe('+/scholar_query', (err) => {
                        if (err) {
                            console.error('❌ Error suscribiéndose a scholar_query:', err);
                            reject(err);
                        } else {
                            console.log('📥 Suscrito a +/scholar_query');
                        }
                    });
                    
                    // Suscribirse a datos de sensores
                    this.mqttClient.subscribe('values', (err) => {
                        if (err) {
                            console.error('❌ Error suscribiéndose a values:', err);
                        } else {
                            console.log('📥 Suscrito a values (datos de sensores)');
                        }
                    });
                    
                    this.isListening = true;
                    resolve(true);
                });
                
                this.mqttClient.on('message', (topic, message) => {
                    this.handleMQTTMessage(topic, message);
                });
                
                this.mqttClient.on('error', (error) => {
                    console.error('❌ Error MQTT Listener:', error);
                    reject(error);
                });
                
                this.mqttClient.on('close', () => {
                    console.log('⚠️ Conexión MQTT Listener cerrada');
                    this.isListening = false;
                });
                
                this.mqttClient.on('reconnect', () => {
                    console.log('🔄 Reconectando MQTT Listener...');
                });
            });
            
        } catch (error) {
            console.error('❌ Error iniciando MQTT Listener:', error);
            return false;
        }
    }

    /**
     * Maneja mensajes MQTT recibidos del hardware
     * @param {string} topic - Tópico MQTT
     * @param {Buffer} message - Mensaje recibido
     */
    async handleMQTTMessage(topic, message) {
        try {
            const messageStr = message.toString().trim();
            console.log(`📨 [MQTT Listener] Mensaje recibido desde -> ${topic} Mensaje -> ${messageStr}`);
            
            const topicParts = topic.split("/");
            const serialNumber = topicParts[0]; // Ej: SMART10003
            const query = topicParts[1]; // Ej: loan_queryu, loan_querye, etc.
            
            switch (query) {
                case 'loan_queryu':
                    await this.handleLoanUserQuery(serialNumber, messageStr);
                    break;
                case 'loan_querye':
                    await this.handleLoanEquipmentQuery(serialNumber, messageStr);
                    break;
                case 'access_query':
                    await this.handleAccessQuery(serialNumber, messageStr);
                    break;
                case 'scholar_query':
                    await this.handleScholarQuery(serialNumber, messageStr);
                    break;
                default:
                    if (topic === 'values') {
                        await this.handleSensorData(messageStr);
                    } else {
                        console.log(`⚠️ Tópico no manejado: ${topic}`);
                    }
                    break;
            }
        } catch (error) {
            console.error('❌ Error procesando mensaje MQTT:', error);
        }
    }

    /**
     * Maneja consultas de usuario para préstamos (loan_queryu)
     * Replica exactamente la funcionalidad del servidor IoT Node.js anterior
     * SINCRONIZADO con prestamoService para mantener estado consistente
     * @param {string} serialNumber - Número de serie del dispositivo
     * @param {string} rfidNumber - Número RFID del usuario
     */
    async handleLoanUserQuery(serialNumber, rfidNumber) {
        try {
            console.log(`🔍 [Loan User Query] Dispositivo: ${serialNumber}, RFID: ${rfidNumber}`);
            
            // 🚫 FILTRO: Ignorar mensajes que vienen de la app (con prefijo "APP:")
            if (rfidNumber.startsWith('APP:')) {
                console.log(`ℹ️ [MQTT Listener] Mensaje ignorado - viene de la app: ${rfidNumber}`);
                return;
            }
            
            // 🔄 SINCRONIZACIÓN: Usar el método del prestamoService que maneja el estado centralizado
            const result = await prestamoService.handleLoanUserQuery(serialNumber, rfidNumber);
            
            if (result.success) {
                // 🔄 SINCRONIZACIÓN: Actualizar estado local con el estado del prestamoService
                const sessionState = prestamoService.getSessionState();
                this.countLoanCard = sessionState.count;
                this.serialLoanUser = sessionState.active ? [{ hab_name: sessionState.user }] : null;
                
                console.log(`✅ [MQTT Listener] Estado sincronizado: countLoanCard=${this.countLoanCard}, usuario=${sessionState.user}`);
                console.log(`✅ ${result.message}`);
            } else {
                console.log(`❌ ${result.message}`);
            }
        } catch (error) {
            console.error('❌ Error en consulta de usuario para préstamo:', error);
            await this.publishMQTTCommand(serialNumber, null, 'error');
        }
    }

    /**
     * Maneja consultas de equipo para préstamos (loan_querye)
     * Replica exactamente la funcionalidad del servidor IoT Node.js anterior
     * SINCRONIZADO con prestamoService para mantener estado consistente
     * @param {string} serialNumber - Número de serie del dispositivo
     * @param {string} rfidNumber - Número RFID del equipo
     */
    async handleLoanEquipmentQuery(serialNumber, rfidNumber) {
        try {
            console.log(`🔍 [Loan Equipment Query] Dispositivo: ${serialNumber}, RFID Equipo: ${rfidNumber}`);
            
            // 🔄 SINCRONIZACIÓN: Verificar estado desde prestamoService
            const sessionState = prestamoService.getSessionState();
            if (!sessionState.active) {
                await this.publishMQTTCommand(serialNumber, null, 'nologin');
                console.log('⚠️ No hay usuario logueado para préstamo (verificado desde prestamoService)');
                return;
            }
            
            // Usar el servicio de préstamos para manejar la consulta de equipo
            const result = await prestamoService.handleLoanEquipmentQuery(serialNumber, rfidNumber);
            
            if (result.success) {
                // El servicio ya maneja el envío de comandos MQTT
                console.log(`✅ Consulta de equipo procesada: ${result.message}`);
            } else {
                console.log(`❌ Error en consulta de equipo: ${result.message}`);
            }
        } catch (error) {
            console.error('❌ Error en consulta de equipo para préstamo:', error);
            await this.publishMQTTCommand(serialNumber, null, 'error');
        }
    }

    /**
     * Maneja consultas de acceso (access_query)
     * Replica la funcionalidad del servidor IoT Node.js anterior
     * @param {string} serialNumber - Número de serie del dispositivo
     * @param {string} rfidNumber - Número RFID del usuario
     */
    async handleAccessQuery(serialNumber, rfidNumber) {
        try {
            console.log(`🔍 [Access Query] Dispositivo: ${serialNumber}, RFID: ${rfidNumber}`);
            
            const user = await prestamoService.getUserByRFID(rfidNumber);
            
            if (user) {
                await this.publishMQTTCommand(serialNumber, user.hab_name, null);
                
                // Obtener último registro de tráfico para este dispositivo
                const lastTraffic = await this.getLastTrafficForDevice(serialNumber);
                let newTrafficState = 1; // Por defecto: entrada
                
                if (lastTraffic) {
                    newTrafficState = lastTraffic.traffic_state === 1 ? 0 : 1;
                }
                
                // Registrar nuevo tráfico
                await prestamoService.registrarTrafico(user.hab_id, serialNumber, newTrafficState);
                
                const command = newTrafficState ? 'granted1' : 'granted0';
                await this.publishMQTTCommand(serialNumber, null, command);
                
                console.log(`✅ Acceso ${newTrafficState ? 'permitido' : 'denegado'} a ${user.hab_name}`);
            } else {
                await this.publishMQTTCommand(serialNumber, null, 'refused');
                console.log('❌ Acceso denegado - RFID no encontrado');
            }
        } catch (error) {
            console.error('❌ Error en consulta de acceso:', error);
            await this.publishMQTTCommand(serialNumber, null, 'error');
        }
    }

    /**
     * Maneja consultas de becarios (scholar_query)
     * Replica la funcionalidad del servidor IoT Node.js anterior
     * @param {string} serialNumber - Número de serie del dispositivo
     * @param {string} rfidNumber - Número RFID del becario
     */
    async handleScholarQuery(serialNumber, rfidNumber) {
        try {
            console.log(`🔍 [Scholar Query] Dispositivo: ${serialNumber}, RFID: ${rfidNumber}`);
            
            const user = await prestamoService.getUserByRFID(rfidNumber);
            
            if (user) {
                await this.publishMQTTCommand(serialNumber, user.hab_name, null);
                
                // Obtener último registro específico del usuario y dispositivo
                const lastUserTraffic = await this.getLastUserTrafficForDevice(user.hab_id, serialNumber);
                let newTrafficState = 1; // Por defecto: entrada
                
                if (lastUserTraffic) {
                    newTrafficState = lastUserTraffic.traffic_state === 1 ? 0 : 1;
                }
                
                // Registrar nuevo tráfico
                await prestamoService.registrarTrafico(user.hab_id, serialNumber, newTrafficState);
                
                const command = newTrafficState ? 'granted1' : 'granted0';
                await this.publishMQTTCommand(serialNumber, null, command);
                
                console.log(`✅ Becario ${newTrafficState ? 'ingreso' : 'salida'}: ${user.hab_name}`);
            } else {
                await this.publishMQTTCommand(serialNumber, null, 'refused');
                console.log('❌ Becario no encontrado');
            }
        } catch (error) {
            console.error('❌ Error en consulta de becario:', error);
            await this.publishMQTTCommand(serialNumber, null, 'error');
        }
    }

    /**
     * Maneja datos de sensores
     * @param {string} message - Mensaje con datos de sensores
     */
    async handleSensorData(message) {
        try {
            console.log(`📊 [Sensor Data] Datos recibidos: ${message}`);
            
            const parts = message.split(',');
            if (parts.length >= 3) {
                const temp1 = parseFloat(parts[0]);
                const temp2 = parseFloat(parts[1]);
                const volts = parseFloat(parts[2]);
                
                // Insertar datos en la base de datos
                await this.insertSensorData(temp1, temp2, volts);
                
                console.log(`📊 Datos de sensores insertados: T1=${temp1}, T2=${temp2}, V=${volts}`);
            }
        } catch (error) {
            console.error('❌ Error insertando datos de sensores:', error);
        }
    }

    /**
     * Publica comandos MQTT al dispositivo
     * @param {string} serialNumber - Número de serie del dispositivo
     * @param {string|null} userName - Nombre del usuario (puede ser null)
     * @param {string|null} command - Comando a enviar (puede ser null)
     */
    async publishMQTTCommand(serialNumber, userName, command) {
        try {
            if (!this.mqttClient || !this.mqttClient.connected) {
                console.warn('⚠️ MQTT Listener no conectado');
                return;
            }
            
            // Enviar nombre del usuario si existe
            if (userName) {
                this.mqttClient.publish(`${serialNumber}/user_name`, userName);
                console.log(`📤 [MQTT Listener] Publicado en ${serialNumber}/user_name: ${userName}`);
            }
            
            // Enviar comando si existe
            if (command) {
                this.mqttClient.publish(`${serialNumber}/command`, command);
                console.log(`📤 [MQTT Listener] Publicado en ${serialNumber}/command: ${command}`);
            }
        } catch (error) {
            console.error('❌ Error publicando comando MQTT:', error);
        }
    }

    /**
     * Obtiene el último registro de tráfico para un dispositivo
     * @param {string} deviceSerie - Serie del dispositivo
     * @returns {Object|null} - Último registro de tráfico
     */
    async getLastTrafficForDevice(deviceSerie) {
        let connection = null;
        try {
            const mysql = require('mysql2/promise');
            connection = await mysql.createConnection({
                host: process.env.DB_HOST,
                user: process.env.DB_USER,
                password: process.env.DB_PASSWORD,
                database: process.env.DB_NAME,
                port: process.env.DB_PORT
            });
            
            const [rows] = await connection.execute(
                'SELECT * FROM traffic WHERE traffic_device = ? ORDER BY traffic_date DESC LIMIT 1',
                [deviceSerie]
            );
            
            return rows.length > 0 ? rows[0] : null;
        } catch (error) {
            console.error('❌ Error obteniendo último tráfico:', error);
            return null;
        } finally {
            if (connection) {
                await connection.end();
            }
        }
    }

    /**
     * Obtiene el último registro de tráfico de un usuario específico para un dispositivo
     * @param {number} userId - ID del usuario
     * @param {string} deviceSerie - Serie del dispositivo
     * @returns {Object|null} - Último registro de tráfico del usuario
     */
    async getLastUserTrafficForDevice(userId, deviceSerie) {
        let connection = null;
        try {
            const mysql = require('mysql2/promise');
            connection = await mysql.createConnection({
                host: process.env.DB_HOST,
                user: process.env.DB_USER,
                password: process.env.DB_PASSWORD,
                database: process.env.DB_NAME,
                port: process.env.DB_PORT
            });
            
            const [rows] = await connection.execute(
                'SELECT * FROM traffic WHERE traffic_hab_id = ? AND traffic_device = ? ORDER BY traffic_date DESC LIMIT 1',
                [userId, deviceSerie]
            );
            
            return rows.length > 0 ? rows[0] : null;
        } catch (error) {
            console.error('❌ Error obteniendo último tráfico del usuario:', error);
            return null;
        } finally {
            if (connection) {
                await connection.end();
            }
        }
    }

    /**
     * Inserta datos de sensores en la base de datos
     * @param {number} temp1 - Temperatura 1
     * @param {number} temp2 - Temperatura 2
     * @param {number} volts - Voltaje
     */
    async insertSensorData(temp1, temp2, volts) {
        let connection = null;
        try {
            const mysql = require('mysql2/promise');
            connection = await mysql.createConnection({
                host: process.env.DB_HOST,
                user: process.env.DB_USER,
                password: process.env.DB_PASSWORD,
                database: process.env.DB_NAME,
                port: process.env.DB_PORT
            });
            
            await connection.execute(
                'INSERT INTO data (data_temp1, data_temp2, data_volts) VALUES (?, ?, ?)',
                [temp1, temp2, volts]
            );
        } catch (error) {
            console.error('❌ Error insertando datos de sensores:', error);
        } finally {
            if (connection) {
                await connection.end();
            }
        }
    }

    /**
     * Detiene el listener MQTT
     */
    async stopListening() {
        try {
            console.log('🛑 Deteniendo MQTT Listener...');
            
            if (this.mqttClient) {
                this.mqttClient.end();
                this.mqttClient = null;
            }
            
            this.isListening = false;
            console.log('✅ MQTT Listener detenido correctamente');
        } catch (error) {
            console.error('❌ Error deteniendo MQTT Listener:', error);
        }
    }

    /**
     * Verifica si el listener está activo
     * @returns {boolean} - True si está escuchando
     */
    isActive() {
        return this.isListening && this.mqttClient && this.mqttClient.connected;
    }

    /**
     * Obtiene el estado actual de la sesión de préstamo
     * SINCRONIZADO con prestamoService para mantener estado consistente
     * @returns {Object} - Estado de la sesión
     */
    getSessionState() {
        // 🔄 SINCRONIZACIÓN: Devolver estado desde prestamoService
        const sessionState = prestamoService.getSessionState();
        
        // Actualizar estado local para mantener sincronización
        this.countLoanCard = sessionState.count;
        this.serialLoanUser = sessionState.active ? [{ hab_name: sessionState.user }] : null;
        
        return sessionState;
    }
}

module.exports = new MQTTListenerService();