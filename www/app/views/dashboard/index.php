<?php 
$title = "Dashboard - SMARTLABS";
include __DIR__ . '/../layout/header.php'; 
?>

<style>
/* Estilos para el dashboard */
.dashboard-buttons {
  margin-bottom: 20px;
}

.dashboard-buttons .btn {
  margin: 5px;
  min-width: 120px;
}

.temperature-panel {
  background: linear-gradient(45deg, #ff6b6b, #ee5a52);
  color: white;
  border-radius: 8px;
}

.mqtt-controls {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

#trafficTable tbody tr {
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.3s ease;
}

#trafficTable tbody tr.visible {
  opacity: 1;
  transform: translateY(0);
}

.alert-success {
  background-color: #d4edda;
  border-color: #c3e6cb;
  color: #155724;
}

.alert-warning {
  background-color: #fff3cd;
  border-color: #ffeaa7;
  color: #856404;
}

.status-indicator {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  margin-right: 5px;
}

.status-online {
  background-color: #28a745;
}

.status-offline {
  background-color: #dc3545;
}

.device-control-panel {
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 15px;
  background: #ffffff;
}

.btn-power-on {
  background-color: #28a745;
  border-color: #28a745;
}

.btn-power-off {
  background-color: #dc3545;
  border-color: #dc3545;
}

.btn-power-on:hover {
  background-color: #218838;
  border-color: #1e7e34;
}

.btn-power-off:hover {
  background-color: #c82333;
  border-color: #bd2130;
}

