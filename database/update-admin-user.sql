-- Admin Kullanıcısını Güncelleme Scripti
-- Bu script mevcut admin kullanıcısını günceller

-- E-posta ve şifreyi güncelle (egitim@islamfederasyonu.at / 01528797Mb##)
UPDATE `kullanicilar` 
SET 
    `email` = 'egitim@islamfederasyonu.at',
    `sifre` = '$2y$10$O0iBgiQbOTMfJej52oXPu.AT/xXXMF6HVOQDyxtxpHCZbO9AkhIX.',
    `ilk_giris_zorunlu` = 0
WHERE `kullanici_id` = 1 
   OR `email` = 'admin@aif.org'
   OR `rol_id` = (SELECT `rol_id` FROM `roller` WHERE `rol_adi` = 'super_admin' LIMIT 1);

-- Eğer admin kullanıcısı yoksa yeni oluştur
INSERT INTO `kullanicilar` (`kullanici_id`, `rol_id`, `email`, `sifre`, `ad`, `soyad`, `ilk_giris_zorunlu`, `aktif`)
SELECT 1, 1, 'egitim@islamfederasyonu.at', '$2y$10$O0iBgiQbOTMfJej52oXPu.AT/xXXMF6HVOQDyxtxpHCZbO9AkhIX.', 'Ana', 'Yönetici', 0, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `kullanicilar` 
    WHERE `kullanici_id` = 1 
       OR `email` = 'egitim@islamfederasyonu.at'
       OR `rol_id` = (SELECT `rol_id` FROM `roller` WHERE `rol_adi` = 'super_admin' LIMIT 1)
);

