@echo off
echo ========================================
echo    SMARTLABS Database Restore
echo ========================================
echo.
echo Este script restaurará la base de datos con el backup completo.
echo ADVERTENCIA: Esto eliminará todos los datos existentes.
echo.
set /p confirm=¿Continuar con la restauración? (s/N): 

if /i not "%confirm%"=="s" (
    echo Operación cancelada.
    pause
    exit /b 0
)

echo.
echo [1/4] Verificando que Docker esté ejecutándose...
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Docker no está ejecutándose. Por favor, inicia Docker Desktop.
    pause
    exit /b 1
)

echo.
echo [2/4] Deteniendo servicios si están ejecutándose...
docker-compose down 2>nul

echo.
echo [3/4] Eliminando volúmenes de base de datos existentes...
docker volume rm smartlabs_mariadb-data 2>nul
docker volume rm smartlabs-mariadb-data 2>nul

echo.
echo [4/4] Iniciando servicios con backup completo...
docker-compose up -d mariadb
if %errorlevel% neq 0 (
    echo ERROR: Falló el inicio de MariaDB.
    pause
    exit /b 1
)

echo.
echo Esperando que MariaDB esté listo...
timeout /t 45 /nobreak >nul

echo.
echo Verificando estado de la base de datos...
docker-compose exec mariadb mysql -u%MARIADB_USER% -p%MARIADB_PASSWORD% -e "SHOW DATABASES;"
if %errorlevel% neq 0 (
    echo ADVERTENCIA: No se pudo verificar la base de datos. Puede necesitar más tiempo.
)

echo.
echo ========================================
echo    Restauración Completada
echo ========================================
echo.
echo La base de datos ha sido restaurada con el backup completo.
echo Puedes iniciar el resto de servicios con:
echo   docker-compose up -d
echo.
echo Para verificar los datos:
echo   - phpMyAdmin: http://localhost:4001
echo   - Credenciales: %MARIADB_USER% / %MARIADB_PASSWORD%
echo.
echo Presiona cualquier tecla para salir...
pause >nul