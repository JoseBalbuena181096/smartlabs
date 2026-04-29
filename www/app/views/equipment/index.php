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
      
      <!-- Selector de dispositivo y estado MQTT -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="form-group">
            <label for="device_id"><strong><i class="fa fa-cogs"></i> Dispositivo MQTT Activo:</strong></label>
            <select id="device_id" class="form-control" onchange="onDeviceChange()">
              <?php if (isset($devices) && !empty($devices)): ?>
                <?php foreach ($devices as $device): ?>
                  <option value="<?php echo htmlspecialchars($device['devices_serie']); ?>" 
                          <?php echo (strtolower($device['devices_alias']) == 'autoprestamoequipos') ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($device['devices_alias']); ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="default">Dispositivo por defecto</option>
              <?php endif; ?>
            </select>
            <small class="text-muted">AutoprestamoEquipos está seleccionado por defecto. Cambie para usar otro dispositivo RFID</small>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label><strong><i class="fa fa-wifi"></i> Estado MQTT:</strong></label>
            <div>
              <span id="mqtt_status" class="badge badge-secondary">Desconectado</span>
              <button type="button" class="btn btn-sm btn-success ml-1" onclick="connectMQTTToSelectedDevice()" title="Conectar manualmente">
                <i class="fa fa-plug"></i> Conectar ahora
              </button>
              <button type="button" class="btn btn-sm btn-outline-info ml-1" onclick="showActiveDeviceInfo()" title="Ver información del dispositivo">
                <i class="fa fa-info-circle"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-warning ml-1" onclick="forceReconnect()" title="Forzar reconexión">
                <i class="fa fa-refresh"></i>
              </button>
              <span id="auto_connect_status" class="badge badge-secondary ml-1">
                <i class="fa fa-info-circle"></i> Esperando dispositivo
              </span>
            </div>
            <small class="text-muted">Se conecta automáticamente al broker MQTT cuando seleccionas un dispositivo</small>
          </div>
        </div>
      </div>

      <!-- Área de mensajes MQTT -->
      <div class="row mb-3">
        <div class="col-md-12">
          <div id="display_new_access" class="alert alert-light text-center" style="min-height: 50px; display: flex; align-items: center; justify-content: center;">
            <span class="text-info"><i class="fa fa-cog fa-spin"></i> <strong>Inicializando conexión MQTT...</strong> Por favor espera mientras se conecta automáticamente</span>
          </div>
        </div>
      </div>
      
      <!-- Mensajes de estado -->
      <?php if (isset($message) && !empty($message)): ?>
        <div class="alert <?php echo $equipment_exists ? 'alert-warning' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
          <i class="fa <?php echo $equipment_exists ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i> <?php echo $message; ?>
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
                      <?php if (isset($equipment_exists) && $equipment_exists): ?>
                        <button type="submit" name="update_equipment" value="1" class="btn btn-warning btn-lg">
                          <i class="fa fa-edit"></i> Actualizar Equipo Existente
                        </button>
                        <button type="button" onclick="clearForm()" class="btn btn-info btn-lg ml-2">
                          <i class="fa fa-plus"></i> Registrar Nuevo Equipo
                        </button>
                      <?php else: ?>
                      <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa fa-save"></i> Registrar Equipo
                      </button>
                      <?php endif; ?>
                      <button type="reset" class="btn btn-secondary btn-lg ml-2" onclick="clearMQTTMessage()">
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

<!-- Incluir librerías MQTT -->
<script src="/libs/mqtt/dist/mqtt.min.js"></script>

<script>
/*
******************************
****** VARIABLES GLOBALES ****
******************************
*/
var client;
var connected = false;
var audioNotificationEnabled = true;

// Generar cadena aleatoria para clientId único
function generarCadenaAleatoria(longitud) {
    const caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    let cadenaAleatoria = '';
    
    for (let i = 0; i < longitud; i++) {
        const indiceAleatorio = Math.floor(Math.random() * caracteres.length);
        cadenaAleatoria += caracteres.charAt(indiceAleatorio);
    }
    
    return cadenaAleatoria;
}

/*
******************************
****** FUNCIONES MQTT ********
******************************
*/

