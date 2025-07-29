# Guía de Desarrollo - SMARTLABS Device Status Server

## Introducción

Esta guía proporciona toda la información necesaria para configurar, desarrollar y mantener el **SMARTLABS Device Status Server**. Incluye instrucciones detalladas para el entorno de desarrollo, testing, debugging y deployment.

## Prerrequisitos

### Software Requerido

- **Node.js**: Versión 16.x o superior
- **npm**: Versión 8.x o superior (incluido con Node.js)
- **MySQL**: Versión 8.0 o superior
- **Git**: Para control de versiones
- **Editor de Código**: VS Code recomendado

### Verificación de Prerrequisitos

```bash
# Verificar versiones instaladas
node --version    # Debe ser >= 16.0.0
npm --version     # Debe ser >= 8.0.0
mysql --version   # Debe ser >= 8.0.0
git --version     # Cualquier versión reciente
```

## Instalación del Entorno de Desarrollo

### 1. Clonar o Navegar al Proyecto

```bash
# Si es un repositorio Git
git clone <repository-url>
cd smartlabs-device-status-server

# O navegar al directorio existente
cd c:\laragon\www\node
```

### 2. Instalar Dependencias

```bash
# Instalar dependencias de producción y desarrollo
npm install

# Verificar instalación
npm list --depth=0
```

### 3. Configurar Base de Datos

#### Crear Base de Datos

```sql
-- Conectar a MySQL como root
mysql -u root -p

-- Crear base de datos
CREATE DATABASE emqx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario (opcional)
CREATE USER 'smartlabs'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON emqx.* TO 'smartlabs'@'localhost';
FLUSH PRIVILEGES;
```

#### Crear Tablas

```sql
-- Usar la base de datos
USE emqx;

-- Tabla de habitantes/usuarios
CREATE TABLE habintants (
    hab_id INT AUTO_INCREMENT PRIMARY KEY,
    hab_name VARCHAR(100) NOT NULL,
    hab_registration VARCHAR(20) UNIQUE,
    hab_email VARCHAR(100),
    hab_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    hab_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_registration (hab_registration),
    INDEX idx_email (hab_email)
);

-- Tabla de tráfico/actividad de dispositivos
CREATE TABLE traffic (
    traffic_id INT AUTO_INCREMENT PRIMARY KEY,
    traffic_device VARCHAR(50) NOT NULL,
    traffic_state TINYINT NOT NULL COMMENT '0=off, 1=on',
    traffic_date DATETIME NOT NULL,
    traffic_hab_id INT,
    traffic_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (traffic_hab_id) REFERENCES habintants(hab_id) ON DELETE SET NULL,
    INDEX idx_device_date (traffic_device, traffic_date DESC),
    INDEX idx_state (traffic_state),
    INDEX idx_hab_id (traffic_hab_id),
    INDEX idx_date (traffic_date)
);
```

## Scripts de Desarrollo

### Scripts NPM Disponibles

```json
{
  "scripts": {
    "start": "node scripts/start-device-server.js",
    "dev": "nodemon scripts/start-device-server.js",
    "test": "jest",
    "test:watch": "jest --watch",
    "test:coverage": "jest --coverage",
    "lint": "eslint src/ scripts/",
    "lint:fix": "eslint src/ scripts/ --fix",
    "format": "prettier --write src/ scripts/",
    "db:setup": "node scripts/setup-database.js",
    "db:seed": "node scripts/generate-test-data.js"
  }
}
```

### Uso de Scripts

```bash
# Desarrollo
npm run dev              # Iniciar con nodemon (auto-reload)
npm start               # Iniciar en modo producción

# Testing
npm test                # Ejecutar todos los tests
npm run test:watch      # Tests en modo watch
npm run test:coverage   # Tests con cobertura

# Calidad de Código
npm run lint            # Verificar estilo de código
npm run lint:fix        # Corregir problemas automáticamente
npm run format          # Formatear código

# Base de Datos
npm run db:setup        # Configurar base de datos
npm run db:seed         # Insertar datos de prueba
```

