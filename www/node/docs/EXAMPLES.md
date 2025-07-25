# Ejemplos de Uso - SMARTLABS Device Status Server

## Tabla de Contenidos

1. [Configuración Inicial](#configuración-inicial)
2. [Conexión WebSocket Básica](#conexión-websocket-básica)
3. [Ejemplos Frontend](#ejemplos-frontend)
4. [Integración con Frameworks](#integración-con-frameworks)
5. [Casos de Uso Avanzados](#casos-de-uso-avanzados)
6. [Manejo de Errores](#manejo-de-errores)
7. [Testing](#testing)

## Configuración Inicial

### Instalación y Configuración

```bash
# Clonar o navegar al directorio
cd c:\laragon\www\node

# Instalar dependencias
npm install

# Configurar variables de entorno (opcional)
echo PORT=3000 > .env
echo NODE_ENV=development >> .env

# Iniciar servidor
npm start
```

### Verificar Funcionamiento

```bash
# Verificar que el servidor esté ejecutándose
curl http://localhost:3000/health

# Respuesta esperada:
# {"status":"ok","timestamp":"2024-01-15T10:30:00.000Z","uptime":123.45}
```

## Conexión WebSocket Básica

### Cliente JavaScript Vanilla

```javascript
// Conectar al servidor WebSocket
const ws = new WebSocket('ws://localhost:3000');

// Manejar eventos de conexión
ws.onopen = function(event) {
    console.log('✅ Conectado al servidor de dispositivos');
    
    // Suscribirse a dispositivos específicos
    ws.send(JSON.stringify({
        type: 'subscribe',
        devices: ['SMART001', 'SMART002', 'SMART003']
    }));
};

// Manejar mensajes del servidor
ws.onmessage = function(event) {
    const message = JSON.parse(event.data);
    
    switch(message.type) {
        case 'welcome':
            console.log('📱 Servidor:', message.message);
            console.log('🔢 Dispositivos disponibles:', message.devices);
            break;
            
        case 'device_status':
            console.log(`📊 ${message.device}:`, message.data);
            updateDeviceUI(message.device, message.data);
            break;
            
        default:
            console.log('📨 Mensaje:', message);
    }
};

// Manejar errores
ws.onerror = function(error) {
    console.error('❌ Error WebSocket:', error);
};

// Manejar cierre de conexión
ws.onclose = function(event) {
    console.log('🔌 Conexión cerrada:', event.code, event.reason);
    
    // Reconectar automáticamente
    setTimeout(() => {
        console.log('🔄 Intentando reconectar...');
        // Reinicializar conexión
    }, 5000);
};

// Función para actualizar UI
function updateDeviceUI(deviceId, data) {
    const deviceElement = document.getElementById(`device-${deviceId}`);
    if (deviceElement) {
        deviceElement.className = `device status-${data.state}`;
        deviceElement.innerHTML = `
            <h3>${deviceId}</h3>
            <div class="status-indicator ${data.state}"></div>
            <p>Estado: ${data.state === 'on' ? 'Encendido' : 'Apagado'}</p>
            <p>Usuario: ${data.user || 'N/A'}</p>
            <p>Última actividad: ${new Date(data.last_activity).toLocaleString()}</p>
        `;
    }
}
```

### Suscripción a Todos los Dispositivos

```javascript
// Suscribirse a todos los dispositivos
ws.onopen = function() {
    ws.send(JSON.stringify({
        type: 'subscribe',
        devices: ['all']
    }));
};

// Manejar actualizaciones masivas
const deviceStates = new Map();

ws.onmessage = function(event) {
    const message = JSON.parse(event.data);
    
    if (message.type === 'device_status') {
        // Almacenar estado en memoria
        deviceStates.set(message.device, message.data);
        
        // Actualizar dashboard
        updateDashboard();
    }
};

function updateDashboard() {
    const totalDevices = deviceStates.size;
    const onlineDevices = Array.from(deviceStates.values())
        .filter(device => device.state === 'on').length;
    
    document.getElementById('total-devices').textContent = totalDevices;
    document.getElementById('online-devices').textContent = onlineDevices;
    document.getElementById('offline-devices').textContent = totalDevices - onlineDevices;
}
```

## Ejemplos Frontend

### HTML Dashboard Completo

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMARTLABS - Monitor de Dispositivos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .devices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .device {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 5px solid #ddd;
        }
        
        .device.status-on {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }
        
        .device.status-off {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da 0%, #f1b0b7 100%);
        }
        
        .device.status-unknown {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        }
        
        .device-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .device-title {
            font-size: 1.2em;
            font-weight: bold;
            margin: 0;
        }
        
        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-indicator.on {
            background-color: #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        }
        
        .status-indicator.off {
            background-color: #dc3545;
        }
        
        .status-indicator.unknown {
            background-color: #ffc107;
        }
        
        .device-info {
            display: grid;
            gap: 8px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
        }
        
        .info-label {
            font-weight: 500;
            color: #666;
        }
        
        .connection-status {
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .connection-status.connected {
            background-color: #d4edda;
            color: #155724;
        }
        
        .connection-status.disconnected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .controls {
            margin-bottom: 20px;
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏢 SMARTLABS - Monitor de Dispositivos</h1>
        <p>Monitoreo en tiempo real del estado de dispositivos IoT</p>
    </div>
    
    <div id="connection-status" class="connection-status disconnected">
        🔌 Desconectado del servidor
    </div>
    
    <div class="controls">
        <button class="btn" onclick="connectWebSocket()">🔄 Reconectar</button>
        <button class="btn" onclick="subscribeToAll()">📡 Suscribirse a Todos</button>
        <button class="btn" onclick="clearDevices()">🗑️ Limpiar</button>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number" id="total-devices">0</div>
            <div>Total Dispositivos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="online-devices" style="color: #28a745;">0</div>
            <div>En Línea</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="offline-devices" style="color: #dc3545;">0</div>
            <div>Fuera de Línea</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="last-update">--:--</div>
            <div>Última Actualización</div>
        </div>
    </div>
    
    <div id="devices-container" class="devices-grid">
        <!-- Los dispositivos se cargarán dinámicamente -->
    </div>
    
    <script>
        let ws = null;
        const deviceStates = new Map();
        
        // Conectar al WebSocket
        function connectWebSocket() {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.close();
            }
            
            ws = new WebSocket('ws://localhost:3000');
            
            ws.onopen = function() {
                updateConnectionStatus(true);
                console.log('✅ Conectado al servidor');
                
                // Suscribirse automáticamente a todos los dispositivos
                subscribeToAll();
            };
            
            ws.onmessage = function(event) {
                const message = JSON.parse(event.data);
                handleMessage(message);
            };
            
            ws.onerror = function(error) {
                console.error('❌ Error WebSocket:', error);
                updateConnectionStatus(false);
            };
            
            ws.onclose = function() {
                updateConnectionStatus(false);
                console.log('🔌 Conexión cerrada');
                
                // Reconectar automáticamente después de 5 segundos
                setTimeout(connectWebSocket, 5000);
            };
        }
        
        // Manejar mensajes del servidor
        function handleMessage(message) {
            switch(message.type) {
                case 'welcome':
                    console.log('📱 Bienvenida:', message.message);
                    break;
                    
                case 'device_status':
                    updateDevice(message.device, message.data);
                    break;
                    
                default:
                    console.log('📨 Mensaje:', message);
            }
        }
        
        // Actualizar estado de dispositivo
        function updateDevice(deviceId, data) {
            deviceStates.set(deviceId, data);
            renderDevice(deviceId, data);
            updateStats();
            updateLastUpdate();
        }
        
        // Renderizar dispositivo en la UI
        function renderDevice(deviceId, data) {
            const container = document.getElementById('devices-container');
            let deviceElement = document.getElementById(`device-${deviceId}`);
            
            if (!deviceElement) {
                deviceElement = document.createElement('div');
                deviceElement.id = `device-${deviceId}`;
                container.appendChild(deviceElement);
            }
            
            const statusText = {
                'on': 'Encendido',
                'off': 'Apagado',
                'unknown': 'Desconocido'
            }[data.state] || 'Desconocido';
            
            deviceElement.className = `device status-${data.state}`;
            deviceElement.innerHTML = `
                <div class="device-header">
                    <h3 class="device-title">${deviceId}</h3>
                    <div class="status-indicator ${data.state}"></div>
                </div>
                <div class="device-info">
                    <div class="info-row">
                        <span class="info-label">Estado:</span>
                        <span><strong>${statusText}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Usuario:</span>
                        <span>${data.user || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Matrícula:</span>
                        <span>${data.user_registration || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span>${data.user_email || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Última actividad:</span>
                        <span>${data.last_activity ? new Date(data.last_activity).toLocaleString() : 'N/A'}</span>
                    </div>
                </div>
            `;
        }
        
        // Actualizar estadísticas
        function updateStats() {
            const total = deviceStates.size;
            const online = Array.from(deviceStates.values())
                .filter(device => device.state === 'on').length;
            const offline = total - online;
            
            document.getElementById('total-devices').textContent = total;
            document.getElementById('online-devices').textContent = online;
            document.getElementById('offline-devices').textContent = offline;
        }
        
        // Actualizar timestamp de última actualización
        function updateLastUpdate() {
            const now = new Date();
            document.getElementById('last-update').textContent = 
                now.toLocaleTimeString();
        }
        
        // Actualizar estado de conexión
        function updateConnectionStatus(connected) {
            const statusElement = document.getElementById('connection-status');
            if (connected) {
                statusElement.className = 'connection-status connected';
                statusElement.textContent = '✅ Conectado al servidor';
            } else {
                statusElement.className = 'connection-status disconnected';
                statusElement.textContent = '❌ Desconectado del servidor';
            }
        }
        
        // Suscribirse a todos los dispositivos
        function subscribeToAll() {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'subscribe',
                    devices: ['all']
                }));
                console.log('📡 Suscrito a todos los dispositivos');
            }
        }
        
        // Limpiar dispositivos
        function clearDevices() {
            deviceStates.clear();
            document.getElementById('devices-container').innerHTML = '';
            updateStats();
        }
        
        // Inicializar conexión al cargar la página
        window.onload = function() {
            connectWebSocket();
        };
    </script>
</body>
</html>
```

## Integración con Frameworks

### React Hook

```javascript
// useDeviceStatus.js
import { useState, useEffect, useCallback } from 'react';

export const useDeviceStatus = (serverUrl = 'ws://localhost:3000') => {
    const [devices, setDevices] = useState(new Map());
    const [connectionStatus, setConnectionStatus] = useState('disconnected');
    const [ws, setWs] = useState(null);
    
    const connect = useCallback(() => {
        const websocket = new WebSocket(serverUrl);
        
        websocket.onopen = () => {
            setConnectionStatus('connected');
            console.log('✅ Conectado al servidor de dispositivos');
        };
        
        websocket.onmessage = (event) => {
            const message = JSON.parse(event.data);
            
            if (message.type === 'device_status') {
                setDevices(prev => new Map(prev.set(message.device, message.data)));
            }
        };
        
        websocket.onerror = (error) => {
            console.error('❌ Error WebSocket:', error);
            setConnectionStatus('error');
        };
        
        websocket.onclose = () => {
            setConnectionStatus('disconnected');
            // Reconectar después de 5 segundos
            setTimeout(connect, 5000);
        };
        
        setWs(websocket);
    }, [serverUrl]);
    
    const subscribe = useCallback((deviceIds) => {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'subscribe',
                devices: deviceIds
            }));
        }
    }, [ws]);
    
    const getDeviceStatus = useCallback((deviceId) => {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'get_status',
                device: deviceId
            }));
        }
    }, [ws]);
    
    useEffect(() => {
        connect();
        
        return () => {
            if (ws) {
                ws.close();
            }
        };
    }, [connect]);
    
    return {
        devices,
        connectionStatus,
        subscribe,
        getDeviceStatus,
        reconnect: connect
    };
};

