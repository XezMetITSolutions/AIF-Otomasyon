<?php
// api/save_iban.php
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

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['iban'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'IBAN verisi eksik.']);
    exit;
}

$iban = trim($input['iban']);

// Simple validation
if (empty($iban)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lütfen geçerli bir IBAN giriniz.']);
    exit;
}

$db = Database::getInstance();

try {
    $sql = "INSERT INTO kullanici_ayarlari (kullanici_id, ayar_adi, ayar_degeri) 
            VALUES (?, 'saved_iban', ?) 
            ON DUPLICATE KEY UPDATE ayar_degeri = VALUES(ayar_degeri)";
    
    $db->query($sql, [$user['id'], $iban]);

    echo json_encode(['success' => true, 'message' => 'IBAN başarıyla kaydedildi.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
