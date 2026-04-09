<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();

try {
    $projeler = $db->fetchAll("SELECT p.*, (SELECT COUNT(*) FROM proje_notlari WHERE proje_id = p.proje_id) as not_sayisi FROM projeler p ORDER BY p.olusturma_tarihi DESC");
    echo json_encode(['success' => true, 'projeler' => $projeler]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
