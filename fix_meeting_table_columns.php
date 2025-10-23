<?php
require_once 'admin/includes/database.php';

echo "<h2>Toplantı Tablosu Kolonları Düzeltme</h2>";

try {
    $db = Database::getInstance();
    
    // Mevcut tablo yapısını kontrol et
    echo "<h3>Mevcut Meetings Tablo Yapısı:</h3>";
    $columns = $db->fetchAll("SHOW COLUMNS FROM meetings");
    
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
    
    // Eksik kolonları ekle
    echo "<h3>Eksik Kolonları Ekleme:</h3>";
    
    $requiredColumns = [
        'byk_code' => "VARCHAR(10) NOT NULL COMMENT 'BYK Kodu'",
        'meeting_date' => "DATE NOT NULL COMMENT 'Toplantı Tarihi'",
        'meeting_time' => "TIME NOT NULL COMMENT 'Toplantı Saati'",
        'location' => "VARCHAR(255) COMMENT 'Toplantı Yeri/Platform'",
        'unit' => "VARCHAR(100) COMMENT 'Birim'",
        'status' => "ENUM('planned', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned' COMMENT 'Durum'",
        'agenda' => "TEXT COMMENT 'Gündem'",
        'notes' => "TEXT COMMENT 'Toplantı Notları'"
    ];
    
    foreach ($requiredColumns as $columnName => $columnDefinition) {
        try {
            // Kolon var mı kontrol et
            $checkSql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_NAME = 'meetings' 
                        AND COLUMN_NAME = ? 
                        AND TABLE_SCHEMA = DATABASE()";
            $exists = $db->fetchOne($checkSql, [$columnName])['COUNT(*)'];
            
            if ($exists == 0) {
                echo "<p style='color: orange;'>⚠️ '$columnName' kolonu eksik, ekleniyor...</p>";
                
                $alterSql = "ALTER TABLE meetings ADD COLUMN $columnName $columnDefinition";
                $db->query($alterSql);
                
                echo "<p style='color: green;'>✅ '$columnName' kolonu başarıyla eklendi!</p>";
            } else {
                echo "<p style='color: blue;'>ℹ️ '$columnName' kolonu zaten mevcut.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ '$columnName' kolonu eklenirken hata: " . $e->getMessage() . "</p>";
        }
    }
    
    // Güncellenmiş tablo yapısını göster
    echo "<h3>Güncellenmiş Meetings Tablo Yapısı:</h3>";
    $updatedColumns = $db->fetchAll("SHOW COLUMNS FROM meetings");
    
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Kolon Adı</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
    echo "<tbody>";
    foreach ($updatedColumns as $column) {
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
    $count = $db->fetchOne("SELECT COUNT(*) FROM meetings")['COUNT(*)'];
    if ($count == 0) {
        echo "<h3>Örnek Veri Ekleme:</h3>";
        
        $sampleMeetings = [
            [
                'title' => 'AT BYK Mart Toplantısı',
                'byk_code' => 'AT',
                'meeting_date' => '2025-03-15',
                'meeting_time' => '14:00:00',
                'location' => 'Merkez Ofis',
                'unit' => 'Ana Teşkilat',
                'status' => 'planned',
                'agenda' => 'Mart ayı faaliyetleri değerlendirmesi'
            ],
            [
                'title' => 'KT BYK Nisan Toplantısı',
                'byk_code' => 'KT',
                'meeting_date' => '2025-04-10',
                'meeting_time' => '10:00:00',
                'location' => 'Online - Zoom',
                'unit' => 'Kadınlar Teşkilatı',
                'status' => 'planned',
                'agenda' => 'Nisan ayı programları planlaması'
            ]
        ];
        
        foreach ($sampleMeetings as $meeting) {
            $db->insert('meetings', [
                'title' => $meeting['title'],
                'byk_code' => $meeting['byk_code'],
                'meeting_date' => $meeting['meeting_date'],
                'meeting_time' => $meeting['meeting_time'],
                'location' => $meeting['location'],
                'unit' => $meeting['unit'],
                'status' => $meeting['status'],
                'agenda' => $meeting['agenda'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        echo "<p style='color: green;'>✅ " . count($sampleMeetings) . " örnek toplantı eklendi.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Tablo zaten veri içeriyor (" . $count . " toplantı), örnek veri eklenmedi.</p>";
    }
    
    echo "<hr>";
    echo "<h3>Sonraki Adımlar:</h3>";
    echo "<p><a href='admin/meeting_reports.php' class='btn btn-primary'>Toplantı Sayfasını Test Et</a></p>";
    echo "<p><a href='admin/calendar.php' class='btn btn-secondary'>Takvimi Görüntüle</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Genel hata: " . $e->getMessage() . "</p>";
}
?>