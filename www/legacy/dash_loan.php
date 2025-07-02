<?php

session_start();
$logged = $_SESSION['logged'];

if(!$logged){
  echo "Ingreso no autorizado";
  die();
}

$devices = $_SESSION['devices'];
$users_traffic_device = [];

$array = array();

foreach ($devices as $device ) {
  array_push($array,$device['devices_serie']);
}


$db_host        = '192.168.0.100';
$db_user        = 'root';
$db_pass        = 'emqxpass';
$db_database    = 'emqx'; 
$db_port        = '4000';

//momento de conectarnos a db
$conn = mysqli_connect($db_host,$db_user,$db_pass,$db_database,$db_port);

if ($conn==false){
  echo "Hubo un problema al conectarse a María DB";
  die();
}

//declaramos variables vacias servirán también para repoblar el formulario
$registration_ = "";
$matricula_ = "";
$consult_loan = '';

// Función para consultar los préstamos del usuario y generar la tabla HTML
function consultarPrestamos($consult_loan, $conn) {
  $output = '';
  
  // Consultar nombre del usuario
  $result_name = $conn->query("SELECT * FROM `cards_habs` WHERE `cards_number` = '$consult_loan'");
  if ($result_name) {
    if ($row_name = $result_name->fetch_assoc()) {
      $output .= "<br>";
      $output .= '<h4 class="h4">Prestamos del usuario: </h4>';
      $output .= "<br>";
      $output .= '<h3 class="h3">' . $row_name["hab_name"] . "</h3>";
      $output .= "<br>";
    }
  }
  
  // Consultar préstamos
  $result_loans = $conn->query("SELECT * FROM `habslab` WHERE `loans_hab_rfid` = '$consult_loan' ORDER BY `loans_date` DESC"); 

  $filtered_loans = [];
  $seen_equipments = [];

  while ($row = $result_loans->fetch_assoc()) {
    $equipment_rfid = $row['equipments_rfid'];

    if (!isset($seen_equipments[$equipment_rfid])) {
      $seen_equipments[$equipment_rfid] = true;
      $filtered_loans[] = $row;
    }
  }
  
  // Generar tabla de préstamos
  if ($result_loans) {
    $output .= '<table class="table table-striped b-t">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th>DATE</th>';
    $output .= '<th>EQUIPMENT</th>';
    $output .= '<th>BRANCH</th>';
    $output .= '<th>STATE</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';
    
    foreach ($filtered_loans as $loan) {
      if($loan["loans_state"]=='1'){
        // Verificar si hay un registro de devolución más reciente
        $equip_rfid = $loan["equipments_rfid"];
        $loan_date = $loan["loans_date"];
        
        // Consultar si existe un registro de devolución más reciente para este equipo
        $check_query = "SELECT * FROM `habslab` WHERE 
                         `equipments_rfid` = '$equip_rfid' AND 
                         `loans_state` = '0' AND 
                         `loans_date` > '$loan_date' 
                         ORDER BY `loans_date` DESC LIMIT 1";
                         
        $check_result = $conn->query($check_query);
        
        // Si no hay devolución más reciente, mostrar como prestado
        if ($check_result->num_rows == 0) {
          $output .= '<tr>';
          $output .= "<td>" . $loan["loans_date"] . "</td>";
          $output .= "<td>" . $loan["equipments_name"] . "</td>";
          $output .= "<td>" . $loan["equipments_brand"] . "</td>";
          $output .= "<td>Prestado</td>";
          $output .= '</tr>';
        }
        // Si hay devolución más reciente, no mostrar este equipo
      }
    }
    
    $output .= '</tbody>';
    $output .= '</table>';
  } else {
    // Mostrar un mensaje de error si la consulta falla
    $output .= "Error en la consulta: " . $conn->error;
  }
  
  return $output;
}

// Procesar mediante AJAX cuando se recibe un valor del input
if (isset($_POST['consult_loan'])) {
  $consult_loan = strip_tags($_POST['consult_loan']);
  echo consultarPrestamos($consult_loan, $conn);
  // Terminar la ejecución para evitar que se muestre el resto de la página
  exit();
}

