<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance();

    echo "<h2>Raggal Takvimi Kayıt Kontrolü</h2>";

    // Admin/BYK Bulma
    $admin = $db->fetch("SELECT * FROM kullanicilar WHERE byk_id IS NOT NULL ORDER BY kullanici_id ASC LIMIT 1");

    if ($admin) {
        $olusturan_id = $admin['kullanici_id'];
        $byk_id = $admin['byk_id'];
    } else {
        $byk = $db->fetch("SELECT * FROM byk LIMIT 1");
        if (!$byk)
            die('BYK bulunamadı.');
        $byk_id = $byk['byk_id'];
        $user = $db->fetch("SELECT * FROM kullanicilar LIMIT 1");
        if (!$user)
            die('Kullanıcı bulunamadı.');
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

    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;'>";
    echo "<tr style='background:#f2f2f2'><th>Tarih</th><th>Başlık</th><th>Durum</th></tr>";

    $added = 0;
    foreach ($events as $evt) {
        $tarih = $evt[0];
        $baslik = $evt[1];

        $check = $db->fetch("SELECT * FROM toplantilar WHERE toplanti_tarihi = ? AND baslik = ?", [$tarih, $baslik]);

        echo "<tr>";
        echo "<td>" . date('d.m.Y H:i', strtotime($tarih)) . "</td>";
        echo "<td>$baslik</td>";

        if ($check) {
            echo "<td style='color:green; font-weight:bold;'>✔ Veritabanında Mevcut (ID: {$check['toplanti_id']})</td>";
        } else {
            $db->query(
                "INSERT INTO toplantilar (byk_id, olusturan_id, baslik, aciklama, toplanti_tarihi, konum, durum, toplanti_turu) VALUES (?, ?, ?, ?, ?, ?, 'planlandi', 'normal')",
                [$byk_id, $olusturan_id, $baslik, 'Raggal 2026 Takvimi', $tarih, 'Raggal']
            );
            echo "<td style='color:blue; font-weight:bold;'>➕ Şimdi Eklendi</td>";
            $added++;
        }
        echo "</tr>";
    }
    echo "</table>";

    echo "<br>";
    if ($added > 0) {
        echo "<div style='padding:15px; background:#e7f3fe; color:#31708f; border:1px solid #bce8f1; border-radius:4px;'><strong>Toplam $added yeni toplantı başarıyla eklendi.</strong></div>";
    } else {
        echo "<div style='padding:15px; background:#dff0d8; color:#3c763d; border:1px solid #d6e9c6; border-radius:4px;'><strong>Tüm kayıtlar zaten veritabanında mevcut, işlem yapılmasına gerek yok.</strong></div>";
    }

} catch (Exception $e) {
    echo '<div style="color:red">Hata: ' . $e->getMessage() . '</div>';
}
?>