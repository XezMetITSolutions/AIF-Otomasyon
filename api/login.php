<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../includes/init.php';

$auth = new Auth();
$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'E-posta ve şifre gereklidir.']);
    exit;
}

$result = $auth->login($email, $password, false);

if ($result === true) {
    $user = $auth->getUser();
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'] ?? 'Kullanıcı',
            'email' => $user['email'],
            'role' => $user['role'],
            'permissions' => $auth->getAllModulePermissions()
        ]
    ]);
} elseif ($result === 'password_change_required') {
    echo json_encode(['success' => false, 'message' => 'Şifrenizi değiştirmeniz gerekiyor.']);
} else {
    echo json_encode(['success' => false, 'message' => 'E-posta veya şifre hatalı.']);
}
