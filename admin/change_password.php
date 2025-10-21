<?php
require_once 'includes/auth.php';
require_once 'includes/user_manager_db.php';

// Login kontrolü - GEÇİCİ OLARAK DEVRE DIŞI
// SessionManager::requireRole(['superadmin', 'manager']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Sadece POST metodu kabul edilir']);
    exit;
}

try {
    // JSON verisini al
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz JSON verisi']);
        exit;
    }
    
    // Gerekli alanları kontrol et
    if (empty($input['username'])) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı gerekli']);
        exit;
    }
    
    if (empty($input['password'])) {
        echo json_encode(['success' => false, 'message' => 'Şifre gerekli']);
        exit;
    }
    
    // Şifre uzunluk kontrolü
    if (strlen($input['password']) < 6) {
        echo json_encode(['success' => false, 'message' => 'Şifre en az 6 karakter olmalıdır']);
        exit;
    }
    
    // Kullanıcıyı bul
    $user = UserManager::getUserByUsername($input['username']);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }
    
    // Şifreyi hash'le
    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
    
    // Şifreyi güncelle
    $result = UserManager::updateUser($user['id'], [
        'password' => $hashedPassword,
        'must_change_password' => 0, // Şifre değiştirildi, tekrar değiştirmek zorunda değil
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Şifre başarıyla değiştirildi'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Şifre değiştirilirken hata oluştu']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
