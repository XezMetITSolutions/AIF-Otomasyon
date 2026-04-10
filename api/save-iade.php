<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

$db = Database::getInstance();
$data = json_decode(file_get_contents('php://input'), true);

$userId = $data['userId'] ?? null;
$items = $data['items'] ?? [];
$iban = $data['iban'] ?? '';
$total = $data['total'] ?? 0;

if (!$userId || empty($items) || !$iban) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi: Kullanıcı, gider kalemleri ve IBAN gereklidir.']);
    exit;
}

try {
    // Kullanıcının ana BYK'sını al
    $user = $db->fetch("SELECT byk_id FROM kullanicilar WHERE kullanici_id = ?", [$userId]);
    $primaryBykId = $user ? $user['byk_id'] : 0;

    // Veritabanına kaydet
    // Not: Web panelinde harcama_talepleri tablosu kullanılıyor. 
    // Detayları JSON olarak veya açıklama alanına birleştirerek kaydedebiliriz.
    
    $baslik = "Mobil İade Talebi (" . count($items) . " Kalem)";
    $aciklama = "";
    foreach($items as $idx => $item) {
        $num = $idx + 1;
        $aciklama .= "[$num] {$item['date']} - {$item['type']}: {$item['amount']}€ ({$item['description']})\n";
    }

    $result = $db->query("
        INSERT INTO harcama_talepleri 
        (kullanici_id, byk_id, baslik, aciklama, tutar, iban, durum, olusturma_tarihi) 
        VALUES (?, ?, ?, ?, ?, ?, 'beklemede', NOW())
    ", [
        $userId,
        $primaryBykId,
        $baslik,
        $aciklama,
        $total,
        $iban
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'İade talebiniz başarıyla oluşturuldu ve yönetici onayına sunuldu.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Veritabanı kaydı sırasında hata oluştu.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
