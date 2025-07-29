# SMARTLABS Web Application

## Descripción

**SMARTLABS Web Application** es una aplicación web PHP que implementa un sistema de gestión de laboratorios inteligentes. Utiliza una arquitectura MVC (Model-View-Controller) para gestionar usuarios, dispositivos, préstamos de equipos y monitoreo en tiempo real a través de MQTT y WebSocket.

## Características Principales

### 🔐 Sistema de Autenticación
- Login seguro con validación de credenciales
- Gestión de sesiones de usuario
- Control de acceso basado en roles

### 📊 Dashboard Interactivo
- Monitoreo en tiempo real de dispositivos
- Estadísticas de uso y acceso
- Visualización de datos históricos
- Integración con sistemas externos

### 🔧 Gestión de Dispositivos
- Registro y administración de dispositivos IoT
- Control remoto de equipos
- Monitoreo de estado en tiempo real
- Historial de actividad

### 👥 Gestión de Usuarios
- Administración de habitantes/usuarios del laboratorio
- Gestión de becarios y permisos
- Registro de actividades por usuario

### 📦 Sistema de Préstamos
- Gestión de préstamos de equipos
- Control de inventario
- Historial de préstamos
- Notificaciones automáticas

### 📈 Estadísticas y Reportes
- Análisis de uso de dispositivos
- Reportes de actividad por usuario
- Métricas de rendimiento del laboratorio

## Tecnologías Utilizadas

### Backend
- **PHP 7.4+**: Lenguaje principal del servidor
- **MySQL 8.0**: Base de datos principal
- **MySQLi**: Driver de base de datos
- **Arquitectura MVC**: Patrón de diseño

### Frontend
- **HTML5/CSS3**: Estructura y estilos
- **JavaScript ES6+**: Funcionalidad del cliente
- **Bootstrap**: Framework CSS
- **Font Awesome**: Iconografía
- **jQuery**: Manipulación del DOM

### Comunicación
- **MQTT**: Protocolo de mensajería IoT
- **WebSocket**: Comunicación en tiempo real
- **AJAX**: Comunicación asíncrona
- **JSON**: Formato de intercambio de datos

## Estructura del Proyecto

```
c:\laragon\www/
├── index.php                    # Punto de entrada principal
├── README.md                    # Este archivo
├── .htaccess                   # Configuración Apache
├── app/                        # Aplicación principal
│   ├── controllers/            # Controladores MVC
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── DeviceController.php
│   │   ├── EquipmentController.php
│   │   ├── HabitantController.php
│   │   ├── LoanController.php
│   │   └── StatsController.php
│   ├── core/                   # Núcleo del framework
│   │   ├── Controller.php      # Clase base de controladores
│   │   ├── Database.php        # Gestión de base de datos
│   │   ├── Router.php          # Enrutador de URLs
│   │   └── autoload.php        # Cargador automático
│   ├── models/                 # Modelos de datos
│   │   ├── User.php
│   │   ├── Device.php
│   │   ├── Equipment.php
│   │   ├── Habitant.php
│   │   ├── Loan.php
│   │   └── Traffic.php
│   └── views/                  # Vistas de la aplicación
│       ├── auth/
│       ├── dashboard/
│       ├── device/
│       ├── equipment/
│       ├── habitant/
│       ├── layout/
│       ├── loan/
│       └── stats/
├── config/                     # Configuraciones
│   ├── app.php                # Configuración de la aplicación
│   └── database.php           # Configuración de base de datos
├── public/                     # Archivos públicos
│   ├── index.php              # Punto de entrada alternativo
│   ├── js/                    # JavaScript del cliente
│   │   ├── config.js
│   │   ├── mqtt-client.js
│   │   ├── device-status-websocket.js
│   │   └── [otros archivos JS]
│   └── audio/                 # Archivos de audio
├── assets/                     # Recursos estáticos
│   ├── bootstrap/
│   ├── font-awesome/
│   ├── images/
│   └── styles/
├── libs/                       # Librerías externas
│   ├── jquery/
│   ├── angular/
│   └── mqtt/
└── views/                      # Vistas adicionales
    ├── app/
    ├── blocks/
    ├── chart/
    └── ui/
```

## Instalación

### Prerrequisitos

- **PHP 7.4 o superior**
- **MySQL 8.0 o superior**
- **Apache/Nginx** con mod_rewrite habilitado
- **Composer** (opcional, para dependencias futuras)

### Pasos de Instalación

