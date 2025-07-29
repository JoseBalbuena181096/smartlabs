# Guía de Desarrollo - SMARTLABS Flutter API

## Configuración del Entorno de Desarrollo

### Prerrequisitos

- **Node.js** v18.0.0 o superior
- **npm** v8.0.0 o superior
- **MySQL** v8.0 o superior
- **MQTT Broker** (EMQX recomendado)
- **Git** para control de versiones
- **Postman** o **Insomnia** para testing de API

### Instalación del Entorno

1. **Clonar el repositorio**
   ```bash
   git clone <repository-url>
   cd flutter-api
   ```

2. **Instalar dependencias**
   ```bash
   npm install
   ```

3. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   ```

4. **Configurar base de datos**
   ```sql
   CREATE DATABASE smartlabs;
   CREATE USER 'smartlabs_user'@'localhost' IDENTIFIED BY 'password';
   GRANT ALL PRIVILEGES ON smartlabs.* TO 'smartlabs_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

5. **Instalar y configurar EMQX (MQTT Broker)**
   ```bash
   # Docker
   docker run -d --name emqx -p 1883:1883 -p 8083:8083 -p 8084:8084 -p 8883:8883 -p 18083:18083 emqx/emqx:latest
   
   # O instalación local
   # Descargar desde https://www.emqx.io/downloads
   ```

## Estructura de Desarrollo

### Scripts de npm

```json
{
  "scripts": {
    "start": "node src/index.js",
    "dev": "nodemon src/index.js",
    "test": "jest",
    "test:watch": "jest --watch",
    "test:coverage": "jest --coverage",
    "lint": "eslint src/",
    "lint:fix": "eslint src/ --fix",
    "format": "prettier --write src/",
    "db:migrate": "node scripts/migrate.js",
    "db:seed": "node scripts/seed.js",
    "docs:generate": "node scripts/generate-docs.js"
  }
}
```

### Configuración de ESLint

**`.eslintrc.js`**
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
    'linebreak-style': ['error', 'unix'],
    'quotes': ['error', 'single'],
    'semi': ['error', 'always'],
    'no-console': 'warn',
    'no-unused-vars': 'error',
    'no-undef': 'error'
  }
};
```

### Configuración de Prettier

**`.prettierrc`**
```json
{
  "semi": true,
  "trailingComma": "es5",
  "singleQuote": true,
  "printWidth": 100,
  "tabWidth": 4,
  "useTabs": false
}
```

### Configuración de Jest

**`jest.config.js`**
```javascript
module.exports = {
  testEnvironment: 'node',
  collectCoverageFrom: [
    'src/**/*.js',
    '!src/index.js',
    '!src/config/*.js'
  ],
  coverageDirectory: 'coverage',
  coverageReporters: ['text', 'lcov', 'html'],
  testMatch: [
    '**/__tests__/**/*.js',
    '**/?(*.)+(spec|test).js'
  ],
  setupFilesAfterEnv: ['<rootDir>/tests/setup.js']
};
```

## Flujo de Desarrollo

### 1. Configuración de Git Hooks

**`.husky/pre-commit`**
```bash
#!/bin/sh
. "$(dirname "$0")/_/husky.sh"

npm run lint
npm run test
```

### 2. Workflow de Desarrollo

1. **Crear rama de feature**
   ```bash
   git checkout -b feature/nueva-funcionalidad
   ```

2. **Desarrollar y testear**
   ```bash
   npm run dev  # Servidor en modo desarrollo
   npm run test:watch  # Tests en modo watch
   ```

3. **Verificar calidad de código**
   ```bash
   npm run lint
   npm run test:coverage
   ```

4. **Commit y push**
   ```bash
   git add .
   git commit -m "feat: agregar nueva funcionalidad"
   git push origin feature/nueva-funcionalidad
   ```

5. **Crear Pull Request**

## Testing

### Estructura de Tests

```
tests/
├── unit/
│   ├── controllers/
│   ├── services/
│   └── utils/
├── integration/
│   ├── api/
│   └── database/
├── e2e/
│   └── scenarios/
├── fixtures/
│   └── data/
└── setup.js
```

### Ejemplo de Test Unitario

**`tests/unit/services/userService.test.js`**
```javascript
const userService = require('../../../src/services/userService');
const dbConfig = require('../../../src/config/database');

// Mock de la base de datos
jest.mock('../../../src/config/database');

