<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();

try {
    try {
        $byk = $db->fetchAll("SELECT id as byk_id, name as byk_adi, code as byk_kodu, color as renk_kodu FROM byk_categories ORDER BY code");
    } catch (Exception $e) {
        $byk = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu, renk_kodu FROM byk WHERE aktif = 1 ORDER BY byk_adi");
    }

    echo json_encode(['success' => true, 'byk' => $byk]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