1. **Clonar o descargar el proyecto**
   ```bash
   git clone <repository-url>
   cd smartlabs-web-app
   ```

2. **Configurar el servidor web**
   - Configurar el document root hacia `c:\laragon\www`
   - Asegurar que mod_rewrite esté habilitado
   - Configurar permisos de escritura en directorios necesarios

3. **Configurar la base de datos**
   ```sql
   CREATE DATABASE emqx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'smartlabs'@'localhost' IDENTIFIED BY 'password';
   GRANT ALL PRIVILEGES ON emqx.* TO 'smartlabs'@'localhost';
   FLUSH PRIVILEGES;
   ```

4. **Configurar archivos de configuración**
   
   Editar `config/database.php`:
   ```php
   <?php
   return [
       'host' => 'localhost',
       'username' => 'smartlabs',
       'password' => 'password',
       'database' => 'emqx',
       'port' => '3306',
       'charset' => 'utf8mb4'
   ];
   ```
   
   Editar `config/app.php`:
   ```php
   <?php
   return [
       'app_name' => 'SMARTLABS',
       'app_url' => 'http://localhost',
       'default_controller' => 'Dashboard',
       'default_action' => 'index',
       'assets_path' => '/assets/',
       'session_timeout' => 3600
   ];
   ```

5. **Importar esquema de base de datos**
   ```bash
   mysql -u smartlabs -p emqx < database/schema.sql
   ```

6. **Configurar permisos**
   ```bash
   chmod -R 755 app/
   chmod -R 644 config/
   ```

## Configuración

### Base de Datos

La aplicación utiliza dos conexiones de base de datos:

1. **Base de datos principal** (configurada en `config/database.php`)
   - Usuarios, dispositivos, préstamos
   - Configuración de la aplicación

2. **Base de datos externa** (hardcodeada en controladores)
   - Datos de tráfico y actividad en tiempo real
   - Integración con sistemas IoT

### MQTT

Configuración en `public/js/config.js`:
```javascript
mqtt: {
    brokerUrl: 'ws://localhost:8083/mqtt',
    username: 'jose',
    password: 'public',
    clientId: 'iotmc' + Math.random().toString(16).substr(2, 8),
    topics: {
        deviceStatus: 'smartlabs/devices/+/status',
        deviceRfid: 'smartlabs/devices/+/rfid',
        deviceControl: 'smartlabs/devices/+/control'
    }
}
```

### Sesiones

La aplicación utiliza sesiones PHP nativas:
- Timeout configurable en `config/app.php`
- Validación automática en cada request
- Redirección automática al login si no está autenticado

## Uso

### Acceso a la Aplicación

1. **URL Principal**: `http://localhost/`
2. **Login**: `http://localhost/Auth/login`
3. **Dashboard**: `http://localhost/Dashboard` (requiere autenticación)

### Rutas Principales

| Ruta | Controlador | Descripción |
|------|-------------|-------------|
| `/` | Dashboard | Página principal |
| `/Auth/login` | Auth | Página de login |
| `/Dashboard` | Dashboard | Panel principal |
| `/Device` | Device | Gestión de dispositivos |
| `/Equipment` | Equipment | Gestión de equipos |
| `/Habitant` | Habitant | Gestión de usuarios |
| `/Loan` | Loan | Gestión de préstamos |
| `/Stats` | Stats | Estadísticas y reportes |

### API Endpoints

La aplicación expone varios endpoints AJAX:

```javascript
// Ejemplos de uso
fetch('/Dashboard/getDeviceStatus', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ device_id: 'device001' })
})
.then(response => response.json())
.then(data => console.log(data));
```

## Esquema de Base de Datos

