<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';

header('Content-Type: text/html; charset=utf-8');

$auth = new Auth();
$user = $auth->getUser();

try {
    $db = Database::getInstance();

    echo "<h2>Raggal 2026 Kayıtları - Detaylı Kontrol</h2>";

    // Tüm Raggal kayıtlarını listele
    $kayitlar = $db->fetchAll("
        SELECT t.*, b.byk_adi 
        FROM toplantilar t
        LEFT JOIN byk b ON t.byk_id = b.byk_id
        WHERE t.konum = 'Raggal' 
        AND YEAR(t.toplanti_tarihi) = 2026
        ORDER BY t.toplanti_tarihi ASC
    ");

    if (empty($kayitlar)) {
        echo "<div style='padding:15px; background:#f2dede; color:#a94442; border:1px solid #ebccd1; border-radius:4px;'>";
        echo "<strong>Hiç kayıt bulunamadı!</strong> Veritabanında 2026 yılı için Raggal konumlu toplantı yok.";
        echo "</div>";
    } else {
        echo "<p><strong>Toplam " . count($kayitlar) . " kayıt bulundu:</strong></p>";

        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;'>";
        echo "<tr style='background:#f2f2f2'><th>ID</th><th>Tarih</th><th>Başlık</th><th>BYK</th><th>Durum</th><th>Link</th></tr>";

        foreach ($kayitlar as $k) {
            echo "<tr>";
            echo "<td>{$k['toplanti_id']}</td>";
            echo "<td>" . date('d.m.Y H:i', strtotime($k['toplanti_tarihi'])) . "</td>";
            echo "<td>{$k['baslik']}</td>";
            echo "<td>{$k['byk_adi']} (ID: {$k['byk_id']})</td>";
            echo "<td>{$k['durum']}</td>";
            echo "<td><a href='/panel/toplanti-duzenle.php?id={$k['toplanti_id']}' target='_blank'>Aç</a></td>";
            echo "</tr>";
        }

        echo "</table>";

        if ($user) {
            echo "<br><p><strong>Sizin BYK ID'niz:</strong> {$user['byk_id']}</p>";
            echo "<p>Eğer yukarıdaki kayıtlarda farklı BYK ID görüyorsanız ve admin değilseniz, panel/toplantilar.php sayfasında bu kayıtlar görünmez.</p>";
        }
    }

} catch (Exception $e) {
    echo '<div style="color:red">Hata: ' . $e->getMessage() . '</div>';
}
?>

<br>
<div style="padding:15px; background:#d9edf7; color:#31708f; border:1px solid #bce8f1; border-radius:4px;">
    <strong>Not:</strong> Kayıtları görmek için:<br>
    • Panel > Toplantılar sayfasına gidin<br>
    • Tarih filtresinde 2026 yılını seçin<br>
    • Eğer BYK filtreniz kayıtlarla uyuşmuyorsa admin yetkisi gerekir
</div>