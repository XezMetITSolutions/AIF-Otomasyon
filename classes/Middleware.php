<?php
/**
 * Middleware Sınıfı
 * Yetki ve erişim kontrolü için middleware'ler
 */

class Middleware
{
    private $auth;

    public function __construct()
    {
        $this->auth = new Auth();
    }

    /**
     * Giriş yapılmış mı kontrol et
     */
    public static function requireAuth()
    {
        $auth = new Auth();
        if (!$auth->checkAuth()) {
            header('Location: /index.php');
            exit;
        }
    }

    /**
     * Rol bazlı erişim kontrolü
     */
    public static function requireRole($allowedRoles)
    {
        self::requireAuth();
        $auth = new Auth();
        if (!$auth->checkRole($allowedRoles)) {
            header('Location: /access-denied.php');
            exit;
        }
    }

    public static function requireSuperAdmin()
    {
        self::requireAuth();
        $auth = new Auth();
        if (!$auth->isSuperAdmin()) {
            header('Location: /access-denied.php');
            exit;
        }
    }

    /**
     * Üye erişimi (Tüm yetkili roller)
     */
    public static function requireUye()
    {
        self::requireRole([Auth::ROLE_SUPER_ADMIN, Auth::ROLE_UYE]);
    }

    /**
     * BYK erişim kontrolü
     */
    public static function requireBykAccess($bykId)
    {
        self::requireAuth();
        $auth = new Auth();
        if (!$auth->checkBykAccess($bykId)) {
            header('Location: /access-denied.php');
            exit;
        }
    }

    /**
     * Modül bazlı erişim kontrolü
     */
    public static function requireModulePermission($moduleKey)
    {
        self::requireAuth();
        $auth = new Auth();
        if (!$auth->hasModulePermission($moduleKey)) {
            header('Location: /access-denied.php');
            exit;
        }
    }

    /**
     * CSRF token kontrolü
     */
    public static function verifyCSRF()
    {
        $appConfig = require __DIR__ . '/../config/app.php';
        $tokenName = $appConfig['security']['csrf_token_name'];

        if (!isset($_POST[$tokenName]) || !isset($_SESSION[$tokenName])) {
            return false;
        }

        if ($_POST[$tokenName] !== $_SESSION[$tokenName]) {
            return false;
        }

        return true;
    }

    /**
     * CSRF token oluştur
     */
    public static function generateCSRF()
    {
        $appConfig = require __DIR__ . '/../config/app.php';
        $tokenName = $appConfig['security']['csrf_token_name'];

        if (!isset($_SESSION[$tokenName])) {
            $_SESSION[$tokenName] = bin2hex(random_bytes(32));
        }

        return $_SESSION[$tokenName];
    }

    /**
     * Yetkisiz erişim hatası göster ve durdur
     */
    public static function forbidden($message = "Bu sayfayı görüntüleme yetkiniz yok.")
    {
        header('HTTP/1.1 403 Forbidden');
        if (file_exists(__DIR__ . '/../access-denied.php')) {
            $_SESSION['error_message'] = $message;
            include __DIR__ . '/../access-denied.php';
        } else {
            die($message);
        }
        exit;
    }
}

