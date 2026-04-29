<?php 
$title = "USUARIO PRESTAMOS - SMARTLABS";
$config = include __DIR__ . '/../../config/app.php';
include __DIR__ . '/../layout/header.php'; 
?>

<!-- content -->
<div id="content" class="app-content box-shadow-z0" role="main">
  <div class="app-header white box-shadow">
    <div class="navbar navbar-toggleable-sm flex-row align-items-center">
      <!-- Open side - Navigation on mobile -->
      <a data-toggle="modal" data-target="#aside" class="hidden-lg-up mr-3">
        <i class="material-icons">&#xe5d2;</i>
      </a>
      <!-- / -->
      
      <div class="">
        <b id="display_new_access">  </b>
      </div>
      
      <!-- Page title -->
      <div class="mb-0 h5 no-wrap" id="pageTitle">Sistema de Autopréstamo SMARTLABS</div>
      
      <!-- Estado MQTT -->
      <div class="ml-3">
        <span id="mqtt_status"><span class="badge badge-secondary">MQTT Iniciando...</span></span>
      </div>
      
      <!-- Estado Watchdog -->
      <div class="ml-3">
        <span id="watchdog_status"><span class="badge badge-info">Watchdog Iniciando...</span></span>
      </div>
      
      <!-- Estado Audio -->
      <div class="ml-3">
        <span id="audio_status"></span>
      </div>

      <!-- navbar collapse -->
      <div class="collapse navbar-collapse" id="collapse">
        <!-- link and dropdown -->
        <ul class="nav navbar-nav mr-auto">
          <li class="nav-item dropdown">
            <a class="nav-link" href data-toggle="dropdown"></a>
            <div ui-include="'views/blocks/dropdown.new.html'"></div>
          </li>
        </ul>
        <div ui-include="'views/blocks/navbar.form.html'"></div>
        <!-- / -->
      </div>
      <!-- / navbar collapse -->

      <!-- BARRA DE LA DERECHA -->
      <ul class="nav navbar-nav ml-auto flex-row">
        <li class="nav-item dropdown pos-stc-xs">
          <a class="nav-link mr-2" href data-toggle="dropdown"></a>
          <div ui-include="'views/blocks/dropdown.notification.html'"></div>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link p-0 clear" href="#" data-toggle="dropdown"></a>
          <div ui-include="'views/blocks/dropdown.user.html'"></div>
        </li>
        <li class="nav-item hidden-md-up">
          <a class="nav-link pl-2" data-toggle="collapse" data-target="#collapse"></a>
        </li>
      </ul>
      <!-- / navbar right -->
    </div>
  </div>
  
  <!-- PIE DE PAGINA -->
  <div class="app-footer">
    <div class="p-2 text-xs">
      <div class="pull-right text-muted py-1">
        <strong> SMARTLABS</strong> <span class="hidden-xs-down">- Built BY Jose Angel Balbuena</span>
        <a ui-scroll-to="content"><i class="fa fa-long-arrow-up p-x-sm"></i></a>
      </div>
      <div class="nav"></div>
    </div>
  </div>
  
  <div ui-view class="app-body" id="view">
    <!-- SECCION CENTRAL -->
    <div class="padding">
      <div class="row">
        <div class="col-sm-12">
          <div class="box">
            <div class="box-header">
              <h2><i class="fa fa-cart-arrow-down"></i> USUARIO PRESTAMOS</h2>
              <small>Sistema de consulta de préstamos mediante RFID</small>
            </div>
            <div class="box-body">
              <!-- Búsqueda por RFID -->
              <div class="row mb-4">
                <div class="col-md-6">
                  <div class="card">
                    <div class="card-header bg-primary text-white">
                      <h5 class="mb-0"><i class="fa fa-credit-card"></i> Búsqueda por RFID</h5>
                    </div>
                    <div class="card-body">
                      <div class="md-form-group" style="margin-left:25px">
                        <input name="registration" id="registration" type="text" placeholder="Ej: 5242243191" class="md-input" value="" required>
                        <label><i class="fa fa-credit-card"></i> RFID USER: </label>
                      </div>
                      <small class="text-muted ml-4">
                        <i class="fa fa-info-circle"></i> Ingresa el RFID del usuario o utiliza el lector automático
                      </small>
                    </div>
                  </div>
                </div>
                
                <!-- Búsqueda por Usuario -->
                <div class="col-md-6">
                  <div class="card search-card-user">
                    <div class="card-header bg-info text-white">
                      <h5 class="mb-0"><i class="fa fa-search-plus"></i> Búsqueda por Usuario</h5>
                    </div>
                    <div class="card-body">
                      <div class="input-group">
                        <input type="text" id="userSearch" class="form-control" placeholder="Buscar por matrícula, nombre o correo..." autocomplete="off">
                        <div class="input-group-append">
                          <button class="btn btn-info" type="button" id="searchBtn">
                            <i class="fa fa-search"></i> BUSCAR
                          </button>
                        </div>
                      </div>
                      <small class="text-muted">
                        <i class="fa fa-info-circle"></i> <span id="searchPlaceholder">Busca automáticamente por matrícula, nombre y correo electrónico</span>
                      </small>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Resultados de búsqueda de usuarios -->
              <div class="row mb-4">
                <div class="col-md-12">
                  <div id="searchResults" class="search-results-enter" style="display: none;">
                    <div class="card">
                      <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fa fa-users"></i> Resultados de Búsqueda</h6>
                      </div>
                      <div class="card-body">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                          <table class="table table-sm table-hover">
                            <thead class="bg-light">
                              <tr>
                                <th width="5%"><i class="fa fa-check-circle text-success"></i></th>
                                <th><i class="fa fa-user text-primary"></i> Usuario</th>
                                <th><i class="fa fa-id-badge text-info"></i> Matrícula</th>
                                <th><i class="fa fa-envelope text-warning"></i> Email</th>
                                <th><i class="fa fa-tags text-success"></i> Coincidencia</th>
                              </tr>
                            </thead>
                            <tbody id="searchResultsBody">
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
          
          <div id="resultado_"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Estilos para las pestañas y búsqueda -->
