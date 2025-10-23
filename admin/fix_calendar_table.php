<?php
require_once 'includes/database.php';

echo "<h2>Takvim Tablosu Düzeltme</h2>";

try {
    $db = Database::getInstance();
    
    // Mevcut tablo yapısını kontrol et
    echo "<h3>Mevcut Tablo Yapısı:</h3>";
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
    
    // Eksik kolonları ekle
    echo "<h3>Eksik Kolonları Ekleme:</h3>";
    
    $requiredColumns = [
        'byk_category' => "VARCHAR(50) DEFAULT NULL COMMENT 'BYK Kategorisi (AT, KT, KGT, GT)'",
        'color' => "VARCHAR(7) DEFAULT NULL COMMENT 'Renk kodu (#dc3545 gibi)'",
        'is_recurring' => "BOOLEAN DEFAULT FALSE COMMENT 'Tekrarlayan etkinlik mi?'",
        'recurrence_type' => "ENUM('daily', 'weekly', 'monthly', 'yearly', 'custom') DEFAULT NULL COMMENT 'Tekrar tipi'",
        'recurrence_value' => "VARCHAR(255) DEFAULT NULL COMMENT 'Tekrar değeri'",
        'recurrence_end_date' => "DATE DEFAULT NULL COMMENT 'Tekrar bitiş tarihi'"
    ];
    
    foreach ($requiredColumns as $columnName => $definition) {
        // Kolon var mı kontrol et
        $exists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $columnName) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            try {
                $sql = "ALTER TABLE calendar_events ADD COLUMN `$columnName` $definition";
                $db->query($sql);
                echo "<p style='color: green;'>✅ '$columnName' kolonu eklendi</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ '$columnName' kolonu eklenirken hata: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ '$columnName' kolonu zaten mevcut</p>";
        }
    }
    
    // Güncellenmiş tablo yapısını göster
    echo "<h3>Güncellenmiş Tablo Yapısı:</h3>";
    $updatedColumns = $db->fetchAll("SHOW COLUMNS FROM calendar_events");
    
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
    
    echo "<hr>";
    echo "<h3>Sonraki Adımlar:</h3>";
    echo "<p><a href='import_2026_events.php' class='btn btn-primary'>2026 Etkinliklerini İçe Aktar</a></p>";
    echo "<p><a href='calendar.php' class='btn btn-secondary'>Takvimi Görüntüle</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Genel hata: " . $e->getMessage() . "</p>";
}
?>
