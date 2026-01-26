# 🚀 Deployment Kılavuzu

**AIF Otomasyon Sistemi - Otomatik FTP Deployment**

## 📋 İçindekiler

- [GitHub Actions Deployment](#github-actions-deployment)
- [GitHub Secrets Yapılandırması](#github-secrets-yapılandırması)
- [Manuel Deployment](#manuel-deployment)
- [Sorun Giderme](#sorun-giderme)
- [Alternatif Yöntemler](#alternatif-yöntemler)

---

## 🔄 GitHub Actions Deployment

### Otomatik Deployment

Proje, GitHub Actions ile otomatik FTP deployment kullanır.

**Trigger:** `main` veya `master` branch'e push yapıldığında otomatik çalışır.

### Workflow Yapılandırması

**Dosya:** `.github/workflows/deploy.yml`

**Özellikler:**
- ✅ Ubuntu latest runner
- ✅ Checkout action ile kod çekme
- ✅ FTP-Deploy-Action kullanımı
- ✅ Güvenli secrets yönetimi
- ✅ Kapsamlı exclude listesi
- ✅ Deployment logları ve monitoring

### Deployment Adımları

1. **📥 Checkout Repository**
   - Repository'den kod çekilir
   - Tüm commit geçmişi alınır

2. **📋 List Files**
   - Deployment öncesi dosya listesi gösterilir
   - Toplam dosya sayısı kontrol edilir

3. **🔐 Setup FTP Secrets**
   - GitHub Secrets kontrolü yapılır
   - FTP bilgileri doğrulanır

4. **📤 Deploy to FTP**
   - Dosyalar FTP sunucusuna yüklenir
   - Exclude listesine göre filtrelenir

---

## 🔐 GitHub Secrets Yapılandırması

### Secrets Ekleme

1. GitHub repository'ye gidin
2. **Settings** sekmesine tıklayın
3. Sol menüden **Secrets and variables → Actions** seçin
4. **New repository secret** butonuna tıklayın

### Gerekli Secrets

| Secret Adı | Açıklama | Örnek Değer | Zorunlu |
|-----------|----------|-------------|---------|
| `FTP_SERVER` | FTP sunucu adresi | `aifcrm.metechnik.at` veya `w01dc0ea.kasserver.com` | ✅ |
| `FTP_USERNAME` | FTP kullanıcı adı | `d0451622` veya `f017c2cc` | ✅ |
| `FTP_PASSWORD` | FTP şifresi | (FTP hesabınızın şifresi) | ✅ |

### Secrets Güvenliği

- ✅ Secrets şifrelenmiş şekilde saklanır
- ✅ Sadece workflow'lar tarafından erişilebilir
- ✅ Repository loglarında görünmez
- ✅ Sadece repository yöneticileri ekleyebilir/değiştirebilir

---

## 🚫 Deployment Exclude Listesi

Aşağıdaki dosya ve klasörler **güvenlik** nedeniyle deployment'a dahil edilmez:

### Git ve Versiyon Kontrol
- `.git*` dosyaları ve klasörleri
- `.github/` workflow klasörü (workflow dosyası hariç)

### Bağımlılıklar ve Paketler
- `node_modules/` klasörü
- `vendor/` klasörü (Composer)
- `package.json`, `package-lock.json`
- `composer.json`, `composer.lock`

### Yapılandırma ve Güvenlik
- `.env` dosyaları ve `.env.*` pattern'leri
- `config/database.local.php`
- `config/app.local.php`
- `.htpasswd` dosyaları

### Dokümantasyon ve Test
- `README.md` dosyaları
- `README*.md` pattern'leri
- `KONTROL_LISTESI.md`
- `tests/`, `test/` klasörleri
- `*_test.php`, `*.test.php` dosyaları
- `.phpunit.xml`, `phpunit.xml.dist`

### Veritabanı ve Yedekler
- `database/*.sql` dosyaları
- `database/*.sql.gz` dosyaları
- `database/*.sql.bak` dosyaları
- `database/schema.sql`
- `backups/` klasörü

### Log ve Geçici Dosyalar
- `logs/` klasörü
- `*.log` dosyaları
- `*.tmp`, `*.temp` dosyaları
- `.cache/` klasörü

### IDE ve Editör
- `.vscode/`, `.idea/` klasörleri
- `.editorconfig`
- `.eslintrc*`, `.prettierrc*`

### Docker ve Container
- `docker-compose.yml`
- `Dockerfile`
- `.dockerignore`

---

## 📤 Manuel Deployment

### GitHub Actions Üzerinden

1. GitHub repository'ye gidin
2. **Actions** sekmesine tıklayın
3. Sol menüden **🚀 FTP Deployment** workflow'unu seçin
4. Sağ üstteki **Run workflow** butonuna tıklayın
5. Branch seçin (`main` veya `master`)
6. **Run workflow** butonuna tıklayın

### FTP Client ile Manuel Yükleme

1. **FileZilla** veya başka bir FTP client açın
2. FTP bilgilerini girin:
   - Host: `aifcrm.metechnik.at` veya `w01dc0ea.kasserver.com`
   - Username: `d0451622` veya `f017c2cc`
   - Password: (FTP şifreniz)
3. Bağlanın
4. Proje dosyalarını yükleyin
5. ⚠️ **Dikkat:** Exclude listesindeki dosyaları yüklemeyin!

---

## 📊 Deployment Monitoring

### GitHub Actions Logları

1. **GitHub → Actions** sekmesine gidin
2. İlgili workflow çalıştırmasını seçin
3. Adım adım logları inceleyin

### Deployment Durumları

- ✅ **Success (Başarılı):** Yeşil işaret - Dosyalar başarıyla yüklendi
- ❌ **Failure (Başarısız):** Kırmızı işaret - Hata oluştu, logları kontrol edin
- 🟡 **In Progress (Devam Ediyor):** Sarı işaret - Deployment devam ediyor

### Deployment Bilgileri

Her başarılı deployment'da şu bilgiler gösterilir:
- 🌐 FTP Server adresi
- 📅 Deployment tarihi ve saati
- 🔄 Commit SHA
- 👤 Deployment yapan kişi

---

## 🐛 Sorun Giderme

### FTP Bağlantı Hatası

**Hata:** `FTP connection failed`

**Çözüm:**
1. GitHub Secrets'daki `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` değerlerini kontrol edin
2. FTP sunucusunun erişilebilir olduğundan emin olun
3. Güvenlik duvarı ayarlarını kontrol edin
4. FTP port'unun açık olduğundan emin olun (genellikle 21)

### Secrets Bulunamadı Hatası

**Hata:** `FTP_SERVER secret tanımlı değil!`

**Çözüm:**
1. GitHub repository → Settings → Secrets and variables → Actions
2. Gerekli secrets'ları ekleyin (`FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`)
3. Secret adlarının tam olarak eşleştiğinden emin olun

### Dosya Yükleme Hatası

**Hata:** `Failed to upload files`

**Çözüm:**
1. FTP kullanıcısının yazma izni olduğundan emin olun
2. Disk alanının yeterli olduğundan emin olun
3. Dosya izinlerini kontrol edin
4. Workflow loglarını detaylı inceleyin

### Deployment Çalışmıyor

**Sorun:** Push yaptım ama deployment başlamadı

**Çözüm:**
1. `main` veya `master` branch'e push yaptığınızdan emin olun
2. `.github/workflows/deploy.yml` dosyasının mevcut olduğunu kontrol edin
3. GitHub Actions'ın repository'de etkin olduğundan emin olun
4. Manuel olarak workflow'u tetiklemeyi deneyin

---

## 🔄 Alternatif Yöntemler

### 1. Git Pull ile Otomatik Deployment

Sunucuda cron job ile otomatik pull:

```bash
# Crontab'a ekleyin (her 5 dakikada bir)
*/5 * * * * cd /path/to/project && git pull origin main
```

### 2. Webhook Tabanlı Deployment

GitHub webhook ile PHP script tetikleme:

```php
<?php
// deploy.php
$payload = json_decode(file_get_contents('php://input'), true);
if ($payload['ref'] === 'refs/heads/main') {
    exec('cd /path/to/project && git pull origin main');
}
?>
```

### 3. CI/CD Servisleri

- **DeployBot:** GitHub entegrasyonu ile otomatik deployment
- **Netlify:** Static site hosting ve CI/CD
- **Vercel:** Frontend deployment için
- **CircleCI:** Kapsamlı CI/CD pipeline

---

## 📝 Deployment Checklist

Deployment öncesi kontrol listesi:

- [ ] GitHub Secrets tanımlı ve doğru
- [ ] FTP sunucusu erişilebilir
- [ ] Exclude listesi güncel
- [ ] Production yapılandırması hazır
- [ ] Veritabanı yedekleri alındı
- [ ] Test ortamında test edildi
- [ ] Rollback planı hazır

---

## 🔒 Güvenlik Notları

1. ⚠️ **Asla** `.env` dosyalarını deployment'a dahil etmeyin
2. ⚠️ **Asla** `database/schema.sql` gibi hassas dosyaları yüklemeyin
3. ⚠️ **Asla** GitHub Secrets'ları repository'ye commit etmeyin
4. ✅ Production yapılandırmasını sunucuda manuel yapın
5. ✅ FTP şifrelerini düzenli olarak değiştirin
6. ✅ Deployment sonrası dosya izinlerini kontrol edin

---

**Son Güncelleme:** Kasım 2025  
**Versiyon:** 1.0.1

