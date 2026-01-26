<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'UserB') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

header('Content-Type: application/json');

// JSON verisi al
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
    exit;
}

$id = $data['id'];
$dataFile = 'submissions.json';

// Mevcut submissions'ı oku
$submissions = [];
if (file_exists($dataFile)) {
    $submissions = json_decode(file_get_contents($dataFile), true);
    if (!is_array($submissions)) {
        $submissions = [];
    }
}

// Debug log
error_log("AJAX Silme işlemi - ID: " . $id);
error_log("Toplam submission sayısı: " . count($submissions));

// Submission'ı bul ve sil
$found = false;
$newSubmissions = [];

foreach ($submissions as $submission) {
    if (($submission['id'] ?? null) === $id) {
        // PDF dosyasını da sil
        if (!empty($submission['pdf_link'])) {
            $pdfPath = $submission['pdf_link'];
            $fullPdfPath = __DIR__ . '/' . $pdfPath;
            if (file_exists($fullPdfPath)) {
                unlink($fullPdfPath);
                error_log("PDF dosyası silindi: " . $fullPdfPath);
            }
        }
        
        $found = true;
        error_log("Submission bulundu ve silindi - ID: " . $id);
    } else {
        // Silinmeyecek submission'ları yeni array'e ekle
        $newSubmissions[] = $submission;
    }
}

if (!$found) {
    error_log("Submission bulunamadı - ID: " . $id);
    echo json_encode(['success' => false, 'message' => 'Silinecek gider bulunamadı']);
    exit;
}

// JSON dosyasını güncelle
$result = file_put_contents($dataFile, json_encode($newSubmissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

error_log("JSON dosyası yazma sonucu: " . ($result !== false ? 'Başarılı' : 'Başarısız'));
error_log("Yeni submission sayısı: " . count($newSubmissions));

if ($result === false) {
    echo json_encode(['success' => false, 'message' => 'Dosya yazma hatası']);
    exit;
}

// Başarılı yanıt
echo json_encode([
    'success' => true, 
    'message' => 'Gider başarıyla silindi',
    'deleted_id' => $id,
    'remaining_count' => count($newSubmissions)
]);
?>


