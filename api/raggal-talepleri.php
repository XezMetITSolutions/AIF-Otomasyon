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
    $tab = $_GET['tab'] ?? 'talep';
    $params = [];
    $where = ["1=1"];

    if ($tab === 'talep') {
        $where[] = "rt.kullanici_id = ?";
        $params[] = $user['id'];
    } elseif ($tab === 'onay') {
        // Only if baskan role? Auth handles it in UI, but API should too
        if (!$auth->hasModulePermission('baskan_raggal_talepleri')) {
            echo json_encode(['success' => true, 'requests' => []]);
            exit;
        }
        $where[] = "rt.durum = 'bekliyor'";
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    try {
        $talepler = $db->fetchAll("
            SELECT rt.*, CONCAT(u.ad, ' ', u.soyad) as talep_eden,
                   CASE 
                        WHEN rt.durum = 'onaylandi' THEN '#10b981'
                        WHEN rt.durum = 'reddedildi' THEN '#ef4444'
                        ELSE '#f59e0b'
                   END as color
            FROM raggal_talepleri rt
            INNER JOIN kullanicilar u ON rt.kullanici_id = u.kullanici_id
            $whereClause
            ORDER BY rt.baslangic_tarihi ASC
        ", $params);
        
        // If tab is takvim, we might want ALL approved ones + own ones
        if ($tab === 'takvim') {
            $talepler = $db->fetchAll("
                SELECT rt.*, CONCAT(u.ad, ' ', u.soyad) as talep_eden,
                       CASE 
                            WHEN rt.durum = 'onaylandi' THEN '#10b981'
                            WHEN rt.durum = 'reddedildi' THEN '#ef4444'
                            ELSE '#f59e0b'
                       END as color
                FROM raggal_talepleri rt
                INNER JOIN kullanicilar u ON rt.kullanici_id = u.kullanici_id
                WHERE rt.durum = 'onaylandi' OR rt.kullanici_id = ?
                ORDER BY rt.baslangic_tarihi ASC
            ", [$user['id']]);
        }

    } catch (Exception $e) {
        $talepler = []; 
    }

    echo json_encode([
        'success' => true,
        'requests' => $talepler
    ]);
    exit;
}
