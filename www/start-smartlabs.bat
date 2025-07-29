@echo off
echo ========================================
echo    SMARTLABS Ecosystem Deployment
echo ========================================
echo.

:: Verificar si Docker está ejecutándose
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Docker no está ejecutándose. Por favor, inicia Docker Desktop.
    pause
    exit /b 1
)

:: Cambiar al directorio del proyecto
cd /d "%~dp0"

echo Iniciando servicios de SMARTLABS...
echo.

:: Construir y levantar todos los servicios
echo [1/4] Construyendo imágenes Docker...
docker-compose build --no-cache
if %errorlevel% neq 0 (
    echo ERROR: Falló la construcción de las imágenes.
    pause
    exit /b 1
)

echo.
echo [2/4] Iniciando servicios base (MariaDB y EMQX)...
docker-compose up -d mariadb emqx
if %ERRORLEVEL% neq 0 (
    echo ERROR: Falló el inicio de servicios base.
    pause
    exit /b 1
)

echo Esperando a que MariaDB cargue el backup completo (60 segundos)...
echo NOTA: El primer inicio puede tardar más debido a la carga del backup de la base de datos.
timeout /t 60 /nobreak > nul

echo Verificando que MariaDB esté funcionando...
docker exec smartlabs-mariadb mysqladmin ping -h localhost
if %ERRORLEVEL% neq 0 (
    echo Esperando 30 segundos adicionales para MariaDB...
    timeout /t 30 /nobreak > nul
)

echo.
echo [4/4] Iniciando servicios de aplicación...
docker-compose up -d
if %errorlevel% neq 0 (
    echo ERROR: Falló el inicio de servicios de aplicación.
    pause
    exit /b 1
)

echo.
echo ========================================
echo    SMARTLABS iniciado exitosamente!
echo ========================================
echo.
echo Servicios disponibles:
echo   - Aplicación Web:     http://localhost:8080
echo   - API Flutter:        http://localhost:8080/api
echo   - Monitor WebSocket:  ws://localhost:8080/ws
echo   - phpMyAdmin:         http://localhost:4001
echo   - EMQX Dashboard:     http://localhost:18083
echo.
echo Para ver logs: docker-compose logs -f [servicio]
echo Para detener:  docker-compose down
echo.
echo Presiona cualquier tecla para ver el estado de los servicios...
pause >nul

docker-compose ps
echo.
echo Presiona cualquier tecla para salir...
pause >nul