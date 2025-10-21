<?php
require_once 'auth.php';
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $expense_id = $_GET['expense_id'] ?? null;
    
    if (!$expense_id) {
        throw new Exception('Gider ID gerekli');
    }
    
    $pdo = getDBConnection();
    
    // Ana gider bilgilerini al
    $stmt = $pdo->prepare("
        SELECT e.*, u.full_name, u.department, u.unit, u.byk
        FROM expenses e 
        LEFT JOIN users u ON e.user_id = u.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$expense_id]);
    $expense = $stmt->fetch();
    
    if (!$expense) {
        throw new Exception('Gider bulunamadı');
    }
    
    // Gider kalemlerini al
    $stmt = $pdo->prepare("
        SELECT * FROM expense_items 
        WHERE expense_id = ? 
        ORDER BY tarih ASC
    ");
    $stmt->execute([$expense_id]);
    $items = $stmt->fetchAll();
    
    // Veriyi PDF formatına uygun hale getir
    $expense_data = [
        'id' => $expense['id'],
        'isim' => $expense['isim'],
        'soyisim' => $expense['soyisim'],
        'iban' => $expense['iban'],
        'total' => number_format($expense['total'], 2),
        'items' => array_map(function($item) {
            return [
                'tarih' => $item['tarih'],
                'region' => $item['region'],
                'birim' => $item['birim'],
                'birim_label' => $item['birim_label'] ?: $item['birim'],
                'gider_turu' => $item['gider_turu'],
                'gider_turu_label' => $item['gider_turu_label'] ?: $item['gider_turu'],
                'tutar' => number_format($item['tutar'], 2),
                'aciklama' => $item['aciklama']
            ];
        }, $items),
        'attachments' => []
    ];
    
    // Ekleri işle (JSON formatında saklanıyor)
    foreach ($items as $item) {
        if ($item['attachments']) {
            $attachments = json_decode($item['attachments'], true);
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    $expense_data['attachments'][] = $attachment;
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $expense_data
    ]);
    
} catch (Exception $e) {
    error_log("Gider veri getirme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>