<?php
require_once 'auth.php';
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $filters = [
        'year' => $_POST['year'] ?? 'Hepsi',
        'month' => $_POST['month'] ?? 'Hepsi',
        'person' => $_POST['person'] ?? 'Hepsi',
        'unit' => $_POST['unit'] ?? 'Hepsi',
        'byk' => $_POST['byk'] ?? 'Hepsi',
        'status' => $_POST['status'] ?? 'Hepsi'
    ];
    
    $pdo = getDBConnection();
    
    // SQL sorgusu oluştur
    $sql = "
        SELECT e.id, e.isim, e.soyisim, e.iban, e.total, e.status, e.created_at,
               u.department, u.unit, u.byk
        FROM expenses e
        LEFT JOIN users u ON e.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Yıl filtresi
    if ($filters['year'] !== 'Hepsi') {
        $sql .= " AND YEAR(e.created_at) = ?";
        $params[] = $filters['year'];
    }
    
    // Ay filtresi
    if ($filters['month'] !== 'Hepsi') {
        $monthNumber = getMonthNumber($filters['month']);
        if ($monthNumber) {
            $sql .= " AND MONTH(e.created_at) = ?";
            $params[] = $monthNumber;
        }
    }
    
    // Kişi filtresi
    if ($filters['person'] !== 'Hepsi') {
        $sql .= " AND CONCAT(e.isim, ' ', e.soyisim) = ?";
        $params[] = $filters['person'];
    }
    
    // Birim filtresi
    if ($filters['unit'] !== 'Hepsi') {
        $sql .= " AND u.unit = ?";
        $params[] = $filters['unit'];
    }
    
    // BYK filtresi
    if ($filters['byk'] !== 'Hepsi') {
        $sql .= " AND u.byk = ?";
        $params[] = $filters['byk'];
    }
    
    // Durum filtresi
    if ($filters['status'] !== 'Hepsi') {
        $statusMap = [
            'Ödeme Bekliyor' => 'pending',
            'Ödendi' => 'paid',
            'Reddedildi' => 'rejected'
        ];
        if (isset($statusMap[$filters['status']])) {
            $sql .= " AND e.status = ?";
            $params[] = $statusMap[$filters['status']];
        }
    }
    
    $sql .= " ORDER BY e.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll();
    
    // Veriyi frontend formatına çevir
    $filtered_data = array_map(function($expense) {
        $statusMap = [
            'pending' => ['text' => 'Ödeme Bekliyor', 'class' => 'pending'],
            'paid' => ['text' => 'Ödendi', 'class' => 'paid'],
            'rejected' => ['text' => 'Reddedildi', 'class' => 'rejected']
        ];
        
        $status = $statusMap[$expense['status']] ?? ['text' => 'Bilinmiyor', 'class' => 'pending'];
        
        return [
            'id' => $expense['id'],
            'name' => $expense['isim'] . ' ' . $expense['soyisim'],
            'iban' => $expense['iban'],
            'total' => number_format($expense['total'], 2),
            'byk' => $expense['byk'] ?: '-',
            'unit' => $expense['unit'] ?: '-',
            'date' => date('d.m.Y H:i', strtotime($expense['created_at'])),
            'status' => $status['class'],
            'status_text' => $status['text']
        ];
    }, $expenses);
    
    echo json_encode([
        'success' => true,
        'data' => $filtered_data
    ]);
    
} catch (Exception $e) {
    error_log("Filtreleme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Ay adını sayıya çevir
function getMonthNumber($monthName) {
    $months = [
        'Ocak' => 1, 'Şubat' => 2, 'Mart' => 3, 'Nisan' => 4,
        'Mayıs' => 5, 'Haziran' => 6, 'Temmuz' => 7, 'Ağustos' => 8,
        'Eylül' => 9, 'Ekim' => 10, 'Kasım' => 11, 'Aralık' => 12
    ];
    return $months[$monthName] ?? null;
}
?>