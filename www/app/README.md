# SmartLabs Web Application (PHP)

🌐 **Aplicación web PHP para la gestión del sistema SmartLabs**

Aplicación web desarrollada en PHP que proporciona una interfaz de usuario completa para la gestión de dispositivos IoT, usuarios, equipos y préstamos en el ecosistema SmartLabs.

## 🚀 Inicio Rápido

### Instalación Local

```bash
# Clonar el repositorio
git clone <repository-url>
cd smartlabs/app

# Configurar servidor web (Apache/Nginx)
# Apuntar DocumentRoot a la carpeta 'public'

# Configurar base de datos
cp .env.example .env
# Editar .env con las credenciales de tu base de datos

# Importar esquema de base de datos
mysql -u username -p database_name < database/schema.sql
```

### Con Docker

```bash
# Desde el directorio raíz del proyecto
docker-compose up web

# La aplicación estará disponible en http://localhost:8080
```

## 📋 Características Principales

| Módulo | Descripción | Usuarios |
|--------|-------------|----------|
| **Dashboard** | Panel principal con estadísticas y estado de dispositivos | Todos |
| **Dispositivos** | Gestión y control de dispositivos IoT | Todos |
| **Equipos** | Catálogo de equipos de laboratorio | Todos |
| **Préstamos** | Sistema de préstamos de equipos | Todos |
| **Usuarios** | Gestión de usuarios y permisos | Admin |
| **Administración** | Gestión avanzada de préstamos | Admin |
| **Estadísticas** | Reportes y análisis de uso | Admin |

## 🏗️ Arquitectura

### Patrón MVC
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     Router      │───▶│   Controller    │───▶│     Model       │
│   (Routes)      │    │   (Logic)       │    │   (Data)        │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   index.php     │    │     Views       │    │   Database      │
│ (Entry Point)   │    │  (Templates)    │    │   (MySQL)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Estructura de Directorios
```
app/
├── controllers/     # Lógica de negocio
├── models/         # Modelos de datos
├── views/          # Templates HTML
├── core/           # Clases base (Router, Controller, Database)
├── helpers/        # Funciones auxiliares
├── middleware/     # Middleware de seguridad
├── config/         # Archivos de configuración
└── public/         # Punto de entrada y assets
```

## ⚙️ Configuración

### Variables de Entorno

```env
# Base de datos principal
DB_HOST=localhost
DB_PORT=3306
DB_NAME=emqx
DB_USER=emqxuser
DB_PASSWORD=emqxpass

# Base de datos externa (dashboard)
EXTERNAL_DB_HOST=external-db.com
EXTERNAL_DB_PORT=3306
EXTERNAL_DB_NAME=external_db
EXTERNAL_DB_USER=external_user
EXTERNAL_DB_PASSWORD=external_pass

# Configuración de la aplicación
APP_NAME=SmartLabs
APP_URL=http://localhost
APP_ENV=development
APP_DEBUG=true

# APIs externas
FLUTTER_API_URL=http://localhost:3000
MONITOR_API_URL=http://localhost:8080

# Seguridad
SESSION_LIFETIME=7200
CSRF_PROTECTION=true
RATE_LIMIT_ENABLED=true
```

### Configuración de Apache

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html/public
    ServerName smartlabs.local
    
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/smartlabs_error.log
    CustomLog ${APACHE_LOG_DIR}/smartlabs_access.log combined
</VirtualHost>
```

## 🔧 Dependencias

### Requisitos del Sistema
- **PHP**: >= 8.0
- **MySQL**: >= 5.7 o MariaDB >= 10.3
- **Apache**: >= 2.4 con mod_rewrite
- **Extensiones PHP**: mysqli, pdo, pdo_mysql, json, session

### Librerías Frontend
- **Bootstrap**: 5.3.0 (CSS Framework)
- **Font Awesome**: 6.0.0 (Iconos)
- **jQuery**: 3.6.0 (JavaScript)
- **Chart.js**: 3.9.1 (Gráficos)

## 🔐 Autenticación y Seguridad

### Sistema de Autenticación
```php
// Login de usuario
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

