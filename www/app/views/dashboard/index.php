<?php 
$title = "Dashboard - SMARTLABS";
include __DIR__ . '/../layout/header.php'; 
?>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<!-- content -->
<div id="content" class="app-content box-shadow-z0" role="main">
  <div class="app-header white box-shadow">
    <div class="navbar navbar-toggleable-sm flex-row align-items-center">
      <!-- Open side - Navigation on mobile -->
      <a data-toggle="modal" data-target="#aside" class="hidden-lg-up mr-3">
        <i class="material-icons">&#xe5d2;</i>
      </a>
      
      <div class="">
        <b id="display_new_access"></b>
      </div>
      
      <div class="mb-0 h5 no-wrap" id="pageTitle">Dashboard Principal</div>
      
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
      <div class="row">
        <div class="col-md-12">
          <div class="box">
            <div class="box-header">
              <h2>Control de Acceso SMARTLABS</h2>
              <small>Monitoreo en tiempo real del tráfico de dispositivos</small>
            </div>
            <div class="box-body">
              <!-- Formulario de filtro por dispositivo -->
              <form method="GET" class="form-inline mb-4">
                <div class="form-group">
                  <label for="serie_device" class="mr-2"><strong>Filtrar por dispositivo:</strong></label>
                  <select name="serie_device" id="serie_device" class="form-control mr-2" required>
                    <option value="">Seleccionar dispositivo...</option>
                    <?php if (!empty($devices)): ?>
                      <?php foreach ($devices as $device): ?>
                        <option value="<?php echo htmlspecialchars($device['devices_serie']); ?>" 
                                <?php echo (isset($_GET['serie_device']) && $_GET['serie_device'] === $device['devices_serie']) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($device['devices_alias']); ?> (<?php echo htmlspecialchars($device['devices_serie']); ?>)
                        </option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                  <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i> Filtrar
                  </button>
                </div>
              </form>

              <!-- Mostrar tráfico si hay dispositivo seleccionado -->
              <?php if (!empty($usersTrafficDevice)): ?>
                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i> 
                  <strong>Dispositivo:</strong> <?php echo htmlspecialchars($_GET['serie_device']); ?>
                  | <strong>Últimos 12 registros</strong> | 
                  <small>Actualización automática cada 30 segundos</small>
                </div>
                
                <div class="table-responsive">
                  <table class="table table-striped b-t" id="trafficTable">
                    <thead class="bg-light">
                      <tr>
                        <th><i class="fa fa-calendar"></i> FECHA/HORA</th>
                        <th><i class="fa fa-user"></i> USUARIO</th>
                        <th><i class="fa fa-id-card"></i> MATRÍCULA</th>
                        <th><i class="fa fa-envelope"></i> EMAIL</th>
                        <th><i class="fa fa-exchange"></i> ACCIÓN</th>
                        <th><i class="fa fa-microchip"></i> DISPOSITIVO</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($usersTrafficDevice as $traffic): ?>
                        <tr class="<?php echo $traffic['traffic_state'] ? 'table-success' : 'table-warning'; ?>">
                          <td>
                            <strong><?php echo date('d/m/Y', strtotime($traffic['traffic_date'])); ?></strong><br>
                            <small class="text-muted"><?php echo date('H:i:s', strtotime($traffic['traffic_date'])); ?></small>
                          </td>
                          <td>
                            <strong><?php echo htmlspecialchars($traffic['hab_name']); ?></strong>
                          </td>
                          <td>
                            <span class="badge badge-info"><?php echo htmlspecialchars($traffic['hab_registration']); ?></span>
                          </td>
                          <td>
                            <small><?php echo htmlspecialchars($traffic['hab_email']); ?></small>
                          </td>
                          <td>
                            <?php if ($traffic['traffic_state']): ?>
                              <span class="badge badge-success">
                                <i class="fa fa-sign-in"></i> ENTRADA
                              </span>
                            <?php else: ?>
                              <span class="badge badge-danger">
                                <i class="fa fa-sign-out"></i> SALIDA
                              </span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <code><?php echo htmlspecialchars($traffic['traffic_device']); ?></code>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                
                <!-- Estadísticas del dispositivo -->
                <div class="row mt-4">
                  <div class="col-md-6">
                    <div class="box bg-success text-white">
                      <div class="box-body text-center">
                        <h3><?php echo count(array_filter($usersTrafficDevice, function($t) { return $t['traffic_state']; })); ?></h3>
                        <p><i class="fa fa-sign-in"></i> Entradas Registradas</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="box bg-warning text-white">
                      <div class="box-body text-center">
                        <h3><?php echo count(array_filter($usersTrafficDevice, function($t) { return !$t['traffic_state']; })); ?></h3>
                        <p><i class="fa fa-sign-out"></i> Salidas Registradas</p>
                      </div>
                    </div>
                  </div>
                </div>
                
              <?php elseif (isset($_GET['serie_device']) && !empty($_GET['serie_device'])): ?>
                <div class="alert alert-warning">
                  <i class="fa fa-exclamation-triangle"></i> 
                  <strong>Sin registros</strong><br>
                  No se encontraron registros de tráfico para el dispositivo <strong><?php echo htmlspecialchars($_GET['serie_device']); ?></strong>
                </div>
              <?php else: ?>
                <div class="alert alert-info text-center">
                  <i class="fa fa-info-circle fa-2x mb-2"></i><br>
                  <strong>Bienvenido al Sistema SMARTLABS</strong><br>
                  Selecciona un dispositivo para monitorear el tráfico de acceso en tiempo real.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Panel de estadísticas generales -->
      <div class="row">
        <div class="col-md-3">
          <div class="box bg-primary text-white">
            <div class="box-body text-center">
              <i class="fa fa-microchip fa-2x"></i>
              <h3><?php echo count($devices); ?></h3>
              <p>Dispositivos Activos</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="box bg-info text-white">
            <div class="box-body text-center">
              <i class="fa fa-users fa-2x"></i>
              <h3 id="total-users"><?php echo count($usersTrafficDevice); ?></h3>
              <p>Accesos del Dispositivo</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="box bg-success text-white">
            <div class="box-body text-center">
              <i class="fa fa-wifi fa-2x"></i>
              <h3 id="system-status">ONLINE</h3>
              <p>Estado del Sistema</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="box bg-warning text-white">
            <div class="box-body text-center">
              <i class="fa fa-clock-o fa-2x"></i>
              <h3 id="last-update"><?php echo date('H:i:s'); ?></h3>
              <p>Última Actualización</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Auto-refresh cada 30 segundos si hay un dispositivo seleccionado
<?php if (isset($_GET['serie_device']) && !empty($_GET['serie_device'])): ?>
setInterval(function() {
    // Verificar si la página sigue siendo visible
    if (!document.hidden) {
        // Solo recargar si hay un dispositivo seleccionado
        window.location.reload();
    }
}, 30000); // 30 segundos

// Actualizar hora cada segundo
setInterval(function() {
    var now = new Date();
    document.getElementById('last-update').textContent = now.toLocaleTimeString();
}, 1000);
<?php endif; ?>

// Resaltar filas con animación al cargar
$(document).ready(function() {
    $('#trafficTable tbody tr').each(function(index) {
        $(this).delay(index * 100).fadeIn(500);
    });
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?> 