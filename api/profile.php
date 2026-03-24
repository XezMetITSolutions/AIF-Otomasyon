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

echo json_encode([
    'success' => true,
    'user' => $user
]);