// Componente React
import React, { useEffect } from 'react';
import { useDeviceStatus } from './useDeviceStatus';

const DeviceMonitor = () => {
    const { devices, connectionStatus, subscribe } = useDeviceStatus();
    
    useEffect(() => {
        // Suscribirse a todos los dispositivos al montar
        subscribe(['all']);
    }, [subscribe]);
    
    const deviceArray = Array.from(devices.entries());
    
    return (
        <div className="device-monitor">
            <div className={`connection-status ${connectionStatus}`}>
                Estado: {connectionStatus}
            </div>
            
            <div className="devices-grid">
                {deviceArray.map(([deviceId, data]) => (
                    <div key={deviceId} className={`device status-${data.state}`}>
                        <h3>{deviceId}</h3>
                        <p>Estado: {data.state}</p>
                        <p>Usuario: {data.user || 'N/A'}</p>
                        <p>Última actividad: {new Date(data.last_activity).toLocaleString()}</p>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default DeviceMonitor;
```

### Vue.js Composable

```javascript
// useDeviceStatus.js
import { ref, onMounted, onUnmounted } from 'vue';

export function useDeviceStatus(serverUrl = 'ws://localhost:3000') {
    const devices = ref(new Map());
    const connectionStatus = ref('disconnected');
    let ws = null;
    
    const connect = () => {
        ws = new WebSocket(serverUrl);
        
        ws.onopen = () => {
            connectionStatus.value = 'connected';
        };
        
        ws.onmessage = (event) => {
            const message = JSON.parse(event.data);
            
            if (message.type === 'device_status') {
                devices.value.set(message.device, message.data);
                // Trigger reactivity
                devices.value = new Map(devices.value);
            }
        };
        
        ws.onerror = () => {
            connectionStatus.value = 'error';
        };
        
        ws.onclose = () => {
            connectionStatus.value = 'disconnected';
            setTimeout(connect, 5000);
        };
    };
    
    const subscribe = (deviceIds) => {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'subscribe',
                devices: deviceIds
            }));
        }
    };
    
    onMounted(() => {
        connect();
    });
    
    onUnmounted(() => {
        if (ws) {
            ws.close();
        }
    });
    
    return {
        devices,
        connectionStatus,
        subscribe
    };
}

// Componente Vue
<template>
  <div class="device-monitor">
    <div :class="`connection-status ${connectionStatus}`">
      Estado: {{ connectionStatus }}
    </div>
    
    <div class="devices-grid">
      <div 
        v-for="[deviceId, data] in devices" 
        :key="deviceId"
        :class="`device status-${data.state}`"
      >
        <h3>{{ deviceId }}</h3>
        <p>Estado: {{ data.state }}</p>
        <p>Usuario: {{ data.user || 'N/A' }}</p>
        <p>Última actividad: {{ new Date(data.last_activity).toLocaleString() }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useDeviceStatus } from './useDeviceStatus';

const { devices, connectionStatus, subscribe } = useDeviceStatus();

onMounted(() => {
  subscribe(['all']);
});
</script>
```

## Casos de Uso Avanzados

### Monitor de Laboratorio Específico

```javascript
class LabMonitor {
    constructor(labId, deviceIds) {
        this.labId = labId;
        this.deviceIds = deviceIds;
        this.devices = new Map();
        this.ws = null;
        this.callbacks = new Map();
    }
    
    connect() {
        this.ws = new WebSocket('ws://localhost:3000');
        
        this.ws.onopen = () => {
            console.log(`🏢 Lab ${this.labId} conectado`);
            this.subscribe();
        };
        
        this.ws.onmessage = (event) => {
            const message = JSON.parse(event.data);
            
            if (message.type === 'device_status') {
                this.updateDevice(message.device, message.data);
            }
        };
    }
    
    subscribe() {
        this.ws.send(JSON.stringify({
            type: 'subscribe',
            devices: this.deviceIds
        }));
    }
    
    updateDevice(deviceId, data) {
        const previousState = this.devices.get(deviceId);
        this.devices.set(deviceId, data);
        
        // Ejecutar callbacks registrados
        this.callbacks.forEach((callback, event) => {
            if (event === 'device_change') {
                callback(deviceId, data, previousState);
            }
        });
        
        // Verificar alertas
        this.checkAlerts(deviceId, data, previousState);
    }
    
    checkAlerts(deviceId, currentData, previousData) {
        // Alerta de dispositivo encendido sin usuario
        if (currentData.state === 'on' && !currentData.user) {
            this.triggerAlert('unauthorized_usage', {
                device: deviceId,
                timestamp: new Date()
            });
        }
        
        // Alerta de cambio de estado
        if (previousData && previousData.state !== currentData.state) {
            this.triggerAlert('state_change', {
                device: deviceId,
                from: previousData.state,
                to: currentData.state,
                user: currentData.user
            });
        }
    }
    
    triggerAlert(type, data) {
        const alertCallback = this.callbacks.get(`alert_${type}`);
        if (alertCallback) {
            alertCallback(data);
        }
        
        console.log(`🚨 Alerta ${type}:`, data);
    }
    
    on(event, callback) {
        this.callbacks.set(event, callback);
    }
    
    getLabStatus() {
        const devices = Array.from(this.devices.values());
        const totalDevices = devices.length;
        const activeDevices = devices.filter(d => d.state === 'on').length;
        const usersInLab = new Set(devices
            .filter(d => d.user)
            .map(d => d.user_registration)
        ).size;
        
        return {
            labId: this.labId,
            totalDevices,
            activeDevices,
            usersInLab,
            utilizationRate: totalDevices > 0 ? (activeDevices / totalDevices) * 100 : 0
        };
    }
}

// Uso del monitor de laboratorio
const lab101 = new LabMonitor('LAB-101', ['SMART001', 'SMART002', 'SMART003']);

// Configurar alertas
lab101.on('alert_unauthorized_usage', (data) => {
    console.log('⚠️ Dispositivo encendido sin usuario autorizado:', data);
    // Enviar notificación al administrador
    sendNotification('admin@smartlabs.com', 'Uso no autorizado detectado', data);
});

lab101.on('alert_state_change', (data) => {
    console.log('🔄 Cambio de estado:', data);
    // Registrar en log de auditoría
    logAuditEvent('device_state_change', data);
});

lab101.on('device_change', (deviceId, data, previousData) => {
    console.log(`📱 ${deviceId} actualizado:`, data);
    // Actualizar dashboard en tiempo real
    updateDashboard(lab101.getLabStatus());
});

// Conectar
lab101.connect();
```

### Sistema de Notificaciones

```javascript
class NotificationSystem {
    constructor() {
        this.ws = null;
        this.notifications = [];
        this.rules = new Map();
    }
    
    connect() {
        this.ws = new WebSocket('ws://localhost:3000');
        
        this.ws.onopen = () => {
            this.ws.send(JSON.stringify({
                type: 'subscribe',
                devices: ['all']
            }));
        };
        
        this.ws.onmessage = (event) => {
            const message = JSON.parse(event.data);
            
            if (message.type === 'device_status') {
                this.processNotificationRules(message.device, message.data);
            }
        };
    }
    
    addRule(name, condition, action) {
        this.rules.set(name, { condition, action });
    }
    
    processNotificationRules(deviceId, data) {
        this.rules.forEach((rule, name) => {
            if (rule.condition(deviceId, data)) {
                rule.action(deviceId, data);
                
                this.notifications.push({
                    id: Date.now(),
                    rule: name,
                    device: deviceId,
                    data: data,
                    timestamp: new Date()
                });
            }
        });
    }
    
    getNotifications(limit = 10) {
        return this.notifications
            .sort((a, b) => b.timestamp - a.timestamp)
            .slice(0, limit);
    }
}

// Configurar sistema de notificaciones
const notificationSystem = new NotificationSystem();

// Regla: Dispositivo encendido más de 2 horas
notificationSystem.addRule(
    'long_usage',
    (deviceId, data) => {
        if (data.state === 'on' && data.last_activity) {
            const hoursSinceActivity = (Date.now() - new Date(data.last_activity)) / (1000 * 60 * 60);
            return hoursSinceActivity > 2;
        }
        return false;
    },
    (deviceId, data) => {
        console.log(`⏰ Dispositivo ${deviceId} encendido por más de 2 horas`);
        sendEmail(data.user_email, 'Recordatorio de uso prolongado', {
            device: deviceId,
            duration: '2+ horas'
        });
    }
);

// Regla: Uso fuera de horario
notificationSystem.addRule(
    'after_hours',
    (deviceId, data) => {
        const now = new Date();
        const hour = now.getHours();
        return data.state === 'on' && (hour < 7 || hour > 22);
    },
    (deviceId, data) => {
        console.log(`🌙 Uso fuera de horario detectado en ${deviceId}`);
        sendSlackNotification('#security', `Dispositivo ${deviceId} en uso fuera de horario por ${data.user}`);
    }
);

notificationSystem.connect();
```

## Manejo de Errores

### Cliente Robusto con Reconexión

```javascript
class RobustWebSocketClient {
    constructor(url, options = {}) {
        this.url = url;
        this.options = {
            reconnectInterval: 5000,
            maxReconnectAttempts: 10,
            heartbeatInterval: 30000,
            ...options
        };
        
        this.ws = null;
        this.reconnectAttempts = 0;
        this.heartbeatTimer = null;
        this.isConnected = false;
        this.messageQueue = [];
        this.eventHandlers = new Map();
    }
    
    connect() {
        try {
            this.ws = new WebSocket(this.url);
            
            this.ws.onopen = () => {
                console.log('✅ Conectado exitosamente');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                
                // Procesar cola de mensajes pendientes
                this.processMessageQueue();
                
                // Iniciar heartbeat
                this.startHeartbeat();
                
                this.emit('connected');
            };
            
            this.ws.onmessage = (event) => {
                try {
                    const message = JSON.parse(event.data);
                    this.emit('message', message);
                } catch (error) {
                    console.error('❌ Error parseando mensaje:', error);
                    this.emit('error', { type: 'parse_error', error });
                }
            };
            
            this.ws.onerror = (error) => {
                console.error('❌ Error WebSocket:', error);
                this.emit('error', { type: 'websocket_error', error });
            };
            
            this.ws.onclose = (event) => {
                console.log('🔌 Conexión cerrada:', event.code, event.reason);
                this.isConnected = false;
                this.stopHeartbeat();
                
                this.emit('disconnected', { code: event.code, reason: event.reason });
                
                // Intentar reconectar si no fue cierre intencional
                if (event.code !== 1000 && this.reconnectAttempts < this.options.maxReconnectAttempts) {
                    this.scheduleReconnect();
                }
            };
            
        } catch (error) {
            console.error('❌ Error creando WebSocket:', error);
            this.emit('error', { type: 'connection_error', error });
            this.scheduleReconnect();
        }
    }
    
    scheduleReconnect() {
        this.reconnectAttempts++;
        const delay = this.options.reconnectInterval * Math.pow(1.5, this.reconnectAttempts - 1);
        
        console.log(`🔄 Reintentando conexión en ${delay}ms (intento ${this.reconnectAttempts}/${this.options.maxReconnectAttempts})`);
        
        setTimeout(() => {
            this.connect();
        }, delay);
    }
    
    send(data) {
        const message = JSON.stringify(data);
        
        if (this.isConnected && this.ws.readyState === WebSocket.OPEN) {
            try {
                this.ws.send(message);
                return true;
            } catch (error) {
                console.error('❌ Error enviando mensaje:', error);
                this.messageQueue.push(data);
                return false;
            }
        } else {
            // Agregar a cola para enviar cuando se reconecte
            this.messageQueue.push(data);
            return false;
        }
    }
    
    processMessageQueue() {
        while (this.messageQueue.length > 0) {
            const message = this.messageQueue.shift();
            this.send(message);
        }
    }
    
    startHeartbeat() {
        this.heartbeatTimer = setInterval(() => {
            if (this.isConnected) {
                this.send({ type: 'ping', timestamp: Date.now() });
            }
        }, this.options.heartbeatInterval);
    }
    
    stopHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
    }
    
    on(event, handler) {
        if (!this.eventHandlers.has(event)) {
            this.eventHandlers.set(event, []);
        }
        this.eventHandlers.get(event).push(handler);
    }
    
    emit(event, data) {
        const handlers = this.eventHandlers.get(event) || [];
        handlers.forEach(handler => {
            try {
                handler(data);
            } catch (error) {
                console.error(`❌ Error en handler de evento ${event}:`, error);
            }
        });
    }
    
    disconnect() {
        this.reconnectAttempts = this.options.maxReconnectAttempts; // Prevenir reconexión
        this.stopHeartbeat();
        
        if (this.ws) {
            this.ws.close(1000, 'Desconexión intencional');
        }
    }
}

