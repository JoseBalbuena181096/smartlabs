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
                            <th><i class="fa fa-cogs"></i> ACCIONES</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($devices as $device): ?>
                            <tr id="row-<?php echo $device['devices_id']; ?>">
                              <td><strong><?php echo htmlspecialchars($device['devices_id']); ?></strong></td>
                              <td>
                                <!-- Vista normal -->
                                <span class="badge badge-info p-2 view-mode" id="alias-view-<?php echo $device['devices_id']; ?>">
                                  <?php echo htmlspecialchars($device['devices_alias']); ?>
                                </span>
                                <!-- Vista edición -->
                                <input type="text" 
                                       class="form-control edit-mode" 
                                       id="alias-edit-<?php echo $device['devices_id']; ?>"
                                       value="<?php echo htmlspecialchars($device['devices_alias']); ?>"
                                       style="display: none;">
                              </td>
                              <td>
                                <!-- Vista normal -->
                                <code class="bg-light p-1 view-mode" id="serie-view-<?php echo $device['devices_id']; ?>">
                                  <?php echo htmlspecialchars($device['devices_serie']); ?>
                                </code>
                                <!-- Vista edición -->
                                <input type="text" 
                                       class="form-control edit-mode" 
                                       id="serie-edit-<?php echo $device['devices_id']; ?>"
                                       value="<?php echo htmlspecialchars($device['devices_serie']); ?>"
                                       style="display: none;">
                              </td>
                              <td>
                                <strong><?php echo date('d/m/Y', strtotime($device['devices_date'])); ?></strong><br>
                                <small class="text-muted"><?php echo date('H:i:s', strtotime($device['devices_date'])); ?></small>
                              </td>
                              <td>
                                <div class="btn-group" role="group">
                                  <!-- Botones modo vista -->
                                  <button type="button" 
                                          class="btn btn-sm btn-info view-mode" 
                                          onclick="enableEdit(<?php echo $device['devices_id']; ?>)"
                                          title="Editar dispositivo">
                                    <i class="fa fa-edit"></i> Editar
                                  </button>
                                  
                                  <!-- Botones modo edición -->
                                  <button type="button" 
                                          class="btn btn-sm btn-success edit-mode" 
                                          onclick="saveEdit(<?php echo $device['devices_id']; ?>)"
                                          style="display: none;"
                                          title="Guardar cambios">
                                    <i class="fa fa-save"></i> Guardar
                                  </button>
                                  
                                  <button type="button" 
                                          class="btn btn-sm btn-secondary edit-mode" 
                                          onclick="cancelEdit(<?php echo $device['devices_id']; ?>)"
                                          style="display: none;"
                                          title="Cancelar edición">
                                    <i class="fa fa-times"></i> Cancelar
                                  </button>
                                  
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



  
<!-- Estilos para edición inline y tabla más grande -->
<style>
/* Hacer la tabla más grande y visible */
#devicesTable {
    font-size: 16px;
}

#devicesTable th {
    font-size: 18px;
    font-weight: bold;
    padding: 15px 12px;
    text-align: center;
}

#devicesTable td {
    padding: 15px 12px;
    vertical-align: middle;
    text-align: center;
}

/* Hacer los badges más grandes */
#devicesTable .badge {
    font-size: 14px;
    padding: 8px 12px;
    font-weight: 600;
}

/* Hacer los códigos más grandes */
#devicesTable code {
    font-size: 14px;
    padding: 6px 10px;
    font-weight: 600;
}

/* Hacer los números ID más grandes */
#devicesTable td strong {
    font-size: 18px;
    font-weight: bold;
}

/* Hacer las fechas más grandes */
#devicesTable td strong,
#devicesTable .text-muted {
    font-size: 14px;
}

/* Botones más grandes */
#devicesTable .btn {
    font-size: 14px;
    padding: 8px 12px;
    font-weight: 600;
}

/* Inputs de edición más grandes */
.edit-mode input {
    min-width: 180px;
    font-size: 16px;
    padding: 10px 12px;
    font-weight: 600;
}

