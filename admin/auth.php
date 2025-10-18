<?php
// AIF Otomasyon - Kullanıcı Yönetimi ve Doğrulama
// Basitleştirilmiş versiyon

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'includes/database.php';

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
    SessionManager::start();

    $response = ['success' => false, 'message' => 'Bilinmeyen hata.', 'redirect' => ''];

    try {
        switch ($_POST['action']) {
            case 'login':
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';

                if (empty($username) || empty($password)) {
                    $response['message'] = 'Kullanıcı adı ve şifre gerekli.';
                    break;
                }

                // Veritabanından kullanıcıyı getir
                $db = Database::getInstance();
                $user = $db->fetchOne("SELECT * FROM users WHERE username = ? AND status = 'active'", [$username]);

                if (!$user) {
                    $response['message'] = 'Kullanıcı bulunamadı veya aktif değil.';
                    break;
                }

                // Şifre kontrolü
                if (password_verify($password, $user['password_hash'])) {
                    // Son giriş zamanını güncelle
                    $db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                    
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
    } catch (Exception $e) {
        $response['message'] = 'Sunucu hatası: ' . $e->getMessage();
        error_log("Auth error: " . $e->getMessage());
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>