# Ejemplos de Uso - SMARTLABS Device Status Server

## Introducción

Este documento proporciona ejemplos prácticos de cómo integrar y utilizar el **SMARTLABS Device Status Server** en diferentes tipos de aplicaciones y escenarios.

## Configuración Inicial

### Conexión Básica WebSocket

```javascript
// Configuración básica de conexión
const WS_URL = 'ws://localhost:3000';
const ws = new WebSocket(WS_URL);

// Manejo de eventos básicos
ws.onopen = () => {
    console.log('✅ Conectado al servidor de estado de dispositivos');
};

ws.onmessage = (event) => {
    const message = JSON.parse(event.data);
    console.log('📨 Mensaje recibido:', message);
};

ws.onerror = (error) => {
    console.error('❌ Error de WebSocket:', error);
};

ws.onclose = (event) => {
    console.log('🔌 Conexión cerrada:', event.code, event.reason);
};
```

## Ejemplos por Tecnología

### 1. JavaScript Vanilla (Navegador)

#### Monitoreo de Dispositivo Específico

```html
<!DOCTYPE html>
<html>
<head>
    <title>Monitor de Dispositivo</title>
    <style>
        .device-status {
            padding: 20px;
            margin: 10px;
            border-radius: 8px;
            font-family: Arial, sans-serif;
        }
        .status-on { background-color: #d4edda; color: #155724; }
        .status-off { background-color: #f8d7da; color: #721c24; }
        .status-unknown { background-color: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <h1>Monitor de Dispositivos SMARTLABS</h1>
    
    <div id="device-list"></div>
    
    <script>
        class DeviceMonitor {
            constructor() {
                this.ws = null;
                this.devices = new Map();
                this.connect();
            }
            
            connect() {
                this.ws = new WebSocket('ws://localhost:3000');
                
                this.ws.onopen = () => {
                    console.log('Conectado al servidor');
                    // Suscribirse a todos los dispositivos
                    this.subscribe(['all']);
                };
                
                this.ws.onmessage = (event) => {
                    const message = JSON.parse(event.data);
                    this.handleMessage(message);
                };
                
                this.ws.onclose = () => {
                    console.log('Conexión perdida, reintentando...');
                    setTimeout(() => this.connect(), 5000);
                };
            }
            
            subscribe(deviceIds) {
                const message = {
                    type: 'subscribe',
                    devices: deviceIds
                };
                this.ws.send(JSON.stringify(message));
            }
            
            handleMessage(message) {
                switch (message.type) {
                    case 'welcome':
                        console.log(`Servidor listo: ${message.devices} dispositivos`);
                        break;
                        
                    case 'device_status':
                        this.updateDeviceDisplay(message.device, message.data);
                        break;
                }
            }
            
            updateDeviceDisplay(deviceId, data) {
                this.devices.set(deviceId, data);
                this.renderDevices();
            }
            
            renderDevices() {
                const container = document.getElementById('device-list');
                container.innerHTML = '';
                
                this.devices.forEach((data, deviceId) => {
                    const deviceDiv = document.createElement('div');
                    deviceDiv.className = `device-status status-${data.state}`;
                    
                    deviceDiv.innerHTML = `
                        <h3>Dispositivo: ${deviceId}</h3>
                        <p><strong>Estado:</strong> ${data.state.toUpperCase()}</p>
                        <p><strong>Usuario:</strong> ${data.user || 'N/A'}</p>
                        <p><strong>Última actividad:</strong> ${new Date(data.last_activity).toLocaleString()}</p>
                        <p><strong>Actualizado:</strong> ${new Date(data.timestamp).toLocaleString()}</p>
                    `;
                    
                    container.appendChild(deviceDiv);
                });
            }
        }
        
        // Inicializar monitor
        const monitor = new DeviceMonitor();
    </script>
</body>
</html>
```

### 2. Node.js (Cliente)

#### Cliente de Monitoreo con Reconexión Automática

