<?php
/**
 * API - Toplantı Katılımcı İşlemleri
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

try {
    Middleware::requireSuperAdmin();
    
    $db = Database::getInstance();
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Action gereklidir');
    }
    
    switch ($action) {
        case 'add':
            $toplanti_id = $input['toplanti_id'] ?? null;
            $kullanici_id = $input['kullanici_id'] ?? null;
            $katilim_durumu = $input['katilim_durumu'] ?? 'katildi';
            
            if (!$toplanti_id || !$kullanici_id) {
                throw new Exception('Toplantı ID ve Kullanıcı ID gereklidir');
            }
            
            $db->query("
                INSERT INTO toplanti_katilimcilar (toplanti_id, kullanici_id, katilim_durumu)
                VALUES (?, ?, ?)
            ", [$toplanti_id, $kullanici_id, $katilim_durumu]);
            
            // Katılımcı sayısını güncelle
            $count = $db->fetch("SELECT COUNT(*) as total FROM toplanti_katilimcilar WHERE toplanti_id = ?", [$toplanti_id]);
            $db->query("UPDATE toplantilar SET katilimci_sayisi = ? WHERE toplanti_id = ?", [$count['total'], $toplanti_id]);
            
            echo json_encode(['success' => true, 'message' => 'Katılımcı eklendi']);
            break;
            
        case 'update':
            $katilimci_id = $input['katilimci_id'] ?? null;
            $katilim_durumu = $input['katilim_durumu'] ?? null;
            
            if (!$katilimci_id || !$katilim_durumu) {
                throw new Exception('Katılımcı ID ve durum gereklidir');
            }
            
            $db->query("
                UPDATE toplanti_katilimcilar 
                SET katilim_durumu = ? 
                WHERE katilimci_id = ?
            ", [$katilim_durumu, $katilimci_id]);
            
            echo json_encode(['success' => true, 'message' => 'Katılım durumu güncellendi']);
            break;
            
        case 'delete':
            $katilimci_id = $input['katilimci_id'] ?? null;
            
            if (!$katilimci_id) {
                throw new Exception('Katılımcı ID gereklidir');
            }
            
            // Toplantı ID'yi al
            $katilimci = $db->fetch("SELECT toplanti_id FROM toplanti_katilimcilar WHERE katilimci_id = ?", [$katilimci_id]);
            
            $db->query("DELETE FROM toplanti_katilimcilar WHERE katilimci_id = ?", [$katilimci_id]);
            
            // Katılımcı sayısını güncelle
            if ($katilimci) {
                $count = $db->fetch("SELECT COUNT(*) as total FROM toplanti_katilimcilar WHERE toplanti_id = ?", [$katilimci['toplanti_id']]);
                $db->query("UPDATE toplantilar SET katilimci_sayisi = ? WHERE toplanti_id = ?", [$count['total'], $katilimci['toplanti_id']]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Katılımcı silindi']);
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
