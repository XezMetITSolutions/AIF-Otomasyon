<?php
/**
 * Mobile PDF API
 * Returns PDF content as Base64 for mobile app direct download
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/init.php';

$toplanti_id = $_GET['id'] ?? null;

if (!$toplanti_id) {
    echo json_encode(['success' => false, 'message' => 'Toplantı ID gereklidir']);
    exit;
}

try {
    // Disable error display to prevent corrupting JSON output
    error_reporting(0);
    ini_set('display_errors', 0);

    // Get PDF content as string ('S' mode in TCPDF)
    // We use ob_start/ob_get_clean just in case there's any accidental output
    ob_start();
    $pdfContent = MeetingPDF::generate($toplanti_id, 'S');
    ob_end_clean();

    if (empty($pdfContent)) {
        throw new Exception('PDF oluşturulamadı');
    }

    echo json_encode([
        'success' => true,
        'filename' => 'Toplanti_Raporu_' . $toplanti_id . '.pdf',
        'pdf_base64' => base64_encode($pdfContent)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
