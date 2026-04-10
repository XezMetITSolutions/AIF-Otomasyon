<?php
/**
 * Public Meeting PDF API
 * Provides PDF generation without session authentication for mobile app integration
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/MeetingPDF.php';

header('Access-Control-Allow-Origin: *');

$toplanti_id = $_GET['id'] ?? null;

if (!$toplanti_id) {
    die('Toplantı ID gereklidir');
}

$db = Database::getInstance();
$toplanti = $db->fetch("SELECT toplanti_id FROM toplantilar WHERE toplanti_id = ?", [$toplanti_id]);

if (!$toplanti) {
    die('Toplantı bulunamadı');
}

// Generate PDF Output
try {
    // Disable error display for the PDF generation phase
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', 0);

    $mode = ($_GET['download'] === '1') ? 'D' : 'I';
    MeetingPDF::generate($toplanti_id, $mode);
} catch (Exception $e) {
    die('Hata: ' . $e->getMessage());
}
