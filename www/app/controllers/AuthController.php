<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Device.php';

class AuthController extends Controller {
    private $userModel;
    private $deviceModel;

    // Rate limit: máximo de intentos fallidos antes de bloquear temporalmente.
    const LOGIN_MAX_ATTEMPTS = 5;
    const LOGIN_LOCKOUT_SECONDS = 300; // 5 min

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->deviceModel = new Device();
    }

    public function login() {
        $msg = "";
        $email = "";

        // Si el usuario ya está logueado, mandar a Dashboard.
        if (!empty($_SESSION['logged'])) {
            $this->redirect('Dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            if ($this->isLoginLocked()) {
                $msg = "Demasiados intentos fallidos. Intenta de nuevo en unos minutos.";
            } else {
                $email = strtolower(trim($_POST['email'] ?? ''));
                $password = $_POST['password'] ?? '';

                if (empty($email)) {
                    $msg = "Debe ingresar un email";
                } elseif (empty($password)) {
                    $msg = "Debe ingresar la clave";
                } elseif (!$this->validateEmail($email)) {
                    $msg = "Email inválido";
                } else {
                    $user = $this->userModel->authenticate($email, $password);

                    if ($user) {
                        $this->resetLoginAttempts();
                        // Regenerar id de sesión tras autenticación exitosa.
                        session_regenerate_id(true);

                        $_SESSION['logged']       = true;
                        $_SESSION['user_id']      = $user['users_id'];
                        $_SESSION['users_email']  = $user['users_email'];
                        $_SESSION['last_regeneration'] = time();

                        // Solo cargar dispositivos del usuario actual, no todos.
                        $devices = $this->deviceModel->findByUserId($user['users_id']);
                        $_SESSION['devices'] = $devices;

                        $this->redirect('Dashboard');
                    } else {
                        $this->bumpLoginAttempts();
                        $msg = "Acceso denegado";
                    }
                }
            }
        }

        $this->view('auth/login', [
            'msg'    => $msg,
            'email'  => $email,
            'csrf'   => self::csrfToken(),
        ]);
    }

    /**
     * Cierra sesión limpiando $_SESSION, expirando la cookie y destruyendo
     * el archivo de sesión. session_destroy() solo no invalida la cookie
     * y la sesión sigue siendo aceptada si la cookie no se borra del cliente.
     */
    public function logout() {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
        $this->redirect('Auth/login');
    }

    /**
     * Mantiene la sesión activa. NO debe regenerar id en cada llamada
     * (eso rompe la sesión si hay varias pestañas abiertas).
     */
    public function keepalive() {
        if (empty($_SESSION['logged'])) {
            $this->json([
                'success'  => false,
                'message'  => 'Sesión no válida',
                'redirect' => '/Auth/login',
            ]);
            return;
        }

        $_SESSION['last_activity'] = time();

        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 86400) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }

        $this->json([
            'success'   => true,
            'timestamp' => time(),
        ]);
    }

    /**
     * Registro de usuarios admin. Antes era público (cualquiera con la URL
     * podía crear cuentas administrativas). Ahora requiere estar autenticado.
     */
    public function register() {
        $this->requireAuth();

        $msg   = "";
        $email = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            $email           = strtolower(trim($_POST['email'] ?? ''));
            $password        = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($email)) {
                $msg = "Debe ingresar un email";
            } elseif (empty($password)) {
                $msg = "Debe ingresar la clave";
            } elseif (empty($confirmPassword)) {
                $msg = "Debe confirmar la clave";
            } elseif (!$this->validateEmail($email)) {
                $msg = "Email inválido";
            } elseif (strlen($password) < 8) {
                $msg = "La clave debe tener al menos 8 caracteres";
            } elseif ($password !== $confirmPassword) {
                $msg = "Las claves no coinciden";
            } elseif ($this->userModel->findByEmail($email)) {
                $msg = "El email ya está registrado";
            } else {
                if ($this->userModel->create($email, $password)) {
                    $this->redirect('Auth/login');
                } else {
                    $msg = "Error al registrar usuario";
                }
            }
        }

        $this->view('auth/register', [
            'msg'   => $msg,
            'email' => $email,
            'csrf'  => self::csrfToken(),
        ]);
    }

    // ----- Rate limit interno -----

    private function isLoginLocked() {
        $attempts = $_SESSION['_login_attempts'] ?? 0;
        $lockedUntil = $_SESSION['_login_locked_until'] ?? 0;
        if ($attempts >= self::LOGIN_MAX_ATTEMPTS && time() < $lockedUntil) {
            return true;
        }
        if (time() >= $lockedUntil) {
            // expiró el lockout, reset
            $_SESSION['_login_attempts'] = 0;
            $_SESSION['_login_locked_until'] = 0;
        }
        return false;
    }

    private function bumpLoginAttempts() {
        $_SESSION['_login_attempts'] = ($_SESSION['_login_attempts'] ?? 0) + 1;
        if ($_SESSION['_login_attempts'] >= self::LOGIN_MAX_ATTEMPTS) {
            $_SESSION['_login_locked_until'] = time() + self::LOGIN_LOCKOUT_SECONDS;
        }
    }

    private function resetLoginAttempts() {
        $_SESSION['_login_attempts'] = 0;
        $_SESSION['_login_locked_until'] = 0;
    }
}
