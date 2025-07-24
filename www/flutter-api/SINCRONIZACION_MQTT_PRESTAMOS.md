# 🔄 Sincronización MQTT Listener y Servicio de Préstamos

## 📋 Resumen de Cambios

Se ha implementado la sincronización entre el endpoint `/prestamos/control` y el servicio `mqttListenerService.js` para mantener un estado consistente de `countLoanCard` y `serialLoanUser`.

## 🎯 Problema Resuelto

Anteriormente, el `mqttListenerService` y el `prestamoService` mantenían estados independientes, lo que causaba inconsistencias cuando:
- Se usaba el endpoint `/prestamos/control` desde la app Flutter
- Se recibían mensajes MQTT del hardware en el tópico `loan_queryu`

## ✅ Solución Implementada

### 1. Estado Centralizado
- **Fuente de verdad**: `prestamoService` mantiene el estado principal
- **Sincronización**: `mqttListenerService` se sincroniza con `prestamoService`

### 2. Cambios en `mqttListenerService.js`

#### Constructor
```javascript
constructor() {
    this.mqttClient = null;
    this.isListening = false;
    // 🔄 SINCRONIZACIÓN: Estado local mantenido sincronizado con prestamoService
    this.serialLoanUser = null;
    this.countLoanCard = 0;
    // ...
}
```

#### Método `handleLoanUserQuery()`
- **Antes**: Manejaba su propio estado independiente
- **Ahora**: Usa `prestamoService.handleLoanUserQuery()` y sincroniza el estado local

```javascript
// 🔄 SINCRONIZACIÓN: Usar el método del prestamoService que maneja el estado centralizado
const result = await prestamoService.handleLoanUserQuery(serialNumber, rfidNumber);

if (result.success) {
    // 🔄 SINCRONIZACIÓN: Actualizar estado local con el estado del prestamoService
    const sessionState = prestamoService.getSessionState();
    this.countLoanCard = sessionState.count;
    this.serialLoanUser = sessionState.active ? [{ hab_name: sessionState.user }] : null;
}
```

#### Método `handleLoanEquipmentQuery()`
- **Antes**: Verificaba su propio estado local
- **Ahora**: Verifica el estado desde `prestamoService`

```javascript
// 🔄 SINCRONIZACIÓN: Verificar estado desde prestamoService
const sessionState = prestamoService.getSessionState();
if (!sessionState.active) {
    await this.publishMQTTCommand(serialNumber, null, 'nologin');
    return;
}
```

#### Método `getSessionState()`
- **Antes**: Devolvía su propio estado local
- **Ahora**: Devuelve el estado de `prestamoService` y actualiza el estado local

```javascript
// 🔄 SINCRONIZACIÓN: Devolver estado desde prestamoService
const sessionState = prestamoService.getSessionState();

// Actualizar estado local para mantener sincronización
this.countLoanCard = sessionState.count;
this.serialLoanUser = sessionState.active ? [{ hab_name: sessionState.user }] : null;

return sessionState;
```

## 🔄 Flujo de Sincronización

### Escenario 1: Uso del endpoint `/prestamos/control`
1. App Flutter llama a `POST /api/prestamo/control/`
2. `prestamoController.controlPrestamo()` llama a `prestamoService.procesarPrestamo()`
3. `prestamoService` actualiza su estado (`countLoanCard`, `serialLoanUser`)
4. Cuando llega un mensaje MQTT, `mqttListenerService` se sincroniza automáticamente

### Escenario 2: Mensaje MQTT del hardware
1. Hardware envía RFID al tópico `{device}/loan_queryu`
2. `mqttListenerService.handleLoanUserQuery()` recibe el mensaje
3. Llama a `prestamoService.handleLoanUserQuery()` (estado centralizado)
4. Sincroniza su estado local con el estado de `prestamoService`

## 🧪 Pruebas

Se ha creado un script de prueba: `test_sincronizacion.js`

```bash
node test_sincronizacion.js
```

## 📊 Beneficios

1. **Consistencia**: Un solo estado de verdad para toda la aplicación
2. **Mantenibilidad**: Cambios en la lógica de préstamos solo en `prestamoService`
3. **Compatibilidad**: Funciona tanto con la app Flutter como con hardware MQTT
4. **Debugging**: Más fácil rastrear el estado de las sesiones

## 🔍 Verificación

Para verificar que la sincronización funciona:

1. **Endpoint de estado**: `GET /api/prestamo/estado/`
2. **Logs**: Buscar mensajes con "🔄 SINCRONIZACIÓN" en los logs
3. **Script de prueba**: Ejecutar `test_sincronizacion.js`

## 🐛 Problema Adicional Resuelto: Cierre Automático de Sesión

### ❌ Problema Detectado
Cuando se enviaba `{ "action": 1 }` al endpoint `/prestamos/control`, la sesión se cerraba inmediatamente como si se hubiera enviado `{ "action": 0 }`.

### 🔍 Causa Raíz
En el método `procesarPrestamo()`, cuando `action` era 'on', se publicaba automáticamente el RFID del usuario al tópico MQTT `{device}/loan_queryu`. Esto activaba el `mqttListenerService.handleLoanUserQuery()` que:

1. Detectaba que ya había una sesión activa (`countLoanCard = 1`)
2. Interpretaba el RFID como una solicitud de logout
3. Cerraba automáticamente la sesión que acababa de abrirse

### ✅ Solución Implementada
Se implementó un sistema de prefijos para distinguir entre mensajes de la app vs. hardware:

**1. En `procesarPrestamo()` - Publicación con prefijo:**
```javascript
// ✅ RESTAURADO: Publicar RFID al tópico loan_queryu con prefijo para evitar bucle infinito
// Usar prefijo "APP:" para distinguir publicaciones desde la app vs. hardware
if (userRFID && this.mqttClient && this.mqttClient.connected) {
    const topic = `${deviceSerie}/loan_queryu`;
    const messageWithPrefix = `APP:${userRFID}`;
    this.mqttClient.publish(topic, messageWithPrefix);
}
```

**2. En `handleLoanUserQuery()` - Filtro de mensajes:**
```javascript
// 🚫 FILTRO: Ignorar mensajes que vienen de la app (con prefijo "APP:")
if (rfidNumber.startsWith('APP:')) {
    console.log(`ℹ️ [MQTT Listener] Mensaje ignorado - viene de la app: ${rfidNumber}`);
    return;
}
```

### 🧪 Verificación
Se creó el script `test_prestamo_fix.js` para verificar que:
1. `action: 1` mantiene la sesión activa
2. La sesión no se cierra automáticamente
3. `action: 0` cierra correctamente la sesión

```bash
node test_prestamo_fix.js
```

## 📝 Notas Importantes

- El estado local en `mqttListenerService` se mantiene para compatibilidad
- La fuente de verdad siempre es `prestamoService`
- Los métodos de sincronización están claramente marcados con "🔄 SINCRONIZACIÓN"
- Se evita la publicación automática de RFID para prevenir bucles infinitos
- No se requieren cambios en el frontend o hardware existente