// Uso del cliente robusto
const client = new RobustWebSocketClient('ws://localhost:3000', {
    reconnectInterval: 3000,
    maxReconnectAttempts: 5,
    heartbeatInterval: 30000
});

client.on('connected', () => {
    console.log('🎉 Cliente conectado y listo');
    client.send({
        type: 'subscribe',
        devices: ['all']
    });
});

client.on('message', (message) => {
    if (message.type === 'device_status') {
        console.log(`📱 ${message.device}:`, message.data);
    }
});

client.on('error', (errorInfo) => {
    console.error('🚨 Error del cliente:', errorInfo);
});

client.on('disconnected', (info) => {
    console.log('📴 Cliente desconectado:', info);
});

client.connect();
```

## Testing

### Test de Conexión WebSocket

```javascript
// test-websocket.js
const WebSocket = require('ws');

class WebSocketTester {
    constructor(url) {
        this.url = url;
        this.results = [];
    }
    
    async testConnection() {
        return new Promise((resolve, reject) => {
            const startTime = Date.now();
            const ws = new WebSocket(this.url);
            
            const timeout = setTimeout(() => {
                ws.close();
                reject(new Error('Timeout de conexión'));
            }, 5000);
            
            ws.onopen = () => {
                clearTimeout(timeout);
                const connectionTime = Date.now() - startTime;
                
                this.results.push({
                    test: 'connection',
                    status: 'pass',
                    time: connectionTime
                });
                
                ws.close();
                resolve(connectionTime);
            };
            
            ws.onerror = (error) => {
                clearTimeout(timeout);
                this.results.push({
                    test: 'connection',
                    status: 'fail',
                    error: error.message
                });
                reject(error);
            };
        });
    }
    
