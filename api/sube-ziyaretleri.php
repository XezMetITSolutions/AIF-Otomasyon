<?php
/**
 * API - Şube Ziyaretleri (Next.js için)
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
    $tab = $_GET['tab'] ?? 'planlanan'; // planlanan, tamamlanan
    
    $where = [];
    $params = [];

    if ($tab === 'planlanan') {
        $where[] = "z.durum = 'planlandi'";
    } else {
        $where[] = "z.durum = 'tamamlandi'";
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    try {
        $ziyaretler = $db->fetchAll("
            SELECT z.*, b.byk_adi, g.grup_adi, g.renk_kodu, CONCAT(u.ad, ' ', u.soyad) as olusturan
            FROM sube_ziyaretleri z
            INNER JOIN byk b ON z.byk_id = b.byk_id
            INNER JOIN ziyaret_gruplari g ON z.grup_id = g.grup_id
            INNER JOIN kullanicilar u ON z.olusturan_id = u.kullanici_id
            $whereClause
            ORDER BY z.ziyaret_tarihi ASC
            LIMIT 100
        ", $params);
    } catch (Exception $e) {
        $ziyaretler = []; 
    }

    echo json_encode([
        'success' => true,
        'requests' => $ziyaretler
    ]);
    exit;
}
