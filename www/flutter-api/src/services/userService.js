const dbConfig = require('../config/database');

/**
 * Servicio para manejo de usuarios en SMARTLABS Flutter API
 */
class UserService {
    constructor() {
        this.db = dbConfig;
    }

    /**
     * Busca un usuario por matrícula
     * @param {string} registration - Matrícula del usuario
     * @returns {Object|null} - Datos del usuario o null si no existe
     */
    async getUserByRegistration(registration) {
        try {
            const connection = this.db.getConnection();
            if (!connection) {
                throw new Error('No hay conexión a la base de datos');
            }

            // Buscar usuario por matrícula en la vista cards_habs que une usuarios con tarjetas
            const [rows] = await connection.execute(
                `SELECT 
                    ch.hab_id,
                    ch.hab_name,
                    ch.cards_number,
                    h.hab_registration,
                    h.hab_email,
                    h.hab_device_id
                FROM cards_habs ch
                INNER JOIN habintants h ON ch.hab_id = h.hab_id
                WHERE h.hab_registration = ?`,
                [registration]
            );

            if (rows.length === 0) {
                return null;
            }

            const user = rows[0];
            return {
                id: user.hab_id,
                name: user.hab_name,
                registration: user.hab_registration,
                email: user.hab_email,
                cards_number: user.cards_number,
                device_id: user.hab_device_id
            };
        } catch (error) {
            console.error('❌ Error buscando usuario por matrícula:', error);
            throw error;
        }
    }

    /**
     * Busca un usuario por RFID (cards_number)
     * @param {string} rfid - Número de tarjeta RFID
     * @returns {Object|null} - Datos del usuario o null si no existe
     */
    async getUserByRFID(rfid) {
        try {
            const connection = this.db.getConnection();
            if (!connection) {
                throw new Error('No hay conexión a la base de datos');
            }

            // Buscar usuario por RFID en la vista cards_habs
            const [rows] = await connection.execute(
                `SELECT 
                    ch.hab_id,
                    ch.hab_name,
                    ch.cards_number,
                    h.hab_registration,
                    h.hab_email,
                    h.hab_device_id
                FROM cards_habs ch
                INNER JOIN habintants h ON ch.hab_id = h.hab_id
                WHERE ch.cards_number = ?`,
                [rfid]
            );

            if (rows.length === 0) {
                return null;
            }

            const user = rows[0];
            return {
                id: user.hab_id,
                name: user.hab_name,
                registration: user.hab_registration,
                email: user.hab_email,
                cards_number: user.cards_number,
                device_id: user.hab_device_id
            };
        } catch (error) {
            console.error('❌ Error buscando usuario por RFID:', error);
            throw error;
        }
    }

    /**
     * Obtiene el historial de acceso de un usuario
     * @param {number} userId - ID del usuario
     * @param {number} limit - Límite de registros (default: 10)
     * @returns {Array} - Historial de acceso
     */
    async getUserAccessHistory(userId, limit = 10) {
        try {
            const connection = this.db.getConnection();
            if (!connection) {
                throw new Error('No hay conexión a la base de datos');
            }

            const [rows] = await connection.execute(
                `SELECT 
                    traffic_id,
                    traffic_date,
                    traffic_device,
                    traffic_state
                FROM traffic 
                WHERE traffic_hab_id = ?
                ORDER BY traffic_date DESC
                LIMIT ?`,
                [userId, limit]
            );

            return rows.map(row => ({
                id: row.traffic_id,
                date: row.traffic_date,
                device: row.traffic_device,
                state: row.traffic_state,
                action: row.traffic_state ? 'Encendido' : 'Apagado'
            }));
        } catch (error) {
            console.error('❌ Error obteniendo historial de acceso:', error);
            throw error;
        }
    }

    /**
     * Valida si un usuario existe y está activo
     * @param {string} registration - Matrícula del usuario
     * @returns {boolean} - True si el usuario es válido
     */
    async validateUser(registration) {
        try {
            const user = await this.getUserByRegistration(registration);
            return user !== null;
        } catch (error) {
            console.error('❌ Error validando usuario:', error);
            return false;
        }
    }
}

module.exports = new UserService();