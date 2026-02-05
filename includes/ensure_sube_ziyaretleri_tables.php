<?php
/**
 * Şube Ziyaretleri Tablolarını Kontrol Et ve Oluştur
 */
$db = Database::getInstance();

try {
    // 1. Ziyaret Grupları
    $db->query("CREATE TABLE IF NOT EXISTS `ziyaret_gruplari` (
        `grup_id` INT AUTO_INCREMENT PRIMARY KEY,
        `grup_adi` VARCHAR(255) NOT NULL,
        `renk_kodu` VARCHAR(20) DEFAULT '#009872',
        `aktif` TINYINT(1) DEFAULT 1,
        `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `guncelleme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. Grup Üyeleri
    $db->query("CREATE TABLE IF NOT EXISTS `ziyaret_grup_uyeleri` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `grup_id` INT NOT NULL,
        `kullanici_id` INT NOT NULL,
        `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_grup_kullanici` (`grup_id`, `kullanici_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 3. Şube Ziyaretleri
    $db->query("CREATE TABLE IF NOT EXISTS `sube_ziyaretleri` (
        `ziyaret_id` INT AUTO_INCREMENT PRIMARY KEY,
        `byk_id` INT NOT NULL,
        `grup_id` INT NOT NULL,
        `ziyaret_tarihi` DATE NOT NULL,
        `cevaplar` JSON DEFAULT NULL,
        `notlar` TEXT NULL,
        `olusturan_id` INT NOT NULL,
        `durum` ENUM('planlandi', 'tamamlandi', 'iptal') DEFAULT 'planlandi',
        `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `guncelleme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_ziyaret_tarihi` (`ziyaret_tarihi`),
        INDEX `idx_ziyaret_byk` (`byk_id`),
        INDEX `idx_ziyaret_grup` (`grup_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

} catch (Exception $e) {
    // Hata durumunda logla veya yoksay
}
