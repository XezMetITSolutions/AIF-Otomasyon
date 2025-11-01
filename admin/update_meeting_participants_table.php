<?php
/**
 * Meeting Participants Tablosunu Güncelle
 * Katılım yanıtı ve mazeret bildirimi için gerekli alanları ekler
 */
require_once 'includes/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>📝 Meeting Participants Tablosu Güncelleniyor</h2>";
    
    // Yeni kolonları kontrol et ve ekle
    $columnsToAdd = [
        [
            'name' => 'response_status',
            'definition' => "ENUM('pending', 'accepted', 'declined') DEFAULT 'pending'"
        ],
        [
            'name' => 'response_date',
            'definition' => 'TIMESTAMP NULL'
        ],
        [
            'name' => 'excuse_reason',
            'definition' => 'TEXT NULL'
        ],
        [
            'name' => 'response_token',
            'definition' => "VARCHAR(100) NULL UNIQUE"
        ],
        [
            'name' => 'participant_email',
            'definition' => 'VARCHAR(200) NULL'
        ],
        [
            'name' => 'user_id',
            'definition' => 'INT NULL'
        ]
    ];
    
    // Mevcut kolonları kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM meeting_participants");
    $existingColumns = [];
    while ($row = $stmt->fetch()) {
        $existingColumns[strtolower($row['Field'])] = true;
    }
    
    // Eksik kolonları ekle
    foreach ($columnsToAdd as $column) {
        $columnName = strtolower($column['name']);
        if (!isset($existingColumns[$columnName])) {
            $sql = "ALTER TABLE meeting_participants ADD COLUMN {$column['name']} {$column['definition']}";
            $pdo->exec($sql);
            echo "✅ '{$column['name']}' kolonu eklendi<br>";
        } else {
            echo "ℹ️ '{$column['name']}' kolonu zaten mevcut<br>";
        }
    }
    
    // İndeksler ekle
    try {
        $pdo->exec("CREATE INDEX idx_response_status ON meeting_participants(response_status)");
        echo "✅ İndeks eklendi: response_status<br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key') === false) {
            echo "ℹ️ İndeks zaten mevcut veya oluşturulamadı<br>";
        }
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_response_token ON meeting_participants(response_token)");
        echo "✅ İndeks eklendi: response_token<br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key') === false) {
            echo "ℹ️ İndeks zaten mevcut veya oluşturulamadı<br>";
        }
    }
    
    // attendance_status ENUM'ını genişlet (eğer gerekirse)
    $sql = "ALTER TABLE meeting_participants 
            MODIFY COLUMN attendance_status 
            ENUM('invited', 'accepted', 'declined', 'attended', 'absent', 'excused') 
            DEFAULT 'invited'";
    try {
        $pdo->exec($sql);
        echo "✅ attendance_status ENUM güncellendi<br>";
    } catch (Exception $e) {
        echo "ℹ️ attendance_status zaten güncel veya güncellenemedi: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>🎉 Tablo başarıyla güncellendi!</h3>";
    
} catch (Exception $e) {
    echo "<h3>❌ Hata:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

