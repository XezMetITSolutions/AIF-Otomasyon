<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/MeetingPDF.php';
require_once __DIR__ . '/../classes/Mail.php';

$auth = new Auth();
if (!$auth->checkAuth()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Oturum açmanız gerekmektedir.']);
    exit;
}
$user = $auth->getUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek yöntemi.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$toplanti_id = $input['toplanti_id'] ?? null;

if (!$toplanti_id) {
    echo json_encode(['success' => false, 'error' => 'Toplantı ID gereklidir.']);
    exit;
}

$db = Database::getInstance();
// Toplantı ve BYK bilgisini al
$toplanti = $db->fetch("
    SELECT t.*, b.byk_adi 
    FROM toplantilar t 
    INNER JOIN byk b ON t.byk_id = b.byk_id 
    WHERE t.toplanti_id = ?
", [$toplanti_id]);

if (!$toplanti) {
    echo json_encode(['success' => false, 'error' => 'Toplantı bulunamadı.']);
    exit;
}

// Yetki Kontrolü: Admin, Başkan veya Toplantıyı Oluşturan
// Basitçe: BYK Üyesi ise ve yetkisi varsa? 
// Yönetici panelindeyiz, genellikle admin veya sekreter erişimi var.
// Middleware::checkRole(...) ? Zaten init.php session kontrolü yapıyor ama rol kontrolü ekleyelim
if (!$auth->isSuperAdmin() && $toplanti['olusturan_id'] != $user['id'] && $toplanti['byk_id'] != $user['byk_id']) {
     echo json_encode(['success' => false, 'error' => 'Bu işlem için yetkiniz yok.']);
     exit;
}

// 1. PDF Oluştur (String olarak)
try {
    $pdfData = MeetingPDF::generate($toplanti_id, 'S');
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'PDF oluşturulurken hata: ' . $e->getMessage()]);
    exit;
}

// 2. BYK Üyelerini Bul (E-posta alıcıları)
// TEST MODE: Sadece belirli adrese gönder
$recipients = [
    ['email' => 'mete.burcak@gmx.at', 'ad' => 'Test', 'soyad' => 'User']
];

/*
// Aktif ve ilgili BYK'ya ait kullanıcılar
$recipients = $db->fetchAll("
    SELECT ad, soyad, email 
    FROM kullanicilar 
    WHERE byk_id = ? AND aktif = 1 AND email IS NOT NULL AND email != ''
", [$toplanti['byk_id']]);

if (empty($recipients)) {
    echo json_encode(['success' => false, 'error' => 'Bu BYK için e-posta adresi kayıtlı üye bulunamadı.']);
    exit;
}
*/

// 3. E-posta Gönderimi
$subject = "Toplantı Tutanağı: " . $toplanti['baslik'];
$message = "
<p>Değerli Başkanlarım,</p>
<p><strong>" . date('d.m.Y', strtotime($toplanti['toplanti_tarihi'])) . "</strong> tarihinde yapmış olduğumuz <strong>" . htmlspecialchars($toplanti['baslik']) . "</strong> toplantımızın tutanağı ekte bilgilerinize sunulmuştur.</p>
<br>
<p>Saygılarımızla,<br>Avusturya İslam Federasyonu</p>
";

$successCount = 0;
$failCount = 0;

foreach ($recipients as $recipient) {
    // PDF ekiyle gönder
    $fileName = 'Tutanak_' . date('Y-m-d', strtotime($toplanti['toplanti_tarihi'])) . '.pdf';
    
    if (Mail::sendWithAttachment($recipient['email'], $subject, $message, $pdfData, $fileName)) {
        $successCount++;
    } else {
        $failCount++;
    }
}

echo json_encode([
    'success' => true, 
    'message' => "Rapor {$successCount} kişiye başarıyla gönderildi." . ($failCount > 0 ? " ({$failCount} başarısız)" : "")
]);
