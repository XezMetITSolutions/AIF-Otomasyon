<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance();

    // Önce kontrol et
    $count = $db->fetch("SELECT COUNT(*) as total FROM toplantilar WHERE konum = 'Raggal' AND YEAR(toplanti_tarihi) = 2026");

    echo "<h2>Raggal 2026 Kayıtlarını Silme</h2>";
    echo "<p>Bulunan kayıt sayısı: <strong>{$count['total']}</strong></p>";

    if ($count['total'] > 0) {
        // Sil
        $db->query("DELETE FROM toplantilar WHERE konum = 'Raggal' AND YEAR(toplanti_tarihi) = 2026");
        echo "<div style='padding:15px; background:#dff0d8; color:#3c763d; border:1px solid #d6e9c6; border-radius:4px;'>";
        echo "<strong>✓ {$count['total']} kayıt başarıyla silindi!</strong>";
        echo "</div>";
    } else {
        echo "<p>Silinecek kayıt bulunamadı.</p>";
    }

} catch (Exception $e) {
    echo '<div style="color:red">Hata: ' . $e->getMessage() . '</div>';
}
?>