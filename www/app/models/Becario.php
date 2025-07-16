<?php
require_once __DIR__ . '/../core/Database.php';

class Becario {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getBecarios($device, $startDate, $endDate, $matricula = null) {
        $sql = "SELECT * FROM traffic_devices WHERE traffic_device = ? AND traffic_date BETWEEN ? AND ?";
        $params = [$device, $startDate, $endDate];

        if ($matricula && !empty(trim($matricula))) {
            $sql .= " AND hab_registration = ?";
            $params[] = $matricula;
        }

        $sql .= " ORDER BY traffic_date ASC";

        return $this->db->query($sql, $params);
    }
}
