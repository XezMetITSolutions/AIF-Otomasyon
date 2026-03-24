<?php
/**
 * API - Çalışma Takvimi / Etkinlikler (Next.js için)
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
$canManage = $auth->hasModulePermission('baskan_etkinlikler');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $monthFilter = $_GET['ay'] ?? '';
    $yearFilter = $_GET['yil'] ?? '';
    $birimFilter = $_GET['birim'] ?? '';

    $where = [];
    $params = [];

    if ($monthFilter) {
        $where[] = "MONTH(e.baslangic_tarihi) = ?";
        $params[] = $monthFilter;
    }

    if ($yearFilter) {
        $where[] = "YEAR(e.baslangic_tarihi) = ?";
        $params[] = $yearFilter;
    }

    if ($birimFilter) {
        $where[] = "b.byk_kodu = ?";
        $params[] = $birimFilter;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $isAdmin = $auth->isSuperAdmin();
    $userByk = $db->fetch("SELECT b.* FROM byk b JOIN kullanicilar k ON b.byk_id = k.byk_id WHERE k.kullanici_id = ?", [$user['id']]);
    $userBykKodu = $userByk['byk_kodu'] ?? '';

    $etkinlikler = [];
    try {
        $etkinlikler = $db->fetchAll("
            SELECT e.*, 
                   COALESCE(b.byk_adi, '-') as byk_adi,
                   COALESCE(b.byk_kodu, '') as byk_kodu,
                   COALESCE(b.renk_kodu, e.renk_kodu, '#009872') as byk_renk,
                   COALESCE(CONCAT(u.ad, ' ', u.soyad), '-') as olusturan
            FROM etkinlikler e
            LEFT JOIN byk b ON e.byk_id = b.byk_id
            LEFT JOIN kullanicilar u ON e.olusturan_id = u.kullanici_id
            $whereClause
            ORDER BY e.baslangic_tarihi ASC
            LIMIT 500
        ", $params);
    } catch (Exception $e) {
        // Table might be missing fields, fallback gracefully
    }

    // Available units for filters
    $availableTypes = [];
    try {
        $units = $db->fetchAll("SELECT DISTINCT byk_kodu FROM byk WHERE byk_kodu IN ('AT', 'KGT', 'KT', 'GT') ORDER BY FIELD(byk_kodu, 'AT', 'KGT', 'KT', 'GT')");
        foreach($units as $u) {
            if (!$isAdmin && $u['byk_kodu'] !== $userBykKodu) continue;
            $availableTypes[] = $u['byk_kodu'];
        }
    } catch(Exception $e) {}


    echo json_encode([
        'success' => true,
        'canManage' => $canManage,
        'isAdmin' => $isAdmin,
        'userBykKodu' => $userBykKodu,
        'etkinlikler' => $etkinlikler,
        'filters' => $availableTypes
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Geçersiz metot.']);
