<?php
class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crea un usuario. Hashea con bcrypt (password_hash).
     */
    public function create($email, $password) {
        $email = strtolower(trim($email));
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (users_email, users_password) VALUES (?, ?)";
        return $this->db->execute($sql, [$email, $hashedPassword]);
    }

    public function findByEmail($email) {
        $email = strtolower(trim($email));
        $sql = "SELECT * FROM users WHERE LOWER(users_email) = ?";
        $result = $this->db->query($sql, [$email]);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Verifica credenciales. Soporta bcrypt y SHA1 legacy:
     *   - Si el hash ya es bcrypt → password_verify.
     *   - Si es SHA1 (40 chars hex) → comparar timing-safe; si match,
     *     rehashear a bcrypt automáticamente y persistir.
     * Email se trata case-insensitive.
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        $stored = $user['users_password'] ?? '';

        // bcrypt nuevo
        if (password_verify($password, $stored)) {
            // rehash si el cost subió o el algoritmo cambió
            if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                $this->updatePassword($user['users_id'], $password);
            }
            return $user;
        }

        // SHA1 legacy (40 chars hex)
        if (strlen($stored) === 40 && ctype_xdigit($stored)) {
            $sha1 = sha1($password);
            if (hash_equals($stored, $sha1)) {
                // Migrar a bcrypt en el siguiente paso para no quedarse con SHA1
                $this->updatePassword($user['users_id'], $password);
                return $user;
            }
        }

        return null;
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
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET users_password = ? WHERE users_id = ?";
        return $this->db->execute($sql, [$hashedPassword, $id]);
    }
}
