<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $config = require_once __DIR__ . '/../../config/database.php';

        // Reporta errores de mysqli como excepciones para que los try/catch funcionen
        // y para no morir con die() filtrando el SQL al usuario final.
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->connection = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            $config['port']
        );

        $this->connection->set_charset($config['charset']);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Asegura que la conexión esté viva. MySQL/MariaDB cierra el socket tras
     * `wait_timeout` (default 8h). Si el ping falla, reabre la conexión.
     */
    private function ensureAlive() {
        if (!$this->connection || !$this->connection->ping()) {
            $config = require __DIR__ . '/../../config/database.php';
            $this->connection = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database'],
                $config['port']
            );
            $this->connection->set_charset($config['charset']);
        }
    }

    private function bindAndExecute($sql, $params) {
        $this->ensureAlive();
        $stmt = $this->connection->prepare($sql);

        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param) || is_bool($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            // bool → int para que bind_param ('i') reciba un valor numérico real.
            $bound = array_map(static function ($v) {
                return is_bool($v) ? (int)$v : $v;
            }, $params);
            $stmt->bind_param($types, ...$bound);
        }

        $stmt->execute();
        return $stmt;
    }

    public function query($sql, $params = []) {
        $stmt = $this->bindAndExecute($sql, $params);
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function execute($sql, $params = []) {
        $this->bindAndExecute($sql, $params);
        return true;
    }
    
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
} 