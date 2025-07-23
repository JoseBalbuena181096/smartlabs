const mysql = require('mysql2/promise');
const mqtt = require('mqtt');
const dbConfig = require('../../config/database');
const mqttConfig = require('../../config/mqtt');

/**
 * Servidor IoT MQTT refactorizado para SMARTLABS
 * Maneja control de acceso, préstamos de equipos y datos de sensores
 */
class IoTMQTTServer {
    constructor() {
        this.dbConnection = null;
        this.mqttClient = null;
        this.serialLoanUser = null;
        this.countLoanCard = 0;
        
        // Configuración de base de datos desde archivo centralizado
        this.dbConfig = dbConfig.primary;
        this.fallbackDbConfig = dbConfig.fallback;
        
        // Configuración MQTT desde archivo centralizado
        this.mqttOptions = {
            ...mqttConfig.client,
            clientId: mqttConfig.client.clientIdPrefix + Math.round(Math.random() * 10000),
            host: mqttConfig.broker.host,
            port: mqttConfig.broker.port,
            username: mqttConfig.broker.username,
            password: mqttConfig.broker.password
        };
    }
    
    /**
     * Inicializa el servidor
     */
    async init() {
        try {
            console.log('🚀 Iniciando servidor IoT MQTT...');
            
            await this.connectToDatabase();
            await this.connectToMQTT();
            
            // Configurar mantenimiento de conexión DB
            this.setupDatabaseMaintenance();
            
            console.log('✅ Servidor IoT MQTT iniciado correctamente');
        } catch (error) {
            console.error('❌ Error iniciando servidor:', error);
            process.exit(1);
        }
    }
    
    /**
     * Conecta a la base de datos MySQL
     */
    async connectToDatabase() {
        try {
            console.log('🔌 Conectando a base de datos...');
            this.dbConnection = await mysql.createConnection(this.dbConfig);
            
            // Probar conexión
            await this.dbConnection.execute('SELECT 1');
            console.log('✅ Conexión a MySQL exitosa');
            
            // Configurar manejo de errores
            this.dbConnection.on('error', async (err) => {
                console.error('❌ Error de base de datos:', err);
                if (err.code === 'PROTOCOL_CONNECTION_LOST' || err.code === 'ECONNRESET') {
                    console.log('🔄 Reconectando a la base de datos...');
                    await this.reconnectDatabase();
                }
            });
            
        } catch (error) {
            console.error('❌ Error conectando a base de datos:', error);
            throw error;
        }
    }
    
    /**
     * Reconecta a la base de datos
     */
    async reconnectDatabase() {
        try {
            if (this.dbConnection) {
                await this.dbConnection.end();
            }
            await this.connectToDatabase();
        } catch (error) {
            console.error('❌ Error en reconexión de base de datos:', error);
            setTimeout(() => this.reconnectDatabase(), 5000);
        }
    }
    
    /**
     * Conecta al broker MQTT
     */
    async connectToMQTT() {
        return new Promise((resolve, reject) => {
            console.log('📡 Conectando a broker MQTT...');
            
            this.mqttClient = mqtt.connect(`mqtt://${this.mqttOptions.host}`, this.mqttOptions);
            
            this.mqttClient.on('connect', () => {
                console.log('✅ Conexión MQTT exitosa');
                
                this.mqttClient.subscribe('+/#', (err) => {
                    if (err) {
                        console.error('❌ Error en suscripción MQTT:', err);
                        reject(err);
                    } else {
                        console.log('✅ Suscripción MQTT exitosa');
                        resolve();
                    }
                });
            });
            
            this.mqttClient.on('message', (topic, message) => {
                this.handleMQTTMessage(topic, message);
            });
            
            this.mqttClient.on('error', (error) => {
                console.error('❌ Error MQTT:', error);
                reject(error);
            });
            
            this.mqttClient.on('close', () => {
                console.log('⚠️ Conexión MQTT cerrada');
            });
        });
    }
    
    /**
     * Maneja mensajes MQTT recibidos
     */
    async handleMQTTMessage(topic, message) {
        try {
            const messageStr = message.toString();
            console.log(`📨 Mensaje recibido desde -> ${topic} Mensaje -> ${messageStr}`);
            
            const topicParts = topic.split("/");
            const serialNumber = topicParts[0];
            const query = topicParts[1];
            
            switch (query) {
                case 'access_query':
                    await this.handleAccessQuery(serialNumber, messageStr);
                    break;
                case 'scholar_query':
                    await this.handleScholarQuery(serialNumber, messageStr);
                    break;
                case 'loan_queryu':
                    await this.handleLoanUserQuery(serialNumber, messageStr);
                    break;
                case 'loan_querye':
                    await this.handleLoanEquipmentQuery(serialNumber, messageStr);
                    break;
                default:
                    if (topic === 'values') {
                        await this.handleSensorData(messageStr);
                    }
                    break;
            }
        } catch (error) {
            console.error('❌ Error procesando mensaje MQTT:', error);
        }
    }
    
