<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../includes/init.php';
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

$auth = new Auth();
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz JSON verisi.']);
    exit;
}

$userId = $data['id'] ?? null;
$oldPassword = $data['old_password'] ?? null;
$newPassword = $data['new_password'] ?? null;

if (!$userId || !$oldPassword || !$newPassword) {
    echo json_encode(['success' => false, 'message' => 'Eksik veri: ' . json_encode($data)]);
    exit;
}

try {
    if ($auth->changePassword($userId, $oldPassword, $newPassword)) {
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Şifre başarıyla değiştirildi.']);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Mevcut şifre hatalı veya kullanıcı bulunamadı.']);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