    async testSubscription() {
        return new Promise((resolve, reject) => {
            const ws = new WebSocket(this.url);
            let welcomeReceived = false;
            let subscriptionConfirmed = false;
            
            const timeout = setTimeout(() => {
                ws.close();
                reject(new Error('Timeout de suscripción'));
            }, 10000);
            
            ws.onopen = () => {
                ws.send(JSON.stringify({
                    type: 'subscribe',
                    devices: ['TEST001']
                }));
            };
            
            ws.onmessage = (event) => {
                const message = JSON.parse(event.data);
                
                if (message.type === 'welcome') {
                    welcomeReceived = true;
                }
                
                if (message.type === 'device_status' || welcomeReceived) {
                    subscriptionConfirmed = true;
                    clearTimeout(timeout);
                    
                    this.results.push({
                        test: 'subscription',
                        status: 'pass',
                        welcomeReceived,
                        subscriptionConfirmed
                    });
                    
                    ws.close();
                    resolve(true);
                }
            };
            
            ws.onerror = (error) => {
                clearTimeout(timeout);
                this.results.push({
                    test: 'subscription',
                    status: 'fail',
                    error: error.message
                });
                reject(error);
            };
        });
    }
    
    async runAllTests() {
        console.log('🧪 Iniciando tests del WebSocket...');
        
        try {
            // Test de conexión
            console.log('📡 Probando conexión...');
            const connectionTime = await this.testConnection();
            console.log(`✅ Conexión exitosa en ${connectionTime}ms`);
            
            // Test de suscripción
            console.log('📋 Probando suscripción...');
            await this.testSubscription();
            console.log('✅ Suscripción exitosa');
            
            console.log('🎉 Todos los tests pasaron');
            return this.results;
            
        } catch (error) {
            console.error('❌ Test falló:', error.message);
            throw error;
        }
    }
}

