<?php
/**
 * API - Aktif Kullanıcı Profilini Getir (Next.js için)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->checkAuth()) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'Oturum açılmamış.', 
        'message' => 'Lütfen giriş yapınız.'
    ]);
    exit;
}

$user = $auth->getUser();

$permissions = [];
$isMuhasebeBaskani = false;
$isAT = false;

if ($user) {
    $db = Database::getInstance();
    try {
        // İzin Matrisi
        $permRows = $db->fetchAll("SELECT modül_adi FROM panel_yetkileri WHERE kullanici_id = ?", [$user['id']]);
        foreach ($permRows as $row) {
            $permissions[] = $row['modül_adi'];
        }

        // Muhasebe Başkanı kontrolü
        $checkMuhasebe = $db->fetch("SELECT count(*) as cnt FROM byk WHERE muhasebe_baskani_id = ?", [$user['id']]);
        if ($checkMuhasebe && $checkMuhasebe['cnt'] > 0) {
            $isMuhasebeBaskani = true;
        }

        // AT kontrolü
        $checkAT = $db->fetch("SELECT b.byk_kodu FROM byk b JOIN kullanicilar k ON b.byk_id = k.byk_id WHERE k.kullanici_id = ?", [$user['id']]);
        if ($checkAT && $checkAT['byk_kodu'] === 'AT') {
            $isAT = true;
        }
    } catch (Exception $e) {}
}

echo json_encode([
    'success' => true,
    'user' => $user,
    'permissions' => $permissions,
    'isMuhasebeBaskani' => $isMuhasebeBaskani,
    'isAT' => $isAT
]);
exit;
