# IMPLEMENTACIÓN DE BECARIOS - SMARTLABS

## Descripción General

La vista **Becarios** ha sido implementada siguiendo **exactamente** la funcionalidad del archivo legacy `becarios.php`, adaptada al sistema MVC actual de SMARTLABS. Esta implementación mantiene toda la lógica específica para becarios, incluyendo el cálculo de horas de uso con límite de 9 horas por sesión.

## Funcionalidades Implementadas

### 1. **Búsqueda por Matrícula (Legacy)**
- **Campo de entrada**: Matrícula del becario (ej: A01736666)
- **Autocompletado**: Al ingresar la matrícula, se autocompleta automáticamente el nombre
- **Validación**: Conversión automática a mayúsculas
- **Endpoint**: `/becarios/buscarUsuario` (POST)

### 2. **Formulario de Consulta de Estadísticas**
- **Dispositivo**: Selector con opción "Becarios (SMART10000)"
- **Fechas**: Campos de fecha de inicio y fin
- **Matrícula**: Campo oculto que se llena automáticamente
- **Botón**: "GENERAR REPORTE"

### 3. **Tabla de Resultados (USER TRAFFIC)**
- **Columnas**: ID, Fecha, Nombre, Matrícula, Email, Dispositivo, Estado
- **Filtrado**: Solo muestra registros válidos (máximo 9 horas por sesión)
- **Actualización**: Dinámica via AJAX

### 4. **Estadísticas Específicas de Becarios**
- **Horas de uso**: Cálculo con límite de 9 horas por sesión (32,400 segundos)
- **Número de sesiones**: Contador de veces usado
- **Lógica específica**: Solo considera sesiones válidas

## Estructura de Archivos

### Controlador
```
www/app/controllers/BecariosController.php
```

**Métodos principales:**
- `index()`: Vista principal
- `buscarUsuario()`: Búsqueda AJAX por matrícula
- `consultarEstadisticas()`: Generación de estadísticas

### Vista
```
www/app/views/becarios/index.php
```

**Características:**
- Interfaz idéntica al archivo legacy
- Formularios con validación
- Tabla de resultados dinámica
- Estadísticas en tiempo real

### JavaScript
```
www/public/js/becarios-search.js
```

**Funciones principales:**
- `enviarDatos()`: Búsqueda por matrícula
- `actualizarTabla()`: Actualización de resultados
- `validarFechas()`: Validación de fechas
- `establecerFechasPorDefecto()`: Configuración automática

## Lógica Específica de Becarios

### Cálculo de Horas de Uso
```php
// Lógica específica de becarios.php
if (($time_end - $time_start <= 32400) && ($time_end > $time_start)) {
    $detal_time = $time_end - $time_start;
    $time_full += $detal_time;
    // Solo registros válidos (máximo 9 horas)
}
```

### Filtrado de Registros
- **Estado 1**: Inicio de sesión
- **Estado 0**: Fin de sesión
- **Validación**: Diferencia máxima de 9 horas (32,400 segundos)
- **Registros válidos**: Solo pares entrada-salida que cumplan el criterio

## Conexión a Base de Datos

### Base de Datos Externa (Prioritaria)
```php
$db_host = '192.168.0.100';
$db_user = 'root';
$db_pass = 'emqxpass';
$db_database = 'emqx';
$db_port = '4000';
```

### Base de Datos Local (Fallback)
- Se usa si la conexión externa falla
- Mantiene la misma estructura de datos

## Rutas Configuradas

### GET Routes
- `/becarios/` → `BecariosController@index`
- `/becarios/consultarEstadisticas` → `BecariosController@consultarEstadisticas`

### POST Routes
- `/becarios/buscarUsuario` → `BecariosController@buscarUsuario`

## Integración con el Sistema

### Sidebar
```php
// www/app/views/layout/sidebar.php
<li>
    <a href="/becarios">
        <span class="nav-icon">
            <i class="fa fa-graduation-cap"></i>
        </span>
        <span class="nav-text">Becarios</span>
    </a>
</li>
```

### Autenticación
- Verificación de sesión activa
- Redirección a login si no está autenticado

## Diferencias con el Sistema Legacy

### Mejoras Implementadas
1. **Sistema MVC**: Código organizado y mantenible
2. **Validaciones**: Mejor manejo de errores
3. **Interfaz**: Diseño moderno y responsivo
4. **AJAX**: Actualizaciones dinámicas sin recargar página
5. **Seguridad**: Sanitización de inputs

### Funcionalidad Preservada
1. **Lógica de cálculo**: Exactamente igual al legacy
2. **Búsqueda por matrícula**: Mismo comportamiento
3. **Filtrado de registros**: Misma lógica de 9 horas
4. **Conexión a BD**: Mismas credenciales y estructura

## Uso del Sistema

### 1. Acceso a Becarios
- Navegar a `/becarios` desde el sidebar
- Verificar autenticación

### 2. Búsqueda de Usuario
- Ingresar matrícula en el campo "INGRESE MATRICULA"
- El nombre se autocompleta automáticamente
- La matrícula se copia al formulario de estadísticas

### 3. Generación de Estadísticas
- Seleccionar fechas de inicio y fin
- Opcional: Filtrar por matrícula específica
- Hacer clic en "GENERAR REPORTE"
- Ver resultados en la tabla y estadísticas

### 4. Interpretación de Resultados
- **Horas de uso**: Tiempo total válido (máx. 9h/sesión)
- **Número de veces usado**: Sesiones válidas contadas
- **Tabla**: Registros de entrada y salida válidos

## Configuración y Mantenimiento

### Variables de Configuración
- Credenciales de BD externa en el controlador
- Límite de horas por sesión (32400 segundos)
- Timeout de conexiones AJAX

### Logs y Debugging
- Console logs en JavaScript para debugging
- Error handling en PHP
- Fallback automático a BD local

## Compatibilidad

### Navegadores Soportados
- Chrome, Firefox, Safari, Edge
- Responsive design para móviles

### Dependencias
- jQuery (incluido en el sistema)
- Bootstrap (incluido en el sistema)
- Font Awesome (incluido en el sistema)

## Estado de la Implementación

✅ **COMPLETADO**
- Controlador funcional
- Vista implementada
- JavaScript integrado
- Rutas configuradas
- Documentación actualizada

### Funcionalidades Verificadas
- ✅ Búsqueda por matrícula
- ✅ Autocompletado de nombre
- ✅ Generación de estadísticas
- ✅ Cálculo específico de becarios
- ✅ Tabla de resultados
- ✅ Integración con sidebar
- ✅ Validaciones de formulario
- ✅ Manejo de errores

## Notas Importantes

1. **Lógica específica**: La implementación mantiene exactamente la lógica del archivo legacy para el cálculo de horas de becarios
2. **Compatibilidad**: Funciona con la estructura de datos existente
3. **Escalabilidad**: Código organizado para futuras mejoras
4. **Mantenimiento**: Documentación completa para facilitar el mantenimiento

---

**Fecha de implementación**: Diciembre 2024  
**Versión**: 1.0  
**Estado**: ✅ Completado y funcional 