```javascript
// device-monitor-client.js
const WebSocket = require('ws');
const EventEmitter = require('events');

class DeviceStatusClient extends EventEmitter {
    constructor(url = 'ws://localhost:3000') {
        super();
        this.url = url;
        this.ws = null;
        this.reconnectInterval = 5000;
        this.maxReconnectAttempts = 10;
        this.reconnectAttempts = 0;
        this.subscribedDevices = [];
        
        this.connect();
    }
    
    connect() {
        try {
            this.ws = new WebSocket(this.url);
            
            this.ws.on('open', () => {
                console.log('✅ Conectado al servidor de dispositivos');
                this.reconnectAttempts = 0;
                this.emit('connected');
                
                // Re-suscribirse a dispositivos si había suscripciones previas
                if (this.subscribedDevices.length > 0) {
                    this.subscribe(this.subscribedDevices);
                }
            });
            
            this.ws.on('message', (data) => {
                try {
                    const message = JSON.parse(data);
                    this.handleMessage(message);
                } catch (error) {
                    console.error('Error parseando mensaje:', error);
                }
            });
            
            this.ws.on('close', (code, reason) => {
                console.log(`🔌 Conexión cerrada: ${code} - ${reason}`);
                this.emit('disconnected', { code, reason });
                this.scheduleReconnect();
            });
            
            this.ws.on('error', (error) => {
                console.error('❌ Error de WebSocket:', error);
                this.emit('error', error);
            });
            
        } catch (error) {
            console.error('Error conectando:', error);
            this.scheduleReconnect();
        }
    }
    
    scheduleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`🔄 Reintentando conexión en ${this.reconnectInterval}ms (intento ${this.reconnectAttempts})`);
            
            setTimeout(() => {
                this.connect();
            }, this.reconnectInterval);
        } else {
            console.error('❌ Máximo número de reintentos alcanzado');
            this.emit('maxReconnectAttemptsReached');
        }
    }
    
    subscribe(deviceIds) {
        this.subscribedDevices = Array.isArray(deviceIds) ? deviceIds : [deviceIds];
        
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            const message = {
                type: 'subscribe',
                devices: this.subscribedDevices
            };
            this.ws.send(JSON.stringify(message));
            console.log(`📡 Suscrito a dispositivos: ${this.subscribedDevices.join(', ')}`);
        }
    }
    
    getDeviceStatus(deviceId) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            const message = {
                type: 'get_status',
                device: deviceId
            };
            this.ws.send(JSON.stringify(message));
        }
    }
    
    handleMessage(message) {
        switch (message.type) {
            case 'welcome':
                console.log(`🎉 ${message.message}`);
                console.log(`📊 Dispositivos disponibles: ${message.devices}`);
                this.emit('welcome', message);
                break;
                
            case 'device_status':
                console.log(`📱 ${message.device}: ${message.data.state} (${message.data.user || 'Sin usuario'})`);
                this.emit('deviceStatus', message.device, message.data);
                break;
                
            default:
                console.log('📨 Mensaje desconocido:', message);
        }
    }
    
    disconnect() {
        if (this.ws) {
            this.ws.close(1000, 'Cliente desconectando');
        }
    }
}

// Ejemplo de uso
const client = new DeviceStatusClient();

// Eventos del cliente
client.on('connected', () => {
    console.log('Cliente conectado exitosamente');
    
    // Suscribirse a dispositivos específicos
    client.subscribe(['device001', 'device002', 'device003']);
});

client.on('deviceStatus', (deviceId, data) => {
    console.log(`Estado actualizado - ${deviceId}:`, {
        estado: data.state,
        usuario: data.user,
        ultimaActividad: data.last_activity
    });
    
    // Aquí puedes agregar lógica personalizada
    if (data.state === 'on') {
        console.log(`🟢 Dispositivo ${deviceId} está ENCENDIDO`);
    } else {
        console.log(`🔴 Dispositivo ${deviceId} está APAGADO`);
    }
});

client.on('error', (error) => {
    console.error('Error del cliente:', error);
});

// Consultar estado específico cada 30 segundos
setInterval(() => {
    client.getDeviceStatus('device001');
}, 30000);

// Manejo de cierre graceful
process.on('SIGINT', () => {
    console.log('\n🛑 Cerrando cliente...');
    client.disconnect();
    process.exit(0);
});

module.exports = DeviceStatusClient;
```

### 3. React.js (Frontend)

#### Hook Personalizado para Monitoreo

