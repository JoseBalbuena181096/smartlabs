# Sistema de Búsqueda de Usuarios Flexible en Stats

## Descripción General

Se implementó un sistema de búsqueda de usuarios flexible en la vista Stats que permite al usuario buscar y seleccionar usuarios de forma dinámica, similar al sistema implementado en LoanAdmin.

## Características Principales

### 1. Búsqueda en Tiempo Real
- **Autocompletado**: Se activa automáticamente cuando el usuario escribe 2 o más caracteres
- **Debounce**: Implementa un retardo de 500ms para evitar múltiples peticiones
- **Campos de búsqueda**: Busca por matrícula, nombre y correo electrónico

### 2. Interfaz Intuitiva
- **Tabla de resultados**: Muestra hasta 10 usuarios encontrados
- **Información completa**: Nombre, matrícula, email y tipo de coincidencia
- **Selección visual**: Click en cualquier fila para seleccionar
- **Feedback visual**: Animaciones y colores para indicar selección

### 3. Dual Database Support
- **Base de datos externa**: Prioridad a la BD externa (192.168.0.100:4000/emqx)
- **Base de datos local**: Fallback automático en caso de fallo externo
- **Compatibilidad**: Funciona con ambos esquemas de base de datos

## Implementación Técnica

### Controlador (StatsController.php)
```php
// Manejo de búsqueda AJAX
if ($_POST && isset($_POST['search_user'])) {
    $query = strip_tags($_POST['search_user']);
    if (!empty($query)) {
        $users = $this->buscarUsuarios($query);
        echo json_encode($users);
        exit();
    }
}
```

### Método de Búsqueda
```php
private function buscarUsuarios($query) {
    // Intenta primero BD externa, luego local
    // Busca en hab_registration, hab_name, hab_email
    // Limita a 10 resultados
    // Indica tipo de coincidencia
}
```

### Vista (stats/index.php)
```html
<div class="card search-card-stats">
    <div class="card-body">
        <div class="input-group">
            <input type="text" id="userSearchStats" class="form-control">
            <button class="btn btn-primary" id="searchBtnStats">
                <i class="fa fa-search"></i>
            </button>
        </div>
        <!-- Tabla de resultados -->
        <div id="searchResultsStats">
            <table class="table table-sm table-hover">
                <!-- Resultados dinámicos -->
            </table>
        </div>
    </div>
</div>
```

## Funcionalidad JavaScript

### Búsqueda Automática
```javascript
$('#userSearchStats').on('input', function() {
    clearTimeout(searchTimeoutStats);
    var query = $(this).val().trim();
    
    if (query.length >= 2) {
        searchTimeoutStats = setTimeout(function() {
            buscarUsuariosStats(query);
        }, 500);
    }
});
```

### Selección de Usuario
```javascript
$('#searchResultsBodyStats tr[data-user]').click(function() {
    var userData = JSON.parse(decodeURIComponent($(this).data('user')));
    
    // Actualizar campos
    $('#selectedUserData').val(userData.hab_registration);
    $('#userSearchStats').val(userData.hab_name + ' (' + userData.hab_registration + ')');
    
    // Feedback visual
    $(this).addClass('table-success');
    $('#searchResultsStats').fadeOut(300);
});
```

## Integración con el Sistema Existente

### Campo Oculto
```html
<input type="hidden" id="selectedUserData" name="user_search" value="">
```

### Compatibilidad
- Mantiene la funcionalidad original del filtro GET
- El usuario seleccionado se pasa al formulario principal
- Compatible con la lógica existente de estadísticas

## Tipos de Coincidencia

### Badges Visuales
- **Matrícula**: Badge azul con icono de identificación
- **Nombre**: Badge verde con icono de usuario
- **Email**: Badge amarillo con icono de correo
- **Múltiple**: Badge gris para coincidencias múltiples

### Lógica de Coincidencia
```sql
CASE 
    WHEN hab_registration LIKE ? THEN 'matricula'
    WHEN hab_name LIKE ? THEN 'nombre'
    WHEN hab_email LIKE ? THEN 'email'
    ELSE 'multiple'
END as match_type
```

## Estados del Sistema

### Estados de Búsqueda
1. **Inicial**: "Busca automáticamente mientras escribes"
2. **Buscando**: "Buscando usuarios..." con spinner
3. **Resultados**: "X usuarios encontrados"
4. **Sin resultados**: "No se encontraron usuarios"
5. **Seleccionado**: "Usuario seleccionado: [nombre]"
6. **Error**: "Error de conexión"

### Estados Visuales
- **Hover**: Transformación y sombreado
- **Seleccionado**: Fondo verde con check
- **Cargando**: Spinner animado
- **Error**: Texto rojo con iconos de advertencia

## Ventajas del Sistema

### Para el Usuario
- **Intuitivo**: Búsqueda automática sin clicks adicionales
- **Rápido**: Resultados inmediatos con debounce
- **Flexible**: Busca en múltiples campos simultáneamente
- **Visual**: Feedback claro de selección y estados

### Para el Sistema
- **Robusto**: Manejo de errores y fallback automático
- **Escalable**: Límite de 10 resultados para performance
- **Compatible**: Mantiene funcionalidad existente
- **Seguro**: Sanitización de inputs y validación

## Archivos Modificados

### Controlador
- `www/app/controllers/StatsController.php`
  - Agregado método `buscarUsuarios()`
  - Manejo de peticiones AJAX en `index()`

### Vista
- `www/app/views/stats/index.php`
  - Interfaz de búsqueda con tabla de resultados
  - JavaScript para búsqueda en tiempo real
  - Estilos CSS para animaciones y estados

### Documentación
- `www/STATS_USER_SEARCH.md` (este archivo)

## Pruebas Recomendadas

1. **Búsqueda por matrícula**: Escribir códigos de estudiante
2. **Búsqueda por nombre**: Escribir nombres parciales
3. **Búsqueda por email**: Escribir dominios o nombres
4. **Selección de usuarios**: Click en diferentes filas
5. **Casos extremos**: Strings vacíos, caracteres especiales
6. **Conectividad**: Probar con BD externa desconectada

## Mantenimiento

### Logs
- Errores de conexión se registran en error_log
- Búsquedas fallidas se manejan con try-catch

### Performance
- Debounce de 500ms para evitar spam
- Límite de 10 resultados por consulta
- Timeout automático para peticiones

### Seguridad
- `strip_tags()` en inputs
- Consultas preparadas (PDO)
- Validación de datos JSON 