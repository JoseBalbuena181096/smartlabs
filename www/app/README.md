# SMARTLABS - Sistema de Gestión de Laboratorio

## Descripción

SMARTLABS es una aplicación web desarrollada en PHP que implementa un sistema de gestión integral para laboratorios. La aplicación utiliza una arquitectura MVC (Modelo-Vista-Controlador) y proporciona funcionalidades para la gestión de dispositivos IoT, control de acceso, préstamos de equipos, y monitoreo en tiempo real.

## Características Principales

- **Autenticación de usuarios** con sistema de sesiones
- **Dashboard en tiempo real** con estadísticas de acceso
- **Gestión de dispositivos IoT** con monitoreo de estado
- **Control de acceso** con registro de tráfico
- **Sistema de préstamos** de equipos de laboratorio
- **Cierre Automático de Sesiones**: Cierre inteligente cuando se detecta el mismo RFID, RFID diferente o valor vacío
- **Gestión de habitantes** y becarios
- **Estadísticas avanzadas** con filtros por dispositivo
- **Interfaz responsive** con Bootstrap
- **API REST** para integración con sistemas externos

## Tecnologías Utilizadas

- **Backend**: PHP 7.4+
- **Base de datos**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap
- **Servidor web**: Apache con mod_rewrite
- **Arquitectura**: MVC personalizada
- **Autenticación**: Sesiones PHP con SHA1

## Estructura del Proyecto

```
c:\laragon\www\
├── app/                          # Aplicación principal
│   ├── controllers/              # Controladores MVC
│   │   ├── AuthController.php    # Autenticación
│   │   ├── DashboardController.php # Dashboard principal
│   │   ├── DeviceController.php  # Gestión de dispositivos
│   │   ├── HabitantController.php # Gestión de habitantes
│   │   ├── LoanController.php    # Sistema de préstamos
│   │   └── ...
│   ├── core/                     # Núcleo del framework
│   │   ├── Controller.php        # Controlador base
│   │   ├── Database.php          # Conexión a BD
│   │   ├── Router.php            # Enrutador
│   │   └── autoload.php          # Cargador automático
│   ├── models/                   # Modelos de datos
│   │   ├── User.php              # Modelo de usuarios
│   │   ├── Device.php            # Modelo de dispositivos
│   │   ├── Traffic.php           # Modelo de tráfico
│   │   └── ...
│   └── views/                    # Vistas de la aplicación
│       ├── auth/                 # Vistas de autenticación
│       ├── dashboard/            # Vistas del dashboard
│       ├── layout/               # Plantillas base
│       └── ...
├── config/                       # Configuración
│   ├── app.php                   # Configuración general
│   └── database.php              # Configuración de BD
├── public/                       # Archivos públicos
│   ├── css/                      # Hojas de estilo
│   ├── js/                       # Scripts JavaScript
│   ├── images/                   # Imágenes
│   └── index.php                 # Punto de entrada alternativo
├── .htaccess                     # Configuración Apache
└── index.php                     # Controlador frontal
```

## Instalación

### Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o MariaDB 10.3+
- Apache con mod_rewrite habilitado
- Extensiones PHP: mysqli, session, json

### Pasos de Instalación

1. **Clonar o descargar el proyecto**
   ```bash
   git clone <repository-url> c:\laragon\www
   ```

2. **Configurar la base de datos**
   - Editar `config/database.php` con las credenciales correctas
   - Importar el esquema de base de datos

3. **Configurar Apache**
   - Asegurar que mod_rewrite esté habilitado
   - El archivo `.htaccess` ya está configurado

4. **Configurar la aplicación**
   - Editar `config/app.php` según el entorno
   - Configurar la URL base de la aplicación

5. **Verificar permisos**
   - Asegurar permisos de lectura/escritura en directorios necesarios

## Configuración

### Configuración de la Aplicación (`config/app.php`)

```php
return [
    'app_name' => 'SMARTLABS',
    'app_url' => 'http://localhost',
    'default_controller' => 'Dashboard',
    'default_action' => 'index',
    'assets_path' => '/public',
    'session_timeout' => 3600
];
```

### Configuración de Base de Datos (`config/database.php`)

```php
return [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'smartlabs',
    'port' => 3306,
    'charset' => 'utf8mb4'
];
```

## Uso

### Autenticación

1. **Acceder al sistema**: `http://localhost/Auth/login`
2. **Registrar nuevo usuario**: `http://localhost/Auth/register`
3. **Cerrar sesión**: `http://localhost/Auth/logout`

### Dashboard Principal

- **URL**: `http://localhost/Dashboard`
- **Funcionalidades**:
  - Visualización de dispositivos del usuario
  - Estadísticas en tiempo real
  - Tráfico de acceso por dispositivo
  - Gráficos y métricas

### Gestión de Dispositivos

- **Listar dispositivos**: `http://localhost/Device`
- **Agregar dispositivo**: Formulario en la vista de dispositivos
- **Editar/Eliminar**: Acciones disponibles en la lista

## Arquitectura MVC

### Controladores

Los controladores manejan la lógica de la aplicación y coordinan entre modelos y vistas:

- **AuthController**: Maneja autenticación y registro
- **DashboardController**: Dashboard principal con estadísticas
- **DeviceController**: CRUD de dispositivos
- **HabitantController**: Gestión de habitantes
- **LoanController**: Sistema de préstamos

### Modelos

Los modelos representan la lógica de datos y la interacción con la base de datos:

