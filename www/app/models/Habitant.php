<?php
class Habitant {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($name, $registration, $email, $cardId, $deviceId) {
        $sql = "INSERT INTO habintants (hab_name, hab_registration, hab_email, hab_card_id, hab_device_id) VALUES (?, ?, ?, ?, ?)";
        return $this->db->execute($sql, [$name, $registration, $email, $cardId, $deviceId]);
    }
    
    public function findByRegistration($registration) {
        $sql = "SELECT * FROM habintants WHERE hab_registration = ?";
        $result = $this->db->query($sql, [$registration]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM habintants WHERE hab_email = ?";
        $result = $this->db->query($sql, [$email]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM habintants WHERE hab_id = ?";
        $result = $this->db->query($sql, [$id]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function getAll() {
        $sql = "SELECT * FROM habintants ORDER BY hab_date DESC";
        return $this->db->query($sql);
    }
    
    public function getWithCards() {
        $sql = "SELECT * FROM cards_habs ORDER BY hab_name";
        return $this->db->query($sql);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM habintants WHERE hab_id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    public function update($id, $name, $registration, $email, $cardId, $deviceId) {
        $sql = "UPDATE habintants SET hab_name = ?, hab_registration = ?, hab_email = ?, hab_card_id = ?, hab_device_id = ? WHERE hab_id = ?";
        return $this->db->execute($sql, [$name, $registration, $email, $cardId, $deviceId, $id]);
    }
} 