# Sistema de Estado de Dispositivos - SMARTLABS

## Descripción General

Este sistema implementa el monitoreo en tiempo real del estado de los dispositivos IoT en el dashboard de SMARTLABS. Combina múltiples tecnologías para proporcionar actualizaciones de estado confiables y en tiempo real.

## Funcionalidades Implementadas

### ✅ 1. Estado Inicial desde Base de Datos
- **Funcionalidad**: Al cargar el dashboard, se consulta automáticamente el estado actual del dispositivo seleccionado
- **Implementación**: 
  - Método `getDeviceStatusData()` en `DashboardController.php`
  - Consulta tanto la base de datos externa (192.168.0.100) como la local
  - Determina el estado basado en el campo `traffic_state` (1=on, 0=off)
- **Archivos modificados**:
  - `app/controllers/DashboardController.php`
  - `app/views/dashboard/index.php`

### ✅ 2. Indicadores Visuales de Estado
- **Funcionalidad**: Paneles visuales que muestran el estado del dispositivo (encendido/apagado/desconocido)
- **Elementos UI**:
  - Panel de estado del dispositivo con indicador de color
  - Panel de conexión del dispositivo
  - Última actividad registrada
- **Estados soportados**:
  - `on` (Encendido) - Verde
  - `off` (Apagado) - Rojo  
  - `unknown` (Desconocido) - Amarillo
  - `online`/`offline` (Conectado/Desconectado)

### ✅ 3. Sistema WebSocket
- **Funcionalidad**: Actualizaciones en tiempo real usando WebSocket
- **Archivo**: `public/js/device-status-websocket.js`
- **Características**:
  - Conexión automática al servidor WebSocket (puerto 3000)
  - Reconexión automática en caso de desconexión
  - Suscripción automática al dispositivo seleccionado
  - Callbacks para eventos de conexión y actualización

### ✅ 4. Sistema MQTT
- **Funcionalidad**: Suscripción a tópicos MQTT para recibir actualizaciones de estado
- **Tópicos soportados**:
  - `{device_id}/status/state` - Estado on/off
  - `{device_id}/status/online` - Estado online/offline
  - `{device_id}/status/activity` - Última actividad
  - `{device_id}/command` - Comandos enviados/recibidos
- **Configuración**: Puerto 8083 (WebSocket over MQTT)

### ✅ 5. Monitor de Estado (Polling)
- **Funcionalidad**: Sistema de respaldo que consulta el estado cada 10 segundos
- **Archivo**: `public/js/device-status-monitor.js`
- **Endpoint**: `/Dashboard/status`
- **Uso**: Funciona como fallback si WebSocket o MQTT fallan

### ✅ 6. Configuración Centralizada
- **Funcionalidad**: Configuración centralizada para todos los sistemas
- **Archivo**: `public/js/device-status-config.js`
- **Configuraciones**:
  - URLs de WebSocket y MQTT
  - Intervalos de polling
  - Elementos DOM
  - Textos y clases CSS

## Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                    DASHBOARD UI                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐│
│  │   Estado del    │  │   Conexión del  │  │   Última        ││
│  │   Dispositivo   │  │   Dispositivo   │  │   Actividad     ││
│  └─────────────────┘  └─────────────────┘  └─────────────────┘│
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────┴───────────────────────────────────┐
│                SISTEMA DE ESTADO                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │  WebSocket  │  │    MQTT     │  │   Monitor   │        │
│  │   (Tiempo   │  │ (Suscripción│  │  (Polling)  │        │
│  │    Real)    │  │  a Tópicos) │  │  (Fallback) │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────┴───────────────────────────────────┐
│                 BACKEND (PHP)                               │
│  ┌─────────────────┐  ┌─────────────────┐                  │
│  │ DashboardController │  │  Base de Datos  │                  │
│  │  - getDeviceStatusData()  │  │  - traffic table │                  │
│  │  - status()       │  │  - traffic_state │                  │
│  │  - command()      │  │  - devices table │                  │
│  └─────────────────┘  └─────────────────┘                  │
└─────────────────────────────────────────────────────────────┘
```

## Flujo de Funcionamiento

### 1. Carga Inicial
1. El usuario accede al dashboard
2. El controlador consulta el estado inicial del dispositivo desde la base de datos
3. Se pasa el estado inicial a la vista como `$deviceInitialStatus`
4. JavaScript actualiza la UI con el estado inicial

### 2. Tiempo Real
1. **WebSocket**: Se conecta al servidor en puerto 3000
2. **MQTT**: Se conecta al broker en puerto 8083
3. **Monitor**: Inicia polling cada 10 segundos
4. Cualquier cambio de estado se refleja inmediatamente en la UI

### 3. Cambio de Dispositivo
1. El usuario selecciona un dispositivo diferente
2. Se actualiza la suscripción WebSocket
3. Se actualiza la suscripción MQTT
4. Se solicita el estado actual del nuevo dispositivo
5. Se actualiza la UI con el nuevo estado

## Uso del Sistema

### Para Desarrolladores

#### 1. Inicialización Manual
```javascript
// Inicializar WebSocket
initDeviceStatusWS('ws://localhost:3000');