// Reproducir audio de notificación usando Web Audio API
function playNotificationSound() {
    if (!audioNotificationEnabled) return;
    
    try {
        // Crear contexto de audio
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        // Crear oscilador para el beep
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Configurar el sonido
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime); // Frecuencia de 800Hz
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime); // Volumen al 30%
        
        // Duración del beep
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2); // 200ms de duración
        
    } catch (error) {
        console.log('No se pudo reproducir el audio de notificación:', error);
    }
}

// Procesar mensajes MQTT recibidos
function process_msg(topic, message) {
    const msg = message.toString();
    const splittedTopic = topic.split("/");
    const serialNumber = splittedTopic[0];
    const query = splittedTopic[1];
    const deviceSerie = document.getElementById("device_id").value;
    
    console.log(`Mensaje MQTT recibido: ${topic} -> ${msg}`);
    
    if ((query == "loan_querye" || query == "scholar_query") && deviceSerie === serialNumber) {
        // Auto-rellenar campo RFID
        const inputRfid = document.getElementById("rfid");
        if (inputRfid) {
            inputRfid.value = msg;
            inputRfid.focus();
            
            // Trigger input event para activar mayúsculas si es necesario
            inputRfid.dispatchEvent(new Event('input', { bubbles: true }));
        }
        
        // Mostrar notificación de nuevo acceso con información del dispositivo
        const displayNewAccess = document.getElementById("display_new_access");
        if (displayNewAccess) {
            // Obtener nombre del dispositivo
            const deviceSelect = document.getElementById("device_id");
            const deviceName = deviceSelect.options[deviceSelect.selectedIndex].text;
            
            displayNewAccess.innerHTML = `
                <span class="text-success">
                    <i class="fa fa-wifi"></i> <strong>RFID detectado: ${msg}</strong><br>
                    <small>📡 Desde: ${deviceName} (${serialNumber}) | 📢 Tópico: ${query}</small><br>
                    <small>✅ Datos listos para registro/actualización</small>
                </span>`;
            displayNewAccess.className = "alert alert-success text-center";
            
            // Limpiar después de 8 segundos
            setTimeout(() => {
                displayNewAccess.innerHTML = '<span class="text-muted"><i class="fa fa-info-circle"></i> Esperando lectura RFID del dispositivo...</span>';
                displayNewAccess.className = "alert alert-light text-center";
            }, 8000);
        }
        
        // Reproducir sonido de notificación
        playNotificationSound();
        
        // Verificar si el equipo ya existe en el servidor
        verificarEquipoExistente(msg);
    } else {
        console.log("Mensaje recibido pero no procesado:", { topic, message: msg, deviceSerie, serialNumber, query });
    }
}

// Verificar si el equipo ya existe
function verificarEquipoExistente(rfid) {
    // Simular verificación (en una implementación real, se haría AJAX al servidor)
    const equipmentTableRows = document.querySelectorAll('#equipmentsTable tbody tr');
    let equipmentExists = false;
    let existingEquipment = null;
    
    equipmentTableRows.forEach(row => {
        const rfidCell = row.querySelector('td:nth-child(4) code');
        if (rfidCell && rfidCell.textContent.trim() === rfid) {
            equipmentExists = true;
            existingEquipment = {
                name: row.querySelector('td:nth-child(2) .badge').textContent.trim(),
                brand: row.querySelector('td:nth-child(3) .badge').textContent.trim()
            };
        }
    });
    
    if (equipmentExists && existingEquipment) {
        // Mostrar alerta y llenar formulario con datos existentes
        showEquipmentExistsAlert(rfid, existingEquipment);
        fillFormWithExistingData(existingEquipment);
    }
}

// Mostrar alerta de equipo existente
function showEquipmentExistsAlert(rfid, equipment) {
    const alertHtml = `
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-triangle"></i> 
            <strong>⚠️ EQUIPO YA REGISTRADO</strong><br>
            El RFID <code>${rfid}</code> ya está registrado como:<br>
            <strong>Nombre:</strong> ${equipment.name}<br>
            <strong>Marca:</strong> ${equipment.brand}<br>
            <small>Puedes actualizar los datos del equipo usando el formulario.</small>
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>`;
    
    // Insertar la alerta después del área de mensajes MQTT
    const mqttArea = document.getElementById("display_new_access");
    mqttArea.insertAdjacentHTML('afterend', alertHtml);
    
    // Auto-remover después de 10 segundos
    setTimeout(() => {
        const alertEl = mqttArea.nextElementSibling;
        if (alertEl && alertEl.classList.contains('alert-warning')) {
            alertEl.remove();
        }
    }, 10000);
}

