<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user']) || !in_array($_SESSION['user'], ['MuhasebeAT', 'MuhasebeGT', 'MuhasebeKGT', 'MuhasebeKT'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

header('Content-Type: application/json');

// Gerekli alanları kontrol et
$required = ['submission_id', 'name', 'surname', 'iban', 'total', 'items_json'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Eksik alan: $field"]);
        exit;
    }
}

$submissionId = $_POST['submission_id'];
$itemsJson = $_POST['items_json'];
$dataFile = 'submissions.json';

// Items JSON'ını decode et
$items = json_decode($itemsJson, true);
if (!is_array($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz items verisi']);
    exit;
}

// Mevcut submissions'ı oku
$submissions = [];
if (file_exists($dataFile)) {
    $submissions = json_decode(file_get_contents($dataFile), true);
    if (!is_array($submissions)) {
        $submissions = [];
    }
}

// Submission'ı bul
$found = false;
foreach ($submissions as &$submission) {
    if (($submission['id'] ?? null) === $submissionId) {
        // Temel bilgileri güncelle
        $submission['isim'] = $_POST['name'];
        $submission['soyisim'] = $_POST['surname'];
        $submission['iban'] = $_POST['iban'];
        $submission['total'] = $_POST['total'];
        $submission['items'] = $items;
        
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

// Yeni ekleri işle
if (isset($_FILES['new_attachments']) && !empty($_FILES['new_attachments']['name'][0])) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0775, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Upload dizini oluşturulamadı']);
            exit;
        }
    }
    
    $newAttachments = [];
    $files = $_FILES['new_attachments'];
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = $files['name'][$i];
            $fileTmp = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileType = $files['type'][$i];
            
            // Güvenli dosya adı oluştur
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $safeName = 'attachment_' . time() . '_' . $i . '_' . uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $safeName;
            
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $newAttachments[] = [
                    'name' => $fileName,
                    'url' => 'uploads/' . $safeName,
                    'size' => $fileSize,
                    'type' => $fileType,
                    'uploaded_at' => date('c')
                ];
            }
        }
    }
    
    // Yeni ekleri mevcut submission'a ekle
    if (!empty($newAttachments)) {
        if (!isset($submission['attachments'])) {
            $submission['attachments'] = [];
        }
        $submission['attachments'] = array_merge($submission['attachments'], $newAttachments);
    }
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
    'message' => 'Gider ve ekler başarıyla güncellendi',
    'submission_id' => $submissionId,
    'new_attachments_count' => count($newAttachments ?? [])
]);
?>

