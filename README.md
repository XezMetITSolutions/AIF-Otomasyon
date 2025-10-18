# AIF Otomasyon Sistemi

Modern, responsive ve güvenli bir otomasyon sistemi. PHP 8.2, Bootstrap 5 ve MySQL ile geliştirilmiştir.

## 🚀 Özellikler

- **Dashboard**: Genel istatistikler ve kontrol paneli
- **Kullanıcı Yönetimi**: BYK bazlı kullanıcı sistemi
- **Takvim**: Etkinlik yönetimi ve görselleştirme
- **Duyurular**: Duyuru yayınlama sistemi
- **Demirbaş**: Envanter yönetimi
- **İade Talepleri**: Para iadesi talepleri
- **Proje Takibi**: Proje yönetimi
- **Rezervasyonlar**: Rezervasyon sistemi
- **Toplantı Raporları**: Rapor yönetimi
- **Raporlar**: Sistem raporları
- **Ayarlar**: Sistem konfigürasyonu
- **Yetki Yönetimi**: Modül bazlı yetkilendirme

## 🏢 BYK Yapısı

- **AT**: Ana Teşkilat (Kırmızı)
- **KT**: Kadınlar Teşkilatı (Mor)
- **KGT**: Kadınlar Gençlik Teşkilatı (Koyu Yeşil)
- **GT**: Gençlik Teşkilatı (Mavi)

Her BYK'nın 17 alt birimi bulunmaktadır.

## 🛠️ Teknolojiler

- **Backend**: PHP 8.2
- **Frontend**: Bootstrap 5, jQuery
- **Veritabanı**: MySQL 8.0+
- **Grafikler**: Chart.js
- **Takvim**: FullCalendar
- **İkonlar**: Font Awesome

## 📦 Kurulum

### 1. Veritabanı Kurulumu

```sql
-- phpMyAdmin'de çalıştır:
-- 1. webhosting_schema.sql
-- 2. webhosting_seed.sql
```

### 2. Konfigürasyon

`admin/config.php` dosyasını düzenleyin:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'd0451622');
define('DB_USER', 'd0451622');
define('DB_PASS', '01528797Mb##');
```

### 3. Giriş Bilgileri

```
Kullanıcı: superadmin
Şifre: admin123
```

## 🚀 Otomatik Deployment

GitHub Actions ile otomatik FTP deployment aktif:

1. **GitHub Secrets** ayarla:
   - `FTP_SERVER`: `aifcrm.metechnik.at`
   - `FTP_USERNAME`: `d0451622`
   - `FTP_PASSWORD`: `01528797Mb##`

2. **Push** ettiğinde otomatik yüklenir

## 📁 Proje Yapısı

```
├── admin/                 # Admin paneli
│   ├── includes/         # Include dosyaları
│   ├── users/           # Kullanıcı paneli
│   └── *.php           # Admin sayfaları
├── .github/workflows/   # GitHub Actions
├── index.php           # Giriş sayfası
└── README.md          # Bu dosya
```

## 🔒 Güvenlik

- **Password Hashing**: bcrypt
- **Session Management**: Güvenli oturum yönetimi
- **CSRF Protection**: Token koruması
- **SQL Injection**: Prepared statements
- **XSS Protection**: Input sanitization

## 📊 Veritabanı

15 ana tablo ile kapsamlı veri yapısı:

- `users` - Kullanıcılar
- `byk_categories` - BYK kategorileri
- `byk_sub_units` - BYK alt birimleri
- `events` - Etkinlikler
- `announcements` - Duyurular
- `inventory` - Demirbaşlar
- `expenses` - İade talepleri
- `projects` - Projeler
- `reservations` - Rezervasyonlar
- `meeting_reports` - Toplantı raporları
- `reports` - Raporlar
- `modules` - Modüller
- `user_permissions` - Kullanıcı yetkileri
- `system_settings` - Sistem ayarları
- `user_sessions` - Oturumlar

## 🎯 Kullanım

1. **Giriş yap**: `https://aifcrm.metechnik.at/`
2. **Dashboard**: Genel bakış
3. **Modüller**: İlgili modüle git
4. **Yönetim**: CRUD işlemleri

## 🤝 Katkıda Bulunma

1. Fork yap
2. Feature branch oluştur (`git checkout -b feature/AmazingFeature`)
3. Commit yap (`git commit -m 'Add some AmazingFeature'`)
4. Push et (`git push origin feature/AmazingFeature`)
5. Pull Request aç

## 📝 Lisans

Bu proje özel kullanım için geliştirilmiştir.

## 📞 İletişim

- **Website**: [https://aifcrm.metechnik.at/](https://aifcrm.metechnik.at/)
- **GitHub**: [https://github.com/XezMetITSolutions/AIF-Otomasyon](https://github.com/XezMetITSolutions/AIF-Otomasyon)

---

**AIF Otomasyon** - Modern, güvenli ve kullanıcı dostu otomasyon sistemi.
