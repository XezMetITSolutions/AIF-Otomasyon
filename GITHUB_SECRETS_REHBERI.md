# GitHub Secrets Ayarlama Rehberi

## 🔐 GitHub Actions Secrets Kurulumu

[AIF-Otomasyon repository](https://github.com/XezMetITSolutions/AIF-Otomasyon) başarıyla oluşturuldu ve tüm dosyalar yüklendi!

### 📋 Sonraki Adımlar:

#### 1. GitHub Repository'ye Git
- [https://github.com/XezMetITSolutions/AIF-Otomasyon](https://github.com/XezMetITSolutions/AIF-Otomasyon)

#### 2. Settings → Secrets and variables → Actions
- Repository sayfasında **"Settings"** sekmesine tıkla
- Sol menüden **"Secrets and variables"** → **"Actions"** seç

#### 3. New repository secret Ekle
Aşağıdaki 3 secret'ı ekle:

```
Name: FTP_SERVER
Value: w01dc0ea.kasserver.com

Name: FTP_USERNAME  
Value: f017c2cc

Name: FTP_PASSWORD
Value: 01528797Mb##
```

### 🚀 Otomatik Deployment Test

#### 1. Herhangi Bir Değişiklik Yap
```bash
# Örnek: README.md'ye bir satır ekle
echo "# Test deployment" >> README.md
```

#### 2. Commit ve Push Et
```bash
git add .
git commit -m "Test: Otomatik deployment testi"
git push
```

#### 3. GitHub Actions Kontrol Et
- Repository'de **"Actions"** sekmesine git
- **"Deploy to FTP"** workflow'unu gör
- **Yeşil tik** = Başarılı deployment

### ✅ Beklenen Sonuç

Push ettiğinde:
1. **GitHub Actions** otomatik çalışır
2. **FTP'ye yükler** (yaklaşık 2-3 dakika)
3. **Web sitesi güncellenir**
4. **Actions** sekmesinde yeşil tik görünür

### 🔍 Troubleshooting

#### Actions Başarısız Olursa:
1. **Actions** sekmesine git
2. **Failed** workflow'a tıkla
3. **Logs** kontrol et
4. **Secrets** doğru mu kontrol et

#### FTP Bağlantı Hatası:
- `FTP_SERVER`: `w01dc0ea.kasserver.com` (doğru mu?)
- `FTP_USERNAME`: `f017c2cc` (doğru mu?)
- `FTP_PASSWORD`: `01528797Mb##` (doğru mu?)

### 🎯 Sonuç

Artık **GitHub'a push ettiğinde otomatik olarak web sitesine yüklenir**!

**Test etmek için:** Herhangi bir dosyayı değiştir ve push et. 2-3 dakika sonra web sitesinde değişikliği göreceksin.
