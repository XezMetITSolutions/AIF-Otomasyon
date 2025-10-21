<?php
require_once 'auth.php';
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $expense_id = $_POST['expense_id'] ?? null;
    
    if (!$expense_id) {
        throw new Exception('Gider ID gerekli');
    }
    
    $pdo = getDBConnection();
    
    // Giderin var olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT id, isim, soyisim, total FROM expenses WHERE id = ?");
    $stmt->execute([$expense_id]);
    $expense = $stmt->fetch();
    
    if (!$expense) {
        throw new Exception('Gider bulunamadı');
    }
    
    // Transaction başlat
    $pdo->beginTransaction();
    
    try {
        // Önce expense_items'ları sil (CASCADE ile otomatik silinir ama güvenlik için)
        $stmt = $pdo->prepare("DELETE FROM expense_items WHERE expense_id = ?");
        $stmt->execute([$expense_id]);
        
        // Ana gideri sil
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        $result = $stmt->execute([$expense_id]);
        
        if (!$result) {
            throw new Exception('Gider silinemedi');
        }
        
        // Transaction'ı commit et
        $pdo->commit();
        
        // Log kaydı
        error_log("Gider silindi: ID $expense_id, İsim: {$expense['isim']} {$expense['soyisim']}, Tutar: {$expense['total']} - " . date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => true,
            'message' => 'Gider başarıyla silindi',
            'deleted_id' => $expense_id,
            'deleted_name' => $expense['isim'] . ' ' . $expense['soyisim'],
            'deleted_amount' => $expense['total']
        ]);
        
    } catch (Exception $e) {
        // Transaction'ı rollback et
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Gider silme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>