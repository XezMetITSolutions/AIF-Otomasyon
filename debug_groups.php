<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "--- Ziyaret Gruplari ---\n";
$gruplar = $db->fetchAll("SELECT * FROM ziyaret_gruplari ORDER BY grup_id");
foreach ($gruplar as $g) {
    echo "ID: " . $g['grup_id'] . " | Adi: " . $g['grup_adi'] . " | Baskan ID: " . ($g['baskan_id'] ?? 'NULL') . "\n";
    $uyeler = $db->fetchAll("SELECT k.ad, k.soyad FROM ziyaret_grup_uyeleri gu JOIN kullanicilar k ON gu.kullanici_id = k.kullanici_id WHERE gu.grup_id = ?", [$g['grup_id']]);
    echo "  Uyeler: ";
    $names = [];
    foreach ($uyeler as $u) $names[] = $u['ad'] . ' ' . $u['soyad'];
    echo implode(", ", $names) . "\n\n";
}
