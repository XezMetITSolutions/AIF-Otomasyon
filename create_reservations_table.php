<?php
require_once 'admin/includes/database.php';

echo "<h2>Rezervasyon Tablosu Oluşturma</h2>";

try {
    $db = Database::getInstance();
    
    // Tablo var mı kontrol et
    $tableExists = false;
    try {
        $db->query("SELECT 1 FROM reservations LIMIT 1");
        $tableExists = true;
        echo "<p style='color: blue;'>ℹ️ 'reservations' tablosu zaten mevcut.</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ 'reservations' tablosu bulunamadı, oluşturuluyor...</p>";
    }
    
    if (!$tableExists) {
        // Tabloyu oluştur
        $sql = "
        CREATE TABLE reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            applicant_name VARCHAR(255) NOT NULL COMMENT 'Başvuran Ad Soyad',
            applicant_phone VARCHAR(20) NOT NULL COMMENT 'Başvuran Telefon',
            applicant_email VARCHAR(255) COMMENT 'Başvuran E-posta',
            region VARCHAR(100) NOT NULL COMMENT 'Bölge',
            unit VARCHAR(50) NOT NULL COMMENT 'Birim (AT, KT, KGT, GT)',
            event_name VARCHAR(255) NOT NULL COMMENT 'Etkinlik Adı',
            event_description TEXT COMMENT 'Etkinlik Açıklaması',
            expected_participants INT COMMENT 'Beklenen Katılımcı Sayısı',
            start_date DATE NOT NULL COMMENT 'Başlangıç Tarihi',
            end_date DATE NOT NULL COMMENT 'Bitiş Tarihi',
            status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending' COMMENT 'Durum',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Oluşturma Tarihi',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Güncelleme Tarihi'
        );";
        
        $db->query($sql);
        echo "<p style='color: green;'>✅ 'reservations' tablosu başarıyla oluşturuldu!</p>";
    }
    
    // Tablo yapısını göster
    echo "<h3>Tablo Yapısı:</h3>";
    $columns = $db->fetchAll("SHOW COLUMNS FROM reservations");
    
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
    $count = $db->fetchOne("SELECT COUNT(*) FROM reservations")['COUNT(*)'];
    if ($count == 0) {
        echo "<h3>Örnek Veri Ekleme:</h3>";
        
        $sampleReservations = [
            [
                'applicant_name' => 'Ahmet Yılmaz',
                'applicant_phone' => '+43 123 456 7890',
                'applicant_email' => 'ahmet@example.com',
                'region' => 'tirol',
                'unit' => 'AT',
                'event_name' => 'Gençlik Kampı',
                'event_description' => 'Yaz gençlik kampı etkinliği',
                'expected_participants' => 50,
                'start_date' => '2025-07-15',
                'end_date' => '2025-07-17',
                'status' => 'pending'
            ],
            [
                'applicant_name' => 'Fatma Demir',
                'applicant_phone' => '+43 987 654 3210',
                'applicant_email' => 'fatma@example.com',
                'region' => 'vorarlberg',
                'unit' => 'KT',
                'event_name' => 'Kadınlar Semineri',
                'event_description' => 'Aile ve çocuk eğitimi semineri',
                'expected_participants' => 30,
                'start_date' => '2025-08-10',
                'end_date' => '2025-08-10',
                'status' => 'approved'
            ]
        ];
        
        foreach ($sampleReservations as $reservation) {
            $db->insert('reservations', [
                'applicant_name' => $reservation['applicant_name'],
                'applicant_phone' => $reservation['applicant_phone'],
                'applicant_email' => $reservation['applicant_email'],
                'region' => $reservation['region'],
                'unit' => $reservation['unit'],
                'event_name' => $reservation['event_name'],
                'event_description' => $reservation['event_description'],
                'expected_participants' => $reservation['expected_participants'],
                'start_date' => $reservation['start_date'],
                'end_date' => $reservation['end_date'],
                'status' => $reservation['status'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        echo "<p style='color: green;'>✅ 2 örnek rezervasyon eklendi.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Tablo zaten veri içeriyor (" . $count . " rezervasyon), örnek veri eklenmedi.</p>";
    }
    
    echo "<hr>";
    echo "<h3>Sonraki Adımlar:</h3>";
    echo "<p><a href='admin/reservations.php' class='btn btn-primary'>Rezervasyon Sayfasını Test Et</a></p>";
    echo "<p><a href='admin/calendar.php' class='btn btn-secondary'>Takvimi Görüntüle</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Genel hata: " . $e->getMessage() . "</p>";
}
?>
