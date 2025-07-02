<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($email, $password) {
        $hashedPassword = sha1($password);
        $sql = "INSERT INTO users (users_email, users_password) VALUES (?, ?)";
        return $this->db->execute($sql, [$email, $hashedPassword]);
    }
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE users_email = ?";
        $result = $this->db->query($sql, [$email]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function authenticate($email, $password) {
        $hashedPassword = sha1($password);
        $sql = "SELECT * FROM users WHERE users_email = ? AND users_password = ?";
        $result = $this->db->query($sql, [$email, $hashedPassword]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE users_id = ?";
        $result = $this->db->query($sql, [$id]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function getAll() {
        $sql = "SELECT * FROM users ORDER BY users_date DESC";
        return $this->db->query($sql);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM users WHERE users_id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    public function updatePassword($id, $newPassword) {
        $hashedPassword = sha1($newPassword);
        $sql = "UPDATE users SET users_password = ? WHERE users_id = ?";
        return $this->db->execute($sql, [$hashedPassword, $id]);
    }
} 