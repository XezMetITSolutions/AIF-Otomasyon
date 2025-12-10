# 🔄 SQL Dosyasından Veri Migration Talimatları

## 📋 Genel Bilgi

Bu script, `d0451622.sql` dosyasındaki verileri mevcut tablo yapısına uyarlayarak import eder.

**ÖNEMLİ:** Bu script sunucuda çalıştırılmalıdır (veritabanına doğrudan erişim gerektirir).

## 🚀 Migration Adımları

### 1. SQL Dosyasını Önce Import Edin

1. phpMyAdmin'e girin
2. `d0451622` veritabanını seçin
3. `d0451622.sql` dosyasını import edin
   - Bu dosya tüm tabloları (`users`, `events`, `meetings`, `announcements`, `expenses`, vb.) oluşturur

### 2. Migration Scriptini Çalıştırın

**Sunucuda SSH veya FTP ile:**

1. `database/migrate-from-sql.php` dosyasını sunucuya yükleyin
2. SSH ile sunucuya bağlanın
3. Aşağıdaki komutu çalıştırın:

```bash
php database/migrate-from-sql.php
```

**VEYA Web Tarayıcısından:**

1. `https://aifcrm.metechnik.at/database/migrate-from-sql.php` adresine gidin
2. Script otomatik olarak çalışacak ve sonuçları gösterecektir

## 📊 Migration Yapılan Tablolar

### 1. Roller (Roles)
- `users` tablosundaki `role` → `roller` tablosuna
- `superadmin` → `super_admin`
- `manager` → `baskan`
- `member` → `uye`

### 2. BYK Kategorileri
- `byk_categories` → `byk` tablosuna
- AT, KT, KGT, GT kodları migrate edilir

### 3. Kullanıcılar
- `users` → `kullanicilar` tablosuna
- E-posta bazında kontrol (tekrar eklenmez)
- Şifre hash'leri korunur

### 4. Etkinlikler
- `events` → `etkinlikler` tablosuna
- BYK kodlarına göre BYK ID'leri eşleştirilir

### 5. Duyurular
- `announcements` → `duyurular` tablosuna
- `target_audience` alanına göre BYK ID'leri eşleştirilir

### 6. Toplantılar
- `meetings` → `toplantilar` tablosuna
- `byk_code` alanına göre BYK ID'leri eşleştirilir

### 7. Harcama Talepleri
- `expenses` + `expense_items` → `harcama_talepleri` tablosuna
- Durum mapping: `pending` → `beklemede`, `paid` → `odenmistir`, vb.

### 8. Demirbaşlar
- `inventory` → `demirbaslar` tablosuna
- Durum mapping: `active` → `kullanimda`, `maintenance` → `arizali`, vb.

### 9. Projeler
- `projects` → `projeler` tablosuna
- Durum mapping: `planning` → `planlama`, `active` → `aktif`, vb.

## ⚠️ Dikkat Edilmesi Gerekenler

1. **Tekrar Çalıştırma:** Script güvenli bir şekilde tekrar çalıştırılabilir
   - E-posta bazında kontrol yapılır (aynı kullanıcı iki kez eklenmez)
   
2. **Veri Kaybı:** Mevcut veriler silinmez, sadece yeni veriler eklenir

3. **Foreign Key Hataları:** Eğer BYK veya kullanıcı bulunamazsa ilgili kayıt atlanır

4. **Durum Mapping:** Tüm durum değerleri Türkçe karşılıklarına çevrilir

## ✅ Migration Sonrası Kontrol

Migration sonrası kontrol edilmesi gerekenler:

```sql
-- Kullanıcı sayısı
SELECT COUNT(*) as toplam_kullanici FROM kullanicilar;

-- BYK sayısı
SELECT COUNT(*) as toplam_byk FROM byk;

-- Etkinlik sayısı
SELECT COUNT(*) as toplam_etkinlik FROM etkinlikler;

-- Duyuru sayısı
SELECT COUNT(*) as toplam_duyuru FROM duyurular;

-- Toplantı sayısı
SELECT COUNT(*) as toplam_toplanti FROM toplantilar;
```

## 🔧 Sorun Giderme

### Hata: "Table doesn't exist"
- Önce `d0451622.sql` dosyasını import edin
- Tüm tablolar oluşturulmuş olmalı

### Hata: "Access denied"
- Veritabanı kullanıcısının yetkilerini kontrol edin
- `config/database.php` dosyasındaki bilgileri kontrol edin

### Hata: "Duplicate entry"
- Normal bir durum, script tekrar eden kayıtları atlar
- E-posta bazında kontrol yapılır

### Migration Yarıda Kesildi
- Script güvenli bir şekilde tekrar çalıştırılabilir
- Zaten var olan kayıtlar atlanır

