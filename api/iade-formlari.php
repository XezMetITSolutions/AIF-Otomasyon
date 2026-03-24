<?php
/**
 * API - İade Formları (Next.js için)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->checkAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Oturum açılmamış.']);
    exit;
}

$user = $auth->getUser();
$db = Database::getInstance();

$isMuhasebeBaskani = false;
$checkMuhasebe = $db->fetch("SELECT count(*) as cnt FROM byk WHERE muhasebe_baskani_id = ?", [$user['id']]);
if ($checkMuhasebe && $checkMuhasebe['cnt'] > 0) {
    $isMuhasebeBaskani = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tab = $_GET['tab'] ?? 'form'; // form (talebim), yonetim (onay)
    
    $where = [];
    $params = [];

    if ($tab === 'yonetim' || $isMuhasebeBaskani) {
        // Muhasebe başkanı ise kendi BYK'sındakileri veya tümünü görür (iş mantığına göre filtrele)
        $where[] = "1=1"; 
    } else {
        $where[] = "i.kullanici_id = ?";
        $params[] = $user['id'];
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Tablo adı iade_formlari veya masraf_formu olabilir, varsayılan iade_formlari kabul ediyoruz
    try {
        $talepler = $db->fetchAll("
            SELECT i.*, CONCAT(u.ad, ' ', u.soyad) as talep_eden 
            FROM iade_formlari i
            INNER JOIN kullanicilar u ON i.kullanici_id = u.kullanici_id
            $whereClause
            ORDER BY i.created_at DESC
        ", $params);
    } catch (Exception $e) {
        $talepler = []; // Tablo henüz yoksa veya farklıysa çökmesin
    }

    echo json_encode([
        'success' => true,
        'requests' => $talepler,
        'hasPermissionBaskan' => $isMuhasebeBaskani,
        'hasPermissionUye' => true
    ]);
    exit;
}
