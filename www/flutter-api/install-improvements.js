#!/usr/bin/env node
/**
 * Script de instalación de mejoras para prevenir API freezing
 * Autor: José Ángel Balbuena Palma
 * Tecnológico de Monterrey - Campus Puebla
 * Fecha: 2024
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🚀 Instalando mejoras para prevenir API freezing...');
console.log('=' .repeat(60));

// Función para ejecutar comandos
function runCommand(command, description) {
    try {
        console.log(`\n📦 ${description}...`);
        execSync(command, { stdio: 'inherit', cwd: __dirname });
        console.log(`✅ ${description} completado`);
    } catch (error) {
        console.error(`❌ Error en ${description}:`, error.message);
        process.exit(1);
    }
}

// Función para verificar si un archivo existe
function fileExists(filePath) {
    return fs.existsSync(path.join(__dirname, filePath));
}

// Función para crear directorio si no existe
function ensureDirectory(dirPath) {
    const fullPath = path.join(__dirname, dirPath);
    if (!fs.existsSync(fullPath)) {
        fs.mkdirSync(fullPath, { recursive: true });
        console.log(`📁 Directorio creado: ${dirPath}`);
    }
}

// Verificar que estamos en el directorio correcto
if (!fileExists('package.json')) {
    console.error('❌ Error: No se encontró package.json. Ejecuta este script desde el directorio flutter-api');
    process.exit(1);
}

// Crear directorios necesarios
console.log('\n📁 Creando directorios necesarios...');
ensureDirectory('logs');
ensureDirectory('src/middleware');
ensureDirectory('src/utils');
ensureDirectory('docs');

// Instalar dependencias nuevas
runCommand('npm install winston winston-daily-rotate-file', 'Instalando dependencias de logging');

// Verificar archivos críticos
console.log('\n🔍 Verificando archivos de mejoras...');
const criticalFiles = [
    'src/middleware/timeoutMiddleware.js',
    'src/utils/asyncHandler.js',
    'docs/TROUBLESHOOTING_API_FREEZE.md',
    '.env.example'
];

let missingFiles = [];
criticalFiles.forEach(file => {
    if (fileExists(file)) {
        console.log(`✅ ${file}`);
    } else {
        console.log(`❌ ${file} - FALTANTE`);
        missingFiles.push(file);
    }
});

if (missingFiles.length > 0) {
    console.log('\n⚠️ Archivos faltantes detectados. Asegúrate de que todos los archivos de mejoras estén presentes.');
}

// Verificar configuración de package.json
console.log('\n📋 Verificando configuración de package.json...');
try {
    const packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
    const requiredDeps = ['winston', 'winston-daily-rotate-file'];
    
    requiredDeps.forEach(dep => {
        if (packageJson.dependencies && packageJson.dependencies[dep]) {
            console.log(`✅ ${dep}: ${packageJson.dependencies[dep]}`);
        } else {
            console.log(`❌ ${dep}: NO ENCONTRADO`);
        }
    });
} catch (error) {
    console.error('❌ Error leyendo package.json:', error.message);
}

// Crear archivo de configuración de logging si no existe
const loggingConfigPath = 'src/config/logging.js';
if (!fileExists(loggingConfigPath)) {
    console.log('\n📝 Creando configuración de logging...');
    const loggingConfig = `/**
 * Configuración de logging con Winston
 * Autor: José Ángel Balbuena Palma
 * Fecha: 2024
 */

const winston = require('winston');
const DailyRotateFile = require('winston-daily-rotate-file');
const path = require('path');

// Configuración de transports
const transports = [
    // Console transport
    new winston.transports.Console({
        format: winston.format.combine(
            winston.format.colorize(),
            winston.format.simple()
        )
    }),
    
    // File transport con rotación diaria
    new DailyRotateFile({
        filename: path.join(__dirname, '../logs/app-%DATE%.log'),
        datePattern: 'YYYY-MM-DD',
        maxSize: process.env.LOG_FILE_MAX_SIZE || '20m',
        maxFiles: process.env.LOG_FILE_MAX_FILES || '14d',
        format: winston.format.combine(
            winston.format.timestamp(),
            winston.format.json()
        )
    }),
    
    // Error file transport
    new DailyRotateFile({
        filename: path.join(__dirname, '../logs/error-%DATE%.log'),
        datePattern: 'YYYY-MM-DD',
        level: 'error',
        maxSize: process.env.LOG_FILE_MAX_SIZE || '20m',
        maxFiles: process.env.LOG_FILE_MAX_FILES || '14d',
        format: winston.format.combine(
            winston.format.timestamp(),
            winston.format.json()
        )
    })
];

// Crear logger
const logger = winston.createLogger({
    level: process.env.LOG_LEVEL || 'info',
    format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.errors({ stack: true }),
        winston.format.json()
    ),
    transports: transports,
    exitOnError: false
});

module.exports = logger;
`;
    
    try {
        ensureDirectory('src/config');
        fs.writeFileSync(loggingConfigPath, loggingConfig);
        console.log(`✅ Configuración de logging creada: ${loggingConfigPath}`);
    } catch (error) {
        console.error(`❌ Error creando configuración de logging:`, error.message);
    }
}

// Mostrar resumen final
console.log('\n' + '='.repeat(60));
console.log('🎉 INSTALACIÓN DE MEJORAS COMPLETADA');
console.log('='.repeat(60));
console.log('\n📋 PRÓXIMOS PASOS:');
console.log('1. Copia .env.example a .env y configura las variables');
console.log('2. Reinicia el contenedor Docker: docker-compose restart smartlabs-flutter-api');
console.log('3. Monitorea los logs: docker-compose logs -f smartlabs-flutter-api');
console.log('4. Verifica el health check: curl http://localhost:3000/health');
console.log('\n📚 DOCUMENTACIÓN:');
console.log('- Revisa docs/TROUBLESHOOTING_API_FREEZE.md para más detalles');
console.log('- Monitorea el uso de memoria y CPU del contenedor');
console.log('- Configura alertas basadas en el health check endpoint');
console.log('\n🔧 COMANDOS ÚTILES:');
console.log('- Reiniciar API: docker-compose restart smartlabs-flutter-api');
console.log('- Ver logs: docker-compose logs -f smartlabs-flutter-api');
console.log('- Estadísticas: docker stats smartlabs-flutter-api');
console.log('- Health check: curl http://localhost:3000/health');
console.log('\n✅ ¡Las mejoras están listas para prevenir el freezing de la API!');