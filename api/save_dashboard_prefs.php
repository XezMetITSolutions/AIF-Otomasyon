<?php
// api/save_dashboard_prefs.php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$user = $auth->getUser();

header('Content-Type: application/json');

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

// Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['widgets'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri.']);
    exit;
}

$widgets = $input['widgets']; // Array like ['etkinlik' => true, 'toplanti' => false]
$jsonVal = json_encode($widgets);

$db = Database::getInstance();

try {
    // Upsert preference
    // MySQL ON DUPLICATE KEY UPDATE syntax
    $sql = "INSERT INTO kullanici_ayarlari (kullanici_id, ayar_adi, ayar_degeri) 
            VALUES (?, 'dashboard_config', ?) 
            ON DUPLICATE KEY UPDATE ayar_degeri = VALUES(ayar_degeri)";
    
    $db->query($sql, [$user['id'], $jsonVal]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
