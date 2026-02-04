<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';

header('Content-Type: text/html; charset=utf-8');

$auth = new Auth();
$user = $auth->getUser();

try {
    $db = Database::getInstance();

    echo "<h2>Raggal 2026 Takvimi - Kayıt Ekleme</h2>";

    // Admin kullanıcı (rezervasyonları onun adına yapacağız)
    $admin = $db->fetch("SELECT * FROM kullanicilar WHERE byk_id IS NOT NULL ORDER BY kullanici_id ASC LIMIT 1");
    if (!$admin) {
        die('Kullanıcı bulunamadı.');
    }

    $kullanici_id = $admin['kullanici_id'];
    echo "<p>Rezervasyonlar <strong>{$admin['ad']} {$admin['soyad']}</strong> adına ekleniyor.</p>";

    // 2026 Raggal Takvimi (Başlangıç ve Bitiş saatleri)
    $events = [
        ['2026-02-27 15:00:00', '2026-02-27 18:00:00', 'İmamlar Toplantısı'],
        ['2026-02-13 18:00:00', '2026-02-13 21:00:00', 'GB Kenan Guman'],
        ['2026-02-15 18:00:00', '2026-02-15 21:00:00', 'GB 55 Kişi'],
        ['2026-04-10 09:00:00', '2026-04-10 12:00:00', 'GT OÖ'],
        ['2026-04-25 08:00:00', '2026-04-25 17:00:00', 'KGT'],
        ['2026-05-08 15:00:00', '2026-05-08 18:00:00', 'KT'],
        ['2026-05-09 15:00:00', '2026-05-09 18:00:00', 'KT'],
        ['2026-05-14 17:00:00', '2026-05-14 20:00:00', 'Ömer Çalar (Rein Asar)'],
        ['2026-05-17 17:00:00', '2026-05-17 20:00:00', 'Ömer Çalar (Rein)'],
        ['2026-06-06 08:00:00', '2026-06-06 17:00:00', 'KGT'],
        ['2026-06-28 21:00:00', '2026-06-29 00:00:00', 'KT SBT'],
        ['2026-10-02 08:00:00', '2026-10-02 17:00:00', 'KT Kübra Nur'],
        ['2026-10-04 08:00:00', '2026-10-04 17:00:00', 'KT Kübra Nur'],
        ['2026-10-23 18:00:00', '2026-10-23 21:00:00', 'GT OÖ'],
        ['2026-10-30 08:00:00', '2026-10-30 17:00:00', 'KGT'],
        ['2026-11-21 08:00:00', '2026-11-21 17:00:00', 'KGT']
    ];

    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;'>";
    echo "<tr style='background:#f2f2f2'><th>Tarih</th><th>Başlık</th><th>Durum</th></tr>";

    $added = 0;
    foreach ($events as $evt) {
        $baslangic = $evt[0];
        $bitis = $evt[1];
        $aciklama = $evt[2];

        // Çakışma kontrolü
        $check = $db->fetch("
            SELECT id FROM raggal_talepleri 
            WHERE baslangic_tarihi = ? AND bitis_tarihi = ? AND aciklama = ?
        ", [$baslangic, $bitis, $aciklama]);

        echo "<tr>";
        echo "<td>" . date('d.m.Y H:i', strtotime($baslangic)) . " - " . date('H:i', strtotime($bitis)) . "</td>";
        echo "<td>$aciklama</td>";

        if ($check) {
            echo "<td style='color:green; font-weight:bold;'>✔ Zaten Mevcut</td>";
        } else {
            $db->query("
                INSERT INTO raggal_talepleri (kullanici_id, baslangic_tarihi, bitis_tarihi, aciklama, durum) 
                VALUES (?, ?, ?, ?, 'onaylandi')
            ", [$kullanici_id, $baslangic, $bitis, $aciklama]);
            echo "<td style='color:blue; font-weight:bold;'>➕ Eklendi (ONAYLANDI)</td>";
            $added++;
        }
        echo "</tr>";
    }
    echo "</table>";

    echo "<br>";
    if ($added > 0) {
        echo "<div style='padding:15px; background:#dff0d8; color:#3c763d; border:1px solid #d6e9c6; border-radius:4px;'>";
        echo "<strong>✓ Toplam $added rezervasyon başarıyla eklendi ve ONAYLANDI!</strong><br>";
        echo "Şimdi <a href='/panel/raggal-talepleri.php?tab=takvim' target='_blank'>Raggal Takvimi</a> sayfasına gidip kontrol edebilirsiniz.";
        echo "</div>";
    } else {
        echo "<div style='padding:15px; background:#d9edf7; color:#31708f; border:1px solid #bce8f1; border-radius:4px;'>";
        echo "<strong>Tüm kayıtlar zaten mevcut.</strong>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo '<div style="color:red">Hata: ' . $e->getMessage() . '</div>';
}
?>