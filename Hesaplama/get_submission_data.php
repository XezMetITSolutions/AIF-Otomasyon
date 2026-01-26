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

echo json_encode([
    'success' => true, 
    'submission' => $submission
]);
?>


