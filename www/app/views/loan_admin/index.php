<?php 
$title = "ADMINISTRADOR DE PRÉSTAMOS - SMARTLABS";
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
        <b id="display_new_access">Panel Administrativo de Préstamos</b>
      </div>
      
      <!-- Page title -->
      <div class="mb-0 h5 no-wrap" id="pageTitle">Sistema Administrativo SMARTLABS</div>

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
  
  <div ui-view class="app-body" id="view">
    <!-- SECCION CENTRAL -->
    <div class="padding">
      <div class="row">
        <div class="col-sm-12">
          <div class="box">
            <div class="box-header">
              <h2><i class="fa fa-user-cog"></i> ADMINISTRADOR DE PRÉSTAMOS</h2>
              <small>Panel administrativo para gestionar devoluciones de equipos prestados</small>
            </div>
            <div class="box-body">
              <!-- Buscador Administrativo -->
              <div class="row justify-content-center">
                <div class="col-md-8">
                  <div class="card mb-3 search-card-admin">
                    <div class="card-header bg-danger text-white">
                      <h5 class="mb-0"><i class="fa fa-search-plus search-type-icon"></i> Búsqueda Administrativa de Usuarios</h5>
                    </div>
                    <div class="card-body">
                      <div class="input-group">
                        <input type="text" id="userSearchAdmin" class="form-control form-control-lg" placeholder="Buscar usuario por matrícula, nombre o correo..." autocomplete="off">
                        <div class="input-group-append">
                          <button class="btn btn-danger btn-lg" type="button" id="searchBtnAdmin">
                            <i class="fa fa-search"></i> BUSCAR
                          </button>
                        </div>
                      </div>
                      <small class="text-muted">
                        <i class="fa fa-shield-alt"></i> <span id="searchPlaceholderAdmin">Busca automáticamente por matrícula, nombre y correo electrónico para administrar préstamos</span>
                      </small>
                      
                      <!-- Resultados de búsqueda administrativos -->
                      <div id="searchResultsAdmin" class="mt-3 search-results-enter" style="display: none;">
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
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
                            <tbody id="searchResultsBodyAdmin">
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Botones de Acciones Globales -->
              <div class="row justify-content-center mt-3">
                <div class="col-md-8">
                  <div class="d-flex justify-content-center">
                    <button class="btn btn-warning btn-lg mr-3" id="showAllLoansBtn">
                      <i class="fa fa-list-alt mr-2"></i>TODOS LOS PRÉSTAMOS
                    </button>
                    <button class="btn btn-success btn-lg" id="exportCSVBtn" style="display: none;">
                      <i class="fa fa-download mr-2"></i>EXPORTAR CSV
                    </button>
                  </div>
                </div>
              </div>
            </div> 
          </div>
          
          <div id="resultado_admin"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Confirmación de Devolución -->
<div class="modal fade" id="confirmReturnModal" tabindex="-1" role="dialog" aria-labelledby="confirmReturnModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="confirmReturnModalLabel">
          <i class="fa fa-exclamation-triangle"></i> Confirmar Devolución
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <div class="mb-3">
          <i class="fa fa-undo fa-3x text-danger"></i>
        </div>
        <h4>¿Confirmar devolución del equipo?</h4>
        <p class="text-muted mb-3">
          <strong id="equipmentNameModal"></strong><br>
          <small>Esta acción registrará la devolución en el sistema</small>
        </p>
        <div class="alert alert-warning">
          <i class="fa fa-info-circle"></i> 
          <strong>Importante:</strong> Una vez confirmada, la devolución quedará registrada permanentemente.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fa fa-times"></i> Cancelar
        </button>
        <button type="button" class="btn btn-danger" id="confirmReturnBtn">
          <i class="fa fa-check"></i> Confirmar Devolución
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Estilos para el administrador -->
<style>
  .search-card-admin {
    transition: all 0.3s ease;
    border: 2px solid #dc3545;
  }
  .search-card-admin:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(220, 53, 69, 0.2);
  }
  .search-results-enter {
    animation: slideDown 0.3s ease-out;
  }
  @keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .user-row:hover {
    background-color: #fff5f5 !important;
    transform: scale(1.02);
    transition: all 0.2s ease;
  }
  .user-row.table-success {
    background-color: #d4edda !important;
    border-color: #c3e6cb !important;
  }
  .badge-counter {
    animation: pulse 1s infinite;
  }
  .search-type-icon {
    font-size: 1.2em;
    margin-right: 5px;
  }
  .btn-return {
    transition: all 0.3s ease;
  }
  .btn-return:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  }
  .modal-content {
    border-radius: 10px;
    overflow: hidden;
  }
  .modal-header {
    border-bottom: none;
  }
  .modal-footer {
    border-top: none;
  }
  /* Estilos para las tablas de préstamos */
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .pulse-danger {
    animation: pulse-danger 2s infinite;
  }
  @keyframes pulse-danger {
    0% { opacity: 1; }
    50% { opacity: 0.7; background-color: #dc3545; }
    100% { opacity: 1; }
  }
  .table-hover tbody tr:hover {
    background-color: #fff3cd !important;
    transform: scale(1.01);
    transition: all 0.2s ease;
  }
  
  /* Estilos para campos deshabilitados durante exportación */
  .form-control:disabled {
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
    color: #6c757d !important;
    cursor: not-allowed;
  }
  
  .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  
  /* Animación de carga para el proceso de exportación */
  .exporting-state {
    position: relative;
    overflow: hidden;
  }
  
  .exporting-state::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    animation: loading-sweep 2s infinite;
  }
  
  @keyframes loading-sweep {
    0% { left: -100%; }
    100% { left: 100%; }
  }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>

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
var currentUserRfid = '';
var equipmentToReturn = '';
var userToReturn = '';

