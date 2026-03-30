<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$auth = new Auth();
$db = Database::getInstance();

$type = $_GET['type'] ?? 'izin';

try {
    if ($type === 'izin') {
        $tasks = $db->fetchAll("
            SELECT i.*, CONCAT(u.ad, ' ', u.soyad) as ad_soyad 
            FROM izin_talepleri i 
            LEFT JOIN kullanicilar u ON i.kullanici_id = u.kullanici_id 
            ORDER BY i.olusturma_tarihi DESC
        ");
    } else {
        $tasks = $db->fetchAll("
            SELECT h.*, CONCAT(u.ad, ' ', u.soyad) as ad_soyad 
            FROM harcama_talepleri h 
            LEFT JOIN kullanicilar u ON h.kullanici_id = u.kullanici_id 
            ORDER BY h.olusturma_tarihi DESC
        ");
    }

    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