// Inicializar MQTT
initDeviceStatusMQTT('ws://localhost:8083/mqtt');

// Inicializar monitor
initDeviceStatusMonitor(10000);
```

#### 2. Suscripciones
```javascript
// Suscribirse a un dispositivo específico
subscribeToDevices(['SMART00005']);

// Suscribirse a tópicos MQTT
subscribeToDeviceStatusTopics('SMART00005');
```

#### 3. Callbacks
```javascript
// Escuchar cambios de estado
onDeviceStatusEvent('onStatusUpdate', function(deviceId, status) {
    console.log('Estado actualizado:', deviceId, status);
});

// Escuchar conexiones
onDeviceStatusEvent('onConnect', function() {
    console.log('Sistema conectado');
});
```

### Para Usuarios
1. Seleccionar dispositivo desde el dropdown
2. El estado se actualiza automáticamente
3. Los indicadores visuales muestran:
   - 🟢 Verde: Dispositivo encendido
   - 🔴 Rojo: Dispositivo apagado
   - 🟡 Amarillo: Estado desconocido

## Configuración

### Base de Datos
- **Tabla principal**: `traffic`
- **Campo de estado**: `traffic_state` (BOOLEAN: 1=on, 0=off)
- **Vista**: `traffic_devices` (une traffic con habitantes)

### Servidores
- **WebSocket Server**: Puerto 3000
- **MQTT Broker**: Puerto 8083
- **Base de datos externa**: 192.168.0.100:4000

### Archivos de Configuración
- `public/js/device-status-config.js` - Configuración centralizada
- `config/database.php` - Configuración de base de datos
- `node/device-status-server.js` - Servidor WebSocket

## Arquitectura de Archivos

```
app/
├── controllers/
│   └── DashboardController.php          # Controlador principal
├── views/
│   └── dashboard/
│       └── index.php                    # Vista del dashboard
public/js/
├── device-status-config.js              # Configuración centralizada
├── device-status-websocket.js           # Cliente WebSocket + MQTT
├── device-status-monitor.js             # Monitor de estado (polling)
└── dashboard-legacy.js                  # Funcionalidades legacy
node/
└── device-status-server.js              # Servidor WebSocket
```

## Próximos Pasos

### Sugerencias de Mejora
1. **Persistencia**: Guardar estado en localStorage
2. **Notificaciones**: Notificaciones push para cambios críticos
3. **Histórico**: Gráficos de estado histórico
4. **Múltiples dispositivos**: Monitoreo simultáneo
5. **Alertas**: Sistema de alertas configurables

### Mantenimiento
- Revisar logs del servidor WebSocket
- Monitorear conexiones MQTT
- Verificar sincronización con base de datos
- Optimizar frecuencia de polling según necesidades

---

## Soporte Técnico

Para problemas o mejoras, revisar:
1. **Logs del navegador**: Consola de desarrollador
2. **Logs del servidor**: Node.js WebSocket server
3. **Logs de MQTT**: Broker MQTT
4. **Logs de PHP**: Apache/Nginx error logs

**Desarrollado para SMARTLABS - Sistema de Monitoreo IoT** 