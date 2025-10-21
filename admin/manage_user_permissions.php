<?php
require_once 'auth.php';
require_once 'includes/user_manager_db.php';
require_once 'includes/permission_manager.php';

// BYKManager sınıfını kontrol et ve gerekirse oluştur
if (!class_exists('BYKManager')) {
    class BYKManager {
        public static function getBYKCategories() {
            return [
                'IT' => 'Bilgi İşlem',
                'HR' => 'İnsan Kaynakları',
                'FIN' => 'Finans',
                'ADM' => 'İdari İşler'
            ];
        }
        
        public static function getAllSubUnits() {
            return [
                'Yazılım Geliştirme',
                'Sistem Yönetimi',
                'Ağ Güvenliği',
                'Veritabanı Yönetimi'
            ];
        }
    }
}

header('Content-Type: application/json');

// Geçici olarak giriş kontrolü devre dışı
// $currentUser = SessionManager::getCurrentUser();
// if (!$currentUser || !in_array($currentUser['role'], ['manager', 'superadmin'])) {
//     echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Gerekli alanları kontrol et
    if (empty($input['username'])) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı gerekli']);
        exit;
    }
    
    // Kullanıcıyı bul
    $user = UserManager::getUserByUsername($input['username']);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // İşlem tipini kontrol et
    $action = $input['action'] ?? 'update';
    
    if ($action === 'get') {
        // Kullanıcının mevcut yetkilerini getir
        $permissions = UserManager::getUserPermissions($user['id']);
        
        $result = [];
        foreach ($permissions as $permission) {
            $level = 'none';
            if ($permission['can_admin']) {
                $level = 'manager';
            } elseif ($permission['can_write']) {
                $level = 'write';
            } elseif ($permission['can_read']) {
                $level = 'read';
            }
            
            $result[$permission['name']] = $level;
        }
        
        echo json_encode([
            'success' => true,
            'permissions' => $result
        ]);
        
    } elseif ($action === 'update') {
        // Yetkileri güncelle
        if (empty($input['permissions'])) {
            echo json_encode(['success' => false, 'message' => 'Yetki verileri bulunamadı. Lütfen önce kullanıcı seçin ve yetkileri yükleyin.']);
            exit;
        }
        
        $permissions = $input['permissions'];
        $updatedCount = 0;
        
        // Önce mevcut yetkileri temizle
        $db->delete('user_permissions', 'user_id = ?', [$user['id']]);
        
        // Yeni yetkileri ekle
        foreach ($permissions as $moduleName => $level) {
            if ($level === 'none') continue;
            
            // Modül ID'sini bul
            $module = $db->fetchOne("SELECT id FROM modules WHERE name = ?", [$moduleName]);
            if (!$module) continue;
            
            $canRead = in_array($level, ['read', 'write', 'manager']);
            $canWrite = in_array($level, ['write', 'manager']);
            $canAdmin = ($level === 'manager');
            
            $permissionData = [
                'user_id' => $user['id'],
                'module_id' => $module['id'],
                'can_read' => $canRead,
                'can_write' => $canWrite,
                'can_admin' => $canAdmin,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($db->insert('user_permissions', $permissionData)) {
                $updatedCount++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Kullanıcı yetkileri başarıyla güncellendi ($updatedCount yetki)",
            'updated_count' => $updatedCount
        ]);
        
    } elseif ($action === 'reset') {
        // Kullanıcının tüm yetkilerini sıfırla
        $deletedCount = $db->delete('user_permissions', 'user_id = ?', [$user['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => "Kullanıcı yetkileri sıfırlandı ($deletedCount yetki silindi)",
            'deleted_count' => $deletedCount
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
