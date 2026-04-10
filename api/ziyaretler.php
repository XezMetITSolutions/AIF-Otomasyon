<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
$userId = $_GET['userId'] ?? null;

try {
    $where = "WHERE 1=1";
    $params = [];

    // Mobile app doesn't have session, check role from DB if userId is provided
    $isSuperAdmin = false;
    if ($userId) {
        $userRow = $db->fetch("SELECT r.rol_adi, r.rol_yetki_seviyesi FROM kullanicilar u JOIN roller r ON u.rol_id = r.rol_id WHERE u.kullanici_id = ?", [$userId]);
        $isSuperAdmin = ($userRow && ($userRow['rol_adi'] === 'super_admin' || (int)$userRow['rol_yetki_seviyesi'] >= 90));
    }

    // Eğer userId geldiyse ve admin değilse, kullanıcının dahil olduğu grupların ziyaretlerini getir
    if ($userId && !$isSuperAdmin) {
        $where = "WHERE (z.olusturan_id = ? OR z.grup_id IN (SELECT grup_id FROM ziyaret_grup_uyeleri WHERE kullanici_id = ?))";
        $params = [$userId, $userId];
    }

    $ziyaretler = $db->fetchAll("
        SELECT z.*, b.byk_adi, g.grup_adi, g.renk_kodu, s.sube_adi, s.adres as sube_adresi
        FROM sube_ziyaretleri z
        INNER JOIN byk b ON z.byk_id = b.byk_id
        INNER JOIN ziyaret_gruplari g ON z.grup_id = g.grup_id
        LEFT JOIN subeler s ON z.sube_id = s.sube_id
        $where 
        ORDER BY z.ziyaret_tarihi ASC
        LIMIT 100
    ", $params);

    echo json_encode([
        'success' => true,
        'ziyaretler' => $ziyaretler
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
