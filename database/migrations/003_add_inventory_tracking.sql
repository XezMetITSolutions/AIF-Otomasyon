-- Migration: Add tracking fields to demirbaslar table
-- Purpose: Track who has the item, when given, and when returning

ALTER TABLE demirbaslar
ADD COLUMN mevcut_kullanici_id INT NULL COMMENT 'Şu anda kimde',
ADD COLUMN verilis_tarihi DATE NULL COMMENT 'Ne zaman verildi',
ADD COLUMN donus_tarihi DATE NULL COMMENT 'Ne zaman dönecek',
ADD COLUMN notlar TEXT NULL COMMENT 'Ek notlar',
ADD CONSTRAINT fk_demirbas_mevcut_kullanici 
    FOREIGN KEY (mevcut_kullanici_id) 
    REFERENCES kullanicilar(kullanici_id) 
    ON DELETE SET NULL;
