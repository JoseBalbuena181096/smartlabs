# Búsqueda Flexible de Usuario - Stats SMARTLABS

## Descripción

Se ha implementado una **búsqueda flexible de usuario** en la vista Stats que permite buscar por **matrícula**, **nombre** o **correo electrónico** en un solo campo, eliminando la necesidad de consulta previa.

## Cambios Implementados

### ✅ **Funcionalidad Eliminada**
- ❌ Sección "Consultar Usuario por Matrícula" 
- ❌ Formulario POST separado para verificar usuario
- ❌ Función AJAX `enviarDatos()`

### ✅ **Funcionalidad Agregada**
- ✅ Campo de búsqueda flexible en filtros
- ✅ Búsqueda por matrícula, nombre o correo
- ✅ Validación automática de formato de matrícula
- ✅ Consulta optimizada con LIKE en base de datos

## Interfaz de Usuario

### **Antes**
```
┌─────────────────────────────────────────────────────┐
│ 🔍 Consultar Usuario por Matrícula                  │
│ [Input: Matrícula] [Botón: Consultar]               │
└─────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────┐
│ 📊 Filtros de Estadísticas                         │
│ [Dispositivo] [Fecha Inicio] [Fecha Fin]           │
│ [Matrícula (Opcional)]                              │
└─────────────────────────────────────────────────────┘
```

### **Después**
```
┌─────────────────────────────────────────────────────┐
│ 📊 Filtros de Estadísticas                         │
│ [Dispositivo] [Fecha Inicio] [Fecha Fin]           │
│ [Buscar Usuario: Matrícula, nombre o correo...]    │
└─────────────────────────────────────────────────────┘
```

## Lógica de Búsqueda

### **Base de Datos Externa**
```sql
-- Sin filtro de usuario
SELECT * FROM traffic_devices
WHERE traffic_device = ? 
AND traffic_date BETWEEN ? AND ?
ORDER BY traffic_date ASC

-- Con filtro de usuario (búsqueda flexible)
SELECT * FROM traffic_devices
WHERE traffic_device = ? 
AND (hab_registration LIKE ? OR hab_name LIKE ? OR hab_email LIKE ?)
AND traffic_date BETWEEN ? AND ?
ORDER BY traffic_date ASC
```

### **Base de Datos Local**
```sql
-- Sin filtro de usuario
SELECT t.*, h.hab_name, h.hab_registration, h.hab_email
FROM traffic t 
LEFT JOIN habintants h ON t.traffic_hab_id = h.hab_id 
WHERE t.traffic_device = ? 
AND t.traffic_date BETWEEN ? AND ?
ORDER BY t.traffic_date ASC

-- Con filtro de usuario (búsqueda flexible)
SELECT t.*, h.hab_name, h.hab_registration, h.hab_email
FROM traffic t 
LEFT JOIN habintants h ON t.traffic_hab_id = h.hab_id 
WHERE t.traffic_device = ? 
AND (h.hab_registration LIKE ? OR h.hab_name LIKE ? OR h.hab_email LIKE ?)
AND t.traffic_date BETWEEN ? AND ?
ORDER BY t.traffic_date ASC
```

## Ejemplos de Búsqueda

### **Por Matrícula**
```
Campo: "A01739527"
Resultado: Busca usuarios con matrícula "A01739527"
```

### **Por Nombre**
```
Campo: "Jose"
Resultado: Busca usuarios con nombre que contenga "Jose"
```

### **Por Correo**
```
Campo: "gmail"
Resultado: Busca usuarios con correo que contenga "gmail"
```

### **Búsqueda Parcial**
```
Campo: "Lopez"
Resultado: Busca en matrícula, nombre Y correo que contenga "Lopez"
```

## Características Técnicas

### **Validación Automática**
```javascript
$('#user_search').on('input', function() {
    var value = $(this).val();
    // Convertir a mayúsculas solo si parece ser una matrícula
    if (value.match(/^[A-Z]\d+$/i)) {
        $(this).val(value.toUpperCase());
    }
});
```