## Configuración de Herramientas

### ESLint (`.eslintrc.js`)

```javascript
module.exports = {
    env: {
        node: true,
        es2021: true,
        jest: true
    },
    extends: [
        'eslint:recommended'
    ],
    parserOptions: {
        ecmaVersion: 12,
        sourceType: 'module'
    },
    rules: {
        'indent': ['error', 4],
        'quotes': ['error', 'single'],
        'semi': ['error', 'always'],
        'no-console': 'warn',
        'no-unused-vars': ['error', { 'argsIgnorePattern': '^_' }]
    }
};
```

### Prettier (`.prettierrc`)

```json
{
    "semi": true,
    "trailingComma": "es5",
    "singleQuote": true,
    "printWidth": 100,
    "tabWidth": 4
}
```

## Testing

### Estructura de Tests

```
tests/
├── unit/
│   ├── config.test.js
│   └── utils.test.js
├── integration/
│   ├── websocket.test.js
│   └── database.test.js
└── load/
    └── performance.test.js
```

### Ejemplo de Test Unitario

```javascript
const { formatDeviceStatus } = require('../src/utils/helpers');

describe('Helpers Utils', () => {
    test('should format device status correctly', () => {
        const rawData = {
            traffic_device: 'device001',
            traffic_state: 1,
            traffic_date: '2025-01-08 10:30:00',
            hab_name: 'Juan Pérez'
        };
        
        const formatted = formatDeviceStatus(rawData);
        
        expect(formatted).toEqual({
            device: 'device001',
            state: 'on',
            last_activity: '2025-01-08 10:30:00',
            user: 'Juan Pérez'
        });
    });
});
```

## Docker y Containerización

### Dockerfile

```dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .

EXPOSE 3000

CMD ["npm", "start"]
```

### Docker Compose

```yaml
version: '3.8'

services:
  device-status-server:
    build: .
    ports:
      - "3000:3000"
    environment:
      - NODE_ENV=production
      - DB_HOST=mysql
    depends_on:
      - mysql

  mysql:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=rootpassword
      - MYSQL_DATABASE=emqx
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```

## Troubleshooting

### Problemas Comunes

#### Error de Conexión a Base de Datos

```bash
# Verificar conectividad
mysql -h localhost -u root -p

# Verificar logs
tail -f logs/error.log
```

#### WebSocket No Se Conecta

```bash
# Verificar puerto
netstat -tulpn | grep :3000

# Test de conexión
wscat -c ws://localhost:3000
```

### Análisis de Logs

```bash
# Ver logs en tiempo real
tail -f logs/app.log

# Filtrar errores
grep "ERROR" logs/app.log

# Logs de WebSocket
grep "websocket" logs/app.log
```

## Mejores Prácticas

### Desarrollo

1. **Implementar tests antes de features**
2. **Seguir convenciones de naming**
3. **Usar ESLint y Prettier**
4. **Hacer commits pequeños y descriptivos**

### Producción

1. **Usar variables de entorno**
2. **Implementar logging estructurado**
3. **Monitorear métricas de performance**
4. **Hacer backups regulares**

### Seguridad

1. **No exponer credenciales en código**
2. **Usar conexiones seguras**
3. **Implementar rate limiting**
4. **Validar todas las entradas**

## Contribución

### Proceso

1. Fork del repositorio
2. Crear rama feature/bugfix
3. Implementar cambios con tests
4. Crear Pull Request
5. Code review
6. Merge a main

### Commit Messages

```bash
feat(websocket): agregar soporte para suscripción múltiple
fix(database): corregir memory leak en connection pool
docs(readme): actualizar instrucciones de instalación
```

## Soporte

Para obtener ayuda:

1. Revisar esta documentación
2. Buscar en issues existentes
3. Crear nuevo issue con detalles
4. Contactar al equipo de desarrollo

---

**¡Feliz desarrollo! 🚀**