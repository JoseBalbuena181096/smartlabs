# Changelog - SMARTLABS Flutter API

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-01-25

### 🔧 Fixed

#### TypeError: Bind parameters must not contain undefined
- **Problema:** Error crítico en consultas SQL cuando usuarios no tenían RFID asignado
- **Impacto:** Fallos en endpoint `/api/prestamo/control/` causando interrupciones en el sistema de préstamos
- **Solución:**
  - Actualizado `getUserByRegistration()` en `prestamoService.js` para incluir datos RFID mediante LEFT JOIN con `cards_habs`
  - Agregada validación robusta de parámetros RFID antes de consultas SQL
  - Implementado manejo de casos donde usuarios no tienen RFID asignado
  - Mejorado logging para debugging y monitoreo

**Archivos modificados:**
- `src/services/prestamoService.js`
  - Método `getUserByRegistration()`: Mejorado query SQL
  - Método `procesarPrestamo()`: Validación de RFID
  - Método `handleLoanEquipmentQuery()`: Logging detallado

**Validaciones agregadas:**
```javascript
if (!userRFID) {
    return {
        success: false,
        message: 'Usuario no tiene RFID asignado',
        action: 'no_rfid'
    };
}
```

**Pruebas realizadas:**
- ✅ Endpoint `/api/prestamo/control/` con action 1 (login)
- ✅ Endpoint `/api/prestamo/control/` con action 0 (logout)
- ✅ Manejo de usuarios sin RFID asignado
- ✅ Logging y debugging mejorados

### 📚 Documentation
- Actualizado README.md con sección "Errores Comunes Solucionados"
- Documentada la corrección en ARCHITECTURE.md del proyecto principal
- Agregada información en DEPLOYMENT.md sobre verificación post-corrección

## [1.0.0] - 2024-12-01

### ✨ Added
- Implementación inicial de la API Flutter para SMARTLABS
- Servicios de usuario, dispositivos y préstamos
- Integración MQTT con hardware IoT
- Sistema de autenticación y autorización
- Endpoints RESTful para aplicación móvil
- Configuración dual de base de datos (principal/fallback)
- Rate limiting y seguridad CORS
- Health checks y monitoreo
- Logging estructurado
- Documentación completa

### 🔒 Security
- Implementación de Helmet.js para headers de seguridad
- Rate limiting por IP
- Validación de entrada en todos los endpoints
- Configuración CORS restrictiva

### 🚀 Performance
- Conexión optimizada a base de datos
- Pooling de conexiones MQTT
- Caching de consultas frecuentes
- Compresión de respuestas

---

## Tipos de Cambios

- `Added` para nuevas funcionalidades
- `Changed` para cambios en funcionalidades existentes
- `Deprecated` para funcionalidades que serán removidas
- `Removed` para funcionalidades removidas
- `Fixed` para corrección de bugs
- `Security` para vulnerabilidades de seguridad

## Versionado

Este proyecto usa [Semantic Versioning](https://semver.org/):
- **MAJOR**: Cambios incompatibles en la API
- **MINOR**: Nuevas funcionalidades compatibles hacia atrás
- **PATCH**: Correcciones de bugs compatibles hacia atrás

---

**Mantenido por**: Equipo SMARTLABS  
**Última actualización**: 25 de Enero, 2025