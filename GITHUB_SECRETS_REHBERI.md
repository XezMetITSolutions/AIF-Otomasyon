# GitHub Actions FTP Deployment Kurulum Rehberi

## 🔐 GitHub Secrets Ayarlama

GitHub repository'nizde şu secrets'ları ayarlamanız gerekiyor:

### 1. Repository'ye Git
- https://github.com/XezMetITSolutions/AIF-Otomasyon
- **Settings** sekmesine tıklayın
- Sol menüden **Secrets and variables** > **Actions** seçin

### 2. Secrets Ekleme
**New repository secret** butonuna tıklayın ve şu secrets'ları ekleyin:

#### FTP_SERVER
```
Name: FTP_SERVER
Value: w01dc0ea.kasserver.com
```

#### FTP_USERNAME
```
Name: FTP_USERNAME
Value: f017c2cc
```

#### FTP_PASSWORD
```
Name: FTP_PASSWORD
Value: 01528797Mb##
```

## 🚀 Deployment Test Etme

### 1. Otomatik Deployment
- Herhangi bir dosyada değişiklik yapın
- Commit ve push yapın
- GitHub Actions otomatik olarak çalışacak

### 2. Manuel Deployment
- GitHub repository'de **Actions** sekmesine gidin
- **Deploy to FTP Server** workflow'unu seçin
- **Run workflow** butonuna tıklayın

## 📊 Deployment Durumu

### Başarılı Deployment
- ✅ Yeşil tik işareti
- ✅ "Deploy to FTP" adımı başarılı
- ✅ Dosyalar sunucuya yüklendi

### Hatalı Deployment
- ❌ Kırmızı X işareti
- ❌ Hata mesajları görünür
- ❌ Secrets kontrolü yapın

## 🔧 Sorun Giderme

### 1. FTP Bağlantı Hatası
```
Error: FTP connection failed
```
**Çözüm:** FTP_SERVER, FTP_USERNAME, FTP_PASSWORD secrets'larını kontrol edin

### 2. Dosya Yükleme Hatası
```
Error: Failed to upload files
```
**Çözüm:** FTP kullanıcısının yazma yetkisi olduğundan emin olun

### 3. Workflow Çalışmıyor
```
No workflows found
```
**Çözüm:** `.github/workflows/deploy.yml` dosyasının repository'de olduğundan emin olun

## 📝 Deployment Logları

GitHub Actions'da deployment loglarını görmek için:

1. **Actions** sekmesine gidin
2. Son deployment'ı seçin
3. **Deploy to FTP** adımını genişletin
4. Detaylı logları görün

## 🎯 BYK Sorunu Test Etme

Deployment tamamlandıktan sonra:

1. https://aifcrm.metechnik.at/admin/users.php sayfasına gidin
2. Yeni kullanıcı ekleyin ve BYK seçin
3. Debug loglarını kontrol edin
4. BYK'nin kaydedilip kaydedilmediğini test edin

## 📞 Destek

Sorun yaşarsanız:
- GitHub Issues açın
- Deployment loglarını paylaşın
- Hata mesajlarını ekleyin

---

**Not:** Bu rehber GitHub Actions ile otomatik FTP deployment için hazırlanmıştır.
