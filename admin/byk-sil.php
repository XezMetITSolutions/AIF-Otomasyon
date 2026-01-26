<?php
/**
 * Ana Yönetici - BYK Silme (byk_categories tablosu)
 * AJAX endpoint
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Geçersiz istek.';
    echo json_encode($response);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    $response['message'] = 'Geçersiz BYK ID.';
    echo json_encode($response);
    exit;
}

try {
    // BYK var mı kontrol et
    $byk = $db->fetch("SELECT * FROM byk_categories WHERE id = ?", [$id]);
    if (!$byk) {
        $response['message'] = 'BYK bulunamadı.';
        echo json_encode($response);
        exit;
    }
    
    // Bu BYK'ya bağlı kullanıcı var mı kontrol et
    $usersCount = $db->fetch("SELECT COUNT(*) as count FROM users WHERE byk_category_id = ?", [$id])['count'] ?? 0;
    if ($usersCount > 0) {
        $response['message'] = "Bu BYK'ya bağlı {$usersCount} kullanıcı bulunmaktadır. Önce kullanıcıları başka bir BYK'ya taşıyın.";
        echo json_encode($response);
        exit;
    }
    
    // Alt birimler var mı kontrol et
    $subUnitsCount = $db->fetch("SELECT COUNT(*) as count FROM byk_sub_units WHERE byk_category_id = ?", [$id])['count'] ?? 0;
    if ($subUnitsCount > 0) {
        $response['message'] = "Bu BYK'ya bağlı {$subUnitsCount} alt birim bulunmaktadır. Önce alt birimleri silin.";
        echo json_encode($response);
        exit;
    }
    
    // BYK'yı sil
    $db->query("DELETE FROM byk_categories WHERE id = ?", [$id]);
    
    $response['success'] = true;
    $response['message'] = 'BYK başarıyla silindi.';
} catch (Exception $e) {
    $response['message'] = 'Silme işlemi sırasında bir hata oluştu: ' . $e->getMessage();
}

echo json_encode($response);
?>

