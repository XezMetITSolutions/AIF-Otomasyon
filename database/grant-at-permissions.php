<?php
/**
 * AT Birimi Üyelerine Şube Ziyaret Yetkisi Verme Scripti
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "🚀 AT Birimi üyeleri taranıyor...\n";

try {
    // 1. AT birimine bağlı tüm üyeleri bul
    $atUsers = $db->fetchAll("
        SELECT k.kullanici_id, k.ad, k.soyad 
        FROM kullanicilar k 
        JOIN byk b ON k.byk_id = b.byk_id 
        WHERE b.byk_kodu = 'AT'
    ");

    if (empty($atUsers)) {
        echo "❌ AT birimine bağlı üye bulunamadı.\n";
        exit;
    }

    echo "✅ " . count($atUsers) . " üye bulundu. Yetkiler tanımlanıyor...\n";

    $count = 0;
    foreach ($atUsers as $user) {
        // 2. Şube ziyaretleri yetkisini ekle (baskan_sube_ziyaretleri)
        $db->query("
            INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view) 
            VALUES (?, 'baskan_sube_ziyaretleri', 1)
            ON DUPLICATE KEY UPDATE can_view = 1
        ", [$user['kullanici_id']]);
        
        echo "   - " . $user['ad'] . " " . $user['soyad'] . " (ID: " . $user['kullanici_id'] . ") yetkilendirildi.\n";
        $count++;
    }

    echo "\n✨ İşlem tamamlandı! Toplam $count AT üyesine yetki verildi.\n";

} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
