<?php 
$title = "Estadísticas de Uso - SMARTLABS";
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
      <div class="mb-0 h5 no-wrap" id="pageTitle">Estadísticas de Uso de Equipos</div>
      
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
              <h2><i class="fa fa-bar-chart"></i> ESTADÍSTICAS DE USO SMARTLABS</h2>
              <small>Análisis de horas de uso y actividad de dispositivos IoT</small>
            </div>
            <div class="box-body">
              
              <!-- Consulta de usuario por matrícula -->
              <div class="card mb-4">
                <div class="card-header bg-info text-white">
                  <h4 class="mb-0"><i class="fa fa-user-circle"></i> Consultar Usuario por Matrícula</h4>
                </div>
                <div class="card-body">
                  <form id="userForm" method="POST" class="row g-3">
                    <div class="col-md-8">
                      <label for="registration" class="form-label"><strong><i class="fa fa-id-card"></i> Matrícula del Usuario:</strong></label>
                      <input type="text" 
                             name="registration" 
                             id="registration"
                             class="form-control form-control-lg" 
                             placeholder="Ej: L03533767" 
                             value="<?php echo isset($registration_) ? htmlspecialchars($registration_) : ''; ?>"
                             style="text-transform: uppercase;">
                      <small class="text-muted">Ingresa la matrícula para verificar datos del usuario</small>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                      <button type="submit" class="btn btn-info btn-lg w-100">
                        <i class="fa fa-search"></i> Consultar Usuario
                      </button>
                    </div>
                  </form>
                  
                  <!-- Resultado de consulta de usuario -->
                  <div id="userResult" class="mt-3">
                    <?php if (isset($userInfo) && !empty($userInfo)): ?>
                      <div class="alert alert-success">
                        <strong><i class="fa fa-user"></i> Usuario encontrado:</strong> <?php echo $userInfo; ?>
                      </div>
                    <?php elseif (isset($_POST['registration'])): ?>
                      <div class="alert alert-warning">
                        <strong><i class="fa fa-exclamation-triangle"></i> Usuario no encontrado.</strong>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              
              <!-- Filtros para estadísticas -->
              <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                  <h4 class="mb-0"><i class="fa fa-filter"></i> Filtros de Estadísticas</h4>
                </div>
                <div class="card-body">
                  <form method="GET" class="row g-3">
                    <div class="col-md-3">
                      <label for="serie_device" class="form-label"><strong><i class="fa fa-microchip"></i> Dispositivo:</strong></label>
                      <select name="serie_device" id="serie_device" class="form-control" required>
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
                    </div>
                    
                    <div class="col-md-3">
                      <label for="start_date" class="form-label"><strong><i class="fa fa-calendar"></i> Fecha Inicio:</strong></label>
                      <input type="datetime-local" 
                             name="start_date" 
                             id="start_date"
                             class="form-control" 
                             value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>"
                             required>
                    </div>
                    
                    <div class="col-md-3">
                      <label for="end_date" class="form-label"><strong><i class="fa fa-calendar"></i> Fecha Fin:</strong></label>
                      <input type="datetime-local" 
                             name="end_date" 
                             id="end_date"
                             class="form-control" 
                             value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>"
                             required>
                    </div>
                    
                    <div class="col-md-3">
                      <label for="matricula" class="form-label"><strong><i class="fa fa-user"></i> Matrícula (Opcional):</strong></label>
                      <input type="text" 
                             name="matricula" 
                             id="matricula"
                             class="form-control" 
                             placeholder="Ej: L03533767" 
                             value="<?php echo isset($_GET['matricula']) ? htmlspecialchars($_GET['matricula']) : ''; ?>"
                             style="text-transform: uppercase;">
                      <small class="text-muted">Filtrar por usuario específico</small>
                    </div>
                    
                    <div class="col-md-12">
                      <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-chart-line"></i> Generar Estadísticas
                      </button>
                      <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="clearFilters()">
                        <i class="fa fa-refresh"></i> Limpiar Filtros
                      </button>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Resultados de estadísticas -->
              <?php if (!empty($usersTrafficDevice)): ?>
                <?php
                // Inicializar variables antes de usar
                $totalTime = 0;
                $sessionCount = 0;
                
                // CALCULAR TIEMPO TOTAL ANTES DE MOSTRAR
                // Ordenar todos los registros por fecha
                usort($usersTrafficDevice, function($a, $b) {
                    return strtotime($a['traffic_date']) - strtotime($b['traffic_date']);
                });
                
                // Procesar registros para calcular tiempo total
                $currentEntry = null;
                foreach ($usersTrafficDevice as $traffic) {
                    if ($traffic['traffic_state'] == 1) {
                        // SE PRENDE - guardar tiempo de inicio
                        $currentEntry = $traffic;
                    } else {
                        // SE APAGA - calcular duración si hay entrada previa
                        if ($currentEntry !== null) {
                            $timeStart = new DateTime($currentEntry['traffic_date']);
                            $timeEnd = new DateTime($traffic['traffic_date']);
                            $diff = $timeStart->diff($timeEnd);
                            
                            $totalMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
                            $totalSeconds = $diff->s;
                            
                            if ($totalMinutes >= 1 || $totalSeconds >= 30) {
                                $totalTime += $totalMinutes;
                                $sessionCount++;
                            }
                            
                            $currentEntry = null;
                        }
                    }
                }
                ?>
                <!-- Resumen ejecutivo -->
                <div class="card mb-3">
                  <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="fa fa-dashboard"></i> Resumen Ejecutivo</h4>
                    <small>Vista rápida de la actividad del dispositivo</small>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-4">
                        <div class="card bg-primary text-white">
                          <div class="card-body text-center">
                            <h2><i class="fa fa-users"></i> <?php 
                              $uniqueUsers = array_unique(array_column($usersTrafficDevice, 'traffic_hab_id'));
                              echo count($uniqueUsers); 
                            ?></h2>
                            <p class="mb-0">Usuarios Únicos</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="card bg-info text-white">
                          <div class="card-body text-center">
                            <h2><i class="fa fa-exchange"></i> <?php echo count($usersTrafficDevice); ?></h2>
                            <p class="mb-0">Total de Accesos</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="card bg-success text-white">
                          <div class="card-body text-center">
                            <h2><i class="fa fa-clock-o"></i> <span id="totalTimePreview"><?php 
                              if (!empty($usersTrafficDevice)) {
                                  echo intval($totalTime / 60) . 'h ' . intval($totalTime % 60) . 'm';
                              } else {
                                  echo '0h 0m';
                              }
                            ?></span></h2>
                            <p class="mb-0">Tiempo Total de Uso</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="card">
                  <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fa fa-chart-bar"></i> Detalle de Registros</h4>
                    <small>
                      Dispositivo: <strong><?php echo htmlspecialchars($_GET['serie_device']); ?></strong> | 
                      Periodo: <?php echo date('d/m/Y H:i', strtotime($_GET['start_date'])); ?> - <?php echo date('d/m/Y H:i', strtotime($_GET['end_date'])); ?>
                      <?php if (!empty($_GET['matricula'])): ?>
                        | Usuario: <strong><?php echo htmlspecialchars($_GET['matricula']); ?></strong>
                      <?php endif; ?>
                    </small>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-striped table-hover" id="statsTable">
                        <thead class="bg-light">
                          <tr>
                            <th><i class="fa fa-calendar"></i> FECHA/HORA</th>
                            <th><i class="fa fa-user"></i> USUARIO</th>
                            <th><i class="fa fa-id-card"></i> MATRÍCULA</th>
                            <th><i class="fa fa-envelope"></i> EMAIL</th>
                            <th><i class="fa fa-exchange"></i> ACCIÓN</th>
                            <th><i class="fa fa-microchip"></i> DISPOSITIVO</th>
                            <th><i class="fa fa-clock-o"></i> DURACIÓN</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                          // ============================================
                          // LÓGICA SIMPLE: ENCENDIDO → APAGADO = DURACIÓN
                          // ============================================
                          
                          $totalTime = 0; // En minutos
                          $sessionCount = 0;
                          $trafficWithDurations = [];
                          
                          // PASO 1: Ordenar todos los registros por fecha
                          usort($usersTrafficDevice, function($a, $b) {
                              return strtotime($a['traffic_date']) - strtotime($b['traffic_date']);
                          });
                          
                          // PASO 2: Procesar registros de forma secuencial
                          $currentEntry = null; // Guardar el último ENCENDIDO
                          
                          foreach ($usersTrafficDevice as $traffic) {
                              $duration = '';
                              $totalMinutes = 0;
                              
                              if ($traffic['traffic_state'] == 1) {
                                  // SE PRENDE - guardar tiempo de inicio
                                  $currentEntry = $traffic;
                                  $traffic['calculated_duration'] = '';
                                  $traffic['duration_minutes'] = 0;
                              } else {
                                  // SE APAGA - calcular duración si hay entrada previa
                                  if ($currentEntry !== null) {
                                      $timeStart = new DateTime($currentEntry['traffic_date']);
                                      $timeEnd = new DateTime($traffic['traffic_date']);
                                      $diff = $timeStart->diff($timeEnd);
                                      
                                      // Calcular duración total en minutos SIMPLE
                                      $totalMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
                                      $totalSeconds = $diff->s;
                                      
                                      if ($totalMinutes >= 1 || $totalSeconds >= 30) { // Al menos 30 segundos
                                          $hours = intval($totalMinutes / 60);
                                          $minutes = $totalMinutes % 60;
                                          $duration = sprintf('%02d:%02d:%02d', 
                                                            $hours, 
                                                            $minutes, 
                                                            $totalSeconds);
                                          
                                          // SUMAR al tiempo total - SOLO MINUTOS ENTEROS
                                          $totalTime += $totalMinutes;
                                          $sessionCount++;
                                      }
                                      
                                      // Actualizar la entrada anterior con la duración calculada
                                      if (count($trafficWithDurations) > 0) {
                                          $lastIndex = count($trafficWithDurations) - 1;
                                          if ($trafficWithDurations[$lastIndex]['traffic_state'] == 1) {
                                              $trafficWithDurations[$lastIndex]['calculated_duration'] = $duration;
                                              $trafficWithDurations[$lastIndex]['duration_minutes'] = $totalMinutes;
                                          }
                                      }
                                      
                                      $currentEntry = null; // Resetear entrada actual
                                  }
                                  
                                  $traffic['calculated_duration'] = $duration;
                                  $traffic['duration_minutes'] = $totalMinutes;
                              }
                              
                              $trafficWithDurations[] = $traffic;
                          }
                          
                          // PASO 3: Mostrar los resultados
                          foreach ($trafficWithDurations as $index => $traffic): 
                          ?>
                            <tr class="<?php echo $traffic['traffic_state'] ? 'table-success' : 'table-warning'; ?>">
                              <td>
                                <strong><?php echo date('d/m/Y', strtotime($traffic['traffic_date'])); ?></strong><br>
                                <small class="text-muted"><?php echo date('H:i:s', strtotime($traffic['traffic_date'])); ?></small>
                              </td>
                              <td>
                                <strong><?php echo htmlspecialchars($traffic['hab_name']); ?></strong>
                                <?php if (!empty($traffic['calculated_duration'])): ?>
                                  <br><small class="badge badge-success">Sesión completa</small>
                                <?php elseif ($traffic['traffic_state']): ?>
                                  <br><small class="badge badge-warning">Sesión abierta</small>
                                <?php else: ?>
                                  <br><small class="badge badge-secondary">Sin entrada</small>
                                <?php endif; ?>
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
                              <td>
                                <?php if (!empty($traffic['calculated_duration'])): ?>
                                  <strong class="text-success">
                                    <i class="fa fa-clock-o"></i> <?php echo $traffic['calculated_duration']; ?>
                                  </strong>
                                <?php elseif ($traffic['traffic_state']): ?>
                                  <span class="text-primary">
                                    <i class="fa fa-hourglass-start"></i> Sesión abierta
                                  </span>
                                <?php else: ?>
                                  <span class="text-muted">
                                    <i class="fa fa-minus"></i> Sin entrada previa
                                  </span>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    
                    <!-- Resumen de estadísticas -->
                    <div class="row mt-4">
                      <div class="col-md-3">
                        <div class="box bg-primary text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-list fa-2x"></i>
                            <h3><?php echo count($usersTrafficDevice); ?></h3>
                            <p>Total Registros</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="box bg-success text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-sign-in fa-2x"></i>
                            <h3><?php echo count(array_filter($usersTrafficDevice, function($t) { return $t['traffic_state']; })); ?></h3>
                            <p>Entradas</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="box bg-warning text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-sign-out fa-2x"></i>
                            <h3><?php echo count(array_filter($usersTrafficDevice, function($t) { return !$t['traffic_state']; })); ?></h3>
                            <p>Salidas</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="box bg-info text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-clock-o fa-2x"></i>
                            <h3><?php 
                              $totalHours = intval(floor($totalTime / 60));
                              $totalMins = intval(round($totalTime % 60));
                              echo $totalHours . 'h ' . $totalMins . 'm'; 
                            ?></h3>
                            <p>Tiempo Total de Uso</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="alert alert-info mt-3">
                      <i class="fa fa-info-circle"></i>
                      <strong>Información:</strong>
                      Se encontraron <strong><?php echo $sessionCount; ?> sesiones completas</strong> de uso del dispositivo.
                      El tiempo total de uso calculado es de <strong><?php echo intval(floor($totalTime / 60)); ?> horas y <?php echo intval(round($totalTime % 60)); ?> minutos</strong>.
                      <br><small class="text-muted">
                        <i class="fa fa-lightbulb-o"></i> 
                        <strong>Nota:</strong> Solo se contabilizan sesiones con entrada y salida válidas. 
                        Las sesiones abiertas (sin salida) no se incluyen en el tiempo total.
                      </small>
                    </div>
                  </div>
                </div>
                
              <?php elseif (isset($_GET['serie_device']) && !empty($_GET['serie_device'])): ?>
                <?php
                // Inicializar variables cuando no hay datos
                $totalTime = 0;
                $sessionCount = 0;
                ?>
                <div class="alert alert-warning">
                  <i class="fa fa-exclamation-triangle"></i>
                  <strong>Sin datos</strong><br>
                  No se encontraron registros para los filtros aplicados.
                </div>
              <?php else: ?>
                <?php
                // Inicializar variables por defecto
                $totalTime = 0;
                $sessionCount = 0;
                ?>
                <div class="alert alert-info text-center">
                  <i class="fa fa-info-circle fa-2x mb-2"></i><br>
                  <strong>Generador de Estadísticas SMARTLABS</strong><br>
                  Aplica los filtros arriba para generar estadísticas de uso de dispositivos.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Panel de ayuda -->
      <div class="row">
        <div class="col-md-12">
          <div class="box">
            <div class="box-header">
              <h4><i class="fa fa-question-circle"></i> Guía de Uso</h4>
            </div>
            <div class="box-body">
              <div class="row">
                <div class="col-md-6">
                  <h5><i class="fa fa-list-ol"></i> Pasos para generar estadísticas:</h5>
                  <ol>
                    <li><strong>Selecciona un dispositivo:</strong> Elige el dispositivo del cual quieres ver estadísticas</li>
                    <li><strong>Define el periodo:</strong> Selecciona fecha y hora de inicio y fin</li>
                    <li><strong>Filtro opcional:</strong> Puedes filtrar por un usuario específico usando su matrícula</li>
                    <li><strong>Genera reporte:</strong> Presiona "Generar Estadísticas" para ver los resultados</li>
                  </ol>
                </div>
                <div class="col-md-6">
                  <h5><i class="fa fa-lightbulb-o"></i> Información sobre los datos:</h5>
                  <ul>
                    <li><strong>Entradas y Salidas:</strong> Se muestran todos los accesos al dispositivo</li>
                    <li><strong>Duración:</strong> Se calcula automáticamente entre entrada y salida</li>
                    <li><strong>Usuarios únicos:</strong> Se pueden identificar todos los usuarios que usaron el equipo</li>
                    <li><strong>Tiempo total:</strong> Suma de todas las sesiones de uso en el periodo</li>
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
    // Auto-mayúsculas para matrículas
    $('#registration, #matricula').on('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Consulta AJAX para verificar usuario
    $('#userForm').submit(function(e) {
        e.preventDefault();
        
        var registration = $('#registration').val().trim();
        
        if (registration === '') {
            alert('Por favor ingresa una matrícula');
            return false;
        }
        
        $.ajax({
            url: '/Stats/index',
            type: 'POST',
            data: {
                registration: registration
            },
            success: function(response) {
                $('#userResult').html('<div class="alert alert-success"><strong>Resultado:</strong> ' + response + '</div>');
            },
            error: function() {
                $('#userResult').html('<div class="alert alert-danger"><strong>Error:</strong> No se pudo consultar el usuario.</div>');
            }
        });
    });
    
    // Animar tabla de resultados
    $('#statsTable tbody tr').each(function(index) {
        $(this).delay(index * 50).fadeIn(300);
    });
    
    // Validación de fechas
    $('form[method="GET"]').submit(function(e) {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        var device = $('#serie_device').val();
        
        if (!device) {
            alert('Por favor selecciona un dispositivo');
            e.preventDefault();
            return false;
        }
        
        if (!startDate || !endDate) {
            alert('Por favor selecciona fecha de inicio y fin');
            e.preventDefault();
            return false;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('La fecha de inicio no puede ser mayor que la fecha de fin');
            e.preventDefault();
            return false;
        }
    });
    
    // Establecer fechas por defecto (último mes)
    if (!$('#start_date').val()) {
        var now = new Date();
        var oneMonthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
        
        $('#start_date').val(oneMonthAgo.toISOString().slice(0, 16));
        $('#end_date').val(now.toISOString().slice(0, 16));
    }
    
    // Tiempo total se muestra directamente desde PHP
});

function clearFilters() {
    $('#serie_device').val('');
    $('#start_date').val('');
    $('#end_date').val('');
    $('#matricula').val('');
    $('#userResult').html('');
}

// Auto-hide user result después de 5 segundos
setTimeout(function() {
    $('#userResult .alert').fadeOut('slow');
}, 5000);
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?> 