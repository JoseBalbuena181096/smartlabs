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

// Función para consultar los préstamos de todos los usuarios y generar la tabla HTML
function consultarPrestamos($conn, $consult_loan = null) {
  $output = '';
  $export_data = []; // Array to store data for CSV export
  
  // Si se proporciona un RFID específico, consultar solo ese usuario
  if($consult_loan) {
    $query = "SELECT * FROM `cards_habs` WHERE `cards_number` = '$consult_loan'";
  } else {
    // Si no se proporciona RFID, consultar todos los usuarios
    $query = "SELECT * FROM `cards_habs`";
  }
  
  $result_users = $conn->query($query);
  
  if (!$result_users) {
    return "Error en la consulta de usuarios: " . $conn->error;
  }
  
  // Add export button at the top
  $output .= '<div style="margin-bottom: 20px;"><button id="export-csv" class="btn btn-success">Exportar a CSV</button></div>';
  
  // Iterar a través de todos los usuarios
  while ($user = $result_users->fetch_assoc()) {
    $cards_number = $user['cards_number'];
    
    // Consultar préstamos para este usuario
    $result_loans = $conn->query("SELECT * FROM `habslab` WHERE `loans_hab_rfid` = '$cards_number' ORDER BY `loans_date` DESC");
    
    if (!$result_loans) {
      $output .= "Error en la consulta de préstamos: " . $conn->error;
      continue;
    }
    
    $filtered_loans = [];
    $seen_equipments = [];
    
    while ($row = $result_loans->fetch_assoc()) {
      $equipment_rfid = $row['equipments_rfid'];
      
      if (!isset($seen_equipments[$equipment_rfid])) {
        $seen_equipments[$equipment_rfid] = true;
        $filtered_loans[] = $row;
      }
    }
    
    // Verificar préstamos activos para este usuario
    $active_loans = 0;
    $active_loans_data = [];
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
        
        // Si no hay devolución más reciente, contar como préstamo activo
        if ($check_result->num_rows == 0) {
          $active_loans++;
          $active_loans_data[] = $loan;
        }
      }
    }
    
    // Solo mostrar usuarios con préstamos activos
    if ($active_loans > 0) {
      // Consultar el email del usuario en la tabla habintants
      $user_card_number = $user['cards_number'];
      $email_query = "SELECT hab_email FROM habintants 
                     JOIN cards ON habintants.hab_card_id = cards.cards_id 
                     WHERE cards.cards_number = '$user_card_number'";
      $email_result = $conn->query($email_query);
      $user_email = "";
      if ($email_result && $email_result->num_rows > 0) {
        $email_row = $email_result->fetch_assoc();
        $user_email = $email_row['hab_email'];
      }
    
      $output .= "<br>";
      $output .= '<div class="user-header" style="display: flex; justify-content: space-between; align-items: center;">';
      $output .= '<div>';
      $output .= '<h4 class="h4">Prestamos del usuario: </h4>';
      // Mostrar nombre Y CORREO del usuario
      $output .= "<h3 class=\"h3\">" . $user["hab_name"] . " <small>(" . $user_email . ")</small></h3>";
      $output .= '</div>';
      $output .= '<div>';
      $output .= '<button class="btn btn-primary request-return" data-user="' . $user["hab_name"] . '" data-email="' . $user_email . '" data-rfid="' . $user_card_number . '">Solicitar devolución</button>';
      $output .= '</div>';
      $output .= '</div>';
      $output .= "<br>";
      
      // Generar tabla de préstamos para este usuario
      $output .= '<table class="table table-striped b-t" id="loans-table-' . $user_card_number . '">';
      $output .= '<thead>';
      $output .= '<tr>';
      $output .= '<th>DATE</th>';
      $output .= '<th>EQUIPMENT</th>';
      $output .= '<th>BRANCH</th>';
      $output .= '<th>STATE</th>';
      $output .= '</tr>';
      $output .= '</thead>';
      $output .= '<tbody>';
      
      // Prepare data for CSV export
      $user_data = [
        'user_name' => $user["hab_name"],
        'user_email' => $user_email,
        'equipments' => []
      ];
      
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
            $output .= '<tr data-date="' . $loan["loans_date"] . '" data-equipment="' . $loan["equipments_name"] . '" data-brand="' . $loan["equipments_brand"] . '">';
            $output .= "<td>" . $loan["loans_date"] . "</td>";
            $output .= "<td>" . $loan["equipments_name"] . "</td>";
            $output .= "<td>" . $loan["equipments_brand"] . "</td>";
            $output .= "<td>Prestado</td>";
            $output .= '</tr>';
            
            // Add to CSV data
            $user_data['equipments'][] = [
              'date' => $loan["loans_date"],
              'name' => $loan["equipments_name"],
              'brand' => $loan["equipments_brand"]
            ];
          }
          // Si hay devolución más reciente, no mostrar este equipo
        }
      }
      
      $output .= '</tbody>';
      $output .= '</table>';
      
      // Add separator between users
      $output .= '<hr>';
      
      // Add user data to export array if they have active loans
      if (!empty($user_data['equipments'])) {
        $export_data[] = $user_data;
      }
    }
  }
  
  // Add hidden data for CSV export
  $output .= '<div id="csv-export-data" style="display:none;">' . json_encode($export_data) . '</div>';
  
  return $output;
}

