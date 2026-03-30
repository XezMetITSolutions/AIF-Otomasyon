<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/init.php';

try {
    $db = Database::getInstance()->getConnection();
    $result = $db->query("SELECT 1")->fetch();
    echo json_encode([
        'success' => true,
        'message' => 'Veritabanı bağlantısı başarılı!',
        'database' => $db->query("SELECT DATABASE()")->fetchColumn()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bağlantı hatası: ' . $e->getMessage()
    ]);
}
