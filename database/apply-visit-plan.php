<?php
/**
 * Şube Ziyaret Planı Uygulama Scripti
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();
require_once __DIR__ . '/../includes/ensure_sube_ziyaretleri_tables.php';

$groupsData = [
    'Grup 1' => ['Adem Imamoglu', 'Ali Gümüs', 'Ali Yaman', 'Beytullah Cavus'],
    'Grup 2' => ['Ekrem Gürel', 'Enes Sivrikaya', 'Hüseyin Akyildiz', 'Ibrahim Cetin'],
    'Grup 3' => ['Rasit Demir', 'Mete Burcak', 'Yakup Alici', 'Umut Burcak', 'Namik Demirkiran'],
    'Grup 4' => ['Halit Sicimli', 'Ömer Kutlucan', 'Volkan Meral', 'Mehmet Baltaci'],
    'Grup 5' => ['Sinan Yigit', 'Fikret Özcan', 'Mahmut Yildiz', 'Yahya Yigit']
];

$schedule = [
    '2026-02-06' => ['Reutte', 'Bregenz', 'Zirl', 'Innsbruck', 'Jenbach'],
    '2026-02-13' => ['Lustenau', 'Hall in Tirol', 'Bludenz', 'Feldkirch', 'Dornbirn'],
    '2026-03-20' => ['Wörgl', 'Vomp', 'Radfeld', 'Reutte', 'Hall in Tirol'],
    '2026-03-27' => ['Zirl', 'Innsbruck', 'Jenbach', 'Lustenau', 'Bregenz'],
    '2026-04-10' => ['Bludenz', 'Feldkirch', 'Dornbirn', 'Wörgl', 'Vomp'],
    '2026-04-24' => ['Radfeld', 'Reutte', 'Hall in Tirol', 'Zirl', 'Innsbruck'],
    '2026-05-01' => ['Jenbach', 'Lustenau', 'Bregenz', 'Bludenz', 'Feldkirch']
];

function normalizeName($name) {
    $map = ['ı'=>'i','İ'=>'I','ğ'=>'g','Ğ'=>'G','ü'=>'u','Ü'=>'U','ş'=>'s','Ş'=>'S','ö'=>'o','Ö'=>'O','ç'=>'c','Ç'=>'C'];
    return strtolower(strtr($name, $map));
}

function findUserId($name, $db) {
    $normalized = normalizeName($name);
    $parts = explode(' ', $normalized);
    $lastName = end($parts);
    $firstName = count($parts) > 1 ? $parts[0] : '';
    
    // 1. Exact match
    $res = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE LOWER(CONCAT(ad, ' ', soyad)) = ? OR LOWER(CONCAT(ad, '  ', soyad)) = ?", [$normalized, $normalized]);
    if ($res) return $res['kullanici_id'];
    
    // 2. Fuzzy match with LIKE
    $res = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE (LOWER(ad) LIKE ? AND LOWER(soyad) LIKE ?) OR LOWER(CONCAT(ad, ' ', soyad)) LIKE ?", ["%$firstName%", "%$lastName%", "%$normalized%"]);
    return $res ? $res['kullanici_id'] : null;
}

function findBykId($name, $db) {
    $sql = "SELECT byk_id FROM byk WHERE byk_adi LIKE ? LIMIT 1";
    $res = $db->fetch($sql, ["%$name%"]);
    return $res ? $res['byk_id'] : null;
}

echo "🚀 Ziyaret Planı Uygulanıyor...\n";

// 1. Grupları Oluştur ve Üyeleri Ata
$groupMap = [];
foreach ($groupsData as $gName => $members) {
    echo "👥 Grup İşleniyor: $gName\n";
    
    // Grubu bul veya oluştur
    $existing = $db->fetch("SELECT grup_id FROM ziyaret_gruplari WHERE grup_adi = ?", [$gName]);
    if ($existing) {
        $gId = $existing['grup_id'];
        $db->query("DELETE FROM ziyaret_grup_uyeleri WHERE grup_id = ?", [$gId]);
    } else {
        $db->query("INSERT INTO ziyaret_gruplari (grup_adi, renk_kodu) VALUES (?, ?)", [$gName, '#'.substr(md5($gName), 0, 6)]);
        $gId = $db->lastInsertId();
    }
    $groupMap[$gName] = $gId;
    
    foreach ($members as $mName) {
        $uId = findUserId($mName, $db);
        if ($uId) {
            $db->query("INSERT INTO ziyaret_grup_uyeleri (grup_id, kullanici_id) VALUES (?, ?)", [$gId, $uId]);
            echo "   ✅ Üye Eklendi: $mName (ID: $uId)\n";
        } else {
            echo "   ⚠️  Üye Bulunamadı: $mName\n";
        }
    }
}

// 2. Takvimi İşle
echo "\n📅 Takvim İşleniyor...\n";
$superAdminId = $db->fetchColumn("SELECT kullanici_id FROM kullanicilar WHERE role = 'super_admin' LIMIT 1");

foreach ($schedule as $date => $assignments) {
    echo "🗓️ Tarih: $date\n";
    foreach ($assignments as $index => $bykName) {
        $gName = "Grup " . ($index + 1);
        $gId = $groupMap[$gName];
        $bykId = findBykId($bykName, $db);
        
        if ($gId && $bykId) {
            // Mevcut ziyareti kontrol et
            $exists = $db->fetch("SELECT ziyaret_id FROM sube_ziyaretleri WHERE byk_id = ? AND grup_id = ? AND ziyaret_tarihi = ?", [$bykId, $gId, $date]);
            if (!$exists) {
                $db->query("INSERT INTO sube_ziyaretleri (byk_id, grup_id, ziyaret_tarihi, durum, olusturan_id) VALUES (?, ?, ?, 'planlandi', ?)", 
                          [$bykId, $gId, $date, $superAdminId]);
                echo "   ✅ Ziyaret Atandı: $bykName -> $gName\n";
            } else {
                echo "   ℹ️  Ziyaret Zaten Var: $bykName -> $gName\n";
            }
        } else {
            if (!$bykId) echo "   ❌ Şube Bulunamadı: $bykName\n";
            if (!$gId) echo "   ❌ Grup Bulunamadı: $gName\n";
        }
    }
}

echo "\n✨ İşlem Tamamlandı!\n";