// Procesar mediante AJAX cuando se recibe un valor del input
if (isset($_POST['consult_loan'])) {
  $consult_loan = strip_tags($_POST['consult_loan']);
  if (!empty($consult_loan)) {
    echo consultarPrestamos($conn, $consult_loan);
  } else {
    // Si el input está vacío, mostrar todos los usuarios
    echo consultarPrestamos($conn, null);
  }
  // Terminar la ejecución para evitar que se muestre el resto de la página
  exit();
}

// Función para enviar correo de solicitud de devolución
if (isset($_POST['action']) && $_POST['action'] == 'send_return_request') {
  $userEmail = $_POST['email'];
  $userName = $_POST['user'];
  $equipment = $_POST['equipment'];
  
  $to = $userEmail;
  $subject = "Solicitud de devolución de equipos - SMARTLABS";
  
  // Verificar si es un correo del dominio tec.mx
  $is_tec_email = (strpos($to, '@tec.mx') !== false);
  
  // Crear directorio para correos si no existe
  $correos_dir = 'correos/';
  if (!file_exists($correos_dir)) {
    mkdir($correos_dir, 0755, true);
  }
  
  $message = "
  <html>
  <head>
  <title>Solicitud de devolución</title>
  <style>
    body { font-family: Arial, sans-serif; }
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .important { color: red; font-weight: bold; }
  </style>
  </head>
  <body>
  <p>Estimado/a $userName,</p>
  <p>Le recordamos que necesita devolver los siguientes equipos antes de las 5:45 PM al Laboratorio Mecatrónica aun constado de Bliblioteca Sur:</p>
  <table border='1' cellpadding='5' cellspacing='0'>
  <tr>
    <th>Fecha de préstamo</th>
    <th>Equipo</th>
    <th>Marca</th>
  </tr>";
  
  foreach($equipment as $item) {
    $message .= "<tr>
      <td>{$item['date']}</td>
      <td>{$item['name']}</td>
      <td>{$item['brand']}</td>
    </tr>";
  }
  
  $message .= "</table>
  <p class='important'><strong>IMPORTANTE: Los equipos no deben salir del laboratorio.</strong></p>
  <p>Agradecemos su cooperación.</p>
  <p>Saludos cordiales,<br>
  Equipo SMARTLABS</p>
  </body>
  </html>";
  
  // Guardar una copia del correo
  $email_file = $correos_dir . 'email_' . time() . '_' . rand(1000, 9999) . '.html';
  file_put_contents($email_file, $message);
  
  // Datos de configuración del servidor SMTP
  $smtp_servidor = "smtp.gmail.com";
  $smtp_puerto = 587;
  $smtp_usuario = "josebalbuena181096@gmail.com";
  $smtp_password = "iwvr xpqo vzyx ofln";
  $smtp_remitente = "Laboratorio Mecatrónica <josebalbuena181096@gmail.com>";
  
  $success = false;
  $error_message = "";
  $method_used = "";
  
  // Intentar enviar usando SMTP nativo de PHP
  try {
    // Crear la conexión al servidor SMTP
    $socket = fsockopen($smtp_servidor, $smtp_puerto, $errno, $errstr, 30);
    
    if (!$socket) {
      throw new Exception("Error al conectar: $errstr ($errno)");
    }
    
    // Leer la respuesta del servidor
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '220') {
      throw new Exception("No se recibió saludo del servidor: $response");
    }
    
    // Enviar comando EHLO
    fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
      throw new Exception("Error en EHLO: $response");
    }
    
    // Limpiar buffer (múltiples líneas de respuesta EHLO)
    while(substr($response, 3, 1) == '-') {
      $response = fgets($socket, 515);
    }
    
    // Iniciar TLS
    fputs($socket, "STARTTLS\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '220') {
      throw new Exception("Error en STARTTLS: $response");
    }
    
    // Actualizar la conexión a TLS
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    // Reenviar EHLO después de TLS
    fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
      throw new Exception("Error en segundo EHLO: $response");
    }
    
    // Limpiar buffer otra vez
    while(substr($response, 3, 1) == '-') {
      $response = fgets($socket, 515);
    }
    
    // Autenticación
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
      throw new Exception("Error en AUTH LOGIN: $response");
    }
    
    // Enviar nombre de usuario en base64
    fputs($socket, base64_encode($smtp_usuario) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
      throw new Exception("Error en usuario: $response");
    }
    
    // Enviar contraseña en base64
    fputs($socket, base64_encode($smtp_password) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '235') {
      throw new Exception("Error en autenticación: $response");
    }
    
    // Establecer remitente
    fputs($socket, "MAIL FROM:<$smtp_usuario>\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
      throw new Exception("Error en MAIL FROM: $response");
    }
    
    // Establecer destinatario
    fputs($socket, "RCPT TO:<$to>\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250' && substr($response, 0, 3) != '251') {
      throw new Exception("Error en RCPT TO: $response");
    }
    
    // Iniciar envío de datos
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '354') {
      throw new Exception("Error en DATA: $response");
    }
    
    // Preparar cabeceras y cuerpo del correo
    $headers = "From: $smtp_remitente\r\n";
    $headers .= "Reply-To: $smtp_usuario\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    
    // Enviar correo (cabeceras + línea en blanco + cuerpo + punto)
    fputs($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
      throw new Exception("Error en envío de mensaje: $response");
    }
    
    // Cerrar conexión
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    $success = true;
    $method_used = "php_socket_smtp";
    
  } catch (Exception $e) {
    $error_message = $e->getMessage();
    
    // Registrar el error
    file_put_contents($correos_dir . 'smtp_error_log.txt', 
                     date('Y-m-d H:i:s') . " - Error en conexión SMTP: " . $error_message . "\n", 
                     FILE_APPEND);
  }
  
  // Si el método SMTP directo falló, intentar con mail() de PHP
  if (!$success) {
    // Intentar con mail()
    $mail_headers = "MIME-Version: 1.0" . "\r\n";
    $mail_headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $mail_headers .= "From: $smtp_remitente" . "\r\n";
    
    $mail_sent = mail($to, $subject, $message, $mail_headers);
    
    if ($mail_sent) {
      $success = true;
      $method_used = "php_mail";
      
      file_put_contents($correos_dir . 'mail_log.txt', 
                       date('Y-m-d H:i:s') . " - Correo enviado con mail() a: $to\n", 
                       FILE_APPEND);
    } else {
      file_put_contents($correos_dir . 'mail_error_log.txt', 
                       date('Y-m-d H:i:s') . " - Error al enviar con mail() a: $to\n", 
                       FILE_APPEND);
    }
  }
  
  // Generar un botón para acceder al correo guardado
  $email_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath($email_file));
  $email_url = str_replace('\\', '/', $email_url); // Para compatibilidad con Windows
  
  // Generar URL de mailto como último recurso
  $mailto_link = "mailto:$to?subject=" . urlencode($subject) . "&body=" . urlencode(strip_tags($message));
  
  // Devolver respuesta
  header('Content-Type: application/json');
  echo json_encode([
    'success' => $success,
    'method' => $method_used,
    'error' => $error_message,
    'email_file' => $email_file,
    'email_url' => $email_url,
    'email_content' => $message,
    'mailto_link' => $mailto_link,
    'is_tec_email' => $is_tec_email
  ]);
  exit();
}

