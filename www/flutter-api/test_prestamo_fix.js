/**
 * Script de prueba para verificar que el endpoint /prestamos/control
 * con action:1 ya no cierra automáticamente la sesión
 */

const axios = require('axios');

// Configuración de la API
const API_BASE_URL = 'http://localhost:3000/api';

/**
 * Función para probar el endpoint /prestamos/control
 */
async function testPrestamoControl() {
    console.log('🧪 Iniciando prueba del endpoint /prestamos/control...');
    
    const testData = {
        registration: 'L03533767', // Matrícula de prueba
        device_serie: 'SMART10003', // Serie del dispositivo de prueba
        action: 1 // Acción de login
    };
    
    try {
        console.log('\n📤 Enviando solicitud con action: 1 (login)...');
        console.log('Datos:', JSON.stringify(testData, null, 2));
        
        // Realizar solicitud POST al endpoint
        const response = await axios.post(`${API_BASE_URL}/prestamo/control/`, testData);
        
        console.log('\n✅ Respuesta recibida:');
        console.log('Status:', response.status);
        console.log('Data:', JSON.stringify(response.data, null, 2));
        
        // Verificar que la sesión se mantiene activa
        if (response.data.success && response.data.data.state === 'active') {
            console.log('\n🎉 ¡ÉXITO! La sesión se mantiene activa después del login');
            
            // Esperar un momento y verificar el estado
            console.log('\n⏳ Esperando 2 segundos para verificar estado...');
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            // Verificar estado actual
            const estadoResponse = await axios.get(`${API_BASE_URL}/prestamo/estado/`);
            console.log('\n📊 Estado actual de la sesión:');
            console.log(JSON.stringify(estadoResponse.data, null, 2));
            
            if (estadoResponse.data.success && estadoResponse.data.data.active) {
                console.log('\n✅ CONFIRMADO: La sesión sigue activa después de 2 segundos');
                
                // Probar cerrar sesión
                console.log('\n📤 Probando cerrar sesión con action: 0...');
                const logoutData = { ...testData, action: 0 };
                const logoutResponse = await axios.post(`${API_BASE_URL}/prestamo/control/`, logoutData);
                
                console.log('\n📊 Respuesta del logout:');
                console.log(JSON.stringify(logoutResponse.data, null, 2));
                
                if (logoutResponse.data.success) {
                    console.log('\n✅ LOGOUT EXITOSO: La sesión se cerró correctamente');
                } else {
                    console.log('\n❌ ERROR: Problema al cerrar la sesión');
                }
            } else {
                console.log('\n❌ ERROR: La sesión se cerró automáticamente (problema no resuelto)');
            }
        } else {
            console.log('\n❌ ERROR: La solicitud no fue exitosa o el estado no es activo');
        }
        
    } catch (error) {
        console.error('\n❌ Error en la prueba:');
        if (error.response) {
            console.error('Status:', error.response.status);
            console.error('Data:', error.response.data);
        } else {
            console.error('Error:', error.message);
        }
    }
}

/**
 * Función para verificar que el servidor está corriendo
 */
async function checkServerStatus() {
    try {
        const response = await axios.get(`${API_BASE_URL}/prestamo/estado/`);
        console.log('✅ Servidor API está corriendo');
        return true;
    } catch (error) {
        console.error('❌ Servidor API no está disponible. Asegúrate de que esté corriendo en puerto 3000');
        return false;
    }
}

/**
 * Función principal
 */
async function main() {
    console.log('🔧 Verificando fix para el problema de cierre automático de sesión');
    console.log('📋 Problema: action:1 cerraba inmediatamente la sesión como si fuera action:0');
    console.log('🎯 Solución: Usar prefijo "APP:" para distinguir mensajes de app vs. hardware\n');
    console.log('✅ Funcionalidades mantenidas:');
    console.log('   - Publicación al tópico loan_queryu (para sistemas dependientes)');
    console.log('   - Filtrado de mensajes para evitar bucle infinito\n');
    
    // Verificar que el servidor esté corriendo
    const serverRunning = await checkServerStatus();
    if (!serverRunning) {
        console.log('\n💡 Para ejecutar el servidor:');
        console.log('   cd c:\\laragon\\www\\flutter-api');
        console.log('   npm start');
        return;
    }
    
    // Ejecutar prueba
    await testPrestamoControl();
    
    console.log('\n🏁 Prueba completada');
}

// Ejecutar si se llama directamente
if (require.main === module) {
    main().catch(console.error);
}

module.exports = { testPrestamoControl, checkServerStatus };