// Llenar formulario con datos existentes
function fillFormWithExistingData(equipment) {
    document.getElementById("name").value = equipment.name;
    document.getElementById("brand").value = equipment.brand;
    
    // Cambiar el botón a modo actualización
    const submitBtn = document.querySelector('button[type="submit"]:not([name])');
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fa fa-edit"></i> Actualizar Equipo Existente';
        submitBtn.className = 'btn btn-warning btn-lg';
        submitBtn.setAttribute('name', 'update_equipment');
        submitBtn.setAttribute('value', '1');
    }
}

// Función para limpiar el formulario
function clearForm() {
    document.getElementById("name").value = "";
    document.getElementById("brand").value = "";
    document.getElementById("rfid").value = "";
    document.getElementById("name").focus();
}

// Función para limpiar mensaje MQTT
function clearMQTTMessage() {
    const displayNewAccess = document.getElementById("display_new_access");
    if (displayNewAccess) {
        displayNewAccess.innerHTML = '<span class="text-muted"><i class="fa fa-info-circle"></i> Esperando lectura RFID del dispositivo...</span>';
        displayNewAccess.className = "alert alert-light text-center";
    }
}

// Función para mostrar información del dispositivo activo
function showActiveDeviceInfo() {
    const deviceSelect = document.getElementById("device_id");
    const deviceSerie = deviceSelect.value;
    const deviceName = deviceSelect.options[deviceSelect.selectedIndex].text;
    
    const info = `
        🔧 Dispositivo Activo: ${deviceName}
        📡 Serie: ${deviceSerie}
        🌐 Estado MQTT: ${connected ? 'Conectado' : 'Desconectado'}
        📊 Suscripción: ${deviceSerie}/loan_querye y ${deviceSerie}/scholar_query
    `;
    
    alert(info);
}

// Función para forzar reconexión
function forceReconnect() {
    if (connected && client) {
        client.end(true);
        connected = false;
        console.log('Conexión forzada a cerrar');
    }
    
    setTimeout(() => {
        connectMQTTToSelectedDevice();
    }, 1000);
}

// Suscribirse al dispositivo seleccionado
function subscribeToSelectedDevice() {
    const deviceSerie = document.getElementById("device_id").value;
    
    if (!deviceSerie || deviceSerie === 'default') {
        console.log('No se puede suscribir: dispositivo no válido');
        return false;
    }
    
    if (!connected || !client) {
        console.log('No se puede suscribir: MQTT no conectado');
        return false;
    }
    
    try {
        const topics = [`${deviceSerie}/loan_querye`, `${deviceSerie}/scholar_query`];
        
        let subscriptionsCompleted = 0;
        const totalSubscriptions = topics.length;
        
        topics.forEach(topic => {
            client.subscribe(topic, { qos: 0 }, (error) => {
                subscriptionsCompleted++;
                
                if (error) {
                    console.error(`Error al suscribirse a ${topic}:`, error);
                } else {
                    console.log(`✅ Suscrito exitosamente a: ${topic}`);
                }
                
                // Cuando se completen todas las suscripciones
                if (subscriptionsCompleted === totalSubscriptions) {
                    // Obtener nombre del dispositivo
                    const deviceSelect = document.getElementById("device_id");
                    const deviceName = deviceSelect.options[deviceSelect.selectedIndex].text;
                    
                    // Actualizar mensaje visual
                    const displayNewAccess = document.getElementById("display_new_access");
                    displayNewAccess.innerHTML = `<span class="text-info"><i class="fa fa-wifi"></i> <strong>Conectado al dispositivo:</strong> ${deviceName} (${deviceSerie}) - Esperando señales RFID...</span>`;
                    displayNewAccess.className = "alert alert-info text-center";
                    
                    setTimeout(() => {
                        displayNewAccess.innerHTML = '<span class="text-muted"><i class="fa fa-info-circle"></i> Esperando lectura RFID del dispositivo...</span>';
                        displayNewAccess.className = "alert alert-light text-center";
                    }, 4000);
                }
            });
        });
        
        console.log(`Suscripciones realizadas para dispositivo: ${deviceSerie}`);
        return true;
    } catch (error) {
        console.error('Error en suscripción:', error);
        return false;
    }
}

