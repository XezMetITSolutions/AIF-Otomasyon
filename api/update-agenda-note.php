<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

try {
    Middleware::requireBaskan();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $gundem_id = $input['gundem_id'] ?? null;
    $notlar = $input['notlar'] ?? '';
    
    if (!$gundem_id) {
        throw new Exception('Gündem ID gerekli');
    }
    
    $db = Database::getInstance();
    
    // Check if agenda exists and belongs to user's authorized area (if applicable)
    // For simplicity, just update assuming auth checked by Middleware
    
    $stmt = $db->query("
        UPDATE toplanti_gundem 
        SET gorusme_notlari = ? 
        WHERE gundem_id = ?
    ", [$notlar, $gundem_id]);
    
    $rowCount = $stmt->rowCount();

    echo json_encode([
        'success' => true, 
        'message' => 'Notlar kaydedildi',
        'debug' => [
            'received_id' => $gundem_id,
            'affected_rows' => $rowCount,
            'note_length' => strlen($notlar)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