describe('UserService', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    describe('getUserByRegistration', () => {
        it('should return user when found', async () => {
            // Arrange
            const mockUser = {
                id: 1,
                name: 'Juan Pérez',
                registration: '12345',
                email: 'juan@test.com'
            };
            
            dbConfig.getConnection.mockReturnValue({
                execute: jest.fn().mockResolvedValue([[mockUser]])
            });

            // Act
            const result = await userService.getUserByRegistration('12345');

            // Assert
            expect(result).toEqual(mockUser);
            expect(dbConfig.getConnection().execute).toHaveBeenCalledWith(
                'SELECT * FROM users WHERE registration = ?',
                ['12345']
            );
        });

        it('should return null when user not found', async () => {
            // Arrange
            dbConfig.getConnection.mockReturnValue({
                execute: jest.fn().mockResolvedValue([[]])
            });

            // Act
            const result = await userService.getUserByRegistration('99999');

            // Assert
            expect(result).toBeNull();
        });

        it('should throw error when database fails', async () => {
            // Arrange
            dbConfig.getConnection.mockReturnValue({
                execute: jest.fn().mockRejectedValue(new Error('DB Error'))
            });

            // Act & Assert
            await expect(userService.getUserByRegistration('12345'))
                .rejects.toThrow('DB Error');
        });
    });
});
```

### Ejemplo de Test de Integración

**`tests/integration/api/users.test.js`**
```javascript
const request = require('supertest');
const SmartLabsFlutterAPI = require('../../../src/index');

describe('Users API Integration', () => {
    let app;
    let server;

    beforeAll(async () => {
        app = new SmartLabsFlutterAPI();
        await app.initializeConnections();
        server = app.app;
    });

    afterAll(async () => {
        await app.stop();
    });

    describe('GET /api/users/registration/:registration', () => {
        it('should return user when valid registration provided', async () => {
            const response = await request(server)
                .get('/api/users/registration/12345')
                .set('X-API-Key', process.env.API_KEY)
                .expect(200);

            expect(response.body.success).toBe(true);
            expect(response.body.data).toHaveProperty('id');
            expect(response.body.data).toHaveProperty('name');
            expect(response.body.data.registration).toBe('12345');
        });

        it('should return 404 when user not found', async () => {
            const response = await request(server)
                .get('/api/users/registration/99999')
                .set('X-API-Key', process.env.API_KEY)
                .expect(404);

            expect(response.body.success).toBe(false);
            expect(response.body.message).toBe('Usuario no encontrado');
        });

        it('should return 401 when no API key provided', async () => {
            await request(server)
                .get('/api/users/registration/12345')
                .expect(401);
        });
    });
});
```

## Base de Datos

### Esquema de Base de Datos

**`scripts/schema.sql`**
```sql
-- Tabla de usuarios
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    registration VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    cards_number VARCHAR(100),
    device_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_registration (registration),
    INDEX idx_cards_number (cards_number)
);

-- Tabla de dispositivos
CREATE TABLE devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_serie VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('workstation', 'equipment', 'tool') NOT NULL,
    location VARCHAR(255),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'inactive',
    specifications JSON,
    last_maintenance DATE,
    next_maintenance DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_device_serie (device_serie),
    INDEX idx_status (status)
);

-- Tabla de préstamos/sesiones
CREATE TABLE loans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    device_id INT NOT NULL,
    session_id VARCHAR(100) UNIQUE,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    duration_minutes INT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (device_id) REFERENCES devices(id),
    INDEX idx_user_id (user_id),
    INDEX idx_device_id (device_id),
    INDEX idx_session_id (session_id),
    INDEX idx_status (status)
);

-- Tabla de logs de acceso
CREATE TABLE access_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    device_id INT,
    action ENUM('login', 'logout', 'access_denied') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (device_id) REFERENCES devices(id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action)
);

-- Tabla de datos de sensores
CREATE TABLE sensor_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT,
    temperature DECIMAL(5,2),
    humidity DECIMAL(5,2),
    voltage DECIMAL(5,2),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id),
    INDEX idx_device_timestamp (device_id, timestamp)
);
```

### Scripts de Migración

**`scripts/migrate.js`**
```javascript
const fs = require('fs');
const path = require('path');
const dbConfig = require('../src/config/database');

