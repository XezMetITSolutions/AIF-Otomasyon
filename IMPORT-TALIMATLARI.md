# 📥 SQL Dosyası Import Talimatları

## ⚠️ ÖNEMLİ: Veritabanı Zaten Oluşturulmuş Olmalı!

`d0451622` veritabanı **zaten oluşturulmuş** olmalı. Bu SQL dosyası sadece **tabloları** oluşturur.

## 🚀 phpMyAdmin ile Import (Adım Adım)

### 1. Veritabanını Kontrol Edin

1. **phpMyAdmin** açın
2. Sol menüden **`d0451622`** veritabanını seçin
3. Eğer görünmüyorsa → cPanel'den oluşturun

### 2. SQL Dosyasını Import Edin

1. phpMyAdmin'de **`d0451622`** veritabanını seçin (sol menü)
2. Üst menüden **SQL** sekmesine tıklayın
   - **VEYA** **İçe Aktar** sekmesine tıklayın
3. **Dosya Seç** butonuna tıklayın
4. **`database/schema.sql`** dosyasını seçin
5. **Git** butonuna tıklayın

### 3. Import Sonucu

✅ **Başarılı:** "15 tablo oluşturuldu" mesajı görmelisiniz

❌ **Hata Alırsanız:**
- "Table already exists" → Tablolar zaten var, normal
- "Access denied" → Veritabanı yetkilerini kontrol edin

## 📋 Oluşturulacak Tablolar

Import sonrası şu tablolar oluşturulur:

1. ✅ `roller` - Kullanıcı rolleri
2. ✅ `byk` - Bölge Yönetim Kurulları
3. ✅ `alt_birimler` - Alt birimler
4. ✅ `kullanicilar` - Kullanıcılar
5. ✅ `modul_yetkileri` - Modül yetkileri
6. ✅ `etkinlikler` - Etkinlikler
7. ✅ `toplantilar` - Toplantılar
8. ✅ `toplanti_katilimcilar` - Toplantı katılımcıları
9. ✅ `toplanti_kararlari` - Toplantı kararları
10. ✅ `projeler` - Projeler
11. ✅ `izin_talepleri` - İzin talepleri
12. ✅ `harcama_talepleri` - Harcama talepleri
13. ✅ `demirbaslar` - Demirbaşlar
14. ✅ `duyurular` - Duyurular
15. ✅ `bildirimler` - Bildirimler

## 🔐 Varsayılan Admin Hesabı

Import sonrası otomatik olarak eklenir:

- **E-posta:** `admin@aif.org`
- **Şifre:** `Admin123!`
- **Rol:** Ana Yönetici (super_admin)
- ⚠️ **İlk girişte şifre değiştirme zorunludur!**

## ✅ Import Sonrası Kontrol

### phpMyAdmin'de Kontrol

1. **`d0451622`** veritabanını seçin
2. **Yapı** sekmesinde 15 tablo görmelisiniz
3. **`kullanicilar`** tablosunu açın
4. **Gözat** sekmesinde `admin@aif.org` kaydını görmelisiniz

### Tablo Sayısı Kontrolü

```sql
SHOW TABLES;
```

Bu komut 15 tablo göstermelidir.

### Admin Kullanıcı Kontrolü

```sql
SELECT * FROM kullanicilar WHERE email = 'admin@aif.org';
```

Bu komut admin kullanıcısını göstermelidir.

## 🐛 Sorun Giderme

### ❌ "Access denied for database"

**Çözüm:**
- Veritabanı adının **`d0451622`** olduğundan emin olun
- phpMyAdmin'de doğru veritabanını seçtiğinizden emin olun

### ❌ "Table already exists"

**Çözüm:**
- Bu normal bir durum, tablolar zaten mevcut
- Yeni bir import yapmak istiyorsanız tabloları önce silin:
  1. phpMyAdmin → Veritabanını seçin
  2. Üst menüden **Operasyonlar** → **Veritabanını sil** (DİKKAT: Tüm veriler silinir!)
  3. Sonra tekrar import edin

### ❌ "Cannot add foreign key constraint"

**Çözüm:**
- Tablolar sırayla oluşturulmalı
- Schema.sql dosyasındaki sırayı koruyun
- Tüm dosyayı bir seferde import edin

### ❌ Import çalıştı ama tablolar yok

**Çözüm:**
1. phpMyAdmin'de doğru veritabanını seçtiğinizden emin olun
2. Import sonrası sayfayı yenileyin
3. Sol menüden veritabanını tekrar seçin

## 📝 Önemli Notlar

1. ✅ **Veritabanı zaten oluşturulmuş olmalı** (`d0451622`)
2. ✅ **Önce veritabanını seçin**, sonra SQL dosyasını import edin
3. ✅ **CREATE DATABASE komutu kaldırıldı** - Sadece tablolar oluşturulur
4. ✅ **Foreign key'ler çalışması için** tüm dosya bir seferde import edilmeli

## 🎯 Hızlı Import Adımları

1. phpMyAdmin aç
2. `d0451622` veritabanını seç (sol menü)
3. SQL sekmesine tıkla
4. `database/schema.sql` dosyasını seç
5. Git butonuna tıkla
6. ✅ Başarılı mesajını bekle

---

**Son Güncelleme:** Kasım 2025

