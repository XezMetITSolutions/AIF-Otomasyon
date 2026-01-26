<?php
/**
 * API - Toplantı Tutanak İşlemleri
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

try {
    Middleware::requireSuperAdmin();
    
    $auth = new Auth();
    $user = $auth->getUser();
    $db = Database::getInstance();
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Action gereklidir');
    }
    
    switch ($action) {
        case 'save_tutanak':
            $toplanti_id = $input['toplanti_id'] ?? null;
            $tutanak_no = trim($input['tutanak_no'] ?? '');
            $tutanak_tarihi = $input['tutanak_tarihi'] ?? null;
            $tutanak_metni = trim($input['tutanak_metni'] ?? '');
            $durum = $input['durum'] ?? 'taslak';
            
            if (!$toplanti_id || !$tutanak_metni) {
                throw new Exception('Toplantı ID ve tutanak metni gereklidir');
            }
            
            // Tutanak var mı kontrol et
            $existing = $db->fetch("SELECT tutanak_id FROM toplanti_tutanak WHERE toplanti_id = ?", [$toplanti_id]);
            
            if ($existing) {
                // Güncelle
                $db->query("
                    UPDATE toplanti_tutanak 
                    SET tutanak_no = ?, tutanak_tarihi = ?, tutanak_metni = ?, 
                        durum = ?, yazan_kullanici_id = ?
                    WHERE toplanti_id = ?
                ", [$tutanak_no, $tutanak_tarihi, $tutanak_metni, $durum, $user['kullanici_id'], $toplanti_id]);
                
                echo json_encode(['success' => true, 'message' => 'Tutanak güncellendi']);
            } else {
                // Yeni ekle
                $db->query("
                    INSERT INTO toplanti_tutanak (
                        toplanti_id, tutanak_no, tutanak_tarihi, tutanak_metni, 
                        durum, yazan_kullanici_id
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ", [$toplanti_id, $tutanak_no, $tutanak_tarihi, $tutanak_metni, $durum, $user['kullanici_id']]);
                
                echo json_encode(['success' => true, 'message' => 'Tutanak oluşturuldu']);
            }
            break;
            
        case 'approve':
            $tutanak_id = $input['tutanak_id'] ?? null;
            
            if (!$tutanak_id) {
                throw new Exception('Tutanak ID gereklidir');
            }
            
            $db->query("
                UPDATE toplanti_tutanak 
                SET durum = 'onaylandi', 
                    onaylayan_kullanici_id = ?, 
                    onay_tarihi = NOW()
                WHERE tutanak_id = ?
            ", [$user['kullanici_id'], $tutanak_id]);
            
            echo json_encode(['success' => true, 'message' => 'Tutanak onaylandı']);
            break;
            
        default:
            throw new Exception('Geçersiz action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
