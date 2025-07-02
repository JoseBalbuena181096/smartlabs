/**
 * Funciones AJAX para SMARTLABS
 * Replica las funcionalidades AJAX de los archivos legacy
 */

class SmartLabsAjax {
    constructor() {
        this.baseUrl = window.location.origin;
        this.init();
    }
    
    init() {
        // Auto-configurar formularios según la página actual
        document.addEventListener('DOMContentLoaded', () => {
            this.setupPageSpecificHandlers();
        });
    }
    
    setupPageSpecificHandlers() {
        const currentPath = window.location.pathname;
        
        // Configurar handlers para página de préstamos (dash_loan.php)
        if (currentPath.includes('/Loan')) {
            this.setupLoanHandlers();
        }
        
        // Configurar handlers para página de estadísticas (horas_uso.php)
        if (currentPath.includes('/Stats')) {
            this.setupStatsHandlers();
        }
        
        // Configurar handlers para página de dashboard
        if (currentPath.includes('/Dashboard')) {
            this.setupDashboardHandlers();
        }
    }
    
    /**
     * Configurar handlers para página de préstamos
     * Replica funcionalidad de dash_loan.php
     */
    setupLoanHandlers() {
        const consultForm = document.getElementById('consultForm');
        const consultInput = document.getElementById('consult_loan');
        const resultDiv = document.getElementById('loans_result');
        
        if (consultForm && consultInput) {
            // Auto-submit cuando se escriba en el campo RFID
            consultInput.addEventListener('input', (e) => {
                const rfid = e.target.value.trim();
                if (rfid.length >= 8) { // Mínimo 8 caracteres para RFID
                    this.consultarPrestamos(rfid, resultDiv);
                }
            });
            
            // Submit manual del formulario
            consultForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const rfid = consultInput.value.trim();
                if (rfid) {
                    this.consultarPrestamos(rfid, resultDiv);
                } else {
                    this.showMessage(resultDiv, 'Por favor ingrese un RFID válido', 'warning');
                }
            });
            
