/**
 * Funcionalidad específica para la vista de Becarios
 * Sigue exactamente la funcionalidad del archivo legacy becarios.php
 */

// Función para buscar usuario por matrícula (como en becarios.php)
function enviarDatos() {
    var valorInput = document.getElementById('registration').value;

    // Crear objeto XMLHttpRequest
    var xhr = new XMLHttpRequest();

    // Configurar la solicitud con la URL correcta
    xhr.open('POST', '/Becarios/buscarUsuario', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    // Definir la función de devolución de llamada
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            // Obtener el valor del nombre y la matrícula desde la respuesta
            var nombre = xhr.responseText.match(/Nombre: (.+?) -/);
            var matricula = xhr.responseText.match(/Matricula: (.+)/);
            if (nombre && nombre[1]) {
                // Actualizar el valor del input 'name'
                document.getElementById('name').value = nombre[1];
            }
            if (matricula && matricula[1]) {
                // Actualizar el valor del input 'matricula' si existe
                var matriculaInput = document.getElementById('matricula');
                if (matriculaInput) {
                    matriculaInput.value = matricula[1];
                }
            }
        }
    };

    // Enviar la solicitud con el valor del input
    xhr.send('registration=' + encodeURIComponent(valorInput));
}

// El formulario se envía normalmente como en el archivo legacy
// No interceptamos el submit para mantener la funcionalidad original

// Función para actualizar la tabla con los datos de tráfico
function actualizarTabla(trafficData) {
    var tbody = document.querySelector('#userTrafficTable tbody');
    
    if (trafficData && trafficData.length > 0) {
        var html = '';
        trafficData.forEach(function(traffic) {
            html += '<tr>';
            html += '<td>' + (traffic.traffic_id || traffic.id || '') + '</td>';
            html += '<td>' + (traffic.traffic_date || traffic.date || '') + '</td>';
            html += '<td>' + (traffic.hab_name || traffic.name || '') + '</td>';
            html += '<td>' + (traffic.hab_registration || traffic.registration || '') + '</td>';
            html += '<td>' + (traffic.hab_email || traffic.email || '') + '</td>';
            html += '<td>' + (traffic.traffic_device || traffic.device || '') + '</td>';
            html += '<td>' + (traffic.traffic_state || traffic.state || '') + '</td>';
            html += '</tr>';
        });
        tbody.innerHTML = html;
    } else {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">NO DATA AVAILABLE</td></tr>';
    }
}

// Actualizar el valor del dispositivo automáticamente (como en becarios.php)
setInterval(function () { 
    var input_serie = document.getElementById("serie_device");
    var device_select = document.getElementById("device_id");
    if (input_serie && device_select) {
        input_serie.value = device_select.value;
    }
    
    // También actualizar matricula si existe el campo
    var matriculaInput = document.getElementById("matricula");
    var registrationInput = document.getElementById("registration");
    if (matriculaInput && registrationInput && registrationInput.value) {
        matriculaInput.value = registrationInput.value;
    }
}, 500);

// Función para limpiar formularios
function limpiarFormularios() {
    var registrationInput = document.getElementById('registration');
    var nameInput = document.getElementById('name');
    var matriculaInput = document.getElementById('matricula');
    var startDateInput = document.getElementById('start_date');
    var endDateInput = document.getElementById('end_date');
    
    if (registrationInput) registrationInput.value = '';
    if (nameInput) nameInput.value = '';
    if (matriculaInput) matriculaInput.value = '';
    if (startDateInput) startDateInput.value = '';
    if (endDateInput) endDateInput.value = '';
    
    // Limpiar tabla
    var tbody = document.querySelector('#userTrafficTable tbody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">NO DATA AVAILABLE</td></tr>';
    }
    
    // Limpiar estadísticas si existen
    var totalHours = document.getElementById('total-hours');
    var totalSessions = document.getElementById('total-sessions');
    if (totalHours) totalHours.textContent = '0';
    if (totalSessions) totalSessions.textContent = '0';
}

// Función para validar fechas
function validarFechas() {
    var startDate = document.getElementById('start_date').value;
    var endDate = document.getElementById('end_date').value;
    
    if (startDate && endDate) {
        if (new Date(startDate) > new Date(endDate)) {
            alert('La fecha de inicio no puede ser mayor que la fecha de fin');
            return false;
        }
    }
    
    return true;
}

// Función para establecer fechas por defecto (último mes)
function establecerFechasPorDefecto() {
    var startDate = document.getElementById('start_date');
    var endDate = document.getElementById('end_date');
    
    if (startDate && endDate && !startDate.value && !endDate.value) {
        var now = new Date();
        var oneMonthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
        
        startDate.value = oneMonthAgo.toISOString().slice(0, 10);
        endDate.value = now.toISOString().slice(0, 10);
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Becarios inicializado con funcionalidad legacy');
    
    // Establecer fechas por defecto
    establecerFechasPorDefecto();
    
    // Validar fechas antes de enviar formulario
    var form = document.querySelector('form[name="form"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validarFechas()) {
                e.preventDefault();
                return false;
            }
            // Si las fechas son válidas, permitir el envío normal del formulario
        });
    }
    
    // Auto-mayúsculas para matrículas
    var registrationInput = document.getElementById('registration');
    if (registrationInput) {
        registrationInput.addEventListener('input', function() {
            var value = this.value;
            if (value.match(/^[A-Z]\d+$/i)) {
                this.value = value.toUpperCase();
            }
        });
    }
});