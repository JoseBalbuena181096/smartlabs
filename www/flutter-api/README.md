# SmartLabs Flutter API

## 🚀 Descripción

API REST desarrollada en Node.js para la aplicación móvil Flutter de SmartLabs. Proporciona endpoints para el control de dispositivos IoT, gestión de usuarios y préstamos de equipos de laboratorio.

## ⚡ Inicio Rápido

### Instalación
```bash
# Instalar dependencias
npm install

# Configurar variables de entorno
cp .env.example .env
# Editar .env con tus configuraciones

# Ejecutar en desarrollo
npm run dev

# Ejecutar en producción
npm start
```

### Docker
```bash
# Construir imagen
docker build -t smartlabs-flutter-api .

# Ejecutar contenedor
docker run -p 3000:3000 --env-file .env smartlabs-flutter-api
```

## 🔗 Endpoints Principales

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/health` | Health check |
| GET | `/api` | Información de la API |
| POST | `/api/devices/control` | Controlar dispositivos |
| GET | `/api/users/registration/:id` | Obtener usuario |
| GET | `/api/users/:registration/devices` | Dispositivos del usuario |
| POST | `/api/prestamo/control/` | Gestión de préstamos |
| GET | `/api/mqtt/status` | Estado del broker MQTT |

## 📱 Ejemplo de Uso

### Controlar Dispositivo
```bash
curl -X POST http://localhost:3000/api/devices/control \
  -H "Content-Type: application/json" \
  -d '{
    "registration": "A12345678",
    "device_serie": "DEV001",
    "action": "on"
  }'
```

### Respuesta
```json
{
  "success": true,
  "message": "Dispositivo controlado exitosamente",
  "data": {
    "device_serie": "DEV001",
    "action": "on",
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

## 🏗️ Arquitectura

```
src/
├── config/          # Configuración (DB, MQTT)
├── controllers/     # Lógica de endpoints
├── services/        # Lógica de negocio
├── routes/          # Definición de rutas
├── middleware/      # Validación, autenticación
└── utils/           # Utilidades y helpers
```

## ⚙️ Configuración

### Variables de Entorno
```bash
# Servidor
PORT=3000
NODE_ENV=development

# Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_USER=emqxuser
DB_PASSWORD=emqxpass
DB_NAME=emqx

# MQTT
MQTT_HOST=localhost
MQTT_PORT=1883
MQTT_USERNAME=smartlabs
MQTT_PASSWORD=smartlabs123
```

## 🔧 Dependencias

### Principales
- **express**: Framework web
- **mysql2**: Cliente MySQL
- **mqtt**: Cliente MQTT
- **cors**: Cross-Origin Resource Sharing
- **helmet**: Headers de seguridad
- **joi**: Validación de datos
- **express-rate-limit**: Limitación de requests

### Desarrollo
- **nodemon**: Auto-restart en desarrollo
- **jest**: Testing framework
- **supertest**: Testing de APIs

## 🧪 Testing

```bash
# Ejecutar tests
npm test

# Tests con coverage
npm run test:coverage

# Tests en modo watch
npm run test:watch
```

## 📊 Monitoreo

### Health Check
```bash
curl http://localhost:3000/health
```

### Logs
```bash
# Ver logs en tiempo real
tail -f logs/combined.log

# Ver solo errores
tail -f logs/error.log
```

## 🔐 Seguridad

- **Rate Limiting**: 100 requests por 15 minutos
- **CORS**: Configurado para dominios específicos
- **Helmet**: Headers de seguridad HTTP
- **Validación**: Joi para validar entrada de datos
- **Sanitización**: Prevención de inyección SQL

## 📚 Documentación

- **Documentación Técnica**: [`docs/API_DOCUMENTATION.md`](./docs/API_DOCUMENTATION.md)
- **Endpoints**: Disponible en `GET /api`
- **Postman Collection**: `docs/SmartLabs_API.postman_collection.json`

## 🚀 Despliegue

### Producción
```bash
# Variables de producción
export NODE_ENV=production
export PORT=3000

# Instalar dependencias de producción
npm ci --only=production

# Ejecutar
npm start
```

### Docker Compose
```yaml
services:
  flutter-api:
    build: .
    ports:
      - "3000:3000"
    environment:
      - NODE_ENV=production
      - DB_HOST=mariadb
      - MQTT_HOST=emqx
    depends_on:
      - mariadb
      - emqx
```

## 🤝 Contribución

1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Scripts NPM

```json
{
  "scripts": {
    "start": "node app.js",
    "dev": "nodemon app.js",
    "test": "jest",
    "test:watch": "jest --watch",
    "test:coverage": "jest --coverage",
    "lint": "eslint src/",
    "lint:fix": "eslint src/ --fix"
  }
}
```

## 🐛 Debugging

### Modo Debug
```bash
# Habilitar debug
DEBUG=smartlabs:* npm run dev

# Debug específico
DEBUG=smartlabs:api npm run dev
```

### Logs Detallados
```bash
# Nivel de log
LOG_LEVEL=debug npm run dev
```

## 📞 Soporte

- **Issues**: [GitHub Issues](https://github.com/smartlabs/flutter-api/issues)
- **Documentación**: [`docs/`](./docs/)
- **Email**: soporte@smartlabs.com

---

**Puerto por defecto**: 3000  
**Versión**: 1.0.0  
**Node.js**: >= 18.0.0