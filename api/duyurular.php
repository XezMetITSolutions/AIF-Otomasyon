<?php
/**
 * API - Duyurular (Next.js için)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Notification.php';

header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->checkAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Oturum açılmamış.']);
    exit;
}

$user = $auth->getUser();
$db = Database::getInstance();
$canManage = $auth->hasModulePermission('baskan_duyurular');
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $whereClause = "d.byk_id = " . (int)($user['byk_id'] ?? 0);
        if (!$canManage) {
            $whereClause .= " AND d.aktif = 1";
        }

        $duyurular = $db->fetchAll("
            SELECT d.*, CONCAT(k.ad, ' ', k.soyad) as olusturan
            FROM duyurular d
            LEFT JOIN kullanicilar k ON d.olusturan_id = k.kullanici_id
            WHERE $whereClause
            ORDER BY d.olusturma_tarihi DESC
        ");

        echo json_encode([
            'success' => true,
            'canManage' => $canManage,
            'duyurular' => $duyurular
        ]);
        exit;
    }

    if ($method === 'POST') {
        if (!$canManage) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Bu işlem için yetkiniz bulunmamaktadır.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'create') {
            $baslik = trim($input['baslik'] ?? '');
            $icerik = trim($input['icerik'] ?? '');
            
            if (!$baslik || !$icerik) {
                echo json_encode(['success' => false, 'error' => 'Başlık ve içerik zorunludur.']);
                exit;
            }

            $db->query("
                INSERT INTO duyurular (byk_id, baslik, icerik, olusturan_id, aktif)
                VALUES (?, ?, ?, ?, 1)
            ", [$user['byk_id'], $baslik, $icerik, $user['id']]);

            // BYK üyelerine bildirim gönder
            $usersToNotify = $db->fetchAll("SELECT kullanici_id FROM kullanicilar WHERE byk_id = ? AND aktif = 1 AND kullanici_id != ?", [$user['byk_id'], $user['id']]);
            foreach ($usersToNotify as $uNotify) {
                Notification::add($uNotify['kullanici_id'], 'Yeni Duyuru: ' . $baslik, 'BYK Başkanlığı tarafından yeni bir duyuru paylaşıldı.', 'bilgi', '/panel/duyurular.php');
            }

            echo json_encode(['success' => true, 'message' => 'Duyuru yayınlandı.']);
            exit;
        }

        if ($action === 'toggle') {
            $duyuruId = (int)($input['duyuru_id'] ?? 0);
            
            $duyuru = $db->fetch("
                SELECT duyuru_id, aktif FROM duyurular
                WHERE duyuru_id = ? AND byk_id = ?
            ", [$duyuruId, $user['byk_id']]);

            if (!$duyuru) {
                echo json_encode(['success' => false, 'error' => 'Duyuru bulunamadı.']);
                exit;
            }

            $yeniDurum = $duyuru['aktif'] ? 0 : 1;
            $db->query("
                UPDATE duyurular SET aktif = ? WHERE duyuru_id = ?
            ", [$yeniDurum, $duyuruId]);

            echo json_encode([
                'success' => true, 
                'message' => $yeniDurum ? 'Duyuru yeniden yayınlandı.' : 'Duyuru taslağa alındı.',
                'newStatus' => $yeniDurum
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Geçersiz işlem.']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Sunucu hatası oluştu.',
        'message' => $e->getMessage()
    ]);
}