<style>
  .search-card-user {
    transition: all 0.3s ease;
    border: 2px solid #17a2b8;
  }
  .search-card-user:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(23, 162, 184, 0.2);
  }
  .search-results-enter {
    animation: slideDown 0.3s ease-out;
  }
  @keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .user-row:hover {
    background-color: #f0f9ff !important;
    transform: scale(1.02);
    transition: all 0.2s ease;
  }
  .user-row.table-success {
    background-color: #d4edda !important;
    border-color: #c3e6cb !important;
  }
  .card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .card-header {
    border-top-left-radius: 8px !important;
    border-top-right-radius: 8px !important;
  }
</style>

<!-- Scripts -->
<script src="libs/jquerymin/jquery.min.js"></script>
<script src="/libs/mqtt/dist/mqtt.min.js"></script>

<script type="text/javascript">
/*
******************************
****** FUNCIONES NAVEGACION **
******************************
*/
function dashboardLab(){
  window.location.href = "/Dashboard";
}

function devicesLab(){
  window.location.href = "/Device";
}

function registerUserLab(){
  window.location.href = "/Habitant";
}

function eliminarUsuario() {
  window.location.href = "/Habitant/delete";
}
       
function horasUso() {
  window.location.href = "/Stats";
}

/*
******************************
****** VARIABLES GLOBALES ****
******************************
*/
function generarCadenaAleatoria(longitud) {
  const caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  let cadenaAleatoria = '';

  for (let i = 0; i < longitud; i++) {
    const indiceAleatorio = Math.floor(Math.random() * caracteres.length);
    cadenaAleatoria += caracteres.charAt(indiceAleatorio);
  }

  return cadenaAleatoria;
}

const cadenaAleatoria = generarCadenaAleatoria(6);

// Sistema de audio mejorado que cumple con políticas de autoplay
var audio = new Audio('/public/audio/audio.mp3');
var audioEnabled = false;
var audioInitialized = false;

// Variable para almacenar el RFID actual
var currentRfid = '';

