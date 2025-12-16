<?php
class Mail {
    public static function send($to, $subject, $message) {
        $config = require __DIR__ . '/../config/mail.php';
        
        require_once __DIR__ . '/SimpleSMTP.php';
        $smtp = new SimpleSMTP($config);
        
        return $smtp->send(
            $to, 
            $subject, 
            $message, 
            $config['from_email'], 
            $config['from_name']
        );
    }

    public static function getMeetingInvitationTemplate($data) {
        $baslik = htmlspecialchars($data['baslik']);
        $tarih = date('d.m.Y H:i', strtotime($data['toplanti_tarihi']));
        $konum = htmlspecialchars($data['konum']);
        $gundem = nl2br(htmlspecialchars($data['aciklama']));
        $adSoyad = htmlspecialchars($data['ad_soyad']);
        
        // Token varsa linkleri oluştur, yoksa # koy (preview için)
        $token = $data['token'] ?? 'preview';
        $baseUrl = 'https://aifcrm.metechnik.at'; // Config'den alınabilir
        $acceptUrl = "{$baseUrl}/toplanti-yanit.php?token={$token}&yanit=katiliyor";
        $rejectUrl = "{$baseUrl}/toplanti-yanit.php?token={$token}&yanit=katilmiyor";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { background: #00936F; color: #fff; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .meeting-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #00936F; }
        .meeting-item { margin-bottom: 10px; }
        .meeting-item strong { width: 100px; display: inline-block; }
        .agenda { background: #fff; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; margin-top: 10px; }
        .buttons { text-align: center; margin-top: 30px; }
        .btn { display: inline-block; padding: 12px 24px; margin: 0 10px; text-decoration: none; border-radius: 5px; font-weight: bold; color: #fff; }
        .btn-accept { background-color: #198754; }
        .btn-reject { background-color: #dc3545; }
        .footer { background: #333; color: #fff; text-align: center; padding: 15px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://aifcrm.metechnik.at/assets/img/logo.png" alt="AIF Logo" style="max-height: 60px; margin-bottom: 10px;">
            <h1>Toplantı Daveti</h1>
        </div>
        <div class="content">
            <p>Sayın <strong>{$adSoyad}</strong>,</p>
            <p>Aşağıda detayları belirtilen toplantıya katılımınız beklenmektedir.</p>
            
            <div class="meeting-details">
                <div class="meeting-item">
                    <strong>Konu:</strong> {$baslik}
                </div>
                <div class="meeting-item">
                    <strong>Tarih:</strong> {$tarih}
                </div>
                <div class="meeting-item">
                    <strong>Konum:</strong> {$konum}
                </div>
            </div>

            <p><strong>Gündem:</strong></p>
            <div class="agenda">
                {$gundem}
            </div>

            <div class="buttons">
                <a href="{$acceptUrl}" class="btn btn-accept">✅ Katılıyorum</a>
                <a href="{$rejectUrl}" class="btn btn-reject">❌ Katılmıyorum</a>
            </div>
        </div>
        <div class="footer">
            &copy; 2025 AIF Otomasyon Sistemi. Bu mesaj otomatik olarak oluşturulmuştur.
        </div>
    </div>
</body>
</html>
HTML;
    }

    public static function getMeetingCancellationTemplate($data) {
        $baslik = htmlspecialchars($data['baslik']);
        $tarih = date('d.m.Y H:i', strtotime($data['toplanti_tarihi']));
        $konum = htmlspecialchars($data['konum'] ?? '-');
        $adSoyad = htmlspecialchars($data['ad_soyad']);
        $iptalNedeni = !empty($data['iptal_nedeni']) ? nl2br(htmlspecialchars($data['iptal_nedeni'])) : 'Belirtilmemiş';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { background: #dc3545; color: #fff; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .meeting-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc3545; }
        .meeting-item { margin-bottom: 10px; }
        .meeting-item strong { width: 120px; display: inline-block; }
        .alert { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .footer { background: #333; color: #fff; text-align: center; padding: 15px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://aifcrm.metechnik.at/assets/img/logo.png" alt="AIF Logo" style="max-height: 60px; margin-bottom: 10px;">
            <h1>⚠️ Toplantı İPTAL Edildi</h1>
        </div>
        <div class="content">
            <p>Sayın <strong>{$adSoyad}</strong>,</p>
            <p>Katılımınız beklenen aşağıdaki toplantı <strong style="color: #dc3545;">iptal edilmiştir</strong>.</p>
            
            <div class="meeting-details">
                <div class="meeting-item">
                    <strong>Konu:</strong> {$baslik}
                </div>
                <div class="meeting-item">
                    <strong>Tarih:</strong> {$tarih}
                </div>
                <div class="meeting-item">
                    <strong>Konum:</strong> {$konum}
                </div>
            </div>

            <div class="alert">
                <strong>İptal Nedeni:</strong><br>
                {$iptalNedeni}
            </div>

            <p style="margin-top: 20px;">Yeni bir toplantı tarihi belirlendiğinde sizinle tekrar iletişime geçilecektir.</p>
            <p>Anlayışınız için teşekkür ederiz.</p>
        </div>
        <div class="footer">
            &copy; 2025 AIF Otomasyon Sistemi. Bu mesaj otomatik olarak oluşturulmuştur.
        </div>
    </div>
</body>
</html>
HTML;
    }
}
