# Ejemplos de Uso - SMARTLABS Flutter API

## Tabla de Contenidos

1. [Configuración Inicial](#configuración-inicial)
2. [Ejemplos de Usuarios](#ejemplos-de-usuarios)
3. [Ejemplos de Dispositivos](#ejemplos-de-dispositivos)
4. [Ejemplos de Préstamos](#ejemplos-de-préstamos)
5. [Ejemplos MQTT](#ejemplos-mqtt)
6. [Manejo de Errores](#manejo-de-errores)
7. [Integración con Flutter](#integración-con-flutter)

## Configuración Inicial

### Base URL
```
http://localhost:3000
```

### Headers Recomendados
```http
Content-Type: application/json
Accept: application/json
```

## Ejemplos de Usuarios

### 1. Buscar Usuario por Matrícula

**Request:**
```http
GET /api/users/registration/A01234567
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Usuario encontrado exitosamente",
  "data": {
    "id": 1,
    "name": "Juan Pérez González",
    "registration": "A01234567",
    "email": "juan.perez@tec.mx",
    "cards_number": "1234567890",
    "device_id": null
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

### 2. Buscar Usuario por RFID

**Request:**
```http
GET /api/users/rfid/1234567890
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Usuario encontrado exitosamente",
  "data": {
    "id": 1,
    "name": "Juan Pérez González",
    "registration": "A01234567",
    "email": "juan.perez@tec.mx",
    "cards_number": "1234567890",
    "device_id": null
  }
}
```

### 3. Obtener Historial de Usuario

**Request:**
```http
GET /api/users/registration/A01234567/history?limit=5
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Historial obtenido exitosamente",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Pérez González",
      "registration": "A01234567"
    },
    "history": [
      {
        "id": 101,
        "device_serie": "SMART10001",
        "device_alias": "Laboratorio IoT 1",
        "action": "on",
        "timestamp": "2024-01-15T10:30:00.000Z"
      },
      {
        "id": 100,
        "device_serie": "SMART10001",
        "device_alias": "Laboratorio IoT 1",
        "action": "off",
        "timestamp": "2024-01-15T09:45:00.000Z"
      }
    ],
    "total": 2
  }
}
```

### 4. Validar Usuario

**Request:**
```http
GET /api/users/validate/A01234567
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Usuario válido",
  "data": {
    "registration": "A01234567",
    "valid": true
  }
}
```

## Ejemplos de Dispositivos

### 1. Controlar Dispositivo

**Request:**
```http
POST /api/devices/control
Content-Type: application/json

{
  "registration": "A01234567",
  "device_serie": "SMART10001",
  "action": 1
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Dispositivo encendido exitosamente",
  "data": {
    "action": "on",
    "state": 1,
    "device": {
      "serie": "SMART10001",
      "alias": "Laboratorio IoT 1"
    },
    "user": {
      "name": "Juan Pérez González"
    },
    "timestamp": "2024-01-15T10:30:00.000Z"
  }
}
```

**Request (Apagar):**
```http
POST /api/devices/control
Content-Type: application/json

{
  "registration": "A01234567",
  "device_serie": "SMART10001",
  "action": 0
}
```

### 2. Obtener Información de Dispositivo

**Request:**
```http
GET /api/devices/SMART10001
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Información del dispositivo obtenida exitosamente",
  "data": {
    "device": {
      "id": 1,
      "alias": "Laboratorio IoT 1",
      "serie": "SMART10001",
      "date": "2024-01-01T00:00:00.000Z"
    },
    "currentState": {
      "state": 1,
      "lastUpdate": "2024-01-15T10:30:00.000Z",
      "isFirstUse": false
    }
  }
}
```

### 3. Obtener Estado de Dispositivo

**Request:**
```http
GET /api/devices/SMART10001/status
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Estado obtenido exitosamente",
  "data": {
    "device_serie": "SMART10001",
    "state": 1,
    "lastUpdate": "2024-01-15T10:30:00.000Z",
    "isActive": true
  }
}
```

### 4. Listar Todos los Dispositivos

**Request:**
```http
GET /api/devices
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Dispositivos obtenidos exitosamente",
  "data": {
    "devices": [
      {
        "id": 1,
        "alias": "Laboratorio IoT 1",
        "serie": "SMART10001",
        "date": "2024-01-01T00:00:00.000Z",
        "currentState": 1
      },
      {
        "id": 2,
        "alias": "Laboratorio IoT 2",
        "serie": "SMART10002",
        "date": "2024-01-01T00:00:00.000Z",
        "currentState": 0
      }
    ],
    "total": 2
  }
}
```

## Ejemplos de Préstamos

### 1. Control Manual de Préstamo

**Request:**
```http
POST /api/prestamo/control
Content-Type: application/json

{
  "registration": "A01234567",
  "device_serie": "SMART10001",
  "action": 1
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Préstamo procesado exitosamente",
  "data": {
    "device_serie": "SMART10001",
    "device_name": "Laboratorio IoT 1",
    "user": {
      "name": "Juan Pérez González"
    },
    "action": "on",
    "state": 1,
    "timestamp": "2024-01-15T10:30:00.000Z"
  }
}
```

## Ejemplos MQTT

### 1. Obtener Estado del MQTT Listener

**Request:**
```http
GET /api/mqtt/status
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "mqtt_listener": {
      "active": true,
      "session": {
        "active": false,
        "user": null,
        "count": 0
      }
    }
  }
}
```

### 2. Iniciar MQTT Listener

**Request:**
```http
POST /api/mqtt/control
Content-Type: application/json

