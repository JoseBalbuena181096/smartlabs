<?php 
$title = "Dispositivos - SMARTLABS";
include __DIR__ . '/../layout/header.php'; 
?>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<!-- content -->
<div id="content" class="app-content box-shadow-z0" role="main">
  <div class="app-header white box-shadow">
    <div class="navbar navbar-toggleable-sm flex-row align-items-center">
      <a data-toggle="modal" data-target="#aside" class="hidden-lg-up mr-3">
        <i class="material-icons">&#xe5d2;</i>
      </a>
      <div class="mb-0 h5 no-wrap" id="pageTitle">Gestión de Dispositivos IoT</div>
      
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
      
      <!-- Mensajes de estado -->
      <?php if (isset($message) && !empty($message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
          <i class="fa fa-info-circle"></i> <?php echo $message; ?>
          <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>
      
      <div class="row">
        <div class="col-md-12">
          <div class="box">
            <div class="box-header">
              <h2><i class="fa fa-microchip"></i> Dispositivos IoT SMARTLABS</h2>
              <small>Gestiona los dispositivos conectados al sistema de laboratorios</small>
            </div>
            <div class="box-body">
              
              <!-- Formulario para agregar dispositivo -->
              <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                  <h4 class="mb-0"><i class="fa fa-plus-circle"></i> Agregar Nuevo Dispositivo</h4>
                </div>
                <div class="card-body">
                  <form method="POST" class="row g-3">
                    <div class="col-md-4">
                      <label for="alias" class="form-label"><strong>Alias del Dispositivo:</strong></label>
                      <input type="text" 
                             name="alias" 
                             id="alias"
                             class="form-control" 
                             placeholder="Ej: CORTADORA LASER CO2 SR1390N-PRO" 
                             value="<?php echo isset($alias) ? htmlspecialchars($alias) : ''; ?>"
                             required>
                      <small class="text-muted">Nombre descriptivo del dispositivo</small>
                    </div>
                    <div class="col-md-4">
                      <label for="serie" class="form-label"><strong>Número de Serie:</strong></label>
                      <input type="text" 
                             name="serie" 
                             id="serie"
                             class="form-control" 
                             placeholder="Ej: SMART00005" 
                             value="<?php echo isset($serie) ? htmlspecialchars($serie) : ''; ?>"
                             required>
                      <small class="text-muted">Identificador único del dispositivo</small>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                      <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fa fa-plus"></i> Agregar Dispositivo
                      </button>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Lista de dispositivos -->
              <div class="card">
                <div class="card-header">
                  <h4 class="mb-0"><i class="fa fa-list"></i> Mis Dispositivos Registrados</h4>
                </div>
                <div class="card-body">
                  <?php if (!empty($devices)): ?>
                    <div class="table-responsive">
                      <table class="table table-striped table-hover" id="devicesTable">
                        <thead class="bg-light">
                          <tr>
                            <th><i class="fa fa-hashtag"></i> ID</th>
                            <th><i class="fa fa-tag"></i> ALIAS</th>
                            <th><i class="fa fa-barcode"></i> NÚMERO DE SERIE</th>
                            <th><i class="fa fa-calendar"></i> FECHA REGISTRO</th>
                            <th><i class="fa fa-user"></i> PROPIETARIO</th>
                            <th><i class="fa fa-cogs"></i> ACCIONES</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($devices as $device): ?>
                            <tr>
                              <td><strong><?php echo htmlspecialchars($device['devices_id']); ?></strong></td>
                              <td>
                                <span class="badge badge-info p-2">
                                  <?php echo htmlspecialchars($device['devices_alias']); ?>
                                </span>
                              </td>
                              <td>
                                <code class="bg-light p-1"><?php echo htmlspecialchars($device['devices_serie']); ?></code>
                              </td>
                              <td>
                                <strong><?php echo date('d/m/Y', strtotime($device['devices_date'])); ?></strong><br>
                                <small class="text-muted"><?php echo date('H:i:s', strtotime($device['devices_date'])); ?></small>
                              </td>
                              <td>
                                <span class="badge badge-secondary">User ID: <?php echo htmlspecialchars($device['devices_user_id']); ?></span>
                              </td>
                              <td>
                                <div class="btn-group" role="group">
                                  <a href="/Device/edit/<?php echo $device['devices_id']; ?>" 
                                     class="btn btn-sm btn-info" 
                                     title="Editar dispositivo">
                                    <i class="fa fa-edit"></i> Editar
                                  </a>
                                  
                                  <form method="POST" 
                                        style="display: inline;" 
                                        onsubmit="return confirm('¿Estás seguro de eliminar el dispositivo \'<?php echo htmlspecialchars($device['devices_alias']); ?>\'?\n\nEsta acción no se puede deshacer.');">
                                    <input type="hidden" name="id_to_delete" value="<?php echo $device['devices_id']; ?>">
                                    <button type="submit" 
                                            class="btn btn-sm btn-danger"
                                            title="Eliminar dispositivo">
                                      <i class="fa fa-trash"></i> Eliminar
                                    </button>
                                  </form>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    
                    <!-- Estadísticas de dispositivos -->
                    <div class="row mt-4">
                      <div class="col-md-4">
                        <div class="box bg-primary text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-microchip fa-2x"></i>
                            <h3><?php echo count($devices); ?></h3>
                            <p>Total Dispositivos</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="box bg-success text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-check-circle fa-2x"></i>
                            <h3><?php echo count($devices); ?></h3>
                            <p>Dispositivos Activos</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="box bg-info text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-calendar fa-2x"></i>
                            <h3>
                              <?php 
                              if (!empty($devices)) {
                                echo date('d/m/Y', strtotime(max(array_column($devices, 'devices_date'))));
                              } else {
                                echo '-';
                              }
                              ?>
                            </h3>
                            <p>Último Registro</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                  <?php else: ?>
                    <div class="alert alert-warning text-center">
                      <i class="fa fa-exclamation-triangle fa-3x mb-3"></i>
                      <h4>No hay dispositivos registrados</h4>
                      <p>Agrega tu primer dispositivo IoT usando el formulario de arriba.</p>
                      <hr>
                      <p class="mb-0">
                        <small>
                          <strong>Nota:</strong> Cada dispositivo debe tener un número de serie único en el sistema.
                        </small>
                      </p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Información adicional -->
      <div class="row">
        <div class="col-md-12">
          <div class="box">
            <div class="box-header">
              <h4><i class="fa fa-info-circle"></i> Información del Sistema</h4>
            </div>
            <div class="box-body">
              <div class="row">
                <div class="col-md-6">
                  <h5><i class="fa fa-lightbulb-o"></i> Consejos:</h5>
                  <ul>
                    <li>Usa nombres descriptivos para identificar fácilmente tus dispositivos</li>
                    <li>El número de serie debe ser único para cada dispositivo</li>
                    <li>Los dispositivos eliminados no podrán recuperarse</li>
                  </ul>
                </div>
                <div class="col-md-6">
                  <h5><i class="fa fa-cog"></i> Configuración:</h5>
                  <ul>
                    <li>Los dispositivos están asociados a tu cuenta de usuario</li>
                    <li>Solo puedes ver y gestionar tus propios dispositivos</li>
                    <li>Los dispositivos activos aparecerán en el dashboard principal</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Animar las filas de la tabla al cargar
    $('#devicesTable tbody tr').each(function(index) {
        $(this).delay(index * 100).fadeIn(500);
    });
    
    // Limpiar formulario después de envío exitoso
    <?php if (isset($message) && strpos($message, 'agregado correctamente') !== false): ?>
    $('#alias').val('');
    $('#serie').val('');
    <?php endif; ?>
    
    // Validación adicional del formulario
    $('form').submit(function(e) {
        var alias = $('#alias').val().trim();
        var serie = $('#serie').val().trim();
        
        if (alias.length < 3) {
            alert('El alias debe tener al menos 3 caracteres');
            e.preventDefault();
            return false;
        }
        
        if (serie.length < 3) {
            alert('El número de serie debe tener al menos 3 caracteres');
            e.preventDefault();
            return false;
        }
    });
});

// Auto-hide alerts después de 5 segundos
setTimeout(function() {
    $('.alert').fadeOut('slow');
}, 5000);
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?> 