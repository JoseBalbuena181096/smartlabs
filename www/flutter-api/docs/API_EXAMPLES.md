# Ejemplos de Uso - SMARTLABS Flutter API

## Introducción

Este documento proporciona ejemplos prácticos de cómo utilizar la SMARTLABS Flutter API, incluyendo casos de uso comunes, ejemplos de código y mejores prácticas.

## Configuración Inicial

### Headers Requeridos

Todas las peticiones deben incluir los siguientes headers:

```http
Content-Type: application/json
X-API-Key: tu_api_key_aqui
```

### Base URL

```
Desarrollo: http://localhost:3000
Producción: https://api.smartlabs.com
```

## Ejemplos de Endpoints

### 1. Gestión de Usuarios

#### Buscar Usuario por Matrícula

**Request:**
```http
GET /api/users/registration/12345
X-API-Key: your_api_key_here
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Usuario encontrado exitosamente",
  "data": {
    "id": 1,
    "name": "Juan Pérez González",
    "registration": "12345",
    "email": "juan.perez@universidad.edu",
    "cards_number": "ABCD1234EFGH",
    "device_id": "LAB001"
  }
}
```

**Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Usuario no encontrado",
  "data": null
}
```

#### Buscar Usuario por RFID

**Request:**
```http
GET /api/users/rfid/ABCD1234EFGH
X-API-Key: your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "message": "Usuario encontrado por RFID",
  "data": {
    "id": 1,
    "name": "Juan Pérez González",
    "registration": "12345",
    "email": "juan.perez@universidad.edu",
    "cards_number": "ABCD1234EFGH",
    "device_id": "LAB001"
  }
}
```

#### Obtener Historial de Usuario

**Request:**
```http
GET /api/users/registration/12345/history?limit=5
X-API-Key: your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "message": "Historial obtenido exitosamente",
  "data": [
    {
      "id": 101,
      "device_serie": "LAB001",
      "action": "login",
      "timestamp": "2024-01-15T10:30:00Z",
      "duration_minutes": 45,
      "status": "completed"
    },
    {
      "id": 100,
      "device_serie": "LAB002",
      "action": "login",
      "timestamp": "2024-01-14T14:15:00Z",
      "duration_minutes": 30,
      "status": "completed"
    }
  ],
  "pagination": {
    "total": 25,
    "limit": 5,
    "offset": 0
  }
}
```

### 2. Control de Dispositivos

#### Controlar Dispositivo (Encender/Apagar)

**Request:**
```http
POST /api/devices/control
Content-Type: application/json
X-API-Key: your_api_key_here

{
  "registration": "12345",
  "device_serie": "LAB001",
  "action": 1
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Dispositivo controlado exitosamente",
  "data": {
    "device_serie": "LAB001",
    "user_registration": "12345",
    "action": "on",
    "timestamp": "2024-01-15T10:30:00Z",
    "status": "active",
    "session_id": "sess_abc123"
  }
}
```

**Request (Apagar):**
```json
{
  "registration": "12345",
  "device_serie": "LAB001",
  "action": 0
}
```

**Response:**
```json
{
  "success": true,
  "message": "Dispositivo apagado exitosamente",
  "data": {
    "device_serie": "LAB001",
    "user_registration": "12345",
    "action": "off",
    "timestamp": "2024-01-15T11:15:00Z",
    "status": "inactive",
    "session_duration_minutes": 45
  }
}
```

#### Obtener Información de Dispositivo

**Request:**
```http
GET /api/devices/LAB001
X-API-Key: your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "message": "Información de dispositivo obtenida",
  "data": {
    "id": 1,
    "device_serie": "LAB001",
    "name": "Estación de Trabajo 1",
    "type": "workstation",
    "location": "Laboratorio A - Mesa 1",
    "status": "active",
    "current_user": {
      "registration": "12345",
      "name": "Juan Pérez González",
      "session_start": "2024-01-15T10:30:00Z"
    },
    "specifications": {
      "cpu": "Intel i7-10700K",
      "ram": "32GB DDR4",
      "gpu": "NVIDIA RTX 3070",
      "storage": "1TB NVMe SSD"
    },
    "last_maintenance": "2024-01-01T00:00:00Z",
    "next_maintenance": "2024-04-01T00:00:00Z"
  }
}
```

#### Obtener Estado de Dispositivo

**Request:**
```http
GET /api/devices/LAB001/status
X-API-Key: your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "message": "Estado de dispositivo obtenido",
  "data": {
    "device_serie": "LAB001",
    "status": "active",
    "online": true,
    "current_user": "12345",
    "session_start": "2024-01-15T10:30:00Z",
    "session_duration_minutes": 45,
    "last_ping": "2024-01-15T11:14:30Z",
    "sensors": {
      "temperature": 35.2,
      "humidity": 45.8,
      "voltage": 12.1
    }
  }
}
```

### 3. Sistema de Préstamos

#### Realizar Préstamo

**Request:**
```http
POST /api/prestamos/control
Content-Type: application/json
X-API-Key: your_api_key_here

