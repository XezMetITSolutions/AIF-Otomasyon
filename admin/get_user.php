<?php
require_once 'auth.php';
require_once 'includes/user_manager_db.php';

header('Content-Type: application/json');

// Geçici olarak giriş kontrolü devre dışı
// $currentUser = SessionManager::getCurrentUser();
// if (!$currentUser || !in_array($currentUser['role'], ['manager', 'superadmin'])) {
//     echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

try {
    $action = $_GET['action'] ?? '';
    $username = $_GET['username'] ?? '';
    
    if ($action === 'list') {
        // Tüm kullanıcıları listele
        $users = UserManager::getAllUsers();
        
        // Şifre hash'lerini gizle
        foreach ($users as &$user) {
            unset($user['password_hash']);
        }
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
        exit;
    }
    
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı gerekli']);
        exit;
    }
    
    $user = UserManager::getUserByUsername($username);
    
    // Debug için log ekle
    error_log("Get User Debug - Username: " . $username);
    error_log("Get User Debug - User found: " . ($user ? 'Yes' : 'No'));
    if ($user) {
        error_log("Get User Debug - User data: " . json_encode($user));
    }
    
    if ($user) {
        // Şifre hash'ini gizle
        unset($user['password_hash']);
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı: ' . $username]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>

