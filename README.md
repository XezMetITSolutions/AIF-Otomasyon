# AIF Otomasyon - CRM Sistemi

Modern PHP tabanlı CRM ve kullanıcı yönetim sistemi.

## 🚀 Özellikler

- **Kullanıcı Yönetimi** - Rol bazlı erişim kontrolü
- **BYK Yönetimi** - Organizasyon birimleri
- **Modern UI** - Bootstrap 5 ile responsive tasarım
- **Debug Araçları** - Gelişmiş hata ayıklama
- **Otomatik Deployment** - GitHub Actions ile FTP

## 🛠️ Teknolojiler

- **Backend:** PHP 8.1+, PDO, MySQL
- **Frontend:** Bootstrap 5, jQuery, Font Awesome
- **Deployment:** GitHub Actions, FTP
- **Database:** MySQL/MariaDB

## 📁 Proje Yapısı

```
├── admin/                 # Admin paneli
│   ├── includes/         # PHP sınıfları
│   ├── api/             # API endpoints
│   └── *.php            # Admin sayfaları
├── manager/              # Manager paneli
├── users/                # Kullanıcı paneli
├── .github/workflows/    # GitHub Actions
└── README.md
```

## 🔧 Kurulum

### 1. Repository'yi klonlayın
```bash
git clone https://github.com/yourusername/aif-otomasyon.git
cd aif-otomasyon
```

### 2. Veritabanını ayarlayın
- MySQL veritabanı oluşturun
- `admin/includes/database.php` dosyasında bağlantı bilgilerini güncelleyin

### 3. Dosyaları FTP'ye yükleyin
- Manuel yükleme veya GitHub Actions kullanın

## 🚀 Otomatik Deployment

### GitHub Actions ile FTP Deployment

1. **Repository Secrets Ayarlayın:**
   - `FTP_SERVER` - FTP sunucu adresi
   - `FTP_USERNAME` - FTP kullanıcı adı
   - `FTP_PASSWORD` - FTP şifresi

2. **Deployment Tetikleyin:**
   - `main` branch'e push yapın
   - GitHub Actions otomatik olarak çalışacak

### Manuel Deployment
```bash
# Dosyaları FTP'ye yükle
rsync -avz --exclude='.git' --exclude='node_modules' ./ user@server:/path/to/website/
```

## 🐛 Debug ve Test

### Debug Sayfası
- `admin/debug_users_page.php` - Kullanıcı yönetimi debug
- Gerçek zamanlı test ve analiz

### Test Araçları
- BYK kategori testleri
- Kullanıcı ekleme/güncelleme testleri
- Veritabanı yapısı kontrolü

## 📊 Kullanıcı Rolleri

- **Superadmin** - Tam erişim
- **Manager** - Sınırlı admin erişimi
- **Member** - Temel kullanıcı erişimi

## 🔐 Güvenlik

- Rol bazlı erişim kontrolü
- SQL injection koruması
- XSS koruması
- CSRF token'ları

## 📝 Changelog

### v1.0.0
- İlk sürüm
- Kullanıcı yönetimi
- BYK sistemi
- Modern UI
- Debug araçları

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit yapın (`git commit -m 'Add amazing feature'`)
4. Push yapın (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır.

## 📞 İletişim

- **Proje Sahibi:** AIF Otomasyon
- **Email:** support@aif.com
- **Website:** https://aifcrm.metechnik.at

---

**Not:** Bu sistem sürekli geliştirilmektedir. Yeni özellikler ve iyileştirmeler için GitHub'ı takip edin.

## 🚀 Deployment Status

- ✅ GitHub Actions aktif
- ✅ FTP Secrets ayarlandı
- ✅ Otomatik deployment hazır
- 🔧 BYK sorunu debug ediliyor