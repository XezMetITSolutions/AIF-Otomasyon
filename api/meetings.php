<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$auth = new Auth();
$db = Database::getInstance();

try {
    $meetings = $db->fetchAll("
        SELECT 
            t.toplanti_id, 
            t.baslik, 
            t.tarih, 
            t.saat, 
            t.durum,
            (SELECT COUNT(*) FROM toplanti_katilimcilari WHERE toplanti_id = t.toplanti_id) as katilimci_sayisi
        FROM toplantilar t
        ORDER BY t.tarih DESC, t.saat DESC
        LIMIT 20
    ");

    echo json_encode([
        'success' => true,
        'meetings' => $meetings
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
