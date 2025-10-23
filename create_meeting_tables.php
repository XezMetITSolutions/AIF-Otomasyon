<?php
require_once 'admin/includes/database.php';

echo "<h2>Toplantı Tabloları Oluşturma</h2>";

try {
    $db = Database::getInstance();
    
    // Ana toplantı tablosu
    echo "<p style='color: blue;'>ℹ️ 'meetings' tablosu kontrol ediliyor...</p>";
    
    try {
        $db->query("SELECT 1 FROM meetings LIMIT 1");
        echo "<p style='color: green;'>✅ 'meetings' tablosu zaten mevcut.</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ 'meetings' tablosu bulunamadı, oluşturuluyor...</p>";
        
        $sql = "
        CREATE TABLE meetings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL COMMENT 'Toplantı Başlığı',
            byk_code VARCHAR(10) NOT NULL COMMENT 'BYK Kodu',
            meeting_date DATE NOT NULL COMMENT 'Toplantı Tarihi',
            meeting_time TIME NOT NULL COMMENT 'Toplantı Saati',
            location VARCHAR(255) COMMENT 'Toplantı Yeri/Platform',
            unit VARCHAR(100) COMMENT 'Birim',
            status ENUM('planned', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned' COMMENT 'Durum',
            agenda TEXT COMMENT 'Gündem',
            notes TEXT COMMENT 'Toplantı Notları',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Oluşturma Tarihi',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Güncelleme Tarihi'
        );";
        
        $db->query($sql);
        echo "<p style='color: green;'>✅ 'meetings' tablosu başarıyla oluşturuldu!</p>";
    }
    
    // Toplantı katılımcıları tablosu
    echo "<p style='color: blue;'>ℹ️ 'meeting_participants' tablosu kontrol ediliyor...</p>";
    
    try {
        $db->query("SELECT 1 FROM meeting_participants LIMIT 1");
        echo "<p style='color: green;'>✅ 'meeting_participants' tablosu zaten mevcut.</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ 'meeting_participants' tablosu bulunamadı, oluşturuluyor...</p>";
        
        $sql = "
        CREATE TABLE meeting_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            meeting_id INT NOT NULL COMMENT 'Toplantı ID',
            user_id INT COMMENT 'Kullanıcı ID',
            name VARCHAR(255) NOT NULL COMMENT 'Katılımcı Adı',
            role VARCHAR(50) COMMENT 'Rol (Başkan, Sekreter, Katılımcı)',
            attendance ENUM('present', 'absent', 'excused') DEFAULT 'present' COMMENT 'Katılım Durumu',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Oluşturma Tarihi',
            FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE
        );";
        
        $db->query($sql);
        echo "<p style='color: green;'>✅ 'meeting_participants' tablosu başarıyla oluşturuldu!</p>";
    }
    
    // Toplantı gündemi tablosu
    echo "<p style='color: blue;'>ℹ️ 'meeting_agenda' tablosu kontrol ediliyor...</p>";
    
    try {
        $db->query("SELECT 1 FROM meeting_agenda LIMIT 1");
        echo "<p style='color: green;'>✅ 'meeting_agenda' tablosu zaten mevcut.</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ 'meeting_agenda' tablosu bulunamadı, oluşturuluyor...</p>";
        
        $sql = "
        CREATE TABLE meeting_agenda (
            id INT AUTO_INCREMENT PRIMARY KEY,
            meeting_id INT NOT NULL COMMENT 'Toplantı ID',
            item_number INT NOT NULL COMMENT 'Madde Numarası',
            title VARCHAR(255) NOT NULL COMMENT 'Gündem Başlığı',
            description TEXT COMMENT 'Açıklama',
            discussion TEXT COMMENT 'Tartışma',
            status ENUM('pending', 'discussed', 'completed') DEFAULT 'pending' COMMENT 'Durum',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Oluşturma Tarihi',
            FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE
        );";
        
        $db->query($sql);
        echo "<p style='color: green;'>✅ 'meeting_agenda' tablosu başarıyla oluşturuldu!</p>";
    }
    
    // Toplantı kararları tablosu
    echo "<p style='color: blue;'>ℹ️ 'meeting_decisions' tablosu kontrol ediliyor...</p>";
    
    try {
        $db->query("SELECT 1 FROM meeting_decisions LIMIT 1");
        echo "<p style='color: green;'>✅ 'meeting_decisions' tablosu zaten mevcut.</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ 'meeting_decisions' tablosu bulunamadı, oluşturuluyor...</p>";
        
        $sql = "
        CREATE TABLE meeting_decisions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            meeting_id INT NOT NULL COMMENT 'Toplantı ID',
            decision_number INT NOT NULL COMMENT 'Karar Numarası',
            title VARCHAR(255) NOT NULL COMMENT 'Karar Başlığı',
            description TEXT NOT NULL COMMENT 'Karar Açıklaması',
            responsible_person VARCHAR(255) COMMENT 'Sorumlu Kişi',
            deadline DATE COMMENT 'Son Tarih',
            status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending' COMMENT 'Durum',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Oluşturma Tarihi',
            FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE
        );";
        
        $db->query($sql);
        echo "<p style='color: green;'>✅ 'meeting_decisions' tablosu başarıyla oluşturuldu!</p>";
    }
    
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
    
    // Tablo yapısını göster
    echo "<h3>Meetings Tablo Yapısı:</h3>";
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
    
    echo "<hr>";
    echo "<h3>Sonraki Adımlar:</h3>";
    echo "<p><a href='admin/meeting_reports.php' class='btn btn-primary'>Toplantı Sayfasını Test Et</a></p>";
    echo "<p><a href='admin/calendar.php' class='btn btn-secondary'>Takvimi Görüntüle</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Genel hata: " . $e->getMessage() . "</p>";
}
?>