// Función para inicializar el audio después de interacción del usuario
function initializeAudio() {
    if (!audioInitialized) {
        audio.load();
        audio.play().then(() => {
            audio.pause();
            audio.currentTime = 0;
            audioEnabled = true;
            audioInitialized = true;
            console.log('✅ Audio habilitado después de interacción del usuario');
            
            // Mostrar notificación de audio habilitado
            showAudioNotification('🔊 Audio habilitado', 'success');
        }).catch((error) => {
            console.log('❌ No se pudo habilitar el audio:', error);
            audioEnabled = false;
            showAudioNotification('🔇 Audio deshabilitado por el navegador', 'warning');
        });
    }
}

// Función para reproducir audio de forma segura
function playNotificationSound() {
    if (audioEnabled && audioInitialized) {
        audio.currentTime = 0;
        audio.play().catch(function(error) {
            console.log('❌ Error al reproducir audio:', error);
            showAudioNotification('🔇 Error reproduciendo audio', 'warning');
        });
    } else {
        console.log('🔇 Audio no disponible - se requiere interacción del usuario');
        showAudioNotification('🔇 Haz clic para habilitar audio', 'info');
    }
}

// Función para mostrar notificaciones de audio
function showAudioNotification(message, type) {
    const colors = {
        success: 'text-success',
        warning: 'text-warning', 
        info: 'text-info',
        error: 'text-danger'
    };
    
    const audioStatus = document.getElementById('audio_status');
    if (audioStatus) {
        audioStatus.innerHTML = `<small class="${colors[type]}">${message}</small>`;
        
        // Auto-ocultar después de 3 segundos para mensajes informativos
        if (type === 'info' || type === 'success') {
            setTimeout(() => {
                audioStatus.innerHTML = '';
            }, 3000);
        }
    }
}

/*
******************************
****** PROCESAMIENTO MQTT ****
******************************
*/
function process_msg(topic, message){
  var msg = message.toString();
  var splitted_topic = topic.split("/");
  var serial_number = splitted_topic[0];
  var query = splitted_topic[1];

  if (query == "loan_queryu") {
    // Sanear el RFID eliminando prefijo "APP:" si existe
    var sanitizedRfid = sanitizeRfid(msg);
    
    // Reproducir audio de notificación
    playNotificationSound();
    
    // Mostrar en display de nuevo acceso
    document.getElementById('display_new_access').innerHTML = 'Procesando RFID: ' + sanitizedRfid;
    
    // Procesar RFID con lógica inteligente de sesiones
    processRfidWithSessionLogic(sanitizedRfid, serial_number);
  } 
  else if (query == "loan_querye") {
    // Usar el RFID almacenado para refrescar la tabla de préstamos
    if(currentRfid) {
      // Reproducir audio de notificación
      playNotificationSound();
      
      // Refrescar los datos de préstamos usando el RFID almacenado
      $.ajax({
        url: '/Loan/index',
        method: 'POST',
        data: { consult_loan: currentRfid },
        success: function(data) {
          $('#resultado_').html(""); 
          var data_ = cortarDespuesDeDoctype(data);
          $('#resultado_').html(data_);
          console.log("Refreshed loan data loan_querye");
        },
        error: function() {
          $('#resultado_').html('<div class="alert alert-danger">Error al refrescar préstamos</div>');
        }
      });
    } else {
      console.log("No hay RFID disponible para refrescar datos de préstamos");
    }
  }
}

/*
******************************
****** CONEXION MQTT MEJORADA *
******************************
*/
// NOTA: El cliente MQTT mejorado se inicializa automáticamente desde loan-mqtt-improved.js
// Este código mantiene compatibilidad con el código existente

// Variables globales para compatibilidad
var connected = false;
var client = null;

// Función de compatibilidad para process_msg (mantenida para el código existente)
function process_msg(topic, message) {
    // Esta función ahora es manejada por el cliente MQTT mejorado
    // pero se mantiene para compatibilidad con código legacy
    console.log('⚠️ process_msg legacy llamada:', topic, message.toString());
}

