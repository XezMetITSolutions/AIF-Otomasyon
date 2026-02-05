<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "🛠️ Gruplar Temizleniyor ve Düzeltiliyor (Hard Reset)...\n";

// 1. Tüm isimleri normalize et (Bosluklari temizle, invisible karakterleri yok et)
$allGroupsRaw = $db->fetchAll("SELECT * FROM ziyaret_gruplari");
foreach ($allGroupsRaw as $g) {
    // Görünmez karakterleri, non-breaking spaceleri ve fazla boşlukları temizle
    $cleanName = preg_replace('/[\x00-\x1F\x7F-\xA0]/u', ' ', $g['grup_adi']); // Tab, newline, nbsp...
    $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName)); // Fazla boşlukları teke indir
    
    if ($g['grup_adi'] !== $cleanName) {
        $db->query("UPDATE ziyaret_gruplari SET grup_adi = ? WHERE grup_id = ?", [$cleanName, $g['grup_id']]);
    }
}

// 2. Mükerrerleri bul ve tekilleştir
$allGroups = $db->fetchAll("SELECT * FROM ziyaret_gruplari ORDER BY grup_id ASC");
$seen = [];
foreach ($allGroups as $g) {
    $name = $g['grup_adi'];
    if (isset($seen[$name])) {
        $primaryId = $seen[$name];
        $duplicateId = $g['grup_id'];
        echo "⚠️ Mükerrer Grup Siliniyor: $name (ID: $duplicateId -> Birleştiriliyor ID: $primaryId)\n";
        
        // Üyeleri taşı
        $db->query("UPDATE IGNORE ziyaret_grup_uyeleri SET grup_id = ? WHERE grup_id = ?", [$primaryId, $duplicateId]);
        // Temizle
        $db->query("DELETE FROM ziyaret_grup_uyeleri WHERE grup_id = ?", [$duplicateId]);
        
        // Ziyaretleri taşı
        $db->query("UPDATE sube_ziyaretleri SET grup_id = ? WHERE grup_id = ?", [$primaryId, $duplicateId]);
        
        // Grubu sil
        $db->query("DELETE FROM ziyaret_gruplari WHERE grup_id = ?", [$duplicateId]);
    } else {
        $seen[$name] = $g['grup_id'];
    }
}

// 3. İsimleri düzelt (Örn: Yanlışlıkla Grup 4 yazılmışsa ama Grup 5 olması gerekiyorsa?)
// Aslında bu manuel olmalı ama otomatik 1-5 arası tam olmalı.
for ($i = 1; $i <= 5; $i++) {
    $name = "Grup $i";
    $exists = $db->fetch("SELECT grup_id FROM ziyaret_gruplari WHERE grup_adi = ?", [$name]);
    if (!$exists) {
        echo "➕ Eksik Grup Ekleniyor: $name\n";
        $db->query("INSERT INTO ziyaret_gruplari (grup_adi, renk_kodu) VALUES (?, ?)", [$name, '#'.substr(md5($name), 0, 6)]);
    }
}

echo "✅ Temizlik ve Senkronizasyon Tamamlandı.\n";