#devicesTable .edit-mode {
    margin: 2px 0;
}

.btn-group .edit-mode {
    margin-left: 5px;
}

/* Highlighting para fila en edición */
tr.editing {
    background-color: #f8f9fa !important;
    border: 2px solid #007bff;
}

/* Espaciado entre botones */
.btn-group .btn {
    margin-right: 5px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Hacer las filas más altas */
#devicesTable tbody tr {
    min-height: 60px;
}
</style>

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
    
    // Ya no necesitamos event listeners aquí, usamos onclick directo
});

// Auto-hide alerts después de 5 segundos
setTimeout(function() {
    $('.alert').fadeOut('slow');
}, 5000);



// Funciones para edición inline
function enableEdit(deviceId) {
    // Ocultar elementos de vista
    var row = document.getElementById('row-' + deviceId);
    var viewElements = row.querySelectorAll('.view-mode');
    var editElements = row.querySelectorAll('.edit-mode');
    
    // Agregar clase de edición a la fila
    row.classList.add('editing');
    
    viewElements.forEach(function(element) {
        element.style.display = 'none';
    });
    
    editElements.forEach(function(element) {
        element.style.display = 'inline-block';
    });
    
    // Enfocar el primer campo de edición
    document.getElementById('alias-edit-' + deviceId).focus();
}

function cancelEdit(deviceId) {
    // Mostrar elementos de vista y ocultar elementos de edición
    var row = document.getElementById('row-' + deviceId);
    var viewElements = row.querySelectorAll('.view-mode');
    var editElements = row.querySelectorAll('.edit-mode');
    
    // Quitar clase de edición de la fila
    row.classList.remove('editing');
    
    viewElements.forEach(function(element) {
        element.style.display = 'inline-block';
    });
    
    editElements.forEach(function(element) {
        element.style.display = 'none';
    });
    
    // Restaurar valores originales
    var aliasView = document.getElementById('alias-view-' + deviceId).textContent.trim();
    var serieView = document.getElementById('serie-view-' + deviceId).textContent.trim();
    
    document.getElementById('alias-edit-' + deviceId).value = aliasView;
    document.getElementById('serie-edit-' + deviceId).value = serieView;
}

function saveEdit(deviceId) {
    var alias = document.getElementById('alias-edit-' + deviceId).value.trim();
    var serie = document.getElementById('serie-edit-' + deviceId).value.trim();
    
    // Validaciones básicas
    if (alias.length < 3) {
        alert('El alias debe tener al menos 3 caracteres');
        document.getElementById('alias-edit-' + deviceId).focus();
        return;
    }
    
    if (serie.length < 3) {
        alert('El número de serie debe tener al menos 3 caracteres');
        document.getElementById('serie-edit-' + deviceId).focus();
        return;
    }
    
    // Deshabilitar botones durante la actualización
    var saveBtn = document.querySelector('#row-' + deviceId + ' .btn-success');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Guardando...';
    
    // Enviar datos al servidor
    fetch('/Device/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'device_id=' + encodeURIComponent(deviceId) + 
              '&alias=' + encodeURIComponent(alias) + 
              '&serie=' + encodeURIComponent(serie)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar los elementos de vista con los nuevos valores
            document.getElementById('alias-view-' + deviceId).textContent = alias;
            document.getElementById('serie-view-' + deviceId).textContent = serie;
            
            // Quitar clase de edición
            document.getElementById('row-' + deviceId).classList.remove('editing');
            
            // Volver al modo vista
            cancelEdit(deviceId);
            
            // Mostrar mensaje de éxito
            alert('Dispositivo actualizado correctamente');
        } else {
            alert('Error: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        alert('Error al conectar con el servidor: ' + error.message);
    })
    .finally(() => {
        // Restaurar botón
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fa fa-save"></i> Guardar';
    });
}

</script>

<?php include __DIR__ . '/../layout/footer.php'; ?> 