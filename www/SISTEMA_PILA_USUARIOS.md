# Sistema de Pila de Usuarios - Dashboard SMARTLABS

## Descripción

Se ha implementado un sistema de pila (stack) para mantener consistencia en la información del usuario mostrada en el Dashboard. Este sistema evita que aparezca "Sin usuario" cuando llegan datos incompletos o inválidos.

## Problema Resuelto

**Problema anterior:**
1. Al inicio cargaba bien el nombre y datos del usuario (encendido/apagado)
2. Luego aparecía "Sin usuario" cuando llegaban datos sin información de usuario
3. Se perdía la información del usuario anterior

**Solución implementada:**
- Pila de estados de usuario válidos
- Solo se actualiza cuando llegan datos completos y válidos
- Se mantiene el último estado válido cuando no hay datos nuevos

## Funcionalidades

### 1. Validación de Datos de Usuario

```javascript
function isValidUserData(userData) {
    // Verifica que los datos sean válidos:
    // - Nombre no vacío y diferente de "Sin usuario"
    // - Al menos registro o email presente
}
```

### 2. Gestión de la Pila

```javascript
// Agregar estado válido a la pila
pushUserState(deviceId, userData);

// Obtener último estado válido
getLastValidUserState(deviceId);

// Limpiar la pila
clearUserStateStack();
```

### 3. Actualización Inteligente de UI

La función `updateDeviceStatusUI` ahora:
1. Intenta agregar datos nuevos a la pila si son válidos
2. Si no son válidos, usa el último estado válido de la pila
3. Combina el estado del dispositivo actual con datos de usuario de la pila

## Configuración

- **Tamaño máximo de pila:** 5 estados válidos
- **Almacenamiento:** Variable global `window.userStateStack`
- **Persistencia:** Solo durante la sesión del navegador

## Funciones de Prueba

### En la Consola del Navegador:

```javascript
// Probar todo el sistema de pila
testUserStack();

// Limpiar la pila
clearStack();

// Ver estado actual de la pila
console.log(window.userStateStack);
```

### Ejemplo de Uso:

```javascript
// Datos válidos - se agregan a la pila
updateDeviceStatusUI({
    device: 'SMART10000',
    state: 'on',
    user: 'Jose Angel Balbuena Palma',
    user_registration: '123456789',
    user_email: 'jose@example.com'
});

// Datos inválidos - usa la pila
updateDeviceStatusUI({
    device: 'SMART10000',
    state: 'off',
    user: '',
    user_name: 'Sin usuario',
    user_registration: '',
    user_email: ''
});
// Resultado: Muestra "Apagado - Jose Angel Balbuena Palma" (datos de la pila)
```

## Estructura de Datos

### Estado de Usuario en la Pila:
```javascript
{
    device: 'SMART10000',
    user: 'Jose Angel Balbuena Palma',
    user_name: 'Jose Angel Balbuena Palma',
    user_registration: '123456789',
    user_email: 'jose@example.com',
    timestamp: '2024-01-01T12:00:00.000Z'
}
```

## Logs de Depuración

El sistema incluye logs detallados:
- 📝 Indica operaciones de pila
- 🟢 Estado encendido
- 🔴 Estado apagado
- 🟡 Estado desconocido
- 🧪 Funciones de prueba

## Beneficios

1. **Consistencia:** Información del usuario siempre visible
2. **Robustez:** Manejo de datos incompletos o inválidos
3. **Experiencia:** No más "Sin usuario" intermitente
4. **Flexibilidad:** Soporte para múltiples dispositivos
5. **Depuración:** Logs detallados para troubleshooting

## Archivos Modificados

- `www/public/js/dashboard-legacy.js` - Implementación principal
- `www/app/views/dashboard/index.php` - Interfaz de usuario
- `www/app/controllers/DashboardController.php` - Lógica del backend

## Notas Técnicas

- La pila se mantiene durante la sesión del navegador
- Los datos se validan antes de agregarse a la pila
- Se prioriza el estado específico del dispositivo
- Fallback al último estado válido general si no hay específico
- Tamaño máximo para evitar uso excesivo de memoria 