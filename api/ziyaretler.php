<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
$userId = $_GET['userId'] ?? null;

try {
    $where = ["1=1"];
    $params = [];

    if ($userId) {
        $user = $db->fetch("SELECT role FROM kullanicilar WHERE kullanici_id = ?", [$userId]);
        $isSuperAdmin = ($user && $user['role'] === 'super_admin');

        if (!$isSuperAdmin) {
            // Sadece dahil olduğu grupları gör
            $where[] = "(g.baskan_id = ? OR EXISTS (SELECT 1 FROM ziyaret_grup_uyeleri gu WHERE gu.grup_id = z.grup_id AND gu.kullanici_id = ?))";
            $params[] = $userId;
            $params[] = $userId;
        }
    }

    $whereClause = implode(' AND ', $where);

    $ziyaretler = $db->fetchAll("
        SELECT z.*, b.byk_adi, g.grup_adi, g.renk_kodu
        FROM sube_ziyaretleri z
        INNER JOIN byk b ON z.byk_id = b.byk_id
        INNER JOIN ziyaret_gruplari g ON z.grup_id = g.grup_id
        WHERE $whereClause
        ORDER BY z.ziyaret_tarihi DESC
        LIMIT 100
    ", $params);

    echo json_encode([
        'success' => true,
        'ziyaretler' => $ziyaretler,
        'debug' => [
            'userId' => $userId,
            'isSuperAdmin' => $isSuperAdmin ?? false,
            'queryWhere' => $whereClause,
            'params' => $params
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
