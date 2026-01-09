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
    Middleware::requireRole([Auth::ROLE_SUPER_ADMIN, Auth::ROLE_UYE]);
    
    $auth = new Auth();
    $user = $auth->getUser();
    
    // Başkan sadece kendi BYK'sını görebilir
    if ($user['role'] === Auth::ROLE_UYE) {
        if (isset($_GET['byk_id']) && $_GET['byk_id'] != $user['byk_id']) {
            throw new Exception('Kendi BYK\'nız dışındaki üyeleri görüntüleyemezsiniz.');
        }
        // BYK ID'yi zorla kullanıcı BYK'sı yap (GET parametresi gelmese bile)
        $_GET['byk_id'] = $user['byk_id'];
    }
    
    $db = Database::getInstance();
    $byk_id = $_GET['byk_id'] ?? null;
    $divan_only = isset($_GET['divan_only']) && $_GET['divan_only'] === 'true';
    
    if (!$byk_id) {
        throw new Exception('BYK ID gereklidir');
    }
    
    $whereClause = "k.byk_id = ? AND k.aktif = 1";
    if ($divan_only) {
        $whereClause .= " AND k.divan_uyesi = 1";
    }

    // BYK'ye ait aktif üyeleri getir
    $uyeler = $db->fetchAll("
        SELECT 
            k.kullanici_id,
            k.ad,
            k.soyad,
            k.email,
            k.divan_uyesi,
            r.rol_adi,
            ab.alt_birim_adi
        FROM kullanicilar k
        LEFT JOIN roller r ON k.rol_id = r.rol_id
        LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
        WHERE $whereClause
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
