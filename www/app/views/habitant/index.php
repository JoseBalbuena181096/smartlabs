<?php 
$title = "Registro de Usuarios - SMARTLABS";
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
      <div class="mb-0 h5 no-wrap" id="pageTitle">Registro de Usuarios del Laboratorio</div>
      
      <!-- Display para nuevos accesos MQTT -->
      <div class="">
        <b id="display_new_access"></b>
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
      
      <!-- Selector de dispositivo -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="form-group">
            <label for="device_id"><strong><i class="fa fa-cogs"></i> Dispositivo Activo:</strong></label>
            <select id="device_id" class="form-control">
              <?php if (isset($devices) && !empty($devices)): ?>
                <?php foreach ($devices as $device): ?>
                  <option value="<?php echo htmlspecialchars($device['devices_serie']); ?>">
                    <?php echo htmlspecialchars($device['devices_alias']); ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="default">Dispositivo por defecto</option>
              <?php endif; ?>
            </select>
            <small class="text-muted">Seleccione el dispositivo para recibir señales RFID</small>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label><strong><i class="fa fa-wifi"></i> Estado MQTT:</strong></label>
            <div>
              <span id="mqtt_status" class="badge badge-warning">Desconectado</span>
              <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="connectMQTT()">
                <i class="fa fa-plug"></i> Conectar
              </button>
            </div>
            <small class="text-muted">Estado de la conexión con el broker MQTT</small>
          </div>
        </div>
      </div>

      <!-- Mensajes de estado -->
      <?php if (isset($message) && !empty($message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
          <i class="fa fa-info-circle"></i> 
          <div><?php echo $message; ?></div>
          <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>
      
      <div class="row">
        <div class="col-md-12">
          <div class="box">
            <div class="box-header">
              <h2><i class="fa fa-user-plus"></i> Registro de Usuarios SMARTLABS</h2>
              <small>Sistema de registro de estudiantes y asignación de tarjetas RFID</small>
            </div>
            <div class="box-body">
              
              <!-- Formulario de registro -->
              <div class="card mb-4">
                <div class="card-header bg-success text-white">
                  <h4 class="mb-0"><i class="fa fa-user-plus"></i> Registrar Nuevo Usuario</h4>
                </div>
                <div class="card-body">
                  <form method="POST" class="row g-3">
                    <div class="col-md-6">
                      <label for="name" class="form-label"><strong><i class="fa fa-user"></i> Nombre Completo:</strong></label>
                      <input type="text" 
                             name="name" 
                             id="name"
                             class="form-control" 
                             placeholder="Ej: JOSE ANGEL BALBUENA PALMA" 
                             value="<?php echo isset($name_) ? htmlspecialchars($name_) : ''; ?>"
                             style="text-transform: uppercase;"
                             required>
                      <small class="text-muted">El nombre se guardará en mayúsculas automáticamente</small>
                    </div>
                    
                    <div class="col-md-6">
                      <label for="registration" class="form-label"><strong><i class="fa fa-id-card"></i> Matrícula:</strong></label>
                      <input type="text" 
                             name="registration" 
                             id="registration"
                             class="form-control" 
                             placeholder="Ej: L03533767" 
                             value="<?php echo isset($registration_) ? htmlspecialchars($registration_) : ''; ?>"
                             style="text-transform: uppercase;"
                             required>
                      <small class="text-muted">Matrícula o identificación única del estudiante</small>
                    </div>
                    
                    <div class="col-md-6">
                      <label for="email" class="form-label"><strong><i class="fa fa-envelope"></i> Email Institucional:</strong></label>
                      <input type="email" 
                             name="email" 
                             id="email"
                             class="form-control" 
                             placeholder="Ej: jose.balbuena.palma@tec.mx" 
                             value="<?php echo isset($email_) ? htmlspecialchars($email_) : ''; ?>"
                             required>
                      <small class="text-muted">Correo electrónico institucional</small>
                    </div>
                    
                    <div class="col-md-6">
                      <label for="rfid" class="form-label"><strong><i class="fa fa-credit-card"></i> RFID de la Tarjeta:</strong></label>
                      <input type="text" 
                             name="rfid" 
                             id="rfid"
                             class="form-control" 
                             placeholder="Ej: 5242243191" 
                             value="<?php echo isset($rfid_) ? htmlspecialchars($rfid_) : ''; ?>"
                             required>
                      <small class="text-muted">Número de identificación de la tarjeta RFID</small>
                    </div>
                    
                    <div class="col-md-12">
                      <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa fa-save"></i> Registrar Usuario y Asignar Tarjeta
                      </button>
                      <button type="reset" class="btn btn-secondary btn-lg ml-2">
                        <i class="fa fa-refresh"></i> Limpiar Formulario
                      </button>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Buscador de usuarios -->
              <div class="card mb-4">
                <div class="card-header bg-info text-white">
                  <h4 class="mb-0"><i class="fa fa-search"></i> Buscador de Usuarios</h4>
                  <small>Busque usuarios por nombre o matrícula</small>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="form-group">
                        <label for="user_search"><strong><i class="fa fa-search"></i> Buscar Usuario:</strong></label>
                        <input type="text" 
                               id="user_search" 
                               class="form-control form-control-lg" 
                               placeholder="Escriba nombre completo o matrícula (ej: JOSE BALBUENA o L03533767)"
                               autocomplete="off"
                               maxlength="100">
                        <div class="d-flex justify-content-between">
                          <small class="text-muted">Escriba al menos 2 caracteres para iniciar la búsqueda</small>
                          <small class="text-muted"><span id="search_char_count">0</span>/100 caracteres</small>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="search_type"><strong><i class="fa fa-filter"></i> Tipo de Búsqueda:</strong></label>
                        <select id="search_type" class="form-control">
                          <option value="all">Nombre y Matrícula</option>
                          <option value="name">Solo Nombre</option>
                          <option value="registration">Solo Matrícula</option>
                          <option value="email">Solo Email</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Resultados de búsqueda -->
                  <div id="search_results" class="mt-3" style="display: none;">
                    <h5><i class="fa fa-list"></i> Resultados de Búsqueda:</h5>
                    <div id="search_results_content"></div>
                  </div>
                  
                  <!-- Botones de acción -->
                  <div class="row mt-3">
                    <div class="col-md-6">
                      <button type="button" class="btn btn-primary" onclick="clearSearch()">
                        <i class="fa fa-refresh"></i> Limpiar Búsqueda
                      </button>
                    </div>
                    <div class="col-md-6 text-right">
                      <button type="button" class="btn btn-success" id="select_user_btn" onclick="selectSearchedUser()" style="display: none;">
                        <i class="fa fa-check"></i> Seleccionar Usuario
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Lista de usuarios registrados -->
              <div class="card">
                <div class="card-header">
                  <h4 class="mb-0"><i class="fa fa-users"></i> Usuarios Registrados Recientemente</h4>
                  <small>Últimos 20 usuarios registrados en el sistema</small>
                </div>
                <div class="card-body">
                  <?php if (!empty($residents)): ?>
                    <div class="table-responsive">
                      <table class="table table-striped table-hover" id="habitantsTable">
                        <thead class="bg-light">
                          <tr>
                            <th><i class="fa fa-hashtag"></i> ID</th>
                            <th><i class="fa fa-user"></i> NOMBRE</th>
                            <th><i class="fa fa-id-card"></i> MATRÍCULA</th>
                            <th><i class="fa fa-envelope"></i> EMAIL</th>
                            <th><i class="fa fa-calendar"></i> FECHA REGISTRO</th>
                            <th><i class="fa fa-cog"></i> ACCIONES</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($residents as $habitant): ?>
                            <tr>
                              <td><strong><?php echo htmlspecialchars($habitant['hab_id']); ?></strong></td>
                              <td>
                                <span class="badge badge-primary p-2">
                                  <?php echo htmlspecialchars($habitant['hab_name']); ?>
                                </span>
                              </td>
                              <td>
                                <code class="bg-light p-1"><?php echo htmlspecialchars($habitant['hab_registration']); ?></code>
                              </td>
                              <td>
                                <small><?php echo htmlspecialchars($habitant['hab_email']); ?></small>
                              </td>
                              <td>
                                <?php if (isset($habitant['hab_date'])): ?>
                                  <strong><?php echo date('d/m/Y', strtotime($habitant['hab_date'])); ?></strong><br>
                                  <small class="text-muted"><?php echo date('H:i:s', strtotime($habitant['hab_date'])); ?></small>
                                <?php else: ?>
                                  <small class="text-muted">Fecha no disponible</small>
                                <?php endif; ?>
                              </td>
                              <td>
                                <button class="btn btn-sm btn-danger" onclick="deleteHabitant(<?php echo $habitant['hab_id']; ?>)">
                                  <i class="fa fa-trash"></i> Eliminar
                                </button>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    
                    <!-- Estadísticas de usuarios -->
                    <div class="row mt-4">
                      <div class="col-md-3">
                        <div class="box bg-primary text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-users fa-2x"></i>
                            <h3><?php echo count($residents); ?></h3>
                            <p>Total Mostrados</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="box bg-success text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-check-circle fa-2x"></i>
                            <h3><?php echo count($residents); ?></h3>
                            <p>Usuarios Activos</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="box bg-info text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-credit-card fa-2x"></i>
                            <h3><?php echo count($residents); ?></h3>
                            <p>Tarjetas Asignadas</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="box bg-warning text-white">
                          <div class="box-body text-center">
                            <i class="fa fa-calendar fa-2x"></i>
                            <h3>
                              <?php 
                              if (!empty($residents)) {
                                $dates = array_filter(array_column($residents, 'hab_date'));
                                if (!empty($dates)) {
                                  echo date('d/m/Y', strtotime(max($dates)));
                                } else {
                                  echo '-';
                                }
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
                    <div class="alert alert-info text-center">
                      <i class="fa fa-info-circle fa-3x mb-3"></i>
                      <h4>No hay usuarios registrados recientemente</h4>
                      <p>Los usuarios aparecerán aquí después de ser registrados en el sistema.</p>
                      <hr>
                      <p class="mb-0">
                        <small>
                          <strong>Nota:</strong> Se muestran los últimos 20 usuarios registrados.
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
              <h4><i class="fa fa-info-circle"></i> Proceso de Registro</h4>
            </div>
            <div class="box-body">
              <div class="row">
                <div class="col-md-6">
                  <h5><i class="fa fa-list-ol"></i> Pasos del registro:</h5>
                  <ol>
                    <li><strong>Verificación de tarjeta:</strong> Se verifica si la tarjeta RFID ya existe</li>
                    <li><strong>Creación de tarjeta:</strong> Si no existe, se crea una nueva entrada</li>
                    <li><strong>Registro de usuario:</strong> Se registra el usuario con sus datos</li>
                    <li><strong>Asignación:</strong> Se asigna la tarjeta al usuario automáticamente</li>
                  </ol>
                </div>
                <div class="col-md-6">
                  <h5><i class="fa fa-exclamation-triangle"></i> Consideraciones importantes:</h5>
                  <ul>
                    <li>Cada tarjeta RFID puede asignarse solo a un usuario</li>
                    <li>La matrícula debe ser única en el sistema</li>
                    <li>Los datos se almacenan en mayúsculas automáticamente</li>
                    <li>Todos los usuarios se asignan al dispositivo ID: 1 por defecto</li>
                  </ul>
                </div>
              </div>
              
              <div class="alert alert-warning mt-3">
                <i class="fa fa-warning"></i>
                <strong>Importante:</strong> 
                Este sistema registra usuarios para acceso a laboratorios. Asegúrate de que la información sea correcta antes de enviar el formulario.
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
    $('#habitantsTable tbody tr').each(function(index) {
        $(this).delay(index * 100).fadeIn(500);
    });
    
    // Limpiar formulario después de envío exitoso
    <?php if (isset($message) && strpos($message, 'Usuario creado') !== false): ?>
    $('#name').val('');
    $('#registration').val('');
    $('#email').val('');
    $('#rfid').val('');
    <?php endif; ?>
    
    // Conversión automática a mayúsculas
    $('#name, #registration').on('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Conversión automática a minúsculas para email
    $('#email').on('input', function() {
        this.value = this.value.toLowerCase();
    });
    
    // Validación del formulario
    $('form').submit(function(e) {
        var name = $('#name').val().trim();
        var registration = $('#registration').val().trim();
        var email = $('#email').val().trim();
        var rfid = $('#rfid').val().trim();
        
        if (name.length < 3) {
            alert('El nombre debe tener al menos 3 caracteres');
            e.preventDefault();
            return false;
        }
        
        if (registration.length < 3) {
            alert('La matrícula debe tener al menos 3 caracteres');
            e.preventDefault();
            return false;
        }
        
        // Validar formato de email básico
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            alert('Por favor ingresa un email válido');
            e.preventDefault();
            return false;
        }
        
        if (rfid.length < 3) {
            alert('El RFID debe tener al menos 3 caracteres');
            e.preventDefault();
            return false;
        }
        
        // Confirmación antes de enviar
        if (!confirm('¿Estás seguro de registrar este usuario?\n\nNombre: ' + name + '\nMatrícula: ' + registration + '\nEmail: ' + email + '\nRFID: ' + rfid)) {
            e.preventDefault();
            return false;
        }
    });
});

// Auto-hide alerts después de 8 segundos
setTimeout(function() {
    $('.alert').fadeOut('slow');
}, 8000);

// Función para eliminar usuario
function deleteHabitant(id) {
    if (confirm('¿Está seguro de que desea eliminar este usuario?\n\nEsta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id_to_delete';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// Buscar usuario por RFID automáticamente
function searchByRFID(rfid) {
    if (!rfid || rfid.length < 3) return;
    
    const formData = new FormData();
    formData.append('search_rfid', '1');
    formData.append('rfid', rfid);
    
    fetch('/Habitant/searchByRFID', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.length > 0) {
            const user = data[0];
            showMQTTNotification(`Usuario encontrado: ${user.hab_name} (${user.hab_registration})`, 'info');
            
            // Preguntar si quiere llenar el formulario con los datos existentes
            if (confirm(`Se encontró el usuario: ${user.hab_name}\nMatrícula: ${user.hab_registration}\n\n¿Desea llenar el formulario con estos datos?`)) {
                // Llenar formulario automáticamente
                setTimeout(() => {
                    fillFormWithUser(user.hab_id, user.hab_name, user.hab_registration, user.hab_email);
                }, 500);
            }
        } else {
            console.log('No se encontró usuario con RFID:', rfid);
        }
    })
    .catch(error => {
        console.error('Error buscando por RFID:', error);
    });
}
</script>

<!-- MQTT Library -->
<script src="/libs/mqtt/dist/mqtt.min.js"></script>

<!-- Audio Notification -->
<script src="/js/audio-notification.js"></script>

<!-- Script específico para Habitant -->
<script src="/js/habitant-functions.js"></script>

<script type="text/javascript">
/*
******************************
****** MQTT SMARTLABS *******
******************************
*/

// Generar ID único para cliente MQTT
function generarCadenaAleatoria(longitud) {
    const caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    let cadenaAleatoria = '';
    for (let i = 0; i < longitud; i++) {
        const indiceAleatorio = Math.floor(Math.random() * caracteres.length);
        cadenaAleatoria += caracteres.charAt(indiceAleatorio);
    }
    return cadenaAleatoria;
}

// Variables MQTT globales
let mqttClient = null;
let mqttConnected = false;
const cadenaAleatoria = generarCadenaAleatoria(6);

// Audio para notificaciones (opcional)
let notificationAudio = null;
try {
    notificationAudio = new Audio('/audio/notification.mp3'); // Asegúrate de tener este archivo
} catch(e) {
    console.log('Audio no disponible:', e);
}

// Procesar mensajes MQTT
function process_msg(topic, message) {
    const msg = message.toString();
    const splittedTopic = topic.split("/");
    const serialNumber = splittedTopic[0];
    const query = splittedTopic[1];
    const deviceSerie = document.getElementById("device_id").value;
    
    console.log(`Mensaje MQTT recibido: ${topic} -> ${msg}`);
    
    if ((query == "access_query" || query == "scholar_query") && deviceSerie === serialNumber) {
        // Auto-rellenar campo RFID
        const inputRfid = document.getElementById("rfid");
        if (inputRfid) {
            inputRfid.value = msg;
            inputRfid.focus();
        }
        
        // Mostrar notificación de nuevo acceso
        const displayNewAccess = document.getElementById("display_new_access");
        if (displayNewAccess) {
            displayNewAccess.innerHTML = `<span class="text-success"><i class="fa fa-wifi"></i> Nuevo acceso: ${msg}</span>`;
            displayNewAccess.style.color = '#28a745';
            displayNewAccess.style.fontWeight = 'bold';
            
            // Limpiar después de 3 segundos
            setTimeout(() => {
                displayNewAccess.innerHTML = "";
            }, 3000);
        }
        
        // Reproducir sonido de notificación usando Web Audio API
        if (window.audioNotifier) {
            try {
                window.audioNotifier.playRFIDSound();
            } catch(e) {
                console.log('No se pudo reproducir el audio:', e);
            }
        }
        
        // Mostrar notificación visual
        showMQTTNotification(`RFID detectado: ${msg}`, 'success');
        
        // Buscar automáticamente si ya existe un usuario con esta tarjeta RFID
        searchByRFID(msg);
    }
}

// Mostrar notificación MQTT
function showMQTTNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
        animation: slideInRight 0.3s ease-out;
    `;
    notification.innerHTML = `
        <i class="fa ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
        <strong>${message}</strong>
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 5000);
}

