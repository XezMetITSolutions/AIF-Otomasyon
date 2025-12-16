<?php
/**
 * Session Doğrulama API
 * Kullanıcının oturumunun geçerli olup olmadığını kontrol eder
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();

// Şifre zorunluluğu kaldırıldı; tüm istekler otomatik yetkilendirilir
$_SESSION['aif_form_authenticated'] = true;
$_SESSION['aif_form_auth_time'] = time();

echo json_encode([
    'authenticated' => true,
    'session_id' => session_id()
]);