```jsx
// hooks/useDeviceStatus.js
import { useState, useEffect, useCallback, useRef } from 'react';

const useDeviceStatus = (wsUrl = 'ws://localhost:3000') => {
    const [devices, setDevices] = useState(new Map());
    const [connectionStatus, setConnectionStatus] = useState('disconnected');
    const [error, setError] = useState(null);
    const wsRef = useRef(null);
    const reconnectTimeoutRef = useRef(null);
    
    const connect = useCallback(() => {
        try {
            wsRef.current = new WebSocket(wsUrl);
            
            wsRef.current.onopen = () => {
                console.log('Conectado al servidor de dispositivos');
                setConnectionStatus('connected');
                setError(null);
            };
            
            wsRef.current.onmessage = (event) => {
                try {
                    const message = JSON.parse(event.data);
                    
                    if (message.type === 'device_status') {
                        setDevices(prev => {
                            const newDevices = new Map(prev);
                            newDevices.set(message.device, message.data);
                            return newDevices;
                        });
                    }
                } catch (err) {
                    console.error('Error parseando mensaje:', err);
                }
            };
            
            wsRef.current.onclose = () => {
                console.log('Conexión cerrada');
                setConnectionStatus('disconnected');
                
                // Reconectar después de 5 segundos
                reconnectTimeoutRef.current = setTimeout(() => {
                    connect();
                }, 5000);
            };
            
            wsRef.current.onerror = (error) => {
                console.error('Error de WebSocket:', error);
                setError('Error de conexión');
                setConnectionStatus('error');
            };
            
        } catch (err) {
            setError('Error conectando al servidor');
            setConnectionStatus('error');
        }
    }, [wsUrl]);
    
    const subscribe = useCallback((deviceIds) => {
        if (wsRef.current && wsRef.current.readyState === WebSocket.OPEN) {
            const message = {
                type: 'subscribe',
                devices: Array.isArray(deviceIds) ? deviceIds : [deviceIds]
            };
            wsRef.current.send(JSON.stringify(message));
        }
    }, []);
    
    const getDeviceStatus = useCallback((deviceId) => {
        if (wsRef.current && wsRef.current.readyState === WebSocket.OPEN) {
            const message = {
                type: 'get_status',
                device: deviceId
            };
            wsRef.current.send(JSON.stringify(message));
        }
    }, []);
    
    useEffect(() => {
        connect();
        
        return () => {
            if (reconnectTimeoutRef.current) {
                clearTimeout(reconnectTimeoutRef.current);
            }
            if (wsRef.current) {
                wsRef.current.close();
            }
        };
    }, [connect]);
    
    return {
        devices,
        connectionStatus,
        error,
        subscribe,
        getDeviceStatus
    };
};

export default useDeviceStatus;
```

#### Componente de Dashboard

