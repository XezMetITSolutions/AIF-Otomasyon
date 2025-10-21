<?php
require_once 'auth.php';
require_once 'includes/user_manager_db.php';
require_once 'includes/byk_manager_db.php';

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
    $raw = file_get_contents('php://input');
    error_log("[add_user] raw input=" . $raw);
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz JSON veri']);
        exit;
    }
    
    // Gerekli alanları kontrol et
    $requiredFields = ['first_name', 'last_name', 'username', 'email', 'role'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Gerekli alan eksik: $field"]);
            exit;
        }
    }

    // Rol whitelist (sadece member ve manager)
    $role = strtolower(trim($input['role']));
    if (!in_array($role, ['member', 'manager'])) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz rol. Sadece Üye veya Yönetici olabilir.']);
        exit;
    }
    $input['role'] = $role;
    
    // E-posta formatını kontrol et
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz e-posta formatı']);
        exit;
    }
    
    // Kullanıcı adı ve e-posta benzersizlik kontrolü
    if (UserManager::getUserByUsername($input['username'])) {
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcı adı zaten kullanılıyor']);
        exit;
    }
    
    if (UserManager::getUserByEmail($input['email'])) {
        echo json_encode(['success' => false, 'message' => 'Bu e-posta adresi zaten kullanılıyor']);
        exit;
    }
    
    // BYK kategori ID'sini al
    $bykCategoryId = null;
    if (!empty($input['byk'])) {
        error_log('[add_user] BYK code received: ' . $input['byk']);
        $bykCategory = BYKManager::getBYKCategoryByCode($input['byk']);
        error_log('[add_user] BYK category lookup result: ' . json_encode($bykCategory));
        if ($bykCategory) {
            $bykCategoryId = $bykCategory['id'];
            error_log('[add_user] BYK category ID resolved: ' . $bykCategoryId);
        } else {
            error_log('[add_user] BYK category NOT FOUND for code: ' . $input['byk']);
        }
    } else {
        error_log('[add_user] No BYK code provided in input');
    }
    error_log('[add_user] Final byk_category_id: ' . ($bykCategoryId ?? 'NULL'));
    
    // Alt birim ID'sini al
    $subUnitId = null;
    if (!empty($input['sub_unit'])) {
        $subUnit = BYKManager::getSubUnitByName($input['sub_unit']);
        if ($subUnit) {
            $subUnitId = $subUnit['id'];
        }
    }
    error_log('[add_user] sub_unit=' . ($input['sub_unit'] ?? '') . ' resolved_sub_unit_id=' . ($subUnitId ?? 'NULL'));
    
    // Kullanıcı verilerini hazırla
    $userData = [
        'first_name' => $input['first_name'],
        'last_name' => $input['last_name'],
        'full_name' => $input['first_name'] . ' ' . $input['last_name'],
        'username' => $input['username'],
        'email' => $input['email'],
        'password' => 'AIF571#', // Varsayılan şifre
        'role' => $input['role'],
        'status' => $input['status'] ?? 'active',
        'byk_category_id' => $bykCategoryId,
        'sub_unit_id' => $subUnitId,
        'is_byk_member' => isset($input['is_byk_member']) ? (int)$input['is_byk_member'] : 0,
        'must_change_password' => 1, // İlk girişte şifre değiştirme zorunluluğu
        'created_at' => date('Y-m-d H:i:s')
    ];
    error_log('[add_user] prepared userData=' . json_encode($userData));
    
    // Kullanıcıyı ekle
    try {
        $userId = UserManager::addUser($userData);
        error_log('[add_user] insert result user_id=' . $userId);
        
    } catch (Exception $e) {
        error_log('[add_user] insert error=' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'DB Hatası: ' . $e->getMessage()]);
        exit;
    }

    if ($userId) {
        echo json_encode([
            'success' => true, 
            'message' => 'Kullanıcı başarıyla eklendi',
            'user_id' => $userId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı eklenirken hata oluştu']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>

