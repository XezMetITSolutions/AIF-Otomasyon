<?php
/**
 * AIF Otomasyon - Yetki Sistemi Kurulum Scripti (aifcrm.metechnik.at)
 * Bu script sadece yetki sistemi tablolarını ve modüllerini kurar
 */

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>AIF Otomasyon - Yetki Sistemi Kurulumu</h2>";
echo "<p><strong>Domain:</strong> aifcrm.metechnik.at</p>";

try {
    // Veritabanı bağlantısını test et
    require_once 'includes/database.php';
    $db = Database::getInstance();
    echo "✓ Veritabanı bağlantısı başarılı<br>";
    
    // Modüller tablosunu oluştur
    echo "<h3>1. Yetki Sistemi Tabloları Oluşturuluyor...</h3>";
    
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
            UNIQUE KEY unique_user_module (user_id, module_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->query($createUserPermissionsTable);
    echo "✓ user_permissions tablosu oluşturuldu<br>";
    
    echo "<h3>2. Modüller Yükleniyor...</h3>";
    
    // Modül verilerini manuel olarak tanımla
    $modules = [
        'dashboard' => [
            'name' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'description' => 'Ana kontrol paneli'
        ],
        'users' => [
            'name' => 'Kullanıcılar',
            'icon' => 'fas fa-users',
            'description' => 'Kullanıcı yönetimi'
        ],
        'permissions' => [
            'name' => 'Yetki Yönetimi',
            'icon' => 'fas fa-shield-alt',
            'description' => 'Yetki ve izin yönetimi'
        ],
        'announcements' => [
            'name' => 'Duyurular',
            'icon' => 'fas fa-bullhorn',
            'description' => 'Duyuru yönetimi'
        ],
        'events' => [
            'name' => 'Etkinlikler',
            'icon' => 'fas fa-calendar-alt',
            'description' => 'Etkinlik yönetimi'
        ],
        'calendar' => [
            'name' => 'Takvim',
            'icon' => 'fas fa-calendar',
            'description' => 'Takvim görüntüleme'
        ],
        'inventory' => [
            'name' => 'Demirbaş Listesi',
            'icon' => 'fas fa-boxes',
            'description' => 'Demirbaş yönetimi'
        ],
        'meeting_reports' => [
            'name' => 'Toplantı Raporları',
            'icon' => 'fas fa-file-alt',
            'description' => 'Toplantı raporları'
        ],
        'reservations' => [
            'name' => 'Rezervasyon',
            'icon' => 'fas fa-bookmark',
            'description' => 'Rezervasyon yönetimi'
        ],
        'expenses' => [
            'name' => 'Para İadesi',
            'icon' => 'fas fa-undo',
            'description' => 'İade talepleri'
        ],
        'projects' => [
            'name' => 'Proje Takibi',
            'icon' => 'fas fa-project-diagram',
            'description' => 'Proje yönetimi'
        ],
        'reports' => [
            'name' => 'Raporlar',
            'icon' => 'fas fa-chart-bar',
            'description' => 'Raporlar ve analizler'
        ],
        'settings' => [
            'name' => 'Ayarlar',
            'icon' => 'fas fa-cog',
            'description' => 'Sistem ayarları'
        ]
    ];
    
    $insertedCount = 0;
    $updatedCount = 0;
    
    foreach ($modules as $moduleKey => $moduleData) {
        try {
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
                echo "✓ Modül güncellendi: {$moduleData['name']}<br>";
            } else {
                // Yeni modül ekle
                $moduleInfo['created_at'] = date('Y-m-d H:i:s');
                $db->insert('modules', $moduleInfo);
                $insertedCount++;
                echo "✓ Yeni modül eklendi: {$moduleData['name']}<br>";
            }
        } catch (Exception $e) {
            echo "⚠ Modül hatası ({$moduleData['name']}): " . $e->getMessage() . "<br>";
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
    
    echo "<h3>Olası Çözümler:</h3>";
    echo "<ul>";
    echo "<li>Veritabanı bağlantı ayarlarını kontrol edin (includes/database.php)</li>";
    echo "<li>Veritabanı kullanıcısının CREATE TABLE yetkisi olduğundan emin olun</li>";
    echo "<li>MySQL versiyonunuzun 5.7+ olduğundan emin olun</li>";
    echo "<li>Veritabanı karakter setinin utf8mb4 olduğundan emin olun</li>";
    echo "</ul>";
}
?>

