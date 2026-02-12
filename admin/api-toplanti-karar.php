<?php
/**
 * API - Toplantı Karar İşlemleri
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

try {
    Middleware::requireUye();
    
    $db = Database::getInstance();
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Action gereklidir');
    }
    
    switch ($action) {
        case 'add':
            $toplanti_id = $input['toplanti_id'] ?? null;
            $gundem_id = $input['gundem_id'] ?? null;
            $karar_no = trim($input['karar_no'] ?? '');
            $baslik = trim($input['baslik'] ?? '');
            $karar_metni = trim($input['karar_metni'] ?? '');
            $oylama_yapildi = $input['oylama_yapildi'] ?? 0;
            $kabul_oyu = $input['kabul_oyu'] ?? 0;
            $red_oyu = $input['red_oyu'] ?? 0;
            $cekinser_oyu = $input['cekinser_oyu'] ?? 0;
            $karar_sonucu = $input['karar_sonucu'] ?? null;
            
            if (!$toplanti_id || !$baslik || !$karar_metni) {
                throw new Exception('Toplantı ID, başlık ve karar metni gereklidir');
            }
            
            $db->query("
                INSERT INTO toplanti_kararlar (
                    toplanti_id, gundem_id, karar_no, baslik, karar_metni,
                    oylama_yapildi, kabul_oyu, red_oyu, cekinser_oyu, karar_sonucu
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $toplanti_id, $gundem_id, $karar_no, $baslik, $karar_metni,
                $oylama_yapildi, $kabul_oyu, $red_oyu, $cekinser_oyu, $karar_sonucu
            ]);
            
            $karar_id = $db->lastInsertId();
            
            // Karar sayısını güncelle
            $count = $db->fetch("SELECT COUNT(*) as total FROM toplanti_kararlar WHERE toplanti_id = ?", [$toplanti_id]);
            $db->query("UPDATE toplantilar SET karar_sayisi = ? WHERE toplanti_id = ?", [$count['total'], $toplanti_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Karar eklendi',
                'karar_id' => $karar_id
            ]);
            break;
            
        case 'update':
            $karar_id = $input['karar_id'] ?? null;
            $karar_no = trim($input['karar_no'] ?? '');
            $baslik = trim($input['baslik'] ?? '');
            $karar_metni = trim($input['karar_metni'] ?? '');
            $oylama_yapildi = $input['oylama_yapildi'] ?? 0;
            $kabul_oyu = $input['kabul_oyu'] ?? 0;
            $red_oyu = $input['red_oyu'] ?? 0;
            $cekinser_oyu = $input['cekinser_oyu'] ?? 0;
            $karar_sonucu = $input['karar_sonucu'] ?? null;
            
            if (!$karar_id) {
                throw new Exception('Karar ID gereklidir');
            }
            
            $db->query("
                UPDATE toplanti_kararlar 
                SET karar_no = ?, baslik = ?, karar_metni = ?,
                    oylama_yapildi = ?, kabul_oyu = ?, red_oyu = ?, 
                    cekinser_oyu = ?, karar_sonucu = ?
                WHERE karar_id = ?
            ", [
                $karar_no, $baslik, $karar_metni,
                $oylama_yapildi, $kabul_oyu, $red_oyu, 
                $cekinser_oyu, $karar_sonucu, $karar_id
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Karar güncellendi']);
            break;
            
        case 'delete':
            $karar_id = $input['karar_id'] ?? null;
            
            if (!$karar_id) {
                throw new Exception('Karar ID gereklidir');
            }
            
            // Toplantı ID'yi al
            $karar = $db->fetch("SELECT toplanti_id FROM toplanti_kararlar WHERE karar_id = ?", [$karar_id]);
            
            $db->query("DELETE FROM toplanti_kararlar WHERE karar_id = ?", [$karar_id]);
            
            // Karar sayısını güncelle
            if ($karar) {
                $count = $db->fetch("SELECT COUNT(*) as total FROM toplanti_kararlar WHERE toplanti_id = ?", [$karar['toplanti_id']]);
                $db->query("UPDATE toplantilar SET karar_sayisi = ? WHERE toplanti_id = ?", [$count['total'], $karar['toplanti_id']]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Karar silindi']);
            break;
            
        case 'get':
            $karar_id = $_GET['karar_id'] ?? null;
            
            if (!$karar_id) {
                throw new Exception('Karar ID gereklidir');
            }
            
            $karar = $db->fetch("SELECT * FROM toplanti_kararlar WHERE karar_id = ?", [$karar_id]);
            
            if (!$karar) {
                throw new Exception('Karar bulunamadı');
            }
            
            echo json_encode(['success' => true, 'karar' => $karar]);
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
