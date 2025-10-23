<?php
require_once 'admin/includes/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔧 Toplantı Tablosu Düzeltme</h2>";
    
    // Meetings tablosuna eksik sütunları ekle
    $columns = [
        'end_time TIME',
        'meeting_type ENUM("regular", "emergency", "special") DEFAULT "regular"',
        'notes TEXT'
    ];
    
    foreach ($columns as $column) {
        try {
            $sql = "ALTER TABLE meetings ADD COLUMN $column";
            $pdo->exec($sql);
            echo "✅ Sütun eklendi: $column<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️ Sütun zaten mevcut: $column<br>";
            } else {
                echo "❌ Hata: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    // Örnek veri ekleme
    echo "<h3>📊 Örnek Veri Ekleme</h3>";
    
    // Örnek toplantı
    $stmt = $pdo->prepare("INSERT INTO meetings (byk_code, title, meeting_date, meeting_time, end_time, location, chairman, secretary, status, meeting_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['AT', 'AT BYK Şubat Toplantısı', '2026-02-15', '14:00:00', '16:00:00', 'AIF Genel Merkez', 'Ahmet Yılmaz', 'Fatma Demir', 'completed', 'regular', 'Şubat ayı değerlendirme toplantısı']);
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
    
    echo "<h3>🎉 Toplantı sistemi başarıyla kuruldu!</h3>";
    echo "<p><strong>Artık toplantı yönetim sistemini kullanabilirsiniz:</strong></p>";
    echo "<p><a href='admin/meeting_reports.php' class='btn btn-primary'>Toplantı Yönetimi Sayfasına Git</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Hata:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