/*
******************************
****** FUNCIONES AUXILIARES **
******************************
*/
function cortarDespuesDeDoctype(inputString) {
    const doctype = '<!DOCTYPE html>';
    const doctypeIndex = inputString.indexOf(doctype);
    
    // Si la frase no se encuentra, devolver el string completo
    if (doctypeIndex === -1) {
        return inputString;
    }
    
    // Cortar el string desde el inicio hasta el final de la frase <!DOCTYPE html>
    return inputString.substring(0, doctypeIndex + doctype.length);
}

// Función saneadora para eliminar prefijo "APP:" del RFID
function sanitizeRfid(rfidInput) {
    if (typeof rfidInput === 'string' && rfidInput.startsWith('APP:')) {
        return rfidInput.substring(4); // Eliminar los primeros 4 caracteres "APP:"
    }
    return rfidInput;
}

/*
******************************
****** LÓGICA DE SESIONES ****
******************************
*/

/**
 * Procesa un RFID con lógica inteligente de sesiones
 * 1. Valida el RFID usando la API Flutter
 * 2. Consulta el estado actual de la sesión
 * 3. Decide si mantener o limpiar el input según el estado
 */
function processRfidWithSessionLogic(rfid, deviceSerial) {
    console.log('🔄 Procesando RFID con lógica de sesiones:', rfid);

    // Saltamos validateRfidWithApi cuando ya hay una sesión activa: el UID
    // que llegó puede ser un equipo (loan_querye) y la api 404 nos haría
    // pensar que la tarjeta no sirve.
    checkSessionState()
        .then(function(sessionState) {
            console.log('📊 Estado de sesión actual:', sessionState);

            if (sessionState && sessionState.session_active === true) {
                // Sesión abierta: usar el RFID del usuario de la sesión, no
                // el rfid que pasamos (que puede ser equipo).
                const userRfid = sessionState.cards_number || rfid;
                $('#registration').val(userRfid);
                window.currentRfid = userRfid;
                document.getElementById('display_new_access').innerHTML =
                    '<span class="text-success"><i class="fa fa-sign-in"></i> Sesión activa: ' + (sessionState.user || userRfid) + '</span>';
                consultarPrestamosUsuario(userRfid);
                return;
            }

            // Sesión inactiva: limpiar pantalla
            console.log('🔄 No hay sesión activa, limpiando datos');
            $('#registration').val(' ');
            $('#resultado_').html('');
            window.currentRfid = '';
            document.getElementById('display_new_access').innerHTML =
                '<span class="text-info"><i class="fa fa-sign-out"></i> Sesión cerrada</span>';
        })
        .catch(function(error) {
            console.error('❌ Error procesando RFID:', error);
            document.getElementById('display_new_access').innerHTML = 
                '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> Error procesando RFID</span>';
        });
}

/**
 * Valida un RFID usando la API Flutter
 */
