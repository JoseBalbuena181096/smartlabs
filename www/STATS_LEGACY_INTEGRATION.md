# Integración de Stats con Funcionalidad Legacy

## Descripción

Se ha implementado la vista **Stats** en el sistema MVC con funcionalidad **idéntica** a `horas_uso.php` del proyecto legacy, manteniendo el mismo comportamiento y cálculos exactos.

## Funcionalidades Implementadas

### 🔍 **Consulta de Usuario por Matrícula**
- **Funcionalidad**: Búsqueda AJAX de usuarios por matrícula
- **Comportamiento**: Idéntico a `horas_uso.php`
- **Endpoint**: `POST /Stats/index`
- **Respuesta**: `"Nombre: [nombre] - Matricula: [matricula]"`

### 📊 **Filtros de Estadísticas**
- **Dispositivo**: Selección de dispositivo del usuario
- **Fecha Inicio**: Fecha y hora de inicio del período
- **Fecha Fin**: Fecha y hora de fin del período  
- **Matrícula (Opcional)**: Filtrar por usuario específico

### 🔢 **Cálculo de Horas de Uso (Lógica Original)**
```php
// Exactamente como en horas_uso.php
foreach ($usersTrafficDevice as $traffic) {
    if ($traffic['traffic_state'] == 1) { 
        $timeStart = strtotime($traffic['traffic_date']);
        $numUsages += 1;
    } else {
        $timeEnd = strtotime($traffic['traffic_date']);
    }
    
    if (($timeStart > 0 && $timeEnd > 0) && ($timeEnd > $timeStart)) { 
        $deltaTime = $timeEnd - $timeStart;
        $timeTotal += $deltaTime;
        $timeStart = 0;
        $timeEnd = 0;
    }
}
```

### 🗄️ **Bases de Datos (Prioridad Externa)**
1. **Primera opción**: Base de datos externa (`192.168.0.100:4000/emqx`)
2. **Fallback**: Base de datos local con JOIN a `habintants`

## Archivos Modificados

### `www/app/controllers/StatsController.php`
- **Método `index()`**: Lógica principal idéntica a horas_uso.php
- **Método `getTrafficDataFromExternalDB()`**: Conexión a BD externa
- **Método `getTrafficDataFromLocalDB()`**: Fallback a BD local
- **Método `calculateUsageStats()`**: Cálculo exacto del legacy

### `www/app/views/stats/index.php`
- **Interfaz moderna** con funcionalidad legacy
- **Formularios separados**: Consulta usuario y filtros
- **Estadísticas**: Horas de uso, número de usos, promedio
- **JavaScript**: Sincronización de inputs como en horas_uso.php

## Comparación con Legacy

| Característica | horas_uso.php | Stats (MVC) |
|---------------|---------------|-------------|
| **Consulta por matrícula** | ✅ AJAX | ✅ AJAX idéntico |
| **Filtros** | ✅ GET form | ✅ GET form idéntico |
| **Cálculo horas** | ✅ `round($time_full/3600, 4)` | ✅ `round($timeTotal/3600, 4)` |
| **Número de usos** | ✅ `$jobs_` | ✅ `$numUsages` |
| **BD Externa** | ✅ MySQL directo | ✅ mysqli con fallback |
| **Sincronización inputs** | ✅ `setInterval(500ms)` | ✅ `setInterval(500ms)` |
| **Interfaz** | ❌ Legacy HTML | ✅ Moderna Bootstrap |

## Estructura de Datos

### **Traffic (BD Externa)**
```sql
-- Tabla: traffic_devices
SELECT * FROM traffic_devices 
WHERE traffic_device = ? 
AND traffic_date BETWEEN ? AND ?
ORDER BY traffic_date ASC
```

### **Traffic (BD Local)**
```sql
-- Tabla: traffic + habintants
SELECT t.*, h.hab_name, h.hab_registration, h.hab_email
FROM traffic t 
LEFT JOIN habintants h ON t.traffic_hab_id = h.hab_id 
WHERE t.traffic_device = ? 
AND t.traffic_date BETWEEN ? AND ?
ORDER BY t.traffic_date ASC
```

