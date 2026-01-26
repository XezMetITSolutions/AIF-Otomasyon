<?php
/**
 * API Endpoint: Delete Meeting
 * Permanently deletes a meeting (for accidentally created ones)
 */

// Disable error display but enable logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set JSON header first
header('Content-Type: application/json; charset=utf-8');

// Shutdown handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']]);
        exit;
    }
});

try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../classes/Database.php';
    require_once __DIR__ . '/../classes/Auth.php';

    // Check authentication
    $auth = new Auth();
    if (!$auth->checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
        exit;
    }

    // Check if user is super admin or baskan
    $user = $auth->getUser();
    if ($user['role'] !== 'super_admin' && $user['role'] !== 'uye') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Geçersiz JSON verisi');
    }

    $toplanti_id = $data['toplanti_id'] ?? null;

    if (!$toplanti_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Toplantı ID gereklidir']);
        exit;
    }

    $db = Database::getInstance();

    // Check if meeting exists
    $toplanti = $db->fetch("
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
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
