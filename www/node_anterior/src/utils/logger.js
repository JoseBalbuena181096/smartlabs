/**
 * Utilidad de logging centralizada para SMARTLABS
 */

const fs = require('fs');
const path = require('path');

class Logger {
    constructor(options = {}) {
        this.logLevel = options.logLevel || 'info';
        this.logToFile = options.logToFile || false;
        this.logDir = options.logDir || path.join(__dirname, '../../logs');
        this.serviceName = options.serviceName || 'SMARTLABS';
        
        // Crear directorio de logs si no existe
        if (this.logToFile && !fs.existsSync(this.logDir)) {
            fs.mkdirSync(this.logDir, { recursive: true });
        }
    }
    
    _formatMessage(level, message, data = null) {
        const timestamp = new Date().toISOString();
        const prefix = `[${timestamp}] [${this.serviceName}] [${level.toUpperCase()}]`;
        
        let formattedMessage = `${prefix} ${message}`;
        
        if (data) {
            formattedMessage += ` ${JSON.stringify(data, null, 2)}`;
        }
        
        return formattedMessage;
    }
    
    _writeToFile(level, formattedMessage) {
        if (!this.logToFile) return;
        
        const logFile = path.join(this.logDir, `${level}.log`);
        const combinedFile = path.join(this.logDir, 'combined.log');
        
        fs.appendFileSync(logFile, formattedMessage + '\n');
        fs.appendFileSync(combinedFile, formattedMessage + '\n');
    }
    
    info(message, data = null) {
        const formatted = this._formatMessage('info', message, data);
        console.log(formatted);
        this._writeToFile('info', formatted);
    }
    
    warn(message, data = null) {
        const formatted = this._formatMessage('warn', message, data);
        console.warn(formatted);
        this._writeToFile('warn', formatted);
    }
    
    error(message, data = null) {
        const formatted = this._formatMessage('error', message, data);
        console.error(formatted);
        this._writeToFile('error', formatted);
    }
    
    debug(message, data = null) {
        if (this.logLevel === 'debug') {
            const formatted = this._formatMessage('debug', message, data);
            console.log(formatted);
            this._writeToFile('debug', formatted);
        }
    }
    
    success(message, data = null) {
        const formatted = this._formatMessage('success', `✅ ${message}`, data);
        console.log(formatted);
        this._writeToFile('info', formatted);
    }
}

// Crear instancia por defecto
const defaultLogger = new Logger({
    logToFile: true,
    logLevel: process.env.LOG_LEVEL || 'info'
});

module.exports = {
    Logger,
    logger: defaultLogger
};