# AIF Otomasyon - Yetki Sistemi Kurulum Rehberi (aifcrm.metechnik.at)

## 🎯 Kurulum Adımları

### 1. Veritabanı Bağlantısını Test Edin
```
http://aifcrm.metechnik.at/admin/test_database_aifcrm.php
```

Bu script:
- Veritabanı bağlantısını test eder
- Mevcut tabloları listeler
- Yetki sistemi durumunu kontrol eder

### 2. Yetki Sistemini Kurun
```
http://aifcrm.metechnik.at/admin/setup_permissions_aifcrm.php
```

Bu script:
- `modules` tablosunu oluşturur
- `user_permissions` tablosunu oluşturur
- 13 modülü veritabanına yükler

### 3. Ana Kurulumu Tamamlayın
```
http://aifcrm.metechnik.at/admin/setup.php
```

Bu script:
- Tüm gerekli tabloları oluşturur
- Örnek verileri ekler
- Yetki sistemi tablolarını da kurar

## 🔧 Alternatif Kurulum Yöntemleri

### Yöntem 1: Mevcut Kurulum Sistemini Kullanın
1. `setup.php` sayfasına gidin
2. "Mevcut Tabloları Kontrol Et" butonuna tıklayın
3. "Bağlantıyı Test Et" butonuna tıklayın
4. "Tabloları Oluştur/Güncelle" butonuna tıklayın
5. "Örnek Veri Ekle" butonuna tıklayın
6. "Kurulumu Tamamla" butonuna tıklayın

### Yöntem 2: Manuel SQL Kurulumu
Eğer scriptler çalışmazsa, aşağıdaki SQL komutlarını manuel olarak çalıştırın:

```sql
-- Modüller tablosu
CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcı yetkileri tablosu
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    can_read BOOLEAN DEFAULT FALSE,
    can_write BOOLEAN DEFAULT FALSE,
    can_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_module (user_id, module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 📊 Kurulum Sonrası Kontroller

### 1. Tablo Kontrolü
```sql
SHOW TABLES;
```
Aşağıdaki tabloların mevcut olduğundan emin olun:
- `modules`
- `user_permissions`
- `users`
- `expenses`
- `expense_items`

### 2. Modül Kontrolü
```sql
SELECT COUNT(*) FROM modules;
```
13 modülün yüklendiğinden emin olun.

### 3. Yetki Sistemi Testi
```
http://aifcrm.metechnik.at/admin/user_permissions.php
```
Bu sayfaya erişebildiğinizden emin olun.

## 🚨 Sorun Giderme

### Veritabanı Bağlantı Hatası
- `includes/database.php` dosyasındaki bağlantı bilgilerini kontrol edin
- MySQL servisinin çalıştığından emin olun
- Veritabanı kullanıcısının yetkilerini kontrol edin

### Tablo Oluşturma Hatası
- Veritabanı kullanıcısının `CREATE TABLE` yetkisi olduğundan emin olun
- MySQL versiyonunuzun 5.7+ olduğundan emin olun
- Veritabanı karakter setinin `utf8mb4` olduğundan emin olun

### Modül Yükleme Hatası
- `modules` tablosunun oluşturulduğundan emin olun
- Veritabanı bağlantısının çalıştığından emin olun
- Hata loglarını kontrol edin

## 📞 Destek

Herhangi bir sorun yaşarsanız:
1. Hata mesajlarını not alın
2. Veritabanı loglarını kontrol edin
3. Test scriptlerini çalıştırın
4. Gerekirse manuel SQL kurulumu yapın

---

**Not**: Bu rehber `aifcrm.metechnik.at` domain'i için özel olarak hazırlanmıştır.

