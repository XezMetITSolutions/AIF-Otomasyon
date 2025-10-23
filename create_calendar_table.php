<?php
require_once 'admin/includes/database.php';

echo "<h2>Takvim Tablosu Oluşturma</h2>";

try {
    $db = Database::getInstance();
    
    // Tablo var mı kontrol et
    $tableExists = false;
    try {
        $db->query("SELECT 1 FROM calendar_events LIMIT 1");
        $tableExists = true;
        echo "<p style='color: blue;'>ℹ️ 'calendar_events' tablosu zaten mevcut.</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ 'calendar_events' tablosu bulunamadı, oluşturuluyor...</p>";
    }
    
    if (!$tableExists) {
        // Tabloyu oluştur
        $sql = "
        CREATE TABLE calendar_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            start_date DATE NOT NULL,
            end_date DATE,
            byk_category VARCHAR(50) DEFAULT NULL COMMENT 'BYK Kategorisi (AT, KT, KGT, GT)',
            color VARCHAR(7) DEFAULT NULL COMMENT 'Renk kodu (#dc3545 gibi)',
            is_recurring BOOLEAN DEFAULT FALSE COMMENT 'Tekrarlayan etkinlik mi?',
            recurrence_type ENUM('daily', 'weekly', 'monthly', 'yearly', 'custom') DEFAULT NULL COMMENT 'Tekrar tipi',
            recurrence_value VARCHAR(255) DEFAULT NULL COMMENT 'Tekrar değeri',
            recurrence_end_date DATE DEFAULT NULL COMMENT 'Tekrar bitiş tarihi',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );";
        
        $db->query($sql);
        echo "<p style='color: green;'>✅ 'calendar_events' tablosu başarıyla oluşturuldu!</p>";
    }
    
    // Tablo yapısını göster
    echo "<h3>Tablo Yapısı:</h3>";
    $columns = $db->fetchAll("SHOW COLUMNS FROM calendar_events");
    
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Kolon Adı</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
    echo "<tbody>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    
    // Örnek veri ekleme (sadece tablo boşsa)
    $count = $db->fetchOne("SELECT COUNT(*) FROM calendar_events")['COUNT(*)'];
    if ($count == 0) {
        echo "<h3>Örnek Veri Ekleme:</h3>";
        
        $sampleEvents = [
            [
                'title' => 'Sabah Namazı Prog.',
                'description' => 'Aylık sabah namazı programı',
                'start_date' => '2026-01-04',
                'end_date' => '2026-01-04',
                'byk_category' => 'AT',
                'color' => '#dc3545'
            ],
            [
                'title' => '1. BKT',
                'description' => 'Bölge Koordinasyon Toplantısı',
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-10',
                'byk_category' => 'AT',
                'color' => '#dc3545'
            ],
            [
                'title' => 'Miraç Kandili',
                'description' => 'Miraç Kandili özel programı',
                'start_date' => '2026-01-15',
                'end_date' => '2026-01-15',
                'byk_category' => 'AT',
                'color' => '#dc3545'
            ]
        ];
        
        foreach ($sampleEvents as $event) {
            $db->insert('calendar_events', [
                'title' => $event['title'],
                'description' => $event['description'],
                'start_date' => $event['start_date'],
                'end_date' => $event['end_date'],
                'byk_category' => $event['byk_category'],
                'color' => $event['color'],
                'is_recurring' => 0,
                'recurrence_type' => null,
                'recurrence_value' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        echo "<p style='color: green;'>✅ 3 örnek etkinlik eklendi.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Tablo zaten veri içeriyor (" . $count . " etkinlik), örnek veri eklenmedi.</p>";
    }
    
    echo "<hr>";
    echo "<h3>Sonraki Adımlar:</h3>";
    echo "<p><a href='import_2026_events.php' class='btn btn-primary'>2026 Etkinliklerini İçe Aktar</a></p>";
    echo "<p><a href='admin/calendar.php' class='btn btn-secondary'>Takvimi Görüntüle</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Genel hata: " . $e->getMessage() . "</p>";
}
?>
