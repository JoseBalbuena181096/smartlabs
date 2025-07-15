<!DOCTYPE html>
<html>
<head>
    <title>Test AJAX Stats Usuario</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .debug-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .user-row-stats { cursor: pointer; }
        .user-row-stats:hover { background-color: #f0f8ff; }
        .user-row-stats.table-success { background-color: #d4edda; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🧪 Test AJAX Búsqueda de Usuarios - Stats</h1>
        
        <div class="debug-info">
            <h5>Debug Info:</h5>
            <p id="debugInfo">Escribe algo para buscar...</p>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Buscar Usuario</h5>
                    </div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <input type="text" id="userSearchStats" class="form-control" 
                                   placeholder="Buscar por matrícula, nombre o correo..." autocomplete="off">
                            <button class="btn btn-primary" type="button" id="searchBtnStats">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i> 
                            <span id="searchPlaceholderStats">Busca automáticamente mientras escribes</span>
                        </small>
                        
                        <!-- Resultados de búsqueda -->
                        <div id="searchResultsStats" class="mt-3" style="display: none;">
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="5%"><i class="fa fa-check-circle text-success"></i></th>
                                            <th><i class="fa fa-user text-primary"></i> Usuario</th>
                                            <th><i class="fa fa-id-badge text-info"></i> Matrícula</th>
                                            <th><i class="fa fa-envelope text-warning"></i> Email</th>
                                            <th><i class="fa fa-tags text-success"></i> Tipo</th>
                                        </tr>
                                    </thead>
                                    <tbody id="searchResultsBodyStats">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Usuario Seleccionado</h5>
                    </div>
                    <div class="card-body">
                        <p id="selectedUserInfo">Ningún usuario seleccionado</p>
                        <input type="hidden" id="selectedUserData" value="">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Log de Peticiones AJAX</h5>
                    </div>
                    <div class="card-body">
                        <div id="ajaxLog" style="height: 300px; overflow-y: auto; background: #f8f9fa; padding: 10px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var searchTimeoutStats;
            var selectedUserInfo = null;
            var requestCounter = 0;
            
            function logAjax(message) {
                var timestamp = new Date().toLocaleTimeString();
                $('#ajaxLog').append(`<div>[${timestamp}] ${message}</div>`);
                $('#ajaxLog').scrollTop($('#ajaxLog')[0].scrollHeight);
            }
            
            function updateDebugInfo(message) {
                $('#debugInfo').text(message);
            }
            
            // Búsqueda en tiempo real con debounce
            $('#userSearchStats').on('input', function() {
                clearTimeout(searchTimeoutStats);
                var query = $(this).val().trim();
                
                updateDebugInfo(`Escribiendo: "${query}" (${query.length} caracteres)`);
                
                if (query.length >= 2) {
                    searchTimeoutStats = setTimeout(function() {
                        buscarUsuariosStats(query);
                    }, 500);
                    updateDebugInfo(`Búsqueda programada para: "${query}"`);
                } else {
                    $('#searchResultsStats').hide();
                    $('#searchPlaceholderStats').text('Busca automáticamente mientras escribes');
                    $('#selectedUserData').val('');
                    selectedUserInfo = null;
                    updateDebugInfo('Muy pocos caracteres, búsqueda cancelada');
                }
            });
            
            // Botón de búsqueda
            $('#searchBtnStats').click(function() {
                var query = $('#userSearchStats').val().trim();
                
                if (query.length >= 1) {
                    buscarUsuariosStats(query);
                } else {
                    $('#searchPlaceholderStats').text('Escribe al menos 2 caracteres para buscar');
                    updateDebugInfo('Búsqueda manual sin suficientes caracteres');
                }
            });
            
            // Función para buscar usuarios
            function buscarUsuariosStats(query) {
                requestCounter++;
                var currentRequest = requestCounter;
                
                logAjax(`🔍 Iniciando búsqueda #${currentRequest} para: "${query}"`);
                updateDebugInfo(`Buscando usuarios... (Petición #${currentRequest})`);
                
                $.ajax({
                    url: '/Stats/index',
                    method: 'POST',
                    data: {
                        search_user: query
                    },
                    timeout: 10000,
                    beforeSend: function() {
                        logAjax(`📡 Enviando petición #${currentRequest} a /Stats/index`);
                        $('#searchResultsBodyStats').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Buscando usuarios...</td></tr>');
                        $('#searchResultsStats').fadeIn(300);
                    },
                    success: function(response) {
                        logAjax(`✅ Respuesta recibida #${currentRequest}: ${response.length} caracteres`);
                        logAjax(`📄 Respuesta: ${response}`);
                        
                        try {
                            var users = JSON.parse(response);
                            logAjax(`📊 JSON parseado correctamente: ${users.length} usuarios`);
                            
                            mostrarResultadosBusquedaStats(users);
                            
                            if (users.length > 0) {
                                $('#searchPlaceholderStats').text(users.length + ' usuario' + (users.length > 1 ? 's' : '') + ' encontrado' + (users.length > 1 ? 's' : ''));
                                updateDebugInfo(`Éxito: ${users.length} usuarios encontrados`);
                            } else {
                                $('#searchPlaceholderStats').text('No se encontraron usuarios');
                                updateDebugInfo('Sin resultados');
                            }
                        } catch (e) {
                            logAjax(`❌ Error parseando JSON #${currentRequest}: ${e.message}`);
                            $('#searchResultsBodyStats').html('<tr><td colspan="5" class="text-center text-danger"><i class="fa fa-exclamation-triangle"></i> Error al procesar resultados</td></tr>');
                            $('#searchPlaceholderStats').text('Error en la búsqueda');
                            updateDebugInfo('Error parseando JSON');
                        }
                    },
                    error: function(xhr, status, error) {
                        logAjax(`❌ Error AJAX #${currentRequest}: ${status} - ${error}`);
                        logAjax(`📋 Status: ${xhr.status}, ReadyState: ${xhr.readyState}`);
                        logAjax(`📋 ResponseText: ${xhr.responseText}`);
                        
                        $('#searchResultsBodyStats').html('<tr><td colspan="5" class="text-center text-danger"><i class="fa fa-times-circle"></i> Error en la búsqueda</td></tr>');
                        $('#searchPlaceholderStats').text('Error de conexión');
                        updateDebugInfo(`Error: ${status} - ${error}`);
                    }
                });
            }
            
            // Mostrar resultados de búsqueda
            function mostrarResultadosBusquedaStats(users) {
                var html = '';
                
                if (users.length === 0) {
                    html = '<tr><td colspan="5" class="text-center text-muted"><i class="fa fa-search"></i> No se encontraron usuarios</td></tr>';
                } else {
                    users.forEach(function(user) {
                        html += '<tr class="user-row-stats" data-user="' + encodeURIComponent(JSON.stringify(user)) + '">';
                        html += '<td><input type="radio" name="selectedUserStats" value="' + user.hab_registration + '"></td>';
                        html += '<td><strong><i class="fa fa-user text-primary mr-1"></i>' + user.hab_name + '</strong></td>';
                        html += '<td><span class="badge bg-primary"><i class="fa fa-id-badge mr-1"></i>' + user.hab_registration + '</span></td>';
                        html += '<td><small><i class="fa fa-envelope text-muted mr-1"></i>' + user.hab_email + '</small></td>';
                        
                        // Mostrar tipo de coincidencia
                        var coincidencia = '';
                        if (user.match_type) {
                            switch(user.match_type) {
                                case 'matricula':
                                    coincidencia = '<span class="badge bg-info"><i class="fa fa-id-badge"></i> Matrícula</span>';
                                    break;
                                case 'nombre':
                                    coincidencia = '<span class="badge bg-success"><i class="fa fa-user"></i> Nombre</span>';
                                    break;
                                case 'email':
                                    coincidencia = '<span class="badge bg-warning"><i class="fa fa-envelope"></i> Correo</span>';
                                    break;
                                default:
                                    coincidencia = '<span class="badge bg-secondary"><i class="fa fa-search"></i> Múltiple</span>';
                            }
                        } else {
                            coincidencia = '<span class="badge bg-secondary"><i class="fa fa-search"></i> General</span>';
                        }
                        html += '<td>' + coincidencia + '</td>';
                        html += '</tr>';
                    });
                }
                
                $('#searchResultsBodyStats').html(html);
                
                // Hacer las filas clickeables
                $('#searchResultsBodyStats tr[data-user]').click(function() {
                    var userData = JSON.parse(decodeURIComponent($(this).data('user')));
                    var radio = $(this).find('input[type="radio"]');
                    
                    logAjax(`👤 Usuario seleccionado: ${userData.hab_name} (${userData.hab_registration})`);
                    
                    // Seleccionar radio button
                    radio.prop('checked', true);
                    
                    // Animar la selección
                    $(this).addClass('table-success').siblings().removeClass('table-success');
                    
                    // Mostrar feedback visual
                    $(this).find('td:first').html('<i class="fa fa-check-circle text-success fa-lg"></i>');
                    
                    // Guardar información del usuario seleccionado
                    selectedUserInfo = userData;
                    
                    // Actualizar campo oculto con la matrícula para el formulario
                    $('#selectedUserData').val(userData.hab_registration);
                    
                    // Mostrar en el input principal
                    $('#userSearchStats').val(userData.hab_name + ' (' + userData.hab_registration + ')');
                    
                    // Mostrar información del usuario seleccionado
                    $('#selectedUserInfo').html(`
                        <strong>${userData.hab_name}</strong><br>
                        <small>Matrícula: ${userData.hab_registration}</small><br>
                        <small>Email: ${userData.hab_email}</small><br>
                        <small>Tipo: ${userData.match_type}</small>
                    `);
                    
                    // Ocultar resultados después de 2 segundos
                    setTimeout(function() {
                        $('#searchResultsStats').fadeOut(300);
                        $('#searchPlaceholderStats').text('Usuario seleccionado: ' + userData.hab_name);
                        updateDebugInfo(`Usuario seleccionado: ${userData.hab_name}`);
                    }, 2000);
                });
            }
            
            logAjax('🚀 Sistema de búsqueda inicializado correctamente');
            updateDebugInfo('Sistema listo. Escribe para buscar...');
        });
    </script>
</body>
</html> 