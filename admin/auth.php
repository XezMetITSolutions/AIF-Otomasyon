<?php
// AIF Otomasyon - Kullanıcı Yönetimi ve Doğrulama
// Veritabanı tabanlı sistem

require_once 'config.php';
require_once 'includes/database.php';
require_once 'includes/user_manager_db.php';

class UserManager {
    
    public function authenticate($username, $password) {
        try {
            $user = UserManager::getUserByUsername($username);
            
            if (!$user) {
                return false;
            }
            
            // Kullanıcı aktif mi kontrol et
            if ($user['status'] !== 'active') {
                return false;
            }
            
            // Şifre doğrulama
            if (UserManager::verifyPassword($password, $user['password_hash'])) {
                // Son giriş tarihini güncelle
                UserManager::updateLastLogin($user['id']);
                return $user;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserByUsername($username) {
        return UserManager::getUserByUsername($username);
    }
    
    public function getAllUsers() {
        return UserManager::getAllUsers();
    }
}

// Session Manager
class SessionManager {
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function login($user) {
        self::start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['byk_category_id'] = $user['byk_category_id'];
        $_SESSION['sub_unit_id'] = $user['sub_unit_id'];
        $_SESSION['login_time'] = time();
    }
    
    public static function logout() {
        self::start();
        session_destroy();
    }
    
    public static function isLoggedIn() {
        self::start();
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }
    
    public static function getCurrentUser() {
        self::start();
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'full_name' => $_SESSION['full_name'],
            'byk_category_id' => $_SESSION['byk_category_id'],
            'sub_unit_id' => $_SESSION['sub_unit_id']
        ];
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ../index.php?error=login_required');
            exit();
        }
    }
    
    public static function requireRole($requiredRole) {
        self::requireLogin();
        $user = self::getCurrentUser();
        
        if ($user['role'] !== $requiredRole) {
            header('Location: ../index.php?error=insufficient_permissions');
            exit();
        }
    }
    
    public static function hasRole($role) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $user = self::getCurrentUser();
        return $user['role'] === $role;
    }
}

// Login işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $userManager = new UserManager();
        $user = $userManager->authenticate($username, $password);
        
        if ($user) {
            SessionManager::login($user);
            echo json_encode([
                'success' => true,
                'message' => 'Giriş başarılı',
                'redirect' => 'admin/dashboard_superadmin.php'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Kullanıcı adı veya şifre hatalı'
            ]);
        }
    } elseif ($_POST['action'] === 'logout') {
        SessionManager::logout();
        echo json_encode([
            'success' => true,
            'message' => 'Çıkış başarılı'
        ]);
    }
    
    exit();
}
?>