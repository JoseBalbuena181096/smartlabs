<?php
class Device {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($alias, $serie, $userId) {
        $sql = "INSERT INTO devices (devices_alias, devices_serie, devices_user_id) VALUES (?, ?, ?)";
        return $this->db->execute($sql, [$alias, $serie, $userId]);
    }
    
    public function findByUserId($userId) {
        $sql = "SELECT * FROM devices WHERE devices_user_id = ? ORDER BY devices_date DESC";
        return $this->db->query($sql, [$userId]);
    }
    
    public function findBySerie($serie) {
        $sql = "SELECT * FROM devices WHERE devices_serie = ?";
        $result = $this->db->query($sql, [$serie]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM devices WHERE devices_id = ?";
        $result = $this->db->query($sql, [$id]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function getAll() {
        $sql = "SELECT * FROM devices ORDER BY devices_date DESC";
        return $this->db->query($sql);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM devices WHERE devices_id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    public function update($id, $alias, $serie) {
        $sql = "UPDATE devices SET devices_alias = ?, devices_serie = ? WHERE devices_id = ?";
        return $this->db->execute($sql, [$alias, $serie, $id]);
    }
} 