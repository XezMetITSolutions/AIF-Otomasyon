-- ============================================
-- Şube Ziyaretleri Sistemi - Database Migration
-- ============================================

-- 1. Ziyaret Grupları Tablosu
CREATE TABLE IF NOT EXISTS `ziyaret_gruplari` (
    `grup_id` INT AUTO_INCREMENT PRIMARY KEY,
    `grup_adi` VARCHAR(255) NOT NULL,
    `renk_kodu` VARCHAR(20) DEFAULT '#009872',
    `aktif` TINYINT(1) DEFAULT 1,
    `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Ziyaret Grup Üyeleri Tablosu
CREATE TABLE IF NOT EXISTS `ziyaret_grup_uyeleri` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `grup_id` INT NOT NULL,
    `kullanici_id` INT NOT NULL,
    `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`grup_id`) REFERENCES `ziyaret_gruplari`(`grup_id`) ON DELETE CASCADE,
    FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_grup_kullanici` (`grup_id`, `kullanici_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Şube Ziyaretleri Tablosu
CREATE TABLE IF NOT EXISTS `sube_ziyaretleri` (
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
    FOREIGN KEY (`byk_id`) REFERENCES `byk`(`byk_id`) ON DELETE CASCADE,
    FOREIGN KEY (`grup_id`) REFERENCES `ziyaret_gruplari`(`grup_id`) ON DELETE CASCADE,
    FOREIGN KEY (`olusturan_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
    INDEX `idx_ziyaret_tarihi` (`ziyaret_tarihi`),
    INDEX `idx_ziyaret_byk` (`byk_id`),
    INDEX `idx_ziyaret_grup` (`grup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Migration tamamlandı
-- ============================================