{
  "registration": "12345",
  "device_serie": "EQUIP001",
  "action": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Préstamo procesado exitosamente",
  "data": {
    "loan_id": "loan_abc123",
    "user_registration": "12345",
    "device_serie": "EQUIP001",
    "action": "loan_start",
    "timestamp": "2024-01-15T10:30:00Z",
    "expected_return": "2024-01-15T18:30:00Z",
    "status": "active"
  }
}
```

#### Devolver Equipo

**Request:**
```json
{
  "registration": "12345",
  "device_serie": "EQUIP001",
  "action": 0
}
```

**Response:**
```json
{
  "success": true,
  "message": "Equipo devuelto exitosamente",
  "data": {
    "loan_id": "loan_abc123",
    "user_registration": "12345",
    "device_serie": "EQUIP001",
    "action": "loan_end",
    "start_time": "2024-01-15T10:30:00Z",
    "end_time": "2024-01-15T16:45:00Z",
    "duration_hours": 6.25,
    "status": "completed"
  }
}
```

## Ejemplos de Código

### JavaScript/Node.js

```javascript
const axios = require('axios');

class SmartLabsAPI {
    constructor(baseURL, apiKey) {
        this.client = axios.create({
            baseURL: baseURL,
            headers: {
                'X-API-Key': apiKey,
                'Content-Type': 'application/json'
            }
        });
    }

    async getUserByRegistration(registration) {
        try {
            const response = await this.client.get(`/api/users/registration/${registration}`);
            return response.data;
        } catch (error) {
            throw new Error(`Error getting user: ${error.response?.data?.message || error.message}`);
        }
    }

    async controlDevice(registration, deviceSerie, action) {
        try {
            const response = await this.client.post('/api/devices/control', {
                registration,
                device_serie: deviceSerie,
                action
            });
            return response.data;
        } catch (error) {
            throw new Error(`Error controlling device: ${error.response?.data?.message || error.message}`);
        }
    }

    async getDeviceStatus(deviceSerie) {
        try {
            const response = await this.client.get(`/api/devices/${deviceSerie}/status`);
            return response.data;
        } catch (error) {
            throw new Error(`Error getting device status: ${error.response?.data?.message || error.message}`);
        }
    }
}

// Uso
const api = new SmartLabsAPI('http://localhost:3000', 'your_api_key_here');

// Ejemplo de uso
async function example() {
    try {
        // Buscar usuario
        const user = await api.getUserByRegistration('12345');
        console.log('Usuario encontrado:', user.data.name);

        // Encender dispositivo
        const controlResult = await api.controlDevice('12345', 'LAB001', 1);
        console.log('Dispositivo encendido:', controlResult.data.status);

        // Verificar estado
        const status = await api.getDeviceStatus('LAB001');
        console.log('Estado del dispositivo:', status.data.status);

    } catch (error) {
        console.error('Error:', error.message);
    }
}

example();
```

### Python

```python
import requests
import json
from typing import Dict, Any, Optional

class SmartLabsAPI:
    def __init__(self, base_url: str, api_key: str):
        self.base_url = base_url.rstrip('/')
        self.headers = {
            'X-API-Key': api_key,
            'Content-Type': 'application/json'
        }
    
    def _make_request(self, method: str, endpoint: str, data: Optional[Dict] = None) -> Dict[str, Any]:
        url = f"{self.base_url}{endpoint}"
        
        try:
            if method.upper() == 'GET':
                response = requests.get(url, headers=self.headers)
            elif method.upper() == 'POST':
                response = requests.post(url, headers=self.headers, json=data)
            else:
                raise ValueError(f"Unsupported HTTP method: {method}")
            
            response.raise_for_status()
            return response.json()
        
        except requests.exceptions.RequestException as e:
            raise Exception(f"API request failed: {str(e)}")
    
    def get_user_by_registration(self, registration: str) -> Dict[str, Any]:
        return self._make_request('GET', f'/api/users/registration/{registration}')
    
    def get_user_by_rfid(self, rfid: str) -> Dict[str, Any]:
        return self._make_request('GET', f'/api/users/rfid/{rfid}')
    
    def control_device(self, registration: str, device_serie: str, action: int) -> Dict[str, Any]:
        data = {
            'registration': registration,
            'device_serie': device_serie,
            'action': action
        }
        return self._make_request('POST', '/api/devices/control', data)
    
    def get_device_status(self, device_serie: str) -> Dict[str, Any]:
        return self._make_request('GET', f'/api/devices/{device_serie}/status')
    
    def get_device_history(self, device_serie: str, limit: int = 20) -> Dict[str, Any]:
        return self._make_request('GET', f'/api/devices/{device_serie}/history?limit={limit}')

