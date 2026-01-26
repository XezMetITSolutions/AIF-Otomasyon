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
if (!isset($_POST['submission_id']) || !isset($_FILES['pdf'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik veri']);
    exit;
}

$submissionId = $_POST['submission_id'];
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
$found = false;
foreach ($submissions as &$submission) {
    if (($submission['id'] ?? null) === $submissionId) {
        // Eski PDF'i sil
        $oldPdfPath = $submission['pdf_link'] ?? '';
        if ($oldPdfPath) {
            $fullOldPath = __DIR__ . '/' . $oldPdfPath;
            if (file_exists($fullOldPath)) {
                unlink($fullOldPath);
            }
        }
        
        // Yeni PDF'i kaydet
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        
        $uniqueFileName = 'spesenformular_' . time() . '_' . uniqid() . '.pdf';
        $uploadPath = $uploadDir . $uniqueFileName;
        
        if (move_uploaded_file($_FILES['pdf']['tmp_name'], $uploadPath)) {
            // Submission'ı güncelle
            $newPdfLink = 'uploads/' . $uniqueFileName;
            $submission['pdf_link'] = $newPdfLink;
            $submission['regenerated_at'] = date('c');
            $submission['regenerated_by'] = $_SESSION['user'];
            
            // Debug log
            error_log("PDF güncellendi - ID: $submissionId, Eski: $oldPdfPath, Yeni: $newPdfLink");
            
            $found = true;
            break;
        } else {
            echo json_encode(['success' => false, 'message' => 'PDF yükleme hatası']);
            exit;
        }
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Submission bulunamadı']);
    exit;
}

// JSON dosyasını güncelle
$result = file_put_contents($dataFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($result === false) {
    error_log("JSON dosyası yazma hatası - ID: $submissionId");
    echo json_encode(['success' => false, 'message' => 'Dosya yazma hatası']);
    exit;
}

// Debug: Güncellenen submission'ı kontrol et
$updatedSubmission = null;
foreach ($submissions as $s) {
    if (($s['id'] ?? null) === $submissionId) {
        $updatedSubmission = $s;
        break;
    }
}

error_log("JSON dosyası güncellendi - ID: $submissionId, PDF Link: " . ($updatedSubmission['pdf_link'] ?? 'YOK'));

echo json_encode([
    'success' => true, 
    'message' => 'PDF başarıyla güncellendi',
    'submission_id' => $submissionId,
    'new_pdf_link' => $updatedSubmission['pdf_link'] ?? '',
    'debug_info' => [
        'old_link' => $oldPdfPath ?? '',
        'new_link' => $updatedSubmission['pdf_link'] ?? '',
        'file_exists' => file_exists($uploadPath ?? ''),
        'json_written' => $result !== false
    ]
]);
?>