### Tabla: users
```sql
CREATE TABLE users (
    users_id INT AUTO_INCREMENT PRIMARY KEY,
    users_email VARCHAR(100) UNIQUE NOT NULL,
    users_password VARCHAR(255) NOT NULL,
    users_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabla: devices
```sql
CREATE TABLE devices (
    devices_id INT AUTO_INCREMENT PRIMARY KEY,
    devices_alias VARCHAR(100) NOT NULL,
    devices_serie VARCHAR(50) UNIQUE NOT NULL,
    devices_user_id INT,
    devices_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (devices_user_id) REFERENCES users(users_id)
);
```

### Tabla: habintants (Base de datos externa)
```sql
CREATE TABLE habintants (
    hab_id INT AUTO_INCREMENT PRIMARY KEY,
    hab_name VARCHAR(100) NOT NULL,
    hab_registration VARCHAR(20) UNIQUE,
    hab_email VARCHAR(100),
    hab_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabla: traffic (Base de datos externa)
```sql
CREATE TABLE traffic (
    traffic_id INT AUTO_INCREMENT PRIMARY KEY,
    traffic_device VARCHAR(50) NOT NULL,
    traffic_state TINYINT NOT NULL,
    traffic_date DATETIME NOT NULL,
    traffic_hab_id INT,
    FOREIGN KEY (traffic_hab_id) REFERENCES habintants(hab_id)
);
```

## Seguridad

### Medidas Implementadas

1. **Autenticación**
   - Hash SHA1 para contraseñas (recomendado migrar a bcrypt)
   - Validación de sesiones en cada request
   - Timeout automático de sesiones

2. **Validación de Datos**
   - Sanitización de inputs en controladores
   - Prepared statements para consultas SQL
   - Validación de tipos de datos

3. **Control de Acceso**
   - Verificación de autenticación en controladores protegidos
   - Redirección automática a login
   - Separación de rutas públicas y privadas

### Recomendaciones de Seguridad

1. **Migrar a bcrypt** para hash de contraseñas
2. **Implementar CSRF protection**
3. **Usar HTTPS** en producción
4. **Validar y sanitizar** todas las entradas
5. **Implementar rate limiting**
6. **Configurar headers de seguridad**

## Desarrollo

### Estructura MVC

#### Controladores
- Extienden la clase base `Controller`
- Manejan la lógica de negocio
- Validan datos de entrada
- Renderizan vistas

#### Modelos
- Representan entidades de datos
- Encapsulan lógica de base de datos
- Proporcionan métodos CRUD

#### Vistas
- Archivos PHP con HTML/CSS/JS
- Reciben datos de controladores
- Implementan la interfaz de usuario

### Agregar Nuevas Funcionalidades

1. **Crear Controlador**
   ```php
   <?php
   class NuevoController extends Controller {
       public function index() {
           $this->requireAuth();
           $this->view('nuevo/index');
       }
   }
   ```

2. **Crear Modelo**
   ```php
   <?php
   class Nuevo {
       private $db;
       
       public function __construct() {
           $this->db = Database::getInstance();
       }
   }
   ```

3. **Crear Vista**
   ```php
   <!-- app/views/nuevo/index.php -->
   <?php include '../layout/header.php'; ?>
   <div class="content">
       <!-- Contenido de la vista -->
   </div>
   <?php include '../layout/footer.php'; ?>
   ```

## Monitoreo y Logs

### Logs de Errores
- Logs automáticos de errores PHP
- Logs de errores de base de datos
- Logs de conexiones MQTT

### Métricas
- Tiempo de respuesta de páginas
- Uso de memoria
- Conexiones de base de datos
- Actividad de usuarios

## Troubleshooting

### Problemas Comunes

1. **Error 500 - Internal Server Error**
   - Verificar permisos de archivos
   - Revisar logs de Apache/PHP
   - Verificar configuración de base de datos

2. **Página en blanco**
   - Activar display_errors en PHP
   - Verificar sintaxis de archivos PHP
   - Revisar includes/requires

3. **Error de conexión a base de datos**
   - Verificar credenciales en `config/database.php`
   - Verificar que MySQL esté ejecutándose
   - Verificar permisos de usuario de base de datos

4. **Problemas de sesión**
   - Verificar configuración de sesiones PHP
   - Limpiar cookies del navegador
   - Verificar permisos de directorio de sesiones

### Debug

```php
// Activar debug en desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug de variables
var_dump($variable);
print_r($array);

// Debug de consultas SQL
echo $sql;
print_r($params);
```

## Contribución

### Estándares de Código

1. **PSR-4** para autoloading
2. **Camel Case** para métodos y variables
3. **Pascal Case** para clases
4. **Comentarios** en español
5. **Indentación** de 4 espacios

### Proceso de Contribución

1. Fork del repositorio
2. Crear rama feature/bugfix
3. Implementar cambios
4. Probar funcionalidad
5. Crear Pull Request
6. Code review
7. Merge a main

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## Soporte

Para obtener soporte:

1. Revisar esta documentación
2. Buscar en issues existentes
3. Crear nuevo issue con detalles
4. Contactar al equipo de desarrollo

---

**SMARTLABS Web Application** - Sistema de Gestión de Laboratorios Inteligentes