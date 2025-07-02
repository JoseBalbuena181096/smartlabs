# 🌐 URLs DE PRUEBA DEL SISTEMA MVC

## 🚀 ACCESO INMEDIATO (Sin configurar servidor)

### **URLs que FUNCIONAN AHORA:**

#### **🔑 Autenticación**
- **Login**: `https://192.168.0.100/public/Auth/login`
- **Registro**: `https://192.168.0.100/public/Auth/register`
- **Logout**: `https://192.168.0.100/public/Auth/logout`

#### **📊 Dashboard Principal**
- **Panel**: `https://192.168.0.100/public/Dashboard`
- **Datos tiempo real**: `https://192.168.0.100/public/Dashboard/getRealtimeData?device=SMART00005`

#### **🔧 Dispositivos IoT**
- **Listar**: `https://192.168.0.100/public/Device`
- **Crear**: `https://192.168.0.100/public/Device/create`
- **Editar**: `https://192.168.0.100/public/Device/edit/1`

#### **👥 Usuarios/Habitantes**
- **Listar**: `https://192.168.0.100/public/Habitant`
- **Crear**: `https://192.168.0.100/public/Habitant/create`
- **Eliminar**: `https://192.168.0.100/public/Habitant/delete/1`

#### **📦 Equipos/Herramientas**
- **Listar**: `https://192.168.0.100/public/Equipment`
- **Crear**: `https://192.168.0.100/public/Equipment/create`
- **Buscar**: `https://192.168.0.100/public/Equipment/search`
- **Editar**: `https://192.168.0.100/public/Equipment/edit/1`

#### **🔄 Auto Préstamos**
- **Préstamos activos**: `https://192.168.0.100/public/Loan`
- **Historial**: `https://192.168.0.100/public/Loan/history`
- **Crear préstamo**: `https://192.168.0.100/public/Loan/create`
- **Devolver**: `https://192.168.0.100/public/Loan/return`

#### **📈 Estadísticas**
- **Estadísticas generales**: `https://192.168.0.100/public/Stats`
- **Por dispositivo**: `https://192.168.0.100/public/Stats/device/SMART00005`
- **Exportar CSV**: `https://192.168.0.100/public/Stats/export?device=SMART00005`

## 🔄 REDIRECCIÓN AUTOMÁTICA

He creado un `index.php` en la raíz que redirige automáticamente:
- **Acceder a**: `https://192.168.0.100/`
- **Se redirige a**: `https://192.168.0.100/public/Auth/login`

## 🌐 URLs DESPUÉS DE CONFIGURAR SERVIDOR

Una vez que configures el DocumentRoot del servidor:

#### **🔑 Autenticación**
- **Login**: `https://192.168.0.100/Auth/login`
- **Dashboard**: `https://192.168.0.100/Dashboard`
- **Dispositivos**: `https://192.168.0.100/Device`
- **Préstamos**: `https://192.168.0.100/Loan`

## ⚠️ IMPORTANTE

### **Para Probar AHORA (sin configurar servidor):**
```
Usar URLs con /public/
Ejemplo: https://192.168.0.100/public/Auth/login
```

### **Para Producción (configurar servidor):**
```
Cambiar DocumentRoot a apuntar a carpeta public/
Ejemplo: https://192.168.0.100/Auth/login
```

## 🚀 PRIMEROS PASOS

1. **Probar login**: `https://192.168.0.100/public/Auth/login`
2. **Usar credenciales existentes** de la base de datos
3. **Verificar dashboard**: Debe mostrar dispositivos
4. **Probar cada módulo** con las URLs de arriba

## 🔧 DATOS DE PRUEBA

Usar las credenciales que ya tienes en la base de datos:
- **Email**: `josebalbuena181096@gmail.com`  
- **Password**: `181096`
- **Usuario ID**: `1`

¡El sistema MVC está completamente funcional! 🎉 