// Procesar mediante MQTT (se manejará con JavaScript)
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>SMARTLABS</title>
  <meta name="description" content="Admin, Dashboard, Bootstrap, Bootstrap 4, Angular, AngularJS" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- for ios 7 style, multi-resolution icon of 152x152 -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-barstyle" content="black-translucent">
  <link rel="apple-touch-icon" href="assets/images/logo.png">
  <meta name="apple-mobile-web-app-title" content="Flatkit">
  <!-- for Chrome on Android, multi-resolution icon of 196x196 -->
  <meta name="mobile-web-app-capable" content="yes">
  <link rel="shortcut icon" sizes="196x196" href="assets/images/descarga.png">

  <!-- style -->
  <link rel="stylesheet" href="assets/animate.css/animate.min.css" type="text/css" />
  <link rel="stylesheet" href="assets/glyphicons/glyphicons.css" type="text/css" />
  <link rel="stylesheet" href="assets/font-awesome/css/font-awesome.min.css" type="text/css" />
  <link rel="stylesheet" href="assets/material-design-icons/material-design-icons.css" type="text/css" />

  <link rel="stylesheet" href="assets/bootstrap/dist/css/bootstrap.min.css" type="text/css" />
  <!-- build:css assets/styles/app.min.css -->
  <link rel="stylesheet" href="assets/styles/app.css" type="text/css" />
  <!-- endbuild -->
  <link rel="stylesheet" href="assets/styles/font.css" type="text/css" />
  <script src="libs/jquerymin/jquery.min.js"></script>
  <!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>-->

</head>
<body>
  <div class="app" id="app">
    <!-- ############ LAYOUT START-->
    <!-- BARRA IZQUIERDA -->
    <!-- aside -->
    <!-- / -->

    <!-- content -->
    <div id="content" class="app-content box-shadow-z0" role="main">
      <div class="app-header white box-shadow">
        <div class="navbar navbar-toggleable-sm flex-row align-items-center">
          <!-- Open side - Naviation on mobile -->
          <a data-toggle="modal" data-target="#aside" class="hidden-lg-up mr-3">
            <i class="material-icons">&#xe5d2;</i>
          </a>
          <!-- / -->
          <div class="">
            <b id="display_new_access">  </b>
          </div>
          <!-- Page title - Bind to $state's title -->
          <div class="mb-0 h5 no-wrap" ng-bind="$state.current.data.title" id="pageTitle"></div>

          <!-- navbar collapse -->
          <div class="collapse navbar-collapse" id="collapse">
            <!-- link and dropdown -->
            <ul class="nav navbar-nav mr-auto">
              <li class="nav-item dropdown">
                <a  class="nav-link" href data-toggle="dropdown">

                </a>
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
              <a class="nav-link mr-2" href data-toggle="dropdown">

              </a>
              <div ui-include="'views/blocks/dropdown.notification.html'"></div>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link p-0 clear" href="#" data-toggle="dropdown">

              </a>
              <div ui-include="'views/blocks/dropdown.user.html'"></div>
            </li>
            <li class="nav-item hidden-md-up">
              <a class="nav-link pl-2" data-toggle="collapse" data-target="#collapse">
              </a>
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
          <div class="nav">
            </div>
          </div>
        </div>
        
        <div ui-view class="app-body" id="view">
          
          
        <!-- SECCION CENTRAL -->
        <div class="padding">
          <div class="row">
            <div class="col-sm-12">
              <div class="box">
                <div class="box-header">
                  <h2>USUARIO PRESTAMOS</h2>
                </div>
                <div class="md-form-group" style="margin-left:25px">
                  <input name="registration" id="registration" type="text"  placeholder="Ej: A01736666" class="md-input" value="" required>
                  <label>RFID USER: </label>
                </div>
                <br>
              </div> 
              
      <div id="resultado_"></div>
      
    </div>
    
    <!-- / -->
    

<!-- ############ LAYOUT END-->

</div>
<!-- build:js scripts/app.html.js -->
<!-- jQuery -->
<script src="libs/jquery/jquery/dist/jquery.js"></script>
<!-- Bootstrap -->
<script src="libs/jquery/tether/dist/js/tether.min.js"></script>
<script src="libs/jquery/bootstrap/dist/js/bootstrap.js"></script>
<!-- core -->
<script src="libs/jquery/underscore/underscore-min.js"></script>
<script src="libs/jquery/jQuery-Storage-API/jquery.storageapi.min.js"></script>
<script src="libs/jquery/PACE/pace.min.js"></script>

<script src="html/scripts/config.lazyload.js"></script>

<script src="html/scripts/palette.js"></script>
<script src="html/scripts/ui-load.js"></script>
<script src="html/scripts/ui-jp.js"></script>
<script src="html/scripts/ui-include.js"></script>
<script src="html/scripts/ui-device.js"></script>
<script src="html/scripts/ui-form.js"></script>
<script src="html/scripts/ui-nav.js"></script>
<script src="html/scripts/ui-screenfull.js"></script>
<script src="html/scripts/ui-scroll-to.js"></script>
<script src="html/scripts/ui-toggle-class.js"></script>