- **User**: Gestión de usuarios y autenticación
- **Device**: Operaciones con dispositivos IoT
- **Traffic**: Registro de accesos y tráfico
- **Habitant**: Información de habitantes del laboratorio
- **Loan**: Sistema de préstamos de equipos

### Vistas

Las vistas presentan la información al usuario:

- **Layout**: Plantillas base (header, footer, sidebar)
- **Auth**: Formularios de login y registro
- **Dashboard**: Interfaz principal con estadísticas
- **Device**: Gestión de dispositivos
- **Habitant**: Gestión de habitantes

## Base de Datos

### Tablas Principales

- **users**: Usuarios del sistema
- **devices**: Dispositivos IoT registrados
- **traffic**: Registro de accesos a dispositivos
- **habintants**: Habitantes del laboratorio
- **equipment**: Equipos disponibles para préstamo
- **loans**: Préstamos de equipos
- **becarios**: Información de becarios

### Esquema de Usuarios

```sql
CREATE TABLE users (
    users_id INT AUTO_INCREMENT PRIMARY KEY,
    users_email VARCHAR(255) UNIQUE NOT NULL,
    users_password VARCHAR(255) NOT NULL,
    users_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Esquema de Dispositivos

```sql
CREATE TABLE devices (
    devices_id INT AUTO_INCREMENT PRIMARY KEY,
    devices_alias VARCHAR(255) NOT NULL,
    devices_serie VARCHAR(255) UNIQUE NOT NULL,
    devices_user_id INT NOT NULL,
    devices_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (devices_user_id) REFERENCES users(users_id)
);
```

## API Endpoints

### Autenticación

- `POST /Auth/login` - Iniciar sesión
- `POST /Auth/register` - Registrar usuario
- `GET /Auth/logout` - Cerrar sesión

### Dashboard

- `GET /Dashboard` - Dashboard principal
- `GET /Dashboard/refresh` - Actualizar datos (AJAX)
- `GET /Dashboard/stats` - Estadísticas en tiempo real

### Dispositivos

- `GET /Device` - Listar dispositivos
- `POST /Device/create` - Crear dispositivo
- `PUT /Device/update/{id}` - Actualizar dispositivo
- `DELETE /Device/delete/{id}` - Eliminar dispositivo

## Seguridad

### Medidas Implementadas

- **Autenticación por sesiones** con timeout configurable
- **Sanitización de datos** en todos los inputs
- **Validación de email** en formularios
- **Protección CSRF** en formularios críticos
- **Escape de datos** en las vistas
- **Conexión segura** a base de datos con prepared statements

### Recomendaciones Adicionales

- Migrar de SHA1 a bcrypt para passwords
- Implementar HTTPS en producción
- Configurar headers de seguridad
- Implementar rate limiting
- Logs de seguridad y auditoría

## Desarrollo

### Agregar un Nuevo Controlador

1. Crear archivo en `app/controllers/`
2. Extender de la clase `Controller`
3. Implementar métodos de acción
4. Crear vistas correspondientes

```php
<?php
require_once __DIR__ . '/../core/Controller.php';

class MiControlador extends Controller {
    public function __construct() {
        parent::__construct();
        $this->requireAuth(); // Si requiere autenticación
    }
    
    public function index() {
        $this->view('mi_vista/index', $data);
    }
}
```

### Agregar un Nuevo Modelo

1. Crear archivo en `app/models/`
2. Implementar métodos de acceso a datos
3. Usar la instancia de Database

```php
<?php
class MiModelo {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll() {
        return $this->db->query("SELECT * FROM mi_tabla");
    }
}
```

## Monitoreo y Logs

### Logs del Sistema

- Logs de Apache: `/var/log/apache2/`
- Logs de PHP: Configurar en `php.ini`
- Logs de aplicación: Implementar logging personalizado

### Métricas Importantes

- Tiempo de respuesta de páginas
- Errores de base de datos
- Intentos de login fallidos
- Uso de memoria y CPU

## Troubleshooting

### Problemas Comunes

1. **Error 500 - Internal Server Error**
   - Verificar logs de Apache
   - Comprobar permisos de archivos
   - Validar sintaxis PHP

2. **Problemas de conexión a BD**
   - Verificar credenciales en `config/database.php`
   - Comprobar que MySQL esté ejecutándose
   - Validar permisos de usuario de BD

3. **URLs no funcionan (404)**
   - Verificar que mod_rewrite esté habilitado
   - Comprobar archivo `.htaccess`
   - Validar configuración de VirtualHost

4. **Sesiones no persisten**
   - Verificar configuración de sesiones en PHP
   - Comprobar permisos del directorio de sesiones
   - Validar configuración de cookies

## Contribución

### Estándares de Código

- Seguir PSR-1 y PSR-2 para estilo de código
- Documentar funciones y clases
- Usar nombres descriptivos para variables y métodos
- Implementar manejo de errores apropiado

### Proceso de Desarrollo

1. Crear rama para nueva funcionalidad
2. Implementar cambios con tests
3. Documentar cambios
4. Crear pull request
5. Revisión de código
6. Merge a rama principal

## Licencia

Este proyecto está bajo la Licencia MIT. Ver archivo `LICENSE` para más detalles.

## Soporte

Para soporte técnico o reportar bugs:

- **Email**: soporte@smartlabs.com
- **Documentación**: Ver archivos en `/app/docs/`
- **Issues**: Usar el sistema de issues del repositorio

---

**Versión**: 2.0.0  
**Última actualización**: Diciembre 2024  
**Mantenido por**: Equipo SMARTLABS