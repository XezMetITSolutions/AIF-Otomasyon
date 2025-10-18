<?php
// AIF Otomasyon - Kullanıcı Yönetimi ve Doğrulama
// Bu dosya login işlemlerini ve kullanıcı bilgilerini yönetir

require_once 'includes/byk_manager.php';
require_once 'includes/permission_manager.php';

class UserManager {
    private $users = [];
    
    public function __construct() {
        // Demo kullanıcıları oluştur
        $this->initializeUsers();
    }
    
    private function initializeUsers() {
        $this->users = [
            'superadmin' => [
                'username' => 'superadmin',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'email' => 'superadmin@aif.com',
                'role' => 'superadmin',
                'full_name' => 'Super Admin',
                'byk' => 'AT',
                'sub_unit' => 'Başkan',
                'status' => 'active',
                'created_at' => '2024-01-01 00:00:00',
                'last_login' => null
            ]
        ];
    }
    
    public function authenticate($username, $password) {
        if (!isset($this->users[$username])) {
            return false;
        }
        
        $user = $this->users[$username];
        
        // Kullanıcı aktif mi kontrol et
        if ($user['status'] !== 'active') {
            return false;
        }
        
        // Şifre doğrulama
        if (password_verify($password, $user['password'])) {
            // Son giriş tarihini güncelle
            $this->users[$username]['last_login'] = date('Y-m-d H:i:s');
            return $user;
        }
        
        return false;
    }
    
    public function getUser($username) {
        return isset($this->users[$username]) ? $this->users[$username] : null;
    }
    
    public function getAllUsers() {
        return $this->users;
    }
    
    public function createUser($userData) {
        $username = $userData['username'];
        
        if (isset($this->users[$username])) {
            return false; // Kullanıcı zaten var
        }
        
        $this->users[$username] = [
            'username' => $username,
            'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
            'email' => $userData['email'],
            'role' => $userData['role'] ?? 'user',
            'full_name' => $userData['full_name'],
            'status' => $userData['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null
        ];
        
        return true;
    }
    
    public function updateUser($username, $userData) {
        if (!isset($this->users[$username])) {
            return false;
        }
        
        foreach ($userData as $key => $value) {
            if ($key !== 'password' && isset($this->users[$username][$key])) {
                $this->users[$username][$key] = $value;
            }
        }
        
        if (isset($userData['password'])) {
            $this->users[$username]['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }
        
        return true;
    }
    
    public function deleteUser($username) {
        if (!isset($this->users[$username])) {
            return false;
        }
        
        unset($this->users[$username]);
        return true;
    }
    
    public function changePassword($username, $oldPassword, $newPassword) {
        if (!isset($this->users[$username])) {
            return false;
        }
        
        if (!password_verify($oldPassword, $this->users[$username]['password'])) {
            return false; // Eski şifre yanlış
        }
        
        $this->users[$username]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        return true;
    }
}

// Session yönetimi
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function login($user) {
        self::start();
        $_SESSION['user'] = $user;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
    }
    
    public static function logout() {
        self::start();
        session_destroy();
    }
    
    public static function isLoggedIn() {
        self::start();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public static function getCurrentUser() {
        self::start();
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ../index.php');
            exit();
        }
    }
    
    public static function requireRole($requiredRole) {
        self::requireLogin();
        $user = self::getCurrentUser();
        
        if (!$user || $user['role'] !== $requiredRole) {
            header('Location: ../index.php?error=unauthorized');
            exit();
        }
    }
}

// Login işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $userManager = new UserManager();
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['rememberMe']);
    
    $user = $userManager->authenticate($username, $password);
    
    if ($user) {
        SessionManager::login($user);
        
        // Remember me cookie
        if ($rememberMe) {
            setcookie('remembered_user', $username, time() + (30 * 24 * 60 * 60), '/'); // 30 gün
        }
        
        // JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Giriş başarılı!',
            'redirect' => 'admin/dashboard_superadmin.php'
        ]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Kullanıcı adı veya şifre hatalı!'
        ]);
        exit();
    }
}

// Logout işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    SessionManager::logout();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Çıkış yapıldı!',
        'redirect' => '../index.php'
    ]);
    exit();
}

// Kullanıcı bilgilerini getir
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_users') {
    $userManager = new UserManager();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'users' => $userManager->getAllUsers()
    ]);
    exit();
}
?>
