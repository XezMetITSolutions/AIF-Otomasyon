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

$db = Database::getInstance();
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz JSON verisi: ' . $rawInput]);
    exit;
}

$userId = $data['id'] ?? null;
$name = $data['name'] ?? null;
$email = $data['email'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID bulunamadı. Data: ' . json_encode($data)]);
    exit;
}

try {
    $nameParts = explode(' ', trim($name), 2);
    $ad = $nameParts[0];
    $soyad = $nameParts[1] ?? '';

    $result = $db->query(
        "UPDATE kullanicilar SET ad = ?, soyad = ?, email = ? WHERE kullanici_id = ?",
        [$ad, $soyad, $email, $userId]
    );

    if ($result) {
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Profil başarıyla güncellendi.']);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Güncelleme yapılamadı. Kullanıcı bulunamıyor olabilir.']);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
