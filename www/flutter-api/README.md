# SMARTLABS Flutter API

API REST para la aplicación Flutter de control de equipos SMARTLABS. Esta API reemplaza la funcionalidad del dispositivo físico `main_maquinasV2.cpp` permitiendo a la aplicación Flutter autenticar usuarios y controlar dispositivos IoT.

## 🚀 Características

- **Autenticación de usuarios** por matrícula y RFID
- **Control de dispositivos** mediante códigos QR
- **Listener MQTT automático** para consultas RFID del hardware
- **Historial de acceso** y uso de equipos
- **Integración MQTT** para comunicación con dispositivos
- **Base de datos MySQL** con fallback automático
- **Rate limiting** y seguridad
- **Documentación automática** de endpoints

## 📋 Requisitos

- Node.js 16+
- MySQL 8.0+
- Broker MQTT (EMQX)
- Docker (opcional)

## 🛠️ Instalación

1. **Clonar e instalar dependencias:**
```bash
cd c:\laragon\www\flutter-api
npm install
```

2. **Configurar variables de entorno:**
```bash
cp .env.example .env
# Editar .env con tus configuraciones
```

3. **Iniciar la base de datos y MQTT:**
```bash
# Desde el directorio del contenedor Docker
cd c:\laragon\www\DOCKER_SMARTLABS-main
docker-compose up -d
```

4. **Iniciar la API:**
```bash
npm start
```

## 🌐 Endpoints

### Health Check
- `GET /health` - Estado de la API
- `GET /api` - Documentación de endpoints

### Usuarios
- `GET /api/users/registration/:registration` - Obtener usuario por matrícula
- `GET /api/users/rfid/:rfid` - Obtener usuario por RFID
- `GET /api/users/registration/:registration/history` - Historial de acceso
- `GET /api/users/validate/:registration` - Validar existencia de usuario

### Dispositivos
- `POST /api/devices/control` - Controlar dispositivo
- `GET /api/devices` - Listar todos los dispositivos
- `GET /api/devices/:device_serie` - Información del dispositivo
- `GET /api/devices/:device_serie/status` - Estado actual
- `GET /api/devices/:device_serie/history` - Historial de uso

### Préstamos
- `POST /api/prestamo/control/` - Controlar préstamo de dispositivo manualmente
- `MQTT +/loan_queryu` - Procesa consultas RFID automáticas del hardware

### Endpoints Internos
- `POST /api/internal/loan-session` - Notificación interna de sesiones (sincronización con backend Node.js)
- `GET /api/internal/status` - Estado del sistema interno

### MQTT Monitoring
- `GET /api/mqtt/status` - Estado del servicio MQTT
- `POST /api/mqtt/restart` - Reiniciar listener MQTT

## 📱 Uso con Flutter

### 1. Autenticación de Usuario
```dart
// Obtener usuario por matrícula
final response = await http.get(
  Uri.parse('http://192.168.0.100:3001/api/users/registration/12345'),
  headers: {'x-api-key': 'smartlabs_api_key_flutter_2024'}
);
```

### 2. Control de Dispositivo
```dart
// Controlar dispositivo escaneando QR
final response = await http.post(
  Uri.parse('http://192.168.0.100:3001/api/devices/control'),
  headers: {
    'Content-Type': 'application/json',
    'x-api-key': 'smartlabs_api_key_flutter_2024'
  },
  body: jsonEncode({
    'registration': '12345',
    'device_serie': 'DEV001',
    'action': 1  // 1 = encender, 0 = apagar
  })
);
```

### 3. Escaneo de QR
```dart
// El QR debe contener el device_serie
// Ejemplo: "DEV001"
String qrResult = await scanner.scan();
String deviceSerie = qrResult; // device_serie del QR
```

## 🔧 Configuración

