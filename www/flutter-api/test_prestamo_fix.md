# Prueba de Corrección de Mensajes MQTT Duplicados

## Problema Original
- Mensajes "found" duplicados cuando action: 1
- Mensajes "unload" duplicados cuando action: 0  
- Mensajes "nofound" inesperados

## Cambios Realizados

### 1. Eliminación de Comandos MQTT Duplicados en prestamoService.js
- Removidas llamadas a `enviarComandosMQTT` en `procesarPrestamo()` para action 1 y 0
- Removidas llamadas duplicadas a `enviarComandosMQTT` con 'nofound' en múltiples funciones
- El servidor IoT de Node.js ahora maneja todos los comandos MQTT

### 2. Sincronización en IoTMQTTServer.js
- Corregido `countLoanCard += 1` a `countLoanCard = 1` para evitar incrementos incorrectos
- Agregados comentarios para clarificar que solo se envía cada comando una vez

## Flujo Corregido

### Action: 1 (Login)
1. API Flutter recibe request con action: 1
2. API obtiene RFID del usuario desde la base de datos
3. API publica RFID en tópico `{device_serie}/loan_queryu`
4. Servidor IoT de Node.js procesa el RFID y envía:
   - `{device_serie}/user_name`: Nombre del usuario
   - `{device_serie}/command`: "found" (UNA SOLA VEZ)
5. Estado se actualiza: countLoanCard = 1

### Action: 0 (Logout)
1. API Flutter recibe request con action: 0
2. API obtiene RFID del usuario desde la base de datos
3. API publica RFID en tópico `{device_serie}/loan_queryu`
4. Servidor IoT de Node.js detecta que countLoanCard = 1 y envía:
   - `{device_serie}/command`: "unload" (UNA SOLA VEZ)
5. Estado se actualiza: countLoanCard = 0, serialLoanUser = null

## Archivos Modificados
- `c:\laragon\www\flutter-api\src\services\prestamoService.js`
- `c:\laragon\www\node\src\services\iot\IoTMQTTServer.js`

## Pruebas Recomendadas
1. Enviar POST a `/api/prestamo/control` con action: 1
2. Verificar que solo se recibe UN mensaje "found"
3. Enviar POST a `/api/prestamo/control` con action: 0
4. Verificar que solo se recibe UN mensaje "unload"
5. Verificar que NO se reciben mensajes "nofound" inesperados