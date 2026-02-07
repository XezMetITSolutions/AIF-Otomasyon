<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

try {
    $sql = "CREATE TABLE IF NOT EXISTS bildirimler (
        bildirim_id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_id INT NOT NULL,
        baslik VARCHAR(255) NOT NULL,
        mesaj TEXT,
        tip VARCHAR(50) DEFAULT 'bilgi',
        link VARCHAR(255) DEFAULT NULL,
        okundu TINYINT(1) DEFAULT 0,
        olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_kullanici (kullanici_id),
        INDEX idx_okundu (okundu)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->query($sql);
    echo "Tablo 'bildirimler' kontrol edildi/oluşturuldu.<br>";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