<script src="html/scripts/app.js"></script>

<!-- ajax -->
<script src="libs/jquery/jquery-pjax/jquery.pjax.js"></script>
<script src="html/scripts/ajax.js"></script>

<script src="libs/mqtt/dist/mqtt.min.js"></script>
<script type="text/javascript">

/*
******************************
****** PROCESOS  *************
******************************
*/

function dashboardLab(){
  window.location.href = "dashboard.php";
}

function devicesLab(){
  window.location.href = "devices.php";
}

function registerUserLab(){
  window.location.href = "register_lab.php";
}

function eliminarUsuario() {
  // Realizar operaciones necesarias, como una solicitud AJAX
  // Redirigir a la página deseada
  window.location.href = "delete_user.php";
}
       
function horasUso() {
  // Realizar operaciones necesarias, como una solicitud AJAX
  // Redirigir a la página deseada
  window.location.href = "horas_uso.php";
}

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

var audio = new Audio('audio.mp3');
// Add this variable to store the current RFID value
var currentRfid = '';

function process_msg(topic, message){
  var msg = message.toString();
  var splitted_topic = topic.split("/");
  var serial_number = splitted_topic[0];
  var query = splitted_topic[1];
  var device_serie = $( "#device_id" ).val();

  if (query == "loan_queryu") {
    // Store the RFID value for later use
    currentRfid = msg;
    
    // Establecer el valor en el campo de entrada
    document.getElementById('registration').value = msg;
    
    // Realizar la consulta automáticamente
    $.ajax({
      url: 'dash_loan.php',
      method: 'POST',
      data: { consult_loan: msg },
      success: function(data) {
        $('#resultado_').html(""); 
        var data_ = cortarDespuesDeDoctype(data);
        $('#resultado_').html(data_);
        console.log(data);
      }
    });
  } 
  else if (query == "loan_querye") {
    // Use the stored RFID value to refresh the loan table
    if(currentRfid) {
      // Play audio notification
      audio.play();
      
      // Refresh the loan data using the stored RFID
      $.ajax({
        url: 'dash_loan.php',
        method: 'POST',
        data: { consult_loan: currentRfid },
        success: function(data) {
          $('#resultado_').html(""); 
          var data_ = cortarDespuesDeDoctype(data);
          $('#resultado_').html(data_);
          console.log("Refreshed loan data");
        }
      });
    } else {
      console.log("No RFID available to refresh loan data");
    }
  }
}

/*
******************************
****** CONEXION  *************
******************************
*/
// connect options
const options = {
      // Authentication
      clientId: 'iotmc'+generarCadenaAleatoria(6),
      username: 'jose',
      password: 'public',
      keepalive: 60,
      clean: true,
      connectTimeout: 4000,
}

var connected = false;

// WebSocket connect url
const WebSocket_URL = 'wss://192.168.0.100:8074/mqtt'
const client = mqtt.connect(WebSocket_URL, options);

client.on('connect', () => {
    console.log('Mqtt conectado por WS! Exito!')

    client.subscribe('+/loan_queryu', { qos: 0 }, (error) => {});
    client.subscribe('+/loan_querye', { qos: 0 }, (error) => {}); // Add this line

    // publish(topic, payload, options/callback)
    client.publish('fabrica', 'esto es un verdadero éxito', (error) => {
      console.log(error || 'Mensaje enviado!!!');
    })
})

client.on('message', (topic, message) => {
  console.log('Mensaje recibido bajo tópico: ', topic, ' -> ', message.toString());
  process_msg(topic, message);
})

client.on('reconnect', (error) => {
    console.log('Error al reconectar', error)
})

client.on('error', (error) => {
    console.log('Error de conexión:', error)
})

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

$(document).ready(function() {
    $('#registration').on('input', function() {
        var valorInput = $(this).val();
        // Store the current input value as the RFID
        currentRfid = valorInput;
        // Enviar el valor al archivo PHP mediante AJAX
        $.ajax({
            url: 'dash_loan.php', // Archivo PHP que realizará la consulta
            method: 'POST',
            data: { consult_loan: valorInput },
            success: function(data) {
              $('#resultado_').html(""); 
              var data_ = cortarDespuesDeDoctype(data);
              $('#resultado_').html(data_); // Mostrar el resultado en el div resultado
              console.log(data);
            }
        });
    });
});
</script>
<!-- endbuild -->
</body>
</html>