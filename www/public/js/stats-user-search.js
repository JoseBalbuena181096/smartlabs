/**
 * Buscador de Usuarios para Estadísticas SMARTLABS
 * Funcionalidad mejorada para búsqueda y selección de usuarios
 */

// Variables globales
var selectedUserInfo = null;
var searchTimeoutStats;

$(document).ready(function() {
    console.log('🚀 Inicializando buscador de usuarios para Stats');
    
    // Verificar que el elemento existe
    if ($('#userSearchStats').length === 0) {
        console.error('❌ ERROR: Elemento #userSearchStats no encontrado');
        return;
    }
    
    console.log('✅ Elemento #userSearchStats encontrado');
    
    // Inicializar eventos del buscador
    initializeUserSearchStats();
    
    // Interceptar el envío del formulario para incluir el usuario seleccionado
    $('#statsForm').on('submit', function(e) {
        if (selectedUserInfo) {
            console.log('📝 Usuario seleccionado incluido en formulario:', selectedUserInfo.hab_registration);
            // Crear un input oculto temporal para enviar con GET
            var hiddenInput = $('<input type="hidden" name="user_search" value="' + selectedUserInfo.hab_registration + '">');
            $(this).append(hiddenInput);
        }
    });
});

/**
 * Inicializar eventos del buscador de usuarios
 */
function initializeUserSearchStats() {
    // Búsqueda en tiempo real con debounce
    $('#userSearchStats').on('input', function() {
        clearTimeout(searchTimeoutStats);
        var query = $(this).val().trim();
        
        console.log('📝 Input detectado:', query);
        
        if (query.length >= 1) {
            console.log('🔍 Query válido, iniciando búsqueda con debounce');
            searchTimeoutStats = setTimeout(function() {
                console.log('⚡ Ejecutando búsqueda después del debounce');
                buscarUsuariosStats(query);
            }, 300); // Debounce de 300ms
        } else {
            console.log('❌ Query muy corto, ocultando resultados');
            $('#searchResultsStats').hide();
            $('#searchPlaceholderStats').text('Busca automáticamente mientras escribes');
            $('#selectedUserData').val('');
            selectedUserInfo = null;
        }
    });
    
    // Botón de búsqueda
    $('#searchBtnStats').click(function() {
        console.log('🔘 Botón de búsqueda clickeado');
        var query = $('#userSearchStats').val().trim();
        
        console.log('🔍 Query del botón:', query);
        
        if (query.length >= 1) {
            console.log('⚡ Ejecutando búsqueda desde botón');
            buscarUsuariosStats(query);
        } else {
            console.log('❌ Query muy corto para botón');
            $('#searchPlaceholderStats').text('Escribe al menos 1 carácter para buscar');
        }
    });
    
    // Event listener para keyup (respuesta instantánea)
    $('#userSearchStats').on('keyup', function() {
        var query = $(this).val().trim();
        
        if (query.length >= 1) {
            // Cancelar cualquier timeout pendiente
            clearTimeout(searchTimeoutStats);
            // Ejecutar búsqueda inmediata sin debounce
            buscarUsuariosStats(query);
        } else {
            $('#searchResultsStats').hide();
            $('#searchPlaceholderStats').text('Busca automáticamente mientras escribes');
        }
    });
    
    // Event listener para 'paste' (pegar texto)
    $('#userSearchStats').on('paste', function() {
        var self = this;
        setTimeout(function() {
            var query = $(self).val().trim();
            
            if (query.length >= 1) {
                console.log('📋 Ejecutando búsqueda desde paste');
                buscarUsuariosStats(query);
            }
        }, 50); // Pequeño delay para que el paste se complete
    });
    
    // Event listener para focus - mostrar mensaje inicial
    $('#userSearchStats').on('focus', function() {
        console.log('🎯 Input enfocado');
        $('#searchPlaceholderStats').text('Escribe nombre, matrícula o correo para buscar...');
    });
    
    // Limpiar selección cuando se modifica el input
    $('#userSearchStats').on('input', function() {
        if (selectedUserInfo) {
            $('#selectedUserData').val('');
            selectedUserInfo = null;
            $('#selectedUserInfo').hide();
        }
    });
}

/**
 * Función para buscar usuarios con optimización para respuesta rápida
 */