// Opciones de conexión MQTT
const mqttOptions = {
    clientId: 'habitant_' + cadenaAleatoria,
    username: 'jose',
    password: 'public',
    keepalive: 60,
    clean: true,
    connectTimeout: 4000,
};

// URL del WebSocket MQTT
const MQTT_WS_URL = 'wss://192.168.0.100:8074/mqtt'; // Usando wss como en el legacy

// Conectar MQTT
function connectMQTT() {
    if (mqttConnected) {
        console.log('MQTT ya está conectado');
        return;
    }
    
    const statusElement = document.getElementById('mqtt_status');
    statusElement.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Conectando...';
    statusElement.className = 'badge badge-warning';
    
    try {
        mqttClient = mqtt.connect(MQTT_WS_URL, mqttOptions);
        
        mqttClient.on('connect', () => {
            console.log('MQTT conectado por WebSocket! Éxito!');
            mqttConnected = true;
            
            // Actualizar estado visual
            statusElement.innerHTML = '<i class="fa fa-check-circle"></i> Conectado';
            statusElement.className = 'badge badge-success';
            
            // Suscribirse a todos los dispositivos disponibles
            const deviceSelect = document.getElementById('device_id');
            const devices = [];
            
            for (let i = 0; i < deviceSelect.options.length; i++) {
                const deviceSerie = deviceSelect.options[i].value;
                if (deviceSerie !== 'default') {
                    devices.push(deviceSerie);
                }
            }
            
            // Suscribirse a topics de cada dispositivo
            devices.forEach(deviceSerie => {
                mqttClient.subscribe(`${deviceSerie}/access_query`, { qos: 0 }, (error) => {
                    if (error) {
                        console.error(`Error suscribiéndose a ${deviceSerie}/access_query:`, error);
                    } else {
                        console.log(`Suscrito a ${deviceSerie}/access_query`);
                    }
                });
                
                mqttClient.subscribe(`${deviceSerie}/scholar_query`, { qos: 0 }, (error) => {
                    if (error) {
                        console.error(`Error suscribiéndose a ${deviceSerie}/scholar_query:`, error);
                    } else {
                        console.log(`Suscrito a ${deviceSerie}/scholar_query`);
                    }
                });
            });
            
            // Publicar mensaje de prueba
            mqttClient.publish('smartlabs/habitant', 'Registro de usuarios conectado', (error) => {
                if (error) {
                    console.error('Error enviando mensaje de prueba:', error);
                } else {
                    console.log('Mensaje de prueba enviado exitosamente');
                }
            });
            
            showMQTTNotification('MQTT conectado exitosamente', 'success');
        });
        
        mqttClient.on('message', (topic, message) => {
            console.log('Mensaje recibido:', topic, '->', message.toString());
            process_msg(topic, message);
        });
        
        mqttClient.on('reconnect', () => {
            console.log('Reconectando MQTT...');
            statusElement.innerHTML = '<i class="fa fa-refresh fa-spin"></i> Reconectando...';
            statusElement.className = 'badge badge-warning';
        });
        
        mqttClient.on('error', (error) => {
            console.error('Error de conexión MQTT:', error);
            mqttConnected = false;
            statusElement.innerHTML = '<i class="fa fa-times-circle"></i> Error';
            statusElement.className = 'badge badge-danger';
            showMQTTNotification('Error de conexión MQTT: ' + error.message, 'danger');
        });
        
        mqttClient.on('close', () => {
            console.log('Conexión MQTT cerrada');
            mqttConnected = false;
            statusElement.innerHTML = '<i class="fa fa-times-circle"></i> Desconectado';
            statusElement.className = 'badge badge-secondary';
        });
        
    } catch (error) {
        console.error('Error iniciando conexión MQTT:', error);
        statusElement.innerHTML = '<i class="fa fa-times-circle"></i> Error';
        statusElement.className = 'badge badge-danger';
        showMQTTNotification('Error iniciando MQTT: ' + error.message, 'danger');
    }
}

