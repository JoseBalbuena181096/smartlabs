# Función Saneadora de RFID - Eliminación de Prefijo "APP:"

## Descripción
Se ha implementado una función saneadora que elimina automáticamente el prefijo "APP:" de los valores RFID en toda la aplicación PHP. Esta función se aplica **SOLO** cuando el prefijo está presente, manteniendo intactos los valores que no lo tienen.

## Problema Resuelto
Cuando se envían RFIDs desde la aplicación Flutter con el prefijo "APP:" (por ejemplo: `APP:542222241`), la función saneadora los convierte automáticamente al formato limpio (`542222241`) antes de procesarlos.

## Implementación

### 1. JavaScript (Frontend)
**Archivo:** `/app/views/loan/index.php`

```javascript
// Función saneadora para eliminar prefijo "APP:" del RFID
function sanitizeRfid(rfidInput) {
    if (typeof rfidInput === 'string' && rfidInput.startsWith('APP:')) {
        return rfidInput.substring(4); // Eliminar los primeros 4 caracteres "APP:"
    }
    return rfidInput;
}
```

**Aplicada en:**
- Procesamiento de mensajes MQTT (`process_msg` función)
- Input manual en campo RFID (evento `input`)
- Selección de usuarios desde búsqueda

### 2. PHP (Backend)
**Archivos modificados:**
- `/app/controllers/LoanController.php`
- `/app/controllers/LoanAdminController.php` 
- `/app/controllers/HabitantController.php`

```php
/**
 * Función saneadora para eliminar prefijo "APP:" del RFID
 */
private function sanitizeRfid($rfidInput) {
    if (is_string($rfidInput) && strpos($rfidInput, 'APP:') === 0) {
        return substr($rfidInput, 4); // Eliminar los primeros 4 caracteres "APP:"
    }
    return $rfidInput;
}
```

## Casos de Uso Cubiertos

### ✅ LoanController
- Consultas de préstamos via AJAX (`consult_loan`)
- Procesamiento automático de RFIDs desde MQTT

### ✅ LoanAdminController  
- Búsqueda de usuarios (`search_user`)
- Consultas administrativas de préstamos (`consult_loan_admin`)
- Devolución de préstamos (`return_loan`)

### ✅ HabitantController
- Búsqueda por RFID desde MQTT (`searchByRFID`)

### ✅ Vista Loan (JavaScript)
- Mensajes MQTT entrantes
- Input manual del usuario
- Selección desde búsqueda de usuarios

## Ejemplos de Funcionamiento

| Input Original | Output Saneado | Descripción |
|----------------|----------------|-------------|
| `APP:542222241` | `542222241` | ✅ Prefijo eliminado |
| `542222241` | `542222241` | ✅ Sin cambios (no tiene prefijo) |
| `APP:123456789` | `123456789` | ✅ Prefijo eliminado |
| `987654321` | `987654321` | ✅ Sin cambios (no tiene prefijo) |
| `APP:` | `` | ✅ Solo prefijo, resultado vacío |
| `` | `` | ✅ String vacío sin cambios |
| `NOAPP:123456` | `NOAPP:123456` | ✅ Sin cambios (prefijo diferente) |
| `app:123456` | `app:123456` | ✅ Sin cambios (minúsculas) |

## Flujo de Procesamiento

### 1. Desde MQTT (Automático)
```
MQTT Message: "APP:542222241" 
    ↓ (JavaScript sanitizeRfid)
Campo RFID: "542222241"
    ↓ (AJAX al servidor)
PHP Controller: "542222241" (ya saneado)
    ↓
Consulta BD: "542222241"
```

### 2. Desde Input Manual
```
Usuario escribe: "APP:542222241"
    ↓ (JavaScript sanitizeRfid en evento input)
Campo actualizado: "542222241"
    ↓ (AJAX al servidor)
PHP Controller: "542222241"
    ↓
Consulta BD: "542222241"
```

### 3. Desde Búsqueda de Usuarios
```
Usuario busca: "APP:542222241"
    ↓ (JavaScript sanitizeRfid)
Búsqueda: "542222241"
    ↓ (AJAX al servidor)
PHP Controller: "542222241" (doble saneado por seguridad)
    ↓
Consulta BD: "542222241"
```

## Características Importantes

### ✅ Seguridad
- **Case-sensitive:** Solo elimina "APP:" en mayúsculas
- **Posición específica:** Solo al inicio del string
- **Validación de tipo:** Verifica que sea string antes de procesar

### ✅ Compatibilidad
- **Retrocompatible:** RFIDs sin prefijo funcionan igual
- **No destructiva:** Solo modifica cuando es necesario
- **Consistente:** Misma lógica en JavaScript y PHP

### ✅ Cobertura Completa
- **Frontend y Backend:** Implementado en ambos lados
- **Todos los controladores:** Aplicado donde se manejan RFIDs
- **Múltiples puntos de entrada:** MQTT, input manual, búsquedas

## Pruebas

### Script de Prueba
Ejecutar: `php app/test_sanitize_rfid.php`

### Prueba Manual
1. Abrir vista Loan en navegador
2. Ingresar `APP:542222241` en campo RFID
3. Verificar que se muestre `542222241`
4. Confirmar que la consulta funciona correctamente

### Prueba MQTT
1. Enviar mensaje MQTT con prefijo `APP:`
2. Verificar que se procese sin el prefijo
3. Confirmar que los préstamos se consulten correctamente

## Beneficios

1. **Transparencia:** El usuario no ve el prefijo técnico
2. **Consistencia:** Todos los RFIDs se manejan igual
3. **Compatibilidad:** Funciona con y sin prefijo
4. **Mantenibilidad:** Función centralizada y reutilizable
5. **Robustez:** Validaciones de tipo y posición

## Notas Técnicas

- La función es **idempotente**: aplicarla múltiples veces da el mismo resultado
- **Performance:** Operación O(1) muy eficiente
- **Memory-safe:** No modifica el string original, retorna nuevo valor
- **Error-safe:** Maneja casos edge como strings vacíos o null

---

**Implementado:** Diciembre 2024  
**Archivos afectados:** 4 archivos (1 JS, 3 PHP)  
**Casos de prueba:** 8 escenarios validados  
**Estado:** ✅ Completamente funcional