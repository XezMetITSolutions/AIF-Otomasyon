-- ============================================
-- Toplantı Yönetim Sistemi - Database Migration
-- ============================================

-- 1. Toplantı Katılımcılar Tablosu
CREATE TABLE IF NOT EXISTS `toplanti_katilimcilar` (
    `katilimci_id` INT AUTO_INCREMENT PRIMARY KEY,
    `toplanti_id` INT NOT NULL,
    `kullanici_id` INT NOT NULL,
    `katilim_durumu` ENUM('katildi', 'ozur_diledi', 'izinli', 'katilmadi') DEFAULT 'katildi',
    `notlar` TEXT NULL,
    `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`toplanti_id`) REFERENCES `toplantilar`(`toplanti_id`) ON DELETE CASCADE,
    FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_toplanti_kullanici` (`toplanti_id`, `kullanici_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Toplantı Gündem Tablosu
CREATE TABLE IF NOT EXISTS `toplanti_gundem` (
    `gundem_id` INT AUTO_INCREMENT PRIMARY KEY,
    `toplanti_id` INT NOT NULL,
    `sira_no` INT NOT NULL DEFAULT 1,
    `baslik` VARCHAR(500) NOT NULL,
    `aciklama` TEXT NULL,
    `sunum_dosyasi` VARCHAR(255) NULL,
    `durum` ENUM('beklemede', 'gorusuluyor', 'karara_baglandi', 'ertelendi') DEFAULT 'beklemede',
    `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`toplanti_id`) REFERENCES `toplantilar`(`toplanti_id`) ON DELETE CASCADE,
    INDEX `idx_toplanti_sira` (`toplanti_id`, `sira_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Toplantı Kararlar Tablosu
CREATE TABLE IF NOT EXISTS `toplanti_kararlar` (
    `karar_id` INT AUTO_INCREMENT PRIMARY KEY,
    `toplanti_id` INT NOT NULL,
    `gundem_id` INT NULL,
    `karar_no` VARCHAR(50) NULL,
    `baslik` VARCHAR(500) NOT NULL,
    `karar_metni` TEXT NOT NULL,
    `oylama_yapildi` TINYINT(1) DEFAULT 0,
    `kabul_oyu` INT DEFAULT 0,
    `red_oyu` INT DEFAULT 0,
    `cekinser_oyu` INT DEFAULT 0,
    `karar_sonucu` ENUM('kabul', 'red', 'ertelendi') NULL,
    `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`toplanti_id`) REFERENCES `toplantilar`(`toplanti_id`) ON DELETE CASCADE,
    FOREIGN KEY (`gundem_id`) REFERENCES `toplanti_gundem`(`gundem_id`) ON DELETE SET NULL,
    INDEX `idx_toplanti_karar` (`toplanti_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Toplantı Tutanak Tablosu
CREATE TABLE IF NOT EXISTS `toplanti_tutanak` (
    `tutanak_id` INT AUTO_INCREMENT PRIMARY KEY,
    `toplanti_id` INT NOT NULL UNIQUE,
    `tutanak_metni` LONGTEXT NOT NULL,
    `tutanak_no` VARCHAR(50) NULL,
    `tutanak_tarihi` DATE NULL,
    `yazan_kullanici_id` INT NULL,
    `onaylayan_kullanici_id` INT NULL,
    `onay_tarihi` TIMESTAMP NULL,
    `durum` ENUM('taslak', 'onay_bekliyor', 'onaylandi') DEFAULT 'taslak',
    `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`toplanti_id`) REFERENCES `toplantilar`(`toplanti_id`) ON DELETE CASCADE,
    FOREIGN KEY (`yazan_kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE SET NULL,
    FOREIGN KEY (`onaylayan_kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Mevcut toplantilar tablosuna ek alanlar
-- Not: Eğer kolonlar zaten varsa hata verecektir, bu normaldir
SET @dbname = DATABASE();
SET @tablename = "toplantilar";

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = 'toplanti_no')) > 0,
  "SELECT 1",
  "ALTER TABLE toplantilar ADD COLUMN toplanti_no VARCHAR(50) NULL AFTER toplanti_id"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = 'bitis_tarihi')) > 0,
  "SELECT 1",
  "ALTER TABLE toplantilar ADD COLUMN bitis_tarihi DATETIME NULL AFTER toplanti_tarihi"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = 'toplanti_turu')) > 0,
  "SELECT 1",
  "ALTER TABLE toplantilar ADD COLUMN toplanti_turu ENUM('normal', 'acil', 'ozel', 'olagan', 'olaganüstü') DEFAULT 'normal' AFTER gundem"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = 'durum')) > 0,
  "SELECT 1",
  "ALTER TABLE toplantilar ADD COLUMN durum ENUM('planlandi', 'devam_ediyor', 'tamamlandi', 'iptal') DEFAULT 'planlandi' AFTER toplanti_turu"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = 'katilimci_sayisi')) > 0,
  "SELECT 1",
  "ALTER TABLE toplantilar ADD COLUMN katilimci_sayisi INT DEFAULT 0 AFTER durum"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = 'karar_sayisi')) > 0,
  "SELECT 1",
  "ALTER TABLE toplantilar ADD COLUMN karar_sayisi INT DEFAULT 0 AFTER katilimci_sayisi"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 6. İndeksler oluştur
CREATE INDEX IF NOT EXISTS `idx_toplanti_tarihi` ON `toplantilar`(`toplanti_tarihi`);
CREATE INDEX IF NOT EXISTS `idx_toplanti_byk` ON `toplantilar`(`byk_id`);
CREATE INDEX IF NOT EXISTS `idx_toplanti_durum` ON `toplantilar`(`durum`);

-- ============================================
-- Migration tamamlandı
-- ============================================