```jsx
// components/DeviceDashboard.jsx
import React, { useEffect, useState } from 'react';
import useDeviceStatus from '../hooks/useDeviceStatus';

const DeviceCard = ({ deviceId, data }) => {
    const getStatusColor = (state) => {
        switch (state) {
            case 'on': return '#28a745';
            case 'off': return '#dc3545';
            default: return '#ffc107';
        }
    };
    
    const getStatusText = (state) => {
        switch (state) {
            case 'on': return 'ENCENDIDO';
            case 'off': return 'APAGADO';
            default: return 'DESCONOCIDO';
        }
    };
    
    return (
        <div style={{
            border: '1px solid #ddd',
            borderRadius: '8px',
            padding: '16px',
            margin: '8px',
            backgroundColor: '#fff',
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
        }}>
            <h3 style={{ margin: '0 0 12px 0' }}>📱 {deviceId}</h3>
            
            <div style={{
                display: 'flex',
                alignItems: 'center',
                marginBottom: '8px'
            }}>
                <div style={{
                    width: '12px',
                    height: '12px',
                    borderRadius: '50%',
                    backgroundColor: getStatusColor(data.state),
                    marginRight: '8px'
                }}></div>
                <strong>{getStatusText(data.state)}</strong>
            </div>
            
            {data.user && (
                <p><strong>👤 Usuario:</strong> {data.user}</p>
            )}
            
            {data.user_registration && (
                <p><strong>🆔 Matrícula:</strong> {data.user_registration}</p>
            )}
            
            <p><strong>🕒 Última actividad:</strong></p>
            <p style={{ fontSize: '0.9em', color: '#666' }}>
                {new Date(data.last_activity).toLocaleString()}
            </p>
            
            <p><strong>🔄 Actualizado:</strong></p>
            <p style={{ fontSize: '0.9em', color: '#666' }}>
                {new Date(data.timestamp).toLocaleString()}
            </p>
        </div>
    );
};

const ConnectionStatus = ({ status, error }) => {
    const getStatusInfo = () => {
        switch (status) {
            case 'connected':
                return { color: '#28a745', text: '🟢 Conectado', icon: '✅' };
            case 'disconnected':
                return { color: '#ffc107', text: '🟡 Desconectado', icon: '⚠️' };
            case 'error':
                return { color: '#dc3545', text: '🔴 Error', icon: '❌' };
            default:
                return { color: '#6c757d', text: '⚪ Desconocido', icon: '❓' };
        }
    };
    
    const statusInfo = getStatusInfo();
    
    return (
        <div style={{
            padding: '12px',
            backgroundColor: '#f8f9fa',
            borderRadius: '4px',
            marginBottom: '16px',
            border: `2px solid ${statusInfo.color}`
        }}>
            <strong style={{ color: statusInfo.color }}>
                {statusInfo.icon} Estado de Conexión: {statusInfo.text}
            </strong>
            {error && (
                <p style={{ color: '#dc3545', margin: '4px 0 0 0' }}>
                    Error: {error}
                </p>
            )}
        </div>
    );
};

const DeviceDashboard = () => {
    const { devices, connectionStatus, error, subscribe, getDeviceStatus } = useDeviceStatus();
    const [selectedDevices, setSelectedDevices] = useState(['all']);
    const [customDevice, setCustomDevice] = useState('');
    
    useEffect(() => {
        if (connectionStatus === 'connected') {
            subscribe(selectedDevices);
        }
    }, [connectionStatus, selectedDevices, subscribe]);
    
    const handleSubscribeToDevice = () => {
        if (customDevice.trim()) {
            setSelectedDevices(prev => {
                if (!prev.includes(customDevice.trim())) {
                    return [...prev.filter(d => d !== 'all'), customDevice.trim()];
                }
                return prev;
            });
            setCustomDevice('');
        }
    };
    
    const handleSubscribeToAll = () => {
        setSelectedDevices(['all']);
    };
    
    const handleGetSpecificStatus = (deviceId) => {
        getDeviceStatus(deviceId);
    };
    
    return (
        <div style={{ padding: '20px', fontFamily: 'Arial, sans-serif' }}>
            <h1>🏢 SMARTLABS - Monitor de Dispositivos</h1>
            
            <ConnectionStatus status={connectionStatus} error={error} />
            
            <div style={{
                backgroundColor: '#f8f9fa',
                padding: '16px',
                borderRadius: '8px',
                marginBottom: '20px'
            }}>
                <h3>🎛️ Controles</h3>
                
                <div style={{ marginBottom: '12px' }}>
                    <button 
                        onClick={handleSubscribeToAll}
                        style={{
                            padding: '8px 16px',
                            backgroundColor: '#007bff',
                            color: 'white',
                            border: 'none',
                            borderRadius: '4px',
                            cursor: 'pointer',
                            marginRight: '8px'
                        }}
                    >
                        📡 Suscribirse a Todos
                    </button>
                    
                    <span style={{ color: '#666' }}>
                        Dispositivos suscritos: {selectedDevices.join(', ')}
                    </span>
                </div>
                
                <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                    <input
                        type="text"
                        value={customDevice}
                        onChange={(e) => setCustomDevice(e.target.value)}
                        placeholder="ID del dispositivo (ej: device001)"
                        style={{
                            padding: '8px',
                            border: '1px solid #ddd',
                            borderRadius: '4px',
                            flex: 1
                        }}
                        onKeyPress={(e) => {
                            if (e.key === 'Enter') {
                                handleSubscribeToDevice();
                            }
                        }}
                    />
                    <button 
                        onClick={handleSubscribeToDevice}
                        style={{
                            padding: '8px 16px',
                            backgroundColor: '#28a745',
                            color: 'white',
                            border: 'none',
                            borderRadius: '4px',
                            cursor: 'pointer'
                        }}
                    >
                        ➕ Suscribir
                    </button>
                </div>
            </div>
            
            <h2>📊 Dispositivos Monitoreados ({devices.size})</h2>
            
            {devices.size === 0 ? (
                <div style={{
                    textAlign: 'center',
                    padding: '40px',
                    color: '#666',
                    backgroundColor: '#f8f9fa',
                    borderRadius: '8px'
                }}>
                    <h3>📭 No hay dispositivos para mostrar</h3>
                    <p>Conéctate al servidor y suscríbete a dispositivos para ver su estado.</p>
                </div>
            ) : (
                <div style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
                    gap: '16px'
                }}>
                    {Array.from(devices.entries()).map(([deviceId, data]) => (
                        <div key={deviceId}>
                            <DeviceCard deviceId={deviceId} data={data} />
                            <button
                                onClick={() => handleGetSpecificStatus(deviceId)}
                                style={{
                                    width: '100%',
                                    padding: '8px',
                                    backgroundColor: '#17a2b8',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '0 0 8px 8px',
                                    cursor: 'pointer',
                                    fontSize: '0.9em'
                                }}
                            >
                                🔄 Actualizar Estado
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default DeviceDashboard;
```

