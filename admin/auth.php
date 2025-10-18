<?php
// AIF Otomasyon - Kullanıcı Yönetimi ve Doğrulama
// Veritabanı tabanlı sistem

require_once 'config.php';
require_once 'includes/database.php';
require_once 'includes/user_manager_db.php';
require_once 'includes/permission_manager.php'; // PermissionManager'ı da dahil et

class SessionManager {
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login($user) {
        self::start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['byk'] = $user['byk'] ?? null;
        $_SESSION['sub_unit'] = $user['sub_unit'] ?? null;
        $_SESSION['last_login'] = $user['last_login'] ?? null;
        $_SESSION['login_time'] = time();
    }

    public static function logout() {
        self::start();
        session_unset();
        session_destroy();
    }

    public static function isLoggedIn() {
        self::start();
        return isset($_SESSION['user_id']);
    }

    public static function getCurrentUser() {
        self::start();
        return self::isLoggedIn() ? $_SESSION : null;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ../index.php?error=not_logged_in');
            exit();
        }
    }

    public static function requireRole($requiredRole) {
        self::requireLogin();
        $currentUser = self::getCurrentUser();
        if ($currentUser['role'] !== $requiredRole) {
            header('Location: ../index.php?error=no_permission');
            exit();
        }
    }
}

// AJAX isteği kontrolü
if (isset($_POST['action'])) {
    SessionManager::start(); // AJAX istekleri için session'ı başlat

    $response = ['success' => false, 'message' => 'Bilinmeyen hata.', 'redirect' => ''];

    switch ($_POST['action']) {
        case 'login':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $rememberMe = isset($_POST['rememberMe']) ? true : false;

            $userManager = new UserManagerDB(); // Veritabanı tabanlı UserManager kullan
            $user = $userManager->authenticate($username, $password);

            if ($user) {
                SessionManager::login($user);
                $response['success'] = true;
                $response['message'] = 'Giriş başarılı! Yönlendiriliyorsunuz...';
                $response['redirect'] = ($user['role'] === 'superadmin') ? 'admin/dashboard_superadmin.php' : 'admin/users/dashboard_member.php';
            } else {
                $response['message'] = 'Kullanıcı adı veya şifre hatalı.';
            }
            break;

        case 'logout':
            SessionManager::logout();
            $response['success'] = true;
            $response['message'] = 'Çıkış yapıldı.';
            $response['redirect'] = '../index.php';
            break;
    }
    echo json_encode($response);
    exit();
}
?>