<?php 
$title = "Registro de Equipos - SMARTLABS";
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
      <div class="mb-0 h5 no-wrap" id="pageTitle">Registro de Equipos para Autopréstamo</div>
      
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
              <h2><i class="fa fa-cube"></i> REGISTRO DE EQUIPOS SMARTLABS</h2>
              <small>Sistema de registro de herramientas y equipos para autopréstamo</small>
            </div>
            <div class="box-body">
              
              <!-- Formulario de registro -->
              <div class="card mb-4">
                <div class="card-header bg-success text-white">
                  <h4 class="mb-0"><i class="fa fa-plus-circle"></i> Registrar Nuevo Equipo</h4>
                </div>
                <div class="card-body">
                  <form method="POST" class="row g-3">
                    <div class="col-md-4">
                      <label for="name" class="form-label"><strong><i class="fa fa-tag"></i> Nombre del Equipo:</strong></label>
                      <input type="text" 
                             name="name" 
                             id="name"
                             class="form-control" 
                             placeholder="Ej: TALADRO PERCUTOR 13MM" 
                             value="<?php echo isset($name_) ? htmlspecialchars($name_) : ''; ?>"
                             style="text-transform: uppercase;"
                             required>
                      <small class="text-muted">Nombre descriptivo del equipo/herramienta</small>
                    </div>
                    
                    <div class="col-md-4">
                      <label for="brand" class="form-label"><strong><i class="fa fa-industry"></i> Marca:</strong></label>
                      <input type="text" 
                             name="brand" 
                             id="brand"
                             class="form-control" 
                             placeholder="Ej: BOSCH" 
                             value="<?php echo isset($brand_) ? htmlspecialchars($brand_) : ''; ?>"
                             style="text-transform: uppercase;"
                             required>
                      <small class="text-muted">Marca o fabricante del equipo</small>
                    </div>
                    
                    <div class="col-md-4">
                      <label for="rfid" class="form-label"><strong><i class="fa fa-credit-card"></i> RFID del Equipo:</strong></label>
                      <input type="text" 
                             name="rfid" 
                             id="rfid"
                             class="form-control" 
                             placeholder="Ej: EQUIP001" 
                             value="<?php echo isset($rfid_) ? htmlspecialchars($rfid_) : ''; ?>"
                             required>
                      <small class="text-muted">Identificador RFID único del equipo</small>
                    </div>
                    
                    <div class="col-md-12">
                      <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa fa-save"></i> Registrar Equipo
                      </button>
                      <button type="reset" class="btn btn-secondary btn-lg ml-2">
                        <i class="fa fa-refresh"></i> Limpiar Formulario
                      </button>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Lista de equipos registrados -->
              <div class="card">
                <div class="card-header">
                  <h4 class="mb-0"><i class="fa fa-list"></i> Equipos Registrados en el Sistema</h4>
                  <small>Inventario completo de equipos disponibles para autopréstamo</small>
                </div>
                <div class="card-body">
                  <?php if (!empty($equipments)): ?>
                    <div class="table-responsive">
                      <table class="table table-striped table-hover" id="equipmentsTable">
                        <thead class="bg-light">
                          <tr>
                            <th><i class="fa fa-hashtag"></i> ID</th>
                            <th><i class="fa fa-tag"></i> NOMBRE DEL EQUIPO</th>
                            <th><i class="fa fa-industry"></i> MARCA</th>
                            <th><i class="fa fa-credit-card"></i> RFID</th>
                            <th><i class="fa fa-info-circle"></i> ESTADO</th>
                            <th><i class="fa fa-cogs"></i> ACCIONES</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($equipments as $equipment): ?>
                            <tr>
                              <td><strong><?php echo htmlspecialchars($equipment['equipments_id']); ?></strong></td>
                              <td>
                                <span class="badge badge-primary p-2">
                                  <i class="fa fa-cube"></i>
                                  <?php echo htmlspecialchars($equipment['equipments_name']); ?>
                                </span>
                              </td>
                              <td>
                                <span class="badge badge-info">
                                  <?php echo htmlspecialchars($equipment['equipments_brand']); ?>
                                </span>
                              </td>
                              <td>
                                <code class="bg-light p-1"><?php echo htmlspecialchars($equipment['equipments_rfid']); ?></code>
                              </td>
                              <td>
                                <span class="badge badge-success">
                                  <i class="fa fa-check-circle"></i> DISPONIBLE
                                </span>
                              </td>
                              <td>
                                <div class="btn-group" role="group">
                                  <a href="/Equipment/edit/<?php echo $equipment['equipments_id']; ?>" 
                                     class="btn btn-sm btn-info" 
                                     title="Editar equipo">
                                    <i class="fa fa-edit"></i> Editar
                                  </a>
                                  
                                  <button class="btn btn-sm btn-warning view-loans" 
                                          data-rfid="<?php echo htmlspecialchars($equipment['equipments_rfid']); ?>"
                                          title="Ver historial de préstamos">
                                    <i class="fa fa-history"></i> Historial
                                  </button>
                                  
                                  <form method="POST" 
                                        style="display: inline;" 
                                        onsubmit="return confirm('¿Estás seguro de eliminar el equipo \'<?php echo htmlspecialchars($equipment['equipments_name']); ?>\'?\n\nEsta acción no se puede deshacer.');">
                                    <input type="hidden" name="id_to_delete" value="<?php echo $equipment['equipments_id']; ?>">
                                    <button type="submit" 
                                            class="btn btn-sm btn-danger"
                                            title="Eliminar equipo">
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
                    
                    <!-- Estadísticas de equipos -->
                    <div class="row mt-4">
                      <div class="col-md-3">
                        <div class="box bg-primary text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-cube fa-2x"></i>
                            <h3><?php echo count($equipments); ?></h3>
                            <p>Total Equipos</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="box bg-success text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-check-circle fa-2x"></i>
                            <h3><?php echo count($equipments); ?></h3>
                            <p>Equipos Disponibles</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="box bg-info text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-industry fa-2x"></i>
                            <h3><?php echo count(array_unique(array_column($equipments, 'equipments_brand'))); ?></h3>
                            <p>Marcas Diferentes</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="box bg-warning text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-credit-card fa-2x"></i>
                            <h3><?php echo count($equipments); ?></h3>
                            <p>RFIDs Asignados</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                  <?php else: ?>
                    <div class="alert alert-warning text-center">
                      <i class="fa fa-exclamation-triangle fa-3x mb-3"></i>
                      <h4>No hay equipos registrados</h4>
                      <p>Registra el primer equipo usando el formulario de arriba.</p>
                      <hr>
                      <p class="mb-0">
                        <small>
                          <strong>Nota:</strong> Cada equipo debe tener un RFID único en el sistema.
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
      
      <!-- Información del proceso -->
      <div class="row">
        <div class="col-md-12">
          <div class="box">
            <div class="box-header">
              <h4><i class="fa fa-info-circle"></i> Información sobre el Registro de Equipos</h4>
            </div>
            <div class="box-body">
              <div class="row">
                <div class="col-md-6">
                  <h5><i class="fa fa-list-ol"></i> Proceso de registro:</h5>
                  <ol>
                    <li><strong>Verificación de RFID:</strong> Se verifica si el RFID ya existe</li>
                    <li><strong>Registro:</strong> Si no existe, se crea el nuevo equipo</li>
                    <li><strong>Disponibilidad:</strong> El equipo queda disponible para autopréstamo</li>
                    <li><strong>Identificación:</strong> Se puede usar para préstamos mediante RFID</li>
                  </ol>
                </div>
                <div class="col-md-6">
                  <h5><i class="fa fa-exclamation-triangle"></i> Consideraciones importantes:</h5>
                  <ul>
                    <li>Cada equipo debe tener un <strong>RFID único</strong></li>
                    <li>Los nombres se guardan en <strong>mayúsculas automáticamente</strong></li>
                    <li>La marca también se guarda en <strong>mayúsculas</strong></li>
                    <li>Los equipos registrados aparecen inmediatamente en el sistema de préstamos</li>
                  </ul>
                </div>
              </div>
              
              <div class="alert alert-info mt-3">
                <i class="fa fa-lightbulb-o"></i>
                <strong>Consejo:</strong> 
                Usa nombres descriptivos y específicos para facilitar la identificación de los equipos durante el proceso de autopréstamo.
                Ejemplo: "TALADRO PERCUTOR 13MM BOSCH" en lugar de solo "TALADRO".
              </div>
              
              <div class="alert alert-warning mt-3">
                <i class="fa fa-warning"></i>
                <strong>Importante:</strong> 
                Los equipos eliminados del sistema no podrán ser prestados. Asegúrate de que realmente quieres eliminar un equipo antes de confirmar la acción.
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
    $('#equipmentsTable tbody tr').each(function(index) {
        $(this).delay(index * 100).fadeIn(500);
    });
    
    // Limpiar formulario después de envío exitoso
    <?php if (isset($message) && strpos($message, 'equipo creado') !== false): ?>
    $('#name').val('');
    $('#brand').val('');
    $('#rfid').val('');
    <?php endif; ?>
    
    // Conversión automática a mayúsculas
    $('#name, #brand').on('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Validación del formulario
    $('form').submit(function(e) {
        var name = $('#name').val().trim();
        var brand = $('#brand').val().trim();
        var rfid = $('#rfid').val().trim();
        
        if (name.length < 3) {
            alert('El nombre del equipo debe tener al menos 3 caracteres');
            e.preventDefault();
            return false;
        }
        
        if (brand.length < 2) {
            alert('La marca debe tener al menos 2 caracteres');
            e.preventDefault();
            return false;
        }
        
        if (rfid.length < 3) {
            alert('El RFID debe tener al menos 3 caracteres');
            e.preventDefault();
            return false;
        }
        
        // Confirmación antes de enviar
        if (!confirm('¿Estás seguro de registrar este equipo?\n\nNombre: ' + name + '\nMarca: ' + brand + '\nRFID: ' + rfid)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Función para ver historial de préstamos
    $('.view-loans').click(function() {
        var rfid = $(this).data('rfid');
        alert('Funcionalidad en desarrollo:\nHistorial de préstamos para RFID: ' + rfid);
        // Aquí se podría implementar una modal o redirección para ver el historial
    });
    
    // Resaltar equipos al pasar mouse
    $('#equipmentsTable tbody tr').hover(
        function() {
            $(this).addClass('table-active');
        },
        function() {
            $(this).removeClass('table-active');
        }
    );
});

// Auto-hide alerts después de 5 segundos
setTimeout(function() {
    $('.alert').fadeOut('slow');
}, 5000);
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?> 