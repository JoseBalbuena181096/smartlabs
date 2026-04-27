const mqttConfig = require('../config/mqtt');
const dbConfig = require('../config/database');
const prestamoService = require('./prestamoService');

/**
 * Listener MQTT para mensajes del hardware.
 *
 * Cambios respecto a la versión anterior:
 *   - BE-D: usa mqttConfig singleton para suscribir y publicar (antes
 *     abría su propio mqtt.connect aparte de prestamoService).
 *   - BE-E: se suscribe a +/status para registrar online/offline en la
 *     tabla device_status (migración 003) — viene del Last Will Testament
 *     que el firmware nuevo publica en {SN}/status retained.
 *   - BE-F: usa dbConfig.execute (pool) en vez de mysql.createConnection
 *     por método.
 *   - BE-H: handleSensorData eliminado (handler huérfano: el firmware no
 *     publica al topic 'values' y el backend no consumía nada útil).
 */
class MQTTListenerService {
    constructor() {
        this.isListening = false;
    }

    /**
     * Registra todas las suscripciones del listener sobre el cliente
     * MQTT compartido. Asume que mqttConfig.connect() ya fue llamado.
     */
    async startListening() {
        if (!mqttConfig.isConnected()) {
            console.error('❌ MQTT singleton no conectado. Llamar mqttConfig.connect() primero.');
            return false;
        }

        const handler = (topic, message) => this.handleMQTTMessage(topic, message);

        mqttConfig.subscribe('+/loan_queryu',  handler);
        mqttConfig.subscribe('+/loan_querye',  handler);
        mqttConfig.subscribe('+/access_query', handler);
        mqttConfig.subscribe('+/scholar_query', handler);
        mqttConfig.subscribe('+/status',       handler); // LWT online/offline

        this.isListening = true;
        console.log('🎧 MQTT Listener registrado en topics +/{loan_queryu,loan_querye,access_query,scholar_query,status}');
        return true;
    }

    async stopListening() {
        // Las suscripciones quedan registradas en el singleton hasta que
        // alguien las quite explícitamente. Para fines del listener,
        // basta marcarlo inactivo y no procesar más mensajes.
        this.isListening = false;
        console.log('🛑 MQTT Listener detenido (sigue suscrito al broker hasta cerrar app).');
    }

    isActive() {
        return this.isListening && mqttConfig.isConnected();
    }

    async getSessionState() {
        return prestamoService.getSessionState();
    }

    // ---------------------------------------------------------------
    // Router de mensajes MQTT
    // ---------------------------------------------------------------
    async handleMQTTMessage(topic, message) {
        if (!this.isListening) return;

        const messageStr = message.toString().trim();
        const [serialNumber, query] = topic.split('/');
        console.log(`📨 [MQTT] ${topic} -> "${messageStr}"`);

        try {
            switch (query) {
                case 'loan_queryu':
                    return await this.handleLoanUserQuery(serialNumber, messageStr);
                case 'loan_querye':
                    return await this.handleLoanEquipmentQuery(serialNumber, messageStr);
                case 'access_query':
                    return await this.handleAccessQuery(serialNumber, messageStr);
                case 'scholar_query':
                    return await this.handleScholarQuery(serialNumber, messageStr);
                case 'status':
                    return await this.handleStatusMessage(serialNumber, messageStr);
                default:
                    console.log(`⚠️ Tópico no manejado: ${topic}`);
            }
        } catch (err) {
            console.error('❌ Error procesando mensaje MQTT:', err);
        }
    }

    // ---------------------------------------------------------------
    // Handlers
    // ---------------------------------------------------------------
    async handleLoanUserQuery(serialNumber, rfidNumber) {
        if (rfidNumber.startsWith('APP:')) {
            // Mensajes que la app inyectaba para simular: ya no se publican
            // pero el filtro queda por compatibilidad con clientes viejos.
            console.log(`ℹ️ Ignorado payload con prefijo APP:`);
            return;
        }
        const result = await prestamoService.handleLoanUserQuery(serialNumber, rfidNumber);
        console.log(result.success ? `✅ ${result.message}` : `❌ ${result.message}`);
    }

