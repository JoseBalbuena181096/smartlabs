/**
 * Funciones de navegación para SMARTLABS
 * Replica las funciones de navegación del archivo legacy dashboard.php
 */

// Funciones de navegación principal
function dashboardLab() {
  window.location.href = "/Dashboard";
}

function devicesLab() {
  window.location.href = "/Device";
}

function registerUserLab() {
  window.location.href = "/Habitant";
}

function eliminarUsuario() {
  window.location.href = "/Habitant/delete";
}

function horasUso() {
  window.location.href = "/Stats";
}

function registroAutoLoan() {
  window.location.href = "/Equipment";
}

function autoLoan() {
  window.location.href = "/Loan";
}

function becarios() {
  window.location.href = "/Habitant/scholars";
}

// Función para generar cadena aleatoria (del legacy)
function generarCadenaAleatoria(longitud) {
  const caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  let cadenaAleatoria = '';
  for (let i = 0; i < longitud; i++) {
    const indiceAleatorio = Math.floor(Math.random() * caracteres.length);
    cadenaAleatoria += caracteres.charAt(indiceAleatorio);
  }
  return cadenaAleatoria;
}

// Función para mostrar alertas compatibles con el sistema
function showAlert(message, type = 'info') {
  // Crear elemento de alerta
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
  alertDiv.style.position = 'fixed';
  alertDiv.style.top = '20px';
  alertDiv.style.right = '20px';
  alertDiv.style.zIndex = '9999';
  alertDiv.style.minWidth = '300px';
  
  alertDiv.innerHTML = `
    <strong>${type === 'success' ? 'Éxito:' : type === 'error' ? 'Error:' : 'Info:'}</strong> ${message}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  `;
  
  document.body.appendChild(alertDiv);
  
  // Auto-remover después de 5 segundos
  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.parentNode.removeChild(alertDiv);
    }
  }, 5000);
}

// Función para validar campos requeridos
function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return false;
  
  const requiredFields = form.querySelectorAll('[required]');
  let valid = true;
  
  requiredFields.forEach(field => {
    if (!field.value.trim()) {
      field.classList.add('is-invalid');
      valid = false;
    } else {
      field.classList.remove('is-invalid');
    }
  });
  
  return valid;
}

// Función para formatear fechas
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Función para cargar contenido dinámico
function loadContent(url, targetId) {
  const target = document.getElementById(targetId);
  if (!target) return;
  
  target.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</div>';
  
  fetch(url)
    .then(response => response.text())
    .then(html => {
      target.innerHTML = html;
    })
    .catch(error => {
      console.error('Error cargando contenido:', error);
      target.innerHTML = '<div class="alert alert-danger">Error al cargar el contenido</div>';
    });
}

// Función para actualizar estadísticas en tiempo real
function updateStats() {
  const statsUrl = '/Dashboard/stats';
  
  fetch(statsUrl)
    .then(response => response.json())
    .then(data => {
      // Actualizar elementos de estadísticas si existen
      const totalElement = document.querySelector('[data-stat="total"]');
      const todayElement = document.querySelector('[data-stat="today"]');
      const weekElement = document.querySelector('[data-stat="week"]');
      const usersElement = document.querySelector('[data-stat="users"]');
      
      if (totalElement) totalElement.textContent = data.totalAccess || 0;
      if (todayElement) todayElement.textContent = data.todayAccess || 0;
      if (weekElement) weekElement.textContent = data.thisWeekAccess || 0;
      if (usersElement) usersElement.textContent = data.uniqueUsers || 0;
    })
    .catch(error => console.error('Error actualizando estadísticas:', error));
}

// Inicializar funciones cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  // Actualizar estadísticas cada 30 segundos
  setInterval(updateStats, 30000);
  
  // Configurar tooltips si Bootstrap está disponible
  if (typeof bootstrap !== 'undefined') {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }
  
  // Configurar auto-focus en campos de búsqueda
  const searchFields = document.querySelectorAll('input[type="search"], input[name*="search"]');
  searchFields.forEach(field => {
    field.addEventListener('focus', function() {
      this.select();
    });
  });
});

// Exportar funciones para uso global
window.SmartLabsNavigation = {
  dashboardLab,
  devicesLab,
  registerUserLab,
  eliminarUsuario,
  horasUso,
  registroAutoLoan,
  autoLoan,
  becarios,
  showAlert,
  validateForm,
  formatDate,
  loadContent,
  updateStats,
  generarCadenaAleatoria
}; 