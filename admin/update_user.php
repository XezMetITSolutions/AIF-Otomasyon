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
    error_log("[update_user] raw input=" . $raw);
    $input = json_decode($raw, true);
    
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz JSON veri']);
        exit;
    }
    
    error_log("[update_user] parsed input=" . json_encode($input));
    
    // Gerekli alanları kontrol et
    if (empty($input['username'])) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı gerekli']);
        exit;
    }
    
    // Kullanıcıyı bul
    $user = UserManager::getUserByUsername($input['username']);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }
    
    // E-posta formatını kontrol et
    if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz e-posta formatı']);
        exit;
    }
    
    // E-posta benzersizlik kontrolü (kendi e-postası hariç)
    if (!empty($input['email']) && $input['email'] !== $user['email']) {
        $existingUser = UserManager::getUserByEmail($input['email']);
        if ($existingUser) {
            echo json_encode(['success' => false, 'message' => 'Bu e-posta adresi zaten kullanılıyor']);
            exit;
        }
    }
    
    // BYK kategori ID'sini al
    $bykCategoryId = null;
    if (!empty($input['byk'])) {
        error_log('[update_user] BYK code received: ' . $input['byk']);
        $bykCategory = BYKManager::getBYKCategoryByCode($input['byk']);
        error_log('[update_user] BYK category lookup result: ' . json_encode($bykCategory));
        if ($bykCategory) {
            $bykCategoryId = $bykCategory['id'];
            error_log('[update_user] BYK category ID resolved: ' . $bykCategoryId);
        } else {
            error_log('[update_user] BYK category NOT FOUND for code: ' . $input['byk']);
        }
    } else {
        error_log('[update_user] No BYK code provided in input');
    }
    error_log('[update_user] Final byk_category_id: ' . ($bykCategoryId ?? 'NULL'));
    
    // BYK debug için response'a ekle
    $debugInfo = [
        'byk_input' => $input['byk'] ?? 'NOT_PROVIDED',
        'byk_category_id' => $bykCategoryId,
        'byk_category_found' => $bykCategory ? true : false
    ];
    
    // Alt birim ID'sini al
    $subUnitId = null;
    if (!empty($input['sub_unit'])) {
        $subUnit = BYKManager::getSubUnitByName($input['sub_unit']);
        if ($subUnit) {
            $subUnitId = $subUnit['id'];
        }
    }
    
    // Güncellenecek verileri hazırla
    $updateData = [];
    
    if (!empty($input['first_name']) && !empty($input['last_name'])) {
        $updateData['first_name'] = $input['first_name'];
        $updateData['last_name'] = $input['last_name'];
        $updateData['full_name'] = $input['first_name'] . ' ' . $input['last_name'];
    } elseif (!empty($input['full_name'])) {
        // Geriye dönük uyumluluk için
        $updateData['full_name'] = $input['full_name'];
    }
    
    if (!empty($input['email'])) {
        $updateData['email'] = $input['email'];
    }
    
    if (!empty($input['password'])) {
        $updateData['password'] = $input['password'];
    }
    
    if (!empty($input['role'])) {
        $updateData['role'] = $input['role'];
    }
    
    if (!empty($input['status'])) {
        $updateData['status'] = $input['status'];
    }
    
    if (isset($input['must_change_password'])) {
        $updateData['must_change_password'] = (int)$input['must_change_password'];
    }
    
    $updateData['byk_category_id'] = $bykCategoryId;
    $updateData['sub_unit_id'] = $subUnitId;
    $updateData['is_byk_member'] = isset($input['is_byk_member']) ? (int)$input['is_byk_member'] : 0;
    $updateData['updated_at'] = date('Y-m-d H:i:s');
    
    error_log("[update_user] updateData=" . json_encode($updateData));
    
    // Kullanıcı adını değiştirme isteği varsa kontrol et
    if (!empty($input['username']) && $input['username'] !== $user['username']) {
        // Aynı kullanıcı adı var mı?
        $exists = UserManager::getUserByUsername($input['username']);
        if ($exists) {
            echo json_encode(['success' => false, 'message' => 'Bu kullanıcı adı zaten kullanılıyor']);
            exit;
        }
        $updateData['username'] = $input['username'];
    }

    // Kullanıcıyı güncelle
    $result = UserManager::updateUser($user['id'], $updateData);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Kullanıcı başarıyla güncellendi',
            'debug' => $debugInfo
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı güncellenirken hata oluştu', 'debug' => $debugInfo]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>

