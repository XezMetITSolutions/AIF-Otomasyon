<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$email = $_GET['email'] ?? 'mete.burcak@gmx.at';

$user = $db->fetch("
    SELECT u.kullanici_id, u.ad, u.soyad, u.email, u.byk_id, r.rol_adi, r.rol_yetki_seviyesi 
    FROM kullanicilar u 
    INNER JOIN roller r ON u.rol_id = r.rol_id 
    WHERE u.email = ?
", [$email]);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$permissions = $db->fetchAll("SELECT * FROM panel_yetkileri WHERE kullanici_id = ?", [$user['kullanici_id']]);

echo json_encode([
    'success' => true,
    'user' => $user,
    'permissions' => $permissions
]);
exit;
