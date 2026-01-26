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

// Session kontrolü
$isAuthenticated = isset($_SESSION['aif_form_authenticated']) && $_SESSION['aif_form_authenticated'] === true;

// Session timeout kontrolü (2 saat)
$sessionTimeout = 2 * 60 * 60; // 2 saat
if ($isAuthenticated && isset($_SESSION['aif_form_auth_time'])) {
    $elapsed = time() - $_SESSION['aif_form_auth_time'];
    if ($elapsed > $sessionTimeout) {
        // Session süresi dolmuş
        session_destroy();
        $isAuthenticated = false;
    }
}

echo json_encode([
    'authenticated' => $isAuthenticated,
    'session_id' => $isAuthenticated ? session_id() : null
]);