// Conectar a MQTT usando el dispositivo seleccionado
function connectMQTTToSelectedDevice() {
    const deviceSerie = document.getElementById("device_id").value;
    
    if (!deviceSerie || deviceSerie === 'default') {
        console.log('No se puede conectar: dispositivo no válido');
        
        // Actualizar estado visual
        document.getElementById('mqtt_status').innerHTML = '<span class="badge badge-warning">Selecciona dispositivo</span>';
        document.getElementById('auto_connect_status').innerHTML = '<i class="fa fa-exclamation-triangle"></i> Sin dispositivo';
        
        const displayNewAccess = document.getElementById("display_new_access");
        displayNewAccess.innerHTML = '<span class="text-warning"><i class="fa fa-info-circle"></i> <strong>Selecciona un dispositivo</strong> para conectar MQTT</span>';
        displayNewAccess.className = "alert alert-warning text-center";
        return;
    }
    
    console.log(`Conectando MQTT para dispositivo: ${deviceSerie}`);
    
    // Actualizar estado visual
    document.getElementById('mqtt_status').innerHTML = '<span class="badge badge-warning">Conectando...</span>';
    document.getElementById('auto_connect_status').innerHTML = '<i class="fa fa-refresh fa-spin"></i> Conectando...';
    
    const displayNewAccess = document.getElementById("display_new_access");
    displayNewAccess.innerHTML = '<span class="text-info"><i class="fa fa-refresh fa-spin"></i> <strong>Conectando a MQTT...</strong> Por favor espera</span>';
    displayNewAccess.className = "alert alert-info text-center";
    
    try {
        // Configuración dinámica de URL MQTT WebSocket
        let WebSocket_URL;
        const hostname = window.location.hostname;
        
        console.log('🔧 Detectando configuración MQTT para hostname:', hostname);
        
        // Determinar URL correcta basada en el hostname
         if (hostname === 'localhost' || hostname === '127.0.0.1') {
             // Acceso desde localhost - usar WSS seguro
             WebSocket_URL = 'ws://localhost:8083/mqtt';
             console.log('📡 Configuración MQTT: Acceso local detectado (WS)');
         } else if (hostname === '<?php echo $config["server_host"]; ?>') {
            // Acceso desde IP externa - usar WS no seguro para evitar problemas de certificados
            WebSocket_URL = 'ws://<?php echo $config["mqtt_host"]; ?>:8083/mqtt';
             console.log('📡 Configuración MQTT: Acceso desde red externa detectado (WS)');
         } else {
             // Fallback - usar WS no seguro
             WebSocket_URL = `ws://${hostname}:8083/mqtt`;
             console.log('📡 Configuración MQTT: Usando hostname dinámico (WS)');
         }
        
        console.log('📡 URL MQTT WebSocket:', WebSocket_URL);
        const clientId = `equipment_register_${generarCadenaAleatoria(6)}`;
        
        const options = {
            clientId: clientId,
            username: 'jose',
            password: 'public',
            keepalive: 60,
            clean: true,
            connectTimeout: 8000,
            reconnectPeriod: 5000,
        };
        
        console.log('Configuración MQTT:', { WebSocket_URL, clientId, deviceSerie });
        
        // Cerrar conexión anterior si existe
        if (client) {
            console.log('Cerrando conexión anterior...');
            client.end(true);
        }
        
        console.log('Creando nueva conexión MQTT...');
        client = mqtt.connect(WebSocket_URL, options);
        
        client.on('connect', () => {
            console.log('¡¡¡ MQTT conectado exitosamente! ¡¡¡');
            connected = true;
            
            // Actualizar estado visual
            document.getElementById('mqtt_status').innerHTML = '<span class="badge badge-success">Conectado</span>';
            document.getElementById('auto_connect_status').innerHTML = '<i class="fa fa-check-circle"></i> Activo';
            
            // Suscribirse automáticamente al dispositivo seleccionado
            const subscribeResult = subscribeToSelectedDevice();
            console.log('Resultado de suscripción:', subscribeResult);
            
            // Mensaje de confirmación
            const displayNewAccess = document.getElementById("display_new_access");
            displayNewAccess.innerHTML = '<span class="text-success"><i class="fa fa-check-circle"></i> <strong>MQTT Conectado</strong> - Escuchando dispositivo seleccionado...</span>';
            displayNewAccess.className = "alert alert-success text-center";
            
            // Limpiar mensaje después de 4 segundos
            setTimeout(() => {
                displayNewAccess.innerHTML = '<span class="text-muted"><i class="fa fa-info-circle"></i> Esperando lectura RFID del dispositivo...</span>';
                displayNewAccess.className = "alert alert-light text-center";
            }, 4000);
        });
        
        client.on('message', (topic, message) => {
            console.log('Mensaje MQTT recibido:', topic, message.toString());
            process_msg(topic, message);
        });
        
        client.on('error', (error) => {
            console.error('*** ERROR DE CONEXIÓN MQTT ***', error);
            connected = false;
            document.getElementById('mqtt_status').innerHTML = '<span class="badge badge-danger">Error</span>';
            document.getElementById('auto_connect_status').innerHTML = '<i class="fa fa-exclamation-triangle"></i> Error';
            
            // Mostrar error en el área de mensajes
            const displayNewAccess = document.getElementById("display_new_access");
            displayNewAccess.innerHTML = '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> <strong>Error de conexión MQTT</strong> - Verificando broker...</span>';
            displayNewAccess.className = "alert alert-danger text-center";
        });
        
        client.on('reconnect', () => {
            console.log('*** RECONECTANDO MQTT ***');
            document.getElementById('mqtt_status').innerHTML = '<span class="badge badge-warning">Reconectando...</span>';
            document.getElementById('auto_connect_status').innerHTML = '<i class="fa fa-refresh fa-spin"></i> Reconectando...';
            
            // Mostrar estado de reconexión
            const displayNewAccess = document.getElementById("display_new_access");
            displayNewAccess.innerHTML = '<span class="text-warning"><i class="fa fa-refresh fa-spin"></i> <strong>Reconectando MQTT...</strong> Por favor espera</span>';
            displayNewAccess.className = "alert alert-warning text-center";
        });
        
        client.on('close', () => {
            console.log('*** CONEXIÓN MQTT CERRADA ***');
            connected = false;
            document.getElementById('mqtt_status').innerHTML = '<span class="badge badge-secondary">Desconectado</span>';
            document.getElementById('auto_connect_status').innerHTML = '<i class="fa fa-times-circle"></i> Desconectado';
        });
        
        console.log('Conexión MQTT iniciada, esperando eventos...');
        
    } catch (error) {
        console.error('*** ERROR CRÍTICO AL CONECTAR MQTT ***', error);
        connected = false;
        document.getElementById('mqtt_status').innerHTML = '<span class="badge badge-danger">Error crítico</span>';
        document.getElementById('auto_connect_status').innerHTML = '<i class="fa fa-exclamation-triangle"></i> Error crítico';
        
        // Mostrar error en el área de mensajes
        const displayNewAccess = document.getElementById("display_new_access");
        displayNewAccess.innerHTML = '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> <strong>Error crítico MQTT</strong> - Verifica la configuración de red</span>';
        displayNewAccess.className = "alert alert-danger text-center";
    }
}

