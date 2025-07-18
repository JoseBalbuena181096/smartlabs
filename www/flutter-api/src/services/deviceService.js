const dbConfig = require('../config/database');
const mqttConfig = require('../config/mqtt');

/**
 * Servicio para manejo de dispositivos en SMARTLABS Flutter API
 */
class DeviceService {
    constructor() {
        this.db = dbConfig;
        this.mqtt = mqttConfig;
    }

    /**
     * Busca un dispositivo por su serie (obtenida del QR)
     * @param {string} deviceSerie - Serie del dispositivo
     * @returns {Object|null} - Datos del dispositivo o null si no existe
     */
    async getDeviceBySerial(deviceSerie) {
        try {
            const connection = this.db.getConnection();
            if (!connection) {
                throw new Error('No hay conexión a la base de datos');
            }

            const [rows] = await connection.execute(
                `SELECT 
                    devices_id,
                    devices_alias,
                    devices_serie,
                    devices_user_id,
                    devices_date
                FROM devices 
                WHERE devices_serie = ?`,
                [deviceSerie]
            );

            if (rows.length === 0) {
                return null;
            }

            const device = rows[0];
            return {
                id: device.devices_id,
                alias: device.devices_alias,
                serie: device.devices_serie,
                userId: device.devices_user_id,
                date: device.devices_date
            };
        } catch (error) {
            console.error('❌ Error buscando dispositivo por serie:', error);
            throw error;
        }
    }

    /**
     * Obtiene el último estado de un dispositivo
     * @param {string} deviceSerie - Serie del dispositivo
     * @returns {Object|null} - Estado del dispositivo
     */
    async getDeviceLastState(deviceSerie) {
        try {
            const connection = this.db.getConnection();
            if (!connection) {
                throw new Error('No hay conexión a la base de datos');
            }

            const [rows] = await connection.execute(
                `SELECT 
                    traffic_state,
                    traffic_date,
                    traffic_hab_id
                FROM traffic 
                WHERE traffic_device = ?
                ORDER BY traffic_date DESC 
                LIMIT 1`,
                [deviceSerie]
            );

            if (rows.length === 0) {
                return {
                    state: false,
                    date: null,
                    userId: null,
                    isFirstUse: true
                };
            }

            const lastRecord = rows[0];
            return {
                state: Boolean(lastRecord.traffic_state),
                date: lastRecord.traffic_date,
                userId: lastRecord.traffic_hab_id,
                isFirstUse: false
            };
        } catch (error) {
            console.error('❌ Error obteniendo estado del dispositivo:', error);
            throw error;
        }
    }

    /**
     * Controla un dispositivo (encender/apagar) simulando el comportamiento de main_maquinasV2.cpp
     * @param {string} deviceSerie - Serie del dispositivo
     * @param {number} userId - ID del usuario
     * @param {string} userName - Nombre del usuario
     * @param {number} action - Acción específica: 1 = encender, 0 = apagar
     * @returns {Object} - Resultado de la operación
     */
    async controlDevice(deviceSerie, userId, userName, action) {
        try {
            // Verificar que el dispositivo existe
            const device = await this.getDeviceBySerial(deviceSerie);
            if (!device) {
                return {
                    success: false,
                    message: 'Dispositivo no encontrado',
                    action: null,
                    state: null
                };
            }

            // Determinar el nuevo estado basado en la acción especificada
            const newState = Boolean(action); // 1 = true (encendido), 0 = false (apagado)
            
            // Registrar el nuevo estado en la base de datos
            const connection = this.db.getConnection();
            await connection.execute(
                `INSERT INTO traffic (traffic_date, traffic_hab_id, traffic_device, traffic_state) 
                 VALUES (CURRENT_TIMESTAMP, ?, ?, ?)`,
                [userId, deviceSerie, newState]
            );

            // Enviar comandos MQTT (simulando el comportamiento del dispositivo físico)
            if (this.mqtt.isConnected()) {
                // Enviar nombre del usuario
                await this.mqtt.publish(`${deviceSerie}/user_name`, userName);
                
                // Enviar comando de control
                const command = newState ? 'granted1' : 'granted0';
                await this.mqtt.publish(`${deviceSerie}/command`, command);
                
                console.log(`✅ Dispositivo ${deviceSerie} ${newState ? 'encendido' : 'apagado'} por ${userName}`);
            } else {
                console.warn('⚠️ MQTT no conectado, solo se registró en base de datos');
            }

            return {
                success: true,
                message: `Dispositivo ${newState ? 'encendido' : 'apagado'} exitosamente`,
                action: newState ? 'encendido' : 'apagado',
                state: newState,
                device: {
                    serie: deviceSerie,
                    alias: device.alias
                },
                user: {
                    id: userId,
                    name: userName
                },
                timestamp: new Date().toISOString()
            };
        } catch (error) {
            console.error('❌ Error controlando dispositivo:', error);
            return {
                success: false,
                message: 'Error interno del servidor',
                action: null,
                state: null,
                error: error.message
            };
        }
    }

    /**
     * Obtiene el historial de uso de un dispositivo
     * @param {string} deviceSerie - Serie del dispositivo
     * @param {number} limit - Límite de registros (default: 20)
     * @returns {Array} - Historial de uso
     */
    async getDeviceHistory(deviceSerie, limit = 20) {
        try {
            const connection = this.db.getConnection();
            if (!connection) {
                throw new Error('No hay conexión a la base de datos');
            }

            const [rows] = await connection.execute(
                `SELECT 
                    t.traffic_id,
                    t.traffic_date,
                    t.traffic_state,
                    h.hab_name,
                    h.hab_registration
                FROM traffic t
                INNER JOIN habintants h ON t.traffic_hab_id = h.hab_id
                WHERE t.traffic_device = ?
                ORDER BY t.traffic_date DESC
                LIMIT ?`,
                [deviceSerie, limit]
            );

            return rows.map(row => ({
                id: row.traffic_id,
                date: row.traffic_date,
                state: Boolean(row.traffic_state),
                action: row.traffic_state ? 'Encendido' : 'Apagado',
                user: {
                    name: row.hab_name,
                    registration: row.hab_registration
                }
            }));
        } catch (error) {
            console.error('❌ Error obteniendo historial del dispositivo:', error);
            throw error;
        }
    }

    /**
     * Lista todos los dispositivos disponibles
     * @returns {Array} - Lista de dispositivos
     */
    async getAllDevices() {
        try {
            const connection = this.db.getConnection();
            if (!connection) {
                throw new Error('No hay conexión a la base de datos');
            }

            const [rows] = await connection.execute(
                `SELECT 
                    devices_id,
                    devices_alias,
                    devices_serie,
                    devices_date
                FROM devices 
                ORDER BY devices_alias ASC`
            );

            return rows.map(row => ({
                id: row.devices_id,
                alias: row.devices_alias,
                serie: row.devices_serie,
                date: row.devices_date
            }));
        } catch (error) {
            console.error('❌ Error obteniendo lista de dispositivos:', error);
            throw error;
        }
    }
}

module.exports = new DeviceService();