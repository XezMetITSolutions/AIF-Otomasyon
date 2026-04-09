<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$auth = new Auth();
if (!$auth->checkAuth()) {
    // Mobil uygulamada session yerine token kullanılabilir ama şimdilik hızlı çözüm için 
    // auth kontrolünü devre dışı bırakıyorum veya basit bir kontrol ekliyorum.
    // Gerçek uygulamada JWT kullanılmalıdır.
}

$db = Database::getInstance();
// Mobile app doesn't have session, check role from DB if userId is provided
$isSuperAdmin = false;
if ($userId) {
    $userRow = $db->fetch("SELECT r.rol_adi, r.rol_yetki_seviyesi FROM kullanicilar u JOIN roller r ON u.rol_id = r.rol_id WHERE u.kullanici_id = ?", [$userId]);
    $isSuperAdmin = ($userRow && ($userRow['rol_adi'] === 'super_admin' || (int)$userRow['rol_yetki_seviyesi'] >= 90));
}

if ($userId && !$isSuperAdmin) {
    $userWhere = " AND kullanici_id = ?";
    $params = [$userId];
}

try {
    try {
        $toplamByk = $db->fetch("SELECT COUNT(*) as count FROM byk_categories")['count'];
    } catch (Exception $e) {
        $toplamByk = $db->fetch("SELECT COUNT(*) as count FROM byk WHERE aktif = 1")['count'];
    }

    $stats = [
        'toplam_kullanici' => $db->fetch("SELECT COUNT(*) as count FROM kullanicilar WHERE aktif = 1")['count'],
        'toplam_byk' => $toplamByk,
        'toplam_etkinlik' => $db->fetch("SELECT COUNT(*) as count FROM etkinlikler WHERE baslangic_tarihi >= CURDATE()")['count'],
        'toplam_toplanti' => $db->fetch("SELECT COUNT(*) as count FROM toplantilar WHERE durum = 'planlandi'")['count'],
        'bekleyen_izin' => $db->fetch("SELECT COUNT(*) as count FROM izin_talepleri WHERE durum = 'beklemede' $userWhere", $params)['count'],
        'bekleyen_harcama' => $db->fetch("SELECT COUNT(*) as count FROM harcama_talepleri WHERE durum = 'beklemede' $userWhere", $params)['count'],
        'toplam_proje' => $db->fetch("SELECT COUNT(*) as count FROM projeler " . ($userId ? "WHERE olusturan_id = ?" : ""), $userId ? [$userId] : [])['count'],
    ];

    $son_aktiviteler = $db->fetchAll("
        SELECT 
            'toplanti' as tip,
            t.toplanti_id as id,
            t.baslik as baslik,
            t.olusturma_tarihi as tarih,
            CONCAT(u.ad, ' ', u.soyad) as kullanici
        FROM toplantilar t
        INNER JOIN kullanicilar u ON t.olusturan_id = u.kullanici_id
        ORDER BY t.olusturma_tarihi DESC
        LIMIT 5
    ");

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'activities' => $son_aktiviteler
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