### Variables de Entorno (.env)
```env
# Base de datos principal
DB_HOST=localhost
DB_USER=admin_iotcurso
DB_PASSWORD=tu_password
DB_NAME=emqx
DB_PORT=3306

# Base de datos fallback
DB_FALLBACK_HOST=127.0.0.1
DB_FALLBACK_USER=root
DB_FALLBACK_PASSWORD=
DB_FALLBACK_NAME=emqx
DB_FALLBACK_PORT=3306

# MQTT Broker
MQTT_HOST=192.168.0.100
MQTT_PORT=1883
MQTT_USERNAME=jose
MQTT_PASSWORD=public

# Servidor
PORT=3000
NODE_ENV=development

# Seguridad
JWT_SECRET=tu_jwt_secret_muy_seguro
API_KEY=tu_api_key_para_flutter
```

## 🏗️ Arquitectura

```
flutter-api/
├── src/
│   ├── config/
│   │   ├── database.js          # Configuración MySQL
│   │   └── mqtt.js              # Configuración MQTT
│   ├── controllers/
│   │   ├── userController.js    # Controladores de usuarios
│   │   └── deviceController.js  # Controladores de dispositivos
│   ├── services/
│   │   ├── userService.js       # Lógica de negocio usuarios
│   │   ├── deviceService.js     # Lógica de negocio dispositivos
│   │   ├── prestamoService.js   # Lógica de préstamos
│   │   └── mqttListenerService.js # Listener MQTT automático
│   ├── routes/
│   │   ├── userRoutes.js        # Rutas de usuarios
│   │   ├── deviceRoutes.js      # Rutas de dispositivos
│   │   ├── prestamoRoutes.js    # Rutas de préstamos
│   │   └── mqttRoutes.js        # Rutas de monitoreo MQTT
│   ├── middleware/
│   │   ├── auth.js              # Autenticación
│   │   └── errorHandler.js      # Manejo de errores
│   └── index.js                 # Aplicación principal
├── package.json
├── .env
└── README.md
```

## 🔄 Flujo de Funcionamiento

### Flujo Manual (App Flutter)
1. **Usuario abre la app Flutter**
2. **Ingresa su matrícula** → API valida en base de datos
3. **Escanea QR del equipo** → Obtiene `device_serie`
4. **App envía request de control** → API verifica usuario y dispositivo
5. **API publica comando MQTT** → Dispositivo se enciende/apaga
6. **API registra en base de datos** → Historial actualizado

### Flujo Automático (Hardware RFID)
1. **Hardware lee tarjeta RFID**
2. **Hardware publica en MQTT** → `{serial}/loan_queryu`
3. **Listener MQTT procesa** → Valida usuario automáticamente
4. **API registra en base de datos** → Historial actualizado
5. **API responde via MQTT** → `{serial}/user_name` y `{serial}/command`
6. **Hardware recibe respuesta** → Se enciende/apaga automáticamente

> 📖 **Documentación completa**: Ver [ENDPOINT_PRESTAMOS_COMPLETO.md](./ENDPOINT_PRESTAMOS_COMPLETO.md)

## 🔒 Seguridad

- **Rate limiting**: 100 requests por 15 minutos
- **API Key**: Autenticación opcional (desarrollo) / requerida (producción)
- **Helmet**: Headers de seguridad
- **CORS**: Configurado para Flutter
- **Validación**: Joi para validar entrada
- **Sanitización**: Logs sin información sensible

## 🔧 Solución de Problemas

### Dispositivo Físico y Sincronización Hardware-API

Se ha implementado una solución completa para sincronizar el estado de sesión entre el hardware físico (`main_usuariosLV2.cpp`), el backend de Node.js y la API de Flutter.

#### Problema Resuelto

El dispositivo físico funcionaba correctamente cuando se acercaba la credencial RFID, pero la API de Flutter tenía contadores de sesión separados, causando desincronización. Ahora la API simula exactamente el comportamiento del hardware físico.

#### Solución de Sincronización Hardware-API

**Problema resuelto**: Desincronización entre el dispositivo físico y la API de Flutter debido a contadores de sesión separados.