    /**
     * Maneja consultas de acceso
     */
    async handleAccessQuery(serialNumber, rfidNumber) {
        try {
            const [cards] = await this.dbConnection.execute(
                'SELECT * FROM cards_habs WHERE cards_number = ?',
                [rfidNumber]
            );
            
            if (cards.length === 1) {
                const card = cards[0];
                this.mqttClient.publish(`${serialNumber}/user_name`, card.hab_name);
                
                // Obtener último registro de tráfico
                const [traffic] = await this.dbConnection.execute(
                    'SELECT * FROM traffic WHERE traffic_device = ? ORDER BY traffic_date DESC LIMIT 1',
                    [serialNumber]
                );
                
                let newTrafficState = 1;
                
                if (traffic.length > 0) {
                    newTrafficState = traffic[0].traffic_state === 1 ? 0 : 1;
                }
                
                // Insertar nuevo registro de tráfico
                await this.dbConnection.execute(
                    'INSERT INTO traffic (traffic_date, traffic_hab_id, traffic_device, traffic_state) VALUES (CURRENT_TIMESTAMP, ?, ?, ?)',
                    [card.hab_id, serialNumber, newTrafficState]
                );
                
                const command = newTrafficState ? 'granted1' : 'granted0';
                this.mqttClient.publish(`${serialNumber}/command`, command);
                
                console.log(`✅ Acceso ${newTrafficState ? 'permitido' : 'denegado'} a ${card.hab_name}`);
            } else {
                this.mqttClient.publish(`${serialNumber}/command`, 'refused');
                console.log('❌ Acceso denegado - RFID no encontrado');
            }
        } catch (error) {
            console.error('❌ Error en consulta de acceso:', error);
            this.mqttClient.publish(`${serialNumber}/command`, 'error');
        }
    }
    
    /**
     * Maneja consultas de becarios
     */
    async handleScholarQuery(serialNumber, rfidNumber) {
        try {
            const [cards] = await this.dbConnection.execute(
                'SELECT * FROM cards_habs WHERE cards_number = ?',
                [rfidNumber]
            );
            
            if (cards.length === 1) {
                const card = cards[0];
                this.mqttClient.publish(`${serialNumber}/user_name`, card.hab_name);
                
                // Obtener último registro específico del usuario y dispositivo
                const [traffic] = await this.dbConnection.execute(
                    'SELECT * FROM traffic WHERE traffic_hab_id = ? AND traffic_device = ? ORDER BY traffic_date DESC LIMIT 1',
                    [card.hab_id, serialNumber]
                );
                
                let newTrafficState = 1;
                
                if (traffic.length > 0) {
                    newTrafficState = traffic[0].traffic_state === 1 ? 0 : 1;
                }
                
                // Insertar nuevo registro
                await this.dbConnection.execute(
                    'INSERT INTO traffic (traffic_date, traffic_hab_id, traffic_device, traffic_state) VALUES (CURRENT_TIMESTAMP, ?, ?, ?)',
                    [card.hab_id, serialNumber, newTrafficState]
                );
                
                const command = newTrafficState ? 'granted1' : 'granted0';
                this.mqttClient.publish(`${serialNumber}/command`, command);
                
                console.log(`✅ Becario ${newTrafficState ? 'ingreso' : 'salida'}: ${card.hab_name}`);
            } else {
                this.mqttClient.publish(`${serialNumber}/command`, 'refused');
                console.log('❌ Becario no encontrado');
            }
        } catch (error) {
            console.error('❌ Error en consulta de becario:', error);
            this.mqttClient.publish(`${serialNumber}/command`, 'error');
        }
    }
    
