<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$user = $auth->getUser();

if (!$user) {
    die('Oturum açmanız gerekmektedir.');
}

$db = Database::getInstance();

// Filtreleri al
$search = $_GET['search'] ?? '';
$monthFilter = $_GET['ay'] ?? '';
$yearFilter = $_GET['yil'] ?? '';

// Temel sorgu (Varsayılan olarak boş, filtreler aşağıda eklenecek)
$where = [];
$params = [];

// Eğer birim filtresi gelmişse kısıtla
$birimFilter = $_GET['birim'] ?? '';
if ($birimFilter) {
    $where[] = "b.byk_kodu = ?";
    $params[] = $birimFilter;
}

if ($search) {
    $where[] = "(e.baslik LIKE ? OR e.aciklama LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($monthFilter) {
    $where[] = "MONTH(e.baslangic_tarihi) = ?";
    $params[] = $monthFilter;
}

if ($yearFilter) {
    $where[] = "YEAR(e.baslangic_tarihi) = ?";
    $params[] = $yearFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Etkinlikleri çek
try {
    $etkinlikler = $db->fetchAll("
        SELECT e.*, b.byk_adi 
        FROM etkinlikler e
        LEFT JOIN byk b ON e.byk_id = b.byk_id
        $whereClause
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 1000
    ", $params);
} catch (Exception $e) {
    die('Veri çekme hatası.');
}

// ICS İçeriği Oluşturma
$eol = "\r\n";
$icsContent = "BEGIN:VCALENDAR" . $eol;
$icsContent .= "VERSION:2.0" . $eol;
$icsContent .= "PRODID:-//AIF Otomasyon//Tr//EN" . $eol;
$icsContent .= "CALSCALE:GREGORIAN" . $eol;
$icsContent .= "METHOD:PUBLISH" . $eol;
$icsContent .= "X-WR-CALNAME:AIF Etkinlik Takvimi" . $eol;
$icsContent .= "X-WR-TIMEZONE:Europe/Istanbul" . $eol;

foreach ($etkinlikler as $etkinlik) {
    if (empty($etkinlik['baslangic_tarihi']) || empty($etkinlik['bitis_tarihi'])) continue;

    $start = new DateTime($etkinlik['baslangic_tarihi']);
    $end = new DateTime($etkinlik['bitis_tarihi']);
    $now = new DateTime();

    $icsContent .= "BEGIN:VEVENT" . $eol;
    $icsContent .= "UID:" . $etkinlik['etkinlik_id'] . "@aifcrm.metechnik.at" . $eol;
    $icsContent .= "DTSTAMP:" . $now->format('Ymd\THis\Z') . $eol;
    
    // Tüm gün kontrolü
    $isAllDay = ($start->format('H:i') == '00:00' && $end->format('H:i') == '23:59');
    
    if ($isAllDay) {
        // FullCalendar ve Google Calendar tüm gün etkinlikleri için bitiş tarihini bir sonraki günün 00:00'ı olarak bekler (genellikle)
        // Ancak ICS formatında DTSTART;VALUE=DATE:YYYYMMDD yeterlidir.
        // Bitiş günü dahil olsun diye +1 gün eklenebilir ama basitlik için normal tarih kullanalım.
        $icsContent .= "DTSTART;VALUE=DATE:" . $start->format('Ymd') . $eol;
        // Bitiş tarihi opsiyonel veya +1 gün. Biz de +1 gün yaparak standarda uyalım.
        $end->modify('+1 day');
        $icsContent .= "DTEND;VALUE=DATE:" . $end->format('Ymd') . $eol;
    } else {
        $icsContent .= "DTSTART:" . $start->format('Ymd\THis') . $eol;
        $icsContent .= "DTEND:" . $end->format('Ymd\THis') . $eol;
    }

    $icsContent .= "SUMMARY:" . escapeIcs($etkinlik['baslik']) . $eol;
    
    $description = $etkinlik['aciklama'] ?? '';
    if (!empty($etkinlik['byk_adi'])) {
        $description = "BYK: " . $etkinlik['byk_adi'] . "\\n\\n" . $description;
    }
    
    if (!empty($description)) {
        $icsContent .= "DESCRIPTION:" . escapeIcs($description) . $eol;
    }
    
    if (!empty($etkinlik['konum'])) {
        $icsContent .= "LOCATION:" . escapeIcs($etkinlik['konum']) . $eol;
    }
    
    $icsContent .= "STATUS:CONFIRMED" . $eol;
    $icsContent .= "END:VEVENT" . $eol;
}

$icsContent .= "END:VCALENDAR";

// Dosya İndirme Headerları
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="aif_takvim.ics"');
header('Content-Length: ' . strlen($icsContent));
header('Connection: close');

echo $icsContent;

// Helper function
function escapeIcs($string) {
    if (!$string) return '';
    $string = str_replace(["\r\n", "\n", "\r"], "\\n", $string); // Newlines
    $string = str_replace([",", ";"], ["\\,", "\\;"], $string); // Reserved chars
    return $string;
}
