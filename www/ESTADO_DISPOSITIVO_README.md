# Sistema de Monitoreo de Estado de Dispositivos SMARTLABS

Este sistema permite monitorear constantemente el estado de los dispositivos SMARTLABS, consultando en tiempo real la base de datos `traffic_devices` para verificar si cada dispositivo está encendido (1) o apagado (0).

## Características

- **Monitoreo constante**: Verifica el estado cada 5 segundos
- **Múltiples métodos de conexión**:
  - AJAX polling (funciona en todos los navegadores)
  - WebSockets (tiempo real, requiere servidor Node.js)
- **Integración con MQTT**: Compatible con el sistema MQTT existente
- **Indicadores visuales**: Muestra el estado actual y la conexión del dispositivo
- **Fallback automático**: Si falla la conexión externa, usa la base de datos local
- **Caché de estado**: Guarda el último estado conocido para evitar parpadeos

## Componentes

### 1. Monitoreo AJAX (device-status-monitor.js)

Este componente consulta periódicamente el endpoint `/Dashboard/status` para obtener el estado actual del dispositivo seleccionado.

**Características:**
- No requiere configuración adicional
- Funciona en todos los navegadores
- Actualiza la interfaz automáticamente

### 2. Monitoreo WebSocket (device-status-websocket.js)

Este componente establece una conexión WebSocket con el servidor para recibir actualizaciones en tiempo real del estado de los dispositivos.

**Características:**
- Actualizaciones instantáneas
- Menor carga en el servidor
- Soporta múltiples dispositivos simultáneamente

### 3. Servidor WebSocket (device-status-server.js)

Servidor Node.js que consulta la base de datos y envía actualizaciones en tiempo real a los clientes conectados.

**Características:**
- Monitoreo constante de todos los dispositivos
- Notificaciones en tiempo real
- Conexión a base de datos externa o local (fallback)

## Instalación

### Monitoreo AJAX (ya instalado)

No requiere instalación adicional, ya está integrado en el dashboard.

### Servidor WebSocket (opcional, para tiempo real)

1. Navegar a la carpeta `node`:
   ```bash
   cd node
   ```

2. Instalar dependencias:
   ```bash
   npm install
   ```

3. Iniciar el servidor:
   ```bash
   npm start
   ```

## Uso

### En el Dashboard

El sistema ya está integrado en el dashboard. Los indicadores de estado aparecen automáticamente:

- **Estado del dispositivo**: Muestra si el dispositivo está encendido (verde), apagado (rojo) o en estado desconocido (amarillo)
- **Conexión del dispositivo**: Muestra si el dispositivo está conectado (verde) o desconectado (rojo)

### Funciones JavaScript disponibles

#### Monitoreo AJAX

```javascript
// Iniciar monitoreo (ya se ejecuta automáticamente)
initDeviceStatusMonitor();

// Verificar estado inmediatamente
checkDeviceStatus();

// Cambiar intervalo de actualización (en milisegundos)
window.deviceStatusMonitor.pollingInterval = 10000; // 10 segundos

// Detener monitoreo
stopDeviceStatusMonitor();

// Función de prueba (simular estados)
testDeviceStatusMonitor('on', true); // Encendido y conectado
testDeviceStatusMonitor('off', false); // Apagado y desconectado
testDeviceStatusMonitor('unknown', false); // Estado desconocido
```

#### WebSocket (si está habilitado)

```javascript
// Iniciar conexión WebSocket (ya se ejecuta automáticamente)
initDeviceStatusWS('ws://localhost:3000');

// Suscribirse a dispositivos específicos
subscribeToDevices(['SMART00001', 'SMART00002']);

// Suscribirse a todos los dispositivos
subscribeToDevices(['all']);

// Solicitar estado de un dispositivo específico
requestDeviceStatus('SMART00001');

// Registrar callback para eventos de estado
onDeviceStatusEvent('onStatusUpdate', function(deviceId, status) {
    console.log(`Dispositivo ${deviceId} actualizado:`, status);
});
```

## Solución de problemas

### El estado no se actualiza

1. Verificar que el dispositivo esté seleccionado en el dropdown
2. Comprobar que existan registros en la tabla `traffic_devices` para ese dispositivo
3. Verificar la conexión a la base de datos (externa o local)
4. Revisar la consola del navegador para posibles errores

### Error de conexión WebSocket

1. Verificar que el servidor WebSocket esté ejecutándose
2. Comprobar la URL del servidor WebSocket (por defecto ws://localhost:3000)
3. Verificar que no haya bloqueos de firewall o proxy

## Notas técnicas

- El estado se determina consultando el campo `traffic_state` en la tabla `traffic_devices`
- Se considera que el dispositivo está "encendido" si `traffic_state = 1` y "apagado" si `traffic_state = 0`
- Se considera que el dispositivo está "conectado" si su última actividad fue en los últimos 5 minutos
- El sistema primero intenta consultar la base de datos externa (192.168.0.100) y si falla, usa la local 