// Variables para el modal de confirmación
var modalEquipmentRfid = '';
var modalUserRfid = '';

/*
******************************
****** BUSCADOR ADMINISTRATIVO **
******************************
*/
$(document).ready(function() {
    var searchTimeoutAdmin;
    
    // Búsqueda en tiempo real con debounce
    $('#userSearchAdmin').on('input', function() {
        clearTimeout(searchTimeoutAdmin);
        var query = $(this).val().trim();
        
        if (query.length >= 2) {
            searchTimeoutAdmin = setTimeout(function() {
                buscarUsuariosAdmin(query);
            }, 500); // Debounce de 500ms
        } else {
            $('#searchResultsAdmin').hide();
            $('#searchPlaceholderAdmin').html('<i class="fa fa-shield-alt"></i> Busca automáticamente por matrícula, nombre y correo electrónico para administrar préstamos');
        }
    });
    
    // Botón de búsqueda
    $('#searchBtnAdmin').click(function() {
        var query = $('#userSearchAdmin').val().trim();
        
        if (query.length >= 1) {
            buscarUsuariosAdmin(query);
        } else {
            $('#searchPlaceholderAdmin').html('<i class="fa fa-exclamation-triangle text-warning"></i> Escribe al menos 1 carácter para buscar');
        }
    });
});

// Función para buscar usuarios administrativos
function buscarUsuariosAdmin(query) {
    $.ajax({
        url: '/LoanAdmin/index',
        method: 'POST',
        data: {
            search_user: query
        },
        beforeSend: function() {
            $('#searchResultsBodyAdmin').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Buscando usuarios...</td></tr>');
            $('#searchResultsAdmin').fadeIn(300);
        },
        success: function(response) {
            try {
                var users = JSON.parse(response);
                mostrarResultadosBusquedaAdmin(users);
                
                // Actualizar contador
                if (users.length > 0) {
                    $('#searchPlaceholderAdmin').html('<i class="fa fa-check-circle text-success"></i> ' + users.length + ' usuario' + (users.length > 1 ? 's' : '') + ' encontrado' + (users.length > 1 ? 's' : ''));
                } else {
                    $('#searchPlaceholderAdmin').html('<i class="fa fa-exclamation-triangle text-warning"></i> No se encontraron usuarios');
                }
            } catch (e) {
                $('#searchResultsBodyAdmin').html('<tr><td colspan="5" class="text-center text-danger"><i class="fa fa-exclamation-triangle"></i> Error al procesar resultados</td></tr>');
                $('#searchPlaceholderAdmin').html('<i class="fa fa-times-circle text-danger"></i> Error en la búsqueda');
            }
        },
        error: function() {
            $('#searchResultsBodyAdmin').html('<tr><td colspan="5" class="text-center text-danger"><i class="fa fa-times-circle"></i> Error en la búsqueda</td></tr>');
            $('#searchPlaceholderAdmin').html('<i class="fa fa-times-circle text-danger"></i> Error de conexión');
        }
    });
}

