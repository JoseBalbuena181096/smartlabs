<?php 
$title = "USUARIO PRESTAMOS - SMARTLABS";
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

var audio = new Audio('/public/audio/audio.mp3');
// Variable para almacenar el RFID actual
var currentRfid = '';

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
    audio.play().catch(function(error) {
      console.log("Error al reproducir audio:", error);
    });
    
    // Mostrar en display de nuevo acceso
    document.getElementById('display_new_access').innerHTML = 'Procesando RFID: ' + sanitizedRfid;
    
    // Procesar RFID con lógica inteligente de sesiones
    processRfidWithSessionLogic(sanitizedRfid, serial_number);
  } 
  else if (query == "loan_querye") {
    // Usar el RFID almacenado para refrescar la tabla de préstamos
    if(currentRfid) {
      // Reproducir audio de notificación
      audio.play().catch(function(error) {
        console.log("Error al reproducir audio:", error);
      });
      
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
****** CONEXION MQTT *********
******************************
*/
// Opciones de conexión
const options = {
    // Autenticación
    clientId: 'iotmc'+generarCadenaAleatoria(6),
    username: 'jose',
    password: 'public',
    keepalive: 60,
    clean: true,
    connectTimeout: 4000,
}

var connected = false;

// Configuración dinámica de URL MQTT WebSocket
let WebSocket_URL;
const hostname = window.location.hostname;

console.log('🔧 Detectando configuración MQTT para hostname:', hostname);

// Determinar URL correcta basada en el hostname
if (hostname === 'localhost' || hostname === '127.0.0.1') {
    // Acceso desde localhost - usar WSS seguro
    WebSocket_URL = 'wss://localhost:8074/mqtt';
    console.log('📡 Configuración MQTT: Acceso local detectado (WSS)');
} else if (hostname === '192.168.0.100') {
    // Acceso desde IP externa - usar WS no seguro para evitar problemas de certificados
    WebSocket_URL = 'ws://192.168.0.100:8073/mqtt';
    console.log('📡 Configuración MQTT: Acceso desde red externa detectado (WS)');
} else {
    // Fallback - usar WS no seguro
    WebSocket_URL = `ws://${hostname}:8073/mqtt`;
    console.log('📡 Configuración MQTT: Usando hostname dinámico (WS)');
}

console.log('📡 URL MQTT WebSocket:', WebSocket_URL);
const client = mqtt.connect(WebSocket_URL, options);

client.on('connect', () => {
    console.log('MQTT conectado por WS! Éxito!')
    connected = true;

    // Suscribirse a los topics de préstamos
    client.subscribe('+/loan_queryu', { qos: 0 }, (error) => {
        if (error) {
            console.log('Error suscribiendo a loan_queryu:', error);
        } else {
            console.log('Suscrito a +/loan_queryu');
        }
    });
    
    client.subscribe('+/loan_querye', { qos: 0 }, (error) => {
        if (error) {
            console.log('Error suscribiendo a loan_querye:', error);
        } else {
            console.log('Suscrito a +/loan_querye');
        }
    });

    // Publicar mensaje de conexión
    client.publish('fabrica', 'Sistema de préstamos conectado', (error) => {
      console.log(error || 'Mensaje de conexión enviado');
    })
})

client.on('message', (topic, message) => {
  console.log('Mensaje recibido bajo tópico: ', topic, ' -> ', message.toString());
  process_msg(topic, message);
})

client.on('reconnect', (error) => {
    console.log('Intentando reconectar MQTT...', error)
    connected = false;
})

client.on('error', (error) => {
    console.log('Error de conexión MQTT:', error)
    connected = false;
})

client.on('disconnect', () => {
    console.log('MQTT desconectado')
    connected = false;
})

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
    
    // Paso 1: Validar RFID usando la API Flutter
    validateRfidWithApi(rfid)
        .then(function(isValid) {
            if (!isValid) {
                console.log('❌ RFID inválido:', rfid);
                document.getElementById('display_new_access').innerHTML = 
                    '<span class="text-danger"><i class="fa fa-times-circle"></i> RFID inválido: ' + rfid + '</span>';
                return;
            }
            
            console.log('✅ RFID válido:', rfid);
            
            // Paso 2: Consultar estado actual de la sesión
            return checkSessionState();
        })
        .then(function(sessionState) {
            if (sessionState === undefined) return; // Error en validación RFID
            
            console.log('📊 Estado de sesión actual:', sessionState);
            
            // Paso 3: Decidir acción basada en el estado de la sesión
            if (sessionState.session_active === false) {
                // No hay sesión activa (estado = 1), enviar espacio en blanco y limpiar datos
                console.log('🔄 No hay sesión activa, enviando espacio en blanco y limpiando datos');
                $('#registration').val(' ');
                $('#resultado_').html(''); // Limpiar datos del usuario
                currentRfid = ''; // Limpiar RFID actual
                document.getElementById('display_new_access').innerHTML = 
                    '<span class="text-info"><i class="fa fa-sign-out"></i> Sesión cerrada</span>';
            } else {
                // Hay una sesión activa (estado = 0), mantener el RFID en el input y consultar préstamos
                console.log('🔄 Sesión activa detectada, manteniendo RFID y consultando préstamos');
                $('#registration').val(rfid);
                currentRfid = rfid;
                document.getElementById('display_new_access').innerHTML = 
                    '<span class="text-success"><i class="fa fa-sign-in"></i> Sesión activa: ' + rfid + '</span>';
                consultarPrestamosUsuario(rfid);
            }
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
function validateRfidWithApi(rfid) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'http://192.168.0.100:3001/api/users/rfid/' + encodeURIComponent(rfid),
            method: 'GET',
            timeout: 5000,
            success: function(response) {
                if (response.success && response.data) {
                    console.log('✅ Usuario encontrado:', response.data.name);
                    resolve(true);
                } else {
                    console.log('❌ Usuario no encontrado para RFID:', rfid);
                    resolve(false);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error validando RFID:', error);
                if (xhr.status === 404) {
                    resolve(false); // RFID no encontrado
                } else {
                    reject(error); // Error de conexión u otro
                }
            }
        });
    });
}

/**
 * Consulta el estado actual de la sesión usando la API Flutter
 */
function checkSessionState() {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'http://192.168.0.100:3001/api/prestamo/estado/',
            method: 'GET',
            timeout: 5000,
            success: function(response) {
                if (response.success) {
                    resolve(response.data);
                } else {
                    reject('Error obteniendo estado de sesión');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error consultando estado de sesión:', error);
                reject(error);
            }
        });
    });
}



/**
 * Consulta los préstamos de un usuario
 */
function consultarPrestamosUsuario(rfid) {
    $.ajax({
        url: '/Loan/index',
        method: 'POST',
        data: { consult_loan: rfid },
        success: function(data) {
            $('#resultado_').html(""); 
            var data_ = cortarDespuesDeDoctype(data);
            $('#resultado_').html(data_);
            console.log("✅ Préstamos consultados para:", rfid);
        },
        error: function() {
            $('#resultado_').html('<div class="alert alert-danger">Error al consultar préstamos</div>');
            console.error('❌ Error consultando préstamos para:', rfid);
        }
    });
}

/*
******************************
****** INICIALIZACION ********
******************************
*/
$(document).ready(function() {
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
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>