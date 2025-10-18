# GitHub Actions Troubleshooting Rehberi

## 🔧 GitHub Actions Hatası Çözüldü

### ❌ **Hata:**
```
Composer could not find a composer.json file
Error: Process completed with exit code 1
```

### ✅ **Çözüm:**
- **Composer kurulum adımı kaldırıldı**
- **PHP setup adımı kaldırıldı** (gerekli değil)
- **Sadece FTP deployment kaldı**

### 📋 **Güncellenen Workflow:**

#### **Önceki (Hatalı):**
```yaml
- name: Setup PHP
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.2'
    
- name: Install dependencies
  run: |
    composer install --no-dev --optimize-autoloader
```

#### **Yeni (Düzeltilmiş):**
```yaml
- name: Checkout code
  uses: actions/checkout@v3
  
- name: Deploy to FTP
  uses: SamKirkland/FTP-Deploy-Action@4.3.3
```

### 🔐 **GitHub Secrets Kontrolü:**

#### **Gerekli Secrets:**
```
FTP_SERVER: w01dc0ea.kasserver.com
FTP_USERNAME: f017c2cc
FTP_PASSWORD: 01528797Mb##
```

#### **Secrets Ayarlama:**
1. **GitHub Repository** → **Settings**
2. **Secrets and variables** → **Actions**
3. **New repository secret** ile ekle

### 🚀 **Test Deployment:**

#### **Manuel Test:**
1. **GitHub Repository** → **Actions**
2. **Deploy to FTP** workflow'unu bul
3. **Run workflow** butonuna tıkla
4. **main** branch seç
5. **Run workflow** ile başlat

#### **Otomatik Test:**
- Herhangi bir dosyayı değiştir
- Commit ve push et
- Actions otomatik çalışacak

### 📊 **Beklenen Sonuç:**

#### **Başarılı Deployment:**
```
✅ Checkout code
✅ Deploy to FTP
✅ Process completed with exit code 0
```

#### **FTP'ye Yüklenen Dosyalar:**
- ✅ `index.php`
- ✅ `admin/` klasörü
- ✅ `admin/users/` klasörü
- ✅ Tüm PHP dosyaları
- ✅ CSS ve JS dosyaları

### 🔍 **Troubleshooting:**

#### **Hala Hata Alıyorsan:**
1. **Secrets** doğru mu kontrol et
2. **FTP bilgileri** doğru mu kontrol et
3. **Actions** sekmesinde **logs** kontrol et
4. **FTP sunucusu** erişilebilir mi kontrol et

#### **FTP Bağlantı Hatası:**
- `FTP_SERVER`: `w01dc0ea.kasserver.com`
- `FTP_USERNAME`: `f017c2cc`
- `FTP_PASSWORD`: `01528797Mb##`

### 🎯 **Sonuç:**

**GitHub Actions artık çalışacak ve FTP'ye otomatik deployment yapacak!**

**Test etmek için:** Herhangi bir dosyayı değiştir ve push et. 2-3 dakika sonra web sitesinde değişikliği göreceksin.
