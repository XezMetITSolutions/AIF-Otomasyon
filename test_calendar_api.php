<?php
require_once 'admin/includes/database.php';

echo "<h2>Calendar API Test</h2>";

try {
    $db = Database::getInstance();
    
    // Test 1: Veritabanı bağlantısı
    echo "<h3>1. Veritabanı Bağlantısı:</h3>";
    $count = $db->fetchOne("SELECT COUNT(*) FROM calendar_events")['COUNT(*)'];
    echo "<p style='color: green;'>✅ Veritabanı bağlantısı OK - Toplam etkinlik: $count</p>";
    
    // Test 2: 2026 etkinlikleri
    echo "<h3>2. 2026 Etkinlikleri:</h3>";
    $events2026 = $db->fetchAll("SELECT * FROM calendar_events WHERE YEAR(start_date) = 2026 ORDER BY start_date LIMIT 5");
    echo "<p>İlk 5 etkinlik:</p>";
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>ID</th><th>Başlık</th><th>Tarih</th><th>BYK</th><th>Renk</th></tr></thead>";
    echo "<tbody>";
    foreach ($events2026 as $event) {
        echo "<tr>";
        echo "<td>" . $event['id'] . "</td>";
        echo "<td>" . htmlspecialchars($event['title']) . "</td>";
        echo "<td>" . $event['start_date'] . "</td>";
        echo "<td>" . $event['byk_category'] . "</td>";
        echo "<td style='color: " . $event['color'] . ";'>" . $event['color'] . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    
    // Test 3: API endpoint test
    echo "<h3>3. API Endpoint Test:</h3>";
    echo "<p><a href='calendar_api.php?action=list&year=2026&month=1' target='_blank'>API Test Link</a></p>";
    
    // Test 4: Bu ayın etkinlikleri
    echo "<h3>4. Bu Ayın Etkinlikleri:</h3>";
    $currentYear = date('Y');
    $currentMonth = date('n');
    $thisMonthEvents = $db->fetchAll("SELECT * FROM calendar_events WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?", [$currentYear, $currentMonth]);
    echo "<p>Bu ay ($currentYear-$currentMonth): " . count($thisMonthEvents) . " etkinlik</p>";
    
    // Test 5: Gelecek etkinlikler
    echo "<h3>5. Gelecek Etkinlikler:</h3>";
    $today = date('Y-m-d');
    $futureEvents = $db->fetchAll("SELECT * FROM calendar_events WHERE start_date >= ? ORDER BY start_date LIMIT 10", [$today]);
    echo "<p>Bugünden itibaren gelecek etkinlikler: " . count($futureEvents) . "</p>";
    
    echo "<hr>";
    echo "<h3>Sonraki Adımlar:</h3>";
    echo "<p><a href='admin/calendar.php' class='btn btn-primary'>Takvimi Test Et</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}
?>
