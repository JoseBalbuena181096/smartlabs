/**
 * Device Status WebSocket Server
 * Monitorea constantemente el estado de los dispositivos en la base de datos
 * y envía actualizaciones en tiempo real a los clientes conectados
 */

const mysql = require('mysql2/promise');
const WebSocket = require('ws');
const http = require('http');
const fs = require('fs');
const path = require('path');

// Importar configuraciones centralizadas
const dbConfig = require('../../config/database');
const deviceConfig = require('../../config/device-status');

// Configuración del servidor WebSocket
const PORT = process.env.PORT || deviceConfig.websocket.port;
const server = http.createServer((req, res) => {
    // Endpoint de health check
    if (req.url === '/health' && req.method === 'GET') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'ok', timestamp: new Date().toISOString() }));
        return;
    }
    
    // Para otras rutas, devolver 404
    res.writeHead(404, { 'Content-Type': 'text/plain' });
    res.end('Not Found');
});
const wss = new WebSocket.Server({ server });

// Usar configuraciones centralizadas
const primaryDbConfig = dbConfig.primary;
const fallbackDbConfig = dbConfig.fallback;

// Estado global de los dispositivos
const deviceStatus = {};
const clients = new Map();
let dbConnection = null;

// Función para conectar a la base de datos
async function connectToDatabase() {
    try {
        // Intentar conexión principal
        console.log('🔌 Intentando conectar a la base de datos principal...');
        const connection = await mysql.createConnection(primaryDbConfig);
        console.log('✅ Conectado a la base de datos principal');
        return connection;
    } catch (error) {
        console.warn('⚠️ Error conectando a la base de datos principal:', error.message);
        
        try {
            // Intentar conexión de fallback
            console.log('🔌 Intentando conectar a la base de datos local...');
            const connection = await mysql.createConnection(fallbackDbConfig);
            console.log('✅ Conectado a la base de datos local (fallback)');
            return connection;
        } catch (fallbackError) {
            console.error('❌ Error conectando a la base de datos local:', fallbackError.message);
            throw new Error('No se pudo conectar a ninguna base de datos');
        }
    }
}

// Iniciar monitoreo de dispositivos
async function startMonitoring() {
    console.log('🔍 Iniciando monitoreo de dispositivos...');
    
    // Consultar estado inicial de todos los dispositivos
    await checkAllDevicesStatus();
    
    // Configurar intervalos de verificación según configuración
    setInterval(checkAllDevicesStatus, deviceConfig.monitoring.pollingInterval);
}

// Consultar estado de todos los dispositivos
async function checkAllDevicesStatus() {
    try {
        console.log('🔍 Ejecutando consulta de dispositivos...');
        const query = `
            SELECT t.traffic_device, t.traffic_state, t.traffic_date, 
                   h.hab_name, h.hab_registration, h.hab_email
            FROM traffic t
            LEFT JOIN habintants h ON t.traffic_hab_id = h.hab_id
            WHERE (t.traffic_device, t.traffic_date) IN (
                SELECT traffic_device, MAX(traffic_date) 
                FROM traffic 
                GROUP BY traffic_device
            )
        `;
        
        const [results] = await dbConnection.execute(query);
        console.log(`📊 Consulta ejecutada: ${results.length} dispositivos encontrados`);
        
        // Actualizar estado global
        results.forEach(device => {
            const deviceId = device.traffic_device;
            const previousState = deviceStatus[deviceId];
            const currentState = {
                device: deviceId,
                state: device.traffic_state == 1 ? 'on' : 'off',
                last_activity: device.traffic_date,
                user: device.hab_name,
                user_name: device.hab_name,
                user_registration: device.hab_registration,
                user_email: device.hab_email,
                timestamp: new Date()
            };
            
            // Verificar si el estado ha cambiado
            if (!previousState || 
                previousState.state !== currentState.state || 
                previousState.last_activity !== currentState.last_activity) {
                
                deviceStatus[deviceId] = currentState;
                
                // Notificar a los clientes interesados en este dispositivo
                broadcastDeviceStatus(deviceId, currentState);
                
                if (deviceConfig.logging.level === 'debug') {
                    console.log(`📱 Dispositivo ${deviceId} actualizado: ${currentState.state} (${currentState.last_activity})`);
                }
            }
        });
        
        if (deviceConfig.logging.level === 'debug') {
            console.log(`📊 Estado actualizado: ${results.length} dispositivos monitoreados`);
        }
        
    } catch (error) {
        console.error('❌ Error consultando estado de dispositivos:', error.message);
        console.error('❌ Stack trace:', error.stack);
        
        // Intentar reconectar si hay error de conexión
        if (error.code === 'PROTOCOL_CONNECTION_LOST' || error.code === 'ECONNRESET') {
            console.log('🔄 Intentando reconectar a la base de datos...');
            try {
                dbConnection = await connectToDatabase();
                console.log('✅ Reconexión exitosa');
            } catch (reconnectError) {
                console.error('❌ Error en reconexión:', reconnectError.message);
            }
        }
    }
}

