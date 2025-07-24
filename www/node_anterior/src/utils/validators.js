/**
 * Utilidades de validación para SMARTLABS
 */

/**
 * Valida si un email tiene formato correcto
 * @param {string} email - Email a validar
 * @returns {boolean} - True si es válido
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Valida si una matrícula tiene formato correcto
 * @param {string} registration - Matrícula a validar
 * @returns {boolean} - True si es válida
 */
function isValidRegistration(registration) {
    // Formato: letras y números, 6-12 caracteres
    const regRegex = /^[A-Za-z0-9]{6,12}$/;
    return regRegex.test(registration);
}

/**
 * Valida si un ID de dispositivo es válido
 * @param {string} deviceId - ID del dispositivo
 * @returns {boolean} - True si es válido
 */
function isValidDeviceId(deviceId) {
    // Formato: letras, números, guiones y guiones bajos
    const deviceRegex = /^[A-Za-z0-9_-]+$/;
    return deviceRegex.test(deviceId) && deviceId.length > 0;
}

/**
 * Valida si un tópico MQTT es válido
 * @param {string} topic - Tópico MQTT
 * @returns {boolean} - True si es válido
 */
function isValidMqttTopic(topic) {
    // No debe contener caracteres especiales excepto / + #
    const topicRegex = /^[A-Za-z0-9/_+#-]+$/;
    return topicRegex.test(topic) && !topic.includes('//');
}

/**
 * Valida si una fecha está en formato ISO válido
 * @param {string} dateString - Fecha en string
 * @returns {boolean} - True si es válida
 */
function isValidISODate(dateString) {
    const date = new Date(dateString);
    return date instanceof Date && !isNaN(date) && date.toISOString() === dateString;
}

/**
 * Valida si un puerto es válido
 * @param {number|string} port - Puerto a validar
 * @returns {boolean} - True si es válido
 */
function isValidPort(port) {
    const portNum = parseInt(port);
    return !isNaN(portNum) && portNum > 0 && portNum <= 65535;
}

/**
 * Valida si una IP es válida
 * @param {string} ip - Dirección IP
 * @returns {boolean} - True si es válida
 */
function isValidIP(ip) {
    const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
    return ipRegex.test(ip);
}

/**
 * Sanitiza un string removiendo caracteres peligrosos
 * @param {string} input - String a sanitizar
 * @returns {string} - String sanitizado
 */
function sanitizeString(input) {
    if (typeof input !== 'string') return '';
    
    return input
        .replace(/[<>"'&]/g, '') // Remover caracteres HTML peligrosos
        .replace(/[\x00-\x1F\x7F]/g, '') // Remover caracteres de control
        .trim();
}

/**
 * Valida estructura de mensaje MQTT
 * @param {object} message - Mensaje a validar
 * @returns {boolean} - True si es válido
 */
function isValidMqttMessage(message) {
    if (!message || typeof message !== 'object') return false;
    
    // Debe tener al menos timestamp y type
    return message.hasOwnProperty('timestamp') && 
           message.hasOwnProperty('type') &&
           typeof message.type === 'string';
}

/**
 * Valida configuración de base de datos
 * @param {object} config - Configuración a validar
 * @returns {boolean} - True si es válida
 */
function isValidDbConfig(config) {
    if (!config || typeof config !== 'object') return false;
    
    const required = ['host', 'user', 'database'];
    return required.every(field => config.hasOwnProperty(field) && config[field]);
}

module.exports = {
    isValidEmail,
    isValidRegistration,
    isValidDeviceId,
    isValidMqttTopic,
    isValidISODate,
    isValidPort,
    isValidIP,
    sanitizeString,
    isValidMqttMessage,
    isValidDbConfig
};