# Ejemplo de uso
if __name__ == "__main__":
    api = SmartLabsAPI('http://localhost:3000', 'your_api_key_here')
    
    try:
        # Buscar usuario
        user = api.get_user_by_registration('12345')
        print(f"Usuario encontrado: {user['data']['name']}")
        
        # Encender dispositivo
        control_result = api.control_device('12345', 'LAB001', 1)
        print(f"Dispositivo encendido: {control_result['data']['status']}")
        
        # Verificar estado
        status = api.get_device_status('LAB001')
        print(f"Estado del dispositivo: {status['data']['status']}")
        
    except Exception as e:
        print(f"Error: {e}")
```

### Flutter/Dart

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class SmartLabsAPI {
  final String baseUrl;
  final String apiKey;
  
  SmartLabsAPI({required this.baseUrl, required this.apiKey});
  
  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'X-API-Key': apiKey,
  };
  
  Future<Map<String, dynamic>> getUserByRegistration(String registration) async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/users/registration/$registration'),
      headers: _headers,
    );
    
    if (response.statusCode == 200) {
      return json.decode(response.body);
    } else {
      throw Exception('Failed to get user: ${response.body}');
    }
  }
  
  Future<Map<String, dynamic>> controlDevice({
    required String registration,
    required String deviceSerie,
    required int action,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/devices/control'),
      headers: _headers,
      body: json.encode({
        'registration': registration,
        'device_serie': deviceSerie,
        'action': action,
      }),
    );
    
    if (response.statusCode == 200) {
      return json.decode(response.body);
    } else {
      throw Exception('Failed to control device: ${response.body}');
    }
  }
  
  Future<Map<String, dynamic>> getDeviceStatus(String deviceSerie) async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/devices/$deviceSerie/status'),
      headers: _headers,
    );
    
    if (response.statusCode == 200) {
      return json.decode(response.body);
    } else {
      throw Exception('Failed to get device status: ${response.body}');
    }
  }
}

// Ejemplo de uso en Flutter
class DeviceControlScreen extends StatefulWidget {
  @override
  _DeviceControlScreenState createState() => _DeviceControlScreenState();
}

class _DeviceControlScreenState extends State<DeviceControlScreen> {
  final SmartLabsAPI api = SmartLabsAPI(
    baseUrl: 'http://localhost:3000',
    apiKey: 'your_api_key_here',
  );
  
  Future<void> turnOnDevice(String registration, String deviceSerie) async {
    try {
      final result = await api.controlDevice(
        registration: registration,
        deviceSerie: deviceSerie,
        action: 1,
      );
      
      if (result['success']) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Dispositivo encendido exitosamente')),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Control de Dispositivos')),
      body: Center(
        child: ElevatedButton(
          onPressed: () => turnOnDevice('12345', 'LAB001'),
          child: Text('Encender Dispositivo'),
        ),
      ),
    );
  }
}
```

## Casos de Uso Comunes

### 1. Flujo de Inicio de Sesión

```javascript
async function loginFlow(registration, deviceSerie) {
    try {
        // 1. Validar usuario
        const user = await api.getUserByRegistration(registration);
        if (!user.success) {
            throw new Error('Usuario no encontrado');
        }
        
        // 2. Verificar estado del dispositivo
        const deviceStatus = await api.getDeviceStatus(deviceSerie);
        if (deviceStatus.data.status === 'active') {
            throw new Error('Dispositivo ya está en uso');
        }
        
        // 3. Encender dispositivo
        const controlResult = await api.controlDevice(registration, deviceSerie, 1);
        
        return {
            success: true,
            message: 'Sesión iniciada exitosamente',
            sessionId: controlResult.data.session_id
        };
        
    } catch (error) {
        return {
            success: false,
            message: error.message
        };
    }
}
```

### 2. Flujo de Cierre de Sesión

```javascript
async function logoutFlow(registration, deviceSerie) {
    try {
        // 1. Verificar que el usuario tiene una sesión activa
        const deviceStatus = await api.getDeviceStatus(deviceSerie);
        if (deviceStatus.data.current_user !== registration) {
            throw new Error('No tienes una sesión activa en este dispositivo');
        }
        
        // 2. Apagar dispositivo
        const controlResult = await api.controlDevice(registration, deviceSerie, 0);
        
        return {
            success: true,
            message: 'Sesión cerrada exitosamente',
            duration: controlResult.data.session_duration_minutes
        };
        
    } catch (error) {
        return {
            success: false,
            message: error.message
        };
    }
}
```

