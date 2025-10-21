<?php
require_once 'includes/database.php';
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

try {
    $db = Database::getInstance();
    
    // Modüller tablosunu oluştur (eğer yoksa)
    $createModulesTable = "
        CREATE TABLE IF NOT EXISTS modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            display_name VARCHAR(100) NOT NULL,
            icon VARCHAR(50) NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->query($createModulesTable);
    
    // Kullanıcı yetkileri tablosunu oluştur (eğer yoksa)
    $createUserPermissionsTable = "
        CREATE TABLE IF NOT EXISTS user_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            module_id INT NOT NULL,
            can_read BOOLEAN DEFAULT FALSE,
            can_write BOOLEAN DEFAULT FALSE,
            can_admin BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_module (user_id, module_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->query($createUserPermissionsTable);
    
    // Modülleri ekle/güncelle
    $modules = PermissionManager::getModules();
    $insertedCount = 0;
    $updatedCount = 0;
    
    foreach ($modules as $moduleKey => $moduleData) {
        // Modülün var olup olmadığını kontrol et
        $existingModule = $db->fetchOne(
            "SELECT id FROM modules WHERE name = ?", 
            [$moduleKey]
        );
        
        $moduleInfo = [
            'name' => $moduleKey,
            'display_name' => $moduleData['name'],
            'icon' => $moduleData['icon'],
            'description' => $moduleData['description'],
            'is_active' => true,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($existingModule) {
            // Modülü güncelle
            $db->update('modules', $moduleInfo, 'id = ?', [$existingModule['id']]);
            $updatedCount++;
        } else {
            // Yeni modül ekle
            $moduleInfo['created_at'] = date('Y-m-d H:i:s');
            $db->insert('modules', $moduleInfo);
            $insertedCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Modüller başarıyla yüklendi",
        'inserted_count' => $insertedCount,
        'updated_count' => $updatedCount,
        'total_modules' => count($modules)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
