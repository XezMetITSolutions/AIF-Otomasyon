<?php
require_once 'auth.php';

header('Content-Type: application/json');

try {
    $expense_id = $_POST['expense_id'] ?? null;
    $isim = $_POST['isim'] ?? null;
    $soyisim = $_POST['soyisim'] ?? null;
    $iban = $_POST['iban'] ?? null;
    $total = $_POST['total'] ?? null;
    $status = $_POST['status'] ?? null;
    
    // Validasyon
    if (!$expense_id) {
        throw new Exception('Gider ID gerekli');
    }
    
    if (!$isim || !$soyisim || !$iban || !$total || !$status) {
        throw new Exception('Tüm alanlar doldurulmalıdır');
    }
    
    // Geçerli durumları kontrol et
    $valid_statuses = ['paid', 'pending', 'rejected'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Geçersiz durum');
    }
    
    // IBAN formatını kontrol et
    if (!preg_match('/^AT\d{18}$/', str_replace(' ', '', $iban))) {
        throw new Exception('Geçersiz IBAN formatı');
    }
    
    // Tutar kontrolü
    if (!is_numeric($total) || $total <= 0) {
        throw new Exception('Geçersiz tutar');
    }
    
    // Veritabanı bağlantısı (örnek)
    // $pdo = new PDO('mysql:host=localhost;dbname=aif_otomasyon', $username, $password);
    
    // Gerçek güncelleme sorgusu (örnek)
    // $stmt = $pdo->prepare("
    //     UPDATE expenses 
    //     SET isim = ?, soyisim = ?, iban = ?, total = ?, status = ?, updated_at = NOW() 
    //     WHERE id = ?
    // ");
    // $result = $stmt->execute([$isim, $soyisim, $iban, $total, $status, $expense_id]);
    
    // Şimdilik başarılı olarak döndürüyoruz
    // Gerçek uygulamada veritabanında güncelleme işlemi yapılacak
    
    // Log kaydı
    error_log("Gider güncellendi: ID $expense_id, İsim: $isim $soyisim, Tutar: $total, Durum: $status - " . date('Y-m-d H:i:s'));
    
    echo json_encode([
        'success' => true,
        'message' => 'Gider başarıyla güncellendi',
        'data' => [
            'id' => $expense_id,
            'isim' => $isim,
            'soyisim' => $soyisim,
            'iban' => $iban,
            'total' => $total,
            'status' => $status
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Gider güncelleme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>