{
  "action": "start"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "MQTT Listener iniciado correctamente",
  "data": {
    "active": true
  }
}
```

### 3. Detener MQTT Listener

**Request:**
```http
POST /api/mqtt/control
Content-Type: application/json

{
  "action": "stop"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "MQTT Listener detenido correctamente",
  "data": {
    "active": false
  }
}
```

## Manejo de Errores

### 1. Datos Inválidos (400 Bad Request)

**Request:**
```http
POST /api/devices/control
Content-Type: application/json

{
  "registration": "",
  "device_serie": "SMART10001",
  "action": 5
}
```

**Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "Datos inválidos",
  "error": "La acción debe ser 0 (apagar) o 1 (encender)"
}
```

### 2. Usuario No Encontrado (404 Not Found)

**Request:**
```http
GET /api/users/registration/INVALID123
```

**Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Usuario no encontrado",
  "data": null
}
```

### 3. Rate Limit Excedido (429 Too Many Requests)

**Response (429 Too Many Requests):**
```json
{
  "success": false,
  "message": "Demasiadas solicitudes, intenta de nuevo más tarde",
  "error": "Rate limit excedido"
}
```

### 4. Error Interno del Servidor (500 Internal Server Error)

**Response (500 Internal Server Error):**
```json
{
  "success": false,
  "message": "Error interno del servidor",
  "error": "Database connection failed"
}
```

## Integración con Flutter

### 1. Configuración HTTP Client

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class SmartLabsAPI {
  static const String baseUrl = 'http://localhost:3000';
  
  static Map<String, String> get headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
}
```

### 2. Buscar Usuario

```dart
Future<Map<String, dynamic>?> getUserByRegistration(String registration) async {
  try {
    final response = await http.get(
      Uri.parse('${SmartLabsAPI.baseUrl}/api/users/registration/$registration'),
      headers: SmartLabsAPI.headers,
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success']) {
        return data['data'];
      }
    }
    return null;
  } catch (e) {
    print('Error: $e');
    return null;
  }
}
```

### 3. Controlar Dispositivo

```dart
Future<bool> controlDevice(String registration, String deviceSerie, int action) async {
  try {
    final response = await http.post(
      Uri.parse('${SmartLabsAPI.baseUrl}/api/devices/control'),
      headers: SmartLabsAPI.headers,
      body: json.encode({
        'registration': registration,
        'device_serie': deviceSerie,
        'action': action,
      }),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return data['success'] ?? false;
    }
    return false;
  } catch (e) {
    print('Error: $e');
    return false;
  }
}
```

### 4. Manejo de Errores en Flutter

```dart
class APIResponse<T> {
  final bool success;
  final String message;
  final T? data;
  final String? error;
  
  APIResponse({
    required this.success,
    required this.message,
    this.data,
    this.error,
  });
  
  factory APIResponse.fromJson(Map<String, dynamic> json, T? Function(dynamic) fromJsonT) {
    return APIResponse<T>(
      success: json['success'] ?? false,
      message: json['message'] ?? '',
      data: json['data'] != null ? fromJsonT(json['data']) : null,
      error: json['error'],
    );
  }
}
```

### 5. Widget de Control de Dispositivo

```dart
class DeviceControlWidget extends StatefulWidget {
  final String deviceSerie;
  final String userRegistration;
  
  const DeviceControlWidget({
    Key? key,
    required this.deviceSerie,
    required this.userRegistration,
  }) : super(key: key);
  
  @override
  _DeviceControlWidgetState createState() => _DeviceControlWidgetState();
}

class _DeviceControlWidgetState extends State<DeviceControlWidget> {
  bool isLoading = false;
  bool deviceState = false;
  
  Future<void> toggleDevice() async {
    setState(() {
      isLoading = true;
    });
    
    final action = deviceState ? 0 : 1; // 0 = off, 1 = on
    final success = await controlDevice(
      widget.userRegistration,
      widget.deviceSerie,
      action,
    );
    
    if (success) {
      setState(() {
        deviceState = !deviceState;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(deviceState ? 'Dispositivo encendido' : 'Dispositivo apagado'),
          backgroundColor: Colors.green,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Error al controlar dispositivo'),
          backgroundColor: Colors.red,
        ),
      );
    }
    
    setState(() {
      isLoading = false;
    });
  }
  
  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Text(
              widget.deviceSerie,
              style: Theme.of(context).textTheme.headline6,
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: isLoading ? null : toggleDevice,
              child: isLoading
                  ? const CircularProgressIndicator()
                  : Text(deviceState ? 'Apagar' : 'Encender'),
            ),
          ],
        ),
      ),
    );
  }
}
```

## Notas Adicionales

### Timeouts Recomendados
- **Conexión:** 30 segundos
- **Lectura:** 60 segundos
- **Escritura:** 30 segundos

### Reintentos
- **Máximo:** 3 intentos
- **Delay:** Exponencial (1s, 2s, 4s)

### Logging
```dart
void logAPICall(String method, String endpoint, int statusCode) {
  print('[$method] $endpoint -> $statusCode');
}
```

### Validación de Datos
```dart
bool isValidRegistration(String registration) {
  return RegExp(r'^[A-Z]\d{8}$').hasMatch(registration);
}

bool isValidDeviceSerie(String serie) {
  return RegExp(r'^SMART\d{5}$').hasMatch(serie);
}
```