// Ejecutar tests
const tester = new WebSocketTester('ws://localhost:3000');
tester.runAllTests()
    .then(results => {
        console.log('📊 Resultados:', results);
    })
    .catch(error => {
        console.error('🚨 Error en tests:', error);
        process.exit(1);
    });
```

### Script de Carga

```javascript
// load-test.js
const WebSocket = require('ws');

class LoadTester {
    constructor(url, options = {}) {
        this.url = url;
        this.options = {
            clients: 10,
            duration: 60000, // 1 minuto
            messageInterval: 1000, // 1 segundo
            ...options
        };
        this.clients = [];
        this.stats = {
            connected: 0,
            messagesReceived: 0,
            errors: 0,
            startTime: null,
            endTime: null
        };
    }
    
    createClient(clientId) {
        return new Promise((resolve, reject) => {
            const ws = new WebSocket(this.url);
            const client = {
                id: clientId,
                ws: ws,
                messagesReceived: 0,
                connected: false
            };
            
            ws.onopen = () => {
                client.connected = true;
                this.stats.connected++;
                
                // Suscribirse a todos los dispositivos
                ws.send(JSON.stringify({
                    type: 'subscribe',
                    devices: ['all']
                }));
                
                console.log(`👤 Cliente ${clientId} conectado`);
                resolve(client);
            };
            
            ws.onmessage = (event) => {
                client.messagesReceived++;
                this.stats.messagesReceived++;
            };
            
            ws.onerror = (error) => {
                this.stats.errors++;
                console.error(`❌ Error cliente ${clientId}:`, error.message);
            };
            
            ws.onclose = () => {
                if (client.connected) {
                    this.stats.connected--;
                    client.connected = false;
                }
            };
            
            setTimeout(() => {
                if (!client.connected) {
                    reject(new Error(`Cliente ${clientId} no pudo conectar`));
                }
            }, 5000);
        });
    }
    
