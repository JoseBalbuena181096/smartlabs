# Sistema de Watchdog para Conexiones MQTT y AJAX

## Problema Identificado

Después de aproximadamente 4 horas de uso continuo, la vista de Loan experimentaba fallos en:
- Conexiones MQTT (pérdida de mensajes RFID)
- Llamadas AJAX (timeouts y errores de red)
- Funcionalidad general que requería recargar la página

## Solución Implementada: Connection Watchdog

### 🐕 ¿Qué es el Connection Watchdog?

Es un sistema de monitoreo inteligente que:
1. **Detecta automáticamente** problemas de conectividad
2. **Repara conexiones** sin intervención del usuario
3. **Previene fallos** con reconexiones programadas
4. **Monitorea continuamente** el estado de MQTT y AJAX

### 📋 Características Principales

#### Monitoreo Continuo
- ✅ Verificación cada 1 minuto del estado de conexiones
- ✅ Detección de inactividad MQTT (2 minutos sin mensajes)
- ✅ Monitoreo de fallos AJAX (máximo 3 fallos consecutivos)
- ✅ Reconexión solo cuando se detectan problemas reales

#### Reparación Automática
- 🔧 Reconexión automática de clientes MQTT
- 🔧 Reinicio de sesiones keep-alive
- 🔧 Verificación y reparación de componentes críticos
- 🔧 Manejo inteligente de errores de red

#### Integración Completa
- 🔗 Integrado con el sistema de sesiones permanentes
- 🔗 Compatible con ambos clientes MQTT (mejorado y legacy)
- 🔗 Intercepta todas las llamadas AJAX para monitoreo
- 🔗 Indicador visual en tiempo real del estado

### 📁 Archivos Modificados/Creados

#### Nuevos Archivos
```
c:\laragon\www\public\js\connection-watchdog.js
```
- Sistema principal de watchdog
- Clase `ConnectionWatchdog` con monitoreo completo
- Auto-inicialización en vista Loan
- Funciones globales de diagnóstico

#### Archivos Modificados

**1. Footer (c:\laragon\www\app\views\layout\footer.php)**
- Incluye el script del watchdog en todas las páginas

**2. Cliente MQTT Mejorado (c:\laragon\www\public\js\loan-mqtt-improved.js)**
- Notificaciones al watchdog en eventos de conexión
- Reporte de actividad en mensajes y heartbeat
- Integración con sistema de monitoreo

**3. Vista Loan (c:\laragon\www\app\views\loan\index.php)**
- Indicador visual del estado del watchdog
- Notificaciones de actividad AJAX al watchdog
- Funciones de diagnóstico y reconexión manual
- Actualización automática del estado en UI

### 🎯 Funcionalidades del Watchdog

#### Detección de Problemas
```javascript
// Verifica cada minuto:
- MQTT inactivo por más de 2 minutos
- Fallos AJAX consecutivos (máximo 3)
- Pérdida de conectividad general
```

#### Reparación Automática
```javascript
// Cuando detecta problemas:
1. Desconecta y reconecta clientes MQTT
2. Reinicia sistemas de keep-alive
3. Verifica componentes críticos
4. Resetea contadores de fallos
```

#### Reconexión Reactiva
```javascript
// Solo cuando se detectan problemas:
1. Reconexión automática de MQTT al detectar desconexión
2. Reparación de AJAX al detectar fallos consecutivos
3. Reset automático de contadores tras reconexión exitosa
4. Disponible reconexión manual si es necesaria
```

### 🖥️ Indicadores Visuales

#### En la Interfaz
- **🐕 Watchdog Activo**: Sistema funcionando correctamente
- **🐕 Watchdog Inactivo**: Sistema detenido (requiere atención)
- **🐕 Watchdog Iniciando...**: Sistema en proceso de inicialización

#### Información Detallada (Tooltip)
- Estado MQTT: ✅ Saludable / ❌ Con problemas
- Estado AJAX: ✅ Saludable / ❌ Con problemas
- Tiempo activo en minutos

### 🛠️ Funciones de Diagnóstico

