# 🗄️ Veritabanı Kurulum Kılavuzu

## 📋 Veritabanı Bilgileri

- **Veritabanı Host:** `localhost`
- **Veritabanı Adı:** `d0451622`
- **Veritabanı Kullanıcı:** `d0451622`
- **Veritabanı Şifre:** `01528797Mb##`

## 🚀 Kurulum Yöntemleri

### Yöntem 1: PHP Script ile Otomatik Kurulum (Önerilen)

#### Lokal Makinede (Test İçin)

1. **XAMPP/WAMP/LAMP** kurulu olmalı
2. **MySQL** servisinin çalıştığından emin olun
3. Script'i çalıştırın:

```bash
cd C:\Users\IT Admin\Documents\Otomasyon
php scripts\setup-database.php
```

veya

```batch
scripts\setup-database.bat
```

#### Sunucuda (cPanel/phpMyAdmin)

1. **cPanel → File Manager** ile sunucuya dosyaları yükleyin
2. **cPanel → Terminal** veya **SSH** ile bağlanın
3. Script'i çalıştırın:

```bash
cd /path/to/project
php scripts/setup-database.php
```

### Yöntem 2: phpMyAdmin ile Manuel Kurulum

1. **cPanel → phpMyAdmin** açın
2. **Yeni** → Veritabanı oluştur:
   - Veritabanı adı: `d0451622`
   - Karakter kümesi: `utf8mb4_unicode_ci`
   - **Oluştur** butonuna tıklayın
3. Veritabanını seçin (`d0451622`)
4. **İçe Aktar** sekmesine gidin
5. **Dosya Seç** butonuna tıklayın
6. `database/schema.sql` dosyasını seçin
7. **Git** butonuna tıklayın

### Yöntem 3: MySQL Komut Satırı ile

1. **SSH** veya **Terminal** ile sunucuya bağlanın
2. MySQL'e giriş yapın:

```bash
mysql -u d0451622 -p
# Şifre: 01528797Mb##
```

3. Veritabanını oluşturun:

```sql
CREATE DATABASE IF NOT EXISTS `d0451622` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. Veritabanını seçin:

```sql
USE `d0451622`;
```

5. Schema dosyasını yükleyin:

```bash
mysql -u d0451622 -p d0451622 < database/schema.sql
```

## ⚙️ Yapılandırma Dosyası Güncelleme

Script otomatik olarak `config/database.php` dosyasını günceller.

Manuel olarak güncellemek için:

```php
// config/database.php
return [
    'host' => 'localhost',
    'dbname' => 'd0451622',
    'username' => 'd0451622',
    'password' => '01528797Mb##',
    'charset' => 'utf8mb4',
    // ...
];
```

## ✅ Kurulum Sonrası Kontrol

1. **Veritabanı Bağlantısını Test Edin:**

```php
<?php
require_once 'includes/init.php';
$db = Database::getInstance();
echo "✅ Veritabanı bağlantısı başarılı!";
?>
```

2. **Varsayılan Admin Hesabı ile Giriş Yapın:**

- **E-posta:** `admin@aif.org`
- **Şifre:** `Admin123!`
- ⚠️ **İlk girişte şifre değiştirme zorunludur!**

## 📊 Oluşturulan Tablolar

Script aşağıdaki tabloları oluşturur:

- ✅ `roller` - Kullanıcı rolleri
- ✅ `byk` - Bölge Yönetim Kurulları
- ✅ `alt_birimler` - Alt birimler
- ✅ `kullanicilar` - Kullanıcılar
- ✅ `modul_yetkileri` - Modül yetkileri
- ✅ `etkinlikler` - Etkinlikler
- ✅ `toplantilar` - Toplantılar
- ✅ `toplanti_katilimcilar` - Toplantı katılımcıları
- ✅ `toplanti_kararlari` - Toplantı kararları
- ✅ `projeler` - Projeler
- ✅ `izin_talepleri` - İzin talepleri
- ✅ `harcama_talepleri` - Harcama talepleri
- ✅ `demirbaslar` - Demirbaşlar
- ✅ `duyurular` - Duyurular
- ✅ `bildirimler` - Bildirimler

## 🔐 Varsayılan Veriler

Script aşağıdaki varsayılan verileri ekler:

### Roller:
- **super_admin** (Ana Yönetici)
- **baskan** (Başkan)
- **uye** (Üye)

### Kullanıcı:
- **Email:** `admin@aif.org`
- **Şifre:** `Admin123!`
- **Rol:** Ana Yönetici
- **İlk Giriş:** Şifre değiştirme zorunlu

## 🐛 Sorun Giderme

### MySQL Bağlantı Hatası

**Hata:** `Connection refused` veya `Access denied`

**Çözüm:**
1. MySQL servisinin çalıştığından emin olun
2. Kullanıcı adı ve şifrenin doğru olduğundan emin olun
3. Host adresini kontrol edin (`localhost` veya IP adresi)
4. Güvenlik duvarı ayarlarını kontrol edin

### Veritabanı Zaten Mevcut

Script, mevcut veritabanını silmek için izin ister. 
Onaylarsanız veritabanı silinir ve yeniden oluşturulur.

### Dosya İzin Hatası

**Çözüm:**
```bash
chmod 644 config/database.php
```

### Schema Dosyası Bulunamadı

**Çözüm:**
1. `database/schema.sql` dosyasının mevcut olduğundan emin olun
2. Script'i doğru klasörden çalıştırın

## 📝 Notlar

- ⚠️ **Veritabanı şifresi** hassas bilgidir, paylaşmayın
- ✅ Script otomatik olarak `config/database.php` dosyasını günceller
- 🔄 Mevcut veritabanını silmek isterseniz script size sorar
- 📊 Script çalıştırıldığında detaylı loglar gösterilir

---

**Son Güncelleme:** Kasım 2025

