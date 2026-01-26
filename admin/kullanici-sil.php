<?php
/**
 * Ana Yönetici - Kullanıcı Silme (AJAX Endpoint)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

Middleware::requireSuperAdmin();

$auth = new Auth();
$db = Database::getInstance();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Geçersiz istek metodu.';
    echo json_encode($response);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$id) {
    $response['message'] = 'Kullanıcı ID gereklidir.';
    echo json_encode($response);
    exit;
}

// Kendi hesabını silmeyi engelle
$currentUser = $auth->getUser();
if ($id == $currentUser['id']) {
    $response['message'] = 'Kendi hesabınızı silemezsiniz.';
    echo json_encode($response);
    exit;
}

try {
    // Soft delete - aktif = 0 yap
    $db->query("UPDATE kullanicilar SET aktif = 0 WHERE kullanici_id = ?", [$id]);
    $response['success'] = true;
    $response['message'] = 'Kullanıcı başarıyla silindi.';
} catch (Exception $e) {
    $response['message'] = 'Kullanıcı silinirken bir hata oluştu: ' . $e->getMessage();
}

echo json_encode($response);
exit;