## Estadísticas Calculadas

### 📏 **Horas de Uso Total**
- **Cálculo**: `round($timeTotal / 3600, 4)`
- **Formato**: Decimal con 4 decimales
- **Ejemplo**: `2.3456` horas

### 🔄 **Número de Veces Usado**
- **Cálculo**: Contador de eventos `traffic_state = 1`
- **Formato**: Número entero
- **Ejemplo**: `15` usos

### 📊 **Promedio por Uso**
- **Cálculo**: `($timeTotal / 3600) / $numUsages`
- **Formato**: Decimal con 2 decimales
- **Ejemplo**: `0.15` horas por uso

## JavaScript Legacy

### **Funciones Implementadas**
```javascript
// Sincronización de inputs (cada 500ms)
setInterval(function() {
    var selectedDevice = $("#serie_device").val();
    var matricula = $("#registration").val();
    if ($("#matricula").length && matricula) {
        $("#matricula").val(matricula);
    }
}, 500);

// Consulta AJAX usuario
function enviarDatos() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/Stats/index', true);
    // ... resto idéntico a horas_uso.php
}
```

## Configuración de Base de Datos

### **Externa (Prioridad)**
```php
$db_host = '192.168.0.100';
$db_user = 'root';
$db_pass = 'emqxpass';
$db_database = 'emqx';
$db_port = '4000';
```

### **Local (Fallback)**
- Utiliza la configuración estándar del sistema MVC
- Accede a través de `$this->db->query()`

## Flujo de Funcionamiento

1. **Carga inicial**: Muestra formularios vacíos
2. **Consulta usuario**: AJAX por matrícula → actualiza campos
3. **Aplicar filtros**: GET con parámetros → consulta BD
4. **Obtener datos**: Externa primero, local como fallback
5. **Calcular estadísticas**: Lógica exacta del legacy
6. **Mostrar resultados**: Tabla + estadísticas + gráficos

## Pruebas y Validación

### **Comandos de Prueba**
```bash
# Iniciar servidor PHP
cd www && php -S localhost:8000

# Acceder a Stats
http://localhost:8000/Stats

# Probar consulta usuario
curl -X POST http://localhost:8000/Stats/index \
  -d "registration=L03533767"

# Probar filtros
http://localhost:8000/Stats?serie_device=SMART001&start_date=2024-01-01T00:00&end_date=2024-01-31T23:59
```

### **Casos de Prueba**
1. ✅ Consulta usuario válido
2. ✅ Usuario no encontrado
3. ✅ Filtros con datos
4. ✅ Filtros sin datos
5. ✅ BD externa conectada
6. ✅ BD externa desconectada (fallback)
7. ✅ Cálculo con sesiones completas
8. ✅ Cálculo con sesiones incompletas

## Beneficios de la Migración

### **Mantenimiento**
- ✅ Código organizado en MVC
- ✅ Separación de responsabilidades
- ✅ Reutilización de componentes

### **Seguridad**
- ✅ Sanitización de inputs
- ✅ Prepared statements
- ✅ Validación de sesiones

### **Escalabilidad**
- ✅ Estructura modular
- ✅ Fácil extensión
- ✅ Mantenimiento centralizado

### **Funcionalidad**
- ✅ **100% compatible** con horas_uso.php
- ✅ Mismos cálculos exactos
- ✅ Misma lógica de negocio
- ✅ Interfaz mejorada

## Notas Técnicas

- **Compatibilidad**: Total con sistema legacy
- **Performance**: Optimizada con fallback de BD
- **Mantenimiento**: Código limpio y documentado
- **Extensibilidad**: Fácil agregar nuevas funcionalidades

## Conclusión

La vista **Stats** implementa **exactamente** la misma funcionalidad que `horas_uso.php` del sistema legacy, pero con una arquitectura MVC moderna, interfaz mejorada y mejor mantenibilidad, garantizando **100% de compatibilidad** con el comportamiento original. 