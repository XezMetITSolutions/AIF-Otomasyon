<?php
/**
 * API Endpoint: Delete Meeting
 * Permanently deletes a meeting (for accidentally created ones)
 */
// Disable error display for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Clean output buffer
ob_start();

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Middleware.php';

// Clear any output from includes
ob_end_clean();

header('Content-Type: application/json');

// Only super admin can delete meetings
try {
    Middleware::requireSuperAdmin();
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$toplanti_id = $data['toplanti_id'] ?? null;

if (!$toplanti_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Toplantı ID gereklidir']);
    exit;
}

$db = Database::getInstance();

try {
    // Check if meeting exists
    $toplanti = $db->fetchOne("
        SELECT toplanti_id, baslik 
        FROM toplantilar 
        WHERE toplanti_id = ?
    ", [$toplanti_id]);
    
    if (!$toplanti) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Toplantı bulunamadı']);
        exit;
    }
    
    // Delete meeting (CASCADE will delete related records)
    $db->query("DELETE FROM toplantilar WHERE toplanti_id = ?", [$toplanti_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Toplantı başarıyla silindi'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
