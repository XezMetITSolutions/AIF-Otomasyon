<?php
class Mail
{
    public static function send($to, $subject, $message)
    {
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

    public static function getMeetingInvitationTemplate($data)
    {
        $baslik = htmlspecialchars($data['baslik'] ?? '');
        $tarih = date('d.m.Y H:i', strtotime($data['toplanti_tarihi'] ?? 'now'));
        $konum = htmlspecialchars($data['konum'] ?? '');
        $gundem = nl2br(htmlspecialchars($data['aciklama'] ?? ''));
        $adSoyad = htmlspecialchars($data['ad_soyad'] ?? '');

        $token = $data['token'] ?? 'preview';
        $baseUrl = Config::get('app_url', 'https://aifnet.islamfederasyonu.at');
        $acceptUrl = "{$baseUrl}/toplanti-yanit.php?token={$token}&yanit=katiliyor";
        $rejectUrl = "{$baseUrl}/toplanti-yanit.php?token={$token}&yanit=katilmiyor";

        $gundemHtml = '';
        if (!empty($gundem)) {
            $gundemHtml = <<<HTML
            <div style="margin-bottom: 35px;">
                <h3 style="margin: 0 0 15px 0; color: #343a40; font-size: 16px; border-bottom: 2px solid #00936F; display: inline-block; padding-bottom: 5px;">Gündem</h3>
                <div style="color: #6c757d; font-size: 15px; line-height: 1.6;">
                    {$gundem}
                </div>
            </div>
HTML;
        }

        $appUrl = Config::get('app_url');
        $appName = Config::get('app_name', 'AIF Otomasyon Sistemi');
        $currentYear = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toplantı Daveti</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', user-scalable=no, -apple-system, BlinkMacSystemFont, Tahoma, Verdana, sans-serif;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 600px; width: 100%;">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #00936F; padding: 35px 20px;">
                            <img src="{$appUrl}/assets/img/AIF.png" alt="AIF Logo" style="height: 48px; border: 0; display: block; filter: brightness(0) invert(1);">
                            <h1 style="color: #ffffff; margin: 20px 0 0 0; font-size: 24px; font-weight: 600; letter-spacing: 0.5px;">Toplantı Daveti</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 40px 20px 40px;">
                            <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                Sayın <strong>{$adSoyad}</strong>,<br><br>
                                Aşağıda detayları yer alan toplantıya katılımınız beklenmektedir.
                            </p>

                            <!-- Details Box -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 12px; color: #495057; font-size: 15px;">
                                                    <strong style="color: #00936F; display: inline-block; width: 80px;">Konu:</strong> {$baslik}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 12px; color: #495057; font-size: 15px;">
                                                    <strong style="color: #00936F; display: inline-block; width: 80px;">Tarih:</strong> {$tarih}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #495057; font-size: 15px;">
                                                    <strong style="color: #00936F; display: inline-block; width: 80px;">Konum:</strong> {$konum}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Agenda -->
                            {$gundemHtml}

                            <!-- Actions -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-bottom: 20px;">
                                        <div style="display: block; margin-bottom: 15px;">
                                            <a href="{$acceptUrl}" style="background-color: #198754; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block; min-width: 140px; font-size: 16px; box-shadow: 0 2px 5px rgba(25, 135, 84, 0.3);">✅ Katılıyorum</a>
                                        </div>
                                        <a href="{$rejectUrl}" style="color: #dc3545; text-decoration: none; font-size: 14px; font-weight: 500; border-bottom: 1px dotted #dc3545; padding-bottom: 2px;">Katılamayacağım (Reddet)</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f8f9fa; padding: 25px; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0; color: #adb5bd; font-size: 12px; line-height: 1.5;">
                                © {$currentYear} {$appName}<br>
                                Bu e-posta otomatik olarak oluşturulmuştur, lütfen yanıtlamayınız.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    public static function getMeetingCancellationTemplate($data)
    {
        $baslik = htmlspecialchars($data['baslik'] ?? '');
        $tarih = date('d.m.Y H:i', strtotime($data['toplanti_tarihi'] ?? 'now'));
        $konum = htmlspecialchars($data['konum'] ?? '-');
        $adSoyad = htmlspecialchars($data['ad_soyad'] ?? '');
        $iptalNedeni = !empty($data['iptal_nedeni']) ? nl2br(htmlspecialchars($data['iptal_nedeni'])) : 'Belirtilmemiş';

        $appUrl = Config::get('app_url');
        $appName = Config::get('app_name', 'AIF Otomasyon Sistemi');
        $currentYear = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toplantı İptali</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', user-scalable=no, -apple-system, BlinkMacSystemFont, Tahoma, Verdana, sans-serif;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <!-- Main Card -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 600px; width: 100%;">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #DC3545; padding: 35px 20px;">
                            <div style="background-color: rgba(255,255,255,0.2); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                                <span style="font-size: 32px; line-height: 64px; display: block;">⚠️</span>
                            </div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 0.5px;">Toplantı İptal Edildi</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 25px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                Sayın <strong>{$adSoyad}</strong>,<br><br>
                                Daha önce planlanan aşağıdaki toplantı ne yazık ki <strong>iptal edilmiştir</strong>.
                            </p>

                            <!-- Meeting Info -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa; border-radius: 8px; border-left: 4px solid #DC3545; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 10px; color: #495057; font-size: 15px;">
                                                    <strong>Konu:</strong> {$baslik}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 10px; color: #495057; font-size: 15px;">
                                                    <strong>Tarih:</strong> {$tarih}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #495057; font-size: 15px;">
                                                    <strong>Konum:</strong> {$konum}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Reason Box -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fff3cd; border-radius: 8px; border: 1px solid #ffeeba; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <strong style="color: #856404; display: block; margin-bottom: 8px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">İptal Nedeni</strong>
                                        <span style="color: #533f03; font-size: 15px; display: block; line-height: 1.5;">{$iptalNedeni}</span>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; color: #6c757d; font-size: 15px; font-style: italic;">
                                Yeni bir toplantı tarihi belirlendiğinde sizinle tekrar iletişime geçilecektir. Anlayışınız için teşekkür ederiz.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f8f9fa; padding: 25px; border-top: 1px solid #e9ecef;">
                            <img src="{$appUrl}/assets/img/AIF.png" alt="AIF Logo" style="height: 24px; opacity: 0.5; margin-bottom: 15px; filter: grayscale(100%);">
                            <p style="margin: 0; color: #adb5bd; font-size: 12px; line-height: 1.5;">
                                © {$currentYear} {$appName}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
