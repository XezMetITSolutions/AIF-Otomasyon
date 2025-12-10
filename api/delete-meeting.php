<?php
/**
 * API Endpoint: Delete Meeting
 * Permanently deletes a meeting (for accidentally created ones)
 */

// Start output buffering
ob_start();

// Disable error display for clean JSON
ini_set('display_errors', 0);
error_reporting(0);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';

// Clear any buffered output
ob_end_clean();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Check authentication
$auth = new Auth();
if (!$auth->checkAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

// Check if user is super admin
$user = $auth->getUser();
if ($user['role'] !== 'super_admin') {
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
    echo json_encode(['success' => false, 'message' => 'Sistem hatası']);
}