### 4. Flutter/Dart (Mobile)

#### Cliente WebSocket para Flutter

```dart
// lib/services/device_status_service.dart
import 'dart:async';
import 'dart:convert';
import 'package:web_socket_channel/web_socket_channel.dart';
import 'package:web_socket_channel/status.dart' as status;

class DeviceData {
  final String device;
  final String state;
  final String lastActivity;
  final String? user;
  final String? userRegistration;
  final String? userEmail;
  final DateTime timestamp;

  DeviceData({
    required this.device,
    required this.state,
    required this.lastActivity,
    this.user,
    this.userRegistration,
    this.userEmail,
    required this.timestamp,
  });

  factory DeviceData.fromJson(Map<String, dynamic> json) {
    return DeviceData(
      device: json['device'],
      state: json['state'],
      lastActivity: json['last_activity'],
      user: json['user'],
      userRegistration: json['user_registration'],
      userEmail: json['user_email'],
      timestamp: DateTime.parse(json['timestamp']),
    );
  }
}

class DeviceStatusService {
  static const String _wsUrl = 'ws://192.168.1.100:3000';
  
  WebSocketChannel? _channel;
  final StreamController<Map<String, DeviceData>> _devicesController = 
      StreamController<Map<String, DeviceData>>.broadcast();
  final StreamController<String> _connectionController = 
      StreamController<String>.broadcast();
  
  final Map<String, DeviceData> _devices = {};
  Timer? _reconnectTimer;
  bool _isConnecting = false;
  
  // Streams públicos
  Stream<Map<String, DeviceData>> get devicesStream => _devicesController.stream;
  Stream<String> get connectionStream => _connectionController.stream;
  
  // Getters
  Map<String, DeviceData> get devices => Map.unmodifiable(_devices);
  bool get isConnected => _channel != null;
  
  Future<void> connect() async {
    if (_isConnecting) return;
    
    _isConnecting = true;
    _connectionController.add('connecting');
    
    try {
      _channel = WebSocketChannel.connect(Uri.parse(_wsUrl));
      
      _channel!.stream.listen(
        _handleMessage,
        onError: _handleError,
        onDone: _handleDisconnection,
      );
      
      _connectionController.add('connected');
      print('✅ Conectado al servidor de dispositivos');
      
    } catch (error) {
      print('❌ Error conectando: $error');
      _connectionController.add('error');
      _scheduleReconnect();
    } finally {
      _isConnecting = false;
    }
  }
  
  void _handleMessage(dynamic message) {
    try {
      final data = jsonDecode(message);
      
      switch (data['type']) {
        case 'welcome':
          print('🎉 ${data['message']}');
          print('📊 Dispositivos disponibles: ${data['devices']}');
          break;
          
        case 'device_status':
          final deviceId = data['device'];
          final deviceData = DeviceData.fromJson(data['data']);
          
          _devices[deviceId] = deviceData;
          _devicesController.add(Map.from(_devices));
          
          print('📱 $deviceId: ${deviceData.state} (${deviceData.user ?? "Sin usuario"})');
          break;
      }
    } catch (error) {
      print('❌ Error procesando mensaje: $error');
    }
  }
  
  void _handleError(error) {
    print('❌ Error de WebSocket: $error');
    _connectionController.add('error');
    _scheduleReconnect();
  }
  
  void _handleDisconnection() {
    print('🔌 Conexión cerrada');
    _connectionController.add('disconnected');
    _channel = null;
    _scheduleReconnect();
  }
  
  void _scheduleReconnect() {
    _reconnectTimer?.cancel();
    _reconnectTimer = Timer(const Duration(seconds: 5), () {
      print('🔄 Reintentando conexión...');
      connect();
    });
  }
  
  void subscribe(List<String> deviceIds) {
    if (_channel != null) {
      final message = {
        'type': 'subscribe',
        'devices': deviceIds,
      };
      
      _channel!.sink.add(jsonEncode(message));
      print('📡 Suscrito a dispositivos: ${deviceIds.join(", ")}');
    }
  }
  
  void getDeviceStatus(String deviceId) {
    if (_channel != null) {
      final message = {
        'type': 'get_status',
        'device': deviceId,
      };
      
      _channel!.sink.add(jsonEncode(message));
    }
  }
  
  void disconnect() {
    _reconnectTimer?.cancel();
    _channel?.sink.close(status.goingAway);
    _channel = null;
    _connectionController.add('disconnected');
  }
  
  void dispose() {
    disconnect();
    _devicesController.close();
    _connectionController.close();
  }
}
```

