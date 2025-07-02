<?php
class Equipment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($name, $rfid, $brand) {
        $sql = "INSERT INTO equipments (equipments_name, equipments_rfid, equipments_brand) VALUES (?, ?, ?)";
        return $this->db->execute($sql, [$name, $rfid, $brand]);
    }
    
    public function findByRfid($rfid) {
        $sql = "SELECT * FROM equipments WHERE equipments_rfid = ?";
        $result = $this->db->query($sql, [$rfid]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM equipments WHERE equipments_id = ?";
        $result = $this->db->query($sql, [$id]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function getAll() {
        $sql = "SELECT * FROM equipments ORDER BY equipments_name";
        return $this->db->query($sql);
    }
    
    public function searchByName($name) {
        $sql = "SELECT * FROM equipments WHERE equipments_name LIKE ? ORDER BY equipments_name";
        return $this->db->query($sql, ["%{$name}%"]);
    }
    
    public function searchByBrand($brand) {
        $sql = "SELECT * FROM equipments WHERE equipments_brand LIKE ? ORDER BY equipments_name";
        return $this->db->query($sql, ["%{$brand}%"]);
    }
    
    public function update($id, $name, $rfid, $brand) {
        $sql = "UPDATE equipments SET equipments_name = ?, equipments_rfid = ?, equipments_brand = ? WHERE equipments_id = ?";
        return $this->db->execute($sql, [$name, $rfid, $brand, $id]);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM equipments WHERE equipments_id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    public function getAvailableEquipments() {
        $sql = "SELECT e.*, 
                       CASE WHEN l.loans_id IS NULL THEN 'Disponible' ELSE 'Prestado' END as status
                FROM equipments e
                LEFT JOIN loans l ON e.equipments_rfid = l.loans_equip_rfid AND l.loans_state = 1
                ORDER BY e.equipments_name";
        return $this->db->query($sql);
    }
} 