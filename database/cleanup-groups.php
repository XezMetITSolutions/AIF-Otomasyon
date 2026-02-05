<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "🛠️ Gruplar Temizleniyor ve Düzeltiliyor...\n";

// 1. Yinelenen isimleri (TRIM edilince aynı olanları) bul ve birleştir
$allGroups = $db->fetchAll("SELECT * FROM ziyaret_gruplari");
$seen = [];
$toDelete = [];

foreach ($allGroups as $g) {
    $trimmedName = trim($g['grup_adi']);
    if (isset($seen[$trimmedName])) {
        echo "⚠️ Çift Grup Bulundu: '$trimmedName' (ID: " . $g['grup_id'] . " -> Siliniyor, ID: " . $seen[$trimmedName] . " -> Birleştiriliyor)\n";
        
        // Üyeleri ve ziyaretleri eski ID'den yeni ID're aktar
        $db->query("UPDATE IGNORE ziyaret_grup_uyeleri SET grup_id = ? WHERE grup_id = ?", [$seen[$trimmedName], $g['grup_id']]);
        $db->query("UPDATE sube_ziyaretleri SET grup_id = ? WHERE grup_id = ? AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM sube_ziyaretleri) as tmp WHERE byk_id = sube_ziyaretleri.byk_id AND grup_id = ? AND ziyaret_tarihi = sube_ziyaretleri.ziyaret_tarihi)", [$seen[$trimmedName], $g['grup_id'], $seen[$trimmedName]]);
        
        $toDelete[] = $g['grup_id'];
    } else {
        $seen[$trimmedName] = $g['grup_id'];
        if ($g['grup_adi'] !== $trimmedName) {
            $db->query("UPDATE ziyaret_gruplari SET grup_adi = ? WHERE grup_id = ?", [$trimmedName, $g['grup_id']]);
        }
    }
}

if (!empty($toDelete)) {
    $ids = implode(',', $toDelete);
    $db->query("DELETE FROM ziyaret_grup_uyeleri WHERE grup_id IN ($ids)");
    $db->query("DELETE FROM ziyaret_gruplari WHERE grup_id IN ($ids)");
}

// 2. Eksik grupları (Grup 4, Grup 5) kontrol et ve oluştur
for ($i = 1; $i <= 5; $i++) {
    $name = "Grup $i";
    $exists = $db->fetch("SELECT grup_id FROM ziyaret_gruplari WHERE grup_adi = ?", [$name]);
    if (!$exists) {
        echo "➕ Eksik Grup Ekleniyor: $name\n";
        $db->query("INSERT INTO ziyaret_gruplari (grup_adi, renk_kodu) VALUES (?, ?)", [$name, '#'.substr(md5($name), 0, 6)]);
    }
}

echo "✅ Temizlik Tamamlandı.\n";
