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
    // Alt birim var mı kontrol et (byk_sub_units tablosunda)
    $altBirim = $db->fetch("SELECT * FROM byk_sub_units WHERE id = ?", [$id]);
    if (!$altBirim) {
        $response['message'] = 'Alt birim bulunamadı.';
        echo json_encode($response);
        exit;
    }
    
    // alt_birimler tablosunda karşılık gelen kaydı bul (alt_birim_adi ile eşleştir)
    $altBirimAdi = $altBirim['name'];
    $bykCategoryId = $altBirim['byk_category_id'];
    
    // BYK kodu bul
    $bykCategory = $db->fetch("SELECT code FROM byk_categories WHERE id = ?", [$bykCategoryId]);
    $bykCode = $bykCategory['code'] ?? null;
    
    // byk_id bul
    $bykId = null;
    if ($bykCode) {
        $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ? LIMIT 1", [$bykCode]);
        if ($byk) {
            $bykId = $byk['byk_id'];
        }
    }
    
    // alt_birimler tablosunda kayıt bul
    $altBirimlerRecord = null;
    if ($bykId) {
        $altBirimlerRecord = $db->fetch("SELECT alt_birim_id FROM alt_birimler WHERE byk_id = ? AND alt_birim_adi = ? LIMIT 1", [$bykId, $altBirimAdi]);
    }
    
    // Bu alt birime bağlı kullanıcı var mı kontrol et (alt_birimler.alt_birim_id ile)
    $usersCount = 0;
    if ($altBirimlerRecord) {
        $usersCount = $db->fetch("SELECT COUNT(*) as count FROM kullanicilar WHERE alt_birim_id = ?", [$altBirimlerRecord['alt_birim_id']])['count'] ?? 0;
    }
    
    if ($usersCount > 0) {
        $response['message'] = "Bu alt birime bağlı {$usersCount} kullanıcı bulunmaktadır. Önce kullanıcıları başka bir alt birime taşıyın.";
        echo json_encode($response);
        exit;
    }
    
    // Alt birimi sil - önce alt_birimler tablosundan, sonra byk_sub_units tablosundan
    if ($altBirimlerRecord) {
        $db->query("DELETE FROM alt_birimler WHERE alt_birim_id = ?", [$altBirimlerRecord['alt_birim_id']]);
    }
    
    // byk_sub_units tablosundan sil
    $db->query("DELETE FROM byk_sub_units WHERE id = ?", [$id]);
    
    $response['success'] = true;
    $response['message'] = 'Alt birim başarıyla silindi.';
} catch (Exception $e) {
    $response['message'] = 'Silme işlemi sırasında bir hata oluştu: ' . $e->getMessage();
}

echo json_encode($response);
?>

