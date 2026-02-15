<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Yetki kontrolü (Üye ve üzeri)
Middleware::requireUye();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$toplanti_id = $input['toplanti_id'] ?? null;
$degerlendirme = trim($input['degerlendirme'] ?? '');

if (!$toplanti_id) {
    echo json_encode(['success' => false, 'message' => 'Toplantı ID gereklidir']);
    exit;
}

$db = Database::getInstance();

try {
    // Check if column exists first (Self-healing API)
    try {
        $db->query("UPDATE toplantilar SET baskan_degerlendirmesi = ? WHERE toplanti_id = ?", [$degerlendirme, $toplanti_id]);
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            $db->query("ALTER TABLE toplantilar ADD COLUMN baskan_degerlendirmesi TEXT NULL AFTER gundem");
            $db->query("UPDATE toplantilar SET baskan_degerlendirmesi = ? WHERE toplanti_id = ?", [$degerlendirme, $toplanti_id]);
        } else {
            throw $e;
        }
    }

    echo json_encode(['success' => true, 'message' => 'Değerlendirme başarıyla kaydedildi']);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
