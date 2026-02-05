<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

// 1. AT Birim ID Al
$at = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = 'AT'");
$atId = $at['byk_id'] ?? null;

// 2. Uye Rol ID Al
$role = $db->fetch("SELECT rol_id FROM roller WHERE rol_adi = 'uye' LIMIT 1");
$roleId = $role['rol_id'] ?? null;

if (!$atId || !$roleId) {
    die("AT unit or Member role not found.");
}

$missingUsers = [
    ['ad' => 'Adem', 'soyad' => 'İmanoğlu', 'email' => 'adem.imanoglu@islamfederasyonu.at'],
    ['ad' => 'Beytullah', 'soyad' => 'Çavuş', 'email' => 'beytullah.cavus@islamfederasyonu.at'],
    ['ad' => 'Yakup', 'soyad' => 'Alıcı', 'email' => 'yakup.alici@islamfederasyonu.at'],
    ['ad' => 'Yahya', 'soyad' => 'Yiğit', 'email' => 'yahya.yigit@islamfederasyonu.at']
];

echo "🚀 Eksik kullanıcılar ekleniyor...\n";

foreach ($missingUsers as $u) {
    // Önce kontrol et
    $exists = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE ad = ? AND soyad = ?", [$u['ad'], $u['soyad']]);
    if ($exists) {
        $userId = $exists['kullanici_id'];
        echo "ℹ️  Zaten var: " . $u['ad'] . " " . $u['soyad'] . "\n";
    } else {
        $pass = password_hash('123456', PASSWORD_DEFAULT);
        $db->query("INSERT INTO kullanicilar (ad, soyad, email, sifre, rol_id, byk_id, aktif) VALUES (?, ?, ?, ?, ?, ?, 1)", 
            [$u['ad'], $u['soyad'], $u['email'], $pass, $roleId, $atId]);
        $userId = $db->lastInsertId();
        echo "✅ Eklendi: " . $u['ad'] . " " . $u['soyad'] . " (ID: $userId)\n";
    }

    // Yetki ver
    $db->query("INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view) VALUES (?, 'baskan_sube_ziyaretleri', 1) ON DUPLICATE KEY UPDATE can_view = 1", [$userId]);
}

echo "\n✨ İşlem tamamlandı. Şimdi apply_plan.php tekrar çalıştırıldığında gruplara otomatik atanacaklar.\n";
