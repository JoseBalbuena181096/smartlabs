const mysql = require('mysql2/promise');
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });

/**
 * Configuración de base de datos para SMARTLABS Flutter API
 * Implementa pool de conexiones para evitar bloqueos
 */
class DatabaseConfig {
    constructor() {
        this.primaryConfig = {
            host: process.env.DB_HOST || 'smartlabs-mariadb',
            user: process.env.DB_USER || 'emqxuser',
            password: process.env.DB_PASSWORD || 'emqxpass',
            database: process.env.DB_NAME || 'emqx',
            port: parseInt(process.env.DB_PORT) || 3306,
            charset: 'utf8mb4',
            // Pool configuration para evitar bloqueos
            connectionLimit: parseInt(process.env.DB_CONNECTION_LIMIT) || 10,
            acquireTimeout: parseInt(process.env.DB_ACQUIRE_TIMEOUT) || 60000,
            timeout: parseInt(process.env.DB_TIMEOUT) || 60000,
            reconnect: true,
            idleTimeout: 300000,
            maxReconnects: 3
        };

        this.fallbackConfig = {
            host: process.env.DB_LOCAL_HOST || 'smartlabs-mariadb',
            user: process.env.DB_LOCAL_USER || 'emqxuser',
            password: process.env.DB_LOCAL_PASSWORD || 'emqxpass',
            database: process.env.DB_LOCAL_NAME || 'emqx',
            port: parseInt(process.env.DB_LOCAL_PORT) || 3306,
            charset: 'utf8mb4',
            // Pool configuration para evitar bloqueos
            connectionLimit: parseInt(process.env.DB_CONNECTION_LIMIT) || 10,
            acquireTimeout: parseInt(process.env.DB_ACQUIRE_TIMEOUT) || 60000,
            timeout: parseInt(process.env.DB_TIMEOUT) || 60000,
            reconnect: true,
            idleTimeout: 300000,
            maxReconnects: 3
        };
        
        console.log('🔧 Configuración de BD (Pool):', {
            primary: `${this.primaryConfig.user}@${this.primaryConfig.host}:${this.primaryConfig.port}/${this.primaryConfig.database}`,
            fallback: `${this.fallbackConfig.user}@${this.fallbackConfig.host}:${this.fallbackConfig.port}/${this.fallbackConfig.database}`,
            connectionLimit: this.primaryConfig.connectionLimit
        });

        this.pool = null;
    }

    /**
     * Conecta a la base de datos con pool de conexiones y fallback automático
     */
    async connect() {
        try {
            console.log('🔌 Creando pool de conexiones a base de datos principal...');
            this.pool = mysql.createPool(this.primaryConfig);
            
            // Probar conexión del pool
            const connection = await this.pool.getConnection();
            await connection.execute('SELECT 1');
            connection.release();
            
            console.log('✅ Pool de conexiones creado exitosamente (principal)');
            return this.pool;
        } catch (error) {
            console.warn('⚠️ Error en base de datos principal, intentando fallback:', error.message);
            try {
                if (this.pool) {
                    await this.pool.end();
                }
                
                this.pool = mysql.createPool(this.fallbackConfig);
                
                // Probar conexión del pool fallback
                const connection = await this.pool.getConnection();
                await connection.execute('SELECT 1');
                connection.release();
                
                console.log('✅ Pool de conexiones creado exitosamente (fallback)');
                return this.pool;
            } catch (fallbackError) {
                console.error('❌ Error en ambas bases de datos:', fallbackError.message);
                throw new Error('No se pudo conectar a ninguna base de datos');
            }
        }
    }

    /**
     * Obtiene el pool de conexiones actual
     */
    getConnection() {
        if (!this.pool) {
            throw new Error('Pool de conexiones no inicializado');
        }
        return this.pool;
    }

    /**
     * Ejecuta una consulta con manejo automático de conexiones
     */
    async execute(query, params = []) {
        if (!this.pool) {
            throw new Error('Pool de conexiones no inicializado');
        }
        
        let connection;
        try {
            connection = await this.pool.getConnection();
            const [results] = await connection.execute(query, params);
            return results;
        } catch (error) {
            console.error('❌ Error ejecutando consulta:', error.message);
            throw error;
        } finally {
            if (connection) {
                connection.release();
            }
        }
    }

    /**
     * Cierra el pool de conexiones
     */
    async close() {
        if (this.pool) {
            await this.pool.end();
            this.pool = null;
            console.log('🔌 Pool de conexiones cerrado');
        }
    }

    /**
     * Reconecta el pool de conexiones
     */
    async reconnect() {
        try {
            console.log('🔄 Reconectando pool de conexiones...');
            if (this.pool) {
                await this.pool.end();
            }
            await this.connect();
            console.log('✅ Pool reconectado exitosamente');
        } catch (error) {
            console.error('❌ Error en reconexión del pool:', error.message);
            throw error;
        }
    }

    /**
     * Obtiene estadísticas del pool de conexiones
     */
    getPoolStats() {
        if (!this.pool) {
            return { status: 'disconnected' };
        }
        
        return {
            status: 'connected',
            totalConnections: this.pool.pool._allConnections.length,
            freeConnections: this.pool.pool._freeConnections.length,
            acquiringConnections: this.pool.pool._acquiringConnections.length
        };
    }
}

module.exports = new DatabaseConfig();