// Add a new function to handle CSV export
if (isset($_POST['action']) && $_POST['action'] == 'export_csv') {
  // Set headers for CSV download
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="deudores_' . date('Y-m-d') . '.csv"');
  header('Pragma: no-cache');
  header('Expires: 0');
  
  // Create a file handle for output
  $output = fopen('php://output', 'w');
  
  // Add UTF-8 BOM for proper encoding in Excel
  fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
  
  // Write header row
  fputcsv($output, ['Nombre', 'Correo', 'Fecha de préstamo', 'Equipo', 'Marca']);
  
  // Get data from POST
  $data = json_decode($_POST['data'], true);
  
  // Check if decoding was successful
  if (json_last_error() !== JSON_ERROR_NONE) {
      error_log("CSV Export Error: Failed to decode JSON data. Error: " . json_last_error_msg());
      // Output an error message if not downloading directly
      // echo "Error processing data for CSV export."; 
      exit();
  }

  // Check if $data is an array
  if (!is_array($data)) {
      error_log("CSV Export Error: Decoded data is not an array.");
      // echo "Error: Invalid data format for CSV export.";
      exit();
  }

  // Write data rows
  foreach ($data as $user) {
    // Add checks to ensure $user is an array and has expected keys
    if (!is_array($user) || !isset($user['user_name']) || !isset($user['user_email']) || !isset($user['equipments']) || !is_array($user['equipments'])) {
        error_log("CSV Export Error: Invalid user data structure: " . print_r($user, true));
        continue; // Skip this invalid user entry
    }
    foreach ($user['equipments'] as $equipment) {
      // Add checks for equipment structure
      if (!is_array($equipment) || !isset($equipment['date']) || !isset($equipment['name']) || !isset($equipment['brand'])) {
          error_log("CSV Export Error: Invalid equipment data structure: " . print_r($equipment, true));
          continue; // Skip this invalid equipment entry
      }
      fputcsv($output, [
        $user['user_name'],
        $user['user_email'],
        $equipment['date'],
        $equipment['name'],
        $equipment['brand']
      ]);
    }
  }
  
  fclose($output);
  exit();
}