async function runMigrations() {
    try {
        console.log('🔄 Ejecutando migraciones...');
        
        const connection = await dbConfig.connect();
        const schemaSQL = fs.readFileSync(path.join(__dirname, 'schema.sql'), 'utf8');
        
        // Dividir por statements
        const statements = schemaSQL
            .split(';')
            .filter(stmt => stmt.trim().length > 0);
        
        for (const statement of statements) {
            await connection.execute(statement);
        }
        
        console.log('✅ Migraciones completadas');
        await dbConfig.close();
        
    } catch (error) {
        console.error('❌ Error en migraciones:', error);
        process.exit(1);
    }
}

if (require.main === module) {
    runMigrations();
}

module.exports = runMigrations;
```

### Scripts de Seed

**`scripts/seed.js`**
```javascript
const dbConfig = require('../src/config/database');

const seedData = {
    users: [
        {
            name: 'Juan Pérez González',
            registration: '12345',
            email: 'juan.perez@universidad.edu',
            cards_number: 'ABCD1234EFGH',
            device_id: 'LAB001'
        },
        {
            name: 'María García López',
            registration: '67890',
            email: 'maria.garcia@universidad.edu',
            cards_number: 'EFGH5678IJKL',
            device_id: 'LAB002'
        }
    ],
    devices: [
        {
            device_serie: 'LAB001',
            name: 'Estación de Trabajo 1',
            type: 'workstation',
            location: 'Laboratorio A - Mesa 1',
            specifications: JSON.stringify({
                cpu: 'Intel i7-10700K',
                ram: '32GB DDR4',
                gpu: 'NVIDIA RTX 3070',
                storage: '1TB NVMe SSD'
            })
        },
        {
            device_serie: 'EQUIP001',
            name: 'Osciloscopio Digital',
            type: 'equipment',
            location: 'Laboratorio B - Estante 1',
            specifications: JSON.stringify({
                brand: 'Tektronix',
                model: 'TBS1052B',
                bandwidth: '50MHz',
                channels: 2
            })
        }
    ]
};

async function seedDatabase() {
    try {
        console.log('🌱 Sembrando datos de prueba...');
        
        const connection = await dbConfig.connect();
        
        // Insertar usuarios
        for (const user of seedData.users) {
            await connection.execute(
                'INSERT INTO users (name, registration, email, cards_number, device_id) VALUES (?, ?, ?, ?, ?)',
                [user.name, user.registration, user.email, user.cards_number, user.device_id]
            );
        }
        
        // Insertar dispositivos
        for (const device of seedData.devices) {
            await connection.execute(
                'INSERT INTO devices (device_serie, name, type, location, specifications) VALUES (?, ?, ?, ?, ?)',
                [device.device_serie, device.name, device.type, device.location, device.specifications]
            );
        }
        
        console.log('✅ Datos de prueba insertados');
        await dbConfig.close();
        
    } catch (error) {
        console.error('❌ Error sembrando datos:', error);
        process.exit(1);
    }
}

if (require.main === module) {
    seedDatabase();
}

module.exports = seedDatabase;
```

## Debugging

### Configuración de VS Code

**`.vscode/launch.json`**
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Debug API",
            "type": "node",
            "request": "launch",
            "program": "${workspaceFolder}/src/index.js",
            "env": {
                "NODE_ENV": "development"
            },
            "console": "integratedTerminal",
            "restart": true,
            "runtimeExecutable": "nodemon",
            "skipFiles": [
                "<node_internals>/**"
            ]
        },
        {
            "name": "Debug Tests",
            "type": "node",
            "request": "launch",
            "program": "${workspaceFolder}/node_modules/.bin/jest",
            "args": ["--runInBand"],
            "console": "integratedTerminal",
            "internalConsoleOptions": "neverOpen",
            "disableOptimisticBPs": true
        }
    ]
}
```

### Logging Avanzado

**`src/utils/logger.js`**
```javascript
const winston = require('winston');

const logger = winston.createLogger({
    level: process.env.LOG_LEVEL || 'info',
    format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.errors({ stack: true }),
        winston.format.json()
    ),
    defaultMeta: { service: 'smartlabs-api' },
    transports: [
        new winston.transports.File({ filename: 'logs/error.log', level: 'error' }),
        new winston.transports.File({ filename: 'logs/combined.log' })
    ]
});

if (process.env.NODE_ENV !== 'production') {
    logger.add(new winston.transports.Console({
        format: winston.format.combine(
            winston.format.colorize(),
            winston.format.simple()
        )
    }));
}

module.exports = logger;
```

## Monitoreo y Performance

### Health Check Endpoint