    async runLoadTest() {
        console.log(`🚀 Iniciando test de carga con ${this.options.clients} clientes...`);
        this.stats.startTime = Date.now();
        
        // Crear clientes
        const clientPromises = [];
        for (let i = 0; i < this.options.clients; i++) {
            clientPromises.push(this.createClient(i + 1));
        }
        
        try {
            this.clients = await Promise.all(clientPromises);
            console.log(`✅ ${this.clients.length} clientes conectados`);
            
            // Ejecutar test por la duración especificada
            await new Promise(resolve => {
                setTimeout(resolve, this.options.duration);
            });
            
            // Cerrar conexiones
            this.clients.forEach(client => {
                if (client.ws.readyState === WebSocket.OPEN) {
                    client.ws.close();
                }
            });
            
            this.stats.endTime = Date.now();
            this.printResults();
            
        } catch (error) {
            console.error('❌ Error en test de carga:', error);
            throw error;
        }
    }
    
    printResults() {
        const duration = (this.stats.endTime - this.stats.startTime) / 1000;
        const messagesPerSecond = this.stats.messagesReceived / duration;
        
        console.log('\n📊 Resultados del Test de Carga:');
        console.log('================================');
        console.log(`⏱️  Duración: ${duration.toFixed(2)} segundos`);
        console.log(`👥 Clientes: ${this.options.clients}`);
        console.log(`📨 Mensajes recibidos: ${this.stats.messagesReceived}`);
        console.log(`📈 Mensajes/segundo: ${messagesPerSecond.toFixed(2)}`);
        console.log(`❌ Errores: ${this.stats.errors}`);
        console.log(`✅ Tasa de éxito: ${((this.stats.messagesReceived / (this.stats.messagesReceived + this.stats.errors)) * 100).toFixed(2)}%`);
        
        // Estadísticas por cliente
        console.log('\n👤 Estadísticas por Cliente:');
        this.clients.forEach(client => {
            console.log(`   Cliente ${client.id}: ${client.messagesReceived} mensajes`);
        });
    }
}

// Ejecutar test de carga
const loadTester = new LoadTester('ws://localhost:3000', {
    clients: 20,
    duration: 30000 // 30 segundos
});

loadTester.runLoadTest()
    .then(() => {
        console.log('🎉 Test de carga completado');
        process.exit(0);
    })
    .catch(error => {
        console.error('🚨 Test de carga falló:', error);
        process.exit(1);
    });
```

---

**Nota**: Estos ejemplos proporcionan una base sólida para integrar el servicio de monitoreo de dispositivos en diferentes tipos de aplicaciones y escenarios de uso.