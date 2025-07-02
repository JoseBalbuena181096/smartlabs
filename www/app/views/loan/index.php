<?php 
$title = "Sistema de Autopréstamo - SMARTLABS";
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
      
      <div class="">
        <b id="display_new_access"></b>
      </div>
      
      <div class="mb-0 h5 no-wrap" id="pageTitle">Sistema de Autopréstamo de Equipos</div>
      
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
              <h2><i class="fa fa-cart-arrow-down"></i> SISTEMA DE AUTOPRÉSTAMO SMARTLABS</h2>
              <small>Consulta de préstamos activos mediante tarjeta RFID</small>
            </div>
            <div class="box-body">
              
              <!-- Formulario de consulta por RFID -->
              <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                  <h4 class="mb-0"><i class="fa fa-search"></i> Consultar Préstamos por RFID</h4>
                </div>
                <div class="card-body">
                  <form id="consultForm" class="row g-3">
                    <div class="col-md-8">
                      <label for="consult_loan" class="form-label"><strong><i class="fa fa-credit-card"></i> RFID de la Tarjeta:</strong></label>
                      <input type="text" 
                             name="consult_loan" 
                             id="consult_loan"
                             class="form-control form-control-lg" 
                             placeholder="Ej: 5242243191 - Acerca tu tarjeta al lector o ingresa el número RFID" 
                             autocomplete="off"
                             autofocus>
                      <small class="text-muted">El número RFID de tu tarjeta de identificación</small>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                      <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fa fa-search"></i> Consultar Préstamos
                      </button>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Área de resultados -->
              <div id="results" class="mt-4"></div>
              
              <!-- Instrucciones de uso -->
              <div class="card">
                <div class="card-header">
                  <h4 class="mb-0"><i class="fa fa-info-circle"></i> ¿Cómo funciona el sistema?</h4>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <h5><i class="fa fa-list-ol"></i> Pasos para consultar:</h5>
                      <ol>
                        <li><strong>Acerca tu tarjeta:</strong> Coloca tu tarjeta RFID cerca del lector</li>
                        <li><strong>O ingresa manualmente:</strong> Escribe el número RFID en el campo</li>
                        <li><strong>Consulta:</strong> Presiona el botón "Consultar Préstamos"</li>
                        <li><strong>Revisa:</strong> Ve la lista de equipos que tienes prestados</li>
                      </ol>
                    </div>
                    <div class="col-md-6">
                      <h5><i class="fa fa-lightbulb-o"></i> Información importante:</h5>
                      <ul>
                        <li>Solo se muestran los equipos <strong>actualmente prestados</strong></li>
                        <li>Los equipos devueltos no aparecen en la lista</li>
                        <li>Cada usuario puede tener múltiples equipos prestados</li>
                        <li>El sistema verifica automáticamente el estado de devolución</li>
                      </ul>
                    </div>
                  </div>
                  
                  <div class="alert alert-info mt-3">
                    <i class="fa fa-info-circle"></i>
                    <strong>Sistema MQTT Conectado:</strong> 
                    El sistema puede recibir automáticamente la información de las tarjetas RFID a través del broker MQTT.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Estadísticas del sistema -->
      <div class="row">
        <div class="col-md-3">
          <div class="box bg-primary text-white">
            <div class="box-body text-center">
              <i class="fa fa-users fa-2x"></i>
              <h3 id="total-users">-</h3>
              <p>Usuarios Activos</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="box bg-success text-white">
            <div class="box-body text-center">
              <i class="fa fa-cube fa-2x"></i>
              <h3 id="total-equipment">-</h3>
              <p>Equipos Disponibles</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="box bg-warning text-white">
            <div class="box-body text-center">
              <i class="fa fa-shopping-cart fa-2x"></i>
              <h3 id="active-loans">-</h3>
              <p>Préstamos Activos</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="box bg-info text-white">
            <div class="box-body text-center">
              <i class="fa fa-clock-o fa-2x"></i>
              <h3 id="last-update"><?php echo date('H:i:s'); ?></h3>
              <p>Última Consulta</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="libs/jquerymin/jquery.min.js"></script>
<script>
$(document).ready(function() {
    
    // Enfocar automáticamente el campo RFID
    $('#consult_loan').focus();
    
    // Consulta AJAX
    $('#consultForm').submit(function(e) {
        e.preventDefault();
        
        var consult_loan = $('#consult_loan').val().trim();
        
        if (consult_loan === '') {
            alert('Por favor ingresa el número RFID de la tarjeta');
            $('#consult_loan').focus();
            return false;
        }
        
        // Mostrar indicador de carga
        $('#results').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
                <p class="mt-2">Consultando préstamos para RFID: <strong>${consult_loan}</strong></p>
            </div>
        `);
        
        // Realizar consulta AJAX
        $.ajax({
            url: '/Loan/index', // El mismo controlador
            type: 'POST',
            data: {
                consult_loan: consult_loan
            },
            success: function(response) {
                $('#results').html(response);
                
                // Actualizar estadísticas
                updateStats();
                
                // Actualizar tiempo
                $('#last-update').text(new Date().toLocaleTimeString());
                
                // Limpiar campo después de consulta exitosa
                $('#consult_loan').val('').focus();
                
                // Animar los resultados
                $('#results .table tr').each(function(index) {
                    $(this).delay(index * 100).fadeIn(500);
                });
            },
            error: function(xhr, status, error) {
                $('#results').html(`
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Error:</strong> No se pudo consultar los préstamos. 
                        Inténtalo nuevamente.
                    </div>
                `);
                console.error('Error:', error);
            }
        });
    });
    
    // Simular llegada de datos MQTT (para demo)
    function simulateMQTT() {
        // Aquí se podría integrar con el broker MQTT real
        // Por ahora solo es una simulación
        console.log('Sistema MQTT en escucha...');
    }
    
    // Actualizar estadísticas
    function updateStats() {
        // Contar elementos en la tabla de resultados
        var tableRows = $('#results table tbody tr').length;
        if (tableRows > 0) {
            $('#active-loans').text(tableRows);
        }
    }
    
    // Actualizar hora cada segundo
    setInterval(function() {
        $('#last-update').text(new Date().toLocaleTimeString());
    }, 1000);
    
    // Permitir input automático desde lector RFID
    $('#consult_loan').on('input', function() {
        var value = $(this).val();
        
        // Si el valor tiene cierta longitud, auto-enviar (típico de lectores RFID)
        if (value.length >= 8 && value.length <= 15) {
            // Pequeño delay para permitir que el lector termine
            setTimeout(function() {
                $('#consultForm').submit();
            }, 100);
        }
    });
    
    // Tecla Enter también envía el formulario
    $('#consult_loan').keypress(function(e) {
        if (e.which === 13) { // Enter key
            $('#consultForm').submit();
        }
    });
    
    // Inicializar simulación MQTT
    simulateMQTT();
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?> 