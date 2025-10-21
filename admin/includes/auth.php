<?php
/**
 * Session Management Class
 * Kullanıcı oturum yönetimi ve yetkilendirme
 */

class SessionManager {
    
    /**
     * Oturum başlat
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Kullanıcı giriş yapmış mı?
     */
    public static function isLoggedIn() {
        self::start();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Mevcut kullanıcı bilgilerini al
     */
    public static function getCurrentUser() {
        self::start();
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'email' => $_SESSION['email'] ?? '',
                'first_name' => $_SESSION['first_name'] ?? '',
                'last_name' => $_SESSION['last_name'] ?? ''
            ];
        }
        return null;
    }
    
    /**
     * Giriş yapmış kullanıcı gerektir
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ../index.php');
            exit;
        }
    }
    
    /**
     * Belirli rol gerektir
     */
    public static function requireRole($allowedRoles) {
        self::requireLogin();
        $user = self::getCurrentUser();
        
        if (!$user || !in_array($user['role'], $allowedRoles)) {
            header('Location: ../index.php');
            exit;
        }
    }
    
    /**
     * Çıkış yap
     */
    public static function logout() {
        self::start();
        session_destroy();
        header('Location: ../index.php');
        exit;
    }
    
    /**
     * Rol bazında yönlendirme
     */
    public static function redirectBasedOnRole() {
        $user = self::getCurrentUser();
        if ($user) {
            switch ($user['role']) {
                case 'superadmin':
                    header('Location: dashboard_superadmin.php');
                    break;
                case 'manager':
                    header('Location: dashboard_manager.php');
                    break;
                case 'member':
                    header('Location: dashboard_member.php');
                    break;
                default:
                    header('Location: ../index.php');
            }
            exit;
        }
    }
}
?>
