<?php
/**
 * Genel Kayıt Silme (AJAX Endpoint)
 * Varsayılan silme işlemi - eğer özel bir silme dosyası yoksa kullanılır
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
    'message' => 'Silme işlemi için özel bir endpoint bulunamadı.'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Geçersiz istek metodu.';
    echo json_encode($response);
    exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
    $response['message'] = 'Kayıt ID gerekli.';
    echo json_encode($response);
    exit;
}

$response['message'] = 'Bu kayıt türü için silme işlemi tanımlanmamış. Lütfen doğru silme endpoint\'ini kullanın.';

echo json_encode($response);
?>

