<?php
/**
 * Bildirim Helper Sınıfı
 * Tarayıcı bildirimleri ve in-app bildirimler
 */
class NotificationHelper {
    
    /**
     * Toplantı bildirimi kaydet
     */
    public static function createMeetingNotification($db, $meetingId, $userId, $type, $message) {
        try {
            $sql = "INSERT INTO notifications 
                    (user_id, type, title, message, related_id, related_type, created_at)
                    VALUES (?, ?, ?, ?, ?, 'meeting', NOW())";
            
            $title = '';
            switch ($type) {
                case 'invitation':
                    $title = 'Toplantı Daveti';
                    break;
                case 'reminder':
                    $title = 'Toplantı Hatırlatması';
                    break;
                case 'response':
                    $title = 'Katılım Yanıtı';
                    break;
                default:
                    $title = 'Toplantı Bildirimi';
            }
            
            $stmt = $db->getConnection()->prepare($sql);
            return $stmt->execute([
                $userId,
                $type,
                $title,
                $message,
                $meetingId
            ]);
        } catch (Exception $e) {
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının okunmamış bildirimlerini getir
     */
    public static function getUnreadNotifications($db, $userId) {
        try {
            $sql = "SELECT * FROM notifications 
                    WHERE user_id = ? AND is_read = 0 
                    ORDER BY created_at DESC 
                    LIMIT 50";
            
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get notifications error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Bildirimi okundu olarak işaretle
     */
    public static function markAsRead($db, $notificationId, $userId) {
        try {
            $sql = "UPDATE notifications 
                    SET is_read = 1, read_at = NOW()
                    WHERE id = ? AND user_id = ?";
            
            $stmt = $db->getConnection()->prepare($sql);
            return $stmt->execute([$notificationId, $userId]);
        } catch (Exception $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * JavaScript bildirim kodu üret
     */
    public static function getNotificationScript($userId) {
        return "
        <script>
        // Bildirim izni iste
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
        // Bildirimleri kontrol et
        function checkNotifications() {
            fetch('api/notifications.php?action=get_unread')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.notifications.length > 0) {
                        data.notifications.forEach(notification => {
                            if (Notification.permission === 'granted') {
                                new Notification(notification.title, {
                                    body: notification.message,
                                    icon: '/admin/assets/img/logo.png',
                                    badge: '/admin/assets/img/logo.png',
                                    tag: 'meeting-' + notification.id,
                                    requireInteraction: false
                                });
                            }
                        });
                    }
                })
                .catch(error => console.error('Notification check error:', error));
        }
        
        // 5 dakikada bir kontrol et
        setInterval(checkNotifications, 5 * 60 * 1000);
        
        // Sayfa yüklendiğinde kontrol et
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkNotifications);
        } else {
            checkNotifications();
        }
        </script>
        ";
    }
}
?>

