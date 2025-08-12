// Configuración de variables de ambiente para el frontend
// Este archivo debe ser generado dinámicamente por PHP

class EnvConfig {
    static getServerHost() {
        // Esta función será reemplazada por PHP con el valor real
        return window.ENV_CONFIG?.SERVER_HOST || '192.168.0.100';
    }
    
    static getApiHost() {
        return window.ENV_CONFIG?.API_HOST || this.getServerHost();
    }
    
    static getMqttHost() {
        return window.ENV_CONFIG?.MQTT_HOST || this.getServerHost();
    }
    
    static getMqttWsUrl() {
        return `ws://${this.getMqttHost()}:8083/mqtt`;
    }
    
    static getApiUrl(port = 3000) {
        return `http://${this.getApiHost()}:${port}`;
    }
    
    static getWebSocketUrl(port = 8086) {
        return `ws://${this.getServerHost()}:${port}`;
    }
}

// Exportar para uso global
window.EnvConfig = EnvConfig;