<?php
class Traffic {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($habId, $device, $state) {
        $sql = "INSERT INTO traffic (traffic_hab_id, traffic_device, traffic_state) VALUES (?, ?, ?)";
        return $this->db->execute($sql, [$habId, $device, $state]);
    }
    
    public function getByDevice($device, $limit = 12) {
        $sql = "SELECT * FROM traffic_devices WHERE traffic_device = ? ORDER BY traffic_date DESC LIMIT ?";
        return $this->db->query($sql, [$device, $limit]);
    }
    
    public function getLastEntryByDevice($device) {
        $sql = "SELECT * FROM traffic WHERE traffic_device = ? ORDER BY traffic_date DESC LIMIT 1";
        $result = $this->db->query($sql, [$device]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function getLastEntryByDeviceAndHab($device, $habId) {
        $sql = "SELECT * FROM traffic WHERE traffic_hab_id = ? AND traffic_device = ? ORDER BY traffic_date DESC LIMIT 1";
        $result = $this->db->query($sql, [$habId, $device]);
        return !empty($result) ? $result[0] : null;
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT * FROM traffic_devices ORDER BY traffic_date DESC";
        return $this->db->query($sql);
    }
    
    public function getStatsByDevice($device) {
        $sql = "SELECT 
                    DATE(traffic_date) as date,
                    COUNT(*) as total_access,
                    SUM(CASE WHEN traffic_state = 1 THEN 1 ELSE 0 END) as entries,
                    SUM(CASE WHEN traffic_state = 0 THEN 1 ELSE 0 END) as exits
                FROM traffic 
                WHERE traffic_device = ? 
                GROUP BY DATE(traffic_date) 
                ORDER BY date DESC 
                LIMIT 30";
        return $this->db->query($sql, [$device]);
    }
    
    public function getHoursUsage($device) {
        $sql = "SELECT 
                    hab_name,
                    COUNT(*) as total_access,
                    MIN(traffic_date) as first_access,
                    MAX(traffic_date) as last_access
                FROM traffic_devices 
                WHERE traffic_device = ? 
                GROUP BY hab_name, traffic_hab_id 
                ORDER BY total_access DESC";
        return $this->db->query($sql, [$device]);
    }
} 