// Función legacy para compatibilidad
function connectMQTT() {
    connectMQTTToSelectedDevice();
}

// Manejar cambio de dispositivo
function onDeviceChange() {
    const selectedDevice = document.getElementById("device_id").value;
    console.log('Dispositivo cambiado a:', selectedDevice);
    
    // Si no hay dispositivo válido seleccionado, mostrar mensaje
    if (!selectedDevice || selectedDevice === 'default') {
        console.log('No se seleccionó un dispositivo válido');
        
        // Desconectar si está conectado
        if (connected && client) {
            client.end(true);
            connected = false;
        }
        
        // Actualizar estado visual
        document.getElementById('mqtt_status').innerHTML = '<span class="badge badge-warning">Selecciona dispositivo</span>';
        document.getElementById('auto_connect_status').innerHTML = '<i class="fa fa-exclamation-triangle"></i> Sin dispositivo';
        
        const displayNewAccess = document.getElementById("display_new_access");
        displayNewAccess.innerHTML = '<span class="text-warning"><i class="fa fa-info-circle"></i> <strong>Selecciona un dispositivo</strong> para conectar MQTT</span>';
        displayNewAccess.className = "alert alert-warning text-center";
        return;
    }
    
    // Conectar automáticamente al nuevo dispositivo
    console.log('Conectando automáticamente al dispositivo:', selectedDevice);
    connectMQTTToSelectedDevice();
}

