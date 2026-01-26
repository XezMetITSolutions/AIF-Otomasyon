<?php
require_once __DIR__ . '/../classes/Mail.php';

header('Content-Type: text/html; charset=utf-8');

// Örnek Veriler
$data = [
    'baslik' => 'AIF Yönetim Kurulu Olağan Toplantısı',
    'toplanti_tarihi' => date('Y-m-d H:i', strtotime('+3 days 14:00')),
    'konum' => 'Genel Merkez Toplantı Salonu',
    'aciklama' => "• Geçen ayın faaliyet raporunun değerlendirilmesi\n• Yeni dönem bütçesinin görüşülmesi\n• Personel alım taleplerinin incelenmesi\n• Dilek ve temenniler",
    'ad_soyad' => 'Ahmet Yılmaz',
    'token' => 'sample_token_123'
];

$template = Mail::getMeetingInvitationTemplate($data);
echo $template['body'];
