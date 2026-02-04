<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

header('Content-Type: text/plain');

try {
    $db = Database::getInstance();

    // BYK ID si olan bir kullanıcı bul
    $admin = $db->fetch("SELECT * FROM kullanicilar WHERE byk_id IS NOT NULL ORDER BY kullanici_id ASC LIMIT 1");

    if ($admin) {
        $olusturan_id = $admin['kullanici_id'];
        $byk_id = $admin['byk_id'];
    } else {
        // Kullanıcılarda BYK yoksa BYK tablosundan al
        $byk = $db->fetch("SELECT * FROM byk LIMIT 1");
        if (!$byk)
            die('Sistemde hiç BYK tanımlı değil.');
        $byk_id = $byk['byk_id'];

        $user = $db->fetch("SELECT * FROM kullanicilar LIMIT 1");
        if (!$user)
            die('Sistemde hiç kullanıcı yok.');
        $olusturan_id = $user['kullanici_id'];
    }

    $events = [
        ['2026-02-27 15:00:00', 'İmamlar Toplantısı'],
        ['2026-02-13 18:00:00', 'GB Kenan Guman'],
        ['2026-02-15 18:00:00', 'GB 55 Kişi'],
        ['2026-04-10 09:00:00', 'GT OÖ'],
        ['2026-04-25 08:00:00', 'KGT'],
        ['2026-05-08 15:00:00', 'KT'],
        ['2026-05-09 15:00:00', 'KT'],
        ['2026-05-14 17:00:00', 'Ömer Çalar (Rein Asar)'],
        ['2026-05-17 17:00:00', 'Ömer Çalar (Rein)'],
        ['2026-06-06 08:00:00', 'KGT'],
        ['2026-06-28 21:00:00', 'KT SBT'],
        ['2026-10-02 08:00:00', 'KT Kübra Nur'],
        ['2026-10-04 08:00:00', 'KT Kübra Nur'],
        ['2026-10-23 18:00:00', 'GT OÖ'],
        ['2026-10-30 08:00:00', 'KGT'],
        ['2026-10-30 08:00:00', 'KGT (Kasım başına taşan hafta)'],
        ['2026-11-21 08:00:00', 'KGT']
    ];

    $added = 0;
    foreach ($events as $evt) {
        $tarih = $evt[0];
        $baslik = $evt[1];

        $check = $db->fetch("SELECT toplanti_id FROM toplantilar WHERE toplanti_tarihi = ? AND baslik = ?", [$tarih, $baslik]);

        if (!$check) {
            $db->query(
                "INSERT INTO toplantilar (byk_id, olusturan_id, baslik, aciklama, toplanti_tarihi, konum, durum, toplanti_turu) VALUES (?, ?, ?, ?, ?, ?, 'planlandi', 'normal')",
                [$byk_id, $olusturan_id, $baslik, 'Raggal 2026 Takvimi', $tarih, 'Raggal']
            );
            $added++;
        }
    }

    echo "İşlem Tamam. $added toplantı eklendi.";
} catch (Exception $e) {
    echo 'Hata: ' . $e->getMessage();
}
?>