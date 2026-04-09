<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();

try {
    $subeler = $db->fetchAll("SELECT * FROM subeler WHERE aktif = 1 ORDER BY sube_adi ASC");
    echo json_encode(['success' => true, 'subeler' => $subeler]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
