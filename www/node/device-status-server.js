/**
 * Device Status WebSocket Server
 * Monitorea constantemente el estado de los dispositivos en la base de datos
 * y envía actualizaciones en tiempo real a los clientes conectados
 */

const WebSocket = require('ws');
const mysql = require('mysql2');
const http = require('http');

// Configuración del servidor WebSocket
const PORT = process.env.PORT || 3000;
const server = http.createServer();
const wss = new WebSocket.Server({ server });

// Configuración de la base de datos
const dbConfig = {
    host: '192.168.0.100',
    user: 'root',
    password: 'emqxpass',
    database: 'emqx',
    port: 4000
};

// Fallback a base de datos local si la externa falla
const localDbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'emqx'
};

// Estado global de los dispositivos
const deviceStatus = {};
const clients = new Map();
let dbConnection = null;

// Conectar a la base de datos
function connectToDatabase() {
    console.log('Intentando conectar a la base de datos externa...');
    
    // Intentar conectar a la base de datos externa
    dbConnection = mysql.createConnection(dbConfig);
    
    dbConnection.connect((err) => {
        if (err) {
            console.error('Error conectando a la base de datos externa:', err.message);
            console.log('Intentando conectar a la base de datos local...');
            
            // Si falla, intentar con la base de datos local
            dbConnection = mysql.createConnection(localDbConfig);
            
            dbConnection.connect((err) => {
                if (err) {
                    console.error('Error conectando a la base de datos local:', err.message);
                    console.log('Reintentando en 5 segundos...');
                    setTimeout(connectToDatabase, 5000);
                } else {
                    console.log('Conectado a la base de datos local');
                    startMonitoring();
                }
            });
        } else {
            console.log('Conectado a la base de datos externa');
            startMonitoring();
        }
    });
    
    dbConnection.on('error', (err) => {
        console.error('Error de base de datos:', err);
        if (err.code === 'PROTOCOL_CONNECTION_LOST') {
            console.log('Reconectando a la base de datos...');
            connectToDatabase();
        } else {
            throw err;
        }
    });
}

// Iniciar monitoreo de dispositivos
function startMonitoring() {
    console.log('Iniciando monitoreo de dispositivos...');
    
    // Consultar estado inicial de todos los dispositivos
    checkAllDevicesStatus();
    
    // Configurar intervalos de verificación
    setInterval(checkAllDevicesStatus, 5000); // Cada 5 segundos
}

// Consultar estado de todos los dispositivos
function checkAllDevicesStatus() {
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
    
    dbConnection.query(query, (err, results) => {
        if (err) {
            console.error('Error consultando estado de dispositivos:', err);
            return;
        }
        
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
                
                console.log(`Dispositivo ${deviceId} actualizado: ${currentState.state} (${currentState.last_activity})`);
            }
        });
    });
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
server.listen(PORT, () => {
    console.log(`Servidor WebSocket iniciado en puerto ${PORT}`);
    connectToDatabase();
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