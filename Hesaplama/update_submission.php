<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user']) || !in_array($_SESSION['user'], ['MuhasebeAT', 'MuhasebeGT', 'MuhasebeKGT', 'MuhasebeKT'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// JSON verisi al
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
    exit;
}

// Gerekli alanları kontrol et
$required = ['submission_id', 'name', 'surname', 'iban', 'total', 'items'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Eksik alan: $field"]);
        exit;
    }
}

$submissionId = $data['submission_id'];
$dataFile = 'submissions.json';

// Mevcut submissions'ı oku
$submissions = [];
if (file_exists($dataFile)) {
    $submissions = json_decode(file_get_contents($dataFile), true);
    if (!is_array($submissions)) {
        $submissions = [];
    }
}

// Submission'ı bul ve güncelle
$found = false;
foreach ($submissions as &$submission) {
    if (($submission['id'] ?? null) === $submissionId) {
        // Temel bilgileri güncelle
        $submission['isim'] = $data['name'];
        $submission['soyisim'] = $data['surname'];
        $submission['iban'] = $data['iban'];
        $submission['total'] = $data['total'];
        $submission['items'] = $data['items'];
        
        // Güncelleme tarihini ekle
        $submission['updated_at'] = date('c');
        $submission['updated_by'] = $_SESSION['user'];
        
        $found = true;
        break;
    }
}

if (!$found) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Gider bulunamadı']);
    exit;
}

// JSON dosyasını güncelle
$result = file_put_contents($dataFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($result === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Dosya yazma hatası']);
    exit;
}

// Başarılı yanıt
echo json_encode([
    'success' => true, 
    'message' => 'Gider başarıyla güncellendi',
    'submission_id' => $submissionId
]);
?>

