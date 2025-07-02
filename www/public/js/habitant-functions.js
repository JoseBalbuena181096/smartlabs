/**
 * Funciones específicas para la página de Habitant
 * SMARTLABS - Registro de Usuarios
 */

// Función para eliminar usuario
function deleteHabitant(id) {
    if (confirm('¿Está seguro de que desea eliminar este usuario?\n\nEsta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id_to_delete';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// Variables globales para búsqueda
let searchTimeout = null;
let selectedUser = null;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Configurar buscador de usuarios
    setupUserSearch();
    
    // Auto-conversión a mayúsculas para campos específicos
    const uppercaseFields = ['name', 'registration'];
    uppercaseFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
    });
    
    // Campo email en minúsculas
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.addEventListener('input', function() {
            this.value = this.value.toLowerCase();
        });
    }
    
    // Validación del formulario
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const registration = document.getElementById('registration').value.trim();
            const email = document.getElementById('email').value.trim();
            const rfid = document.getElementById('rfid').value.trim();
            
            // Validaciones
            if (name.length < 3) {
                alert('El nombre debe tener al menos 3 caracteres');
                e.preventDefault();
                return false;
            }
            
            if (registration.length < 3) {
                alert('La matrícula debe tener al menos 3 caracteres');
                e.preventDefault();
                return false;
            }
            
            // Validar formato de email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert('Por favor ingresa un email válido');
                e.preventDefault();
                return false;
            }
            
            if (rfid.length < 3) {
                alert('El RFID debe tener al menos 3 caracteres');
                e.preventDefault();
                return false;
            }
            
            // Confirmación antes de enviar
            if (!confirm('¿Estás seguro de registrar este usuario?\n\nNombre: ' + name + '\nMatrícula: ' + registration + '\nEmail: ' + email + '\nRFID: ' + rfid)) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Auto-hide alerts después de 8 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        });
    }, 8000);
    
    // Animar las filas de la tabla
    const tableRows = document.querySelectorAll('#habitantsTable tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Mostrar notificación de éxito si el usuario fue creado
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'user_created') {
        const successMessage = document.createElement('div');
        successMessage.className = 'alert alert-success alert-dismissible fade show position-fixed';
        successMessage.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        `;
        successMessage.innerHTML = `
            <i class="fa fa-check-circle"></i>
            <strong>¡Usuario creado exitosamente!</strong>
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        document.body.appendChild(successMessage);
        
        setTimeout(() => {
            if (successMessage.parentNode) {
                successMessage.parentNode.removeChild(successMessage);
            }
        }, 5000);
    }
});

/**
 * Configurar buscador de usuarios
 */
function setupUserSearch() {
    const searchInput = document.getElementById('user_search');
    const searchType = document.getElementById('search_type');
    const charCount = document.getElementById('search_char_count');
    
    if (searchInput) {
        // Contador de caracteres
        searchInput.addEventListener('input', function() {
            const length = this.value.length;
            if (charCount) {
                charCount.textContent = length;
                charCount.style.color = length >= 95 ? '#dc3545' : length >= 80 ? '#ffc107' : '#6c757d';
            }
        });
        
        // Placeholder dinámico basado en el tipo de búsqueda
        const updatePlaceholder = () => {
            const type = searchType.value;
            const placeholders = {
                'all': 'Escriba nombre, matrícula o email (ej: JOSE BALBUENA, L03533767 o jose@tec.mx)',
                'name': 'Escriba el nombre completo (ej: JOSE ANGEL BALBUENA PALMA)',
                'registration': 'Escriba la matrícula (ej: L03533767)',
                'email': 'Escriba el email (ej: jose.balbuena@tec.mx)'
            };
            searchInput.placeholder = placeholders[type] || placeholders['all'];
        };
        
        // Actualizar placeholder al cambiar tipo
        searchType.addEventListener('change', updatePlaceholder);
        updatePlaceholder(); // Establecer placeholder inicial
        
        // Búsqueda en tiempo real
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Limpiar timeout anterior
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Si hay menos de 2 caracteres, limpiar resultados
            if (searchTerm.length < 2) {
                clearSearchResults();
                return;
            }
            
            // Agregar indicador visual de que se está escribiendo
            this.style.borderColor = '#ffc107';
            
            // Esperar 500ms antes de buscar (debounce)
            searchTimeout = setTimeout(() => {
                this.style.borderColor = '#17a2b8';
                performUserSearch(searchTerm);
            }, 500);
        });
        
        // Buscar al cambiar tipo de búsqueda
        searchType.addEventListener('change', function() {
            const searchTerm = searchInput.value.trim();
            if (searchTerm.length >= 2) {
                performUserSearch(searchTerm);
            }
        });
    }
}

/**
 * Realizar búsqueda de usuarios
 */
function performUserSearch(searchTerm) {
    const searchType = document.getElementById('search_type').value;
    const resultsContainer = document.getElementById('search_results');
    const resultsContent = document.getElementById('search_results_content');
    
    // Mostrar indicador de carga
    resultsContainer.style.display = 'block';
    resultsContent.innerHTML = `
        <div class="text-center">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p>Buscando usuarios...</p>
        </div>
    `;
    
    // Realizar petición AJAX
    const formData = new FormData();
    formData.append('search_users', '1');
    formData.append('search_term', searchTerm);
    formData.append('search_type', searchType);
    
    fetch('/Habitant/search', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        displaySearchResults(data);
    })
    .catch(error => {
        console.error('Error en búsqueda:', error);
        resultsContent.innerHTML = `
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle"></i>
                Error al buscar usuarios: ${error.message}
            </div>
        `;
    });
}

/**
 * Mostrar resultados de búsqueda
 */
