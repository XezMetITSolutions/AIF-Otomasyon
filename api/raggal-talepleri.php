<?php
/**
 * API - Raggal Talepleri (Next.js için)
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $where = [];
    $params = [];

    // Herkes kendi taleplerini veya yetkisine göre
    $where[] = "1=1";

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    try {
        $talepler = $db->fetchAll("
            SELECT rt.*, CONCAT(u.ad, ' ', u.soyad) as talep_eden 
            FROM raggal_talepleri rt
            INNER JOIN kullanicilar u ON rt.kullanici_id = u.kullanici_id
            $whereClause
            ORDER BY rt.created_at DESC
        ", $params);
    } catch (Exception $e) {
        $talepler = []; 
    }

    echo json_encode([
        'success' => true,
        'requests' => $talepler
    ]);
    exit;
}
