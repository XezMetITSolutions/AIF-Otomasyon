-- SQL skript pro vytvoření tabulek demirbas_talepleri a raggal_talepleri
-- Tento soubor můžete importovat přímo v phpMyAdmin

-- Tabulka pro demirbaş talepleri
CREATE TABLE IF NOT EXISTS `demirbas_talepleri` (
    `talep_id` INT AUTO_INCREMENT PRIMARY KEY,
    `kullanici_id` INT NOT NULL,
    `demirbas_id` INT DEFAULT NULL,
    `baslik` VARCHAR(255),
    `aciklama` TEXT,
    `baslangic_tarihi` DATETIME DEFAULT NULL,
    `bitis_tarihi` DATETIME DEFAULT NULL,
    `durum` VARCHAR(50) DEFAULT 'bekliyor',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
    FOREIGN KEY (`demirbas_id`) REFERENCES `demirbaslar`(`demirbas_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabulka pro raggal rezervasyon talepleri
CREATE TABLE IF NOT EXISTS `raggal_talepleri` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kullanici_id` INT NOT NULL,
    `baslangic_tarihi` DATETIME NOT NULL,
    `bitis_tarihi` DATETIME NOT NULL,
    `aciklama` TEXT,
    `durum` VARCHAR(50) DEFAULT 'beklemede',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
