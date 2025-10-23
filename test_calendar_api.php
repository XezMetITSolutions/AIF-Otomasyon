<?php
require_once 'admin/includes/database.php';

echo "<h2>Calendar API Test</h2>";

try {
    $db = Database::getInstance();
    
    // Test 1: Tablo var mı?
    echo "<h3>1. Tablo Kontrolü:</h3>";
    try {
        $count = $db->fetchOne("SELECT COUNT(*) FROM calendar_events")['COUNT(*)'];
        echo "<p style='color: green;'>✅ calendar_events tablosu mevcut - $count etkinlik</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Tablo hatası: " . $e->getMessage() . "</p>";
        exit;
    }
    
    // Test 2: API endpoint'i test et
    echo "<h3>2. API Endpoint Testi:</h3>";
    
    // GET request simülasyonu
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['action'] = 'list';
    $_GET['year'] = '2026';
    $_GET['month'] = '1';
    
    ob_start();
    include 'admin/api/calendar_api.php';
    $output = ob_get_clean();
    
    echo "<h4>API Response:</h4>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // JSON parse test
    $jsonData = json_decode($output, true);
    if ($jsonData === null) {
        echo "<p style='color: red;'>❌ JSON Parse Hatası: " . json_last_error_msg() . "</p>";
    } else {
        echo "<p style='color: green;'>✅ JSON başarıyla parse edildi</p>";
        echo "<p>Success: " . ($jsonData['success'] ? 'true' : 'false') . "</p>";
        echo "<p>Events count: " . count($jsonData['events'] ?? []) . "</p>";
    }
    
    // Test 3: POST request testi
    echo "<h3>3. POST Request Testi:</h3>";
    
    $testData = [
        'action' => 'save',
        'id' => 1,
        'title' => 'Test Etkinlik',
        'start_date' => '2026-01-15',
        'end_date' => '2026-01-15',
        'byk_category' => 'AT',
        'description' => 'Test açıklaması',
        'is_recurring' => 0,
        'recurrence_type' => 'none',
        'recurrence_pattern' => '',
        'recurrence_end_date' => null
    ];
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['action'] = 'save';
    
    // Simulate JSON input
    $jsonInput = json_encode($testData);
    
    ob_start();
    // Mock the file_get_contents for php://input
    $originalInput = file_get_contents('php://input');
    file_put_contents('php://temp', $jsonInput);
    
    include 'admin/api/calendar_api.php';
    $postOutput = ob_get_clean();
    
    echo "<h4>POST Response:</h4>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;'>";
    echo htmlspecialchars($postOutput);
    echo "</pre>";
    
    // JSON parse test for POST
    $postJsonData = json_decode($postOutput, true);
    if ($postJsonData === null) {
        echo "<p style='color: red;'>❌ POST JSON Parse Hatası: " . json_last_error_msg() . "</p>";
    } else {
        echo "<p style='color: green;'>✅ POST JSON başarıyla parse edildi</p>";
        echo "<p>Success: " . ($postJsonData['success'] ? 'true' : 'false') . "</p>";
        echo "<p>Message: " . ($postJsonData['message'] ?? 'N/A') . "</p>";
    }
    
    // Test 4: İlk birkaç etkinliği göster
    echo "<h3>4. İlk 5 Etkinlik:</h3>";
    $events = $db->fetchAll("SELECT * FROM calendar_events ORDER BY start_date LIMIT 5");
    
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>ID</th><th>Başlık</th><th>Tarih</th><th>BYK</th><th>Renk</th></tr></thead>";
    echo "<tbody>";
    foreach ($events as $event) {
        echo "<tr>";
        echo "<td>" . $event['id'] . "</td>";
        echo "<td>" . htmlspecialchars($event['title']) . "</td>";
        echo "<td>" . $event['start_date'] . "</td>";
        echo "<td>" . $event['byk_category'] . "</td>";
        echo "<td style='color: " . $event['color'] . ";'>" . $event['color'] . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Genel hata: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='admin/calendar.php' class='btn btn-primary'>Takvimi Görüntüle</a></p>";
?>
