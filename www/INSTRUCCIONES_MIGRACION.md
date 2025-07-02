# 🚀 Instrucciones Finales de Migración

## ✅ Estado Actual
Se ha creado completamente la nueva estructura MVC, pero los archivos PHP antiguos aún están en la raíz del proyecto. 

## 📁 Paso Final: Mover Archivos Legacy

### **Opción 1: Script Automático Windows (Recomendado)**

#### **Método A - Batch (.bat)**
```bash
# Hacer doble clic en el archivo:
mover_legacy.bat

# O ejecutar desde CMD:
mover_legacy.bat
```

#### **Método B - PowerShell (.ps1)** 
```powershell
# Ejecutar desde PowerShell:
.\mover_legacy.ps1

# Si hay error de políticas de ejecución:
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
.\mover_legacy.ps1
```

### **Opción 2: Comandos Manuales**

Si prefieres hacerlo manualmente, ejecuta estos comandos en PowerShell:

```powershell
# Mover archivos PHP antiguos
Move-Item quitar_prestamo.php legacy\
Move-Item email.php legacy\
Move-Item dash_loan.php legacy\
Move-Item becarios.php legacy\
Move-Item horas_uso.php legacy\
Move-Item register_equipment_lab.php legacy\
Move-Item register_lab.php legacy\
Move-Item dashboard.php legacy\
Move-Item delete_user.php legacy\
Move-Item devices.php legacy\
Move-Item dashboard_users.php legacy\
Move-Item register.php legacy\
Move-Item login_users.php legacy\
Move-Item login.php legacy\
Move-Item index.html legacy\
Move-Item audio.mp3 legacy\
Move-Item index.php legacy\
Move-Item .htaccess legacy\

# Mover archivos del sistema (si existen)
Move-Item .DS_Store legacy\ -ErrorAction SilentlyContinue
Move-Item .ftpconfig legacy\ -ErrorAction SilentlyContinue
```

## 📂 Estructura Final Esperada

Después de ejecutar el script, tu estructura debe verse así:

```
smartlabs/www/
├── 📁 legacy/                 ← ARCHIVOS PHP ANTIGUOS
│   ├── quitar_prestamo.php
│   ├── email.php
│   ├── dash_loan.php
│   ├── becarios.php
│   ├── horas_uso.php
│   ├── register_equipment_lab.php
│   ├── register_lab.php
│   ├── dashboard.php
│   ├── delete_user.php
│   ├── devices.php
│   ├── dashboard_users.php
│   ├── register.php
│   ├── login_users.php
│   ├── login.php
│   ├── index.html
│   ├── audio.mp3
│   ├── index.php
│   ├── .htaccess
│   └── README.md
├── 📁 app/                    ← NUEVA ESTRUCTURA MVC
│   ├── controllers/
│   ├── models/
│   ├── views/
│   └── core/
├── 📁 config/                 ← CONFIGURACIONES
│   ├── database.php
│   └── app.php
├── 📁 public/                 ← PUNTO DE ENTRADA
│   ├── index.php             ← NUEVO FRONT CONTROLLER
│   └── .htaccess             ← ROUTING
├── 📁 node/                   ← SIN CAMBIOS
├── 📁 hadware/                ← SIN CAMBIOS
├── 📁 database/               ← SIN CAMBIOS
├── 📄 MIGRACION_MVC.md
├── 📄 INSTRUCCIONES_MIGRACION.md
├── 🔧 mover_legacy.bat
└── 🔧 mover_legacy.ps1
```

## 🌐 Configuración del Servidor Web

### **Apache/XAMPP/WAMP**
```apache
# Cambiar DocumentRoot a:
DocumentRoot "C:/ruta/a/smartlabs/www/public"

# O crear un VirtualHost:
<VirtualHost *:80>
    DocumentRoot "C:/ruta/a/smartlabs/www/public"
    ServerName smartlabs.local
    <Directory "C:/ruta/a/smartlabs/www/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### **Nginx**
```nginx
server {
    listen 80;
    server_name smartlabs.local;
    root /ruta/a/smartlabs/www/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?url=$uri&$args;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 🔗 Nuevas URLs del Sistema

Una vez completada la migración, el sistema estará disponible en:

```
http://tu-servidor/Auth/login          ← Login
http://tu-servidor/Dashboard           ← Panel principal  
http://tu-servidor/Device              ← Gestión dispositivos
http://tu-servidor/Habitant            ← Registro usuarios
http://tu-servidor/Equipment           ← Gestión equipos
http://tu-servidor/Loan                ← Auto préstamos
http://tu-servidor/Stats               ← Estadísticas
```

## ✅ Verificación Post-Migración

1. **Acceder al login**: `http://tu-servidor/Auth/login`
2. **Probar credenciales existentes** de la base de datos
3. **Verificar dashboard**: Debe mostrar dispositivos
4. **Probar funcionalidades** principales

## 🔧 Servicios que NO Requieren Cambios

- ✅ **Node.js** (`node/index.js`) - Sigue igual
- ✅ **Base de datos** - Estructura intacta  
- ✅ **EMQX Broker** - Configuración igual
- ✅ **ESP32/Hardware** - Código sin cambios
- ✅ **Docker** (si se usa) - Contenedores iguales

## 🚨 En Caso de Problemas

1. **Error 404**: Verificar que `.htaccess` esté en `public/`
2. **Error 500**: Revisar permisos de archivos/carpetas
3. **Error DB**: Verificar `config/database.php`
4. **Assets no cargan**: Copiar `assets/`, `libs/`, `html/` a `public/`

## 🎯 ¡Ya Casi Listo!

Después de ejecutar el script de migración, tu sistema MVC estará completamente operativo manteniendo toda la funcionalidad IoT original. 