            // Focus automático en el campo RFID (como en legacy)
            consultInput.focus();
        }
    }
    
    /**
     * Consultar préstamos por RFID (replica funcionalidad de dash_loan.php)
     */
    consultarPrestamos(rfid, resultDiv) {
        if (!resultDiv) return;
        
        // Mostrar indicador de carga
        resultDiv.innerHTML = `
            <div class="text-center">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p>Consultando préstamos...</p>
            </div>
        `;
        
        const formData = new FormData();
        formData.append('consult_loan', rfid);
        
        fetch('/Loan', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim()) {
                resultDiv.innerHTML = data;
                this.animateResult(resultDiv);
            } else {
                this.showMessage(resultDiv, 'No se encontraron préstamos para esta tarjeta', 'info');
            }
        })
        .catch(error => {
            console.error('Error consultando préstamos:', error);
            this.showMessage(resultDiv, 'Error al consultar préstamos', 'danger');
        });
    }
    
    /**
     * Configurar handlers para página de estadísticas
     * Replica funcionalidad de horas_uso.php
     */
    setupStatsHandlers() {
        const registrationInput = document.getElementById('registration');
        const userInfoDiv = document.getElementById('user_info');
        
        if (registrationInput && userInfoDiv) {
            // Consulta automática cuando se escribe matrícula
            registrationInput.addEventListener('input', (e) => {
                const registration = e.target.value.trim().toUpperCase();
                if (registration.length >= 3) {
                    this.consultarUsuario(registration, userInfoDiv);
                } else {
                    userInfoDiv.innerHTML = '';
                }
            });
        }
        
        // Configurar filtros de estadísticas
        const filterForm = document.getElementById('statsFilterForm');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.loadStatsData();
            });
        }
    }
    
    /**
     * Consultar usuario por matrícula (replica funcionalidad de horas_uso.php)
     */
    consultarUsuario(registration, userInfoDiv) {
        if (!userInfoDiv) return;
        
        const formData = new FormData();
        formData.append('registration', registration);
        
        fetch('/Stats', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            userInfoDiv.innerHTML = data;
            this.animateResult(userInfoDiv);
        })
        .catch(error => {
            console.error('Error consultando usuario:', error);
            userInfoDiv.innerHTML = '<span class="text-danger">Error al consultar usuario</span>';
        });
    }
    
    /**
     * Cargar datos de estadísticas con filtros
     */
    loadStatsData() {
        const device = document.getElementById('serie_device')?.value;
        const startDate = document.getElementById('start_date')?.value;
        const endDate = document.getElementById('end_date')?.value;
        const matricula = document.getElementById('matricula')?.value;
        
        if (!device || !startDate || !endDate) {
            this.showMessage(document.getElementById('stats_results'), 'Complete todos los campos requeridos', 'warning');
            return;
        }
        
        const params = new URLSearchParams({
            serie_device: device,
            start_date: startDate,
            end_date: endDate
        });
        
        if (matricula) {
            params.append('matricula', matricula);
        }
        
        window.location.href = `/Stats?${params.toString()}`;
    }
    
    /**
     * Configurar handlers para dashboard
     */
    setupDashboardHandlers() {
        // Auto-refresh del dashboard cada 30 segundos (como en legacy)
        this.setupAutoRefresh();
        
        // Manejar filtro de dispositivo
        const deviceFilter = document.getElementById('deviceFilter');
        if (deviceFilter) {
            deviceFilter.addEventListener('change', (e) => {
                const selectedDevice = e.target.value;
                if (selectedDevice) {
                    window.location.href = `/Dashboard?device=${selectedDevice}`;
                } else {
                    window.location.href = '/Dashboard';
                }
            });
        }
    }
    
    /**
     * Auto-refresh para el dashboard (replica funcionalidad de dashboard.php)
     */
    setupAutoRefresh() {
        if (!document.getElementById('trafficTable')) return;
        
        let refreshInterval;
        
        const startRefresh = () => {
            refreshInterval = setInterval(() => {
                if (document.visibilityState === 'visible') {
                    this.refreshTrafficData();
                }
            }, 30000); // 30 segundos como en legacy
        };
        
        const stopRefresh = () => {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        };
        
        // Iniciar auto-refresh
        startRefresh();
        
        // Pausar cuando la página no es visible
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                startRefresh();
            } else {
                stopRefresh();
            }
        });
        
        // Limpiar al salir de la página
        window.addEventListener('beforeunload', stopRefresh);
    }
    
    /**
     * Actualizar datos de tráfico en dashboard
     */
    refreshTrafficData() {
        const trafficTable = document.getElementById('trafficTable');
        if (!trafficTable) return;
        
        const currentDevice = new URLSearchParams(window.location.search).get('device');
        const url = currentDevice ? `/Dashboard?device=${currentDevice}` : '/Dashboard';
        
        fetch(url)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(html, 'text/html');
            const newTable = newDoc.getElementById('trafficTable');
            
            if (newTable) {
                trafficTable.innerHTML = newTable.innerHTML;
                this.animateRefresh(trafficTable);
            }
        })
        .catch(error => {
            console.error('Error actualizando datos de tráfico:', error);
        });
    }
    
    /**
     * Funciones de utilidad
     */
    showMessage(container, message, type = 'info') {
        if (!container) return;
        
        const alertClass = `alert-${type}`;
        container.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
    
    animateResult(element) {
        if (!element) return;
        
        element.style.opacity = '0';
        element.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.3s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, 50);
    }
    
    animateRefresh(element) {
        if (!element) return;
        
        element.style.opacity = '0.7';
        setTimeout(() => {
            element.style.transition = 'opacity 0.3s ease';
            element.style.opacity = '1';
        }, 200);
    }
    
    /**
     * Validaciones de formularios (como en archivos legacy)
     */
    validateRFID(rfid) {
        return rfid && rfid.length >= 8 && /^[0-9]+$/.test(rfid);
    }
    
    validateRegistration(registration) {
        return registration && registration.length >= 3;
    }
    
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Conversiones automáticas (como en archivos legacy)
     */
    autoUppercase(input) {
        if (input) {
            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.toUpperCase();
            });
        }
    }
    
    autoLowercase(input) {
        if (input) {
            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.toLowerCase();
            });
        }
    }
}

// Inicializar automáticamente
window.smartLabsAjax = new SmartLabsAjax();

// Funciones globales para compatibilidad con código legacy
window.consultarPrestamos = function(rfid) {
    const resultDiv = document.getElementById('loans_result');
    window.smartLabsAjax.consultarPrestamos(rfid, resultDiv);
};

window.consultarUsuario = function(registration) {
    const userInfoDiv = document.getElementById('user_info');
    window.smartLabsAjax.consultarUsuario(registration, userInfoDiv);
};

// Auto-configurar campos de texto para conversiones automáticas
document.addEventListener('DOMContentLoaded', function() {
    // Campos que deben ser mayúsculas
    const uppercaseFields = document.querySelectorAll('.uppercase, [data-uppercase]');
    uppercaseFields.forEach(field => {
        window.smartLabsAjax.autoUppercase(field);
    });
    
    // Campos que deben ser minúsculas (emails)
    const lowercaseFields = document.querySelectorAll('.lowercase, [data-lowercase], input[type="email"]');
    lowercaseFields.forEach(field => {
        window.smartLabsAjax.autoLowercase(field);
    });
}); 