<?php
class Loan {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($habRfid, $equipRfid, $state = true) {
        $sql = "INSERT INTO loans (loans_hab_rfid, loans_equip_rfid, loans_state) VALUES (?, ?, ?)";
        return $this->db->execute($sql, [$habRfid, $equipRfid, $state]);
    }
    
    public function getActiveLoans() {
        $sql = "SELECT * FROM habslab WHERE loans_state = 1 ORDER BY loans_date DESC";
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
    
    public function getActiveLoanByEquipment($equipRfid) {
        $sql = "SELECT * FROM habslab WHERE loans_equip_rfid = ? AND loans_state = 1 ORDER BY loans_date DESC LIMIT 1";
        $result = $this->db->query($sql, [$equipRfid]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function returnLoan($habRfid, $equipRfid) {
        $sql = "UPDATE loans SET loans_state = 0 WHERE loans_hab_rfid = ? AND loans_equip_rfid = ? AND loans_state = 1";
        return $this->db->execute($sql, [$habRfid, $equipRfid]);
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