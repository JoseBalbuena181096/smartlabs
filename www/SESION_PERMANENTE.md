# Configuración de Sesión Permanente - SMARTLABS

## 🔒 Descripción

Este sistema ha sido configurado para mantener las sesiones de usuario **PERMANENTEMENTE ACTIVAS**, eliminando la necesidad de relogin o recargas de página debido a expiración de sesión.

## ⚙️ Configuraciones Implementadas

### 1. Configuración PHP de Sesión (`config/session.php`)
- **Garbage Collection**: Deshabilitado (`gc_probability = 0`)
- **Tiempo de vida máximo**: 2,147,483,647 segundos (máximo valor de 32-bit)
- **Cookies de sesión**: Sin expiración (`cookie_lifetime = 0`)
- **Nombre de sesión**: `SMARTLABS_PERMANENT_SESSION`

### 2. Configuración de Aplicación (`config/app.php`)
- **session_timeout**: 0 (sin límite)
- **gc_maxlifetime**: 0 (sin garbage collection automático)
- **cookie_lifetime**: 0 (cookie permanente)
- **keepalive interval**: 60 segundos (1 minuto)

### 3. Sistema de Keep-Alive JavaScript
- **Frecuencia**: Cada 1 minuto
- **Timeout**: 15 segundos
- **Reintentos**: 5 intentos máximo
- **Indicador visual**: Muestra "Sesión Permanente Activa"

### 4. Mejoras en AJAX
- **Timeouts extendidos**: 10-15 segundos
- **Reintentos automáticos**: 3-5 intentos
- **Manejo de errores**: Detección de errores de autenticación
- **Cache deshabilitado**: `cache: false` en todas las llamadas

## 🛡️ Características de Seguridad

### Regeneración de ID de Sesión
- **Frecuencia**: Cada 24 horas (en lugar de 1 hora)
- **Propósito**: Mantener seguridad sin interrumpir la sesión

### Validación de Sesión
- **Keep-alive endpoint**: `/Auth/keepalive`
- **Validación continua**: Cada minuto
- **Detección de errores**: Manejo automático de fallos de conexión

## 📊 Monitoreo y Logs

### Consola del Navegador
```
🚀 Inicializando sistema de sesión permanente...
🔒 SMARTLABS - Sesión configurada para NUNCA expirar
⏰ Keep-alive cada 1 minuto para mantener conexión activa
✅ Sistema de sesión permanente iniciado
🛡️ La sesión permanecerá activa indefinidamente
```

### Indicador Visual
- **Color azul**: Sesión permanente activa
- **Posición**: Esquina superior derecha
- **Mensaje**: "Sesión Permanente Activa"

## 🔧 Funciones Mejoradas

### Vista de Préstamos (`loan/index.php`)
- `validateRfidWithApi()`: Timeout 10s, 3 reintentos
- `checkSessionState()`: Timeout 10s, 3 reintentos
- `consultarPrestamosUsuario()`: Timeout 15s, 3 reintentos

### Manejo de Errores
- **401/403**: Redirección automática al login
- **Errores de red**: Reintentos con backoff exponencial
- **Timeouts**: Reintentos automáticos

## ✅ Beneficios

1. **Sin interrupciones**: La sesión nunca expira automáticamente
2. **Operación continua**: Más de 10 horas sin problemas
3. **Recuperación automática**: Reintentos en caso de fallos temporales
4. **Monitoreo visual**: Indicador de estado de sesión
5. **Seguridad mantenida**: Regeneración periódica de ID de sesión

## ⚠️ Consideraciones

- La sesión solo se cierra al hacer logout manual
- El navegador debe permanecer abierto para mantener la cookie
- El sistema requiere conectividad de red para el keep-alive
- Los logs de PHP pueden mostrar advertencias sobre garbage collection deshabilitado

## 🔄 Reversión

Para volver a sesiones con expiración:
1. Cambiar valores en `config/app.php` a números positivos
2. Habilitar garbage collection en `config/session.php`
3. Ajustar intervalo de keep-alive en JavaScript

---

**Implementado**: $(date)
**Estado**: ✅ Activo
**Versión**: 1.0