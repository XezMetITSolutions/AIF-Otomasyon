<?php
/**
 * API - Yönetim: Demirbaşlar (Next.js için)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->checkAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Oturum açılmamış.']);
    exit;
}

$user = $auth->getUser();
$db = Database::getInstance();

try {
    $demirbaslar = $db->fetchAll("
        SELECT d.*, 
               CONCAT(u.ad, ' ', u.soyad) as sorumlu_adi,
               CONCAT(mk.ad, ' ', mk.soyad) as mevcut_kullanici_adi
        FROM demirbaslar d 
        LEFT JOIN kullanicilar u ON d.sorumlu_kisi_id = u.kullanici_id 
        LEFT JOIN kullanicilar mk ON d.mevcut_kullanici_id = mk.kullanici_id
        ORDER BY d.created_at DESC
    ");

    echo json_encode([
        'success' => true,
        'demirbaslar' => $demirbaslar
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