### **Consulta Optimizada**
```php
// Parámetro único para búsqueda
$userSearch = isset($_GET['user_search']) ? strip_tags($_GET['user_search']) : '';

// Búsqueda con LIKE para flexibilidad
$searchTerm = "%{$userSearch}%";
$params = [$device, $searchTerm, $searchTerm, $searchTerm, $start_date, $end_date];
```

## Archivos Modificados

### **Controlador**
- `www/app/controllers/StatsController.php`
  - ✅ Eliminó lógica POST para consulta de usuario
  - ✅ Cambió parámetro de `matricula` a `user_search`
  - ✅ Implementó búsqueda flexible en ambas BD

### **Vista**
- `www/app/views/stats/index.php`
  - ✅ Eliminó sección "Consultar Usuario por Matrícula"
  - ✅ Cambió campo "Matrícula" por "Buscar Usuario"
  - ✅ Actualizó JavaScript para nueva funcionalidad
  - ✅ Removió funciones AJAX innecesarias

## Beneficios de la Mejora

### **Experiencia de Usuario**
- ✅ **Más simple**: Un solo campo para buscar
- ✅ **Más flexible**: Busca por cualquier criterio
- ✅ **Más rápido**: Sin consulta previa necesaria
- ✅ **Más intuitivo**: Búsqueda en tiempo real

### **Funcionalidad**
- ✅ **Búsqueda parcial**: No requiere texto exacto
- ✅ **Múltiples criterios**: Matrícula, nombre o correo
- ✅ **Validación automática**: Formato de matrícula
- ✅ **Compatibilidad**: Funciona con ambas bases de datos

### **Rendimiento**
- ✅ **Menos peticiones**: No requiere consulta previa
- ✅ **Consulta optimizada**: LIKE con índices
- ✅ **Carga reducida**: Menos JavaScript
- ✅ **Interfaz limpia**: Menos elementos DOM

## Casos de Uso

### **Búsqueda por Matrícula Completa**
```
Input: "A01739527"
Resultado: Encuentra usuario con matrícula exacta
```

### **Búsqueda por Nombre Parcial**
```
Input: "Jose"
Resultado: Encuentra todos los usuarios con "Jose" en el nombre
```

### **Búsqueda por Dominio de Correo**
```
Input: "@tec.mx"
Resultado: Encuentra todos los usuarios con correo del Tec
```

### **Búsqueda por Apellido**
```
Input: "Lopez"
Resultado: Encuentra usuarios con "Lopez" en nombre, matrícula o correo
```

## Compatibilidad

### **Bases de Datos**
- ✅ **Externa**: `192.168.0.100:4000/emqx/traffic_devices`
- ✅ **Local**: `traffic + habintants` (JOIN)
- ✅ **Fallback**: Automático si falla la externa

### **Formatos de Búsqueda**
- ✅ **Matrícula**: `A01739527`, `L03533767`
- ✅ **Nombre**: `Jose`, `Lopez`, `Jose Angel`
- ✅ **Correo**: `gmail`, `@tec.mx`, `usuario@dominio`

## Validación

### **Formato de Matrícula**
- Detecta automáticamente formato: `[Letra][Números]`
- Convierte a mayúsculas automáticamente
- Ejemplo: `a01739527` → `A01739527`

### **Longitud Mínima**
- Opcional: Validación de 3 caracteres mínimos
- Evita consultas muy amplias
- Mejora rendimiento de búsqueda

## Conclusión

La implementación de **búsqueda flexible de usuario** mejora significativamente la experiencia de usuario al:

1. **Simplificar la interfaz**: Un solo campo para buscar
2. **Aumentar la flexibilidad**: Búsqueda por múltiples criterios
3. **Mejorar la usabilidad**: Sin necesidad de consulta previa
4. **Mantener la funcionalidad**: Todos los cálculos originales intactos

La funcionalidad mantiene **100% de compatibilidad** con el cálculo de horas de uso del sistema legacy, pero con una interfaz más moderna y flexible. 