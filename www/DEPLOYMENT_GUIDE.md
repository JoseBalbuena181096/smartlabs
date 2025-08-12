# Guía de Despliegue - Variables de Ambiente

## Configuración para Producción

Este proyecto ha sido actualizado para usar variables de ambiente en lugar de direcciones IP hardcodeadas. Esto permite un despliegue más flexible en diferentes entornos.

### Variables de Ambiente Principales

Las siguientes variables deben configurarse en el archivo `.env` antes del despliegue:

```bash
# Configuración del Servidor
SERVER_HOST=tu-servidor-ip-o-dominio
API_HOST=tu-api-ip-o-dominio
MQTT_HOST=tu-mqtt-broker-ip-o-dominio
```

### Archivos Actualizados

Los siguientes archivos han sido modificados para usar variables de ambiente:

#### Archivos de Configuración
- `.env` - Variables principales del proyecto
- `.env.example` - Plantilla de variables
- `flutter-api/.env` - Variables específicas de la API
- `config/app.php` - Configuración PHP con variables de ambiente

#### Frontend JavaScript
- `public/js/config.js`
- `public/js/mqtt-client.js`
- `public/js/device-status-websocket.js`
- `public/js/loan-mqtt-improved.js`
- `public/js/device-status-config.js`
- `public/js/dashboard-legacy.js`
- `public/env-config.php` - Configuración dinámica para JavaScript
- `public/js/env-config.js` - Clase de configuración JavaScript

#### Backend PHP
- `app/views/loan/index.php`
- `app/views/equipment/index.php`
- `app/views/habitant/index.php`
- `app/views/dashboard/index.php`
- `app/controllers/DashboardController.php`

#### API Node.js
- `flutter-api/src/index.js`
- `flutter-api/src/services/mqttListenerService.js`
- `flutter-api/src/services/prestamoService.js`

#### Docker
- `docker-compose.yml` - Variables de ambiente para contenedores

### Pasos para Despliegue

1. **Configurar Variables de Ambiente**
   ```bash
   cp .env.example .env
   # Editar .env con los valores de producción
   ```

2. **Configurar API**
   ```bash
   cp flutter-api/.env.example flutter-api/.env
   # Editar flutter-api/.env con los valores de producción
   ```

3. **Verificar Docker Compose**
   - Las variables del archivo `.env` se cargarán automáticamente en los contenedores
   - Verificar que las variables `SERVER_HOST`, `API_HOST` y `MQTT_HOST` estén configuradas

4. **Desplegar con Docker**
   ```bash
   docker-compose up -d
   ```

### Configuración JavaScript Dinámica

El sistema incluye un mecanismo para generar configuración JavaScript dinámicamente:

- `public/env-config.php` genera las variables de ambiente para el frontend
- `public/js/env-config.js` proporciona una clase para acceder a estas variables
- Los archivos JavaScript usan `window.EnvConfig` para obtener las URLs correctas

### Ejemplo de Configuración de Producción

```bash
# .env para producción
SERVER_HOST=smartlabs.empresa.com
API_HOST=api.smartlabs.empresa.com
MQTT_HOST=mqtt.smartlabs.empresa.com

# O usando IP específica
SERVER_HOST=10.0.1.100
API_HOST=10.0.1.100
MQTT_HOST=10.0.1.100
```

### Fallbacks

Todos los archivos mantienen fallbacks a `192.168.0.100` si las variables de ambiente no están configuradas, asegurando compatibilidad con el entorno de desarrollo actual.

### Verificación

Para verificar que las variables se están cargando correctamente:

1. Revisar los logs de los contenedores
2. Acceder a `http://tu-servidor/env-config.php` para ver la configuración JavaScript
3. Verificar en la consola del navegador que `window.ENV_CONFIG` contiene los valores correctos