<?php
/**
 * Email Helper Sınıfı
 * SMTP ile email gönderme işlemleri
 */
require_once __DIR__ . '/../config.php';

class EmailHelper {
    private static $smtpHost;
    private static $smtpPort;
    private static $smtpUsername;
    private static $smtpPassword;
    private static $fromEmail;
    private static $fromName;
    
    /**
     * SMTP ayarlarını başlat
     */
    private static function init() {
        self::$smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        self::$smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
        self::$smtpUsername = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        self::$smtpPassword = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        self::$fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@aifcrm.metechnik.at';
        self::$fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'AIF Otomasyon';
    }
    
    /**
     * Email gönder (PHP mail() fonksiyonu ile - basit versiyon)
     */
    public static function sendEmail($to, $subject, $body, $isHTML = true) {
        self::init();
        
        // Eğer SMTP bilgileri yoksa PHP mail() kullan
        if (empty(self::$smtpUsername) || empty(self::$smtpPassword)) {
            return self::sendEmailSimple($to, $subject, $body, $isHTML);
        }
        
        // PHPMailer veya benzeri bir kütüphane kullanılabilir
        // Şimdilik basit mail() fonksiyonu ile devam ediyoruz
        return self::sendEmailSimple($to, $subject, $body, $isHTML);
    }
    
    /**
     * Basit email gönderme (PHP mail() ile)
     */
    private static function sendEmailSimple($to, $subject, $body, $isHTML = true) {
        $headers = [];
        $headers[] = "From: " . self::$fromName . " <" . self::$fromEmail . ">";
        $headers[] = "Reply-To: " . self::$fromEmail;
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        if ($isHTML) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        }
        
        $headersString = implode("\r\n", $headers);
        
        try {
            $result = mail($to, $subject, $body, $headersString);
            
            if ($result) {
                error_log("Email sent successfully to: $to");
                return true;
            } else {
                error_log("Failed to send email to: $to");
                return false;
            }
        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Toplantı daveti emaili gönder
     */
    public static function sendMeetingInvitation($participant, $meeting, $responseToken) {
        self::init();
        
        $to = $participant['participant_email'] ?? $participant['email'] ?? '';
        if (empty($to)) {
            error_log("Email address not found for participant: " . $participant['participant_name']);
            return false;
        }
        
        $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://aifcrm.metechnik.at';
        $acceptUrl = $siteUrl . "/admin/meeting_response.php?token={$responseToken}&action=accept";
        $declineUrl = $siteUrl . "/admin/meeting_response.php?token={$responseToken}&action=decline";
        
        $subject = "Toplantı Daveti: " . htmlspecialchars($meeting['title']);
        
        $date = date('d.m.Y', strtotime($meeting['meeting_date']));
        $time = date('H:i', strtotime($meeting['meeting_time']));
        $endTime = !empty($meeting['end_time']) ? date('H:i', strtotime($meeting['end_time'])) : '';
        
        $body = "
        <!DOCTYPE html>
        <html lang='tr'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #009872; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .meeting-info { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #009872; }
                .button-group { text-align: center; margin: 30px 0; }
                .btn { display: inline-block; padding: 12px 30px; margin: 10px; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .btn-accept { background: #28a745; color: white; }
                .btn-decline { background: #dc3545; color: white; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .info-row { margin: 10px 0; }
                .info-label { font-weight: bold; display: inline-block; width: 120px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>📅 Toplantı Daveti</h2>
                </div>
                <div class='content'>
                    <p>Sayın <strong>" . htmlspecialchars($participant['participant_name']) . "</strong>,</p>
                    
                    <p>Toplantıya davet edildiniz:</p>
                    
                    <div class='meeting-info'>
                        <div class='info-row'>
                            <span class='info-label'>Toplantı:</span>
                            <span>" . htmlspecialchars($meeting['title']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Tarih:</span>
                            <span>{$date}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Saat:</span>
                            <span>{$time}" . ($endTime ? " - {$endTime}" : "") . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Yer:</span>
                            <span>" . htmlspecialchars($meeting['location']) . "</span>
                        </div>
                        " . (!empty($meeting['agenda']) ? "
                        <div class='info-row'>
                            <span class='info-label'>Gündem:</span>
                            <span>" . nl2br(htmlspecialchars($meeting['agenda'])) . "</span>
                        </div>" : "") . "
                    </div>
                    
                    <div class='button-group'>
                        <a href='{$acceptUrl}' class='btn btn-accept'>✅ Katılacağım</a>
                        <a href='{$declineUrl}' class='btn btn-decline'>❌ Katılmayacağım</a>
                    </div>
                    
                    <p style='font-size: 12px; color: #666;'>
                        <strong>Not:</strong> Yukarıdaki butonlara tıklayarak katılım durumunuzu bildirebilirsiniz. 
                        Katılamayacaksanız, lütfen mazeret nedeninizi belirtiniz.
                    </p>
                </div>
                <div class='footer'>
                    <p>Bu email AIF Otomasyon Sistemi tarafından otomatik gönderilmiştir.</p>
                    <p>&copy; " . date('Y') . " AIF Otomasyon</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::sendEmail($to, $subject, $body, true);
    }
    
    /**
     * Toplantı hatırlatma emaili gönder
     */
    public static function sendMeetingReminder($participant, $meeting) {
        self::init();
        
        $to = $participant['participant_email'] ?? $participant['email'] ?? '';
        if (empty($to)) {
            return false;
        }
        
        $subject = "Hatırlatma: " . htmlspecialchars($meeting['title']);
        
        $date = date('d.m.Y', strtotime($meeting['meeting_date']));
        $time = date('H:i', strtotime($meeting['meeting_time']));
        
        $body = "
        <!DOCTYPE html>
        <html lang='tr'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #ffc107; color: #333; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>⏰ Toplantı Hatırlatması</h2>
                </div>
                <div class='content'>
                    <p>Sayın <strong>" . htmlspecialchars($participant['participant_name']) . "</strong>,</p>
                    
                    <p>Toplantı yaklaşıyor:</p>
                    <p><strong>" . htmlspecialchars($meeting['title']) . "</strong></p>
                    <p>Tarih: <strong>{$date}</strong></p>
                    <p>Saat: <strong>{$time}</strong></p>
                    <p>Yer: <strong>" . htmlspecialchars($meeting['location']) . "</strong></p>
                    
                    <p>Lütfen toplantıya zamanında katılım sağlayınız.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::sendEmail($to, $subject, $body, true);
    }
}
?>