### 3. Monitoreo de Dispositivos

```javascript
class DeviceMonitor {
    constructor(api, deviceSerie) {
        this.api = api;
        this.deviceSerie = deviceSerie;
        this.intervalId = null;
    }
    
    startMonitoring(callback, interval = 30000) {
        this.intervalId = setInterval(async () => {
            try {
                const status = await this.api.getDeviceStatus(this.deviceSerie);
                callback(null, status.data);
            } catch (error) {
                callback(error, null);
            }
        }, interval);
    }
    
    stopMonitoring() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
}

// Uso
const monitor = new DeviceMonitor(api, 'LAB001');
monitor.startMonitoring((error, status) => {
    if (error) {
        console.error('Error monitoring device:', error);
    } else {
        console.log('Device status:', status);
        // Actualizar UI con el estado del dispositivo
    }
});
```

## Manejo de Errores

### Códigos de Error Comunes

| Código | Descripción | Acción Recomendada |
|--------|-------------|--------------------|
| 400 | Bad Request | Verificar parámetros de entrada |
| 401 | Unauthorized | Verificar API Key |
| 404 | Not Found | Verificar que el recurso existe |
| 409 | Conflict | Recurso ya está en uso |
| 429 | Too Many Requests | Implementar rate limiting |
| 500 | Internal Server Error | Reintentar después de un tiempo |

### Ejemplo de Manejo de Errores

```javascript
class APIErrorHandler {
    static handle(error) {
        if (error.response) {
            const { status, data } = error.response;
            
            switch (status) {
                case 400:
                    return {
                        type: 'validation',
                        message: data.message || 'Datos inválidos',
                        details: data.error
                    };
                    
                case 401:
                    return {
                        type: 'auth',
                        message: 'API Key inválida o faltante',
                        action: 'refresh_token'
                    };
                    
                case 404:
                    return {
                        type: 'not_found',
                        message: data.message || 'Recurso no encontrado'
                    };
                    
                case 409:
                    return {
                        type: 'conflict',
                        message: data.message || 'Recurso en conflicto'
                    };
                    
                case 429:
                    return {
                        type: 'rate_limit',
                        message: 'Demasiadas peticiones',
                        retryAfter: error.response.headers['retry-after']
                    };
                    
                default:
                    return {
                        type: 'server_error',
                        message: 'Error interno del servidor'
                    };
            }
        } else {
            return {
                type: 'network',
                message: 'Error de conexión'
            };
        }
    }
}
```

## Mejores Prácticas

### 1. Rate Limiting

```javascript
class RateLimitedAPI {
    constructor(api, maxRequests = 100, timeWindow = 60000) {
        this.api = api;
        this.requests = [];
        this.maxRequests = maxRequests;
        this.timeWindow = timeWindow;
    }
    
    async makeRequest(method, ...args) {
        const now = Date.now();
        
        // Limpiar requests antiguos
        this.requests = this.requests.filter(time => now - time < this.timeWindow);
        
        if (this.requests.length >= this.maxRequests) {
            throw new Error('Rate limit exceeded');
        }
        
        this.requests.push(now);
        return await this.api[method](...args);
    }
}
```

### 2. Retry Logic

```javascript
class RetryableAPI {
    constructor(api, maxRetries = 3, baseDelay = 1000) {
        this.api = api;
        this.maxRetries = maxRetries;
        this.baseDelay = baseDelay;
    }
    
    async makeRequestWithRetry(method, ...args) {
        let lastError;
        
        for (let attempt = 0; attempt <= this.maxRetries; attempt++) {
            try {
                return await this.api[method](...args);
            } catch (error) {
                lastError = error;
                
                if (attempt === this.maxRetries) {
                    break;
                }
                
                // Exponential backoff
                const delay = this.baseDelay * Math.pow(2, attempt);
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }
        
        throw lastError;
    }
}
```

### 3. Caching

```javascript
class CachedAPI {
    constructor(api, cacheTTL = 300000) { // 5 minutos
        this.api = api;
        this.cache = new Map();
        this.cacheTTL = cacheTTL;
    }
    
    async getUserByRegistration(registration) {
        const cacheKey = `user:${registration}`;
        const cached = this.cache.get(cacheKey);
        
        if (cached && Date.now() - cached.timestamp < this.cacheTTL) {
            return cached.data;
        }
        
        const data = await this.api.getUserByRegistration(registration);
        this.cache.set(cacheKey, {
            data,
            timestamp: Date.now()
        });
        
        return data;
    }
}
```

---

**Ejemplos de API v1.0**  
**SMARTLABS Team**  
**Fecha: 2024**