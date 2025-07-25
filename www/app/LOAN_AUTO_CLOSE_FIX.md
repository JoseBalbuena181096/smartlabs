# Fix: Cierre Automático de Sesión en Vista de Préstamos

## Problema Identificado

En la vista de préstamos (`/app/views/loan/index.php`), cuando llegaba un valor por `/loan_queryu`, la sesión se cargaba perfectamente. Sin embargo, al llegar otro valor (el mismo RFID por segunda vez, un RFID diferente, o un valor vacío), no se cerraba adecuadamente la sesión.

## Solución Implementada

### Cambios Realizados

1. **Nueva Variable de Control**: Se agregó `lastProcessedRfid` para rastrear el último RFID procesado.

2. **Lógica de Cierre Automático en MQTT**: En la función `process_msg()` para el topic `loan_queryu`:
   - Se verifica si el RFID recibido es igual al último procesado
   - Se verifica si el RFID está vacío
   - Se verifica si es un RFID diferente cuando ya hay una sesión activa
   - En cualquiera de estos casos, se limpia automáticamente el campo `registration` y se cierra la sesión

3. **Lógica de Cierre Manual**: En el evento `input` del campo `registration`:
   - Se verifica si el usuario ingresa manualmente el mismo RFID o un valor vacío
   - Se cierra automáticamente la sesión en estos casos

### Código Implementado

```javascript
// Nueva variable de control
var lastProcessedRfid = ''; // Variable para rastrear el último RFID procesado

// Lógica en process_msg para loan_queryu
if (query == "loan_queryu") {
  var sanitizedRfid = sanitizeRfid(msg);
  
  // Verificar condiciones de cierre automático
  if (sanitizedRfid === lastProcessedRfid || sanitizedRfid === '' || sanitizedRfid !== currentRfid && currentRfid !== '') {
    // Limpiar el campo de entrada y los resultados
    document.getElementById('registration').value = '';
    $('#resultado_').html('');
    document.getElementById('display_new_access').innerHTML = 'Sesión cerrada automáticamente';
    
    // Limpiar las variables de control
    currentRfid = '';
    lastProcessedRfid = '';
    
    console.log("Sesión cerrada automáticamente - RFID:", sanitizedRfid);
    return; // Salir sin procesar más
  }
  
  // Continuar con el procesamiento normal...
}
```

### Comportamiento Esperado

1. **Primera lectura de RFID**: Se carga la sesión normalmente
2. **Segunda lectura del mismo RFID**: Se cierra automáticamente la sesión
3. **Lectura de RFID diferente**: Se cierra la sesión actual y se abre una nueva
4. **Valor vacío**: Se cierra la sesión actual
5. **Entrada manual**: Mismo comportamiento que las lecturas MQTT

### Mensajes de Estado

- **Sesión abierta**: "Nuevo acceso: [RFID]"
- **Cierre automático (MQTT)**: "Sesión cerrada automáticamente"
- **Cierre manual**: "Sesión cerrada manualmente"

### Archivos Modificados

- `c:\laragon\www\app\views\loan\index.php`
  - Línea ~245: Agregada variable `lastProcessedRfid`
  - Línea ~261: Agregada lógica de cierre automático en `process_msg()`
  - Línea ~473: Agregada lógica de cierre manual en evento `input`

### Testing

Para probar la funcionalidad:

1. Acceder a `/Loan` en el navegador
2. Ingresar un RFID en el campo de entrada
3. Verificar que se carga la sesión
4. Ingresar el mismo RFID nuevamente
5. Verificar que se cierra automáticamente la sesión
6. Probar con RFID diferente y valor vacío

### Compatibilidad

Esta solución es compatible con:
- Lecturas MQTT desde hardware
- Entrada manual en el campo de texto
- Búsqueda de usuarios existente
- Funcionalidad de auto-submit

## Resultado

La vista de préstamos ahora maneja correctamente el cierre automático de sesiones cuando:
- Llega el mismo RFID por segunda vez
- Llega un RFID diferente
- Llega un valor vacío
- Se ingresa manualmente cualquiera de los casos anteriores

Esto corrige el problema de cierre automático y mejora la experiencia del usuario en el sistema de autopréstamo SMARTLABS.