#display_new_access {
  font-weight: bold;
  color: #28a745;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.5; }
  100% { opacity: 1; }
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
      
      <div class="">
        <span id="mqtt_status" class="status-indicator status-offline"></span>
        <small id="mqtt_status_text">MQTT: Desconectado</small>
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
      
      <!-- Panel de Control MQTT -->
      <div class="row">
        <div class="col-xs-12 col-sm-9">
          <div class="box p-a mqtt-controls">
            <div class="box-header">
              <h2>Control de Dispositivos MQTT</h2>
              <small>Control remoto de dispositivos IoT</small>
            </div>
            <div class="form-group row dashboard-buttons">
              <div class="col-md-4">
                <button onclick="command('open')" class="btn btn-power-on btn-block mb-2">
                  <i class="fa fa-power-off"></i> ENCENDER
                </button>
              </div>
              <div class="col-md-4">
                <button onclick="command('close')" class="btn btn-power-off btn-block mb-2">
                  <i class="fa fa-power-off"></i> APAGAR
                </button>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="device_id">Dispositivo:</label>
                  <select id="device_id" class="form-control">
                    <?php if (!empty($devices)): ?>
                      <?php foreach ($devices as $device): ?>
                        <option value="<?php echo htmlspecialchars($device['devices_serie']); ?>">
                          <?php echo htmlspecialchars($device['devices_alias']); ?>
                        </option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>
              </div>
            </div>
            
            <!-- Formulario de filtro por dispositivo -->
            <div class="form-group">
              <form method="GET" class="form-inline">
                <input name="serie_device" id="serie_device" type="text" class="form-control mr-2" 
                       placeholder="Número de serie del dispositivo" required>
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-search"></i> VERIFICAR TRÁFICO
                </button>
              </form>
            </div>
          </div>
        </div>
        
        <!-- Panel de Temperatura -->
        <div class="col-xs-12 col-sm-3">
          <div class="box p-a temperature-panel">
            <div class="pull-left m-r">
              <span class="w-48 rounded accent">
                <i class="fa fa-thermometer-half"></i>
              </span>
            </div>
            <div class="clear">
              <h4 class="m-0 text-lg _300">
                <b id="display_temp1">--</b>
                <span class="text-sm"> °C</span>
              </h4>
              <small class="text-muted">TEMPERATURA ESP32</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel de Tráfico de Usuarios -->
      <div class="row">
        <div class="col-sm-12">
          <div class="box">
            <div class="box-header">
              <h2>Tráfico de Usuarios</h2>
              <small>
                Monitoreo de acceso de usuarios - <?php echo $_SESSION['user_email'] ?? 'Usuario'; ?>
              </small>
            </div>
            
            <?php if (!empty($usersTrafficDevice)): ?>
              <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> 
                <strong>Dispositivo:</strong> <?php echo htmlspecialchars($_GET['serie_device'] ?? 'No especificado'); ?>
                | <strong>Últimos 12 registros</strong> | 
                <small>Actualización automática vía MQTT</small>
              </div>
              
              <div class="table-responsive">
                <table class="table table-striped b-t" id="trafficTable">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>FECHA</th>
                      <th>NOMBRE</th>
                      <th>REGISTRO</th>
                      <th>EMAIL</th>
                      <th>DISPOSITIVO</th>
                      <th>ESTADO</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($usersTrafficDevice as $traffic): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($traffic['traffic_id'] ?? $traffic['id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($traffic['traffic_date'] ?? $traffic['date'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($traffic['hab_name'] ?? $traffic['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($traffic['hab_registration'] ?? $traffic['registration'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($traffic['hab_email'] ?? $traffic['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($traffic['traffic_device'] ?? $traffic['device'] ?? ''); ?></td>
                        <td>
                          <?php if ($traffic['traffic_state']): ?>
                            <span class="badge badge-success">ACTIVO</span>
                          <?php else: ?>
                            <span class="badge badge-warning">INACTIVO</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              
            <?php elseif (isset($_GET['serie_device']) && !empty($_GET['serie_device'])): ?>
              <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i> 
                <strong>Sin registros</strong><br>
                No se encontraron registros para el dispositivo <strong><?php echo htmlspecialchars($_GET['serie_device']); ?></strong>
              </div>
            <?php else: ?>
              <div class="alert alert-info text-center">
                <i class="fa fa-info-circle fa-2x mb-2"></i><br>
                <strong>Bienvenido al Sistema SMARTLABS</strong><br>
                Selecciona un dispositivo para monitorear el tráfico en tiempo real.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Panel de Estadísticas -->
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
              <i class="fa fa-exchange fa-2x"></i>
              <h3><?php echo isset($stats['totalAccess']) ? $stats['totalAccess'] : 0; ?></h3>
              <p>Accesos Totales</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="box bg-success text-white">
            <div class="box-body text-center">
              <i class="fa fa-calendar fa-2x"></i>
              <h3><?php echo isset($stats['todayAccess']) ? $stats['todayAccess'] : 0; ?></h3>
              <p>Accesos Hoy</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="box bg-warning text-white">
            <div class="box-body text-center">
              <i class="fa fa-users fa-2x"></i>
              <h3><?php echo isset($stats['uniqueUsers']) ? $stats['uniqueUsers'] : 0; ?></h3>
              <p>Usuarios Únicos</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Scripts MQTT y funcionalidades -->
<script src="https://unpkg.com/mqtt@4.3.7/dist/mqtt.min.js"></script>
<script src="<?php echo '/public/js/navigation.js'; ?>"></script>
<script src="<?php echo '/public/js/dashboard-legacy.js'; ?>"></script>
<script>
// Test inmediato después de cargar dashboard-legacy.js
console.log('🔧 Verificando funciones inmediatamente después de cargar dashboard-legacy.js');
console.log('🔧 window.command:', typeof window.command);
console.log('🔧 command:', typeof command);

// Test funcional del comando
function testCommand() {
  console.log('🧪 Probando función command...');
  if (typeof window.command === 'function') {
    console.log('✅ window.command está disponible');
    return true;
  } else {
    console.error('❌ window.command NO está disponible');
    return false;
  }
}

// Ejecutar test
window.testCommand = testCommand;
testCommand();

// Backup global de command para asegurar que esté disponible
setTimeout(() => {
  if (typeof window.command === 'function') {
    // Hacer backup global explícito
    window.command_backup = window.command;
    
    // Usar eval para crear función global (último recurso)
    eval(`
      function command(action) {
        console.log('🔧 Usando función command global');
        if (typeof window.command === 'function') {
          return window.command(action);
        } else {
          console.error('❌ window.command no está disponible');
        }
      }
    `);
    
    console.log('✅ Función command disponible globalmente via eval');
  } else {
    console.error('❌ No se puede crear backup de command');
  }
}, 100);
</script>
<script>
// Pasar datos de dispositivos a JavaScript
window.userDevices = <?php echo json_encode($devices); ?>;

// Evitar conflictos de declaraciones - usar window namespace
window.client = null;
window.mqttConnected = false;
window.audio = null;

// Inicializar audio legacy
try {
  window.audio = new Audio('/public/audio/audio.mp3');
  window.audio.preload = 'auto';
} catch (e) {
  console.log('No se pudo cargar el audio legacy:', e);
}

// Funciones disponibles desde dashboard-legacy.js
// - command(action)
// - process_msg(topic, message)
// - initializeMQTT()
// - updateMqttStatus(connected, message)
// - syncSerieInput()
// - updateDashboardStats()
// - showNotification(message, type)

// Inicializar compatibilidad con versiones anteriores
document.addEventListener('DOMContentLoaded', function() {
  console.log('Dashboard MVC inicializado con funcionalidades legacy');
  
  // Verificar que las funciones globales estén disponibles
  if (typeof command === 'function') {
    console.log('✓ Función command() disponible');
  } else {
    console.error('✗ Función command() NO disponible');
  }
  
  if (typeof window.command === 'function') {
    console.log('✓ window.command() disponible');
  } else {
    console.error('✗ window.command() NO disponible');
  }
  
  if (window.DashboardLegacy) {
    console.log('✓ DashboardLegacy module cargado');
  } else {
    console.error('✗ DashboardLegacy module NO cargado');
  }
  
  if (window.userDevices) {
    console.log('✓ Dispositivos de usuario:', window.userDevices.length);
  } else {
    console.error('✗ Dispositivos de usuario NO disponibles');
  }
  
  // Verificar todas las funciones necesarias
  const functionsToCheck = [
    'command',
    'process_msg',
    'initializeMQTT',
    'updateMqttStatus',
    'syncSerieInput',
    'updateDashboardStats',
    'showNotification',
    'generarCadenaAleatoria',
    'initAudio',
    'setupAutoRefresh'
  ];
  
  functionsToCheck.forEach(funcName => {
    if (typeof window[funcName] === 'function') {
      console.log(`✓ window.${funcName}() disponible`);
    } else {
      console.error(`✗ window.${funcName}() NO disponible`);
    }
  });
  
  // Animaciones para la tabla si está disponible
  const trafficTable = document.getElementById('trafficTable');
  if (trafficTable) {
    const rows = trafficTable.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
      setTimeout(() => {
        row.classList.add('visible');
      }, index * 100);
    });
  }
  
  // Test final: verificar que los botones funcionen
  const openButton = document.querySelector('button[onclick="command(\'open\')"]');
  const closeButton = document.querySelector('button[onclick="command(\'close\')"]');
  
  if (openButton && closeButton) {
    console.log('✓ Botones de control encontrados');
    
    // Test funcional
    if (typeof window.command === 'function') {
      console.log('✓ Test funcional: window.command() está disponible para los botones');
      
      // Reemplazar los onclick handlers directamente
      openButton.onclick = function(e) {
        console.log('🔧 onclick handler: intentando ejecutar command("open")');
        if (typeof window.command === 'function') {
          window.command('open');
        } else {
          console.error('❌ window.command no está disponible en onclick handler');
        }
      };
      
      closeButton.onclick = function(e) {
        console.log('🔧 onclick handler: intentando ejecutar command("close")');
        if (typeof window.command === 'function') {
          window.command('close');
        } else {
          console.error('❌ window.command no está disponible en onclick handler');
        }
      };
      
      console.log('✅ onclick handlers reemplazados correctamente');
      
    } else {
      console.error('✗ Test funcional: window.command() NO está disponible para los botones');
    }
  } else {
    console.error('✗ Botones de control NO encontrados');
  }
  
  // Verificación final de la función command
  if (typeof window.command === 'function') {
    console.log('✅ FINAL: window.command está disponible');
  } else {
    console.error('❌ FINAL: window.command NO está disponible');
    
    // Crear función command como último recurso
    window.command = function(action) {
      console.log('🚨 Usando función command de emergencia');
      if (window.DashboardLegacy && typeof window.DashboardLegacy.command === 'function') {
        return window.DashboardLegacy.command(action);
      } else {
        alert('Error: Función command no está disponible. Verifique la consola.');
      }
    };
    
    console.log('✅ Función command de emergencia creada');
  }
  
  console.log('✅ Dashboard MVC completamente inicializado');
});

// Fallback para jQuery si está disponible
if (typeof $ !== 'undefined') {
  $(document).ready(function() {
    $('#trafficTable tbody tr').each(function(index) {
      $(this).delay(index * 100).fadeIn(500);
    });
  });
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?> 