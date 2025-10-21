<?php
require_once 'auth.php';

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
    
    // CSV başlıkları
    $csv = "Gider No,İsim Soyisim,IBAN,Toplam (€),BYK,Birim,Gönderim Tarihi,Durum\n";
    
    // Örnek veri - gerçek uygulamada veritabanı sorgusu yapılacak
    $csv .= "94,Semra Yıldız,AT862060200001095074,96.98,KT,İrşad,15.10.2025 10:56,Ödeme Bekliyor\n";
    
    echo json_encode([
        'success' => true,
        'csv' => $csv
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>


