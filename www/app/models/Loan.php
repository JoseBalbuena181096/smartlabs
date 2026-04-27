<?php
class Loan {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Inserta un nuevo registro de préstamo.
     * El sistema es append-only: cada préstamo es state=1, cada devolución es state=0,
     * el estado real de un equipo es el del último registro por loans_date.
     */
    public function create($habRfid, $equipRfid, $state = 1) {
        $sql = "INSERT INTO loans (loans_hab_rfid, loans_equip_rfid, loans_state) VALUES (?, ?, ?)";
        return $this->db->execute($sql, [$habRfid, $equipRfid, (int)$state]);
    }

    /**
     * Equipos cuyo último movimiento es un préstamo (state=1) — los realmente activos.
     * La subquery selecciona el loans_date más reciente por par (equipo, usuario)
     * y se queda solo con los que terminan en state=1.
     */
    public function getActiveLoans() {
        $sql = "SELECT h.* FROM habslab h
                INNER JOIN (
                    SELECT loans_equip_rfid, loans_hab_rfid, MAX(loans_date) AS max_date
                    FROM habslab
                    GROUP BY loans_equip_rfid, loans_hab_rfid
                ) last ON h.loans_equip_rfid = last.loans_equip_rfid
                       AND h.loans_hab_rfid  = last.loans_hab_rfid
                       AND h.loans_date      = last.max_date
                WHERE h.loans_state = 1
                ORDER BY h.loans_date DESC";
        return $this->db->query($sql);
    }

    public function getAllLoans() {
        $sql = "SELECT * FROM habslab ORDER BY loans_date DESC";
        return $this->db->query($sql);
    }

    public function getLoansByEquipment($equipRfid) {
        $sql = "SELECT * FROM habslab WHERE loans_equip_rfid = ? ORDER BY loans_date DESC";
        return $this->db->query($sql, [$equipRfid]);
    }

    public function getLoansByHabitant($habRfid) {
        $sql = "SELECT * FROM habslab WHERE loans_hab_rfid = ? ORDER BY loans_date DESC";
        return $this->db->query($sql, [$habRfid]);
    }

    /**
     * Devuelve el préstamo activo de un equipo, o null si su último movimiento fue devolución.
     * Antes filtraba directamente por loans_state=1 sin verificar si después hubo state=0,
     * por lo que reportaba como activo un equipo ya devuelto.
     */
    public function getActiveLoanByEquipment($equipRfid) {
        $sql = "SELECT * FROM habslab
                WHERE loans_equip_rfid = ?
                ORDER BY loans_date DESC
                LIMIT 1";
        $result = $this->db->query($sql, [$equipRfid]);
        if (empty($result)) {
            return null;
        }
        return ((int)$result[0]['loans_state'] === 1) ? $result[0] : null;
    }

    /**
     * Registra una devolución como un nuevo INSERT con state=0.
     * Antes hacía UPDATE, lo que era incompatible con el patrón append-only que
     * usan LoanAdminController, las vistas y el flujo MQTT del backend.
     * Solo inserta si efectivamente hay un préstamo activo para ese par usuario+equipo.
     */
    public function returnLoan($habRfid, $equipRfid) {
        $active = $this->getActiveLoanByEquipment($equipRfid);
        if (!$active || $active['loans_hab_rfid'] !== $habRfid) {
            return false;
        }
        return $this->create($habRfid, $equipRfid, 0);
    }

    public function delete($id) {
        $sql = "DELETE FROM loans WHERE loans_id = ?";
        return $this->db->execute($sql, [$id]);
    }

    public function findById($id) {
        $sql = "SELECT * FROM loans WHERE loans_id = ?";
        $result = $this->db->query($sql, [$id]);
        return !empty($result) ? $result[0] : null;
    }
}
