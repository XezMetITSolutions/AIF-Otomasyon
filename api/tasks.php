<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$auth = new Auth();
$db = Database::getInstance();

$type = $_GET['type'] ?? 'izin';
$userId = $_GET['userId'] ?? null;
$scope = $_GET['scope'] ?? null;

// Mobile app doesn't have session, check role from DB if userId is provided
$isSuperAdmin = false;
if ($userId) {
    $userRow = $db->fetch("SELECT r.rol_adi, r.rol_yetki_seviyesi FROM kullanicilar u JOIN roller r ON u.rol_id = r.rol_id WHERE u.kullanici_id = ?", [$userId]);
    $isSuperAdmin = ($userRow && ($userRow['rol_adi'] === 'super_admin' || (int)$userRow['rol_yetki_seviyesi'] >= 90));
}

$where = "WHERE 1=1";
$params = [];

if ($scope === 'my' && $userId) {
    $where .= " AND i.kullanici_id = ?";
    $params[] = $userId;
} elseif ($userId && !$isSuperAdmin) {
    // Admin değilse sadece kendi taleplerini görsün
    $where .= " AND i.kullanici_id = ?";
    $params[] = $userId;
}

try {
    if ($type === 'izin') {
        $tasks = $db->fetchAll("
            SELECT i.*, CONCAT(u.ad, ' ', u.soyad) as ad_soyad 
            FROM izin_talepleri i 
            LEFT JOIN kullanicilar u ON i.kullanici_id = u.kullanici_id 
            $where
            ORDER BY i.olusturma_tarihi DESC
        ", $params);
    } elseif ($type === 'harcama' || $type === 'rezervasyon') {
        // Harcama/Rezervasyon için WHERE clause'u güncelle (alias h kullanılıyor)
        $whereH = str_replace('i.', 'h.', $where);
        
        // Önce rezervasyon_talepleri tablosunu kontrol et, boşsa harcama_talepleri'ne bak
        $tasks = $db->fetchAll("
            SELECT h.*, CONCAT(u.ad, ' ', u.soyad) as ad_soyad 
            FROM rezervasyon_talepleri h 
            LEFT JOIN kullanicilar u ON h.kullanici_id = u.kullanici_id 
            $whereH
            ORDER BY h.olusturma_tarihi DESC
        ", $params);

        if (empty($tasks)) {
            $tasks = $db->fetchAll("
                SELECT h.*, CONCAT(u.ad, ' ', u.soyad) as ad_soyad 
                FROM harcama_talepleri h 
                LEFT JOIN kullanicilar u ON h.kullanici_id = u.kullanici_id 
                $whereH
                ORDER BY h.olusturma_tarihi DESC
            ", $params);
        }
    } else {
        $tasks = [];
    }

    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