function validateRfidWithApi(rfid, retryCount = 0) {
    const maxRetries = 3;
    const retryDelay = 2000; // 2 segundos
    
    return new Promise(function(resolve, reject) {
        // Construir URL de forma más robusta
        var apiHost = '<?php echo !empty($config["api_host"]) ? $config["api_host"] : "192.168.0.100"; ?>';
        var apiUrl = 'http://' + apiHost + ':3000/api/users/rfid/' + encodeURIComponent(rfid);
        
        console.log('🔍 API URL construida:', apiUrl);
        
        $.ajax({
            url: apiUrl,
            method: 'GET',
            timeout: 10000, // Aumentado a 10 segundos
            cache: false,
            beforeSend: function() {
                // Notificar al watchdog de actividad AJAX
                if (window.connectionWatchdog) {
                    window.connectionWatchdog.state.ajaxLastActivity = Date.now();
                }
            },
            success: function(response) {
                // Notificar al watchdog de éxito
                if (window.connectionWatchdog) {
                    window.connectionWatchdog.state.ajaxLastActivity = Date.now();
                    window.connectionWatchdog.state.ajaxFailures = 0;
                }
                
                if (response.success && response.data) {
                    console.log('✅ Usuario encontrado:', response.data.name);
                    resolve(true);
                } else {
                    console.log('❌ Usuario no encontrado para RFID:', rfid);
                    resolve(false);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error validando RFID:', { xhr, status, error, retryCount });
                
                // Si es 404, no reintentar
                if (xhr.status === 404) {
                    resolve(false);
                    return;
                }
                
                // Si es error de sesión, manejar apropiadamente
                if (xhr.status === 401 || xhr.status === 403) {
                    console.log('🔒 Error de autenticación detectado');
                    reject({ type: 'auth_error', error });
                    return;
                }
                
                // Reintentar si no hemos alcanzado el máximo
                if (retryCount < maxRetries) {
                    console.log(`🔄 Reintentando validación RFID (${retryCount + 1}/${maxRetries})`);
                    setTimeout(() => {
                        validateRfidWithApi(rfid, retryCount + 1)
                            .then(resolve)
                            .catch(reject);
                    }, retryDelay * (retryCount + 1)); // Backoff exponencial
                } else {
                    reject({ type: 'network_error', error });
                }
            }
        });
    });
}

/**
 * Consulta el estado actual de la sesión usando la API Flutter
 */
function checkSessionState(retryCount = 0) {
    const maxRetries = 3;
    const retryDelay = 2000;
    
    return new Promise(function(resolve, reject) {
        // Construir URL de forma más robusta
        var apiHost = '<?php echo !empty($config["api_host"]) ? $config["api_host"] : "192.168.0.100"; ?>';
        var apiUrl = 'http://' + apiHost + ':3000/api/prestamo/estado/';
        
        console.log('🔍 Session API URL construida:', apiUrl);
        
        $.ajax({
            url: apiUrl,
            method: 'GET',
            timeout: 10000, // Aumentado a 10 segundos
            cache: false,
            beforeSend: function() {
                // Notificar al watchdog de actividad AJAX
                if (window.connectionWatchdog) {
                    window.connectionWatchdog.state.ajaxLastActivity = Date.now();
                }
            },
            success: function(response) {
                // Notificar al watchdog de éxito
                if (window.connectionWatchdog) {
                    window.connectionWatchdog.state.ajaxLastActivity = Date.now();
                    window.connectionWatchdog.state.ajaxFailures = 0;
                }
                
                console.log('✅ Estado de sesión verificado:', response);
                if (response.success) {
                    resolve(response.data);
                } else {
                    reject('Error obteniendo estado de sesión');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error verificando estado de sesión:', { xhr, status, error, retryCount });
                
                // Si es error de autenticación, no reintentar
                if (xhr.status === 401 || xhr.status === 403) {
                    console.log('🔒 Error de autenticación en estado de sesión');
                    reject({ type: 'auth_error', error });
                    return;
                }
                
                // Reintentar si no hemos alcanzado el máximo
                if (retryCount < maxRetries) {
                    console.log(`🔄 Reintentando verificación de estado (${retryCount + 1}/${maxRetries})`);
                    setTimeout(() => {
                        checkSessionState(retryCount + 1)
                            .then(resolve)
                            .catch(reject);
                    }, retryDelay * (retryCount + 1));
                } else {
                    reject({ type: 'network_error', error });
                }
            }
        });
    });
}



/**
 * Consulta los préstamos de un usuario
 */
function consultarPrestamosUsuario(rfid, retryCount = 0) {
    const maxRetries = 3;
    const retryDelay = 2000;
    
    $.ajax({
        url: '/Loan/index',
        method: 'POST',
        data: { consult_loan: rfid, _csrf: '<?php echo htmlspecialchars($csrf ?? Controller::csrfToken()); ?>' },
        timeout: 15000, // 15 segundos para operaciones de base de datos
        cache: false,
        beforeSend: function() {
            console.log('🔄 Consultando préstamos para RFID:', rfid);
            
            // Notificar al watchdog de actividad AJAX
            if (window.connectionWatchdog) {
                window.connectionWatchdog.state.ajaxLastActivity = Date.now();
            }
        },
        success: function(data) {
            // Notificar al watchdog de éxito
            if (window.connectionWatchdog) {
                window.connectionWatchdog.state.ajaxLastActivity = Date.now();
                window.connectionWatchdog.state.ajaxFailures = 0;
            }
            
            $('#resultado_').html(""); 
            var data_ = cortarDespuesDeDoctype(data);
            $('#resultado_').html(data_);
            console.log("✅ Préstamos consultados para:", rfid);
        },
        error: function(xhr, status, error) {
            console.error('❌ Error consultando préstamos:', { xhr, status, error, retryCount });
            
            // Si es error de autenticación, redirigir al login
            if (xhr.status === 401 || xhr.status === 403) {
                console.log('🔒 Error de autenticación, redirigiendo al login');
                window.location.href = '/Auth/login';
                return;
            }
            
            // Reintentar si no hemos alcanzado el máximo
            if (retryCount < maxRetries) {
                console.log(`🔄 Reintentando consulta de préstamos (${retryCount + 1}/${maxRetries})`);
                setTimeout(() => {
                    consultarPrestamosUsuario(rfid, retryCount + 1);
                }, retryDelay * (retryCount + 1));
            } else {
                $('#resultado_').html('<div class="alert alert-danger">Error al consultar préstamos después de varios intentos. Por favor, recarga la página.</div>');
                console.error('❌ Error consultando préstamos para:', rfid);
            }
        }
    });
}

/*
******************************
****** INICIALIZACION ********
******************************
*/
$(document).ready(function() {
    // Inicializar audio con interacción del usuario
    function setupAudioInitialization() {
        // Eventos para inicializar audio en primera interacción
        const events = ['click', 'touchstart', 'keydown'];
        
        function handleFirstInteraction() {
            initializeAudio();
            // Remover listeners después de la primera interacción
            events.forEach(event => {
                document.removeEventListener(event, handleFirstInteraction);
            });
        }
        
        // Agregar listeners para primera interacción
        events.forEach(event => {
            document.addEventListener(event, handleFirstInteraction, { once: true });
        });
        
        // Mostrar mensaje inicial
        showAudioNotification('🔇 Haz clic en cualquier lugar para habilitar audio', 'info');
    }
    
    // Configurar inicialización de audio
    setupAudioInitialization();
    
    // Auto-focus en el campo de entrada
    $('#registration').focus();
    
    // Auto-submit cuando se escribe en el input con lógica de sesiones
    $('#registration').on('input', function() {
        var valorInput = $(this).val();
        
        // Sanear el RFID eliminando prefijo "APP:" si existe
        var sanitizedRfid = sanitizeRfid(valorInput);
        
        // Si el valor fue saneado, actualizar el campo
        if (sanitizedRfid !== valorInput) {
            $(this).val(sanitizedRfid);
            valorInput = sanitizedRfid;
        }
        
        // Procesar con lógica de sesiones si hay valor
        if (valorInput.length > 0) {
            // Usar la nueva lógica de sesiones para input manual
            processRfidWithSessionLogic(sanitizedRfid, 'WEBAPP001');
        } else {
            // Limpiar resultados si el campo está vacío
            $('#resultado_').html('');
            currentRfid = '';
            document.getElementById('display_new_access').innerHTML = 
                '<span class="text-muted"><i class="fa fa-info-circle"></i> Ingresa un RFID</span>';
        }
    });
    
    // Indicador de conexión MQTT
    setInterval(function() {
        if (connected) {
            $('#display_new_access').html('<span class="text-success"><i class="fa fa-wifi"></i> MQTT Conectado</span>');
        } else {
            $('#display_new_access').html('<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> MQTT Desconectado</span>');
        }
    }, 5000);
    
    console.log('Sistema de préstamos SMARTLABS inicializado');
    
    // Inicializar funcionalidad de búsqueda de usuarios
    initializeUserSearch();
});

/*
******************************
****** BÚSQUEDA DE USUARIOS **
******************************
*/
function initializeUserSearch() {
    var searchTimeout;
    
    // Búsqueda en tiempo real con debounce
    $('#userSearch').on('input', function() {
        clearTimeout(searchTimeout);
        var query = $(this).val().trim();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(function() {
                buscarUsuarios(query);
            }, 500); // Debounce de 500ms
        } else {
            $('#searchResults').hide();
            $('#searchPlaceholder').html('<i class="fa fa-info-circle"></i> Busca automáticamente por matrícula, nombre y correo electrónico');
        }
    });
    
    // Botón de búsqueda
    $('#searchBtn').click(function() {
        var query = $('#userSearch').val().trim();
        
        if (query.length >= 1) {
            buscarUsuarios(query);
        } else {
            $('#searchPlaceholder').html('<i class="fa fa-exclamation-triangle text-warning"></i> Escribe al menos 1 carácter para buscar');
        }
    });
}

// Función para buscar usuarios
function buscarUsuarios(query) {
    $.ajax({
        url: '/LoanAdmin/index', // Reutilizamos el endpoint de LoanAdmin
        method: 'POST',
        data: {
            search_user: query
        },
        beforeSend: function() {
            $('#searchResultsBody').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Buscando usuarios...</td></tr>');
            $('#searchResults').fadeIn(300);
        },
        success: function(response) {
            try {
                var users = JSON.parse(response);
                mostrarResultadosBusqueda(users);
                
                // Actualizar contador
                if (users.length > 0) {
                    $('#searchPlaceholder').html('<i class="fa fa-check-circle text-success"></i> ' + users.length + ' usuario' + (users.length > 1 ? 's' : '') + ' encontrado' + (users.length > 1 ? 's' : ''));
                } else {
                    $('#searchPlaceholder').html('<i class="fa fa-exclamation-triangle text-warning"></i> No se encontraron usuarios');
                }
            } catch (e) {
                $('#searchResultsBody').html('<tr><td colspan="5" class="text-center text-danger"><i class="fa fa-exclamation-triangle"></i> Error al procesar resultados</td></tr>');
                $('#searchPlaceholder').html('<i class="fa fa-times-circle text-danger"></i> Error en la búsqueda');
            }
        },
        error: function() {
            $('#searchResultsBody').html('<tr><td colspan="5" class="text-center text-danger"><i class="fa fa-times-circle"></i> Error en la búsqueda</td></tr>');
            $('#searchPlaceholder').html('<i class="fa fa-times-circle text-danger"></i> Error de conexión');
        }
    });
}

// Mostrar resultados de búsqueda
function mostrarResultadosBusqueda(users) {
    var html = '';
    
    if (users.length === 0) {
        html = '<tr><td colspan="5" class="text-center text-muted"><i class="fa fa-search"></i> No se encontraron usuarios</td></tr>';
    } else {
        users.forEach(function(user, index) {
            html += '<tr class="user-row" style="cursor: pointer;" data-rfid="' + user.cards_number + '">';
            html += '<td><input type="radio" name="selectedUser" value="' + user.cards_number + '"></td>';
            html += '<td><strong><i class="fa fa-user text-primary mr-1"></i>' + user.hab_name + '</strong></td>';
            html += '<td><span class="badge badge-primary"><i class="fa fa-id-badge mr-1"></i>' + user.hab_registration + '</span></td>';
            html += '<td><small><i class="fa fa-envelope text-muted mr-1"></i>' + user.hab_email + '</small></td>';
            
            // Mostrar tipo de coincidencia
            var coincidencia = '';
            if (user.match_type) {
                switch(user.match_type) {
                    case 'matricula':
                        coincidencia = '<span class="badge badge-info"><i class="fa fa-id-badge"></i> Matrícula</span>';
                        break;
                    case 'nombre':
                        coincidencia = '<span class="badge badge-success"><i class="fa fa-user"></i> Nombre</span>';
                        break;
                    case 'email':
                        coincidencia = '<span class="badge badge-warning"><i class="fa fa-envelope"></i> Correo</span>';
                        break;
                    default:
                        coincidencia = '<span class="badge badge-secondary"><i class="fa fa-search"></i> Múltiple</span>';
                }
            } else {
                coincidencia = '<span class="badge badge-secondary"><i class="fa fa-search"></i> General</span>';
            }
            html += '<td>' + coincidencia + '</td>';
            html += '</tr>';
        });
    }
    
    $('#searchResultsBody').html(html);
    
    // Hacer las filas clickeables
    $('#searchResultsBody tr[data-rfid]').click(function() {
        var rfid = $(this).data('rfid');
        var radio = $(this).find('input[type="radio"]');
        
        // Seleccionar radio button
        radio.prop('checked', true);
        
        // Animar la selección
        $(this).addClass('table-success').siblings().removeClass('table-success');
        
        // Mostrar feedback visual
        $(this).find('td:first').html('<i class="fa fa-check-circle text-success fa-lg"></i>');
        
        // Auto-consultar préstamos después de 1 segundo
        setTimeout(function() {
            currentRfid = rfid;
            
            // Consultar préstamos automáticamente
            $.ajax({
                url: '/Loan/index',
                method: 'POST',
                data: { consult_loan: rfid },
                success: function(data) {
                    $('#resultado_').html("");
                    var data_ = cortarDespuesDeDoctype(data);
                    $('#resultado_').html(data_);
                    
                    // Limpiar buscador con animación
                    $('#userSearch').val('');
                    $('#searchResults').fadeOut(300);
                    $('#searchPlaceholder').html('<i class="fa fa-info-circle"></i> Busca automáticamente por matrícula, nombre y correo electrónico');
                    
                    // Mostrar mensaje de éxito
                    $('#display_new_access').html('<span class="text-success"><i class="fa fa-check-circle"></i> Usuario seleccionado: ' + rfid + '</span>');
                    
                    // Mostrar el RFID seleccionado en el input RFID
                    $('#registration').val(rfid);
                    
                    console.log("Consulta por usuario seleccionado:", data);
                },
                error: function() {
                    $('#resultado_').html('<div class="alert alert-danger">Error al consultar préstamos</div>');
                }
            });
        }, 1000);
    });
}

// Función para actualizar el estado del watchdog en la UI
function updateWatchdogStatus() {
    if (window.connectionWatchdog) {
        const status = window.connectionWatchdog.getStatus();
        const watchdogElement = document.getElementById('watchdog_status');
        
        if (status.isActive) {
            const mqttHealth = status.mqttHealthy ? '✅' : '❌';
            const ajaxHealth = status.ajaxHealthy ? '✅' : '❌';
            const uptime = Math.floor(status.uptime / 60000); // minutos
            
            watchdogElement.innerHTML = `<span class="badge badge-success" title="MQTT: ${mqttHealth} | AJAX: ${ajaxHealth} | Uptime: ${uptime}m">🐕 Watchdog Activo</span>`;
        } else {
            watchdogElement.innerHTML = '<span class="badge badge-warning">🐕 Watchdog Inactivo</span>';
        }
    } else {
        const watchdogElement = document.getElementById('watchdog_status');
        watchdogElement.innerHTML = '<span class="badge badge-secondary">🐕 Watchdog Iniciando...</span>';
    }
}

// Actualizar estado del watchdog cada 30 segundos
setInterval(updateWatchdogStatus, 30000);

// Actualizar estado inicial después de 6 segundos (para dar tiempo a que se inicialice)
setTimeout(updateWatchdogStatus, 6000);

// Función global para obtener diagnósticos completos
window.getDiagnostics = function() {
    const diagnostics = {
        timestamp: new Date().toISOString(),
        session: {
            keepAlive: window.sessionKeepAlive ? window.sessionKeepAlive.getStatus() : 'No disponible'
        },
        watchdog: window.connectionWatchdog ? window.connectionWatchdog.getStatus() : 'No disponible',
        mqtt: {
            improved: window.loanMqttClient ? window.loanMqttClient.getConnectionStatus() : 'No disponible',
            legacy: window.mqttClient ? 'Disponible' : 'No disponible'
        },
        currentRfid: window.currentRfid || 'Ninguno',
        pageUrl: window.location.href
    };
    
    console.log('📊 Diagnósticos del Sistema:', diagnostics);
    return diagnostics;
};

// Función global para forzar reconexión de todo
window.forceFullReconnect = function() {
    console.log('🔄 Forzando reconexión completa...');
    
    // Forzar reconexión del watchdog
    if (window.connectionWatchdog) {
        window.connectionWatchdog.forceReconnect();
    }
    
    // Forzar reconexión MQTT
    if (window.loanMqttClient) {
        window.loanMqttClient.handleDisconnection();
    }
    
    // Enviar keep-alive
    if (window.sessionKeepAlive) {
        window.sessionKeepAlive.sendKeepAlive();
    }
    
    console.log('✅ Reconexión completa iniciada');
};

</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>