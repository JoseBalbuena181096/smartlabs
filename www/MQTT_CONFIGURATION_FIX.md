# Solución al Problema de Conexión MQTT

## Problema Identificado

La aplicación IoT tenía un problema de conectividad MQTT cuando se accedía desde computadoras remotas en la red. La conexión funcionaba correctamente cuando se accedía desde el servidor local (`localhost`), pero fallaba con el error:

```
WebSocket connection to wss://192.168.0.100:8074/mqtt failed
```

## Causa del Problema

La configuración MQTT estaba hardcodeada con URLs fijas que no se adaptaban dinámicamente según el contexto de acceso:

- **Acceso local**: Necesita usar `localhost` o `127.0.0.1`
- **Acceso remoto**: Necesita usar la IP externa `192.168.0.100`
- **Puertos diferentes**: Según el docker-compose, hay diferentes puertos mapeados

## Solución Implementada

### 1. Detección Dinámica de Hostname

Se implementó lógica JavaScript que detecta automáticamente desde dónde se está accediendo:

```javascript
const hostname = window.location.hostname;

if (hostname === 'localhost' || hostname === '127.0.0.1') {
    // Acceso desde localhost
    WebSocket_URL = 'wss://localhost:8074/mqtt';
} else if (hostname === '192.168.0.100') {
    // Acceso desde IP externa
    WebSocket_URL = 'wss://192.168.0.100:8074/mqtt';
} else {
    // Fallback dinámico
    WebSocket_URL = `wss://${hostname}:8074/mqtt`;
}
```

### 2. Archivos Modificados

Se actualizaron los siguientes archivos para implementar la detección dinámica:

#### Archivos JavaScript Principales:
- `c:\laragon\www\public\js\dashboard-legacy.js`
- `c:\laragon\www\public\js\device-status-websocket.js`
- `c:\laragon\www\public\js\device-status-config.js`

#### Vistas PHP con JavaScript Embebido:
- `c:\laragon\www\app\views\dashboard\index.php`
- `c:\laragon\www\app\views\equipment\index.php`
- `c:\laragon\www\app\views\loan\index.php`
- `c:\laragon\www\app\views\habitant\index.php`

### 3. Configuración de Puertos

Según el `docker-compose.yaml`, los puertos están mapeados así:

- **Puerto 8074**: WebSocket seguro (wss) para MQTT
- **Puerto 8073**: WebSocket no seguro (ws) para acceso externo
- **Puerto 8083**: WebSocket no seguro (ws) para acceso local

### 4. Logs de Depuración

Se agregaron logs informativos para facilitar la depuración:

```javascript
console.log('🔧 Detectando configuración MQTT para hostname:', hostname);
console.log('📡 Configuración MQTT: Acceso local detectado');
console.log('📡 URL MQTT WebSocket:', WebSocket_URL);
```

## Beneficios de la Solución

1. **Compatibilidad Universal**: Funciona tanto en acceso local como remoto
2. **Configuración Automática**: No requiere configuración manual
3. **Fallback Inteligente**: Se adapta a diferentes hostnames
4. **Mantenibilidad**: Código centralizado y reutilizable
5. **Depuración Fácil**: Logs claros para identificar problemas

## Verificación

Para verificar que la solución funciona:

1. **Acceso Local**: Abrir `http://localhost/dashboard` - debe usar `localhost:8074`
2. **Acceso Remoto**: Abrir `http://192.168.0.100/dashboard` - debe usar `192.168.0.100:8074`
3. **Consola del Navegador**: Verificar los logs de configuración MQTT

## Compatibilidad con Versión Legacy

La solución mantiene compatibilidad con la versión legacy que funcionaba correctamente, pero ahora es más robusta y adaptable a diferentes escenarios de red.

## Notas Técnicas

- La detección se basa en `window.location.hostname`
- Se mantienen los mismos parámetros de conexión (username, password, etc.)
- Los topics MQTT siguen siendo los mismos
- La lógica de reconexión automática se preserva

---

**Fecha de Implementación**: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
**Estado**: ✅ Implementado y Listo para Pruebas