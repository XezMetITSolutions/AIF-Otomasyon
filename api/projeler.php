<?php
/**
 * API - Projeler (Next.js için)
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

$isSuperAdmin = $auth->isSuperAdmin();
$canManage = $auth->hasModulePermission('baskan_projeler');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT p.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as sorumlu
            FROM projeler p
            INNER JOIN byk b ON p.byk_id = b.byk_id
            LEFT JOIN kullanicilar u ON p.sorumlu_id = u.kullanici_id
            WHERE 1=1";
    
    $params = [];

    if (!$isSuperAdmin && !$canManage) {
        $sql .= " AND (p.sorumlu_id = ? OR EXISTS (
            SELECT 1 FROM proje_ekipleri pe 
            JOIN proje_ekip_uyeleri peu ON pe.id = peu.ekip_id 
            WHERE pe.proje_id = p.proje_id AND peu.kullanici_id = ?
        ))";
        $params[] = $user['id'];
        $params[] = $user['id'];
    }

    $sql .= " ORDER BY p.olusturma_tarihi DESC LIMIT 50";

    try {
        $projeler = $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        $projeler = [];
    }

    echo json_encode([
        'success' => true,
        'requests' => $projeler,
        'canManage' => $canManage
    ]);
    exit;
}
