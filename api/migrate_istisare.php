<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// 1. Yeni İstişare Oturumları tablosunu oluştur
$db->query("
CREATE TABLE IF NOT EXISTS istisare_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    baslik VARCHAR(255) NOT NULL,
    sube_ismi VARCHAR(255) NOT NULL,
    kurul_uyeleri TEXT,
    durum ENUM('aktif', 'kapali') DEFAULT 'aktif',
    eklenme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 2. Oylama tablosuna session_id ekle
try {
    $db->query("ALTER TABLE istisare_oylama ADD COLUMN session_id INT AFTER id");
} catch (Exception $e) {
    // Zaten eklenmiş olabilir
}

// 3. Mevcut oyları 'AIF Innsbruck' oturumuna bağla (Eğer daha önce yapılmadıysa)
$existingSession = $db->fetch("SELECT id FROM istisare_sessions LIMIT 1");
if (!$existingSession) {
    $db->query("INSERT INTO istisare_sessions (baslik, sube_ismi, kurul_uyeleri) VALUES (?, ?, ?)", 
        ['AIF Innsbruck 2026', 'AIF Innsbruck', 'Mete Burçak, İbrahim Çetin']);
    $sessionId = $db->lastInsertId();
    $db->query("UPDATE istisare_oylama SET session_id = ? WHERE session_id IS NULL OR session_id = 0", [$sessionId]);
}

echo "Migrasyon tamamlandı!";
