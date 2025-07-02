# Migración a Arquitectura MVC - SMARTLABS

## Resumen de la Reorganización

Se ha reorganizado completamente el proyecto de auto préstamo de máquinas/herramientas para implementar el patrón **Modelo-Vista-Controlador (MVC)** manteniendo toda la funcionalidad existente.

## Nueva Estructura del Proyecto

```
proyecto/
├── app/
│   ├── controllers/          # Controladores (lógica de negocio)
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── DeviceController.php
│   │   ├── HabitantController.php
│   │   ├── LoanController.php
│   │   ├── EquipmentController.php
│   │   └── StatsController.php
│   ├── models/              # Modelos (acceso a datos)
│   │   ├── User.php
│   │   ├── Device.php
│   │   ├── Habitant.php
│   │   ├── Card.php
│   │   ├── Traffic.php
│   │   ├── Loan.php
│   │   └── Equipment.php
│   ├── views/               # Vistas (presentación)
│   │   ├── layout/
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── device/
│   │   ├── habitant/
│   │   ├── loan/
│   │   ├── equipment/
│   │   └── stats/
│   └── core/                # Núcleo del sistema
│       ├── Database.php
│       ├── Controller.php
│       └── Router.php
├── config/                  # Configuraciones
│   ├── database.php
│   └── app.php
├── public/                  # Punto de entrada público
│   ├── index.php
│   └── .htaccess
├── node/                    # Código Node.js (sin cambios)
├── hadware/                 # Código ESP32 (sin cambios)
├── database/                # Base de datos (sin cambios)
└── assets/                  # Recursos estáticos (CSS, JS, imágenes)
```

## Mapeo de Archivos Antiguos vs Nuevos

### Archivos PHP Antiguos → Nuevos Controladores/Modelos

| Archivo Antiguo | Nuevo Controlador | Modelo Relacionado |
|----------------|-------------------|-------------------|
| `login.php` | `AuthController::login()` | `User` |
| `register.php` | `AuthController::register()` | `User` |
| `dashboard.php` | `DashboardController::index()` | `Device`, `Traffic` |
| `devices.php` | `DeviceController::index()` | `Device` |
| `register_lab.php` | `HabitantController::create()` | `Habitant`, `Card` |
| `register_equipment_lab.php` | `EquipmentController::create()` | `Equipment` |
| `dash_loan.php` | `LoanController::index()` | `Loan` |
| `quitar_prestamo.php` | `LoanController::return()` | `Loan` |
| `horas_uso.php` | `StatsController::index()` | `Traffic` |
| `dashboard_users.php` | `HabitantController::index()` | `Habitant` |
| `delete_user.php` | `HabitantController::delete()` | `Habitant` |
| `becarios.php` | `StatsController::device()` | `Traffic` |

## Ventajas de la Nueva Arquitectura

### 1. **Separación de Responsabilidades**
- **Modelos**: Solo se encargan del acceso y manipulación de datos
- **Vistas**: Solo se encargan de la presentación
- **Controladores**: Solo se encargan de la lógica de negocio

### 2. **Reutilización de Código**
- Conexión a base de datos centralizada en `Database.php`
- Funciones comunes en la clase base `Controller.php`
- Validaciones y sanitización unificadas

### 3. **Mantenibilidad**
- Código más organizado y fácil de mantener
- Menos duplicación de código
- Fácil localización de errores

### 4. **Escalabilidad**
- Fácil agregar nuevas funcionalidades
- Estructura preparada para crecimiento
- Facilita el trabajo en equipo

### 5. **Seguridad Mejorada**
- Sanitización automática de datos
- Consultas preparadas (prepared statements)
- Validación centralizada

## Nuevas URLs del Sistema

### Autenticación
- `GET /Auth/login` - Página de login
- `POST /Auth/login` - Procesar login
- `GET /Auth/register` - Página de registro
- `POST /Auth/register` - Procesar registro
- `GET /Auth/logout` - Cerrar sesión

### Dashboard
- `GET /Dashboard` - Panel principal
- `GET /Dashboard/getRealtimeData` - Datos en tiempo real (AJAX)

