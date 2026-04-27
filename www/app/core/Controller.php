<?php
class Controller {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->checkSession();
    }

    /**
     * Inicia/configura la sesión PHP. Lee parámetros desde config/session.php
     * (ya configura ini_set globalmente) y monta cookies httponly + samesite.
     * Regenera el id de sesión cada 24 h por seguridad.
     */
    protected function checkSession() {
        if (session_status() != PHP_SESSION_NONE) {
            return;
        }

        $sessionConfig = require __DIR__ . '/../../config/session.php';

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => !empty($sessionConfig['cookie_secure']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 86400) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    protected function requireAuth() {
        if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
            $this->redirect('Auth/login');
        }
    }

    protected function view($viewName, $data = []) {
        extract($data);
        $viewFile = __DIR__ . "/../views/{$viewName}.php";
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            die("Vista no encontrada: {$viewName}");
        }
    }

    protected function redirect($url) {
        header("Location: /{$url}");
        exit();
    }

    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * Limpia el input para guardar en BD: quita tags y normaliza whitespace.
     * NO aplica htmlspecialchars: el escape para HTML va en la vista (en
     * output, no en input), si no la BD se llena de entidades HTML y produce
     * doble encoding cuando la vista vuelve a escapar.
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        if (!is_string($data)) {
            return $data;
        }
        return trim(strip_tags($data));
    }

    /**
     * Helper para escapar valores en output (vistas).
     * Uso: <?= Controller::e($var) ?>
     */
    public static function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Hashea un password con bcrypt (PASSWORD_DEFAULT). Reemplaza el SHA1
     * sin sal anterior. La verificación va por User::authenticate, que tiene
     * compatibilidad con hashes SHA1 viejos (rehash perezoso al login).
     */
    protected function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    // ----- CSRF -----

    /**
     * Devuelve un token CSRF para esta sesión (lo crea si no existe).
     * Embeber en cada form: <input type="hidden" name="_csrf" value="<?= Controller::e($csrf) ?>">
     */
    public static function csrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Valida que el _csrf del POST coincida con el de la sesión. Si no,
     * responde 403 y termina la request. Llamar al inicio de cada acción
     * que reciba POST sensible.
     */
    protected function verifyCsrf() {
        $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['_csrf_token'] ?? '';
        if (!$expected || !is_string($sent) || !hash_equals($expected, $sent)) {
            http_response_code(403);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'CSRF inválido']);
            } else {
                echo '<h1>403 - CSRF inválido</h1>';
            }
            exit();
        }
    }

    private function isJsonRequest() {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'application/json') !== false;
    }
}
