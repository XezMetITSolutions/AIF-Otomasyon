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

// Şifreyi al
$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';

// Load environment variables
require_once __DIR__ . '/../includes/load_env.php';

// Gerçek şifre (sadece sunucuda)
$CORRECT_PASSWORD = getenv('HESAPLAMA_PASSWORD') ?: 'fatura!1234';

// Şifreyi kontrol et
if (hash_equals($CORRECT_PASSWORD, $password)) {
    // Şifre doğru - session oluştur
    session_start();
    $_SESSION['aif_form_authenticated'] = true;
    $_SESSION['aif_form_auth_time'] = time();

    echo json_encode([
        'success' => true,
        'message' => 'Giriş başarılı',
        'token' => session_id()
    ]);
} else {
    // Şifre yanlış
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Hatalı şifre'
    ]);

    // Brute force koruması için log
    error_log('Failed login attempt from ' . $_SERVER['REMOTE_ADDR']);
}
