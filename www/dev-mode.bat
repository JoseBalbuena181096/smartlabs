@echo off
echo ========================================
echo    SMARTLABS Development Mode
echo ========================================
echo.
echo Selecciona el modo de desarrollo:
echo.
echo 1. Desarrollo Híbrido (Solo DB + MQTT en Docker)
echo 2. Desarrollo Completo (Todo en Docker)
echo 3. Detener todos los servicios
echo 4. Ver estado de servicios
echo.
set /p choice=Ingresa tu opción (1-4): 

if "%choice%"=="1" goto hybrid
if "%choice%"=="2" goto full
if "%choice%"=="3" goto stop
if "%choice%"=="4" goto status
echo Opción inválida.
goto end

:hybrid
echo.
echo ========================================
echo    Modo Desarrollo Híbrido
echo ========================================
echo.
echo Iniciando servicios base (MariaDB + EMQX + phpMyAdmin)...
echo Las aplicaciones se ejecutarán en Laragon.
echo.

:: Detener servicios completos si están corriendo
docker-compose down 2>nul

:: Iniciar servicios de desarrollo
docker-compose -f docker-dev.yml up -d
if %errorlevel% neq 0 (
    echo ERROR: Falló el inicio de servicios de desarrollo.
    pause
    exit /b 1
)

echo.
echo ✅ Servicios base iniciados exitosamente!
echo.
echo Servicios disponibles:
echo   - MariaDB:            localhost:4000
echo   - phpMyAdmin:         http://localhost:4001
echo   - EMQX Dashboard:     http://localhost:18083
echo   - EMQX MQTT:          localhost:1883
echo.
echo 📝 Para desarrollo local:
echo   1. Inicia Laragon
echo   2. Configura la aplicación PHP para usar localhost:4000
echo   3. Inicia el servidor Node.js manualmente
echo   4. Inicia la API Flutter manualmente
echo.
echo Configuración de base de datos:
echo   Host: localhost
echo   Puerto: 4000
echo   Usuario: smartlabs_user
echo   Contraseña: smartlabs_secure_2024
echo   Base de datos: smartlabs_db
echo.
goto end

:full
echo.
echo ========================================
echo    Modo Desarrollo Completo
echo ========================================
echo.
echo Iniciando ecosistema completo en Docker...
echo.

:: Detener servicios de desarrollo si están corriendo
docker-compose -f docker-dev.yml down 2>nul

:: Iniciar servicios completos
call start-smartlabs.bat
goto end

:stop
echo.
echo ========================================
echo    Deteniendo Servicios
echo ========================================
echo.
echo Deteniendo todos los servicios de desarrollo...

:: Detener ambos tipos de servicios
docker-compose down 2>nul
docker-compose -f docker-dev.yml down 2>nul

echo.
echo ✅ Todos los servicios han sido detenidos.
goto end

:status
echo.
echo ========================================
echo    Estado de Servicios
echo ========================================
echo.
echo Servicios de desarrollo híbrido:
docker-compose -f docker-dev.yml ps 2>nul
echo.
echo Servicios completos:
docker-compose ps 2>nul
echo.
echo Contenedores activos:
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
goto end

:end
echo.
echo Presiona cualquier tecla para salir...
pause >nul