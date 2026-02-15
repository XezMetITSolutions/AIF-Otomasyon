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
    error_log("AIF API: Request received for BYK " . ($_GET['byk_id'] ?? 'null') . " by user " . ($user['id'] ?? 'unknown'));
    session_write_close(); 

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

    // Seçilen BYK'nın kodunu bul (byk tablosundan veya byk_categories'den)
    $bykCode = null;
    $byk = $db->fetch("SELECT byk_kodu FROM byk WHERE byk_id = ?", [$byk_id]);
    if ($byk) {
        $bykCode = $byk['byk_kodu'];
    } else {
        try {
            $cat = $db->fetch("SELECT code FROM byk_categories WHERE id = ?", [$byk_id]);
            if ($cat) $bykCode = $cat['code'];
        } catch (Exception $e) {}
    }

    // Eğer kod AT, GT, KGT, KT ise, bu koda sahip TÜM birimlerin üyelerini getir
    $targetBykIds = [$byk_id];
    if ($bykCode && in_array(strtoupper($bykCode), ['AT', 'GT', 'KGT', 'KT'])) {
        $code = strtoupper($bykCode);
        // byk tablosundaki aynı kodlu ID'leri ekle
        $relatedByks = $db->fetchAll("SELECT byk_id FROM byk WHERE UPPER(byk_kodu) = ?", [$code]);
        foreach ($relatedByks as $rb) $targetBykIds[] = $rb['byk_id'];
        
        // byk_categories tablosundaki aynı kodlu ID'leri ekle
        try {
            $relatedCats = $db->fetchAll("SELECT id FROM byk_categories WHERE UPPER(code) = ?", [$code]);
            foreach ($relatedCats as $rc) $targetBykIds[] = $rc['id'];
        } catch (Exception $e) {}
    }
    
    $targetBykIds = array_unique(array_filter($targetBykIds));
    $placeholders = implode(',', array_fill(0, count($targetBykIds), '?'));

    // BYK'ye ait aktif üyeleri getir (Hem ana BYK hem de ikincil BYK'lar)
    $uyeler = $db->fetchAll("
        SELECT DISTINCT
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
        LEFT JOIN kullanici_byklar kb ON k.kullanici_id = kb.kullanici_id
        WHERE (k.byk_id IN ($placeholders) OR kb.byk_id IN ($placeholders)) AND k.aktif = 1
        " . ($divan_only ? " AND k.divan_uyesi = 1" : "") . "
        ORDER BY k.ad, k.soyad
    ", array_merge($targetBykIds, $targetBykIds));

    echo json_encode([
        'success' => true,
        'uyeler' => $uyeler,
        'count' => count($uyeler),
        'debug_code' => $bykCode,
        'debug_ids' => $targetBykIds
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