// Verificación de autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    redirect('/Auth/login');
}
```

### Roles de Usuario
- **user**: Usuario estándar (acceso a dispositivos y préstamos)
- **admin**: Administrador (acceso completo al sistema)

### Medidas de Seguridad
- ✅ Protección CSRF
- ✅ Sanitización de entrada
- ✅ Prevención de XSS
- ✅ Headers de seguridad
- ✅ Rate limiting
- ✅ Validación de sesiones

## 📡 Integración con APIs

### Flutter API (Node.js)
```php
// Controlar dispositivo
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/api/device/control');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'device_serie' => $deviceSerie,
    'action' => 'toggle'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
```

### Monitor Service (WebSocket)
```javascript
// Conectar al monitor de dispositivos
const ws = new WebSocket('ws://localhost:8080');

ws.onopen = function() {
    // Suscribirse a dispositivos del usuario
    ws.send(JSON.stringify({
        type: 'subscribe_user_devices',
        user_id: userId
    }));
};

ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    if (data.type === 'device_status_update') {
        updateDeviceStatus(data.device_serie, data.status);
    }
};
```

## 🧪 Testing

### Tests Unitarios
```bash
# Ejecutar tests con PHPUnit
vendor/bin/phpunit tests/

# Test específico
vendor/bin/phpunit tests/Controllers/AuthControllerTest.php

# Con coverage
vendor/bin/phpunit --coverage-html coverage/
```

### Tests de Integración
```bash
# Tests de base de datos
php tests/integration/DatabaseTest.php

# Tests de API
php tests/integration/ApiIntegrationTest.php
```

## 📊 Monitoreo

### Health Check
```php
// public/health.php
header('Content-Type: application/json');

try {
    $db = new Database();
    $db->query('SELECT 1');
    
    echo json_encode([
        'status' => 'healthy',
        'timestamp' => date('c'),
        'services' => [
            'database' => 'up',
            'session' => session_status() === PHP_SESSION_ACTIVE ? 'up' : 'down'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'unhealthy',
        'error' => $e->getMessage()
    ]);
}
```

### Logs
```bash
# Ver logs de Apache
tail -f /var/log/apache2/smartlabs_error.log

# Ver logs de la aplicación
tail -f logs/app.log

# Ver logs de acceso
tail -f /var/log/apache2/smartlabs_access.log
```

## 🚀 Deployment

### Producción
```bash
# Optimizar para producción
composer install --no-dev --optimize-autoloader

# Configurar permisos
chmod -R 755 .
chown -R www-data:www-data .
chmod -R 777 logs/

# Configurar SSL
certbot --apache -d smartlabs.com
```

### Docker Compose
```yaml
services:
  web:
    build: .
    ports:
      - "8080:80"
    environment:
      - DB_HOST=mariadb
      - DB_NAME=emqx
      - DB_USER=emqxuser
      - DB_PASSWORD=emqxpass
    depends_on:
      - mariadb
    volumes:
      - ./logs:/var/www/html/logs
```

## 🔍 Debugging

### Habilitar Debug
```php
// config/app.php
'debug' => true,
'log_level' => 'debug',
'display_errors' => true
```

### Logs de Debug
```php
// Logging personalizado
error_log("Debug: " . print_r($data, true));

// Log de queries
$db->enableQueryLog();
$queries = $db->getQueryLog();
```

### Herramientas de Desarrollo
```bash
# Xdebug para debugging
sudo apt-get install php-xdebug

# Composer para dependencias
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 📚 Documentación

- 📖 **[Documentación Técnica Completa](docs/WEB_APPLICATION_DOCUMENTATION.md)**
- 🏗️ **[Guía de Arquitectura](docs/ARCHITECTURE.md)**
- 🔐 **[Guía de Seguridad](docs/SECURITY.md)**
- 🚀 **[Guía de Deployment](docs/DEPLOYMENT.md)**

## 🤝 Contribución

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

### Estándares de Código
- **PSR-12**: Estándar de codificación PHP
- **Comentarios**: Documentar funciones complejas
- **Naming**: CamelCase para clases, snake_case para variables
- **Security**: Siempre sanitizar entrada de usuario

## 📞 Soporte

- **Email**: soporte@smartlabs.com
- **Documentación**: [docs.smartlabs.com](https://docs.smartlabs.com)
- **Issues**: [GitHub Issues](https://github.com/smartlabs/issues)
- **Wiki**: [GitHub Wiki](https://github.com/smartlabs/wiki)

---

**Versión**: 1.0.0  
**Licencia**: MIT  
**Mantenido por**: Equipo SmartLabs