**`src/routes/healthRoutes.js`**
```javascript
const express = require('express');
const dbConfig = require('../config/database');
const mqttConfig = require('../config/mqtt');
const router = express.Router();

router.get('/health', async (req, res) => {
    const health = {
        status: 'ok',
        timestamp: new Date().toISOString(),
        uptime: process.uptime(),
        memory: process.memoryUsage(),
        services: {
            database: 'unknown',
            mqtt: 'unknown'
        }
    };

    try {
        // Check database
        const connection = dbConfig.getConnection();
        if (connection) {
            await connection.execute('SELECT 1');
            health.services.database = 'ok';
        } else {
            health.services.database = 'error';
        }
    } catch (error) {
        health.services.database = 'error';
    }

    try {
        // Check MQTT
        if (mqttConfig.isConnected()) {
            health.services.mqtt = 'ok';
        } else {
            health.services.mqtt = 'error';
        }
    } catch (error) {
        health.services.mqtt = 'error';
    }

    const hasErrors = Object.values(health.services).includes('error');
    const statusCode = hasErrors ? 503 : 200;
    
    if (hasErrors) {
        health.status = 'error';
    }

    res.status(statusCode).json(health);
});

module.exports = router;
```

## Deployment

### Docker Configuration

**`Dockerfile`**
```dockerfile
FROM node:18-alpine

# Crear directorio de aplicación
WORKDIR /usr/src/app

# Copiar archivos de dependencias
COPY package*.json ./

# Instalar dependencias
RUN npm ci --only=production

# Copiar código fuente
COPY src/ ./src/

# Crear usuario no-root
RUN addgroup -g 1001 -S nodejs
RUN adduser -S smartlabs -u 1001

# Cambiar ownership
RUN chown -R smartlabs:nodejs /usr/src/app
USER smartlabs

# Exponer puerto
EXPOSE 3000

# Comando de inicio
CMD ["node", "src/index.js"]
```

**`docker-compose.yml`**
```yaml
version: '3.8'

services:
  api:
    build: .
    ports:
      - "3000:3000"
    environment:
      - NODE_ENV=production
      - DB_HOST=mysql
      - MQTT_HOST=emqx
    depends_on:
      - mysql
      - emqx
    volumes:
      - ./logs:/usr/src/app/logs
    restart: unless-stopped

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: smartlabs
      MYSQL_USER: smartlabs_user
      MYSQL_PASSWORD: password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./scripts/schema.sql:/docker-entrypoint-initdb.d/schema.sql
    restart: unless-stopped

  emqx:
    image: emqx/emqx:latest
    ports:
      - "1883:1883"
      - "8083:8083"
      - "8084:8084"
      - "18083:18083"
    environment:
      - EMQX_NAME=emqx
      - EMQX_HOST=127.0.0.1
    restart: unless-stopped

volumes:
  mysql_data:
```

### CI/CD Pipeline

**`.github/workflows/ci.yml`**
```yaml
name: CI/CD Pipeline

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        node-version: [16.x, 18.x]
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Use Node.js ${{ matrix.node-version }}
      uses: actions/setup-node@v3
      with:
        node-version: ${{ matrix.node-version }}
        cache: 'npm'
    
    - name: Install dependencies
      run: npm ci
    
    - name: Run linter
      run: npm run lint
    
    - name: Run tests
      run: npm run test:coverage
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage/lcov.info

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Build Docker image
      run: docker build -t smartlabs-api .
    
    - name: Deploy to production
      run: |
        echo "Deploying to production..."
        # Aquí irían los comandos de deployment
```

## Troubleshooting

### Problemas Comunes

1. **Error de conexión a MySQL**
   ```bash
   # Verificar que MySQL esté corriendo
   sudo systemctl status mysql
   
   # Verificar configuración
   mysql -u root -p -e "SHOW DATABASES;"
   ```

2. **Error de conexión MQTT**
   ```bash
   # Verificar que EMQX esté corriendo
   docker ps | grep emqx
   
   # Verificar logs
   docker logs emqx
   ```

3. **Puerto en uso**
   ```bash
   # Encontrar proceso usando el puerto
   lsof -i :3000
   
   # Matar proceso
   kill -9 <PID>
   ```

### Logs de Debug

```bash
# Logs en tiempo real
tail -f logs/combined.log

# Filtrar errores
grep "ERROR" logs/combined.log

# Logs de MQTT
grep "MQTT" logs/combined.log
```

---

**Guía de Desarrollo v1.0**  
**SMARTLABS Team**  
**Fecha: 2024**