function displaySearchResults(results) {
    const resultsContent = document.getElementById('search_results_content');
    
    if (!results || results.length === 0) {
        resultsContent.innerHTML = `
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                No se encontraron usuarios que coincidan con la búsqueda.
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>Seleccionar</th>
                        <th><i class="fa fa-hashtag"></i> ID</th>
                        <th><i class="fa fa-user"></i> Nombre</th>
                        <th><i class="fa fa-id-card"></i> Matrícula</th>
                        <th><i class="fa fa-envelope"></i> Email</th>
                        <th><i class="fa fa-calendar"></i> Fecha</th>
                        <th><i class="fa fa-cog"></i> Acciones</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    results.forEach(user => {
        const date = user.hab_date ? new Date(user.hab_date).toLocaleDateString('es-ES') : 'N/A';
        html += `
            <tr class="search-result-row" data-user-id="${user.hab_id}">
                <td>
                    <input type="radio" name="selected_user" value="${user.hab_id}" 
                           onchange="selectUser(${user.hab_id}, '${user.hab_name}', '${user.hab_registration}', '${user.hab_email}')">
                </td>
                <td><strong>${user.hab_id}</strong></td>
                <td>
                    <span class="badge badge-primary">${user.hab_name}</span>
                </td>
                <td>
                    <code>${user.hab_registration}</code>
                </td>
                <td>
                    <small>${user.hab_email}</small>
                </td>
                <td>
                    <small>${date}</small>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="fillFormWithUser(${user.hab_id}, '${user.hab_name}', '${user.hab_registration}', '${user.hab_email}')">
                        <i class="fa fa-edit"></i> Usar
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteHabitant(${user.hab_id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                Se encontraron <strong>${results.length}</strong> usuario(s) que coinciden con la búsqueda.
            </div>
        </div>
    `;
    
    resultsContent.innerHTML = html;
    
    // Animar los resultados
    setTimeout(() => {
        const rows = document.querySelectorAll('.search-result-row');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }, 100);
}

/**
 * Seleccionar usuario de los resultados
 */
function selectUser(userId, name, registration, email) {
    selectedUser = {
        id: userId,
        name: name,
        registration: registration,
        email: email
    };
    
    // Mostrar botón de selección
    document.getElementById('select_user_btn').style.display = 'inline-block';
    
    // Resaltar fila seleccionada
    document.querySelectorAll('.search-result-row').forEach(row => {
        row.classList.remove('table-warning');
    });
    
    const selectedRow = document.querySelector(`[data-user-id="${userId}"]`);
    if (selectedRow) {
        selectedRow.classList.add('table-warning');
    }
}

/**
 * Llenar formulario con datos del usuario seleccionado
 */
function fillFormWithUser(userId, name, registration, email) {
    // Llenar campos del formulario con animación
    const nameField = document.getElementById('name');
    const registrationField = document.getElementById('registration');
    const emailField = document.getElementById('email');
    
    // Animar el llenado de cada campo
    animateFieldFill(nameField, name);
    setTimeout(() => animateFieldFill(registrationField, registration), 200);
    setTimeout(() => animateFieldFill(emailField, email), 400);
    
    // Mostrar notificación
    showNotification(`Formulario llenado con datos de: ${name}`, 'success');
    
    // Scroll al formulario
    document.getElementById('name').scrollIntoView({ behavior: 'smooth' });
    
    // Focus en el campo RFID después de la animación
    setTimeout(() => {
        document.getElementById('rfid').focus();
        // Resaltar el campo RFID
        const rfidField = document.getElementById('rfid');
        rfidField.style.border = '2px solid #28a745';
        rfidField.style.boxShadow = '0 0 10px rgba(40, 167, 69, 0.3)';
        
        setTimeout(() => {
            rfidField.style.border = '';
            rfidField.style.boxShadow = '';
        }, 2000);
    }, 800);
    
    // Limpiar resultados de búsqueda
    setTimeout(() => {
        clearSearchResults();
    }, 1000);
}

/**
 * Animar el llenado de un campo
 */
function animateFieldFill(field, value) {
    if (!field) return;
    
    // Limpiar el campo primero
    field.value = '';
    field.classList.add('filled');
    
    // Escribir el valor letra por letra
    let index = 0;
    const typeInterval = setInterval(() => {
        field.value += value[index];
        index++;
        
        if (index >= value.length) {
            clearInterval(typeInterval);
            // Remover la clase después de la animación
            setTimeout(() => {
                field.classList.remove('filled');
            }, 1000);
        }
    }, 50); // 50ms por carácter
}

/**
 * Seleccionar usuario marcado
 */
function selectSearchedUser() {
    if (!selectedUser) {
        alert('Por favor seleccione un usuario primero');
        return;
    }
    
    fillFormWithUser(selectedUser.id, selectedUser.name, selectedUser.registration, selectedUser.email);
}

/**
 * Limpiar búsqueda
 */
function clearSearch() {
    document.getElementById('user_search').value = '';
    document.getElementById('search_type').value = 'all';
    clearSearchResults();
    selectedUser = null;
}

/**
 * Limpiar resultados de búsqueda
 */
function clearSearchResults() {
    const resultsContainer = document.getElementById('search_results');
    const resultsContent = document.getElementById('search_results_content');
    const selectBtn = document.getElementById('select_user_btn');
    
    resultsContainer.style.display = 'none';
    resultsContent.innerHTML = '';
    selectBtn.style.display = 'none';
    selectedUser = null;
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
        animation: slideInRight 0.3s ease-out;
    `;
    notification.innerHTML = `
        <i class="fa ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
        <strong>${message}</strong>
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remover después de 3 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 3000);
} 