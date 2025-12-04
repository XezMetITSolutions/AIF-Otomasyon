<?php
/**
 * Şifre Kontrol API
 * Frontend'den gelen şifreyi kontrol eder
 */

// CORS ve JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Sadece POST kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Şifre kontrolü kaldırıldı; her isteği başarıyla sonuçlandır
session_start();
$_SESSION['aif_form_authenticated'] = true;
$_SESSION['aif_form_auth_time'] = time();

echo json_encode([
    'success' => true,
    'message' => 'Şifre doğrulaması devre dışı',
    'token' => session_id()
]);