#### Widget de Flutter para Mostrar Dispositivos

```dart
// lib/widgets/device_monitor_widget.dart
import 'package:flutter/material.dart';
import '../services/device_status_service.dart';

class DeviceMonitorWidget extends StatefulWidget {
  @override
  _DeviceMonitorWidgetState createState() => _DeviceMonitorWidgetState();
}

class _DeviceMonitorWidgetState extends State<DeviceMonitorWidget> {
  final DeviceStatusService _service = DeviceStatusService();
  final TextEditingController _deviceController = TextEditingController();
  
  @override
  void initState() {
    super.initState();
    _service.connect();
  }
  
  @override
  void dispose() {
    _service.dispose();
    _deviceController.dispose();
    super.dispose();
  }
  
  Color _getStatusColor(String state) {
    switch (state) {
      case 'on':
        return Colors.green;
      case 'off':
        return Colors.red;
      default:
        return Colors.orange;
    }
  }
  
  String _getStatusText(String state) {
    switch (state) {
      case 'on':
        return 'ENCENDIDO';
      case 'off':
        return 'APAGADO';
      default:
        return 'DESCONOCIDO';
    }
  }
  
  Widget _buildConnectionStatus() {
    return StreamBuilder<String>(
      stream: _service.connectionStream,
      builder: (context, snapshot) {
        final status = snapshot.data ?? 'disconnected';
        
        Color color;
        IconData icon;
        String text;
        
        switch (status) {
          case 'connected':
            color = Colors.green;
            icon = Icons.wifi;
            text = 'Conectado';
            break;
          case 'connecting':
            color = Colors.orange;
            icon = Icons.wifi_off;
            text = 'Conectando...';
            break;
          case 'error':
            color = Colors.red;
            icon = Icons.error;
            text = 'Error';
            break;
          default:
            color = Colors.grey;
            icon = Icons.wifi_off;
            text = 'Desconectado';
        }
        
        return Container(
          padding: EdgeInsets.all(12),
          margin: EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            border: Border.all(color: color),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Row(
            children: [
              Icon(icon, color: color),
              SizedBox(width: 8),
              Text(
                'Estado: $text',
                style: TextStyle(
                  color: color,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
  
  Widget _buildDeviceCard(String deviceId, DeviceData data) {
    return Card(
      margin: EdgeInsets.all(8),
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.devices, size: 24),
                SizedBox(width: 8),
                Expanded(
                  child: Text(
                    deviceId,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: _getStatusColor(data.state),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    _getStatusText(data.state),
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
            SizedBox(height: 12),
            
            if (data.user != null) ..[
              Row(
                children: [
                  Icon(Icons.person, size: 16, color: Colors.grey[600]),
                  SizedBox(width: 4),
                  Text('Usuario: ${data.user}'),
                ],
              ),
              SizedBox(height: 4),
            ],
            
            if (data.userRegistration != null) ..[
              Row(
                children: [
                  Icon(Icons.badge, size: 16, color: Colors.grey[600]),
                  SizedBox(width: 4),
                  Text('Matrícula: ${data.userRegistration}'),
                ],
              ),
              SizedBox(height: 4),
            ],
            
            Row(
              children: [
                Icon(Icons.access_time, size: 16, color: Colors.grey[600]),
                SizedBox(width: 4),
                Expanded(
                  child: Text(
                    'Última actividad: ${DateTime.parse(data.lastActivity).toLocal()}',
                    style: TextStyle(fontSize: 12),
                  ),
                ),
              ],
            ),
            
            SizedBox(height: 8),
            
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                TextButton.icon(
                  onPressed: () => _service.getDeviceStatus(deviceId),
                  icon: Icon(Icons.refresh, size: 16),
                  label: Text('Actualizar'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('SMARTLABS - Monitor'),
        backgroundColor: Colors.blue[700],
      ),
      body: Column(
        children: [
          _buildConnectionStatus(),
          
          // Controles
          Container(
            padding: EdgeInsets.all(16),
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _deviceController,
                        decoration: InputDecoration(
                          labelText: 'ID del Dispositivo',
                          hintText: 'ej: device001',
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ),
                    SizedBox(width: 8),
                    ElevatedButton(
                      onPressed: () {
                        if (_deviceController.text.isNotEmpty) {
                          _service.subscribe([_deviceController.text]);
                          _deviceController.clear();
                        }
                      },
                      child: Text('Suscribir'),
                    ),
                  ],
                ),
                SizedBox(height: 8),
                ElevatedButton(
                  onPressed: () => _service.subscribe(['all']),
                  child: Text('Suscribirse a Todos'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                  ),
                ),
              ],
            ),
          ),
          
          // Lista de dispositivos
          Expanded(
            child: StreamBuilder<Map<String, DeviceData>>(
              stream: _service.devicesStream,
              builder: (context, snapshot) {
                if (!snapshot.hasData || snapshot.data!.isEmpty) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.devices_other,
                          size: 64,
                          color: Colors.grey[400],
                        ),
                        SizedBox(height: 16),
                        Text(
                          'No hay dispositivos para mostrar',
                          style: TextStyle(
                            fontSize: 18,
                            color: Colors.grey[600],
                          ),
                        ),
                        SizedBox(height: 8),
                        Text(
                          'Conéctate y suscríbete a dispositivos',
                          style: TextStyle(
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  );
                }
                
                final devices = snapshot.data!;
                
                return ListView.builder(
                  itemCount: devices.length,
                  itemBuilder: (context, index) {
                    final entry = devices.entries.elementAt(index);
                    return _buildDeviceCard(entry.key, entry.value);
                  },
                );
              },
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          if (_service.isConnected) {
            _service.disconnect();
          } else {
            _service.connect();
          }
        },
        child: StreamBuilder<String>(
          stream: _service.connectionStream,
          builder: (context, snapshot) {
            final status = snapshot.data ?? 'disconnected';
            return Icon(
              status == 'connected' ? Icons.wifi_off : Icons.wifi,
            );
          },
        ),
      ),
    );
  }
}
```

