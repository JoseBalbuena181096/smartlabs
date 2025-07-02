<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Loan.php';
require_once __DIR__ . '/../models/Equipment.php';

class LoanController extends Controller {
    private $loanModel;
    private $equipmentModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->loanModel = new Loan();
        $this->equipmentModel = new Equipment();
    }
    
    public function index() {
        // Manejar petición AJAX de consulta de préstamos (como dash_loan.php)
        if ($_POST && isset($_POST['consult_loan'])) {
            $consult_loan = strip_tags($_POST['consult_loan']);
            
            if (!empty($consult_loan)) {
                echo $this->consultarPrestamos($consult_loan);
                exit(); // Terminar ejecución para AJAX
            }
        }
        
        $activeLoans = $this->loanModel->getActiveLoans();
        
        $this->view('loan/index', [
            'loans' => $activeLoans
        ]);
    }
    
    /**
     * Función para consultar los préstamos del usuario y generar la tabla HTML
     * Replica exactamente la funcionalidad de dash_loan.php
     */
    private function consultarPrestamos($consult_loan) {
        $output = '';
        
        // Consultar nombre del usuario
        $row_name_result = $this->db->query("SELECT * FROM `cards_habs` WHERE `cards_number` = ?", [$consult_loan]);
        $row_name = !empty($row_name_result) ? $row_name_result[0] : null;
        
        if ($row_name) {
            $output .= '<div class="card mb-4">';
            $output .= '<div class="card-header bg-primary text-white">';
            $output .= '<h4 class="mb-0"><i class="fa fa-user-circle"></i> Préstamos del Usuario</h4>';
            $output .= '</div>';
            $output .= '<div class="card-body">';
            $output .= '<h3 class="text-primary"><i class="fa fa-user"></i> ' . htmlspecialchars($row_name["hab_name"]) . '</h3>';
            $output .= '<p class="text-muted"><i class="fa fa-credit-card"></i> RFID: <strong>' . htmlspecialchars($consult_loan) . '</strong></p>';
            $output .= '</div>';
            $output .= '</div>';
        } else {
            // Usuario no encontrado
            $output .= '<div class="alert alert-warning text-center">';
            $output .= '<i class="fa fa-exclamation-triangle fa-2x text-warning mb-3"></i><br>';
            $output .= '<h4 class="text-warning">Usuario no encontrado</h4>';
            $output .= '<p class="mb-0">No se encontró un usuario registrado con el RFID: <strong>' . htmlspecialchars($consult_loan) . '</strong><br>';
            $output .= '<small class="text-muted">Verifica que la tarjeta esté registrada en el sistema.</small></p>';
            $output .= '</div>';
            return $output; // Salir temprano si no hay usuario
        }
        
        // Consultar préstamos
        $loans = $this->db->query("SELECT * FROM `habslab` WHERE `loans_hab_rfid` = ? ORDER BY `loans_date` DESC", [$consult_loan]);
        
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
        
        // Generar tabla de préstamos con mejor estética
        if ($filtered_loans && $prestamos_activos > 0) {
            // Agregar estilos CSS para animaciones
            $output .= '<style>
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .pulse {
                    animation: pulse 2s infinite;
                }
                @keyframes pulse {
                    0% { opacity: 1; }
                    50% { opacity: 0.7; }
                    100% { opacity: 1; }
                }
                .table-hover tbody tr:hover {
                    background-color: #f8f9fa !important;
                    transform: scale(1.01);
                    transition: all 0.2s ease;
                }
                .badge-pill {
                    font-size: 0.8em;
                    padding: 0.4em 0.8em;
                }
            </style>';
            
            $output .= '<div class="card shadow-sm">';
            $output .= '<div class="card-header bg-success text-white">';
            $output .= '<h4 class="mb-0"><i class="fa fa-list-alt"></i> Equipos Prestados Actualmente <span class="badge badge-light">' . $prestamos_activos . '</span></h4>';
            $output .= '<small><i class="fa fa-info-circle"></i> Lista de equipos que tienes en préstamo activo</small>';
            $output .= '</div>';
            $output .= '<div class="card-body p-0">';
            $output .= '<div class="table-responsive">';
            $output .= '<table class="table table-hover table-striped mb-0">';
            $output .= '<thead class="bg-light">';
            $output .= '<tr>';
            $output .= '<th><i class="fa fa-calendar text-primary"></i> FECHA PRÉSTAMO</th>';
            $output .= '<th><i class="fa fa-cube text-success"></i> EQUIPO</th>';
            $output .= '<th><i class="fa fa-tag text-info"></i> MARCA</th>';
            $output .= '<th><i class="fa fa-check-circle text-warning"></i> ESTADO</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';
            
            foreach ($filtered_loans as $loan) {
                if($loan["loans_state"]=='1'){
                    // Verificar si hay un registro de devolución más reciente
                    $equip_rfid = $loan["equipments_rfid"];
                    $loan_date = $loan["loans_date"];
                    
                    // Consultar si existe un registro de devolución más reciente para este equipo
                    $check_result = $this->db->query("SELECT * FROM `habslab` WHERE 
                                     `equipments_rfid` = ? AND 
                                     `loans_state` = '0' AND 
                                     `loans_date` > ? 
                                     ORDER BY `loans_date` DESC LIMIT 1", [$equip_rfid, $loan_date]);
                    
                    // Si no hay devolución más reciente, mostrar como prestado
                    if (empty($check_result)) {
                        $fecha_formateada = date('d/m/Y H:i', strtotime($loan["loans_date"]));
                        $tiempo_transcurrido = $this->calcularTiempoTranscurrido($loan["loans_date"]);
                        
                        $output .= '<tr class="table-warning" style="animation: fadeIn 0.5s ease-in;">';
                        $output .= '<td>';
                        $output .= '<div class="d-flex flex-column">';
                        $output .= '<strong class="text-dark">' . $fecha_formateada . '</strong>';
                        $output .= '<small class="text-muted"><i class="fa fa-clock-o"></i> ' . $tiempo_transcurrido . '</small>';
                        $output .= '</div>';
                        $output .= '</td>';
                        $output .= '<td>';
                        $output .= '<div class="d-flex align-items-center">';
                        $output .= '<i class="fa fa-cog text-primary mr-2"></i>';
                        $output .= '<span class="font-weight-bold">' . htmlspecialchars($loan["equipments_name"]) . '</span>';
                        $output .= '</div>';
                        $output .= '</td>';
                        $output .= '<td>';
                        $output .= '<span class="badge badge-info badge-pill">';
                        $output .= '<i class="fa fa-industry mr-1"></i>' . htmlspecialchars($loan["equipments_brand"]);
                        $output .= '</span>';
                        $output .= '</td>';
                        $output .= '<td>';
                        $output .= '<span class="badge badge-warning badge-pill pulse">';
                        $output .= '<i class="fa fa-exclamation-triangle mr-1"></i>EN PRÉSTAMO';
                        $output .= '</span>';
                        $output .= '</td>';
                        $output .= '</tr>';
                    }
                }
            }
            
            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>'; // table-responsive
            $output .= '</div>'; // card-body
            $output .= '<div class="card-footer bg-light">';
            $output .= '<div class="row">';
            $output .= '<div class="col-md-6">';
            $output .= '<small class="text-muted"><i class="fa fa-lightbulb-o"></i> <strong>Tip:</strong> Acerca la tarjeta al lector para devolver equipos</small>';
            $output .= '</div>';
            $output .= '<div class="col-md-6 text-right">';
            $output .= '<small class="text-success"><i class="fa fa-check-circle"></i> Total equipos prestados: <strong>' . $prestamos_activos . '</strong></small>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>'; // card-footer
            $output .= '</div>'; // card
        } else {
            $output .= '<div class="alert alert-success text-center">';
            $output .= '<i class="fa fa-check-circle fa-3x text-success mb-3"></i><br>';
            $output .= '<h4 class="text-success">¡Excelente!</h4>';
            $output .= '<p class="mb-0">No tienes equipos pendientes de devolución.<br>';
            $output .= '<small class="text-muted">Puedes solicitar nuevos préstamos cuando lo necesites.</small></p>';
            $output .= '</div>';
        }
        
        return $output;
    }
    
    /**
     * Calcula el tiempo transcurrido desde una fecha dada
     */
    private function calcularTiempoTranscurrido($fecha) {
        $ahora = new DateTime();
        $fecha_prestamo = new DateTime($fecha);
        $diferencia = $ahora->diff($fecha_prestamo);
        
        if ($diferencia->days > 0) {
            return $diferencia->days . ' día' . ($diferencia->days > 1 ? 's' : '') . ' ago';
        } elseif ($diferencia->h > 0) {
            return $diferencia->h . ' hora' . ($diferencia->h > 1 ? 's' : '') . ' ago';
        } elseif ($diferencia->i > 0) {
            return $diferencia->i . ' minuto' . ($diferencia->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Hace un momento';
        }
    }
    
    public function history() {
        $allLoans = $this->loanModel->getAllLoans();
        
        $this->view('loan/history', [
            'loans' => $allLoans
        ]);
    }
    
    public function create() {
        if ($_POST) {
            $habRfid = $this->sanitize($_POST['hab_rfid']);
            $equipRfid = $this->sanitize($_POST['equip_rfid']);
            
            if (empty($habRfid) || empty($equipRfid)) {
                $equipments = $this->equipmentModel->getAvailableEquipments();
                
                $this->view('loan/create', [
                    'error' => 'Todos los campos son requeridos',
                    'hab_rfid' => $habRfid,
                    'equip_rfid' => $equipRfid,
                    'equipments' => $equipments
                ]);
                return;
            }
            
            // Verificar si el equipo ya está prestado
            $activeLoan = $this->loanModel->getActiveLoanByEquipment($equipRfid);
            if ($activeLoan) {
                $equipments = $this->equipmentModel->getAvailableEquipments();
                
                $this->view('loan/create', [
                    'error' => 'El equipo ya está prestado',
                    'hab_rfid' => $habRfid,
                    'equip_rfid' => $equipRfid,
                    'equipments' => $equipments
                ]);
                return;
            }
            
            if ($this->loanModel->create($habRfid, $equipRfid, true)) {
                $this->redirect('Loan');
            } else {
                $equipments = $this->equipmentModel->getAvailableEquipments();
                
                $this->view('loan/create', [
                    'error' => 'Error al crear el préstamo',
                    'hab_rfid' => $habRfid,
                    'equip_rfid' => $equipRfid,
                    'equipments' => $equipments
                ]);
            }
        } else {
            $equipments = $this->equipmentModel->getAvailableEquipments();
            
            $this->view('loan/create', [
                'equipments' => $equipments
            ]);
        }
    }
    
    public function return($equipRfid = null) {
        if ($_POST) {
            $habRfid = $this->sanitize($_POST['hab_rfid']);
            $equipRfid = $this->sanitize($_POST['equip_rfid']);
            
            if ($this->loanModel->returnLoan($habRfid, $equipRfid)) {
                $this->redirect('Loan');
            } else {
                $this->view('loan/return', [
                    'error' => 'Error al devolver el equipo'
                ]);
            }
        } else {
            $this->view('loan/return', [
                'equip_rfid' => $equipRfid
            ]);
        }
    }
    
    public function delete($id) {
        $this->loanModel->delete($id);
        $this->redirect('Loan/history');
    }
} 