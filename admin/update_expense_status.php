<?php
require_once 'auth.php';
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $expense_id = $_POST['expense_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$expense_id || !$status) {
        throw new Exception('Gider ID ve durum gerekli');
    }
    
    // Geçerli durumları kontrol et
    $valid_statuses = ['paid', 'pending', 'rejected'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Geçersiz durum');
    }
    
    // Durum metinleri
    $status_texts = [
        'paid' => 'Ödendi',
        'pending' => 'Ödeme Bekliyor',
        'rejected' => 'Reddedildi'
    ];
    
    $pdo = getDBConnection();
    
    // Giderin var olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT id FROM expenses WHERE id = ?");
    $stmt->execute([$expense_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Gider bulunamadı');
    }
    
    // Durumu güncelle
    $stmt = $pdo->prepare("UPDATE expenses SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$status, $expense_id]);
    
    if (!$result) {
        throw new Exception('Durum güncellenemedi');
    }
    
    // Log kaydı
    error_log("Gider durumu güncellendi: ID $expense_id, Durum: $status - " . date('Y-m-d H:i:s'));
    
    echo json_encode([
        'success' => true,
        'message' => 'Durum başarıyla güncellendi',
        'status' => $status,
        'status_text' => $status_texts[$status] ?? $status
    ]);
    
} catch (Exception $e) {
    error_log("Gider durum güncelleme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>