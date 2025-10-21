<?php
require_once '../admin/config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // Gider başvurusu verilerini al
    $sql = "
        SELECT e.*, u.full_name, u.department, u.unit, u.byk
        FROM expenses e
        LEFT JOIN users u ON e.user_id = u.id
        ORDER BY e.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $expenses = $stmt->fetchAll();
    
    // Gider kalemlerini de al
    foreach ($expenses as &$expense) {
        $items_sql = "SELECT * FROM expense_items WHERE expense_id = ? ORDER BY tarih ASC";
        $items_stmt = $pdo->prepare($items_sql);
        $items_stmt->execute([$expense['id']]);
        $expense['items'] = $items_stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $expenses
    ]);
    
} catch (Exception $e) {
    error_log("Gider verileri getirme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>


