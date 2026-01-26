<?php
/**
 * Eski Tabloları Temizleme Scripti
 * Migration sonrası kullanılmayan tabloları güvenli bir şekilde siler
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

// HTML output için header
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Tablo Temizleme</title><style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style></head><body>";
    echo "<h1>Eski Tabloları Temizleme</h1><pre>";
}

$db = Database::getInstance();

echo "=== Eski Tabloları Temizleme Başlıyor ===\n\n";
echo "⚠️  ÖNEMLİ: Bu işlem geri alınamaz!\n";
echo "Verilerin başarıyla migrate edildiğinden emin olun.\n\n";

// Foreign key kontrollerini geçici olarak kapat
$db->query("SET FOREIGN_KEY_CHECKS = 0");

// Silinecek tablolar (kesin)
$tablesToDelete = [
    // Ana tablolar (migrate edildi)
    'users',              // → kullanicilar
    'events',             // → etkinlikler
    'announcements',      // → duyurular
    'meetings',           // → toplantilar
    'expenses',           // → harcama_talepleri
    'inventory',          // → demirbaslar
    'projects',           // → projeler
    
    // İlişkili tablolar
    'expense_items',      // → expenses'e bağlı
    'meeting_agenda',     // → meetings'e bağlı
    'meeting_decisions',  // → meetings'e bağlı
    'meeting_files',      // → meetings'e bağlı
    'meeting_follow_ups', // → meetings'e bağlı
    'meeting_notes',      // → meetings'e bağlı
    'meeting_notifications', // → meetings'e bağlı
    'meeting_participants',  // → meetings'e bağlı
    'meeting_reports',    // → meetings'e bağlı
    'user_permissions',   // → users'a bağlı
    'user_sessions',      // → users'a bağlı
];

// Kontrol edilmesi gereken tablolar (kullanıcı onayı gerekli)
$tablesToCheck = [
    'byk_categories',     // → byk tablosuna migrate edildi (ama kontrol edin!)
    'byk_sub_units',      // → alt_birimler tablosuna migrate edildi (ama kontrol edin!)
    'byk_units',          // → byk tablosuna migrate edildi (ama kontrol edin!)
    'calendar_events',    // Takvim için kullanılıyor mu?
    'event_types',        // Gerekli mi?
    'expense_types',      // Gerekli mi?
    'announcement_types', // Gerekli mi?
    'sub_units',          // Alt birimler için kullanılıyor mu?
];

// Silinmemesi gereken tablolar
$tablesToKeep = [
    'system_settings',    // Sistem ayarları - GEREKLİ!
    'modules',           // Modül yönetimi - GEREKLİ!
    'positions',         // Pozisyonlar - gerekli olabilir
    'reports',           // Raporlar - gerekli olabilir
    'reservations',      // Rezervasyonlar - gerekli olabilir
];

try {
    $deleted = 0;
    $notFound = 0;
    $errors = 0;
    
    echo "1. Kesin Silinecek Tablolar:\n";
    echo str_repeat("=", 50) . "\n";
    
    foreach ($tablesToDelete as $table) {
        try {
            // Tablo var mı kontrol et (tablo adı için placeholder kullanılamaz, doğrudan sorgu)
            $tableName = $db->getConnection()->quote($table);
            $exists = $db->fetch("SHOW TABLES LIKE {$tableName}");
            
            if ($exists) {
                // Önce tablo içindeki kayıt sayısını göster (güvenli sorgulama)
                try {
                    $countResult = $db->fetch("SELECT COUNT(*) as count FROM `" . str_replace('`', '``', $table) . "`");
                    $count = $countResult['count'] ?? 0;
                } catch (Exception $e) {
                    $count = 0;
                }
                
                // Tabloyu sil (güvenli tablo adı kullanımı)
                $db->query("DROP TABLE IF EXISTS `" . str_replace('`', '``', $table) . "`");
                $deleted++;
                echo "   ✓ Tablo silindi: {$table}";
                if ($count > 0) {
                    echo " ({$count} kayıt vardı)";
                }
                echo "\n";
            } else {
                $notFound++;
                echo "   - Tablo bulunamadı: {$table}\n";
            }
        } catch (Exception $e) {
            $errors++;
            echo "   ❌ Hata: {$table} - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n2. Kontrol Edilmesi Gereken Tablolar:\n";
    echo str_repeat("=", 50) . "\n";
    echo "⚠️  Bu tablolar başka yerlerde kullanılıyor olabilir!\n";
    echo "Manuel olarak kontrol edin ve gerekirse silin.\n\n";
    
    foreach ($tablesToCheck as $table) {
        try {
            // Tablo var mı kontrol et
            $tableName = $db->getConnection()->quote($table);
            $exists = $db->fetch("SHOW TABLES LIKE {$tableName}");
            
            if ($exists) {
                try {
                    $countResult = $db->fetch("SELECT COUNT(*) as count FROM `" . str_replace('`', '``', $table) . "`");
                    $count = $countResult['count'] ?? 0;
                    echo "   ⚠ {$table} - {$count} kayıt var (Manuel kontrol gerekli)\n";
                } catch (Exception $e) {
                    echo "   ⚠ {$table} - Tablo mevcut (kayıt sayısı alınamadı)\n";
                }
            } else {
                echo "   - {$table} - Bulunamadı\n";
            }
        } catch (Exception $e) {
            echo "   ❌ {$table} - Hata: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n3. Silinmemesi Gereken Tablolar (Sistem Gerekli):\n";
    echo str_repeat("=", 50) . "\n";
    
    foreach ($tablesToKeep as $table) {
        try {
            // Tablo var mı kontrol et
            $tableName = $db->getConnection()->quote($table);
            $exists = $db->fetch("SHOW TABLES LIKE {$tableName}");
            
            if ($exists) {
                try {
                    $countResult = $db->fetch("SELECT COUNT(*) as count FROM `" . str_replace('`', '``', $table) . "`");
                    $count = $countResult['count'] ?? 0;
                    echo "   ✅ {$table} - {$count} kayıt (SİLİNMEDİ - Sistem gerekli)\n";
                } catch (Exception $e) {
                    echo "   ✅ {$table} - Tablo mevcut (SİLİNMEDİ - Sistem gerekli)\n";
                }
            } else {
                echo "   - {$table} - Bulunamadı\n";
            }
        } catch (Exception $e) {
            echo "   ❌ {$table} - Hata: " . $e->getMessage() . "\n";
        }
    }
    
    // Foreign key kontrollerini tekrar aktif et
    $db->query("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Temizleme Tamamlandı!\n\n";
    echo "Özet:\n";
    echo "  - {$deleted} tablo silindi\n";
    echo "  - {$notFound} tablo bulunamadı\n";
    echo "  - {$errors} hata oluştu\n";
    
    if (count($tablesToCheck) > 0) {
        echo "\n⚠️  Uyarı: Kontrol edilmesi gereken tablolar var.\n";
        echo "Manuel olarak kontrol edip gerekirse silin.\n";
    }
    
} catch (Exception $e) {
    // Foreign key kontrollerini tekrar aktif et (hata durumunda da)
    $db->query("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n❌ Genel Hata: " . $e->getMessage() . "\n";
    echo "Satır: " . $e->getLine() . "\n";
    echo "Dosya: " . $e->getFile() . "\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "</pre></body></html>";
    }
    
    exit(1);
}

// HTML output için footer
if (php_sapi_name() !== 'cli') {
    echo "</pre><p><a href='/admin/dashboard.php'>Dashboard'a Dön</a></p></body></html>";
}

