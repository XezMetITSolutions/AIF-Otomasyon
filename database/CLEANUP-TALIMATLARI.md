# 🗑️ Eski Tabloları Temizleme Talimatları

## 📋 Genel Bilgi

Migration sonrası eski tablolar (`users`, `events`, `announcements`, vb.) artık gereksiz hale geldi. Veriler yeni tablolara (`kullanicilar`, `etkinlikler`, `duyurular`, vb.) migrate edildi.

## ⚠️ ÖNEMLİ: Temizlemeden Önce

1. ✅ **Verilerin migrate edildiğinden emin olun**
   - Yeni tablolarda verilerin olduğunu kontrol edin
   - Eksik veri varsa migration scriptini tekrar çalıştırın

2. ✅ **Yedek alın**
   - Veritabanının tam yedeğini alın
   - Veya en azından silinecek tabloları export edin

3. ✅ **Test ortamında deneyin** (mümkünse)

## 🗑️ Silinecek Tablolar (Güvenli)

### Ana Tablolar (Veriler Yeni Tablolarda)
- ✅ `users` → `kullanicilar`'a migrate edildi
- ✅ `events` → `etkinlikler`'e migrate edildi
- ✅ `announcements` → `duyurular`'a migrate edildi
- ✅ `meetings` → `toplantilar`'a migrate edildi
- ✅ `expenses` → `harcama_talepleri`'ne migrate edildi
- ✅ `inventory` → `demirbaslar`'a migrate edildi
- ✅ `projects` → `projeler`'e migrate edildi

### İlişkili Tablolar (Ana Tablolara Bağlı)
- ✅ `expense_items` → `expenses`'e bağlı
- ✅ `meeting_agenda` → `meetings`'e bağlı
- ✅ `meeting_decisions` → `meetings`'e bağlı
- ✅ `meeting_files` → `meetings`'e bağlı
- ✅ `meeting_follow_ups` → `meetings`'e bağlı
- ✅ `meeting_notes` → `meetings`'e bağlı
- ✅ `meeting_notifications` → `meetings`'e bağlı
- ✅ `meeting_participants` → `meetings`'e bağlı
- ✅ `meeting_reports` → `meetings`'e bağlı
- ✅ `user_permissions` → `users`'a bağlı
- ✅ `user_sessions` → `users`'a bağlı

## ⚠️ Kontrol Edilmesi Gereken Tablolar

Bu tablolar başka yerlerde kullanılıyor olabilir, **manuel kontrol gerekli**:

- ⚠️ `byk_categories` → BYK tablosuna migrate edildi (ama başka yerde kullanılıyor mu?)
- ⚠️ `byk_sub_units` → Alt birimler tablosuna migrate edildi (ama başka yerde kullanılıyor mu?)
- ⚠️ `byk_units` → BYK tablosuna migrate edildi (ama başka yerde kullanılıyor mu?)
- ⚠️ `calendar_events` → Takvim için kullanılıyor mu?
- ⚠️ `event_types` → Etkinlik tipleri gerekli mi?
- ⚠️ `expense_types` → Harcama tipleri gerekli mi?
- ⚠️ `announcement_types` → Duyuru tipleri gerekli mi?
- ⚠️ `sub_units` → Alt birimler için kullanılıyor mu?

## ❌ SİLİNMEMELİ Tablolar

Bu tablolar sistem için **gerekli**:

- ❌ `system_settings` → Sistem ayarları
- ❌ `modules` → Modül yönetimi
- ❌ `positions` → Pozisyonlar (gerekli olabilir)
- ❌ `reports` → Raporlar (gerekli olabilir)
- ❌ `reservations` → Rezervasyonlar (gerekli olabilir)

## 🚀 Temizleme Yöntemleri

### Yöntem 1: PHP Script ile (Önerilen)

```bash
# SSH ile
php database/cleanup-old-tables.php

# Veya web tarayıcısından
https://aifcrm.metechnik.at/database/cleanup-old-tables.php
```

**Avantajları:**
- Güvenli kontrol yapar
- Tablo var mı kontrol eder
- Kayıt sayısını gösterir
- Hata yakalama mevcut

### Yöntem 2: SQL Dosyası ile (Manuel)

1. phpMyAdmin'e girin
2. `d0451622` veritabanını seçin
3. **SQL** sekmesine tıklayın
4. `database/cleanup-old-tables.sql` dosyasının içeriğini kopyalayın
5. SQL sorgu alanına yapıştırın
6. **Git** butonuna tıklayın

**Dikkat:** SQL dosyasında yorum satırları var, sadece `DROP TABLE` komutlarını çalıştırın.

## ✅ Temizleme Sonrası Kontrol

Temizleme sonrası kontrol edilmesi gerekenler:

```sql
-- Yeni tablolarda veri var mı?
SELECT COUNT(*) as kullanici FROM kullanicilar;
SELECT COUNT(*) as etkinlik FROM etkinlikler;
SELECT COUNT(*) as duyuru FROM duyurular;
SELECT COUNT(*) as toplanti FROM toplantilar;
SELECT COUNT(*) as harcama FROM harcama_talepleri;
SELECT COUNT(*) as demirbas FROM demirbaslar;
SELECT COUNT(*) as proje FROM projeler;

-- Eski tablolar silindi mi?
SHOW TABLES LIKE 'users';
SHOW TABLES LIKE 'events';
SHOW TABLES LIKE 'announcements';
SHOW TABLES LIKE 'meetings';
```

## 🔧 Sorun Giderme

### Hata: "Cannot delete or update a parent row"
- Foreign key constraint hatası
- Script otomatik olarak `SET FOREIGN_KEY_CHECKS = 0` yapıyor
- Manuel SQL çalıştırıyorsanız önce bu komutu çalıştırın

### Hata: "Table doesn't exist"
- Tablo zaten silinmiş, normal

### Veriler kayboldu mu?
- Yedekten geri yükleyin
- Migration scriptini tekrar çalıştırın

## 📝 Özet

**Kesin Silinecek (11 tablo):**
1. users
2. events
3. announcements
4. meetings
5. expenses
6. inventory
7. projects
8. expense_items
9. meeting_* (8 tablo)
10. user_permissions
11. user_sessions

**Kontrol Edilecek (8 tablo):**
- byk_categories, byk_sub_units, byk_units, calendar_events, event_types, expense_types, announcement_types, sub_units

**Silinmeyecek (5+ tablo):**
- system_settings, modules, positions, reports, reservations (ve diğer sistem tabloları)

