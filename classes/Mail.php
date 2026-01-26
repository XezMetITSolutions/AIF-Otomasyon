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

    public static function getTemplate($kod)
    {
        try {
            $db = Database::getInstance();
            return $db->fetch("SELECT * FROM email_sablonlari WHERE kod = ?", [$kod]);
        } catch (Exception $e) {
            return null;
        }
    }

    public static function parseTemplate($content, $data)
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }

    public static function getMeetingInvitationTemplate($data)
    {
        $sablon = self::getTemplate('toplanti_daveti');

        $vars = [
            'ad_soyad' => htmlspecialchars($data['ad_soyad'] ?? ''),
            'baslik' => htmlspecialchars($data['baslik'] ?? ''),
            'tarih' => date('d.m.Y H:i', strtotime($data['toplanti_tarihi'] ?? 'now')),
            'konum' => htmlspecialchars($data['konum'] ?? ''),
            'app_name' => Config::get('app_name', 'AIF Otomasyon Sistemi'),
            'app_url' => Config::get('app_url', 'https://aifnet.islamfederasyonu.at'),
            'year' => date('Y')
        ];

        $token = $data['token'] ?? 'preview';
        $baseUrl = $vars['app_url'];
        $vars['accept_url'] = "{$baseUrl}/toplanti-yanit.php?token={$token}&yanit=katiliyor";
        $vars['reject_url'] = "{$baseUrl}/toplanti-yanit.php?token={$token}&yanit=katilmiyor";

        $gundem = nl2br(htmlspecialchars($data['aciklama'] ?? ''));
        $vars['gundem_html'] = '';
        if (!empty($gundem)) {
            $vars['gundem_html'] = <<<HTML
            <div style="margin-bottom: 35px;">
                <h3 style="margin: 0 0 15px 0; color: #343a40; font-size: 16px; border-bottom: 2px solid #00936F; display: inline-block; padding-bottom: 5px;">Gündem</h3>
                <div style="color: #6c757d; font-size: 15px; line-height: 1.6;">
                    {$gundem}
                </div>
            </div>
HTML;
        }

        if ($sablon) {
            return [
                'subject' => self::parseTemplate($sablon['konu'], $vars),
                'body' => self::parseTemplate($sablon['icerik'], $vars)
            ];
        }

        // Fallback or return as array for consistency
        return ['subject' => 'Toplantı Daveti', 'body' => 'Şablon bulunamadı.'];
    }

    public static function getMeetingCancellationTemplate($data)
    {
        $sablon = self::getTemplate('toplanti_iptali');

        $vars = [
            'ad_soyad' => htmlspecialchars($data['ad_soyad'] ?? ''),
            'baslik' => htmlspecialchars($data['baslik'] ?? ''),
            'tarih' => date('d.m.Y H:i', strtotime($data['toplanti_tarihi'] ?? 'now')),
            'konum' => htmlspecialchars($data['konum'] ?? '-'),
            'iptal_nedeni' => !empty($data['iptal_nedeni']) ? nl2br(htmlspecialchars($data['iptal_nedeni'])) : 'Belirtilmemiş',
            'app_name' => Config::get('app_name', 'AIF Otomasyon Sistemi'),
            'app_url' => Config::get('app_url', 'https://aifnet.islamfederasyonu.at'),
            'year' => date('Y')
        ];

        if ($sablon) {
            return [
                'subject' => self::parseTemplate($sablon['konu'], $vars),
                'body' => self::parseTemplate($sablon['icerik'], $vars)
            ];
        }

        return ['subject' => 'Toplantı İptal Edildi', 'body' => 'Şablon bulunamadı.'];
    }
}
