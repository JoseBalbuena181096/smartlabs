const mysql = require('mysql2/promise');
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });

/**
 * Configuración de base de datos para SMARTLABS Flutter API
 */
class DatabaseConfig {
    constructor() {
        this.primaryConfig = {
            host: process.env.DB_HOST || '192.168.0.100',
            user: process.env.DB_USER || 'emqxuser',
            password: process.env.DB_PASSWORD || 'emqxpass',
            database: process.env.DB_NAME || 'emqx',
            port: parseInt(process.env.DB_PORT) || 4000,
            charset: 'utf8mb4',
            connectTimeout: 60000,
            acquireTimeout: 60000
        };

        this.fallbackConfig = {
            host: process.env.DB_LOCAL_HOST || 'localhost',
            user: process.env.DB_LOCAL_USER || 'emqxuser',
            password: process.env.DB_LOCAL_PASSWORD || 'emqxpass',
            database: process.env.DB_LOCAL_NAME || 'emqx',
            port: parseInt(process.env.DB_LOCAL_PORT) || 4000,
            charset: 'utf8mb4',
            connectTimeout: 30000,
            acquireTimeout: 30000
        };
        
        console.log('🔧 Configuración de BD:', {
            primary: `${this.primaryConfig.user}@${this.primaryConfig.host}:${this.primaryConfig.port}/${this.primaryConfig.database}`,
            fallback: `${this.fallbackConfig.user}@${this.fallbackConfig.host}:${this.fallbackConfig.port}/${this.fallbackConfig.database}`
        });

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