    async handleLoanEquipmentQuery(serialNumber, rfidNumber) {
        const result = await prestamoService.handleLoanEquipmentQuery(serialNumber, rfidNumber);
        console.log(result.success ? `✅ ${result.message}` : `❌ ${result.message}`);
    }

    async handleAccessQuery(serialNumber, rfidNumber) {
        const user = await prestamoService.getUserByRFID(rfidNumber);
        if (!user) {
            await this.publishMQTTCommand(serialNumber, null, 'refused');
            return;
        }

        await this.publishMQTTCommand(serialNumber, user.hab_name, null);

        const last = await this.getLastTrafficForDevice(serialNumber);
        const newState = last && last.traffic_state === 1 ? 0 : 1;

        await prestamoService.registrarTrafico(user.hab_id, serialNumber, newState);
        await this.publishMQTTCommand(serialNumber, null, newState ? 'granted1' : 'granted0');
    }

    async handleScholarQuery(serialNumber, rfidNumber) {
        const user = await prestamoService.getUserByRFID(rfidNumber);
        if (!user) {
            await this.publishMQTTCommand(serialNumber, null, 'refused');
            return;
        }

        await this.publishMQTTCommand(serialNumber, user.hab_name, null);

        const last = await this.getLastUserTrafficForDevice(user.hab_id, serialNumber);
        const newState = last && last.traffic_state === 1 ? 0 : 1;

        await prestamoService.registrarTrafico(user.hab_id, serialNumber, newState);
        await this.publishMQTTCommand(serialNumber, null, newState ? 'granted1' : 'granted0');
    }

    /**
     * Procesa los mensajes retained {SN}/status (online / offline) que
     * publica el firmware nuevo. Persiste el estado en la tabla
     * device_status para que la UI/admin pueda ver estaciones caídas.
     */
    async handleStatusMessage(serialNumber, status) {
        const norm = status === 'online' ? 'online' : 'offline';
        try {
            await dbConfig.execute(
                `INSERT INTO device_status (device_serie, status)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = CURRENT_TIMESTAMP`,
                [serialNumber, norm]
            );
            console.log(`📡 ${serialNumber} → ${norm}`);
        } catch (err) {
            if (err && err.code === 'ER_NO_SUCH_TABLE') {
                console.warn('⚠️ Tabla device_status no existe (aplica migración 003)');
                return;
            }
            console.error('❌ Error registrando device_status:', err.message);
        }
    }

    // ---------------------------------------------------------------
    // Publish helpers
    // ---------------------------------------------------------------
    async publishMQTTCommand(serialNumber, userName, command) {
        if (!mqttConfig.isConnected()) {
            console.warn('⚠️ MQTT no conectado al publicar');
            return;
        }
        try {
            if (userName) await mqttConfig.publish(`${serialNumber}/user_name`, userName);
            if (command)  await mqttConfig.publish(`${serialNumber}/command`,   command);
        } catch (err) {
            console.error('❌ Error publishing:', err.message);
        }
    }

    // ---------------------------------------------------------------
    // Queries
    // ---------------------------------------------------------------
    async getLastTrafficForDevice(deviceSerie) {
        const rows = await dbConfig.execute(
            'SELECT * FROM traffic WHERE traffic_device = ? ORDER BY traffic_date DESC LIMIT 1',
            [deviceSerie]
        );
        return rows.length ? rows[0] : null;
    }

    async getLastUserTrafficForDevice(userId, deviceSerie) {
        const rows = await dbConfig.execute(
            'SELECT * FROM traffic WHERE traffic_hab_id = ? AND traffic_device = ? ORDER BY traffic_date DESC LIMIT 1',
            [userId, deviceSerie]
        );
        return rows.length ? rows[0] : null;
    }
}

module.exports = new MQTTListenerService();
