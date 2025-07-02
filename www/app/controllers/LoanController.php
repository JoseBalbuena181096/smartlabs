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
        // Manejar petición AJAX de consulta de préstamos
        if ($_POST && isset($_POST['consult_loan'])) {
            $consultLoan = $this->sanitize($_POST['consult_loan']);
            
            if (!empty($consultLoan)) {
                echo $this->consultarPrestamos($consultLoan);
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
            $output .= "<br>";
            $output .= '<h4 class="h4">Prestamos del usuario: </h4>';
            $output .= "<br>";
            $output .= '<h3 class="h3">' . htmlspecialchars($row_name["hab_name"]) . "</h3>";
            $output .= "<br>";
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
        
        // Generar tabla de préstamos
        if ($filtered_loans) {
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
                    $check_result = $this->db->query("SELECT * FROM `habslab` WHERE 
                                     `equipments_rfid` = ? AND 
                                     `loans_state` = '0' AND 
                                     `loans_date` > ? 
                                     ORDER BY `loans_date` DESC LIMIT 1", [$equip_rfid, $loan_date]);
                    
                    // Si no hay devolución más reciente, mostrar como prestado
                    if (empty($check_result)) {
                        $output .= '<tr>';
                        $output .= "<td>" . htmlspecialchars($loan["loans_date"]) . "</td>";
                        $output .= "<td>" . htmlspecialchars($loan["equipments_name"]) . "</td>";
                        $output .= "<td>" . htmlspecialchars($loan["equipments_brand"]) . "</td>";
                        $output .= "<td>Prestado</td>";
                        $output .= '</tr>';
                    }
                }
            }
            
            $output .= '</tbody>';
            $output .= '</table>';
        } else {
            $output .= '<div class="alert alert-info">No se encontraron préstamos para esta tarjeta.</div>';
        }
        
        return $output;
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