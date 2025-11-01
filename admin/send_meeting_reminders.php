<?php
/**
 * Toplantı Hatırlatma Email Gönderme Scripti
 * Toplantıdan 24 saat önce katılımcılara hatırlatma emaili gönderir
 * Cron job ile çalıştırılabilir: php admin/send_meeting_reminders.php
 */
require_once 'includes/database.php';
require_once 'includes/email_helper.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Yarın yapılacak toplantıları bul (24 saat öncesi)
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    $sql = "
        SELECT DISTINCT m.*
        FROM meetings m
        WHERE m.meeting_date = ?
        AND m.status = 'planned'
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tomorrow]);
    $meetings = $stmt->fetchAll();
    
    $totalEmailsSent = 0;
    
    foreach ($meetings as $meeting) {
        // Toplantının katılımcılarını bul
        $participantsSql = "
            SELECT mp.*, u.email, u.full_name
            FROM meeting_participants mp
            LEFT JOIN users u ON mp.user_id = u.id
            WHERE mp.meeting_id = ?
            AND mp.response_status IN ('pending', 'accepted')
            AND mp.participant_email IS NOT NULL AND mp.participant_email != ''
        ";
        
        $participantsStmt = $pdo->prepare($participantsSql);
        $participantsStmt->execute([$meeting['id']]);
        $participants = $participantsStmt->fetchAll();
        
        foreach ($participants as $participant) {
            // Email adresini belirle
            $email = $participant['participant_email'] ?? $participant['email'] ?? '';
            if (empty($email)) {
                continue;
            }
            
            $participantData = [
                'participant_name' => $participant['participant_name'] ?? $participant['full_name'] ?? '',
                'participant_email' => $email,
                'email' => $email
            ];
            
            // Hatırlatma emaili gönder
            if (EmailHelper::sendMeetingReminder($participantData, $meeting)) {
                $totalEmailsSent++;
                
                // Bildirimi kaydet
                $notificationSql = "
                    INSERT INTO meeting_notifications 
                    (meeting_id, notification_type, recipient_email, recipient_name, subject, status, sent_at)
                    VALUES (?, 'reminder', ?, ?, ?, 'sent', NOW())
                ";
                $notificationStmt = $pdo->prepare($notificationSql);
                $notificationStmt->execute([
                    $meeting['id'],
                    $email,
                    $participantData['participant_name'],
                    'Hatırlatma: ' . $meeting['title']
                ]);
            }
        }
    }
    
    echo "Toplam " . count($meetings) . " toplantı için " . $totalEmailsSent . " hatırlatma emaili gönderildi.\n";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
    error_log("Meeting reminder error: " . $e->getMessage());
}
?>

