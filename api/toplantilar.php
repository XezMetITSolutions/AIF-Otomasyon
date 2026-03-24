<?php
/**
 * API - Toplantılar (Next.js için)
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
$canManage = $auth->hasModulePermission('baskan_toplantilar');

// Check if user belongs to 'AT' (Global Admin Unit)
$userByk = $db->fetch("SELECT * FROM byk WHERE byk_id = ?", [$user['byk_id']]);
$isAdmin = ($userByk && $userByk['byk_kodu'] === 'AT');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tab = $_GET['tab'] ?? 'gelecek'; // gelecek, gecmis
    $monthFilter = $_GET['ay'] ?? '';
    $bykFilter = $_GET['byk'] ?? '';

    $where = [];
    $params = [];

    // BYK filter
    if ($isAdmin) {
        if ($bykFilter) {
            $where[] = "t.byk_id = ?";
            $params[] = $bykFilter;
        }
    } else {
        $where[] = "EXISTS (SELECT 1 FROM toplanti_katilimcilar tk WHERE tk.toplanti_id = t.toplanti_id AND tk.kullanici_id = ?)";
        $params[] = $user['id'];
    }

    // Date filter (future/past)
    if ($tab === 'gelecek') {
        $where[] = "t.toplanti_tarihi >= CURDATE()";
    } else {
        $where[] = "t.toplanti_tarihi < CURDATE()";
    }

    // Month filter
    if ($monthFilter) {
        $where[] = "DATE_FORMAT(t.toplanti_tarihi, '%Y-%m') = ?";
        $params[] = $monthFilter;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $toplantilar = $db->fetchAll("
        SELECT t.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as olusturan,
               (SELECT COUNT(*) FROM toplanti_katilimcilar tk WHERE tk.toplanti_id = t.toplanti_id) as total_participants,
               (SELECT COUNT(*) FROM toplanti_katilimcilar tk WHERE tk.toplanti_id = t.toplanti_id AND tk.katilim_durumu = 'katilacak') as confirmed_participants
        FROM toplantilar t
        INNER JOIN byk b ON t.byk_id = b.byk_id
        INNER JOIN kullanicilar u ON t.olusturan_id = u.kullanici_id
        $whereClause
        ORDER BY t.toplanti_tarihi " . ($tab === 'gelecek' ? 'ASC' : 'DESC') . "
        LIMIT 100
    ", $params);

    // Get BYK list for filter (admin only)
    $bykList = [];
    if ($isAdmin) {
        $bykList = $db->fetchAll("SELECT byk_id, byk_adi FROM byk ORDER BY byk_adi");
    }

    echo json_encode([
        'success' => true,
        'canManage' => $canManage,
        'isAdmin' => $isAdmin,
        'toplantilar' => $toplantilar,
        'bykList' => $bykList
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Geçersiz metot.']);