## Casos de Uso Comunes

### 1. Dashboard de Administración

```javascript
// Monitoreo de todos los dispositivos con alertas
class AdminDashboard {
    constructor() {
        this.client = new DeviceStatusClient();
        this.alerts = [];
        this.setupAlerts();
    }
    
    setupAlerts() {
        this.client.on('deviceStatus', (deviceId, data) => {
            // Alerta si un dispositivo lleva más de 1 hora encendido
            if (data.state === 'on') {
                const lastActivity = new Date(data.last_activity);
                const hourAgo = new Date(Date.now() - 3600000);
                
                if (lastActivity < hourAgo) {
                    this.createAlert({
                        type: 'warning',
                        device: deviceId,
                        message: `Dispositivo ${deviceId} lleva más de 1 hora encendido`,
                        timestamp: new Date()
                    });
                }
            }
        });
    }
    
    createAlert(alert) {
        this.alerts.push(alert);
        console.warn('🚨 ALERTA:', alert.message);
        
        // Enviar notificación (email, SMS, etc.)
        this.sendNotification(alert);
    }
    
    sendNotification(alert) {
        // Implementar envío de notificaciones
        // Email, SMS, Push notifications, etc.
    }
}
```

### 2. Sistema de Reportes

```javascript
// Generación de reportes de uso
class DeviceReportGenerator {
    constructor() {
        this.client = new DeviceStatusClient();
        this.usageData = new Map();
        this.setupDataCollection();
    }
    
    setupDataCollection() {
        this.client.on('deviceStatus', (deviceId, data) => {
            if (!this.usageData.has(deviceId)) {
                this.usageData.set(deviceId, []);
            }
            
            this.usageData.get(deviceId).push({
                timestamp: new Date(),
                state: data.state,
                user: data.user,
                lastActivity: data.last_activity
            });
        });
    }
    
    generateDailyReport(deviceId, date) {
        const deviceData = this.usageData.get(deviceId) || [];
        const dayStart = new Date(date);
        dayStart.setHours(0, 0, 0, 0);
        const dayEnd = new Date(date);
        dayEnd.setHours(23, 59, 59, 999);
        
        const dayData = deviceData.filter(entry => {
            const entryDate = new Date(entry.timestamp);
            return entryDate >= dayStart && entryDate <= dayEnd;
        });
        
        const totalUsage = this.calculateUsageTime(dayData);
        const uniqueUsers = new Set(dayData.map(d => d.user).filter(u => u)).size;
        
        return {
            deviceId,
            date: date.toISOString().split('T')[0],
            totalUsageMinutes: totalUsage,
            uniqueUsers,
            activations: dayData.filter(d => d.state === 'on').length,
            deactivations: dayData.filter(d => d.state === 'off').length
        };
    }
    
    calculateUsageTime(data) {
        let totalMinutes = 0;
        let lastOnTime = null;
        
        data.forEach(entry => {
            if (entry.state === 'on' && !lastOnTime) {
                lastOnTime = new Date(entry.timestamp);
            } else if (entry.state === 'off' && lastOnTime) {
                const offTime = new Date(entry.timestamp);
                totalMinutes += (offTime - lastOnTime) / (1000 * 60);
                lastOnTime = null;
            }
        });
        
        return Math.round(totalMinutes);
    }
}
```