// Mostrar resultados de búsqueda administrativos
function mostrarResultadosBusquedaAdmin(users) {
    var html = '';
    
    if (users.length === 0) {
        html = '<tr><td colspan="5" class="text-center text-muted"><i class="fa fa-search"></i> No se encontraron usuarios</td></tr>';
    } else {
        users.forEach(function(user, index) {
            html += '<tr class="user-row" style="cursor: pointer;" data-rfid="' + user.cards_number + '">';
            html += '<td><input type="radio" name="selectedUserAdmin" value="' + user.cards_number + '"></td>';
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
    
    $('#searchResultsBodyAdmin').html(html);
    
    // Hacer las filas clickeables
    $('#searchResultsBodyAdmin tr[data-rfid]').click(function() {
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
            currentUserRfid = rfid;
            
            // Consultar préstamos automáticamente
            $.ajax({
                url: '/LoanAdmin/index',
                method: 'POST',
                data: { consult_loan_admin: rfid },
                success: function(data) {
                    $('#resultado_admin').html("");
                    $('#resultado_admin').html(data);
                    
                    // Ocultar botón de exportar ya que solo se ve un usuario
                    $('#exportCSVBtn').fadeOut(300);
                    
                    // Limpiar buscador con animación
                    $('#userSearchAdmin').val('');
                    $('#searchResultsAdmin').fadeOut(300);
                    $('#searchPlaceholderAdmin').html('<i class="fa fa-shield-alt"></i> Busca automáticamente por matrícula, nombre y correo electrónico para administrar préstamos');
                    
                    // Mostrar mensaje de éxito
                    $('#display_new_access').html('<span class="text-success"><i class="fa fa-check-circle"></i> Usuario seleccionado: ' + rfid + '</span>');
                    
                    console.log("Consulta administrativa:", data);
                },
                error: function() {
                    $('#resultado_admin').html('<div class="alert alert-danger">Error al consultar préstamos</div>');
                }
            });
        }, 1000);
    });
}

/*
******************************
****** GESTIÓN DE DEVOLUCIONES **
******************************
*/

// Función para confirmar devolución (llamada desde los botones DEVOLVER)
function confirmarDevolucion(equipmentRfid, userRfid, equipmentName) {
    modalEquipmentRfid = equipmentRfid;
    modalUserRfid = userRfid;
    
    // Actualizar el modal con la información del equipo
    $('#equipmentNameModal').text(equipmentName);
    
    // Mostrar el modal
    $('#confirmReturnModal').modal('show');
}

// Confirmar devolución cuando se presiona el botón del modal
$(document).ready(function() {
    $('#confirmReturnBtn').click(function() {
        procesarDevolucion(modalEquipmentRfid, modalUserRfid);
    });
});

// Procesar la devolución
function procesarDevolucion(equipmentRfid, userRfid) {
    $.ajax({
        url: '/LoanAdmin/index',
        method: 'POST',
        data: {
            return_loan: true,
            equipment_rfid: equipmentRfid,
            user_rfid: userRfid
        },
        beforeSend: function() {
            $('#confirmReturnBtn').html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
            $('#confirmReturnBtn').prop('disabled', true);
        },
        success: function(response) {
            try {
                var result = JSON.parse(response);
                
                if (result.success) {
                    // Cerrar modal
                    $('#confirmReturnModal').modal('hide');
                    
                    // Mostrar mensaje de éxito
                    $('#display_new_access').html('<span class="text-success"><i class="fa fa-check-circle"></i> Equipo devuelto exitosamente - ' + result.datetime + '</span>');
                    
                    // Refrescar la lista de préstamos
                    setTimeout(function() {
                        $.ajax({
                            url: '/LoanAdmin/index',
                            method: 'POST',
                            data: { consult_loan_admin: currentUserRfid },
                            success: function(data) {
                                $('#resultado_admin').html(data);
                                
                                // Ocultar botón de exportar ya que solo se ve un usuario
                                $('#exportCSVBtn').fadeOut(300);
                            }
                        });
                    }, 1500);
                    
                } else {
                    alert('Error: ' + result.message);
                }
                
            } catch (e) {
                alert('Error al procesar la respuesta');
            }
        },
        error: function() {
            alert('Error de conexión');
        },
        complete: function() {
            // Restaurar botón
            $('#confirmReturnBtn').html('<i class="fa fa-check"></i> Confirmar Devolución');
            $('#confirmReturnBtn').prop('disabled', false);
        }
    });
}

/*
******************************
****** FUNCIONES GLOBALES **
******************************
*/

// Mostrar TODOS los préstamos
function mostrarTodosLosPrestamos() {
    $.ajax({
        url: '/LoanAdmin/index',
        method: 'POST',
        data: { show_all_loans: true },
        beforeSend: function() {
            $('#resultado_admin').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x text-warning"></i><br><h4 class="mt-3">Cargando todos los préstamos...</h4></div>');
            $('#showAllLoansBtn').html('<i class="fa fa-spinner fa-spin mr-2"></i>CARGANDO...');
            $('#showAllLoansBtn').prop('disabled', true);
        },
        success: function(data) {
            $('#resultado_admin').html(data);
            $('#display_new_access').html('<span class="text-warning"><i class="fa fa-list-alt"></i> Vista de todos los préstamos activos del sistema</span>');
            
            // Mostrar botón de exportar solo cuando se ven todos los préstamos
            $('#exportCSVBtn').fadeIn(300);
            
            console.log("Todos los préstamos cargados");
        },
        error: function() {
            $('#resultado_admin').html('<div class="alert alert-danger text-center"><i class="fa fa-times-circle"></i> Error al cargar los préstamos</div>');
        },
        complete: function() {
            // Restaurar botón
            $('#showAllLoansBtn').html('<i class="fa fa-list-alt mr-2"></i>TODOS LOS PRÉSTAMOS');
            $('#showAllLoansBtn').prop('disabled', false);
        }
    });
}

// Exportar CSV - Descarga directa en la misma vista
function exportarCSV() {
    // Mostrar loading en el botón
    $('#exportCSVBtn').html('<i class="fa fa-spinner fa-spin mr-2"></i>GENERANDO CSV...');
    $('#exportCSVBtn').prop('disabled', true);
    
    // Deshabilitar búsquedas y otros botones durante la exportación
    $('#userSearchAdmin').prop('disabled', true).attr('placeholder', 'Exportando CSV... Espera por favor').addClass('exporting-state');
    $('#searchBtnAdmin').prop('disabled', true);
    $('#showAllLoansBtn').prop('disabled', true);
    
    // Agregar overlay visual a la sección de búsqueda
    $('.search-card-admin').addClass('exporting-state').css({
        'pointer-events': 'none',
        'opacity': '0.7'
    });
    
    // Mostrar mensaje de proceso con animación más visible
    $('#display_new_access').html('<span class="text-info"><i class="fa fa-cog fa-spin fa-2x"></i> <strong>Generando archivo CSV...</strong> La descarga iniciará automáticamente</span>');
    
    // Función para restaurar todo al estado normal
    function restaurarTodo() {
        $('#exportCSVBtn').html('<i class="fa fa-download mr-2"></i>EXPORTAR CSV');
        $('#exportCSVBtn').prop('disabled', false);
        
        // Rehabilitar búsquedas
        $('#userSearchAdmin').prop('disabled', false).attr('placeholder', 'Buscar usuario por matrícula, nombre o correo...').removeClass('exporting-state');
        $('#searchBtnAdmin').prop('disabled', false);
        $('#showAllLoansBtn').prop('disabled', false);
        
        // Remover overlay visual
        $('.search-card-admin').removeClass('exporting-state').css({
            'pointer-events': 'auto',
            'opacity': '1'
        });
        
        console.log("Exportación completada y controles restaurados");
    }
    
    try {
        // Crear iframe oculto para descarga sin abrir ventana
        var iframe = $('<iframe></iframe>');
        iframe.attr({
            id: 'csvDownloadFrame',
            style: 'display: none; width: 0; height: 0;',
            src: 'about:blank'
        });
        $('body').append(iframe);
        
        // Crear formulario que apunta al iframe oculto
        var form = $('<form></form>');
        form.attr({
            method: 'POST',
            action: '/LoanAdmin/index',
            target: 'csvDownloadFrame',
            style: 'display: none;'
        });
        
        // Agregar campo hidden
        form.append('<input type="hidden" name="export_csv" value="true">');
        
        // Agregar formulario al body
        $('body').append(form);
        
        // Enviar formulario para iniciar descarga
        setTimeout(function() {
            form.submit();
            
            // Actualizar mensaje en la misma vista
            $('#display_new_access').html('<span class="text-warning"><i class="fa fa-download fa-2x"></i> <strong>Descarga iniciada...</strong> Revisa tu carpeta de descargas</span>');
            
            // Restaurar controles después de 3 segundos
            setTimeout(function() {
                $('#display_new_access').html('<span class="text-success"><i class="fa fa-check-circle"></i> ¡Archivo CSV descargado exitosamente! Revisa tu carpeta de descargas - ' + new Date().toLocaleTimeString() + '</span>');
                
                // Restaurar todo
                restaurarTodo();
                
                // Limpiar elementos temporales
                $('#csvDownloadFrame').remove();
                form.remove();
                
            }, 3000); // 3 segundos para completar descarga
            
        }, 500); // Esperar 500ms antes de enviar formulario
        
    } catch (error) {
        console.error('Error al exportar CSV:', error);
        $('#display_new_access').html('<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> Error al iniciar la descarga del CSV</span>');
        restaurarTodo();
    }
}

// Event listeners para los nuevos botones
$(document).ready(function() {
    // Botón "Todos los préstamos"
    $('#showAllLoansBtn').click(function() {
        mostrarTodosLosPrestamos();
    });
    
    // Botón "Exportar CSV"
    $('#exportCSVBtn').click(function() {
        exportarCSV();
    });
});

</script> 