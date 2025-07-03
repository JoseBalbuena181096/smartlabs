<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Loan.php';
require_once __DIR__ . '/../models/Equipment.php';

class LoanAdminController extends Controller {
    private $loanModel;
    private $equipmentModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->loanModel = new Loan();
        $this->equipmentModel = new Equipment();
    }
    
    public function index() {
        // Manejar diferentes tipos de peticiones AJAX
        if ($_POST) {
            // Búsqueda de usuarios
            if (isset($_POST['search_user'])) {
                $query = strip_tags($_POST['search_user']);
                if (!empty($query)) {
                    $users = $this->buscarUsuarios($query);
                    echo json_encode($users);
                    exit();
                }
            }
            
            // Consulta de préstamos de un usuario específico
            if (isset($_POST['consult_loan_admin'])) {
                $userRfid = strip_tags($_POST['consult_loan_admin']);
                if (!empty($userRfid)) {
                    echo $this->consultarPrestamosAdmin($userRfid);
                    exit();
                }
            }
            
            // Devolver préstamo
            if (isset($_POST['return_loan'])) {
                $equipmentRfid = strip_tags($_POST['equipment_rfid']);
                $userRfid = strip_tags($_POST['user_rfid']);
                
                if (!empty($equipmentRfid) && !empty($userRfid)) {
                    $result = $this->devolverPrestamo($equipmentRfid, $userRfid);
                    echo json_encode($result);
                    exit();
                }
            }
            
            // Mostrar todos los préstamos
            if (isset($_POST['show_all_loans'])) {
                echo $this->mostrarTodosLosPrestamos();
                exit();
            }
            
            // Exportar CSV (método tradicional)
            if (isset($_POST['export_csv'])) {
                $this->exportarCSV();
                exit();
            }
            
            // Generar CSV como JSON para descarga AJAX
            if (isset($_POST['generate_csv_json'])) {
                $this->generarCSVJSON();
                exit();
            }
        }
        
        // Mostrar vista principal
        $this->view('loan_admin/index');
    }
    
    /**
     * Buscar usuarios por matrícula, nombre o correo
     */
    private function buscarUsuarios($query) {
        $sql = "SELECT h.hab_name, h.hab_registration, h.hab_email, c.cards_number,
                CASE 
                    WHEN h.hab_registration LIKE ? THEN 'matricula'
                    WHEN h.hab_name LIKE ? THEN 'nombre'
                    WHEN h.hab_email LIKE ? THEN 'email'
                    ELSE 'multiple'
                END as match_type
                FROM habintants h 
                JOIN cards c ON h.hab_card_id = c.cards_id 
                WHERE h.hab_registration LIKE ? 
                   OR h.hab_name LIKE ? 
                   OR h.hab_email LIKE ?
                ORDER BY h.hab_name 
                LIMIT 10";
        
        $searchTerm = "%{$query}%";
        $results = $this->db->query($sql, [
            $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm, $searchTerm
        ]);
        
        return $results;
    }
    
    /**
     * Consultar préstamos de un usuario específico (versión administrativa)
     */
    private function consultarPrestamosAdmin($userRfid) {
        $output = '';
        
        // Consultar información del usuario
        $user = $this->db->query("SELECT c.*, h.hab_registration, h.hab_email FROM `cards_habs` c 
                                  JOIN habintants h ON c.hab_id = h.hab_id 
                                  WHERE `cards_number` = ?", [$userRfid]);
        $user = !empty($user) ? $user[0] : null;
        
        if (!$user) {
            $output .= '<div class="alert alert-danger text-center">';
            $output .= '<i class="fa fa-exclamation-triangle fa-2x mb-3"></i><br>';
            $output .= '<h4>Usuario no encontrado</h4>';
            $output .= '<p>No se encontró usuario con RFID: <strong>' . htmlspecialchars($userRfid) . '</strong></p>';
            $output .= '</div>';
            return $output;
        }
        
        // Header del usuario
        $output .= '<div class="card mb-4 shadow-sm">';
        $output .= '<div class="card-header bg-danger text-white">';
        $output .= '<h4 class="mb-0"><i class="fa fa-user-cog"></i> Panel Administrativo - Préstamos del Usuario</h4>';
        $output .= '</div>';
        $output .= '<div class="card-body">';
        $output .= '<div class="row">';
        $output .= '<div class="col-md-6">';
        $output .= '<h3 class="text-primary"><i class="fa fa-user"></i> ' . htmlspecialchars($user["hab_name"]) . ' <small>(' . htmlspecialchars($user["hab_email"]) . ')</small></h3>';
        $output .= '<p class="text-muted"><i class="fa fa-credit-card"></i> RFID: <strong>' . htmlspecialchars($userRfid) . '</strong></p>';
        $output .= '<p class="text-muted"><i class="fa fa-id-badge"></i> Matrícula: <strong>' . htmlspecialchars($user["hab_registration"]) . '</strong></p>';
        $output .= '</div>';
        $output .= '<div class="col-md-6 text-right">';
        $output .= '<button class="btn btn-warning btn-sm" onclick="mostrarTodosLosPrestamos()">';
        $output .= '<i class="fa fa-arrow-left"></i> Volver a Todos los Préstamos';
        $output .= '</button>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Obtener préstamos del usuario
        $loans = $this->db->query("SELECT * FROM `habslab` WHERE `loans_hab_rfid` = ? ORDER BY `loans_date` DESC", [$userRfid]);
        
        $filtered_loans = [];
        $seen_equipments = [];
        
        foreach ($loans as $row) {
            $equipment_rfid = $row['equipments_rfid'];
            
            if (!isset($seen_equipments[$equipment_rfid])) {
                $seen_equipments[$equipment_rfid] = true;
                $filtered_loans[] = $row;
            }
        }
        
        // Contar préstamos activos
        $prestamos_activos = 0;
        foreach ($filtered_loans as $loan) {
            if($loan["loans_state"]=='1'){
                $equip_rfid = $loan["equipments_rfid"];
                $loan_date = $loan["loans_date"];
                $check_result = $this->db->query("SELECT * FROM `habslab` WHERE 
                                 `equipments_rfid` = ? AND 
                                 `loans_state` = '0' AND 
                                 `loans_date` > ? 
                                 ORDER BY `loans_date` DESC LIMIT 1", [$equip_rfid, $loan_date]);
                if (empty($check_result)) {
                    $prestamos_activos++;
                }
            }
        }
        
        // Mostrar tabla de préstamos
        if ($filtered_loans && $prestamos_activos > 0) {
            $output .= '<div class="card shadow-sm">';
            $output .= '<div class="card-header bg-warning text-dark">';
            $output .= '<h4 class="mb-0"><i class="fa fa-cog"></i> Equipos en Préstamo <span class="badge badge-danger">' . $prestamos_activos . '</span></h4>';
            $output .= '<small><i class="fa fa-shield-alt"></i> Administra las devoluciones desde este panel</small>';
            $output .= '</div>';
            $output .= '<div class="card-body p-0">';
            $output .= '<div class="table-responsive">';
            $output .= '<table class="table table-hover table-striped mb-0">';
            $output .= '<thead class="bg-light">';
            $output .= '<tr>';
            $output .= '<th><i class="fa fa-calendar text-primary"></i> FECHA</th>';
            $output .= '<th><i class="fa fa-cube text-success"></i> EQUIPO</th>';
            $output .= '<th><i class="fa fa-tag text-info"></i> MARCA</th>';
            $output .= '<th><i class="fa fa-clock-o text-warning"></i> TIEMPO</th>';
            $output .= '<th><i class="fa fa-cogs text-danger"></i> ACCIONES</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';
            
            foreach ($filtered_loans as $loan) {
                if($loan["loans_state"]=='1'){
                    $equip_rfid = $loan["equipments_rfid"];
                    $loan_date = $loan["loans_date"];
                    
                    $check_result = $this->db->query("SELECT * FROM `habslab` WHERE 
                                     `equipments_rfid` = ? AND 
                                     `loans_state` = '0' AND 
                                     `loans_date` > ? 
                                     ORDER BY `loans_date` DESC LIMIT 1", [$equip_rfid, $loan_date]);
                    
                    if (empty($check_result)) {
                        $fecha_formateada = date('d/m/Y H:i', strtotime($loan["loans_date"]));
                        $tiempo_transcurrido = $this->calcularTiempoTranscurrido($loan["loans_date"]);
                        
                        $output .= '<tr class="table-warning">';
                        $output .= '<td><strong>' . $fecha_formateada . '</strong></td>';
                        $output .= '<td>';
                        $output .= '<div class="d-flex align-items-center">';
                        $output .= '<i class="fa fa-cog text-primary mr-2"></i>';
                        $output .= '<span class="font-weight-bold">' . htmlspecialchars($loan["equipments_name"]) . '</span>';
                        $output .= '</div>';
                        $output .= '</td>';
                        $output .= '<td><span class="badge badge-info">' . htmlspecialchars($loan["equipments_brand"]) . '</span></td>';
                        $output .= '<td><small class="text-muted">' . $tiempo_transcurrido . '</small></td>';
                        $output .= '<td>';
                        $output .= '<button class="btn btn-danger btn-sm btn-return" onclick="confirmarDevolucion(\'' . $equip_rfid . '\', \'' . $userRfid . '\', \'' . htmlspecialchars($loan["equipments_name"]) . '\')">';
                        $output .= '<i class="fa fa-undo mr-1"></i>DEVOLVER';
                        $output .= '</button>';
                        $output .= '</td>';
                        $output .= '</tr>';
                    }
                }
            }
            
            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '<div class="card-footer bg-light text-center">';
            $output .= '<small class="text-muted"><i class="fa fa-info-circle"></i> Haz clic en DEVOLVER para procesar la devolución de cada equipo</small>';
            $output .= '</div>';
            $output .= '</div>';
        } else {
            $output .= '<div class="alert alert-info text-center">';
            $output .= '<i class="fa fa-info-circle fa-2x mb-3"></i><br>';
            $output .= '<h4>Sin préstamos activos</h4>';
            $output .= '<p>Este usuario no tiene equipos en préstamo actualmente.</p>';
            $output .= '</div>';
        }
        
        return $output;
    }
    
    /**
     * Devolver un préstamo específico
     */
    private function devolverPrestamo($equipmentRfid, $userRfid) {
        try {
            // Insertar registro de devolución
            $sql = "INSERT INTO loans (loans_hab_rfid, loans_equip_rfid, loans_state, loans_date) 
                    VALUES (?, ?, 0, NOW())";
            
            $this->db->query($sql, [$userRfid, $equipmentRfid]);
            
            return [
                'success' => true,
                'message' => 'Devolución procesada exitosamente',
                'datetime' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al procesar la devolución: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Mostrar todos los préstamos activos del sistema
     */
    private function mostrarTodosLosPrestamos() {
        $output = '';
        
        // Header general
        $output .= '<div class="card mb-4 shadow-sm">';
        $output .= '<div class="card-header bg-warning text-dark">';
        $output .= '<h4 class="mb-0"><i class="fa fa-list-alt"></i> Todos los Préstamos Activos del Sistema</h4>';
        $output .= '<small><i class="fa fa-info-circle"></i> Vista general de todos los equipos prestados</small>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Obtener todos los préstamos
        $all_loans = $this->db->query("SELECT h.*, hab.hab_name, hab.hab_registration, hab.hab_email 
                                       FROM habslab h 
                                       JOIN cards c ON h.loans_hab_rfid = c.cards_number
                                       JOIN habintants hab ON c.cards_id = hab.hab_card_id 
                                       ORDER BY h.loans_date DESC");
        
        $active_loans = [];
        $seen_equipments = [];
        
        foreach ($all_loans as $loan) {
            $equipment_rfid = $loan['equipments_rfid'];
            $user_rfid = $loan['loans_hab_rfid'];
            $key = $equipment_rfid . '_' . $user_rfid;
            
            if (!isset($seen_equipments[$key])) {
                $seen_equipments[$key] = true;
                
                if ($loan["loans_state"] == '1') {
                    $check_result = $this->db->query("SELECT * FROM `habslab` WHERE 
                                     `equipments_rfid` = ? AND 
                                     `loans_state` = '0' AND 
                                     `loans_date` > ? 
                                     ORDER BY `loans_date` DESC LIMIT 1", 
                                     [$equipment_rfid, $loan["loans_date"]]);
                    
                    if (empty($check_result)) {
                        $active_loans[] = $loan;
                    }
                }
            }
        }
        
        $total_prestamos = count($active_loans);
        
        if ($total_prestamos > 0) {
            $output .= '<div class="card shadow-sm">';
            $output .= '<div class="card-header bg-danger text-white">';
            $output .= '<h4 class="mb-0"><i class="fa fa-exclamation-triangle"></i> Préstamos Activos <span class="badge badge-light">' . $total_prestamos . '</span></h4>';
            $output .= '<small>Gestiona las devoluciones desde este panel administrativo</small>';
            $output .= '</div>';
            $output .= '<div class="card-body p-0">';
            $output .= '<div class="table-responsive">';
            $output .= '<table class="table table-hover table-striped mb-0">';
            $output .= '<thead class="bg-light">';
            $output .= '<tr>';
            $output .= '<th><i class="fa fa-user text-primary"></i> USUARIO</th>';
            $output .= '<th><i class="fa fa-id-badge text-info"></i> MATRÍCULA</th>';
            $output .= '<th><i class="fa fa-calendar text-success"></i> FECHA</th>';
            $output .= '<th><i class="fa fa-cube text-warning"></i> EQUIPO</th>';
            $output .= '<th><i class="fa fa-tag text-secondary"></i> MARCA</th>';
            $output .= '<th><i class="fa fa-clock-o text-muted"></i> TIEMPO</th>';
            $output .= '<th><i class="fa fa-cogs text-danger"></i> ACCIONES</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';
            
            foreach ($active_loans as $loan) {
                $fecha_formateada = date('d/m/Y H:i', strtotime($loan["loans_date"]));
                $tiempo_transcurrido = $this->calcularTiempoTranscurrido($loan["loans_date"]);
                
                $output .= '<tr class="table-warning">';
                $output .= '<td>';
                $output .= '<div class="d-flex align-items-center">';
                $output .= '<i class="fa fa-user-circle text-primary mr-2"></i>';
                $output .= '<div>';
                $output .= '<strong>' . htmlspecialchars($loan["hab_name"]) . '</strong><br>';
                $output .= '<small class="text-muted">' . htmlspecialchars($loan["hab_email"]) . '</small>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</td>';
                $output .= '<td><span class="badge badge-primary">' . htmlspecialchars($loan["hab_registration"]) . '</span></td>';
                $output .= '<td><strong>' . $fecha_formateada . '</strong></td>';
                $output .= '<td>';
                $output .= '<div class="d-flex align-items-center">';
                $output .= '<i class="fa fa-cog text-primary mr-2"></i>';
                $output .= '<span class="font-weight-bold">' . htmlspecialchars($loan["equipments_name"]) . '</span>';
                $output .= '</div>';
                $output .= '</td>';
                $output .= '<td><span class="badge badge-info">' . htmlspecialchars($loan["equipments_brand"]) . '</span></td>';
                $output .= '<td><small class="text-muted">' . $tiempo_transcurrido . '</small></td>';
                $output .= '<td>';
                $output .= '<button class="btn btn-danger btn-sm btn-return" onclick="confirmarDevolucion(\'' . $loan["equipments_rfid"] . '\', \'' . $loan["loans_hab_rfid"] . '\', \'' . htmlspecialchars($loan["equipments_name"]) . '\')">';
                $output .= '<i class="fa fa-undo mr-1"></i>DEVOLVER';
                $output .= '</button>';
                $output .= '</td>';
                $output .= '</tr>';
            }
            
            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '<div class="card-footer bg-light text-center">';
            $output .= '<small class="text-muted"><i class="fa fa-info-circle"></i> Total de préstamos activos: <strong>' . $total_prestamos . '</strong> equipos</small>';
            $output .= '</div>';
            $output .= '</div>';
        } else {
            $output .= '<div class="alert alert-success text-center">';
            $output .= '<i class="fa fa-check-circle fa-2x mb-3"></i><br>';
            $output .= '<h4>¡Excelente!</h4>';
            $output .= '<p>No hay préstamos activos en el sistema actualmente.</p>';
            $output .= '</div>';
        }
        
        return $output;
    }
    
    /**
     * Exportar todos los préstamos a CSV
     */
    private function exportarCSV() {
        // Obtener todos los préstamos activos
        $all_loans = $this->db->query("SELECT h.*, hab.hab_name, hab.hab_registration, hab.hab_email 
                                       FROM habslab h 
                                       JOIN cards c ON h.loans_hab_rfid = c.cards_number
                                       JOIN habintants hab ON c.cards_id = hab.hab_card_id 
                                       ORDER BY h.loans_date DESC");
        
        $active_loans = [];
        $seen_equipments = [];
        
        foreach ($all_loans as $loan) {
            $equipment_rfid = $loan['equipments_rfid'];
            $user_rfid = $loan['loans_hab_rfid'];
            $key = $equipment_rfid . '_' . $user_rfid;
            
            if (!isset($seen_equipments[$key])) {
                $seen_equipments[$key] = true;
                
                if ($loan["loans_state"] == '1') {
                    $check_result = $this->db->query("SELECT * FROM `habslab` WHERE 
                                     `equipments_rfid` = ? AND 
                                     `loans_state` = '0' AND 
                                     `loans_date` > ? 
                                     ORDER BY `loans_date` DESC LIMIT 1", 
                                     [$equipment_rfid, $loan["loans_date"]]);
                    
                    if (empty($check_result)) {
                        $active_loans[] = $loan;
                    }
                }
            }
        }
        
        // Configurar headers para descarga
        $filename = 'prestamos_activos_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Crear el archivo CSV
        $output = fopen('php://output', 'w');
        
        // Escribir BOM para UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Escribir headers
        fputcsv($output, [
            'USUARIO',
            'MATRÍCULA',
            'CORREO',
            'FECHA_PRÉSTAMO',
            'EQUIPO',
            'MARCA',
            'RFID_USUARIO',
            'RFID_EQUIPO',
            'TIEMPO_TRANSCURRIDO'
        ]);
        
        // Escribir datos
        foreach ($active_loans as $loan) {
            $tiempo_transcurrido = $this->calcularTiempoTranscurrido($loan["loans_date"]);
            
            fputcsv($output, [
                $loan["hab_name"],
                $loan["hab_registration"],
                $loan["hab_email"],
                $loan["loans_date"],
                $loan["equipments_name"],
                $loan["equipments_brand"],
                $loan["loans_hab_rfid"],
                $loan["equipments_rfid"],
                $tiempo_transcurrido
            ]);
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Calcular tiempo transcurrido desde una fecha
     */
    private function calcularTiempoTranscurrido($fecha) {
        $ahora = new DateTime();
        $fecha_prestamo = new DateTime($fecha);
        $diferencia = $ahora->diff($fecha_prestamo);
        
        if ($diferencia->days > 0) {
            return $diferencia->days . ' día' . ($diferencia->days > 1 ? 's' : '') . ' ' . $diferencia->h . 'h';
        } elseif ($diferencia->h > 0) {
            return $diferencia->h . 'h ' . $diferencia->i . 'm';
        } else {
            return $diferencia->i . ' minutos';
        }
    }

    /**
     * Generar CSV como respuesta JSON para descarga AJAX
     */
    private function generarCSVJSON() {
        // Aumentar límite de memoria y tiempo de ejecución
        ini_set('memory_limit', '256M');
        set_time_limit(600); // 10 minutos
        
        try {
            // Obtener todos los préstamos activos
            $all_loans = $this->db->query("SELECT h.*, hab.hab_name, hab.hab_registration, hab.hab_email 
                                           FROM habslab h 
                                           JOIN cards c ON h.loans_hab_rfid = c.cards_number
                                           JOIN habintants hab ON c.cards_id = hab.hab_card_id 
                                           ORDER BY h.loans_date DESC");
            
            if (empty($all_loans)) {
                // Si no hay préstamos, devolver CSV vacío
                $response = [
                    'success' => true,
                    'filename' => 'prestamos_activos_' . date('Y-m-d_H-i-s') . '.csv',
                    'content' => base64_encode("\xEF\xBB\xBF" . "USUARIO,MATRÍCULA,CORREO,FECHA_PRÉSTAMO,EQUIPO,MARCA,RFID_USUARIO,RFID_EQUIPO,TIEMPO_TRANSCURRIDO\n"),
                    'total_records' => 0,
                    'generated_at' => date('Y-m-d H:i:s')
                ];
                
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($response);
                exit();
            }
            
            $active_loans = [];
            $seen_equipments = [];
            
            foreach ($all_loans as $loan) {
                $equipment_rfid = $loan['equipments_rfid'];
                $user_rfid = $loan['loans_hab_rfid'];
                $key = $equipment_rfid . '_' . $user_rfid;
                
                if (!isset($seen_equipments[$key])) {
                    $seen_equipments[$key] = true;
                    
                    if ($loan["loans_state"] == '1') {
                        $check_result = $this->db->query("SELECT * FROM `habslab` WHERE 
                                         `equipments_rfid` = ? AND 
                                         `loans_state` = '0' AND 
                                         `loans_date` > ? 
                                         ORDER BY `loans_date` DESC LIMIT 1", 
                                         [$equipment_rfid, $loan["loans_date"]]);
                        
                        if (empty($check_result)) {
                            $active_loans[] = $loan;
                        }
                    }
                }
            }
            
            // Crear un archivo temporal en memoria con mayor capacidad
            $temp_file = fopen('php://temp/maxmemory:5242880', 'w'); // 5MB
            
            if (!$temp_file) {
                throw new Exception('No se pudo crear el archivo temporal');
            }
            
            // Escribir BOM para UTF-8
            fwrite($temp_file, "\xEF\xBB\xBF");
            
            // Escribir headers
            fputcsv($temp_file, [
                'USUARIO',
                'MATRÍCULA',
                'CORREO',
                'FECHA_PRÉSTAMO',
                'EQUIPO',
                'MARCA',
                'RFID_USUARIO',
                'RFID_EQUIPO',
                'TIEMPO_TRANSCURRIDO'
            ]);
            
            // Escribir datos
            foreach ($active_loans as $loan) {
                $tiempo_transcurrido = $this->calcularTiempoTranscurrido($loan["loans_date"]);
                
                fputcsv($temp_file, [
                    $loan["hab_name"] ?? '',
                    $loan["hab_registration"] ?? '',
                    $loan["hab_email"] ?? '',
                    $loan["loans_date"] ?? '',
                    $loan["equipments_name"] ?? '',
                    $loan["equipments_brand"] ?? '',
                    $loan["loans_hab_rfid"] ?? '',
                    $loan["equipments_rfid"] ?? '',
                    $tiempo_transcurrido
                ]);
            }
            
            // Obtener el contenido del archivo
            rewind($temp_file);
            $csv_content = stream_get_contents($temp_file);
            fclose($temp_file);
            
            if ($csv_content === false) {
                throw new Exception('Error al leer el contenido del archivo CSV');
            }
            
            // Preparar respuesta JSON
            $filename = 'prestamos_activos_' . date('Y-m-d_H-i-s') . '.csv';
            $response = [
                'success' => true,
                'filename' => $filename,
                'content' => base64_encode($csv_content),
                'total_records' => count($active_loans),
                'generated_at' => date('Y-m-d H:i:s'),
                'file_size' => strlen($csv_content)
            ];
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            exit();
            
        } catch (Exception $e) {
            // En caso de error, devolver respuesta de error
            $error_response = [
                'success' => false,
                'error' => $e->getMessage(),
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($error_response);
            exit();
        }
    }
}
?>