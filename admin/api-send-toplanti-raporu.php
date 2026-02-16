<?php
// Output buffering to catch any stray output (warnings, etc.)
ob_start();

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Mail.php';
require_once __DIR__ . '/../classes/MeetingPDF.php';

// Turn off displaying errors to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->checkAuth()) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekmektedir.']);
    exit;
}

$user = $auth->getUser();
$db = Database::getInstance();

$toplanti_id = $_POST['id'] ?? null;

if (!$toplanti_id) {
    ob_end_clean();
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
    if (empty($pdfData)) {
        throw new Exception('PDF raporu oluşturulamadı.');
    }
    $pdfName = 'Toplanti_Raporu_' . $toplanti_id . '.pdf';

    // Mail Gönder
    $to = 'mete.burcak@gmx.at'; // Şimdilik sadece bu adrese (test amaçlı)
    $subject = 'Toplantı Raporu: ' . $toplanti['baslik'];
    $message = '<p>Merhaba,</p>';
    $message .= '<p><strong>' . htmlspecialchars($toplanti['baslik']) . '</strong> başlıklı toplantının raporu ekte sunulmuştur.</p>';
    $message .= '<p>İyi çalışmalar.</p>';

    $result = Mail::sendWithAttachment($to, $subject, $message, $pdfData, $pdfName);

    // Any output captured so far is likely warnings, let's clear them
    $capturedOutput = ob_get_clean();

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Toplantı raporu başarıyla gönderildi (Test adresi: ' . $to . ')',
            'debug' => !empty($capturedOutput) ? $capturedOutput : null
        ]);
    } else {
        throw new Exception(Mail::$lastError ?? 'E-posta gönderilemedi.');
    }

} catch (Exception $e) {
    if (ob_get_level() > 0)
        ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
