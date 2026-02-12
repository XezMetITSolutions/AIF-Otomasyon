<?php
/**
 * Raggal Rezervasyon Talepleri ICS Feed
 * Telefon takvimlerine entegrasyon için.
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$userId = $_GET['u'] ?? null;
$token = $_GET['t'] ?? null;

if (!$userId || !$token) {
    http_response_code(403);
    die("Yetkisiz erişim.");
}

// Basit güvenlik kontrolü
$salt = "RaggalSalt2024"; 
$expectedToken = md5($userId . $salt);

if ($token !== $expectedToken) {
    http_response_code(403);
    die("Geçersiz anahtar.");
}

$db = Database::getInstance();

// Kullanıcıyı kontrol et
$user = $db->fetch("SELECT * FROM kullanicilar WHERE kullanici_id = ?", [$userId]);
if (!$user) {
    http_response_code(404);
    die("Kullanıcı bulunamadı.");
}

// Etkinlikleri çek (Onaylı ve Bekleyenler)
// Kullanıcı kendi reddettiklerini de görebilir
$events = $db->fetchAll("
    SELECT 
        r.*, 
        r.aciklama as title,
        CONCAT(u.ad, ' ', u.soyad) as kullanici_adi
    FROM raggal_talepleri r
    JOIN kullanicilar u ON r.kullanici_id = u.kullanici_id
    WHERE r.durum != 'reddedildi' OR r.kullanici_id = ?
", [$userId]);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="raggal-talepleri.ics"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//AIF//Raggal Talepleri//TR\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";
echo "X-WR-CALNAME:Raggal Rezervasyonları\r\n";
echo "X-WR-TIMEZONE:Europe/Vienna\r\n";

foreach ($events as $event) {
    $baslangic = $event['baslangic_tarihi'];
    $bitis = $event['bitis_tarihi'];

    // ICS'de tüm gün süren etkinlikler için DTEND ertesi günün başlangıcı olmalıdır (exclusive)
    $dtStart = date('Ymd', strtotime($baslangic));
    $dtEnd = date('Ymd', strtotime($bitis . ' +1 day'));
    
    $summary = $event['kullanici_adi'] . ": " . ($event['title'] ?: 'Raggal Rezervasyonu');
    
    if ($event['durum'] === 'bekliyor') {
        $summary = "⏳ [BEKLİYOR] " . $summary;
    } elseif ($event['durum'] === 'reddedildi') {
        $summary = "❌ [REDDEDİLDİ] " . $summary;
    }

    $description = "Durum: " . ucfirst($event['durum']) . "\n";
    $description .= "Kullanıcı: " . $event['kullanici_adi'] . "\n";
    if ($event['title']) {
        $description .= "Açıklama: " . $event['title'];
    }

    echo "BEGIN:VEVENT\r\n";
    echo "UID:raggal_" . $event['id'] . "@aifnet.islamfederasyonu.at\r\n";
    echo "DTSTAMP:" . date('Ymd\THis\Z') . "\r\n";
    echo "DTSTART;VALUE=DATE:" . $dtStart . "\r\n";
    echo "DTEND;VALUE=DATE:" . $dtEnd . "\r\n";
    echo "SUMMARY:" . escapeIcs($summary) . "\r\n";
    echo "DESCRIPTION:" . escapeIcs($description) . "\r\n";
    echo "STATUS:" . ($event['durum'] === 'onaylandi' ? 'CONFIRMED' : 'TENTATIVE') . "\r\n";
    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";

function escapeIcs($string) {
    $string = str_replace('\\', '\\\\', $string);
    $string = str_replace(',', '\\,', $string);
    $string = str_replace(';', '\\;', $string);
    $string = str_replace("\n", '\\n', $string);
    $string = str_replace("\r", '', $string);
    return $string;
}