// Enviar estado a los clientes interesados
function broadcastDeviceStatus(deviceId, status) {
    wss.clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            // Verificar si el cliente está interesado en este dispositivo
            const clientDevices = clients.get(client) || [];
            
            if (clientDevices.includes(deviceId) || clientDevices.includes('all')) {
                client.send(JSON.stringify({
                    type: 'device_status',
                    device: deviceId,
                    data: status
                }));
            }
        }
    });
}

// Configurar eventos del WebSocket
wss.on('connection', (ws) => {
    console.log('Cliente conectado');
    clients.set(ws, []);
    
    // Enviar estado actual de todos los dispositivos
    ws.send(JSON.stringify({
        type: 'welcome',
        message: 'Conectado al servidor de estado de dispositivos',
        devices: Object.keys(deviceStatus).length
    }));
    
    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            
            // Manejar suscripción a dispositivos
            if (data.type === 'subscribe') {
                const deviceIds = data.devices || [];
                clients.set(ws, deviceIds);
                console.log(`Cliente suscrito a dispositivos: ${deviceIds.join(', ') || 'ninguno'}`);
                
                // Enviar estado actual de los dispositivos suscritos
                deviceIds.forEach(deviceId => {
                    if (deviceId === 'all') {
                        Object.entries(deviceStatus).forEach(([id, status]) => {
                            ws.send(JSON.stringify({
                                type: 'device_status',
                                device: id,
                                data: status
                            }));
                        });
                    } else if (deviceStatus[deviceId]) {
                        ws.send(JSON.stringify({
                            type: 'device_status',
                            device: deviceId,
                            data: deviceStatus[deviceId]
                        }));
                    }
                });
            }
            
            // Manejar solicitud de estado específico
            if (data.type === 'get_status' && data.device) {
                const deviceId = data.device;
                
                if (deviceStatus[deviceId]) {
                    ws.send(JSON.stringify({
                        type: 'device_status',
                        device: deviceId,
                        data: deviceStatus[deviceId]
                    }));
                } else {
                    // Consultar específicamente este dispositivo
                    const query = `
                        SELECT t.traffic_device, t.traffic_state, t.traffic_date, 
                               h.hab_name, h.hab_registration, h.hab_email
                        FROM traffic t
                        LEFT JOIN habintants h ON t.traffic_hab_id = h.hab_id
                        WHERE t.traffic_device = ?
                        ORDER BY t.traffic_date DESC
                        LIMIT 1
                    `;
                    
                    dbConnection.query(query, [deviceId], (err, results) => {
                        if (err || results.length === 0) {
                            ws.send(JSON.stringify({
                                type: 'device_status',
                                device: deviceId,
                                data: {
                                    device: deviceId,
                                    state: 'unknown',
                                    error: err ? err.message : 'Dispositivo no encontrado'
                                }
                            }));
                        } else {
                            const device = results[0];
                            const status = {
                                device: deviceId,
                                state: device.traffic_state == 1 ? 'on' : 'off',
                                last_activity: device.traffic_date,
                                user: device.hab_name,
                                user_name: device.hab_name,
                                user_registration: device.hab_registration,
                                user_email: device.hab_email,
                                timestamp: new Date()
                            };
                            
                            // Actualizar caché
                            deviceStatus[deviceId] = status;
                            
                            ws.send(JSON.stringify({
                                type: 'device_status',
                                device: deviceId,
                                data: status
                            }));
                        }
                    });
                }
            }
        } catch (e) {
            console.error('Error procesando mensaje:', e);
        }
    });
    
    ws.on('close', () => {
        console.log('Cliente desconectado');
        clients.delete(ws);
    });
});

// Iniciar servidor
server.listen(PORT, async () => {
    console.log(`🚀 Servidor WebSocket iniciado en puerto ${PORT}`);
    try {
        dbConnection = await connectToDatabase();
        await startMonitoring();
    } catch (error) {
        console.error('❌ Error iniciando servidor:', error);
        process.exit(1);
    }
});

// Manejar señales de terminación
process.on('SIGINT', () => {
    console.log('Cerrando servidor...');
    if (dbConnection) {
        dbConnection.end();
    }
    server.close(() => {
        console.log('Servidor cerrado');
        process.exit(0);
    });
});