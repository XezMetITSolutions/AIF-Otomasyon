<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$auth = new Auth();
// Şimdilik test amaçlı auth kontrolünü gevşek tutuyoruz (daha sonra JWT eklenebilir)

$db = Database::getInstance();

try {
    $users = $db->fetchAll("
        SELECT u.kullanici_id, u.ad, u.soyad, u.email, u.aktif, r.rol_adi, b.byk_adi
        FROM kullanicilar u
        LEFT JOIN roller r ON u.rol_id = r.rol_id
        LEFT JOIN byk b ON u.byk_id = b.byk_id
        ORDER BY u.ad ASC
    ");

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
