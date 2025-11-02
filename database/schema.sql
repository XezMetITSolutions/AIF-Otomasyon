-- AIF Otomasyon Sistemi Veritabanı Şeması
-- MySQL 8.0+ için optimize edilmiştir

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Veritabanı oluştur
CREATE DATABASE IF NOT EXISTS `aif_otomasyon` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `aif_otomasyon`;

-- Tabloları oluştur

-- Roller tablosu
CREATE TABLE IF NOT EXISTS `roller` (
  `rol_id` int(11) NOT NULL AUTO_INCREMENT,
  `rol_adi` varchar(50) NOT NULL,
  `rol_aciklama` text,
  `rol_yetki_seviyesi` int(11) NOT NULL DEFAULT '1' COMMENT '1=Üye, 2=Başkan, 3=Ana Yönetici',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rol_id`),
  UNIQUE KEY `rol_adi` (`rol_adi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bölge Yönetim Kurulları (BYK) tablosu
CREATE TABLE IF NOT EXISTS `byk` (
  `byk_id` int(11) NOT NULL AUTO_INCREMENT,
  `byk_adi` varchar(255) NOT NULL,
  `byk_kodu` varchar(20) NOT NULL,
  `renk_kodu` varchar(7) DEFAULT '#007bff' COMMENT 'Hex renk kodu',
  `aciklama` text,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`byk_id`),
  UNIQUE KEY `byk_kodu` (`byk_kodu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alt birimler tablosu
CREATE TABLE IF NOT EXISTS `alt_birimler` (
  `alt_birim_id` int(11) NOT NULL AUTO_INCREMENT,
  `byk_id` int(11) NOT NULL,
  `alt_birim_adi` varchar(255) NOT NULL,
  `alt_birim_kodu` varchar(20),
  `aciklama` text,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`alt_birim_id`),
  KEY `byk_id` (`byk_id`),
  FOREIGN KEY (`byk_id`) REFERENCES `byk`(`byk_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `kullanici_id` int(11) NOT NULL AUTO_INCREMENT,
  `rol_id` int(11) NOT NULL,
  `byk_id` int(11) DEFAULT NULL COMMENT 'Başkan ve üyeler için',
  `alt_birim_id` int(11) DEFAULT NULL COMMENT 'Üyeler için',
  `email` varchar(255) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `ad` varchar(100) NOT NULL,
  `soyad` varchar(100) NOT NULL,
  `telefon` varchar(20),
  `profil_resmi` varchar(255),
  `ilk_giris_zorunlu` tinyint(1) NOT NULL DEFAULT '1',
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `son_giris` datetime DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kullanici_id`),
  UNIQUE KEY `email` (`email`),
  KEY `rol_id` (`rol_id`),
  KEY `byk_id` (`byk_id`),
  KEY `alt_birim_id` (`alt_birim_id`),
  FOREIGN KEY (`rol_id`) REFERENCES `roller`(`rol_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`byk_id`) REFERENCES `byk`(`byk_id`) ON DELETE SET NULL,
  FOREIGN KEY (`alt_birim_id`) REFERENCES `alt_birimler`(`alt_birim_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modül yetkileri tablosu
CREATE TABLE IF NOT EXISTS `modul_yetkileri` (
  `yetki_id` int(11) NOT NULL AUTO_INCREMENT,
  `rol_id` int(11) NOT NULL,
  `modul_adi` varchar(100) NOT NULL,
  `goruntuleme` tinyint(1) NOT NULL DEFAULT '0',
  `ekleme` tinyint(1) NOT NULL DEFAULT '0',
  `duzenleme` tinyint(1) NOT NULL DEFAULT '0',
  `silme` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`yetki_id`),
  UNIQUE KEY `rol_modul` (`rol_id`, `modul_adi`),
  FOREIGN KEY (`rol_id`) REFERENCES `roller`(`rol_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Etkinlikler tablosu
CREATE TABLE IF NOT EXISTS `etkinlikler` (
  `etkinlik_id` int(11) NOT NULL AUTO_INCREMENT,
  `byk_id` int(11) NOT NULL,
  `alt_birim_id` int(11) DEFAULT NULL,
  `baslik` varchar(255) NOT NULL,
  `aciklama` text,
  `baslangic_tarihi` datetime NOT NULL,
  `bitis_tarihi` datetime NOT NULL,
  `konum` varchar(255),
  `renk_kodu` varchar(7) DEFAULT '#007bff',
  `tekrarlama` varchar(50) DEFAULT NULL COMMENT 'gunluk, haftalik, aylik, yillik',
  `olusturan_id` int(11) NOT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`etkinlik_id`),
  KEY `byk_id` (`byk_id`),
  KEY `alt_birim_id` (`alt_birim_id`),
  KEY `olusturan_id` (`olusturan_id`),
  FOREIGN KEY (`byk_id`) REFERENCES `byk`(`byk_id`) ON DELETE CASCADE,
  FOREIGN KEY (`alt_birim_id`) REFERENCES `alt_birimler`(`alt_birim_id`) ON DELETE SET NULL,
  FOREIGN KEY (`olusturan_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Toplantılar tablosu
CREATE TABLE IF NOT EXISTS `toplantilar` (
  `toplanti_id` int(11) NOT NULL AUTO_INCREMENT,
  `byk_id` int(11) NOT NULL,
  `toplanti_turu` varchar(50) NOT NULL DEFAULT 'normal' COMMENT 'normal, acil, ozel',
  `baslik` varchar(255) NOT NULL,
  `aciklama` text,
  `toplanti_tarihi` datetime NOT NULL,
  `konum` varchar(255),
  `gundem` text,
  `olusturan_id` int(11) NOT NULL,
  `durum` varchar(50) NOT NULL DEFAULT 'planlandi' COMMENT 'planlandi, devam_ediyor, tamamlandi, iptal',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`toplanti_id`),
  KEY `byk_id` (`byk_id`),
  KEY `olusturan_id` (`olusturan_id`),
  FOREIGN KEY (`byk_id`) REFERENCES `byk`(`byk_id`) ON DELETE CASCADE,
  FOREIGN KEY (`olusturan_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Toplantı katılımcıları tablosu
CREATE TABLE IF NOT EXISTS `toplanti_katilimcilar` (
  `katilimci_id` int(11) NOT NULL AUTO_INCREMENT,
  `toplanti_id` int(11) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `katilim_durumu` varchar(50) DEFAULT 'beklemede' COMMENT 'beklemede, katilacak, katilmayacak, mazeret',
  `mazeret_aciklama` text,
  `yanit_tarihi` datetime DEFAULT NULL,
  PRIMARY KEY (`katilimci_id`),
  UNIQUE KEY `toplanti_kullanici` (`toplanti_id`, `kullanici_id`),
  KEY `kullanici_id` (`kullanici_id`),
  FOREIGN KEY (`toplanti_id`) REFERENCES `toplantilar`(`toplanti_id`) ON DELETE CASCADE,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Toplantı kararları tablosu
CREATE TABLE IF NOT EXISTS `toplanti_kararlari` (
  `karar_id` int(11) NOT NULL AUTO_INCREMENT,
  `toplanti_id` int(11) NOT NULL,
  `karar_metni` text NOT NULL,
  `sorumlu_id` int(11) DEFAULT NULL,
  `durum` varchar(50) NOT NULL DEFAULT 'beklemede' COMMENT 'beklemede, devam_ediyor, tamamlandi',
  `teslim_tarihi` date DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`karar_id`),
  KEY `toplanti_id` (`toplanti_id`),
  KEY `sorumlu_id` (`sorumlu_id`),
  FOREIGN KEY (`toplanti_id`) REFERENCES `toplantilar`(`toplanti_id`) ON DELETE CASCADE,
  FOREIGN KEY (`sorumlu_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projeler tablosu
CREATE TABLE IF NOT EXISTS `projeler` (
  `proje_id` int(11) NOT NULL AUTO_INCREMENT,
  `byk_id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `aciklama` text,
  `durum` varchar(50) NOT NULL DEFAULT 'planlama' COMMENT 'planlama, aktif, tamamlandi, iptal',
  `baslangic_tarihi` date,
  `bitis_tarihi` date,
  `sorumlu_id` int(11) DEFAULT NULL,
  `olusturan_id` int(11) NOT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`proje_id`),
  KEY `byk_id` (`byk_id`),
  KEY `sorumlu_id` (`sorumlu_id`),
  KEY `olusturan_id` (`olusturan_id`),
  FOREIGN KEY (`byk_id`) REFERENCES `byk`(`byk_id`) ON DELETE CASCADE,
  FOREIGN KEY (`sorumlu_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE SET NULL,
  FOREIGN KEY (`olusturan_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- İzin talepleri (Urlaubsanfrage) tablosu
CREATE TABLE IF NOT EXISTS `izin_talepleri` (
  `izin_id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `baslangic_tarihi` date NOT NULL,
  `bitis_tarihi` date NOT NULL,
  `izin_nedeni` varchar(255),
  `aciklama` text,
  `durum` varchar(50) NOT NULL DEFAULT 'beklemede' COMMENT 'beklemede, onaylandi, reddedildi',
  `onaylayan_id` int(11) DEFAULT NULL,
  `onay_tarihi` datetime DEFAULT NULL,
  `onay_aciklama` text,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`izin_id`),
  KEY `kullanici_id` (`kullanici_id`),
  KEY `onaylayan_id` (`onaylayan_id`),
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
  FOREIGN KEY (`onaylayan_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Harcama talepleri tablosu
CREATE TABLE IF NOT EXISTS `harcama_talepleri` (
  `talep_id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `byk_id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `aciklama` text,
  `tutar` decimal(10,2) NOT NULL,
  `dosya_yolu` varchar(255),
  `durum` varchar(50) NOT NULL DEFAULT 'beklemede' COMMENT 'beklemede, onaylandi, reddedildi, odenmistir',
  `onaylayan_id` int(11) DEFAULT NULL,
  `onay_tarihi` datetime DEFAULT NULL,
  `onay_aciklama` text,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`talep_id`),
  KEY `kullanici_id` (`kullanici_id`),
  KEY `byk_id` (`byk_id`),
  KEY `onaylayan_id` (`onaylayan_id`),
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
  FOREIGN KEY (`byk_id`) REFERENCES `byk`(`byk_id`) ON DELETE CASCADE,
  FOREIGN KEY (`onaylayan_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demirbaşlar tablosu
CREATE TABLE IF NOT EXISTS `demirbaslar` (
  `demirbas_id` int(11) NOT NULL AUTO_INCREMENT,
  `byk_id` int(11) DEFAULT NULL,
  `alt_birim_id` int(11) DEFAULT NULL,
  `demirbas_adi` varchar(255) NOT NULL,
  `kategori` varchar(100),
  `seri_no` varchar(100),
  `durum` varchar(50) NOT NULL DEFAULT 'kullanimda' COMMENT 'kullanimda, depoda, arizali',
  `aciklama` text,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`demirbas_id`),
  KEY `byk_id` (`byk_id`),
  KEY `alt_birim_id` (`alt_birim_id`),
  FOREIGN KEY (`byk_id`) REFERENCES `byk`(`byk_id`) ON DELETE SET NULL,
  FOREIGN KEY (`alt_birim_id`) REFERENCES `alt_birimler`(`alt_birim_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Duyurular tablosu
CREATE TABLE IF NOT EXISTS `duyurular` (
  `duyuru_id` int(11) NOT NULL AUTO_INCREMENT,
  `byk_id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `icerik` text NOT NULL,
  `olusturan_id` int(11) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`duyuru_id`),
  KEY `byk_id` (`byk_id`),
  KEY `olusturan_id` (`olusturan_id`),
  FOREIGN KEY (`byk_id`) REFERENCES `byk`(`byk_id`) ON DELETE CASCADE,
  FOREIGN KEY (`olusturan_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bildirimler tablosu
CREATE TABLE IF NOT EXISTS `bildirimler` (
  `bildirim_id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `mesaj` text NOT NULL,
  `tip` varchar(50) NOT NULL DEFAULT 'bilgi' COMMENT 'bilgi, uyari, basarili, hata',
  `link` varchar(255),
  `okundu` tinyint(1) NOT NULL DEFAULT '0',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bildirim_id`),
  KEY `kullanici_id` (`kullanici_id`),
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan verileri ekle

-- Rolleri ekle
INSERT INTO `roller` (`rol_id`, `rol_adi`, `rol_aciklama`, `rol_yetki_seviyesi`) VALUES
(1, 'super_admin', 'Ana Yönetici - Tüm sistemin yönetimi', 3),
(2, 'baskan', 'Başkan - BYK yönetimi', 2),
(3, 'uye', 'Üye - Kişisel alan', 1);

-- Varsayılan ana yönetici kullanıcısı (şifre: Admin123!)
INSERT INTO `kullanicilar` (`kullanici_id`, `rol_id`, `email`, `sifre`, `ad`, `soyad`, `ilk_giris_zorunlu`, `aktif`) VALUES
(1, 1, 'admin@aif.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana', 'Yönetici', 1, 1);