**Solución**: La API de Flutter ahora simula exactamente el comportamiento del hardware físico, publicando directamente en MQTT como lo hace el ESP32.

1. **Verificar configuración:**
   ```bash
   # Verificar que el backend de Node.js esté ejecutándose
   cd c:\laragon\www\node
   npm start
   
   # Verificar que la API de Flutter esté ejecutándose
   cd c:\laragon\www\flutter-api
   npm start
   ```

2. **Probar simulación del hardware físico:**
   ```bash
   # Endpoint que simula exactamente el comportamiento del ESP32
   curl -X POST http://localhost:3001/api/prestamo/simular/ \
     -H "Content-Type: application/json" \
     -H "x-api-key: smartlabs_api_key_flutter_2024" \
     -d '{"registration":"L03533767","device_serie":"SMART10003"}'
   ```

   **Comportamiento**:
   1. Busca al usuario por matrícula
   2. Obtiene su RFID de la base de datos
   3. Publica el RFID en `{device_serie}/loan_queryu` (exactamente como el hardware)
   4. El backend de Node.js procesa la consulta automáticamente

3. **Verificar logs esperados:**
   - ✅ "Publicando en MQTT como dispositivo físico: SMART10003/loan_queryu -> [RFID]"
   - ✅ "RFID publicado exitosamente"
   - ✅ Respuesta del backend con comando correspondiente

4. **Probar hardware físico:**
   - Conectar el dispositivo físico
   - Pasar una tarjeta RFID
   - Verificar que los comandos (`found`, `unload`, `nofound`) funcionen correctamente
   - Confirmar que el comportamiento es idéntico al endpoint simulado

5. **Ver documentación completa:**
   - `SOLUCION_SINCRONIZACION_HARDWARE.md` - Solución actual (Hardware-API)
   - `SOLUCION_SINCRONIZACION_MEJORADA.md` - Solución anterior (API-Backend)
   - `PROBLEMA_DISPOSITIVO_FISICO.md` - Análisis del problema original

## 🐛 Debugging
### Logs

```bash
# Iniciar con logs detallados
NODE_ENV=development npm start
```

### Health Check
```bash
curl http://192.168.0.100:3001/health
```

### Test de Endpoints
```bash
# Obtener usuario
curl -H "x-api-key: smartlabs_api_key_flutter_2024" \
     http://192.168.0.100:3001/api/users/registration/12345

# Controlar dispositivo
curl -X POST \
     -H "Content-Type: application/json" \
     -H "x-api-key: smartlabs_api_key_flutter_2024" \
     -d '{"registration":"12345","device_serie":"DEV001","action":1}' \
     http://192.168.0.100:3001/api/devices/control

# Controlar préstamo de dispositivo (sustituye main_usuariosLV2.cpp)
curl -X POST \
     -H "Content-Type: application/json" \
     -H "x-api-key: smartlabs_api_key_flutter_2024" \
     -d '{"registration":"L03533767","device_serie":"SMART10003","action":1}' \
     http://192.168.0.100:3001/api/prestamo/control/
```

## 📊 Monitoreo

- **Health endpoint**: `/health`
- **Logs estructurados**: Timestamp, método, URL, IP
- **Error tracking**: Errores categorizados y loggeados
- **MQTT status**: Conexión y mensajes
- **Database status**: Conexión y queries

## 🚀 Producción

1. **Configurar variables de producción**
2. **Usar PM2 para gestión de procesos**
3. **Configurar proxy reverso (nginx)**
4. **Habilitar HTTPS**
5. **Configurar monitoreo**

```bash
# Instalar PM2
npm install -g pm2

# Iniciar en producción
NODE_ENV=production pm2 start src/index.js --name "smartlabs-api"
```

## 🤝 Contribución

1. Fork del proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto es parte del sistema SMARTLABS para control de equipos IoT.

---

**Desarrollado para SMARTLABS** 🔬⚡