    /**
     * Maneja consultas de usuario para préstamos
     * Sincronizado con el API de Flutter para evitar duplicación
     */
    async handleLoanUserQuery(serialNumber, rfidNumber) {
        try {
            const [cards] = await this.dbConnection.execute(
                'SELECT * FROM cards_habs WHERE cards_number = ?',
                [rfidNumber]
            );
            
            if (cards.length === 1) {
                if (this.countLoanCard === 1) {
                    // Solo enviar unload una vez
                    this.mqttClient.publish(`${serialNumber}/command`, 'unload');
                    this.countLoanCard = 0;
                    this.serialLoanUser = null;
                    console.log('🔄 Sesión de préstamo reiniciada');
                } else {
                    // Solo enviar found una vez
                    this.mqttClient.publish(`${serialNumber}/user_name`, cards[0].hab_name);
                    this.mqttClient.publish(`${serialNumber}/command`, 'found');
                    this.serialLoanUser = cards;
                    this.countLoanCard = 1; // Cambio: asignar 1 directamente en lugar de incrementar
                    console.log(`✅ Usuario encontrado para préstamo: ${cards[0].hab_name}`);
                }
            } else {
                this.mqttClient.publish(`${serialNumber}/command`, 'nofound');
                console.log('❌ Usuario no encontrado para préstamo');
            }
        } catch (error) {
            console.error('❌ Error en consulta de usuario para préstamo:', error);
            this.mqttClient.publish(`${serialNumber}/command`, 'error');
        }
    }
    
    /**
     * Maneja consultas de equipo para préstamos
     */
    async handleLoanEquipmentQuery(serialNumber, rfidNumber) {
        try {
            if (this.countLoanCard === 0 || this.serialLoanUser === null) {
                this.mqttClient.publish(`${serialNumber}/command`, 'nologin');
                console.log('⚠️ No hay usuario logueado para préstamo');
                return;
            }
            
            const [equipment] = await this.dbConnection.execute(
                'SELECT * FROM equipments WHERE equipments_rfid = ?',
                [rfidNumber]
            );
            
            if (equipment.length === 1) {
                const equip = equipment[0];
                this.mqttClient.publish(`${serialNumber}/user_name`, equip.equipments_name);
                
                // Obtener último préstamo del equipo
                const [loans] = await this.dbConnection.execute(
                    'SELECT * FROM loans WHERE loans_equip_rfid = ? ORDER BY loans_date DESC LIMIT 1',
                    [equip.equipments_rfid]
                );
                
                let newLoanState = 1; // Por defecto: prestado
                
                if (loans.length > 0) {
                    newLoanState = loans[0].loans_state === 1 ? 0 : 1;
                }
                
                // Insertar nuevo registro de préstamo
                await this.dbConnection.execute(
                    'INSERT INTO loans (loans_date, loans_hab_rfid, loans_equip_rfid, loans_state) VALUES (CURRENT_TIMESTAMP, ?, ?, ?)',
                    [this.serialLoanUser[0].cards_number, equip.equipments_rfid, newLoanState]
                );
                
                const command = newLoanState === 1 ? 'prestado' : 'devuelto';
                this.mqttClient.publish(`${serialNumber}/command`, command);
                
                console.log(`✅ Equipo ${command}: ${equip.equipments_name}`);
            } else {
                this.mqttClient.publish(`${serialNumber}/command`, 'nofound');
                console.log('❌ Equipo no encontrado');
            }
        } catch (error) {
            console.error('❌ Error en consulta de equipo para préstamo:', error);
            this.mqttClient.publish(`${serialNumber}/command`, 'error');
        }
    }
    
    /**
     * Maneja datos de sensores
     */
    async handleSensorData(message) {
        try {
            const parts = message.split(',');
            if (parts.length >= 3) {
                const temp1 = parseFloat(parts[0]);
                const temp2 = parseFloat(parts[1]);
                const volts = parseFloat(parts[2]);
                
                await this.dbConnection.execute(
                    'INSERT INTO data (data_temp1, data_temp2, data_volts) VALUES (?, ?, ?)',
                    [temp1, temp2, volts]
                );
                
                console.log(`📊 Datos de sensores insertados: T1=${temp1}, T2=${temp2}, V=${volts}`);
            }
        } catch (error) {
            console.error('❌ Error insertando datos de sensores:', error);
        }
    }
    
    /**
     * Configura el mantenimiento de la conexión de base de datos
     */
    setupDatabaseMaintenance() {
        setInterval(async () => {
            try {
                await this.dbConnection.execute('SELECT 1');
                console.log('💓 Ping a base de datos - OK');
                console.log('👤 Usuario de préstamo actual:', this.serialLoanUser ? this.serialLoanUser[0].hab_name : 'Ninguno');
            } catch (error) {
                console.error('❌ Error en ping de base de datos:', error);
                await this.reconnectDatabase();
            }
        }, 5000);
    }
    
    /**
     * Cierra las conexiones del servidor
     */
    async shutdown() {
        console.log('🛑 Cerrando servidor IoT MQTT...');
        
        if (this.mqttClient) {
            this.mqttClient.end();
        }
        
        if (this.dbConnection) {
            await this.dbConnection.end();
        }
        
        console.log('✅ Servidor cerrado correctamente');
    }
}

// Exportar la clase para uso en otros módulos
module.exports = IoTMQTTServer;