function buscarUsuariosStats(query) {
    console.log('=== 🔍 INICIANDO BÚSQUEDA AJAX ===');
    console.log('Query:', query);
    console.log('URL actual:', window.location.pathname);
    
    // Mostrar indicador de carga inmediato
    $('#searchResultsBodyStats').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Buscando...</td></tr>');
    $('#searchResultsStats').show();
    
    // Usar la URL correcta del controlador Stats
    var ajaxUrl = '/stats/';
    
    // Si estamos en la página stats, usar la URL actual
    if (window.location.pathname.includes('stats')) {
        ajaxUrl = window.location.pathname;
    }
    
    console.log('🌐 URL AJAX:', ajaxUrl);
    
    $.ajax({
        url: ajaxUrl,
        method: 'POST',
        data: {
            search_user: query
        },
        timeout: 5000, // 5 segundos timeout
        dataType: 'json', // Esperar JSON
        beforeSend: function() {
            console.log('📤 Enviando petición AJAX...');
        },
        success: function(response, status, xhr) {
            console.log('=== ✅ RESPUESTA AJAX EXITOSA ===');
            console.log('Status:', status);
            console.log('Response type:', typeof response);
            console.log('Response:', response);
            
            try {
                // Si la respuesta ya es un objeto (dataType: 'json'), no necesitamos parsear
                var users = Array.isArray(response) ? response : JSON.parse(response);
                console.log('👥 Usuarios procesados:', users);
                mostrarResultadosBusquedaStats(users);
                
                // Actualizar contador
                if (users.length > 0) {
                    $('#searchPlaceholderStats').text(users.length + ' usuario' + (users.length > 1 ? 's' : '') + ' encontrado' + (users.length > 1 ? 's' : ''));
                } else {
                    $('#searchPlaceholderStats').text('No se encontraron usuarios');
                }
            } catch (e) {
                console.error('❌ Error al parsear JSON:', e);
                console.error('Response recibida:', response);
                $('#searchResultsBodyStats').html('<tr><td colspan="5" class="text-center text-danger"><i class="fa fa-exclamation-triangle"></i> Error al procesar resultados</td></tr>');
                $('#searchPlaceholderStats').text('Error en la búsqueda');
            }
        },
        error: function(xhr, status, error) {
            console.error('=== ❌ ERROR AJAX ===');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response Code:', xhr.status);
            console.error('Response Text:', xhr.responseText);
            
            var errorMessage = 'Error desconocido';
            if (status === 'timeout') {
                errorMessage = 'Tiempo de espera agotado';
            } else if (status === 'error') {
                errorMessage = 'Error de conexión (Code: ' + xhr.status + ')';
            } else if (status === 'parsererror') {
                errorMessage = 'Error al parsear respuesta';
            }
            
            $('#searchResultsBodyStats').html('<tr><td colspan="5" class="text-center text-danger"><i class="fa fa-times-circle"></i> ' + errorMessage + '</td></tr>');
            $('#searchPlaceholderStats').text('Error: ' + errorMessage);
        },
        complete: function(xhr, status) {
            console.log('=== ✅ AJAX COMPLETADO ===');
            console.log('Status final:', status);
        }
    });
}

/**
 * Mostrar resultados de búsqueda
 */
function mostrarResultadosBusquedaStats(users) {
    console.log('📊 Mostrando resultados:', users);
    var html = '';
    
    if (users.length === 0) {
        html = '<tr><td colspan="5" class="text-center text-muted"><i class="fa fa-search"></i> No se encontraron usuarios</td></tr>';
    } else {
        users.forEach(function(user) {
            console.log('👤 Procesando usuario:', user);
            html += '<tr class="user-row-stats" data-user="' + encodeURIComponent(JSON.stringify(user)) + '">';
            html += '<td><input type="radio" name="selectedUserStats" value="' + user.hab_registration + '"></td>';
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
    
    $('#searchResultsBodyStats').html(html);
    console.log('📝 HTML generado:', html);
    
    // Hacer las filas clickeables
    $('#searchResultsBodyStats tr[data-user]').click(function() {
        var userData = JSON.parse(decodeURIComponent($(this).data('user')));
        var radio = $(this).find('input[type="radio"]');
        
        console.log('✅ Usuario seleccionado:', userData);
        
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
        console.log('💾 Campo oculto actualizado con:', userData.hab_registration);
        
        // Mostrar información del usuario seleccionado
        $('#selectedUserName').text(userData.hab_name);
        $('#selectedUserReg').text(userData.hab_registration);
        $('#selectedUserInfo').show();
        
        // Mostrar en el input principal
        $('#userSearchStats').val(userData.hab_name + ' (' + userData.hab_registration + ')');
        
        // Ocultar resultados después de 2 segundos
        setTimeout(function() {
            $('#searchResultsStats').fadeOut(300);
            $('#searchPlaceholderStats').text('Usuario seleccionado: ' + userData.hab_name);
        }, 2000);
    });
}

/**
 * Función para limpiar la selección de usuario
 */
function clearUserSelection() {
    selectedUserInfo = null;
    $('#selectedUserData').val('');
    $('#userSearchStats').val('');
    $('#selectedUserInfo').hide();
    $('#searchPlaceholderStats').text('Busca automáticamente mientras escribes');
    $('#searchResultsStats').hide();
    console.log('🧹 Selección de usuario limpiada');
}

/**
 * Función de prueba para verificar que el AJAX funciona
 */
function testSearch() {
    console.log('=== 🧪 INICIANDO PRUEBA DE BÚSQUEDA ===');
    console.log('Ejecutando búsqueda de prueba con query: jose');
    
    // También llenar el input para prueba visual
    $('#userSearchStats').val('jose');
    $('#searchPlaceholderStats').text('Ejecutando búsqueda de prueba...');
    
    buscarUsuariosStats('jose');
}

/**
 * Función global para verificar estado
 */
function debugBuscador() {
    console.log('=== 🐛 DEBUG BUSCADOR ===');
    console.log('jQuery cargado:', typeof $ !== 'undefined');
    console.log('Input existe:', $('#userSearchStats').length > 0);
    console.log('Resultados div existe:', $('#searchResultsStats').length > 0);
    console.log('Placeholder existe:', $('#searchPlaceholderStats').length > 0);
    
    if ($('#userSearchStats').length > 0) {
        console.log('Valor actual del input:', $('#userSearchStats').val());
    }
    
    console.log('Usuario seleccionado:', selectedUserInfo);
}

// Ejecutar debug automáticamente al cargar la página
$(window).on('load', function() {
    console.log('=== 📄 PÁGINA COMPLETAMENTE CARGADA ===');
    setTimeout(function() {
        debugBuscador();
        console.log('💡 Para probar manualmente, ejecuta: testSearch()');
    }, 500);
}); 