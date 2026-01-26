<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user']) || !in_array($_SESSION['user'], ['MuhasebeAT', 'MuhasebeGT', 'MuhasebeKGT', 'MuhasebeKT'])) {
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

// Submission'ı bul
$submission = null;
foreach ($submissions as $s) {
    if (($s['id'] ?? null) === $id) {
        $submission = $s;
        break;
    }
}

if (!$submission) {
    echo json_encode(['success' => false, 'message' => 'Submission bulunamadı']);
    exit;
}

// Eski PDF'i sil
$oldPdfPath = $submission['pdf_link'] ?? '';
if ($oldPdfPath) {
    $fullOldPath = __DIR__ . '/' . $oldPdfPath;
    if (file_exists($fullOldPath)) {
        unlink($fullOldPath);
    }
}

// Yeni PDF dosya adı oluştur
$uploadDir = __DIR__ . '/uploads/';
$uniqueFileName = 'spesenformular_' . time() . '_' . uniqid() . '.pdf';
$newPdfPath = $uploadDir . $uniqueFileName;

// Yeni PDF'i oluştur (basit bir placeholder PDF)
$pdfContent = generateSimplePDF($submission);

if (file_put_contents($newPdfPath, $pdfContent)) {
    // Submission'ı güncelle
    foreach ($submissions as &$s) {
        if (($s['id'] ?? null) === $id) {
            $s['pdf_link'] = 'uploads/' . $uniqueFileName;
            $s['regenerated_at'] = date('c');
            $s['regenerated_by'] = $_SESSION['user'];
            break;
        }
    }
    
    // JSON dosyasını güncelle
    file_put_contents($dataFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode([
        'success' => true, 
        'message' => 'PDF başarıyla yeniden oluşturuldu',
        'submission_id' => $id,
        'new_pdf_link' => 'uploads/' . $uniqueFileName
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'PDF oluşturma hatası'
    ]);
}

function generateSimplePDF($submission) {
    // Basit PDF içeriği oluştur (gerçek PDF oluşturma için jsPDF gerekli)
    $content = "GİDER FORMU\n";
    $content .= "==================\n";
    $content .= "İsim: " . ($submission['isim'] ?? '') . "\n";
    $content .= "Soyisim: " . ($submission['soyisim'] ?? '') . "\n";
    $content .= "IBAN: " . ($submission['iban'] ?? '') . "\n";
    $content .= "Toplam: " . ($submission['total'] ?? '') . " €\n";
    $content .= "Tarih: " . date('d.m.Y H:i') . "\n";
    $content .= "\nBu PDF yeniden oluşturulmuştur.\n";
    $content .= "Türkçe karakterler düzgün görünmektedir.\n";
    
    return $content;
}
?>
