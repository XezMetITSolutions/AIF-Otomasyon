<?php
/**
 * Görev İçerik İzinleri - Database Migration
 * 
 * Her görev içeriği için görünürlük ayarları:
 * - sadece_ben: Sadece oluşturan görebilir
 * - ekip: Sadece ekip üyeleri görebilir
 * - herkes: Projedeki herkes görebilir
 * - ozel: Belirli kullanıcılar görebilir
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "=== Görev İçerik İzinleri Ekleniyor ===\n\n";

try {
    // 1. Checklist görünürlük
    echo "1. Checklist tablosuna görünürlük kolonları ekleniyor...\n";

    $columns = $db->fetchAll("SHOW COLUMNS FROM gorev_checklist");
    $hasVisibility = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'gorulebilirlik') {
            $hasVisibility = true;
        }
    }

    if (!$hasVisibility) {
        $db->query("
            ALTER TABLE gorev_checklist 
            ADD COLUMN gorulebilirlik ENUM('sadece_ben', 'ekip', 'herkes', 'ozel') DEFAULT 'ekip' AFTER tamamlandi,
            ADD COLUMN olusturan_id INT DEFAULT NULL AFTER gorulebilirlik
        ");
        echo "   ✓ gorev_checklist görünürlük kolonları eklendi\n";
    } else {
        echo "   ✓ gorev_checklist zaten görünürlük kolonlarına sahip\n";
    }

    // 2. Notlar görünürlük
    echo "\n2. Notlar tablosuna görünürlük kolonu ekleniyor...\n";

    $columns = $db->fetchAll("SHOW COLUMNS FROM gorev_notlari");
    $hasVisibility = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'gorulebilirlik') {
            $hasVisibility = true;
        }
    }

    if (!$hasVisibility) {
        $db->query("
            ALTER TABLE gorev_notlari 
            ADD COLUMN gorulebilirlik ENUM('sadece_ben', 'ekip', 'herkes', 'ozel') DEFAULT 'ekip' AFTER not_icerik
        ");
        echo "   ✓ gorev_notlari görünürlük kolonu eklendi\n";
    } else {
        echo "   ✓ gorev_notlari zaten görünürlük kolonuna sahip\n";
    }

    // 3. Dosyalar görünürlük
    echo "\n3. Dosyalar tablosuna görünürlük kolonu ekleniyor...\n";

    $columns = $db->fetchAll("SHOW COLUMNS FROM gorev_dosyalari");
    $hasVisibility = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'gorulebilirlik') {
            $hasVisibility = true;
        }
    }

    if (!$hasVisibility) {
        $db->query("
            ALTER TABLE gorev_dosyalari 
            ADD COLUMN gorulebilirlik ENUM('sadece_ben', 'ekip', 'herkes', 'ozel') DEFAULT 'ekip' AFTER aciklama
        ");
        echo "   ✓ gorev_dosyalari görünürlük kolonu eklendi\n";
    } else {
        echo "   ✓ gorev_dosyalari zaten görünürlük kolonuna sahip\n";
    }

    // 4. Özel izinler tablosu (sadece_ben, ekip, herkes olmayan durumlar için)
    echo "\n4. Özel izinler tablosu oluşturuluyor...\n";

    $db->query("
        CREATE TABLE IF NOT EXISTS gorev_icerik_izinleri (
            id INT AUTO_INCREMENT PRIMARY KEY,
            icerik_tipi ENUM('checklist', 'not', 'dosya') NOT NULL,
            icerik_id INT NOT NULL,
            kullanici_id INT NOT NULL,
            INDEX idx_icerik (icerik_tipi, icerik_id),
            INDEX idx_kullanici (kullanici_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "   ✓ gorev_icerik_izinleri tablosu oluşturuldu\n";

    echo "\n✅ Tüm değişiklikler başarıyla uygulandı!\n";
    echo "\nGörünürlük Seviyeleri:\n";
    echo "  • sadece_ben: Sadece oluşturan kişi görebilir\n";
    echo "  • ekip: Sadece görev ekibindeki üyeler görebilir\n";
    echo "  • herkes: Projedeki tüm üyeler görebilir\n";
    echo "  • ozel: Belirli kullanıcılar görebilir (gorev_icerik_izinleri tablosunda)\n";

} catch (Exception $e) {
    echo "\n❌ HATA: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>