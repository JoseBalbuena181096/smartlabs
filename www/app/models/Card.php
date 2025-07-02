<?php
class Card {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($number, $assigned = false) {
        $sql = "INSERT INTO cards (cards_number, cards_assigned) VALUES (?, ?)";
        return $this->db->execute($sql, [$number, $assigned]);
    }
    
    public function findByNumber($number) {
        $sql = "SELECT * FROM cards WHERE cards_number = ?";
        $result = $this->db->query($sql, [$number]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM cards WHERE cards_id = ?";
        $result = $this->db->query($sql, [$id]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function getAll() {
        $sql = "SELECT * FROM cards ORDER BY cards_date DESC";
        return $this->db->query($sql);
    }
    
    public function getAvailable() {
        $sql = "SELECT * FROM cards WHERE cards_assigned = 0 ORDER BY cards_date DESC";
        return $this->db->query($sql);
    }
    
    public function getAssigned() {
        $sql = "SELECT * FROM cards WHERE cards_assigned = 1 ORDER BY cards_date DESC";
        return $this->db->query($sql);
    }
    
    public function assign($id) {
        $sql = "UPDATE cards SET cards_assigned = 1 WHERE cards_id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    public function unassign($id) {
        $sql = "UPDATE cards SET cards_assigned = 0 WHERE cards_id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM cards WHERE cards_id = ?";
        return $this->db->execute($sql, [$id]);
    }
} 