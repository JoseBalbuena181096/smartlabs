# 🔧 CONFIGURACIÓN DEL SERVIDOR WEB PARA MVC

## ⚠️ PROBLEMA ACTUAL
El servidor web está sirviendo desde la raíz del proyecto en lugar de la carpeta `public/`

## 🎯 SOLUCIÓN: Configurar DocumentRoot

### **XAMPP/WAMP/Apache**

#### **Opción A: Cambiar DocumentRoot Global**
1. Abrir archivo de configuración Apache:
   - **XAMPP**: `C:\xampp\apache\conf\httpd.conf`
   - **WAMP**: `C:\wamp64\bin\apache\apache2.x.x\conf\httpd.conf`

2. Buscar línea `DocumentRoot` y cambiar:
```apache
# ANTES:
DocumentRoot "C:/xampp/htdocs"

# DESPUÉS:
DocumentRoot "C:/Users/josetec/Desktop/smartlabs/www/public"
```

3. Buscar `<Directory` y cambiar:
```apache
# ANTES:
<Directory "C:/xampp/htdocs">

# DESPUÉS:
<Directory "C:/Users/josetec/Desktop/smartlabs/www/public">
```

4. Asegurar que `mod_rewrite` esté habilitado:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

5. Reiniciar Apache

#### **Opción B: VirtualHost (Recomendado)**
1. Abrir archivo `httpd-vhosts.conf`:
   - **XAMPP**: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

2. Agregar VirtualHost:
```apache
<VirtualHost *:80>
    DocumentRoot "C:/Users/josetec/Desktop/smartlabs/www/public"
    ServerName smartlabs.local
    ServerAlias www.smartlabs.local
    
    <Directory "C:/Users/josetec/Desktop/smartlabs/www/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/smartlabs_error.log"
    CustomLog "logs/smartlabs_access.log" common
</VirtualHost>
```

3. Editar archivo hosts:
   - Abrir `C:\Windows\System32\drivers\etc\hosts` como administrador
   - Agregar línea:
```
127.0.0.1    smartlabs.local
```

4. Reiniciar Apache
5. Acceder via: `http://smartlabs.local/`

### **Nginx**
```nginx
server {
    listen 80;
    server_name 192.168.0.100;
    root C:/Users/josetec/Desktop/smartlabs/www/public;
    index index.php index.html;

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

## 🌐 URLs DESPUÉS DE CONFIGURAR

Una vez configurado correctamente:

- **Dashboard**: `http://192.168.0.100/Dashboard`
- **Login**: `http://192.168.0.100/Auth/login` 
- **Dispositivos**: `http://192.168.0.100/Device`
- **Préstamos**: `http://192.168.0.100/Loan`

## ✅ VERIFICACIÓN

1. Verificar que `.htaccess` funciona
2. Probar URL: `http://192.168.0.100/Auth/login`
3. Si funciona, el MVC está operativo 