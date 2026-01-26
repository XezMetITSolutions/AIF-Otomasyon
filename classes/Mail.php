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

    public static function sendMeetingInvitation($data)
    {
        $sablon = self::getTemplate('toplanti_daveti');

        $vars = [
            'ad_soyad' => htmlspecialchars($data['ad_soyad'] ?? ''),
            'baslik' => htmlspecialchars($data['baslik'] ?? ''),
            'tarih' => date('d.m.Y H:i', strtotime($data['toplanti_tarihi'] ?? 'now')),
            'konum' => htmlspecialchars($data['konum'] ?? ''),
            'app_name' => Config::get('app_name', 'AİFNET'),
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
            $data['accept_url'] = $vars['accept_url'];
            $data['reject_url'] = $vars['reject_url'];
            $data['gundem_html'] = $vars['gundem_html'];
            $data['tarih'] = $vars['tarih'];
            $data['email'] = $data['email'] ?? '';

            return self::sendWithTemplate($data['email'], 'toplanti_daveti', $data);
        }

        return false;
    }

    public static function sendMeetingCancellation($data)
    {
        $sablon = self::getTemplate('toplanti_iptali');

        $vars = [
            'ad_soyad' => htmlspecialchars($data['ad_soyad'] ?? ''),
            'baslik' => htmlspecialchars($data['baslik'] ?? ''),
            'tarih' => date('d.m.Y H:i', strtotime($data['toplanti_tarihi'] ?? 'now')),
            'konum' => htmlspecialchars($data['konum'] ?? '-'),
            'iptal_nedeni' => !empty($data['iptal_nedeni']) ? nl2br(htmlspecialchars($data['iptal_nedeni'])) : 'Belirtilmemiş',
            'app_name' => Config::get('app_name', 'AİFNET'),
            'app_url' => Config::get('app_url', 'https://aifnet.islamfederasyonu.at'),
            'year' => date('Y')
        ];

        if ($sablon) {
            $data['tarih'] = $vars['tarih'];
            $data['iptal_nedeni'] = $vars['iptal_nedeni'];
            $data['email'] = $data['email'] ?? '';
            return self::sendWithTemplate($data['email'], 'toplanti_iptali', $data);
        }

        return false;
    }
    public static function sendWithTemplate($to, $templateCode, $data)
    {
        $sablon = self::getTemplate($templateCode);
        if (!$sablon) {
            return false;
        }

        // Add common global variables
        if (!isset($data['app_name']))
            $data['app_name'] = Config::get('app_name', 'AİFNET');
        if (!isset($data['app_url']))
            $data['app_url'] = Config::get('app_url', 'https://aifnet.islamfederasyonu.at');
        if (!isset($data['year']))
            $data['year'] = date('Y');
        if (!isset($data['panel_url']))
            $data['panel_url'] = $data['app_url'] . '/panel/dashboard.php';

        $subject = self::parseTemplate($sablon['konu'], $data);
        $message = self::parseTemplate($sablon['icerik'], $data);

        return self::send($to, $subject, $message);
    }
}
