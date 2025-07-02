<?php 
$title = "USUARIO PRESTAMOS - SMARTLABS";
include __DIR__ . '/../layout/header.php'; 
?>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

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
              <div class="md-form-group" style="margin-left:25px">
                <input name="registration" id="registration" type="text" placeholder="Ej: 5242243191" class="md-input" value="" required>
                <label><i class="fa fa-credit-card"></i> RFID USER: </label>
              </div>
              <br>
            </div> 
          </div>
          
          <div id="resultado_"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="libs/jquerymin/jquery.min.js"></script>
<script src="libs/mqtt/dist/mqtt.min.js"></script>

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

var audio = new Audio('public/audio/audio.mp3');
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
    // Almacenar el valor RFID para uso posterior
    currentRfid = msg;
    
    // Mostrar en display de nuevo acceso
    document.getElementById('display_new_access').innerHTML = 'Nuevo acceso: ' + msg;
    
    // Establecer el valor en el campo de entrada
    document.getElementById('registration').value = msg;
    
    // Realizar la consulta automáticamente
    $.ajax({
      url: '/Loan/index',
      method: 'POST',
      data: { consult_loan: msg },
      success: function(data) {
        $('#resultado_').html(""); 
        var data_ = cortarDespuesDeDoctype(data);
        $('#resultado_').html(data_);
        console.log("Consulta MQTT loan_queryu:", data);
      },
      error: function() {
        $('#resultado_').html('<div class="alert alert-danger">Error al consultar préstamos</div>');
      }
    });
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

// URL de conexión WebSocket
const WebSocket_URL = 'wss://192.168.0.100:8074/mqtt'
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

/*
******************************
****** INICIALIZACION ********
******************************
*/
$(document).ready(function() {
    // Auto-focus en el campo de entrada
    $('#registration').focus();
    
    // Auto-submit cuando se escribe en el input (como en dash_loan.php)
    $('#registration').on('input', function() {
        var valorInput = $(this).val();
        
        // Almacenar el valor actual como RFID
        currentRfid = valorInput;
        
        // Enviar el valor al controlador mediante AJAX (auto-submit)
        if (valorInput.length > 0) {
            $.ajax({
                url: '/Loan/index', // Controlador que maneja la consulta
                method: 'POST',
                data: { consult_loan: valorInput },
                success: function(data) {
                  $('#resultado_').html(""); 
                  var data_ = cortarDespuesDeDoctype(data);
                  $('#resultado_').html(data_); // Mostrar resultado
                  console.log("Consulta auto-submit:", data);
                },
                error: function() {
                  $('#resultado_').html('<div class="alert alert-danger">Error al consultar préstamos</div>');
                }
            });
        } else {
            // Limpiar resultados si el campo está vacío
            $('#resultado_').html('');
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
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?> 