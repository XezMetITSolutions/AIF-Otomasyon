<?php
/**
 * AIF Otomasyon - Modül ve Yetki Sistemi Kurulum Scripti
 * Bu script modülleri veritabanına yükler ve gerekli tabloları oluşturur
 */

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

echo "<h2>AIF Otomasyon - Modül ve Yetki Sistemi Kurulumu</h2>";

try {
    $db = Database::getInstance();
    
    echo "<h3>1. Veritabanı Tabloları Oluşturuluyor...</h3>";
    
    // Modüller tablosunu oluştur
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
    echo "✓ modules tablosu oluşturuldu<br>";
    
    // Eğer tablo zaten varsa eksik kolonları ekle
    try {
        // Önce kolonun var olup olmadığını kontrol et
        $columns = $db->fetchAll("SHOW COLUMNS FROM modules LIKE 'is_active'");
        if (empty($columns)) {
            $db->query("ALTER TABLE modules ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
            echo "✓ is_active kolonu eklendi<br>";
        } else {
            echo "ℹ is_active kolonu zaten mevcut<br>";
        }
    } catch (Exception $e) {
        echo "ℹ is_active kolonu kontrolü: " . $e->getMessage() . "<br>";
    }
    
    // Kullanıcı yetkileri tablosunu oluştur
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
    echo "✓ user_permissions tablosu oluşturuldu<br>";
    
    // Eğer tablo zaten varsa eksik kolonları ekle
    $permissionColumns = ['can_read', 'can_write', 'can_admin'];
    foreach ($permissionColumns as $column) {
        try {
            $columns = $db->fetchAll("SHOW COLUMNS FROM user_permissions LIKE '$column'");
            if (empty($columns)) {
                $db->query("ALTER TABLE user_permissions ADD COLUMN $column BOOLEAN DEFAULT FALSE");
                echo "✓ $column kolonu eklendi<br>";
            } else {
                echo "ℹ $column kolonu zaten mevcut<br>";
            }
        } catch (Exception $e) {
            echo "ℹ $column kolonu kontrolü: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3>2. Modüller Yükleniyor...</h3>";
    
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
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // is_active kolonu varsa ekle
        try {
            $columns = $db->fetchAll("SHOW COLUMNS FROM modules LIKE 'is_active'");
            if (!empty($columns)) {
                $moduleInfo['is_active'] = true;
            }
        } catch (Exception $e) {
            // is_active kolonu yoksa atla
        }
        
        if ($existingModule) {
            // Modülü güncelle
            $db->update('modules', $moduleInfo, 'id = ?', [$existingModule['id']]);
            $updatedCount++;
            echo "✓ Modül güncellendi: {$moduleData['name']}<br>";
        } else {
            // Yeni modül ekle
            $moduleInfo['created_at'] = date('Y-m-d H:i:s');
            $db->insert('modules', $moduleInfo);
            $insertedCount++;
            echo "✓ Yeni modül eklendi: {$moduleData['name']}<br>";
        }
    }
    
    echo "<h3>3. Kurulum Tamamlandı!</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Özet:</strong><br>";
    echo "• Toplam modül sayısı: " . count($modules) . "<br>";
    echo "• Yeni eklenen modül: $insertedCount<br>";
    echo "• Güncellenen modül: $updatedCount<br>";
    echo "</div>";
    
    echo "<h3>4. Sonraki Adımlar:</h3>";
    echo "<ol>";
    echo "<li><a href='permissions.php'>Yetki Yönetimi</a> sayfasına gidin</li>";
    echo "<li>Kullanıcılara modül bazında yetki atayın</li>";
    echo "<li>Sidebar'ı güncelleyin (includes/sidebar.php)</li>";
    echo "</ol>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Not:</strong> Bu scripti sadece bir kez çalıştırın. Modüller zaten yüklüyse güncelleme yapar.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Hata:</strong> " . $e->getMessage();
    echo "<br><strong>Dosya:</strong> " . $e->getFile();
    echo "<br><strong>Satır:</strong> " . $e->getLine();
    echo "<br><strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
