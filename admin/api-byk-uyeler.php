<?php
/**
 * API - BYK Üyelerini Getir
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

try {
    Middleware::requireSuperAdmin();
    
    $db = Database::getInstance();
    $byk_id = $_GET['byk_id'] ?? null;
    
    if (!$byk_id) {
        throw new Exception('BYK ID gereklidir');
    }
    
    // BYK'ye ait aktif üyeleri getir
    $uyeler = $db->fetchAll("
        SELECT 
            k.kullanici_id,
            k.ad,
            k.soyad,
            k.email,
            r.rol_adi,
            ab.alt_birim_adi
        FROM kullanicilar k
        LEFT JOIN roller r ON k.rol_id = r.rol_id
        LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
        WHERE k.byk_id = ? 
        AND k.aktif = 1
        ORDER BY k.ad, k.soyad
    ", [$byk_id]);
    
    echo json_encode([
        'success' => true,
        'uyeler' => $uyeler,
        'count' => count($uyeler)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
