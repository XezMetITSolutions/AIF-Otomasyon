<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();

try {
    // Sadece bugünden sonraki etkinlikleri, en yakından uzağa doğru (ASC) getiriyoruz
    $etkinlikler = $db->fetchAll("
        SELECT e.*, 
               COALESCE(b.byk_adi, '-') as byk_adi,
               COALESCE(b.byk_kodu, '') as byk_kodu,
               COALESCE(b.renk_kodu, e.renk_kodu, '#009872') as byk_renk
        FROM etkinlikler e
        LEFT JOIN byk b ON e.byk_id = b.byk_id
        WHERE e.baslangic_tarihi >= CURDATE()
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 100
    ");

    echo json_encode([
        'success' => true,
        'etkinlikler' => $etkinlikler
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
