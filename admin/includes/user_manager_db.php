<?php
require_once 'database.php';

class UserManagerDB {
    
    /**
     * Tüm kullanıcıları getir
     */
    public static function getAllUsers($filters = []) {
        return DBHelper::getUsers($filters);
    }
    
    /**
     * Kullanıcı ID ile getir
     */
    public static function getUserById($id) {
        $db = Database::getInstance();
        
        return $db->fetchOne(
            "SELECT u.*, bc.name as byk_name, bc.code as byk_code, bc.color as byk_color,
                    bsu.name as sub_unit_name
             FROM users u
             LEFT JOIN byk_categories bc ON u.byk_category_id = bc.id
             LEFT JOIN byk_sub_units bsu ON u.sub_unit_id = bsu.id
             WHERE u.id = ?",
            [$id]
        );
    }
    
    /**
     * Kullanıcı adı ile getir
     */
    public static function getUserByUsername($username) {
        $db = Database::getInstance();
        
        return $db->fetchOne(
            "SELECT u.*, bc.name as byk_name, bc.code as byk_code, bc.color as byk_color,
                    bsu.name as sub_unit_name
             FROM users u
             LEFT JOIN byk_categories bc ON u.byk_category_id = bc.id
             LEFT JOIN byk_sub_units bsu ON u.sub_unit_id = bsu.id
             WHERE u.username = ?",
            [$username]
        );
    }
    
    /**
     * E-posta ile kullanıcı getir
     */
    public static function getUserByEmail($email) {
        $db = Database::getInstance();
        
        return $db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }
    
    /**
     * Yeni kullanıcı ekle
     */
    public static function addUser($data) {
        $db = Database::getInstance();
        
        // Şifreyi hashle
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        
        // Varsayılan değerler
        $data['status'] = $data['status'] ?? 'active';
        $data['role'] = $data['role'] ?? 'member';
        
        return $db->insert('users', $data);
    }
    
    /**
     * Kullanıcı güncelle
     */
    public static function updateUser($id, $data) {
        $db = Database::getInstance();
        
        // Şifre güncelleniyorsa hashle
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        
        return $db->update('users', $data, 'id = ?', [$id]);
    }
    
    /**
     * Kullanıcı sil
     */
    public static function deleteUser($id) {
        $db = Database::getInstance();
        
        return $db->delete('users', 'id = ?', [$id]);
    }
    
    /**
     * Kullanıcı şifresini doğrula
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Kullanıcı giriş yap
     */
    public static function login($username, $password) {
        $user = self::getUserByUsername($username);
        
        if (!$user) {
            return false;
        }
        
        if (!self::verifyPassword($password, $user['password_hash'])) {
            return false;
        }
        
        if ($user['status'] !== 'active') {
            return false;
        }
        
        // Son giriş zamanını güncelle
        self::updateLastLogin($user['id']);
        
        return $user;
    }
    
    /**
     * Son giriş zamanını güncelle
     */
    public static function updateLastLogin($userId) {
        $db = Database::getInstance();
        
        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
    }
    
    /**
     * Kullanıcı istatistikleri
     */
    public static function getUserStats() {
        $db = Database::getInstance();
        
        $stats = [];
        
        // Toplam kullanıcı sayısı
        $stats['total'] = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
        
        // Aktif kullanıcı sayısı
        $stats['active'] = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'];
        
        // Rol bazında sayılar
        $roleStats = $db->fetchAll("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        foreach ($roleStats as $role) {
            $stats['by_role'][$role['role']] = $role['count'];
        }
        
        // BYK bazında sayılar
        $bykStats = $db->fetchAll(
            "SELECT bc.code, bc.name, COUNT(u.id) as count 
             FROM byk_categories bc 
             LEFT JOIN users u ON bc.id = u.byk_category_id AND u.status = 'active'
             GROUP BY bc.id, bc.code, bc.name"
        );
        
        foreach ($bykStats as $byk) {
            $stats['by_byk'][$byk['code']] = [
                'name' => $byk['name'],
                'count' => $byk['count']
            ];
        }
        
        return $stats;
    }
    
    /**
     * Kullanıcı arama
     */
    public static function searchUsers($searchTerm, $filters = []) {
        $db = Database::getInstance();
        
        $sql = "SELECT u.*, bc.name as byk_name, bc.code as byk_code, bc.color as byk_color,
                       bsu.name as sub_unit_name
                FROM users u
                LEFT JOIN byk_categories bc ON u.byk_category_id = bc.id
                LEFT JOIN byk_sub_units bsu ON u.sub_unit_id = bsu.id
                WHERE (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
        
        $params = ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%"];
        
        if (!empty($filters['role'])) {
            $sql .= " AND u.role = ?";
            $params[] = $filters['role'];
        }
        
        if (!empty($filters['byk_category_id'])) {
            $sql .= " AND u.byk_category_id = ?";
            $params[] = $filters['byk_category_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY u.full_name";
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Kullanıcı yetkilerini getir
     */
    public static function getUserPermissions($userId) {
        return DBHelper::getUserPermissions($userId);
    }
    
    /**
     * Kullanıcı yetkisi ekle/güncelle
     */
    public static function setUserPermission($userId, $moduleId, $canRead = false, $canWrite = false, $canAdmin = false) {
        $db = Database::getInstance();
        
        $data = [
            'user_id' => $userId,
            'module_id' => $moduleId,
            'can_read' => $canRead,
            'can_write' => $canWrite,
            'can_admin' => $canAdmin
        ];
        
        // Önce mevcut yetkiyi kontrol et
        $existing = $db->fetchOne(
            "SELECT id FROM user_permissions WHERE user_id = ? AND module_id = ?",
            [$userId, $moduleId]
        );
        
        if ($existing) {
            return $db->update('user_permissions', $data, 'id = ?', [$existing['id']]);
        } else {
            return $db->insert('user_permissions', $data);
        }
    }
    
    /**
     * Kullanıcı yetkisini sil
     */
    public static function removeUserPermission($userId, $moduleId) {
        $db = Database::getInstance();
        
        return $db->delete('user_permissions', 'user_id = ? AND module_id = ?', [$userId, $moduleId]);
    }
    
    /**
     * Kullanıcının modül yetkisini kontrol et
     */
    public static function hasModulePermission($userId, $moduleName, $permissionType = 'read') {
        $db = Database::getInstance();
        
        $sql = "SELECT up.can_read, up.can_write, up.can_admin 
                FROM user_permissions up
                JOIN modules m ON up.module_id = m.id
                WHERE up.user_id = ? AND m.name = ?";
        
        $permission = $db->fetchOne($sql, [$userId, $moduleName]);
        
        if (!$permission) {
            return false;
        }
        
        switch ($permissionType) {
            case 'read':
                return $permission['can_read'];
            case 'write':
                return $permission['can_write'];
            case 'admin':
                return $permission['can_admin'];
            default:
                return false;
        }
    }
}
?>
