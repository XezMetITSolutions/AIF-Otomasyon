<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Demirbaş Talepleri Tablosu
    $sqlDemirbas = "CREATE TABLE IF NOT EXISTS demirbas_talepleri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_id INT NOT NULL,
        demirbas_id INT NULL,
        baslik VARCHAR(255) NOT NULL,
        aciklama TEXT,
        durum ENUM('bekliyor', 'onaylandi', 'reddedildi') DEFAULT 'bekliyor',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(kullanici_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $conn->exec($sqlDemirbas);
    echo "demirbas_talepleri table created or already exists.\n";

    // Raggal Talepleri Tablosu
    $sqlRaggal = "CREATE TABLE IF NOT EXISTS raggal_talepleri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_id INT NOT NULL,
        baslangic_tarihi DATETIME NOT NULL,
        bitis_tarihi DATETIME NOT NULL,
        aciklama TEXT,
        durum ENUM('bekliyor', 'onaylandi', 'reddedildi') DEFAULT 'bekliyor',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(kullanici_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $conn->exec($sqlRaggal);
    echo "raggal_talepleri table created or already exists.\n";

} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
}