// Al cargar la página inicialmente, mostrar todos los préstamos
$all_loans_html = consultarPrestamos($conn, null);

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

  if (query == "loan_queryu") {
    // Store the RFID value for later use
    currentRfid = msg;
    
    // Realizar la consulta automáticamente
    $.ajax({
      url: 'email.php',
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
        url: 'email.php',
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
    // Cargar todos los préstamos al iniciar la página
    $.ajax({
        url: 'email.php',
        method: 'POST',
        data: { consult_loan: '' }, // Valor vacío para obtener todos los usuarios
        success: function(data) {
            $('#resultado_').html("");
            var data_ = cortarDespuesDeDoctype(data);
            $('#resultado_').html(data_);
            console.log('Loaded all loans');
        }
    });
    
    // Evento para el botón de solicitar devolución
    $(document).on('click', '.request-return', function() {
        var userName = $(this).data('user');
        var userEmail = $(this).data('email');
        var userRfid = $(this).data('rfid');
        var equipmentList = [];
        var isTecEmail = userEmail.indexOf('@tec.mx') !== -1;
        
        // Recolectar información de equipos prestados de este usuario
        $('#loans-table-' + userRfid + ' tbody tr').each(function() {
            equipmentList.push({
                date: $(this).data('date'),
                name: $(this).data('equipment'),
                brand: $(this).data('brand')
            });
        });
        
        // Mostrar mensaje de procesamiento
        if (isTecEmail) {
            alert('Procesando solicitud de devolución para ' + userName + ' (correo TEC.MX detectado)...');
        } else {
            alert('Procesando solicitud de devolución para ' + userName + '...');
        }
        
        // Enviar solicitud de devolución por email
        $.ajax({
            url: 'email.php',
            method: 'POST',
            data: {
                action: 'send_return_request',
                user: userName,
                email: userEmail,
                equipment: equipmentList
            },
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                
                if (response.success) {
                    // Correo enviado exitosamente
                    alert('Correo enviado exitosamente a ' + userEmail + ' usando método: ' + response.method);
                } else {
                    // Mostrar opciones en caso de error
                    var mensaje = 'No se pudo enviar el correo automáticamente a ' + userEmail + '.\n\n';
                    
                    if (response.error) {
                        mensaje += 'Error: ' + response.error + '\n\n';
                    }
                    
                    mensaje += 'Por favor, elija una de las siguientes opciones para notificar al usuario:';
                    
                    // Mostrar opciones de recuperación
                    var opciones = [
                        'Abrir el correo en una nueva ventana para copiarlo',
                        'Usar mi cliente de correo predeterminado',
                        'Cancelar'
                    ];
                    
                    // Crear diálogo personalizado con botones
                    var dialogoHTML = '<div id="opciones-correo" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:9999;">' +
                        '<div style="width:500px;max-width:90%;background:white;border-radius:8px;padding:20px;box-shadow:0 4px 10px rgba(0,0,0,0.3);">' +
                        '<h3 style="margin-top:0;color:#d9534f;">Error al enviar correo</h3>' +
                        '<p>' + mensaje.replace(/\n/g, '<br>') + '</p>' +
                        '<div style="display:flex;flex-direction:column;gap:10px;margin-top:20px;">' +
                        '<button id="opcion-1" style="padding:10px;border:none;background:#5bc0de;color:white;border-radius:4px;cursor:pointer;">Abrir correo en nueva ventana</button>' +
                        '<button id="opcion-2" style="padding:10px;border:none;background:#5cb85c;color:white;border-radius:4px;cursor:pointer;">Usar mi cliente de correo</button>' +
                        '<button id="opcion-3" style="padding:10px;border:none;background:#f0ad4e;color:white;border-radius:4px;cursor:pointer;">Cancelar</button>' +
                        '</div></div></div>';
                    
                    // Añadir diálogo al DOM
                    $('body').append(dialogoHTML);
                    
                    // Manejar eventos de botones
                    $('#opcion-1').click(function() {
                        // Abrir correo en nueva ventana
                        var newWindow = window.open('', '_blank');
                        if (newWindow) {
                            newWindow.document.write(response.email_content);
                            newWindow.document.title = 'Solicitud de devolución - ' + userName;
                            $('#opciones-correo').remove();
                        } else {
                            alert('No se pudo abrir una nueva ventana. Verifique que no tenga bloqueado los popups.');
                        }
                    });
                    
                    $('#opcion-2').click(function() {
                        // Usar cliente de correo
                        window.location.href = response.mailto_link;
                        $('#opciones-correo').remove();
                    });
                    
                    $('#opcion-3').click(function() {
                        // Cancelar
                        $('#opciones-correo').remove();
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la solicitud:', status, error);
                
                var errorMessage = 'Error en la comunicación con el servidor';
                
                try {
                    var responseJson = JSON.parse(xhr.responseText);
                    if (responseJson && responseJson.error) {
                        errorMessage += ': ' + responseJson.error;
                    }
                } catch (e) {
                    errorMessage += ': ' + error;
                }
                
                alert(errorMessage + '\n\nEl correo se ha guardado como archivo HTML en el servidor.');
            }
        });
    });
    
    // Evento para exportar a CSV
    $(document).on('click', '#export-csv', function() {
        // Obtener datos del elemento oculto
        var exportData = $('#csv-export-data').text();
        
        // Método 1: Enviar solicitud con formulario
        var form = $('<form action="email.php" method="post" target="_blank"></form>');
        form.append('<input type="hidden" name="action" value="export_csv">');
        form.append('<input type="hidden" name="data" value="' + exportData + '">');
        $('body').append(form);
        form.submit();
        form.remove();
    });
});
</script>
<!-- endbuild -->
</body>
</html>