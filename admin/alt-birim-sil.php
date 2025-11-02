<?php
/**
 * Alt Birim Silme (AJAX Endpoint)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

header('Content-Type: application/json');

$db = Database::getInstance();

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Geçersiz istek metodu.';
    echo json_encode($response);
    exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
    $response['message'] = 'Alt birim ID gerekli.';
    echo json_encode($response);
    exit;
}

try {
    // Alt birim var mı kontrol et
    $altBirim = $db->fetch("SELECT * FROM byk_sub_units WHERE id = ?", [$id]);
    if (!$altBirim) {
        $response['message'] = 'Alt birim bulunamadı.';
        echo json_encode($response);
        exit;
    }
    
    // Bu alt birime bağlı kullanıcı var mı kontrol et
    $usersCount = $db->fetch("SELECT COUNT(*) as count FROM kullanicilar WHERE alt_birim_id = ?", [$id])['count'] ?? 0;
    if ($usersCount > 0) {
        $response['message'] = "Bu alt birime bağlı {$usersCount} kullanıcı bulunmaktadır. Önce kullanıcıları başka bir alt birime taşıyın.";
        echo json_encode($response);
        exit;
    }
    
    // Alt birimi sil
    $db->query("DELETE FROM byk_sub_units WHERE id = ?", [$id]);
    
    $response['success'] = true;
    $response['message'] = 'Alt birim başarıyla silindi.';
} catch (Exception $e) {
    $response['message'] = 'Silme işlemi sırasında bir hata oluştu: ' . $e->getMessage();
}

echo json_encode($response);
?>

