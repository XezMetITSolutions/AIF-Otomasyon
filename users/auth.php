<?php
session_start();

class SessionManager {
    public static function isLoggedIn(): bool {
        return isset($_SESSION['username']) && !empty($_SESSION['username']);
    }

    public static function getCurrentUser(): ?array {
        if (!self::isLoggedIn()) {
            return null;
        }

        try {
            require_once 'includes/database.php';
            $db = Database::getInstance();
            
            $user = $db->fetchOne(
                "SELECT u.*, b.name as byk_name, b.code as byk_code, b.color as byk_color, 
                        su.name as sub_unit_name
                 FROM users u 
                 LEFT JOIN byk_categories b ON u.byk_category_id = b.id 
                 LEFT JOIN sub_units su ON u.sub_unit_id = su.id 
                 WHERE u.username = ? AND u.status = 'active'",
                [$_SESSION['username']]
            );
            
            return $user ?: null;
        } catch (Exception $e) {
            error_log("SessionManager getCurrentUser error: " . $e->getMessage());
            return null;
        }
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ../index.php');
            exit;
        }
        
        return true;
    }

    public static function requireRole($allowedRoles) {
        self::requireLogin();
        
        $user = self::getCurrentUser();
        if (!$user || !in_array($user['role'], $allowedRoles)) {
            header('Location: ../index.php');
            exit;
        }
        
        return true;
    }

    public static function logout() {
        session_destroy();
        header('Location: ../index.php');
        exit;
    }

    public static function redirectBasedOnRole() {
        $user = self::getCurrentUser();
        if (!$user) {
            header('Location: ../index.php');
            exit;
        }
        
        if ($user['role'] === 'superadmin' || $user['role'] === 'manager') {
            header('Location: ../admin/dashboard_superadmin.php');
        } else {
            header('Location: dashboard_member.php');
        }
        exit;
    }
}
?>