/*
******************************
****** INICIALIZACIÓN ********
******************************
*/

$(document).ready(function() {
    console.log('=== INICIO DE SCRIPT EQUIPMENT ===');
    console.log('jQuery versión:', $.fn.jquery);
    console.log('Document ready ejecutado');
    
    // Configurar estado inicial
    document.getElementById('mqtt_status').innerHTML = '<span class="badge badge-secondary">Desconectado</span>';
    document.getElementById('auto_connect_status').innerHTML = '<i class="fa fa-info-circle"></i> Esperando dispositivo';
    
    // Conectar automáticamente después de un pequeño delay
    setTimeout(function() {
        const selectedDevice = document.getElementById("device_id").value;
        if (selectedDevice && selectedDevice !== 'default') {
            console.log('=== CONECTANDO AUTOMÁTICAMENTE AL CARGAR ===');
            connectMQTTToSelectedDevice();
        } else {
            console.log('No hay dispositivo seleccionado para conectar automáticamente');
        }
    }, 1000);
});

// Auto-hide alerts después de 8 segundos
setTimeout(function() {
    $('.alert-success, .alert-info').fadeOut('slow');
}, 8000);

// Verificación adicional después de la carga completa de la página
window.onload = function() {
    console.log('=== VERIFICACIÓN WINDOW.ONLOAD ===');
    
    // Esperar un poco más para asegurar que todo esté cargado
    setTimeout(() => {
        const selectedDevice = document.getElementById("device_id").value;
        const deviceSelect = document.getElementById("device_id");
        
        console.log('Estado después de window.onload:');
        console.log('- Dispositivo seleccionado:', selectedDevice);
        console.log('- Conectado:', connected);
        console.log('- Elemento select:', deviceSelect);
        
        if (selectedDevice && selectedDevice !== 'default' && !connected) {
            console.log('=== EJECUTANDO CONEXIÓN DESDE WINDOW.ONLOAD ===');
            
            // Hacer más visible el botón de conexión
            const connectButton = document.querySelector('button[onclick="connectMQTTToSelectedDevice()"]');
            if (connectButton) {
                connectButton.className = 'btn btn-sm btn-warning ml-1 pulse';
                connectButton.innerHTML = '<i class="fa fa-plug"></i> ¡Conectar ahora!';
                connectButton.setAttribute('title', 'La conexión automática no funcionó. Haz clic para conectar manualmente.');
            }
            
            // Mostrar mensaje de que la conexión automática no funcionó
            const displayNewAccess = document.getElementById("display_new_access");
            if (displayNewAccess) {
                displayNewAccess.innerHTML = '<span class="text-warning"><i class="fa fa-exclamation-triangle"></i> <strong>Conexión automática no ejecutada</strong> - Haz clic en "¡Conectar ahora!" para conectar manualmente</span>';
                displayNewAccess.className = "alert alert-warning text-center";
            }
            
            // Intentar conectar automáticamente una vez más
            setTimeout(() => {
                console.log('=== ÚLTIMO INTENTO DE CONEXIÓN AUTOMÁTICA ===');
                connectMQTTToSelectedDevice();
            }, 1000);
        } else if (selectedDevice && selectedDevice !== 'default' && connected) {
            console.log('=== CONEXIÓN YA ESTABLECIDA ===');
            
            // Hacer menos visible el botón de conexión si ya está conectado
            const connectButton = document.querySelector('button[onclick="connectMQTTToSelectedDevice()"]');
            if (connectButton) {
                connectButton.className = 'btn btn-sm btn-outline-secondary ml-1';
                connectButton.innerHTML = '<i class="fa fa-plug"></i> Reconectar';
                connectButton.setAttribute('title', 'Reconectar MQTT');
            }
        }
    }, 2000);
};

// Función para hacer pulsar el botón de conexión
function addPulseEffect() {
    const style = document.createElement('style');
    style.textContent = `
        .pulse {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
}

// Agregar el efecto de pulso al cargar
addPulseEffect();

// Mensaje final
console.log('=== INICIALIZACIÓN COMPLETA ===');
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>