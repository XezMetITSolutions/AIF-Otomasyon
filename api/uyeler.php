<?php
/**
 * API - Üyeler/Kullanıcılar Listesi (Next.js için)
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
$canManage = $auth->hasModulePermission('baskan_uyeler');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $q = trim($_GET['q'] ?? '');
    
    $params = [$user['byk_id']];
    $where = 'WHERE k.byk_id = ?';

    if ($q !== '') {
        $where .= " AND (CONCAT(k.ad, ' ', k.soyad) LIKE ? OR k.email LIKE ? OR k.telefon LIKE ?)";
        $keyword = '%' . $q . '%';
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
    }

    $uyeler = $db->fetchAll("
        SELECT k.kullanici_id, k.ad, k.soyad, k.email, k.telefon, k.aktif, k.son_giris, r.rol_adi
        FROM kullanicilar k
        INNER JOIN roller r ON k.rol_id = r.rol_id
        $where
        ORDER BY k.ad ASC
    ", $params);

    // Apply visibility constraints based on permission
    $filteredUyeler = array_map(function($uye) use ($canManage) {
        if (!$canManage) {
            $uye['email'] = null; // Hide sensitive data
            $uye['telefon'] = null;
            $uye['son_giris'] = null;
        }
        return $uye;
    }, $uyeler);

    $aktifSayisi = array_reduce($filteredUyeler, fn($carry, $item) => $carry + ($item['aktif'] ? 1 : 0), 0);

    echo json_encode([
        'success' => true,
        'canManage' => $canManage,
        'stats' => [
            'total' => count($filteredUyeler),
            'active' => $aktifSayisi
        ],
        'uyeler' => $filteredUyeler
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Geçersiz metot.']);
