<?php
require_once 'admin/includes/database.php';

echo "<h2>2026 Etkinlikleri Veritabanına Aktarma</h2>";

try {
    $db = Database::getInstance();
    
    // Önce mevcut 2026 etkinliklerini temizle
    echo "<p>Mevcut 2026 etkinlikleri temizleniyor...</p>";
    $db->query("DELETE FROM calendar_events WHERE YEAR(start_date) = 2026");
    
    // Yedek dosyasından verileri al
    require_once 'admin/events_2026_backup.php';
    
    echo "<p>Toplam " . count($events_2026_backup) . " etkinlik bulundu.</p>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($events_2026_backup as $event) {
        try {
            // Veritabanına ekle
            $result = $db->insert('calendar_events', [
                'title' => $event['title'],
                'description' => '', // Açıklama yok
                'start_date' => $event['date'],
                'end_date' => $event['date'], // Tek günlük etkinlikler
                'byk_category' => $event['byk'],
                'color' => $event['color'],
                'is_recurring' => 0, // Tekrarlayan değil
                'recurrence_type' => null,
                'recurrence_value' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $successCount++;
            } else {
                $errorCount++;
                echo "<p style='color: red;'>❌ Hata: " . $event['title'] . " (" . $event['date'] . ")</p>";
            }
            
        } catch (Exception $e) {
            $errorCount++;
            echo "<p style='color: red;'>❌ Hata: " . $event['title'] . " - " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Sonuç:</h3>";
    echo "<p style='color: green;'>✅ Başarıyla eklenen: " . $successCount . " etkinlik</p>";
    echo "<p style='color: red;'>❌ Hata olan: " . $errorCount . " etkinlik</p>";
    
    if ($successCount > 0) {
        echo "<p style='color: blue;'>🎉 2026 etkinlikleri başarıyla veritabanına aktarıldı!</p>";
        echo "<p><a href='admin/calendar.php' class='btn btn-primary'>Takvimi Görüntüle</a></p>";
    }
    
    // BYK istatistikleri
    $bykStats = $db->fetchAll("
        SELECT 
            byk_category,
            COUNT(*) as count,
            color
        FROM calendar_events 
        WHERE YEAR(start_date) = 2026 
        GROUP BY byk_category, color
        ORDER BY byk_category
    ");
    
    echo "<h3>BYK İstatistikleri:</h3>";
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>BYK</th><th>Etkinlik Sayısı</th><th>Renk</th></tr></thead>";
    echo "<tbody>";
    foreach ($bykStats as $stat) {
        $bykName = '';
        switch($stat['byk_category']) {
            case 'AT': $bykName = 'Ana Teşkilat'; break;
            case 'KT': $bykName = 'Kadınlar Teşkilatı'; break;
            case 'KGT': $bykName = 'Kadınlar Gençlik Teşkilatı'; break;
            case 'GT': $bykName = 'Gençlik Teşkilatı'; break;
        }
        echo "<tr>";
        echo "<td><strong>" . $stat['byk_category'] . "</strong> - " . $bykName . "</td>";
        echo "<td>" . $stat['count'] . "</td>";
        echo "<td><span style='color: " . $stat['color'] . "; font-weight: bold;'>" . $stat['color'] . "</span></td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Genel hata: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Not:</strong> Bu script tüm 2026 etkinliklerini veritabanına aktarır.</p>";
echo "<p><strong>Güvenlik:</strong> Mevcut 2026 etkinlikleri önce silinir, sonra yedek dosyasından yeniden yüklenir.</p>";
?>
