<?php
/**
 * Kimlik Doğrulama ve Yetki Yönetimi Sınıfı
 * AIF Otomasyon Sistemi
 */

class Auth {
    private $db;
    private $modulePermissionsCacheUser = null;
    private $modulePermissionsCache = [];
    private $accountingHeadCache = []; // Cache for accounting head status
    
    // Rol sabitleri
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_UYE = 'uye';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Kullanıcı girişi
     */
    public function login($email, $password, $remember = false) {
        $user = $this->db->fetch(
            "SELECT u.*, r.rol_adi, r.rol_yetki_seviyesi 
             FROM kullanicilar u 
             INNER JOIN roller r ON u.rol_id = r.rol_id 
             WHERE u.email = ? AND u.aktif = 1",
            [$email]
        );
        
        if (!$user || !password_verify($password, $user['sifre'])) {
            return false;
        }
        
        // İlk giriş kontrolü - şifre değiştirme zorunluluğu
        if ($user['ilk_giris_zorunlu'] == 1) {
            $_SESSION['requires_password_change'] = true;
            $_SESSION['temp_user_id'] = $user['kullanici_id'];
            return 'password_change_required';
        }
        
        // Oturum bilgilerini kaydet
        $_SESSION['user_id'] = $user['kullanici_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['ad'] . ' ' . $user['soyad'];
        $_SESSION['user_role'] = $user['rol_adi'];
        $_SESSION['role_level'] = $user['rol_yetki_seviyesi'];
        $_SESSION['byk_id'] = $user['byk_id'] ?? null;
        $_SESSION['alt_birim_id'] = $user['alt_birim_id'] ?? null;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Son giriş zamanını güncelle
        $this->db->query(
            "UPDATE kullanicilar SET son_giris = NOW() WHERE kullanici_id = ?",
            [$user['kullanici_id']]
        );
        
        return true;
    }
    
    /**
     * Kullanıcı çıkışı
     */
    public function logout() {
        session_destroy();
        header('Location: /index.php');
        exit;
    }
    
    /**
     * Oturum kontrolü
     */
    public function checkAuth() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Oturum süresi kontrolü
        $appConfig = require __DIR__ . '/../config/app.php';
        $sessionLifetime = $appConfig['security']['session_lifetime'];
        
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $sessionLifetime) {
            $this->logout();
            return false;
        }
        
        // Oturum süresini yenile
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    /**
     * Rol kontrolü
     */
    public function checkRole($allowedRoles) {
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        
        if (!$this->checkAuth()) {
            return false;
        }
        
        return in_array($_SESSION['user_role'], $allowedRoles);
    }
    
    /**
     * Kullanıcı bilgilerini getir
     */
    public function getUser() {
        if (!$this->checkAuth()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
            'role_level' => $_SESSION['role_level'],
            'byk_id' => $_SESSION['byk_id'],
            'alt_birim_id' => $_SESSION['alt_birim_id']
        ];
    }
    
    /**
     * BYK kontrolü - Kullanıcı kendi BYK'sına erişebilir
     */
    public function checkBykAccess($bykId) {
        $user = $this->getUser();
        
        if ($user['role'] === self::ROLE_SUPER_ADMIN) {
            return true; // Ana yönetici tüm BYK'lara erişebilir
        }
        
        // Üyeler sadece kendi BYK'larına erişebilir
        return $user['byk_id'] == $bykId;
    }

    /**
     * Süper admin mi?
     */
    public function isSuperAdmin() {
        $user = $this->getUser();
        return $user && ($user['role'] === self::ROLE_SUPER_ADMIN || ($user['role_level'] ?? 0) >= 90);
    }

    /**
     * Üye mi?
     */
    public function isUye() {
        $user = $this->getUser();
        return $user && $user['role'] === self::ROLE_UYE;
    }

