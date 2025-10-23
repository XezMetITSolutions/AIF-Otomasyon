<?php
require_once 'admin/includes/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🏗️ Toplantı Yönetim Sistemi - Veritabanı Kurulumu</h2>";
    
    // 1. BYK Tablosu (eğer yoksa)
    $sql = "CREATE TABLE IF NOT EXISTS byk_units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(10) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        color VARCHAR(7) DEFAULT '#007bff',
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ BYK Units tablosu oluşturuldu/kontrol edildi<br>";
    
    // BYK verilerini ekle
    $bykData = [
        ['AT', 'Ana Teşkilat', '#1976d2'],
        ['KT', 'Kadınlar Teşkilatı', '#c2185b'],
        ['KGT', 'Kadınlar Gençlik Teşkilatı', '#7b1fa2'],
        ['GT', 'Gençlik Teşkilatı', '#388e3c']
    ];
    
    foreach ($bykData as $byk) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO byk_units (code, name, color) VALUES (?, ?, ?)");
        $stmt->execute($byk);
    }
    echo "✅ BYK verileri eklendi<br>";
    
    // 2. Toplantılar Tablosu (mevcut tabloyu genişlet)
    $sql = "CREATE TABLE IF NOT EXISTS meetings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        byk_code VARCHAR(10) NOT NULL,
        title VARCHAR(200) NOT NULL,
        meeting_date DATE NOT NULL,
        meeting_time TIME NOT NULL,
        end_time TIME,
        location VARCHAR(200) NOT NULL,
        chairman VARCHAR(100) NOT NULL,
        secretary VARCHAR(100),
        status ENUM('planned', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned',
        meeting_type ENUM('regular', 'emergency', 'special') DEFAULT 'regular',
        notes TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (byk_code) REFERENCES byk_units(code),
        INDEX idx_byk_date (byk_code, meeting_date),
        INDEX idx_status (status)
    )";
    
    $pdo->exec($sql);
    echo "✅ Meetings tablosu oluşturuldu/kontrol edildi<br>";
    
    // 3. Toplantı Katılımcıları Tablosu
    $sql = "CREATE TABLE IF NOT EXISTS meeting_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meeting_id INT NOT NULL,
        participant_name VARCHAR(100) NOT NULL,
        participant_role VARCHAR(50) DEFAULT 'member',
        attendance_status ENUM('invited', 'attended', 'absent', 'excused') DEFAULT 'invited',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
        INDEX idx_meeting (meeting_id),
        INDEX idx_status (attendance_status)
    )";
    
    $pdo->exec($sql);
    echo "✅ Meeting Participants tablosu oluşturuldu/kontrol edildi<br>";
    
    // 4. Gündem Maddeleri Tablosu
    $sql = "CREATE TABLE IF NOT EXISTS meeting_agenda (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meeting_id INT NOT NULL,
        agenda_order INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        responsible_person VARCHAR(100),
        estimated_duration INT DEFAULT 15,
        status ENUM('pending', 'discussed', 'completed', 'postponed') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
        INDEX idx_meeting_order (meeting_id, agenda_order),
        INDEX idx_status (status)
    )";
    
    $pdo->exec($sql);
    echo "✅ Meeting Agenda tablosu oluşturuldu/kontrol edildi<br>";
    
    // 5. Kararlar ve Görevler Tablosu
    $sql = "CREATE TABLE IF NOT EXISTS meeting_decisions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meeting_id INT NOT NULL,
        decision_number VARCHAR(20) NOT NULL,
        decision_text TEXT NOT NULL,
        responsible_person VARCHAR(100) NOT NULL,
        deadline DATE,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        progress_notes TEXT,
        completion_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
        INDEX idx_meeting (meeting_id),
        INDEX idx_status (status),
        INDEX idx_deadline (deadline),
        INDEX idx_responsible (responsible_person)
    )";
    
    $pdo->exec($sql);
    echo "✅ Meeting Decisions tablosu oluşturuldu/kontrol edildi<br>";
    
    // 6. Toplantı Dosyaları Tablosu
    $sql = "CREATE TABLE IF NOT EXISTS meeting_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meeting_id INT NOT NULL,
        file_name VARCHAR(200) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(50),
        file_size INT,
        uploaded_by INT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
        INDEX idx_meeting (meeting_id)
    )";
    
    $pdo->exec($sql);
    echo "✅ Meeting Files tablosu oluşturuldu/kontrol edildi<br>";
    
    // 7. Toplantı Bildirimleri Tablosu
    $sql = "CREATE TABLE IF NOT EXISTS meeting_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meeting_id INT NOT NULL,
        notification_type ENUM('reminder', 'deadline', 'completion', 'update') NOT NULL,
        recipient_email VARCHAR(200),
        recipient_name VARCHAR(100),
        subject VARCHAR(200),
        message TEXT,
        sent_at TIMESTAMP NULL,
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
        INDEX idx_meeting (meeting_id),
        INDEX idx_status (status),
        INDEX idx_type (notification_type)
    )";
    
    $pdo->exec($sql);
    echo "✅ Meeting Notifications tablosu oluşturuldu/kontrol edildi<br>";
    
    // Örnek veri ekleme
    echo "<h3>📊 Örnek Veri Ekleme</h3>";
    
    // Örnek toplantı
    $stmt = $pdo->prepare("INSERT INTO meetings (byk_code, title, meeting_date, meeting_time, end_time, location, chairman, secretary, status, meeting_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['AT', 'AT BYK Şubat Toplantısı', '2026-02-15', '14:00:00', '16:00:00', 'AIF Genel Merkez', 'Ahmet Yılmaz', 'Fatma Demir', 'completed', 'regular']);
    $meetingId = $pdo->lastInsertId();
    echo "✅ Örnek toplantı eklendi (ID: $meetingId)<br>";
    
    // Örnek katılımcılar
    $participants = [
        [$meetingId, 'Ahmet Yılmaz', 'chairman', 'attended'],
        [$meetingId, 'Fatma Demir', 'secretary', 'attended'],
        [$meetingId, 'Mehmet Kaya', 'member', 'attended'],
        [$meetingId, 'Ayşe Özkan', 'member', 'attended']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO meeting_participants (meeting_id, participant_name, participant_role, attendance_status) VALUES (?, ?, ?, ?)");
    foreach ($participants as $participant) {
        $stmt->execute($participant);
    }
    echo "✅ Örnek katılımcılar eklendi<br>";
    
    // Örnek gündem maddeleri
    $agendaItems = [
        [$meetingId, 1, 'Eğitim Çalışmaları', 'Yeni eğitim programlarının planlanması', 'Eğitim Sorumlusu', 30],
        [$meetingId, 2, 'Mali Durum', 'Aylık mali raporun değerlendirilmesi', 'Muhasebe Sorumlusu', 20],
        [$meetingId, 3, 'Yeni Proje Planı', 'Gelecek dönem projelerinin belirlenmesi', 'Başkan', 45]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO meeting_agenda (meeting_id, agenda_order, title, description, responsible_person, estimated_duration) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($agendaItems as $item) {
        $stmt->execute($item);
    }
    echo "✅ Örnek gündem maddeleri eklendi<br>";
    
    // Örnek kararlar
    $decisions = [
        [$meetingId, '2026-AT-01', 'Yeni eğitim programı başlatılacak', 'Eğitim Sorumlusu', '2026-03-15', 'high', 'in_progress'],
        [$meetingId, '2026-AT-02', 'Kadınlar iftar programı organize edilecek', 'Sosyal Hizmetler', '2026-04-10', 'medium', 'completed'],
        [$meetingId, '2026-AT-03', 'Gençlik kampı için bütçe ayrılacak', 'Mali İşler', '2026-05-01', 'high', 'pending']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO meeting_decisions (meeting_id, decision_number, decision_text, responsible_person, deadline, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($decisions as $decision) {
        $stmt->execute($decision);
    }
    echo "✅ Örnek kararlar eklendi<br>";
    
    echo "<h3>🎉 Toplantı Yönetim Sistemi başarıyla kuruldu!</h3>";
    echo "<p><strong>Oluşturulan Tablolar:</strong></p>";
    echo "<ul>";
    echo "<li>byk_units - BYK birimleri</li>";
    echo "<li>meetings - Toplantılar</li>";
    echo "<li>meeting_participants - Katılımcılar</li>";
    echo "<li>meeting_agenda - Gündem maddeleri</li>";
    echo "<li>meeting_decisions - Kararlar ve görevler</li>";
    echo "<li>meeting_files - Toplantı dosyaları</li>";
    echo "<li>meeting_notifications - Bildirimler</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Hata:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
