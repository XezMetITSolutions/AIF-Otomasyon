# AIF Otomasyon - Web Hosting Kurulum Rehberi

## 📋 Kurulum Adımları

### 1. Dosyaları Web Hosting'e Yükleme

1. **FTP ile bağlanın** (FileZilla, WinSCP vb.)
2. **Tüm dosyaları** `public_html` veya `www` klasörüne yükleyin
3. **Dosya izinlerini** ayarlayın:
   - Klasörler: `755`
   - PHP dosyaları: `644`
   - `uploads/` ve `logs/` klasörleri: `777`

### 2. Veritabanı Oluşturma

#### cPanel'de:
1. **MySQL Databases** bölümüne gidin
2. **Yeni veritabanı** oluşturun: `aif_otomasyon`
3. **Veritabanı kullanıcısı** oluşturun
4. **Kullanıcıyı veritabanına** bağlayın
5. **Tüm yetkileri** verin

#### phpMyAdmin'de:
1. **phpMyAdmin** açın
2. **SQL** sekmesine gidin
3. `webhosting_schema.sql` dosyasının içeriğini yapıştırın
4. **Çalıştır** butonuna tıklayın
5. `webhosting_seed.sql` dosyasının içeriğini yapıştırın
6. **Çalıştır** butonuna tıklayın

### 3. Konfigürasyon Ayarları

`admin/config.php` dosyasını düzenleyin:

```php
// Veritabanı Ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'd0451622'); // Hosting'deki veritabanı adı
define('DB_USER', 'd0451622'); // Hosting'deki kullanıcı adı
define('DB_PASS', '01528797Mb##'); // Hosting'deki şifre

// Site Ayarları
define('SITE_URL', 'https://aifcrm.metechnik.at'); // Gerçek domain
define('DEBUG_MODE', false); // Production'da false
```

### 4. Güvenlik Ayarları

#### .htaccess Dosyası Oluşturun:
```apache
# AIF Otomasyon .htaccess
RewriteEngine On

# PHP hatalarını gizle
php_flag display_errors Off
php_flag log_errors On

# Dosya yükleme limiti
php_value upload_max_filesize 10M
php_value post_max_size 10M

# Güvenlik başlıkları
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# Sensitive dosyaları koru
<Files "config.php">
    Order Allow,Deny
    Deny from all
</Files>

<Files "database_schema.sql">
    Order Allow,Deny
    Deny from all
</Files>

<Files "database_seed.sql">
    Order Allow,Deny
    Deny from all
</Files>

# Klasör listesini gizle
Options -Indexes

# Cache ayarları
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>
```

### 5. E-posta Ayarları (SMTP)

`admin/config.php` dosyasında SMTP ayarlarını güncelleyin:

```php
// E-posta Ayarları
define('SMTP_HOST', 'mail.yourdomain.com'); // Hosting SMTP sunucusu
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@yourdomain.com');
define('SMTP_PASSWORD', 'email_sifresi');
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'AIF Otomasyon');
```

### 6. SSL Sertifikası

- **Let's Encrypt** ücretsiz SSL sertifikası kurun
- **HTTPS** yönlendirmesi yapın
- **Mixed Content** uyarılarını kontrol edin

### 7. Yedekleme Ayarları

#### Otomatik Yedekleme:
```php
// config.php'de
define('AUTO_BACKUP', true);
define('BACKUP_FREQUENCY', 'daily'); // daily, weekly, monthly
define('BACKUP_TIME', '02:00');
```

#### Manuel Yedekleme:
- **cPanel Backup** kullanın
- **phpMyAdmin Export** ile veritabanı yedekleyin
- **FTP ile dosya yedekleme** yapın

### 8. Performans Optimizasyonu

#### PHP Ayarları:
```ini
# php.ini
memory_limit = 256M
max_execution_time = 300
max_input_vars = 3000
post_max_size = 10M
upload_max_filesize = 10M
```

#### MySQL Ayarları:
```sql
# my.cnf
innodb_buffer_pool_size = 256M
query_cache_size = 64M
tmp_table_size = 64M
max_heap_table_size = 64M
```

### 9. Test ve Doğrulama

#### Sistem Testleri:
1. **Giriş yapma** testi
2. **Kullanıcı ekleme** testi
3. **Etkinlik ekleme** testi
4. **Dosya yükleme** testi
5. **E-posta gönderme** testi

#### Güvenlik Testleri:
1. **SQL Injection** testi
2. **XSS** testi
3. **CSRF** testi
4. **File Upload** testi

### 10. Monitoring ve Loglama

#### Log Dosyaları:
- **Error Log**: `logs/error.log`
- **Access Log**: `logs/access.log`
- **User Activity**: `logs/user_activity.log`

#### Monitoring:
- **Uptime monitoring** kurun
- **Performance monitoring** yapın
- **Security scanning** çalıştırın

## 🔧 Sorun Giderme

### Yaygın Sorunlar:

#### 1. Veritabanı Bağlantı Hatası:
```php
// Hata: Connection failed
// Çözüm: config.php'deki veritabanı bilgilerini kontrol edin
```

#### 2. Dosya Yükleme Hatası:
```php
// Hata: Upload failed
// Çözüm: uploads/ klasörü izinlerini 777 yapın
```

#### 3. Session Hatası:
```php
// Hata: Session start failed
// Çözüm: session.save_path ayarını kontrol edin
```

#### 4. Memory Limit Hatası:
```php
// Hata: Memory limit exceeded
// Çözüm: php.ini'de memory_limit artırın
```

## 📞 Destek

### Teknik Destek:
- **E-posta**: support@aif.com
- **Telefon**: +43 XXX XXX XXX
- **Dokümantasyon**: https://docs.aif.com

### Güncellemeler:
- **GitHub**: https://github.com/aif/otomasyon
- **Changelog**: https://github.com/aif/otomasyon/releases

## 🚀 Go Live Checklist

- [ ] Dosyalar yüklendi
- [ ] Veritabanı oluşturuldu
- [ ] Konfigürasyon ayarlandı
- [ ] SSL sertifikası kuruldu
- [ ] E-posta ayarları yapıldı
- [ ] Güvenlik ayarları yapıldı
- [ ] Yedekleme ayarlandı
- [ ] Testler yapıldı
- [ ] Monitoring kuruldu
- [ ] Dokümantasyon hazırlandı

## 📊 Sistem Gereksinimleri

### Minimum Gereksinimler:
- **PHP**: 8.0+
- **MySQL**: 5.7+
- **Apache**: 2.4+
- **RAM**: 512MB
- **Disk**: 1GB

### Önerilen Gereksinimler:
- **PHP**: 8.2+
- **MySQL**: 8.0+
- **Apache**: 2.4+
- **RAM**: 2GB+
- **Disk**: 5GB+

## 🔐 Güvenlik Önerileri

1. **Güçlü şifreler** kullanın
2. **2FA** aktif edin
3. **Düzenli güncellemeler** yapın
4. **Yedekleme** alın
5. **Logları** kontrol edin
6. **Firewall** kullanın
7. **DDoS koruması** aktif edin

---

**Not**: Bu rehber genel bir kılavuzdur. Hosting sağlayıcınızın özel ayarları olabilir.
