<?php
/**
 * Notification Helper Class
 * Handles creating and managing user notifications
 */

class Notification
{

    /**
     * Add a new notification
     * 
     * @param int $userId Target user ID
     * @param string $title Notification title
     * @param string $message Notification body
     * @param string $type Type: 'bilgi', 'uyari', 'basarili', 'hata'
     * @param string|null $link Optional link to redirect
     * @return bool Success status
     */
    public static function add($userId, $title, $message, $type = 'bilgi', $link = null, $sendEmail = true)
    {
        $db = Database::getInstance();

        try {
            // 1. Veritabanına Ekle
            $sql = "INSERT INTO bildirimler (kullanici_id, baslik, mesaj, tip, link, okundu, olusturma_tarihi) 
                    VALUES (?, ?, ?, ?, ?, 0, NOW())";

            $db->query($sql, [$userId, $title, $message, $type, $link]);

            // 2. E-posta Gönder (Eğer isteniyorsa)
            if ($sendEmail) {
                // Config ve Mail sınıflarının yüklü olduğundan emin olalım (init.php otomatik yükler ama yine de kontrol)
                if (class_exists('Mail') && class_exists('Config')) {
                    $user = $db->fetch("SELECT email, ad, soyad FROM kullanicilar WHERE kullanici_id = ?", [$userId]);

                    if ($user && !empty($user['email'])) {
                        $appName = Config::get('app_name', 'AİFNET');
                        $appUrl = rtrim(Config::get('app_url', 'https://aifnet.islamfederasyonu.at'), '/');
                        $fullLink = $link ? (strpos($link, 'http') === 0 ? $link : $appUrl . '/' . ltrim($link, '/')) : $appUrl;

                        $subject = "Bildirim: " . $title;

                        // Basit HTML Şablonu
                        $emailBody = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;'>
                            <h2 style='color: #00936F; margin-top: 0;'>{$title}</h2>
                            <p>Sayın <strong>{$user['ad']} {$user['soyad']}</strong>,</p>
                            <p>{$message}</p>
                            " . ($link ? "<p><a href='{$fullLink}' style='background-color: #00936F; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block;'>Görüntüle</a></p>" : "") . "
                            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #999;'>Bu e-posta {$appName} tarafından otomatik olarak gönderilmiştir.</p>
                        </div>";

                        Mail::send($user['email'], $subject, $emailBody);
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            // Log error silently usually, or return false
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark a notification as read
     */
    public static function markAsRead($notificationId, $userId)
    {
        $db = Database::getInstance();
        $db->query("UPDATE bildirimler SET okundu = 1 WHERE bildirim_id = ? AND kullanici_id = ?", [$notificationId, $userId]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsRead($userId)
    {
        $db = Database::getInstance();
        $db->query("UPDATE bildirimler SET okundu = 1 WHERE kullanici_id = ?", [$userId]);
    }
}