### Dispositivos
- `GET /Device` - Listar dispositivos
- `GET /Device/create` - Formulario crear dispositivo
- `POST /Device/create` - Procesar creación
- `GET /Device/edit/{id}` - Formulario editar
- `POST /Device/edit/{id}` - Procesar edición
- `GET /Device/delete/{id}` - Eliminar dispositivo

### Habitantes/Usuarios
- `GET /Habitant` - Listar habitantes
- `GET /Habitant/create` - Formulario crear habitante
- `POST /Habitant/create` - Procesar creación
- `GET /Habitant/delete/{id}` - Eliminar habitante

### Equipos
- `GET /Equipment` - Listar equipos
- `GET /Equipment/create` - Formulario crear equipo
- `POST /Equipment/create` - Procesar creación
- `GET /Equipment/edit/{id}` - Formulario editar
- `GET /Equipment/search` - Buscar equipos

### Préstamos
- `GET /Loan` - Préstamos activos
- `GET /Loan/history` - Historial de préstamos
- `GET /Loan/create` - Formulario crear préstamo
- `GET /Loan/return` - Devolver equipo

### Estadísticas
- `GET /Stats` - Estadísticas generales
- `GET /Stats/device/{serial}` - Estadísticas por dispositivo
- `GET /Stats/export` - Exportar estadísticas CSV

## Configuración

### Base de Datos
Editar `config/database.php`:
```php
return [
    'host' => '192.168.0.100',
    'username' => 'root',
    'password' => 'emqxpass',
    'database' => 'emqx',
    'port' => '4000',
    'charset' => 'utf8mb4'
];
```

### Aplicación
Editar `config/app.php`:
```php
return [
    'app_name' => 'SMARTLABS',
    'app_url' => 'http://localhost',
    'default_controller' => 'Dashboard',
    'default_action' => 'index',
    'assets_path' => '/assets/',
    'session_timeout' => 3600,
];
```

## Compatibilidad

### Sistema Node.js
- **Sin cambios**: El código en `node/index.js` sigue funcionando igual
- Mantiene la comunicación MQTT con broker EMQX
- Sigue manejando los dispositivos ESP32

### Base de Datos
- **Sin cambios**: La base de datos mantiene su estructura
- Todas las tablas y vistas siguen iguales
- Los datos existentes se conservan

### Dispositivos ESP32
- **Sin cambios**: El código en `hadware/` sigue igual
- Los dispositivos siguen comunicándose por MQTT
- No requiere reprogramación

## Migración Paso a Paso

### 1. Backup
```bash
# Hacer backup del proyecto actual
cp -r proyecto_actual proyecto_backup
```

### 2. Configurar Servidor Web
- Apuntar DocumentRoot a `/ruta/proyecto/public/`
- Activar mod_rewrite en Apache
- Configurar permisos de lectura/escritura

### 3. Configurar Base de Datos
- Verificar que la base de datos funcione
- Actualizar credenciales en `config/database.php`

### 4. Probar Funcionalidad
- Acceder a `http://tu-servidor/Auth/login`
- Verificar login con usuarios existentes
- Probar funcionalidades principales

### 5. Migrar Assets
```bash
# Copiar assets del proyecto anterior
cp -r proyecto_anterior/assets public/
cp -r proyecto_anterior/libs public/
cp -r proyecto_anterior/html public/
```

## Mantenimiento de Archivos Antiguos

Los archivos PHP antiguos pueden mantenerse temporalmente para referencia, pero deben moverse a una carpeta `legacy/` para evitar conflictos:

```bash
mkdir legacy
mv *.php legacy/
# Excepto public/index.php
```

## Próximos Pasos Recomendados

1. **API REST**: Implementar endpoints JSON para integración
2. **Autenticación JWT**: Para mejor seguridad en API
3. **Logs**: Sistema de logging estructurado
4. **Cache**: Implementar cache para mejor rendimiento
5. **Tests**: Agregar pruebas unitarias y de integración
6. **Docker**: Containerizar la aplicación completa

## Soporte

Para dudas sobre la migración o funcionamiento del nuevo sistema, consultar este documento o revisar el código en los controladores y modelos correspondientes. 