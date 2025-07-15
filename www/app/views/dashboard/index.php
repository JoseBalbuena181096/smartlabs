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

/* Estilos para el indicador de estado del dispositivo */
.device-status-panel {
  background: linear-gradient(45deg, #667eea, #764ba2);
  color: white;
  border-radius: 8px;
}

.device-status-indicator {
  margin-left: 10px;
  display: inline-block;
}

.device-status-indicator.status-on {
  color: #28a745 !important;
  animation: pulse 2s infinite;
}

.device-status-indicator.status-off {
  color: #dc3545 !important;
}

.device-status-indicator.status-unknown {
  color: #ffc107 !important;
}

.device-status-indicator i {
  font-size: 14px;
}

#device-status-icon.status-on {
  background-color: #28a745 !important;
}

#device-status-icon.status-off {
  background-color: #dc3545 !important;
}

#device-status-icon.status-unknown {
  background-color: #ffc107 !important;
}

#device-last-activity {
  display: block;
  margin-top: 2px;
  font-size: 10px;
  opacity: 0.8;
}

.device-status-panel .w-48 {
  transition: all 0.3s ease;
}

.device-status-panel:hover .w-48 {
  transform: scale(1.1);
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
                        <option value="<?php echo htmlspecialchars($device['devices_serie']); ?>" 
                                <?php echo ($selectedDevice === $device['devices_serie']) ? 'selected' : ''; ?>>
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
      
      <!-- Panel de Estado del Dispositivo -->
      <div class="row">
        <div class="col-xs-12">
          <div class="box p-a device-status-panel">
            <div class="pull-left m-r">
              <span class="w-48 rounded" id="device-status-icon">
                <i class="fa fa-microchip"></i>
              </span>
            </div>
            <div class="clear">
              <h4 class="m-0 text-lg _300">
                <span id="device-status-text">Sin seleccionar</span>
                <span class="device-status-indicator" id="device-status-indicator">
                  <i class="fa fa-circle text-muted"></i>
                </span>
              </h4>
              <small class="text-muted">
                ESTADO DEL DISPOSITIVO
                <span id="device-last-activity" class="text-xs"></span>
              </small>
              <div id="device-user-info" class="mt-1" style="display: block;">
                <small class="text-light">
                  <i class="fa fa-user"></i> <span id="device-user-name">Sin usuario</span><br>
                  <i class="fa fa-id-card"></i> <span id="device-user-registration">---</span><br>
                  <i class="fa fa-envelope"></i> <span id="device-user-email">---</span>
                </small>
              </div>
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
<script src="<?php echo '/public/js/device-status-config.js'; ?>"></script>
<script src="<?php echo '/public/js/dashboard-legacy.js'; ?>"></script>
<script src="<?php echo '/public/js/device-status-monitor.js'; ?>"></script>
<script src="<?php echo '/public/js/device-status-websocket.js'; ?>"></script>
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

// Función para probar el sistema de estado
function testStateUpdate() {
  const deviceId = document.getElementById('device_id').value;
  if (!deviceId) {
    alert('Por favor seleccione un dispositivo');
    return;
  }
  
  console.log('🧪 Probando actualización de estado para:', deviceId);
  
  // Simular estado alternativo
  const currentState = window.deviceStatusWS.lastStatus[deviceId]?.state || 'off';
  const newState = currentState === 'on' ? 'off' : 'on';
  
  console.log('🔄 Estado actual:', currentState, '-> Nuevo estado:', newState);
  
  // Actualizar estado en la base de datos
  updateDeviceStateInDatabase(deviceId, newState);
  
  // Mostrar notificación
  showNotification(`Estado de prueba: ${newState.toUpperCase()}`, 'info');
}

// Hacer función disponible globalmente
window.testStateUpdate = testStateUpdate;

// Función para probar la UI directamente
function testUIUpdate() {
  console.log('🧪 Probando actualización directa de UI');
  
  // Probar estado ON con usuario
  const testDataOn = {
    device: 'SMART10000',
    state: 'on',
    online: true,
    user: 'Jose Angel Balbuena Palma',
    user_name: 'Jose Angel Balbuena Palma',
    user_registration: '123456789',
    user_email: 'jose.balbuena@example.com',
    last_activity: new Date().toISOString(),
    timestamp: new Date().toISOString()
  };
  
  console.log('🧪 Probando estado ON con datos:', testDataOn);
  updateDeviceStatusUI(testDataOn);
  
  // Después de 3 segundos, probar estado OFF
  setTimeout(() => {
    const testDataOff = {
      device: 'SMART10000',
      state: 'off',
      online: false,
      user: 'Jose Angel Balbuena Palma',
      user_name: 'Jose Angel Balbuena Palma',
      user_registration: '123456789',
      user_email: 'jose.balbuena@example.com',
      last_activity: new Date().toISOString(),
      timestamp: new Date().toISOString()
    };
    
    console.log('🧪 Probando estado OFF con datos:', testDataOff);
    updateDeviceStatusUI(testDataOff);
  }, 3000);
  
  showNotification('Probando actualización directa de UI - Revisa la consola', 'info');
}

// Hacer función disponible globalmente
window.testUIUpdate = testUIUpdate;

// Función para forzar actualización con datos de WebSocket
function forceUpdateFromWebSocket() {
  console.log('🔧 Forzando actualización desde WebSocket...');
  
  // Obtener el dispositivo seleccionado
  const deviceSelect = document.getElementById('device_id');
  const selectedDevice = deviceSelect ? deviceSelect.value : 'SMART10000';
  
  // Obtener último estado conocido
  const lastStatus = window.deviceStatusWS?.lastStatus?.[selectedDevice];
  
  if (lastStatus) {
    console.log('🔧 Último estado conocido:', lastStatus);
    
    // Formatear datos para la UI
    const formattedData = {
      device: selectedDevice,
      state: lastStatus.state || 'unknown',
      online: lastStatus.state === 'on',
      user: lastStatus.user || lastStatus.user_name || lastStatus.hab_name,
      user_name: lastStatus.user_name || lastStatus.user || lastStatus.hab_name,
      user_registration: lastStatus.user_registration || lastStatus.hab_registration,
      user_email: lastStatus.user_email || lastStatus.hab_email,
      last_activity: lastStatus.last_activity || lastStatus.timestamp
    };
    
    console.log('🔧 Datos formateados para forzar actualización:', formattedData);
    
    // Forzar actualización
    if (typeof updateDeviceStatusUI === 'function') {
      updateDeviceStatusUI(formattedData);
      console.log('✅ Actualización forzada completada');
    } else {
      console.error('❌ updateDeviceStatusUI no está disponible');
    }
  } else {
    console.warn('⚠ No hay datos de estado disponibles para forzar actualización');
  }
}

// Hacer función disponible globalmente
window.forceUpdateFromWebSocket = forceUpdateFromWebSocket;

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
window.selectedDevice = <?php echo json_encode($selectedDevice); ?>;
window.deviceInitialStatus = <?php echo json_encode($deviceInitialStatus); ?>;

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
  
  // Mostrar estado inicial del dispositivo si está disponible
  if (window.deviceInitialStatus) {
    console.log('✓ Estado inicial del dispositivo:', window.deviceInitialStatus);
    updateDeviceStatusUI(window.deviceInitialStatus);
  } else {
    console.log('⚠ No hay estado inicial del dispositivo disponible');
    
    // Simular datos iniciales para prueba
    const testData = {
      device: 'SMART10000',
      state: 'on',
      online: true,
      user: 'Jose Angel Balbuena Palma',
      user_name: 'Jose Angel Balbuena Palma',
      user_registration: '123456',
      user_email: 'test@example.com',
      last_activity: new Date().toISOString()
    };
    
    console.log('🧪 Probando updateDeviceStatusUI con datos de prueba:', testData);
    
    // Esperar un poco para que los elementos DOM estén disponibles
    setTimeout(() => {
      if (typeof updateDeviceStatusUI === 'function') {
        updateDeviceStatusUI(testData);
        console.log('✅ Función de prueba ejecutada');
      } else {
        console.error('❌ updateDeviceStatusUI no está disponible');
      }
    }, 1000);
  }
  
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
    'updateDeviceStatus',
    'updateDeviceStatusUI',
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
  
  // Configurar evento de cambio de dispositivo
  const deviceSelect = document.getElementById('device_id');
  if (deviceSelect) {
    deviceSelect.addEventListener('change', function() {
      const selectedDevice = this.value;
      console.log('Dispositivo seleccionado cambiado a:', selectedDevice);
      
      // Actualizar variable global
      window.selectedDevice = selectedDevice;
      
      // Solicitar estado actual del nuevo dispositivo
      if (typeof requestDeviceStatus === 'function') {
        requestDeviceStatus(selectedDevice);
      }
      
      // Actualizar suscripción WebSocket si está disponible
      if (typeof subscribeToDevices === 'function') {
        subscribeToDevices([selectedDevice]);
      }
      
      // Actualizar suscripción MQTT si está disponible
      if (typeof subscribeToDeviceStatusTopics === 'function') {
        subscribeToDeviceStatusTopics(selectedDevice);
      }
      
      // Actualizar monitor de estado
      if (window.deviceStatusMonitor) {
        window.deviceStatusMonitor.selectedDevice = selectedDevice;
      }
    });
  }
  
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
  
  // Inicializar sistema de monitoreo de estado
  console.log('🔧 Inicializando sistema de monitoreo de estado...');
  
  // Configurar WebSocket si está disponible
  if (typeof initDeviceStatusWS === 'function') {
    console.log('✓ Inicializando WebSocket...');
    
    // Configurar callbacks para WebSocket
    if (typeof onDeviceStatusEvent === 'function') {
      // Callback para actualizaciones de estado
      onDeviceStatusEvent('onStatusUpdate', function(deviceId, status) {
        console.log('📡 Estado actualizado vía WebSocket:', deviceId, status);
        if (deviceId === window.selectedDevice) {
          console.log('🔧 Actualizando UI para dispositivo seleccionado:', deviceId);
          console.log('🔧 Datos completos del estado:', status);
          
          // Asegurar que el objeto tiene el formato correcto
          const statusData = {
            device: deviceId,
            state: status.state,
            online: status.state === 'on', // Si está encendido, está conectado
            user: status.user || status.user_name || status.hab_name,
            user_name: status.user_name || status.user || status.hab_name,
            user_registration: status.user_registration || status.hab_registration,
            user_email: status.user_email || status.hab_email,
            last_activity: status.last_activity || status.timestamp
          };
          
          console.log('🔧 Datos formateados para UI:', statusData);
          updateDeviceStatusUI(statusData);
        }
      });
      
      // Callback para conexión
      onDeviceStatusEvent('onConnect', function() {
        console.log('📡 WebSocket conectado');
        if (window.selectedDevice) {
          subscribeToDevices([window.selectedDevice]);
        }
      });
    }
    
    // Intentar inicializar WebSocket con URL forzada
    try {
      const wsUrl = 'ws://localhost:3000';
      console.log('🔧 Conectando WebSocket a:', wsUrl);
      initDeviceStatusWS(wsUrl);
    } catch (e) {
      console.error('❌ Error inicializando WebSocket:', e);
    }
  }
  
  // Configurar MQTT para monitoreo de estado si está disponible
  if (typeof initDeviceStatusMQTT === 'function') {
    console.log('✓ Inicializando MQTT para estado...');
    
    // Intentar inicializar MQTT con URL forzada
    try {
      const mqttUrl = 'ws://localhost:8083/mqtt';
      console.log('🔧 Conectando MQTT a:', mqttUrl);
      initDeviceStatusMQTT(mqttUrl);
      
      // Configurar suscripción automática al cambiar dispositivo
      if (window.selectedDevice) {
        setTimeout(() => {
          if (window.deviceStatusMQTT.isConnected) {
            subscribeToDeviceStatusTopics(window.selectedDevice);
          }
        }, 2000);
      }
    } catch (e) {
      console.error('❌ Error inicializando MQTT:', e);
    }
  }
  
  // Configurar monitor de estado (polling como fallback)
  if (typeof initDeviceStatusMonitor === 'function') {
    console.log('✓ Inicializando monitor de estado...');
    const pollingInterval = window.DeviceStatusConfig.monitor.pollingInterval;
    initDeviceStatusMonitor(pollingInterval);
  }
  
  console.log('✅ Dashboard MVC completamente inicializado');
  
  // Instrucciones para el usuario
  console.log('');
  console.log('🔧 FUNCIONES DE PRUEBA DISPONIBLES:');
  console.log('   - testUIUpdate()           - Prueba la actualización de UI');
  console.log('   - forceUpdateFromWebSocket() - Fuerza actualización con datos de WebSocket');
  console.log('   - testCommand()            - Prueba la función command');
  console.log('');
  console.log('💡 Para probar manualmente, ejecuta en la consola:');
  console.log('   testUIUpdate()');
  console.log('');
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