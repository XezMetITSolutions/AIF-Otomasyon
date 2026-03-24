<?php
/**
 * API - Giriş Yap (Next.js Entegrasyonu için)
 * JSON formatında yanıt döner
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

// Desteklenen metot kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek metodu.']);
    exit;
}

// JSON Payload'u oku
$input = json_decode(file_get_contents('php://input'), true);

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$remember = isset($input['remember']) ? (bool)$input['remember'] : false;

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'E-posta ve şifre gereklidir.']);
    exit;
}

$auth = new Auth();
$result = $auth->login($email, $password, $remember);

if ($result === true) {
    $user = $auth->getUser();
    
    // Güvenlik açısından şifreyi çıktıdan sil (Gerekirse)
    echo json_encode([
        'success' => true,
        'message' => 'Giriş başarılı!',
        'user' => $user
    ]);
} elseif ($result === 'password_change_required') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'password_change_required',
        'message' => 'Şifrenizi değiştirmeniz gerekmektedir.'
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'E-posta veya şifre hatalı.'
    ]);
}