    /**
     * Kullanıcının Muhasebe Başkanı olup olmadığını kontrol et
     */
    public function isAccountingHead($userId) {
        if (isset($this->accountingHeadCache[$userId])) {
            return $this->accountingHeadCache[$userId];
        }

        try {
            $result = $this->db->fetch("SELECT count(*) as cnt FROM byk WHERE muhasebe_baskani_id = ?", [$userId]);
            $isHead = ($result && $result['cnt'] > 0);
            $this->accountingHeadCache[$userId] = $isHead;
            return $isHead;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Modül yetkisi kontrolü
     */
    public function hasModulePermission($moduleKey) {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        // Super admin tüm modüllere erişebilir
        if ($user['role'] === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        // Muhasebe Başkanı ise ilgili modüllere otomatik erişim
        if ($this->isAccountingHead($user['id']) && in_array($moduleKey, ['baskan_harcama_talepleri', 'baskan_iade_formlari'])) {
            return true;
        }

        $modules = $this->getModuleDefinitions();
        $moduleConfig = $modules[$moduleKey] ?? null;

        // Tanımsız modüllerde kısıtlama uygulama
        if (!$moduleConfig) {
            return true;
        }

        // Üyeler 'uye' kategorisindeki modüllere her zaman erişebilir
        if (($moduleConfig['category'] ?? '') === 'uye' && $user['role'] === self::ROLE_UYE) {
            return true;
        }

        // DB'deki yetkileri kontrol et
        $modulePermissions = $this->getCachedModulePermissions($user['id']);

        // Eğer veritabanında bu modül için explicit (açıkça) bir yetki tanımlanmışsa,
        // ROL KONTROLÜNDEN ÖNCE bunu dikkate al.
        if (array_key_exists($moduleKey, $modulePermissions)) {
            return (bool)$modulePermissions[$moduleKey];
        }

        // Eğer modül kategorisi 'baskan' ise ve özel yetki verilmemişse, 
        // varsayılan olarak ENGELLE. (Çünkü herkes 'uye' rolünde)
        if (($moduleConfig['category'] ?? '') === 'baskan') {
            return false;
        }

        $default = (bool)($moduleConfig['default'] ?? true);
        return $default;
    }

    private function getModuleDefinitions() {
        static $definitions = null;
        if ($definitions === null) {
            $configPath = __DIR__ . '/../config/baskan_modules.php';
            $definitions = file_exists($configPath) ? require $configPath : [];
        }
        return $definitions;
    }

    private function getCachedModulePermissions($userId) {
        if ($this->modulePermissionsCacheUser !== $userId) {
            $this->modulePermissionsCache = $this->loadModulePermissions($userId);
            $this->modulePermissionsCacheUser = $userId;
        }
        return $this->modulePermissionsCache;
    }

    private function loadModulePermissions($userId) {
        try {
            $rows = $this->db->fetchAll("
                SELECT module_key, can_view 
                FROM baskan_modul_yetkileri 
                WHERE kullanici_id = ?
            ", [$userId]);
        } catch (Exception $e) {
            // Tablo henüz oluşturulmadıysa varsayılanları kullan
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $result[$row['module_key']] = (bool)$row['can_view'];
        }
        return $result;
    }
    
    /**
     * Şifre değiştirme
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->db->fetch(
            "SELECT sifre FROM kullanicilar WHERE kullanici_id = ?",
            [$userId]
        );
        
        if (!password_verify($oldPassword, $user['sifre'])) {
            return false;
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->query(
            "UPDATE kullanicilar SET sifre = ?, ilk_giris_zorunlu = 0 WHERE kullanici_id = ?",
            [$hashedPassword, $userId]
        );
        
        return true;
    }
    
    /**
     * İlk giriş şifre değiştirme
     */
    public function changePasswordFirstLogin($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->query(
            "UPDATE kullanicilar SET sifre = ?, ilk_giris_zorunlu = 0 WHERE kullanici_id = ?",
            [$hashedPassword, $userId]
        );
        
        // Geçici oturumu gerçek oturuma dönüştür
        if (isset($_SESSION['temp_user_id']) && $_SESSION['temp_user_id'] == $userId) {
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['requires_password_change']);
            
            // Normal oturum oluştur
            $user = $this->db->fetch(
                "SELECT u.*, r.rol_adi, r.rol_yetki_seviyesi 
                 FROM kullanicilar u 
                 INNER JOIN roller r ON u.rol_id = r.rol_id 
                 WHERE u.kullanici_id = ?",
                [$userId]
            );
            
            $_SESSION['user_id'] = $user['kullanici_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['ad'] . ' ' . $user['soyad'];
            $_SESSION['user_role'] = $user['rol_adi'];
            $_SESSION['role_level'] = $user['rol_yetki_seviyesi'];
            $_SESSION['byk_id'] = $user['byk_id'] ?? null;
            $_SESSION['alt_birim_id'] = $user['alt_birim_id'] ?? null;
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
        }
        
        return true;
    }
}
