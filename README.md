# 🧭 AIF Otomasyon Sistemi

**Versiyon:** 1.0.1  
**Son Güncelleme:** Kasım 2025

AIF (Automation Information Framework) Otomasyon Sistemi, Bölge Yönetim Kurulları (BYK) için geliştirilmiş kapsamlı bir yönetim ve otomasyon platformudur.

## 📋 İçindekiler

- [Özellikler](#özellikler)
- [Teknoloji Yığını](#teknoloji-yığını)
- [Kurulum](#kurulum)
- [Yapılandırma](#yapılandırma)
- [Kullanım](#kullanım)
- [Yetki Yapısı](#yetki-yapısı)
- [Güvenlik](#güvenlik)
- [GitHub Actions Deployment](#-github-actions-ile-otomatik-deployment)
- [Destek](#destek)

## ✨ Özellikler

### 🔐 Üç Seviyeli Yetki Sistemi

1. **Ana Yönetici** - Tüm sistemin yönetimi
2. **Başkan** - BYK bazlı yönetim
3. **Üye** - Kişisel alan ve işlemler

### 📊 Ana Modüller

- **Üye ve Yetki Yönetimi** - Kullanıcı, rol ve yetki yönetimi
- **BYK Yönetimi** - Bölge Yönetim Kurulları ve alt birimler
- **Takvim & Etkinlik Yönetimi** - Renk kodlu takvim, tekrarlayan etkinlikler
- **Toplantı Yönetimi** - Toplantı planlama, gündem, karar takibi
- **Toplantı Katılım Sistemi** - E-posta davetleri, katılım takibi
- **Proje Takibi** - Durum takibi, sorumlu atama
- **Demirbaş Yönetimi** - Kategori bazlı filtreleme, durum takibi
- **Harcama & İade Sistemi** - Talep onay süreçleri
- **Tatil / İzin Yönetimi (Urlaubsanfrage)** - İzin başvuru ve onay süreçleri
- **Raporlama ve Analiz** - Grafik ve istatistikler
- **Sistem Ayarları** - SMTP, güvenlik, tema ayarları

## 🛠 Teknoloji Yığını

### Backend
- **PHP 8.2+** - Sunucu tarafı programlama
- **MySQL 8.0+** - İlişkisel veritabanı

### Frontend
- **HTML5, CSS3** - Yapısal ve stil
- **JavaScript (ES6+)** - İstemci tarafı programlama
- **Bootstrap 5.3.0** - Responsive CSS framework
- **jQuery 3.7.1** - JavaScript kütüphanesi
- **Chart.js** - Grafik ve istatistik görselleştirme
- **Font Awesome 6.4.0** - İkon kütüphanesi
- **AOS (Animate On Scroll)** - Scroll animasyonları

### Diğer
- **Git, GitHub** - Versiyon kontrolü
- **GitHub Actions** - CI/CD
- **FTP** - Deployment

## 📦 Kurulum

### Gereksinimler

- PHP 8.2 veya üzeri
- MySQL 8.0 veya üzeri
- Apache/Nginx web sunucusu
- Mod_rewrite etkin (Apache için)

### Adımlar

1. **Depoyu klonlayın:**
```bash
git clone https://github.com/XezMetITSolutions/AIF-Otomasyon.git
cd AIF-Otomasyon
```

2. **Veritabanını oluşturun:**
```bash
mysql -u root -p < database/schema.sql
```

3. **Yapılandırma dosyalarını düzenleyin:**
   - `config/database.php` - Veritabanı bağlantı bilgileri
   - `config/app.php` - Uygulama ayarları, SMTP bilgileri

4. **Dosya izinlerini ayarlayın:**
```bash
chmod -R 755 .
chmod -R 777 uploads/  # Dosya yüklemeleri için (varsa)
```

5. **Web sunucusunu yapılandırın:**
   - Apache: `.htaccess` dosyası otomatik olarak dahil edilmiştir
   - Nginx: `nginx.conf` dosyasını web sunucu yapılandırmasına ekleyin

6. **Tarayıcıda açın:**
```
http://localhost/index.php
```

### Varsayılan Giriş Bilgileri

- **E-posta:** admin@aif.org
- **Şifre:** Admin123!
- ⚠️ **Not:** İlk girişte şifre değiştirme zorunludur.

## ⚙️ Yapılandırma

### Veritabanı Yapılandırması

`config/database.php` dosyasını düzenleyin:

```php
return [
    'host' => 'localhost',
    'dbname' => 'aif_otomasyon',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
];
```

### Uygulama Yapılandırması

`config/app.php` dosyasını düzenleyin:

- `app_url` - Uygulama URL'si
- `smtp` - SMTP ayarları (e-posta gönderimi için)
- `security` - Güvenlik ayarları
- `upload` - Dosya yükleme limitleri

## 👥 Yetki Yapısı

### Ana Yönetici
- Tüm modüllere tam erişim
- Tüm BYK'ları yönetebilir
- Sistem ayarlarını değiştirebilir
- Kullanıcı ekleme/düzenleme/silme

### Başkan
- Kendi BYK'sına ait kayıtları yönetir
- Üye ekleme/düzenleme
- Etkinlik ve toplantı oluşturma
- İzin taleplerini onaylama/reddetme
- Raporları görüntüleme

### Üye
- Kendi profilini düzenleme
- İzin başvurusu yapma
- Harcama talebi oluşturma
- Etkinlik ve toplantıları görüntüleme
- Toplantı katılım bildirimi

Detaylı yetki yapısı için [YETKI_YAPISI.md](YETKI_YAPISI.md) dosyasına bakın.

## 🔒 Güvenlik

- **XSS Koruması:** Tüm kullanıcı girdileri `htmlspecialchars()` ile temizlenir
- **SQL Injection Koruması:** PDO prepared statements kullanılır
- **CSRF Koruması:** CSRF token kontrolü yapılır
- **Şifre Güvenliği:** `password_hash()` ve `password_verify()` kullanılır
- **Oturum Yönetimi:** Güvenli oturum yönetimi ve otomatik timeout
- **İlk Giriş Şifre Değiştirme:** Güvenlik için zorunlu

## 📁 Proje Yapısı

```
aif-otomasyon/
├── admin/              # Ana Yönetici paneli
├── baskan/             # Başkan paneli
├── uye/                # Üye paneli
├── api/                # API endpoint'leri
├── assets/             # CSS, JS, images
│   ├── css/
│   └── js/
├── classes/            # PHP sınıfları
│   ├── Auth.php
│   ├── Database.php
│   └── Middleware.php
├── config/             # Yapılandırma dosyaları
│   ├── database.php
│   └── app.php
├── database/           # Veritabanı şemaları
│   └── schema.sql
├── includes/           # Ortak dosyalar
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── init.php
├── index.php          # Giriş sayfası
├── logout.php         # Çıkış sayfası
└── README.md          # Bu dosya
```

## 🚀 Kullanım

### İlk Kurulum Sonrası

1. Giriş yapın (varsayılan admin hesabı ile)
2. Şifrenizi değiştirin (zorunlu)
3. BYK'ları oluşturun
4. Başkanları atayın
5. Üyeleri ekleyin

### Ana Yönetici İşlemleri

- Sistem ayarlarını yapılandırın
- SMTP ayarlarını girin
- Tema ve renk ayarlarını yapın

## 🐛 Sorun Giderme

### Veritabanı Bağlantı Hatası

- `config/database.php` dosyasındaki bilgileri kontrol edin
- MySQL servisinin çalıştığından emin olun
- Kullanıcı adı ve şifrenin doğru olduğundan emin olun

### Dosya İzin Hatası

- Dosya izinlerini kontrol edin: `chmod -R 755 .`
- Web sunucusu kullanıcısının dosyalara erişim izni olduğundan emin olun

### E-posta Gönderim Hatası

- SMTP ayarlarını `config/app.php` içinde kontrol edin
- SMTP sunucusunun erişilebilir olduğundan emin olun
- Güvenlik duvarı ayarlarını kontrol edin

## 🚀 GitHub Actions ile Otomatik Deployment

### Deployment Yapılandırması

Proje, GitHub Actions ile otomatik FTP deployment kullanır. `main` branch'e yapılan her push otomatik olarak FTP sunucusuna deploy edilir.

### GitHub Secrets Yapılandırması

Deployment için aşağıdaki GitHub Secrets tanımlanmalıdır:

1. Repository'ye gidin: **Settings → Secrets and variables → Actions**
2. Aşağıdaki secrets'ları ekleyin:

| Secret Adı | Açıklama | Örnek Değer |
|------------|----------|-------------|
| `FTP_SERVER` | FTP sunucu adresi | `aifcrm.metechnik.at` veya `w01dc0ea.kasserver.com` |
| `FTP_USERNAME` | FTP kullanıcı adı | `d0451622` veya `f017c2cc` |
| `FTP_PASSWORD` | FTP şifresi | (FTP hesabınızın şifresi) |

### Deployment Trigger

- **Otomatik:** `main` veya `master` branch'e push olduğunda
- **Manuel:** GitHub Actions sekmesinden `workflow_dispatch` ile tetiklenebilir

### Deployment Exclude Listesi

Aşağıdaki dosyalar ve klasörler deployment'a dahil edilmez (güvenlik için):

- `.git*` dosyaları ve klasörleri
- `.github/` workflow klasörü
- `node_modules/` klasörü
- `.env` dosyaları
- `README.md` dosyaları
- `composer.json/lock` dosyaları
- `package.json/lock` dosyaları
- SQL schema dosyaları (`database/*.sql`)
- Test ve debug dosyaları
- Güvenlik ve yapılandırma dokümantasyonları

### Deployment Adımları

1. **Checkout code:** Repository'den kod çekilir
2. **List files:** Deployment öncesi dosya listesi gösterilir
3. **Deploy to FTP:** FTP sunucusuna dosyalar yüklenir

### Deployment Monitoring

- Deployment durumu: **GitHub → Actions** sekmesinden takip edilebilir
- Başarılı deployment: ✅ Yeşil işaret
- Başarısız deployment: ❌ Kırmızı işaret (loglar incelenebilir)

### Manuel Deployment

1. GitHub repository'ye gidin
2. **Actions** sekmesine tıklayın
3. Sol menüden **🚀 FTP Deployment** workflow'unu seçin
4. **Run workflow** butonuna tıklayın
5. Branch seçin (`main` veya `master`)
6. **Run workflow** butonuna tıklayın

### Workflow Dosyası

Workflow yapılandırması: `.github/workflows/deploy.yml`

**Özellikler:**
- Ubuntu latest runner
- FTP-Deploy-Action (SamKirkland/FTP-Deploy-Action@4.3.3)
- Güvenli secrets yönetimi
- Detaylı exclude listesi
- Deployment logları

### Alternatif Deployment Yöntemleri

Eğer GitHub Actions kullanmak istemezseniz:

1. **Manuel FTP:** FileZilla gibi FTP client'lar ile
2. **Git pull:** Sunucuda cron job ile otomatik pull
3. **Webhook:** GitHub webhook ile PHP script tetikleme
4. **CI/CD Servisleri:** DeployBot, Netlify, Vercel gibi servisler

## 📞 Destek

Sorularınız ve destek için:
- **E-posta:** support@aif.org
- **GitHub Issues:** [GitHub Repository Issues](https://github.com/XezMetITSolutions/AIF-Otomasyon/issues)
- **Repository:** [https://github.com/XezMetITSolutions/AIF-Otomasyon](https://github.com/XezMetITSolutions/AIF-Otomasyon)

## 📄 Lisans

Bu proje özel bir projedir. Tüm hakları saklıdır.

---

**Not:** Bu dokümantasyon geliştirme aşamasındadır ve güncellenebilir.

