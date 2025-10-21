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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Debug: Gelen veriyi logla
    error_log("Delete User Request: " . json_encode($input));
    
    // Gerekli alanları kontrol et
    if (empty($input['username'])) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı gerekli']);
        exit;
    }
    
    // Kullanıcıyı bul - daha güvenli yöntem
    $user = null;
    try {
        $user = UserManager::getUserByUsername($input['username']);
    } catch (Exception $e) {
        error_log("getUserByUsername Error: " . $e->getMessage());
        // Fallback: doğrudan veritabanından çek
        try {
            $db = Database::getInstance();
            $user = $db->fetchOne("SELECT * FROM users WHERE username = ?", [$input['username']]);
        } catch (Exception $e2) {
            error_log("Direct DB Query Error: " . $e2->getMessage());
        }
    }
    
    // Debug için log ekle
    error_log("Delete User Debug - Username: " . $input['username']);
    error_log("Delete User Debug - User found: " . ($user ? 'Yes' : 'No'));
    if ($user) {
        error_log("Delete User Debug - User data: " . json_encode($user));
    }
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı: ' . $input['username']]);
        exit;
    }
    
    // aif-admin hesabını silmeyi engelle
    if ($user['username'] === 'aif-admin' || $user['username'] === 'AIF-Admin') {
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcı silinemez! (Ana yönetici hesabı)']);
        exit;
    }
    
    // Kendi hesabını silmeye çalışıyorsa engelle
    if (isset($currentUser) && $user['username'] === $currentUser['username']) {
        echo json_encode(['success' => false, 'message' => 'Kendi hesabınızı silemezsiniz']);
        exit;
    }
    
    // Kullanıcıyı sil - daha güvenli yöntem
    try {
        $result = UserManager::deleteUser($user['id']);
        error_log("Delete User Result: " . $result);
        
        if ($result > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Kullanıcı başarıyla silindi',
                'deleted_rows' => $result
            ]);
        } else {
            // Fallback: doğrudan veritabanından sil
            try {
                $db = Database::getInstance();
                $result = $db->delete('users', 'id = ?', [$user['id']]);
                error_log("Direct Delete Result: " . $result);
                
                if ($result > 0) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Kullanıcı başarıyla silindi (fallback)',
                        'deleted_rows' => $result
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Kullanıcı silinirken hata oluştu - hiçbir satır etkilenmedi']);
                }
            } catch (Exception $e2) {
                error_log("Direct Delete Error: " . $e2->getMessage());
                echo json_encode(['success' => false, 'message' => 'Silme hatası (fallback): ' . $e2->getMessage()]);
            }
        }
    } catch (Exception $deleteError) {
        error_log("Delete User Error: " . $deleteError->getMessage());
        echo json_encode(['success' => false, 'message' => 'Silme hatası: ' . $deleteError->getMessage()]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>

