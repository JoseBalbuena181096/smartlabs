<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Device.php';

class AuthController extends Controller {
    private $userModel;
    private $deviceModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->deviceModel = new Device();
    }
    
    public function login() {
        $msg = "";
        $email = "";
        
        if ($_POST && isset($_POST['email']) && isset($_POST['password'])) {
            $email = $this->sanitize($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($email)) {
                $msg = "Debe ingresar un email";
            } elseif (empty($password)) {
                $msg = "Debe ingresar la clave";
            } elseif (!$this->validateEmail($email)) {
                $msg = "Email inválido";
            } else {
                $user = $this->userModel->authenticate($email, $password);
                
                if ($user) {
                    $_SESSION['logged'] = true;
                    $_SESSION['user_id'] = $user['users_id'];
                    $_SESSION['users_email'] = $user['users_email'];
                    
                    // Cargar dispositivos del usuario
                    $devices = $this->deviceModel->getAll();
                    $_SESSION['devices'] = $devices;
                    
                    $this->redirect('Dashboard');
                } else {
                    $msg = "Acceso denegado!!!";
                    $_SESSION['logged'] = false;
                }
            }
        }
        
        $this->view('auth/login', [
            'msg' => $msg,
            'email' => $email
        ]);
    }
    
    public function logout() {
        session_destroy();
        $this->redirect('Auth/login');
    }
    
    public function register() {
        $msg = "";
        $email = "";
        
        if ($_POST && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
            $email = $this->sanitize($_POST['email']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (empty($email)) {
                $msg = "Debe ingresar un email";
            } elseif (empty($password)) {
                $msg = "Debe ingresar la clave";
            } elseif (empty($confirmPassword)) {
                $msg = "Debe confirmar la clave";
            } elseif (!$this->validateEmail($email)) {
                $msg = "Email inválido";
            } elseif ($password !== $confirmPassword) {
                $msg = "Las claves no coinciden";
            } else {
                // Verificar si el email ya existe
                $existingUser = $this->userModel->findByEmail($email);
                if ($existingUser) {
                    $msg = "El email ya está registrado";
                } else {
                    if ($this->userModel->create($email, $password)) {
                        $msg = "Usuario registrado exitosamente";
                        $this->redirect('Auth/login');
                    } else {
                        $msg = "Error al registrar usuario";
                    }
                }
            }
        }
        
        $this->view('auth/register', [
            'msg' => $msg,
            'email' => $email
        ]);
    }
} 