#### Obtener Estado Completo
```javascript
// En consola del navegador:
getDiagnostics()

// Retorna información completa del sistema:
{
  timestamp: "2024-01-XX...",
  session: { keepAlive: {...} },
  watchdog: { isActive: true, ... },
  mqtt: { improved: {...}, legacy: "..." },
  currentRfid: "...",
  pageUrl: "..."
}
```

#### Forzar Reconexión Manual
```javascript
// En consola del navegador:
forceFullReconnect()

// Ejecuta reconexión completa de todos los sistemas
```

#### Estado del Watchdog
```javascript
// En consola del navegador:
getWatchdogStatus()

// Retorna estado detallado del watchdog
```

### 📊 Logs y Monitoreo

#### Mensajes de Consola
```
[Watchdog] 🐕 Inicializando Connection Watchdog...
[Watchdog] 🚀 Iniciando Connection Watchdog...
[Watchdog] ✅ Connection Watchdog iniciado
[Watchdog] 📊 Estado Watchdog - MQTT: 45s, AJAX: 12s, Fallos MQTT: 0, Fallos AJAX: 0
[Watchdog] 🔄 Reconexión preventiva programada (cada 4 horas)...
```

#### Detección de Problemas
```
[Watchdog] ⚠️ MQTT inactivo por 125s (Fallos: 1)
[Watchdog] ❌ AJAX Error detectado: timeout (Fallos: 2)
[Watchdog] 🔧 Intentando reparar conexión MQTT...
[Watchdog] 🔧 Intentando reparar conexiones AJAX...
```

### ⚙️ Configuración

#### Parámetros Ajustables
```javascript
const watchdog = new ConnectionWatchdog({
    checkInterval: 60000,        // Verificación cada 1 minuto
    mqttTimeout: 120000,         // MQTT inactivo después de 2 minutos
    ajaxTimeout: 30000,          // Timeout AJAX de 30 segundos
    maxFailures: 3,              // Máximo 3 fallos antes de reparar
    enablePreventiveReconnect: false, // Solo reconectar en problemas
    debug: true                  // Logs detallados
});
```

### 🔒 Seguridad y Estabilidad

#### Características de Seguridad
- ✅ No interfiere con el funcionamiento normal
- ✅ Solo actúa cuando detecta problemas reales
- ✅ Mantiene logs detallados para auditoría
- ✅ Límites en reintentos para evitar loops infinitos

#### Estabilidad
- ✅ Manejo robusto de errores
- ✅ Cleanup automático al salir de la página
- ✅ No consume recursos excesivos
- ✅ Compatible con sistemas existentes

### 🎯 Beneficios

#### Para el Usuario
- ✅ **Sin interrupciones**: El sistema funciona continuamente sin recargas
- ✅ **Transparente**: Reparaciones automáticas sin intervención
- ✅ **Confiable**: Detección proactiva de problemas
- ✅ **Informativo**: Indicadores visuales del estado

#### Para el Sistema
- ✅ **Autorecuperación**: Soluciona problemas automáticamente
- ✅ **Prevención**: Evita fallos antes de que ocurran
- ✅ **Monitoreo**: Visibilidad completa del estado
- ✅ **Diagnóstico**: Herramientas para troubleshooting

### 🚀 Resultado Final

**Problema Original**: Después de 4 horas, MQTT y AJAX fallaban requiriendo recarga manual.

**Solución Implementada**: Sistema de watchdog que:
1. **Detecta** problemas automáticamente
2. **Repara** conexiones sin intervención
3. **Previene** fallos con mantenimiento programado
4. **Monitorea** continuamente el estado del sistema

**Resultado**: La vista Loan ahora funciona indefinidamente sin requerir recargas manuales, con reparación automática de conexiones solo cuando se detectan problemas reales, evitando reconexiones innecesarias y manteniendo la estabilidad del sistema.

---

### 📞 Soporte

Para diagnósticos o problemas:
1. Abrir consola del navegador (F12)
2. Ejecutar `getDiagnostics()` para información completa
3. Ejecutar `forceFullReconnect()` para reparación manual
4. Revisar logs del watchdog para detalles específicos