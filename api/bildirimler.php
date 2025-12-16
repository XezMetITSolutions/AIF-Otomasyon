<?php
/**
 * Bildirimler API Endpoint
 * JSON formatında bildirimleri döner
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

$auth = new Auth();
$user = $auth->getUser();

if (!$user) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Yetkilendirme gerekli'
    ]);
    exit;
}

$db = Database::getInstance();

// Bildirimleri al
$bildirimler = $db->fetchAll("
    SELECT 
        bildirim_id,
        baslik,
        mesaj,
        tip,
        link,
        okundu,
        olusturma_tarihi
    FROM bildirimler
    WHERE kullanici_id = ?
    ORDER BY okundu ASC, olusturma_tarihi DESC
    LIMIT 20
", [$user['id']]);

echo json_encode([
    'success' => true,
    'data' => $bildirimler
]);

