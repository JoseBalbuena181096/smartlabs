# SMARTLABS IoT Server - Estructura Reorganizada

## 📁 Estructura del Proyecto

```
node/
├── src/                          # Código fuente principal
│   ├── config/                   # Configuraciones centralizadas
│   │   ├── database.js          # Configuración de base de datos
│   │   ├── mqtt.js              # Configuración MQTT
│   │   └── device-status.js     # Configuración de monitoreo
│   ├── services/                # Servicios del sistema
│   │   ├── iot/                 # Servicios IoT
│   │   │   └── IoTMQTTServer.js # Servidor principal IoT MQTT
│   │   └── device-status/       # Servicios de monitoreo
│   │       ├── server.js        # Servidor WebSocket
│   │       └── websocket.js     # Lógica WebSocket
│   ├── utils/                   # Utilidades compartidas
│   ├── client/                  # Archivos del lado cliente
│   │   ├── device-status-config.js   # Configuración cliente
│   │   └── device-status-monitor.js  # Monitor cliente
│   └── index.js                 # Punto de entrada principal
├── scripts/                     # Scripts de inicio
│   ├── start-all.js            # Inicia todos los servicios
│   ├── start-iot-server.js     # Solo servidor IoT
│   └── start-device-server.js  # Solo servidor de dispositivos
├── config/                      # Configuraciones de despliegue
│   └── ecosystem.config.js     # Configuración PM2
├── logs/                        # Archivos de log
├── docs/                        # Documentación
│   └── README.md               # Este archivo
├── package.json                # Dependencias y scripts
└── package-lock.json          # Lock de dependencias
```

## 🚀 Comandos Disponibles

### Desarrollo
```bash
# Iniciar todos los servicios
npm start

# Iniciar solo el servidor IoT MQTT
npm run start:iot

# Iniciar solo el servidor de estado de dispositivos
npm run start:device-status

# Modo desarrollo (con nodemon)
npm run dev
npm run dev:iot
npm run dev:device-status
```

### Producción con PM2
```bash
# Desde la carpeta config
cd config
pm2 start ecosystem.config.js

# Comandos PM2
pm2 status
pm2 logs
pm2 restart device-status-server
pm2 stop device-status-server
```

## 📋 Características de la Nueva Estructura

### ✅ Beneficios

1. **Organización Modular**: Código separado por responsabilidades
2. **Configuración Centralizada**: Todas las configuraciones en un lugar
3. **Escalabilidad**: Fácil agregar nuevos servicios
4. **Mantenibilidad**: Código más fácil de mantener y debuggear
5. **Reutilización**: Configuraciones y utilidades compartidas
6. **Despliegue Flexible**: Scripts específicos para diferentes servicios

### 🔧 Configuraciones Centralizadas

- **database.js**: Configuración de MySQL con fallback automático
- **mqtt.js**: Configuración del broker MQTT y tópicos
- **device-status.js**: Configuración del sistema de monitoreo

### 📡 Servicios

1. **IoT MQTT Server**: Maneja control de acceso, préstamos y sensores
2. **Device Status Server**: WebSocket para monitoreo en tiempo real
3. **Client Scripts**: Archivos JavaScript para el frontend

### 🔄 Migración desde la Estructura Anterior

Los archivos fueron reorganizados de la siguiente manera:

- `index.js` → `src/services/iot/IoTMQTTServer.js`
- `device-status-server.js` → `src/services/device-status/server.js`
- `device-status-config.js` → `src/client/device-status-config.js`
- `device-status-monitor.js` → `src/client/device-status-monitor.js`
- `device-status-websocket.js` → `src/services/device-status/websocket.js`
- `ecosystem.config.js` → `config/ecosystem.config.js`

### 🛠️ Compatibilidad

- ✅ **Funcionalidad**: Toda la funcionalidad existente se mantiene
- ✅ **Base de datos**: Mismas configuraciones de conexión
- ✅ **MQTT**: Mismos tópicos y configuraciones
- ✅ **WebSocket**: Mismo puerto y protocolo
- ✅ **PM2**: Configuración actualizada pero compatible

## 🔍 Troubleshooting

### Problemas Comunes

1. **Error de módulos no encontrados**:
   ```bash
   npm install
   ```

2. **Error de permisos en logs**:
   ```bash
   mkdir logs
   chmod 755 logs
   ```

3. **Error de conexión a base de datos**:
   - Verificar configuración en `src/config/database.js`
   - Comprobar que el servidor MySQL esté activo

4. **Error de conexión MQTT**:
   - Verificar configuración en `src/config/mqtt.js`
   - Comprobar que el broker EMQX esté activo

### Logs

Los logs se guardan en la carpeta `logs/`:
- `combined.log`: Todos los logs
- `out.log`: Salida estándar
- `error.log`: Solo errores

---

**Desarrollado para SMARTLABS - Sistema IoT Reorganizado**