<?php 
$title = "Becarios - SMARTLABS";
include __DIR__ . '/../layout/header.php'; 
?>

<style>
/* Estilos específicos para la vista de Becarios */
.becarios-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px;
  padding: 30px;
  margin-bottom: 30px;
  box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
  position: relative;
  overflow: hidden;
}

.becarios-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  transform: rotate(45deg);
}

.becarios-header h1 {
  font-size: 2.2rem;
  font-weight: 700;
  margin-bottom: 10px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.becarios-header p {
  font-size: 1.1rem;
  opacity: 0.95;
  margin: 0;
}

.form-section {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin-bottom: 25px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 123, 255, 0.1);
  transition: all 0.3s ease;
}

.form-section:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.form-section h3 {
  color: #2c3e50;
  margin-bottom: 25px;
  border-bottom: 3px solid #007bff;
  padding-bottom: 12px;
  font-weight: 600;
  font-size: 1.3rem;
}

.stats-panel {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  border-radius: 12px;
  text-align: center;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 6px 25px rgba(40, 167, 69, 0.3);
  transition: all 0.3s ease;
}

.stats-panel:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 35px rgba(40, 167, 69, 0.4);
}

.stats-panel h1 {
  margin: 0;
  font-size: 3rem;
  font-weight: 800;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.stats-panel .stats-label {
  font-size: 1.1rem;
  opacity: 0.95;
  margin-bottom: 15px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.data-section {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin-bottom: 25px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 123, 255, 0.1);
}

.data-section h3 {
  color: #2c3e50;
  margin-bottom: 25px;
  border-bottom: 3px solid #007bff;
  padding-bottom: 12px;
  font-weight: 600;
  font-size: 1.3rem;
}

.table-container {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
}

.data-table {
  margin-bottom: 0;
  font-size: 0.95rem;
}

.table-header {
  background: linear-gradient(135deg, #343a40 0%, #495057 100%);
  color: white;
}

.table-header th {
  border: none;
  padding: 15px 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.85rem;
}

.data-table tbody tr {
  transition: all 0.2s ease;
}

.data-table tbody tr:hover {
  background-color: #f8f9fa;
  transform: scale(1.005);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.data-table tbody td {
  padding: 12px;
  vertical-align: middle;
  border-color: #e9ecef;
}

.device-select {
  background: white;
  border: 2px solid #007bff;
  border-radius: 8px;
  padding: 12px 15px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.device-select:focus {
  border-color: #0056b3;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  outline: none;
}

.md-form-group {
  margin-bottom: 25px;
}

.md-form-group label {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 8px;
  display: block;
  font-size: 0.95rem;
}

.md-input {
  border: 2px solid #e9ecef;
  border-radius: 8px;
  padding: 12px 15px;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.md-input:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  outline: none;
}

.date-input {
  border: 2px solid #e9ecef;
  border-radius: 8px;
  padding: 12px 15px;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.date-input:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  outline: none;
}

.btn-action {
  padding: 12px 20px;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.3s ease;
  border: none;
  font-size: 0.95rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.btn-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.btn-primary.btn-action {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.btn-success.btn-action {
  background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.alert {
  border-radius: 8px;
  border: none;
  padding: 20px;
  font-size: 0.95rem;
}

.alert-info {
  background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
  color: #0c5460;
}

.badge {
  padding: 8px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-success {
  background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
}

.badge-danger {
  background: linear-gradient(135deg, #f44336 0%, #ef5350 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(244, 67, 54, 0.3);
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeInUp 0.6s ease-out;
}

/* Responsive improvements */
@media (max-width: 768px) {
  .becarios-header {
    padding: 20px;
    text-align: center;
  }
  
  .becarios-header h1 {
    font-size: 1.8rem;
  }
  
  .form-section {
    padding: 20px;
  }
  
  .stats-panel h1 {
    font-size: 2.5rem;
  }
  
  .btn-group {
    flex-direction: column;
  }
  
  .btn-action {
    margin-bottom: 10px;
    width: 100%;
  }
}
</style>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<!-- content -->
<div id="content" class="app-content box-shadow-z0" role="main">
  <div class="app-header white box-shadow">
    <div class="navbar navbar-toggleable-sm flex-row align-items-center">
      <!-- Open side - Navigation on mobile -->
      <a data-toggle="modal" data-target="#aside" class="hidden-lg-up mr-3">
        <i class="material-icons">&#xe5d2;</i>
      </a>
      
      <div class="mb-0 h5 no-wrap" id="pageTitle">
        <i class="fa fa-graduation-cap"></i> Gestión de Becarios
      </div>
      
      <div class="collapse navbar-collapse" id="collapse">
        <ul class="nav navbar-nav mr-auto">
          <li class="nav-item dropdown">
            <div ui-include="'views/blocks/dropdown.new.html'"></div>
          </li>
        </ul>
        <div ui-include="'views/blocks/navbar.form.html'"></div>
      </div>
    </div>
  </div>

  <div class="app-body">
    <div class="padding">
      
      <!-- Header de la sección -->
      <div class="becarios-header animate-fade-in">
        <h1><i class="fa fa-graduation-cap"></i> Sistema de Gestión de Becarios</h1>
        <p class="mb-0">Consulta y análisis de horas de servicio becario</p>
      </div>
      
      <!-- Formulario de búsqueda de becarios -->
      <div class="row">
        <div class="col-md-8">
          <div class="form-section animate-fade-in">
            <h3><i class="fa fa-search"></i> Búsqueda de Usuario</h3>
            
            <div class="row">
              <div class="col-md-6">
                <label for="device_id" class="form-label"><strong><i class="fa fa-microchip"></i> Dispositivo:</strong></label>
                <select id="device_id" class="form-control device-select">
                  <option value="SMART10000">Becarios</option>
                  <?php if (!empty($devices)): ?>
                    <?php foreach ($devices as $device): ?>
                      <option value="<?php echo htmlspecialchars($device['traffic_device']); ?>">
                        <?php echo htmlspecialchars($device['traffic_device']); ?>
                      </option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
            </div>
            
            <div class="row mt-3">
              <div class="col-md-6">
                <div class="md-form-group">
                  <label><i class="fa fa-id-card"></i> MATRÍCULA:</label>
                  <input name="registration" id="registration" type="text" class="md-input form-control" placeholder="Ej: A01736666" value="" onchange="enviarDatos()" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="md-form-group">
                  <label><i class="fa fa-user"></i> NOMBRE:</label>
                  <input name="name" id="name" type="text" class="md-input form-control" value="" readonly>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="form-section animate-fade-in">
            <h3><i class="fa fa-info-circle"></i> Información</h3>
            <div class="alert alert-info">
              <i class="fa fa-lightbulb-o"></i>
              <strong>Instrucciones:</strong><br>
              1. Selecciona el dispositivo<br>
              2. Ingresa la matrícula del becario<br>
              3. El nombre se completará automáticamente<br>
              4. Configura las fechas para generar el reporte
            </div>
          </div>
        </div>
      </div>

      
      <!-- Sección de fechas y acciones -->
      <div class="row mt-4">
        <div class="col-md-12">
          <div class="form-section animate-fade-in">
            <h3><i class="fa fa-calendar"></i> Configuración de Fechas</h3>
            
            <form method="get" class="row g-3">
              <div class="col-md-3">
                <label for="start_date" class="form-label"><strong><i class="fa fa-calendar-check-o"></i> Fecha de inicio:</strong></label>
                <input type="date" id="start_date" name="start_date" class="form-control date-input" required>
              </div>
              <div class="col-md-3">
                <label for="end_date" class="form-label"><strong><i class="fa fa-calendar-times-o"></i> Fecha de fin:</strong></label>
                <input type="date" id="end_date" name="end_date" class="form-control date-input" required>
              </div>
              <div class="col-md-3">
                <label for="serie_device" class="form-label"><strong><i class="fa fa-microchip"></i> Dispositivo:</strong></label>
                <input type="text" id="serie_device" name="serie_device" class="form-control md-input" placeholder="Serie del dispositivo" required>
              </div>
              <div class="col-md-3">
                <label for="matricula" class="form-label"><strong><i class="fa fa-id-card"></i> Matrícula (opcional):</strong></label>
                <input type="text" id="matricula" name="matricula" class="form-control md-input" placeholder="Matrícula del usuario">
              </div>
              <div class="col-12 d-flex justify-content-center mt-3">
                <div class="btn-group">
                  <button type="submit" class="btn btn-primary btn-action">
                    <i class="fa fa-file-text-o"></i> Generar Reporte
                  </button>

                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

    <!-- Panel de estadísticas -->
    <div class="row mt-4">
      <div class="col-md-6">
        <div class="stats-panel animate-fade-in">
          <div class="stats-label">Horas de uso:</div>
          <h1><?php echo $timeFullHours; ?></h1>
        </div>
      </div>
      <div class="col-md-6">
        <div class="stats-panel animate-fade-in">
          <div class="stats-label">Número de veces usado:</div>
          <h1><?php echo $jobsCount; ?></h1>
        </div>
      </div>
    </div>

    <!-- Tabla de tráfico de usuarios -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="data-section animate-fade-in">
          <h3>Tráfico de Usuarios por Dispositivo</h3>
          
          <div class="table-container">
            <div class="table-container">
              <table id="userTrafficTable" class="table table-striped data-table">
                <thead class="table-header">
                  <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Nombre</th>
                    <th>Matrícula</th>
                    <th>Email</th>
                    <th>Dispositivo</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($usersTrafficDevice)): ?>
                    <?php foreach ($usersTrafficDevice as $traffic): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($traffic['traffic_id']); ?></td>
                        <td><?php echo htmlspecialchars($traffic['traffic_date']); ?></td>
                        <td><?php echo htmlspecialchars($traffic['hab_name']); ?></td>
                        <td><?php echo htmlspecialchars($traffic['hab_registration']); ?></td>
                        <td><?php echo htmlspecialchars($traffic['hab_email']); ?></td>
                        <td><?php echo htmlspecialchars($traffic['traffic_device']); ?></td>
                        <td>
                          <?php 
                            if ($traffic['traffic_state'] == 1) {
                              echo '<span class="badge badge-success">Entró</span>';
                            } else {
                              echo '<span class="badge badge-danger">Salió</span>';
                            }
                          ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="text-center">NO DATA AVAILABLE</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>
</div>



<!-- Scripts específicos para Becarios -->
<script src="/public/js/becarios-search.js"></script>
<script>


// Función para enviar datos (búsqueda de usuario por matrícula)
function enviarDatos() {
    var valorInput = document.getElementById('registration').value;

    // Crear objeto XMLHttpRequest
    var xhr = new XMLHttpRequest();

    // Configurar la solicitud con la URL correcta
    xhr.open('POST', '/Becarios', true);
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
                // Actualizar el valor del input 'matricula'
                document.getElementById('matricula').value = matricula[1];
            }
        }
    };

    // Enviar la solicitud con el valor del input
    xhr.send('registration=' + encodeURIComponent(valorInput));
}

// Actualizar serie del dispositivo cada 500ms
setInterval(function () { 
    var inputSerie = document.getElementById("serie_device");
    var deviceSelect = document.getElementById("device_id");
    if (deviceSelect && inputSerie) {
        inputSerie.value = deviceSelect.value;
    }
}, 500);
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>