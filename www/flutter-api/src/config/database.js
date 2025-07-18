const mysql = require('mysql2/promise');
require('dotenv').config();

/**
 * Configuración de base de datos para SMARTLABS Flutter API
 */
class DatabaseConfig {
    constructor() {
        this.primaryConfig = {
            host: process.env.DB_HOST,
            user: process.env.DB_USER,
            password: process.env.DB_PASSWORD,
            database: process.env.DB_NAME,
            port: process.env.DB_PORT,
            charset: 'utf8mb4',
            connectTimeout: 60000,
            acquireTimeout: 60000,
            timeout: 60000
        };

        this.fallbackConfig = {
            host: process.env.DB_LOCAL_HOST,
            user: process.env.DB_LOCAL_USER,
            password: process.env.DB_LOCAL_PASSWORD,
            database: process.env.DB_LOCAL_NAME,
            port: process.env.DB_LOCAL_PORT,
            charset: 'utf8mb4',
            connectTimeout: 30000,
            acquireTimeout: 30000,
            timeout: 30000
        };

        this.connection = null;
    }

    /**
     * Conecta a la base de datos con fallback automático
     */
    async connect() {
        try {
            console.log('🔌 Conectando a base de datos principal...');
            this.connection = await mysql.createConnection(this.primaryConfig);
            await this.connection.execute('SELECT 1');
            console.log('✅ Conexión exitosa a base de datos principal');
            return this.connection;
        } catch (error) {
            console.warn('⚠️ Error en base de datos principal, intentando fallback:', error.message);
            try {
                this.connection = await mysql.createConnection(this.fallbackConfig);
                await this.connection.execute('SELECT 1');
                console.log('✅ Conexión exitosa a base de datos local (fallback)');
                return this.connection;
            } catch (fallbackError) {
                console.error('❌ Error en ambas bases de datos:', fallbackError.message);
                throw new Error('No se pudo conectar a ninguna base de datos');
            }
        }
    }

    /**
     * Obtiene la conexión actual
     */
    getConnection() {
        return this.connection;
    }

    /**
     * Cierra la conexión
     */
    async close() {
        if (this.connection) {
            await this.connection.end();
            console.log('🔌 Conexión de base de datos cerrada');
        }
    }

    /**
     * Reconecta a la base de datos
     */
    async reconnect() {
        try {
            if (this.connection) {
                await this.connection.end();
            }
            await this.connect();
        } catch (error) {
            console.error('❌ Error en reconexión:', error.message);
            throw error;
        }
    }
}

module.exports = new DatabaseConfig();