// Función para comandos de dispositivos (opcional)
function sendDeviceCommand(action) {
    if (!mqttConnected || !mqttClient) {
        showMQTTNotification('MQTT no está conectado', 'warning');
        return;
    }
    
    const deviceSerie = document.getElementById("device_id").value;
    if (!deviceSerie || deviceSerie === 'default') {
        showMQTTNotification('Seleccione un dispositivo válido', 'warning');
        return;
    }
    
    const topic = `${deviceSerie}/command`;
    mqttClient.publish(topic, action, (error) => {
        if (error) {
            console.error(`Error enviando comando ${action}:`, error);
            showMQTTNotification(`Error enviando comando: ${error.message}`, 'danger');
        } else {
            console.log(`Comando ${action} enviado a ${deviceSerie}`);
            showMQTTNotification(`Comando ${action} enviado exitosamente`, 'success');
        }
    });
}

// Inicializar MQTT cuando la página cargue
document.addEventListener('DOMContentLoaded', function() {
    // Conectar automáticamente después de 2 segundos
    setTimeout(connectMQTT, 2000);
    
    // Reconectar automáticamente si se pierde la conexión
    setInterval(function() {
        if (!mqttConnected) {
            console.log('Intentando reconectar MQTT...');
            connectMQTT();
        }
    }, 30000); // Cada 30 segundos
});

// Agregar estilos CSS para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    #display_new_access {
        min-height: 20px;
        display: inline-block;
    }
    
    /* Estilos para el buscador de usuarios */
    #user_search {
        border: 2px solid #17a2b8;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    #user_search:focus {
        border-color: #138496;
        box-shadow: 0 0 10px rgba(23, 162, 184, 0.3);
        transform: scale(1.02);
    }
    
    .search-result-row {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .search-result-row:hover {
        background-color: #f8f9fa !important;
        transform: translateX(5px);
    }
    
    .search-result-row.table-warning {
        background-color: #fff3cd !important;
        border-left: 4px solid #ffc107;
    }
    
    #search_results {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
        margin-top: 15px;
    }
    
    .btn-info:hover {
        transform: scale(1.05);
    }
    
    .btn-danger:hover {
        transform: scale(1.05);
    }
    
    /* Animación para los campos del formulario cuando se llenan */
    .form-control.filled {
        background-color: #d4edda;
        border-color: #28a745;
        animation: fillSuccess 0.5s ease;
    }
    
    @keyframes fillSuccess {
        0% { background-color: #ffffff; }
        50% { background-color: #d1ecf1; }
        100% { background-color: #d4edda; }
    }
`;
document.head.appendChild(style);

</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>