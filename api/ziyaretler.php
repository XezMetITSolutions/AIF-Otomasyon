<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
$userId = $_GET['userId'] ?? null;

try {
    // Filtreleme mantığını geçici olarak esnetiyoruz (Hepsini görsün)
    $ziyaretler = $db->fetchAll("
        SELECT z.*, b.byk_adi, g.grup_adi, g.renk_kodu, s.sube_adi, s.adres as sube_adresi
        FROM sube_ziyaretleri z
        INNER JOIN byk b ON z.byk_id = b.byk_id
        INNER JOIN ziyaret_gruplari g ON z.grup_id = g.grup_id
        LEFT JOIN subeler s ON z.sube_id = s.sube_id
        WHERE z.ziyaret_tarihi >= '2026-04-01'
        ORDER BY z.ziyaret_tarihi ASC
        LIMIT 100
    ");

    echo json_encode([
        'success' => true,
        'ziyaretler' => $ziyaretler
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
