<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Mail.php';
require_once __DIR__ . '/../classes/MeetingPDF.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->checkAuth()) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekmektedir.']);
    exit;
}

$user = $auth->getUser();
$db = Database::getInstance();

$toplanti_id = $_POST['id'] ?? null;

if (!$toplanti_id) {
    echo json_encode(['success' => false, 'message' => 'Toplantı ID gereklidir.']);
    exit;
}

try {
    // Toplantı bilgilerini ve yetki kontrolünü getir
    $toplanti = $db->fetch("SELECT * FROM toplantilar WHERE toplanti_id = ? AND byk_id = ?", [$toplanti_id, $user['byk_id']]);

    if (!$toplanti) {
        throw new Exception('Toplantı bulunamadı veya bu toplantıya erişim yetkiniz yok.');
    }

    // PDF Üret
    $pdfData = MeetingPDF::generate($toplanti_id, 'S');
    $pdfName = 'Toplanti_Raporu_' . $toplanti_id . '.pdf';

    // Mail Gönder
    $to = 'mete.burcak@gmx.at'; // Şimdilik sadece bu adrese (test amaçlı)
    $subject = 'Toplantı Raporu: ' . $toplanti['baslik'];
    $message = '<p>Merhaba,</p>';
    $message .= '<p><strong>' . htmlspecialchars($toplanti['baslik']) . '</strong> başlıklı toplantının raporu ekte sunulmuştur.</p>';
    $message .= '<p>İyi çalışmalar.</p>';

    $result = Mail::sendWithAttachment($to, $subject, $message, $pdfData, $pdfName);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Toplantı raporu başarıyla gönderildi (Test adresi: ' . $to . ')']);
    } else {
        throw new Exception(Mail::$lastError ?? 'E-posta gönderilemedi.');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
