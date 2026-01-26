<?php
// includes/ensure_preferences_table.php

$db = Database::getInstance();

try {
    $db->query("
        CREATE TABLE IF NOT EXISTS kullanici_ayarlari (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kullanici_id INT NOT NULL,
            ayar_adi VARCHAR(50) NOT NULL,
            ayar_degeri TEXT,
            guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_preference (kullanici_id, ayar_adi)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {
    // Tablo zaten varsa veya yetki yoksa sessizce devam et
    // error_log($e->getMessage());
}
