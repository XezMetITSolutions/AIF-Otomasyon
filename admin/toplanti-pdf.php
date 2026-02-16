<?php
ob_start();
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/MeetingPDF.php';

$auth = new Auth();
if (!$auth->checkAuth()) {
    die('Oturum açmanız gerekmektedir.');
}
$user = $auth->getUser();

$toplanti_id = $_GET['id'] ?? null;

if (!$toplanti_id) {
    die('Toplantı ID gereklidir');
}

// Check BYK access and other permissions inside MeetingPDF or here?
// Better here for separation of concerns
$db = Database::getInstance();
$toplanti = $db->fetch("SELECT byk_id FROM toplantilar WHERE toplanti_id = ?", [$toplanti_id]);

if (!$toplanti) {
    die('Toplantı bulunamadı');
}

if ($user['role'] === Auth::ROLE_UYE && $toplanti['byk_id'] != $user['byk_id']) {
    die('Erişim reddedildi: Bu toplantının raporunu görüntüleme yetkiniz yok.');
}

// Generate PDF Output
try {
    // Clear any output that appeared before PDF generation (like Deprecated notices)
    if (ob_get_length())
        ob_end_clean();

    // Disable error display and deprecated warnings for the PDF generation phase
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
    ini_set('display_errors', 0);

    MeetingPDF::generate($toplanti_id, 'I');
} catch (Exception $e) {
    if (ob_get_length())
        ob_end_clean();
    die('Hata: ' . $e->getMessage());
}
