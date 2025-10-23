<?php
require_once 'includes/database.php';

echo "<h2>Takvim Etkinlikleri Tablosu Oluşturuluyor</h2>";

try {
    $db = Database::getInstance();
    
    // Events tablosu oluştur
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NULL,
        byk_category VARCHAR(10) NOT NULL,
        description TEXT NULL,
        is_recurring TINYINT(1) DEFAULT 0,
        recurrence_type ENUM('none', 'daily', 'weekly', 'monthly', 'yearly') DEFAULT 'none',
        recurrence_pattern VARCHAR(100) NULL,
        recurrence_end_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $result = $db->query($createTableSQL);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Events tablosu başarıyla oluşturuldu!</p>";
        
        // Mevcut etkinlikleri veritabanına ekle
        $events_2026 = [
            // OCAK 2026
            ['date' => '2026-01-02', 'title' => 'Kış Tatil Kursu (Kızlar)', 'byk' => 'KT', 'color' => '#6f42c1'],
            ['date' => '2026-01-03', 'title' => 'Kış Tatil Kursu (Kızlar)', 'byk' => 'KT', 'color' => '#6f42c1'],
            ['date' => '2026-01-04', 'title' => 'Sabah Namazı Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-04', 'title' => 'Kış Tatil Kursu (Kızlar)', 'byk' => 'KT', 'color' => '#6f42c1'],
            ['date' => '2026-01-05', 'title' => 'Kış Tatil Kursu (Kızlar)', 'byk' => 'KT', 'color' => '#6f42c1'],
            ['date' => '2026-01-09', 'title' => 'İlk Yardım Kursu (İYSHB) - KGT GES', 'byk' => 'KGT', 'color' => '#198754'],
            ['date' => '2026-01-10', 'title' => '1. BKT', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-10', 'title' => 'KGT GES', 'byk' => 'KGT', 'color' => '#198754'],
            ['date' => '2026-01-11', 'title' => '1. BKT', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-11', 'title' => '1. GT ŞBT', 'byk' => 'GT', 'color' => '#0d6efd'],
            ['date' => '2026-01-11', 'title' => 'Hac & Umre Okulu', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-15', 'title' => 'Miraç Kandili', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-17', 'title' => '1. KT BBT', 'byk' => 'KT', 'color' => '#6f42c1'],
            ['date' => '2026-01-17', 'title' => '1. KGT BBT', 'byk' => 'KGT', 'color' => '#198754'],
            ['date' => '2026-01-18', 'title' => '6. Meslek Eğitim Fuarı', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-18', 'title' => '1. BBT', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-18', 'title' => 'BHUSBT', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-18', 'title' => '1. KT BBT', 'byk' => 'KT', 'color' => '#6f42c1'],
            ['date' => '2026-01-18', 'title' => '1. KGT BBT', 'byk' => 'KGT', 'color' => '#198754'],
            ['date' => '2026-01-20', 'title' => 'İmamlar Toplantısı', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-23', 'title' => 'İrşad Progr.', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-24', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-24', 'title' => 'BHUSBT', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-24', 'title' => 'Tems. B.', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-24', 'title' => '1. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
            ['date' => '2026-01-25', 'title' => '1. ŞBT', 'byk' => 'AT', 'color' => '#dc3545'],
            ['date' => '2026-01-25', 'title' => 'KGT Turnuva', 'byk' => 'KGT', 'color' => '#198754'],
            ['date' => '2026-01-25', 'title' => '1. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
            ['date' => '2026-01-31', 'title' => 'ŞB-YES Güney - GT Sabah Namazı', 'byk' => 'GT', 'color' => '#0d6efd']
        ];
        
        $inserted = 0;
        foreach ($events_2026 as $event) {
            $insertSQL = "INSERT INTO events (title, start_date, end_date, byk_category, description) VALUES (?, ?, ?, ?, ?)";
            $result = $db->query($insertSQL, [
                $event['title'],
                $event['date'],
                $event['date'], // end_date = start_date for single day events
                $event['byk'],
                ''
            ]);
            
            if ($result) {
                $inserted++;
            }
        }
        
        echo "<p style='color: green;'>✅ {$inserted} etkinlik veritabanına eklendi!</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Events tablosu oluşturulamadı!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}

echo "<p><a href='calendar.php'>Takvim Sayfasına Dön</a></p>";
?>