## Manejo de Errores y Mejores Prácticas

### 1. Reconexión Robusta

```javascript
class RobustWebSocketClient {
    constructor(url) {
        this.url = url;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 10;
        this.reconnectDelay = 1000;
        this.maxReconnectDelay = 30000;
        this.backoffFactor = 1.5;
    }
    
    connect() {
        return new Promise((resolve, reject) => {
            try {
                this.ws = new WebSocket(this.url);
                
                this.ws.onopen = () => {
                    console.log('✅ Conectado exitosamente');
                    this.reconnectAttempts = 0;
                    this.reconnectDelay = 1000;
                    resolve();
                };
                
                this.ws.onerror = (error) => {
                    console.error('❌ Error de conexión:', error);
                    reject(error);
                };
                
                this.ws.onclose = () => {
                    this.scheduleReconnect();
                };
                
            } catch (error) {
                reject(error);
            }
        });
    }
    
    scheduleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('❌ Máximo número de reintentos alcanzado');
            return;
        }
        
        this.reconnectAttempts++;
        
        console.log(`🔄 Reintentando conexión en ${this.reconnectDelay}ms (intento ${this.reconnectAttempts})`);
        
        setTimeout(() => {
            this.connect().catch(() => {
                // El error ya se maneja en connect()
            });
        }, this.reconnectDelay);
        
        // Exponential backoff
        this.reconnectDelay = Math.min(
            this.reconnectDelay * this.backoffFactor,
            this.maxReconnectDelay
        );
    }
}
```

### 2. Rate Limiting y Throttling

```javascript
class ThrottledDeviceClient {
    constructor() {
        this.lastRequestTime = new Map();
        this.minRequestInterval = 1000; // 1 segundo entre requests
    }
    
    getDeviceStatus(deviceId) {
        const now = Date.now();
        const lastRequest = this.lastRequestTime.get(deviceId) || 0;
        
        if (now - lastRequest < this.minRequestInterval) {
            console.warn(`⚠️ Rate limit: Esperando antes de consultar ${deviceId}`);
            return;
        }
        
        this.lastRequestTime.set(deviceId, now);
        
        // Realizar la consulta
        this.ws.send(JSON.stringify({
            type: 'get_status',
            device: deviceId
        }));
    }
}
```

### 3. Caché Local

```javascript
class CachedDeviceClient {
    constructor() {
        this.cache = new Map();
        this.cacheTimeout = 30000; // 30 segundos
    }
    
    getDeviceStatus(deviceId, useCache = true) {
        if (useCache && this.cache.has(deviceId)) {
            const cached = this.cache.get(deviceId);
            const age = Date.now() - cached.timestamp;
            
            if (age < this.cacheTimeout) {
                console.log(`📋 Usando datos en caché para ${deviceId}`);
                return Promise.resolve(cached.data);
            }
        }
        
        // Solicitar datos frescos
        return this.requestFreshData(deviceId);
    }
    
    updateCache(deviceId, data) {
        this.cache.set(deviceId, {
            data,
            timestamp: Date.now()
        });
    }
}
```

## Conclusión

Estos ejemplos muestran cómo integrar el **SMARTLABS Device Status Server** en diferentes tipos de aplicaciones y escenarios. El servidor proporciona una base sólida para el monitoreo en tiempo real de dispositivos IoT, con flexibilidad para adaptarse a diferentes necesidades y tecnologías.

### Puntos Clave:

- **Flexibilidad**: Compatible con múltiples tecnologías y plataformas
- **Escalabilidad**: Diseñado para manejar múltiples clientes y dispositivos
- **Robustez**: Incluye manejo de errores y reconexión automática
- **Tiempo Real**: Actualizaciones instantáneas mediante WebSocket
- **Facilidad de Uso**: API simple y bien documentada

---

**Versión**: 2.0.0  
**Fecha**: Enero 2025  
**Mantenido por**: Equipo SMARTLABS