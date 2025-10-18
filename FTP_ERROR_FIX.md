# GitHub Actions FTP Hatası Çözüm Rehberi

## ❌ **Hata Durumu:**
```
deploy
failed 1 minute ago in 4s
1 error
```

## 🔍 **Muhtemel Nedenler:**

### 1. **GitHub Secrets Ayarlanmamış**
- FTP_SERVER eksik
- FTP_USERNAME eksik  
- FTP_PASSWORD eksik

### 2. **FTP Bağlantı Hatası**
- Sunucu adresi yanlış
- Kullanıcı adı yanlış
- Şifre yanlış
- Port sorunu

### 3. **Dosya Yolu Hatası**
- server-dir yanlış
- local-dir yanlış

## ✅ **Çözüm Adımları:**

### **1. GitHub Secrets Kontrolü:**

#### **Repository'ye Git:**
```
https://github.com/XezMetITSolutions/AIF-Otomasyon
```

#### **Settings → Secrets → Actions:**
1. **Settings** sekmesine tıkla
2. **Secrets and variables** → **Actions**
3. **Repository secrets** bölümünde kontrol et

#### **Gerekli Secrets:**
```
FTP_SERVER: w01dc0ea.kasserver.com
FTP_USERNAME: f017c2cc
FTP_PASSWORD: 01528797Mb##
```

### **2. Secrets Ekleme:**

#### **Eğer Secrets Yoksa:**
1. **New repository secret** butonuna tıkla
2. **Name**: `FTP_SERVER`
3. **Secret**: `w01dc0ea.kasserver.com`
4. **Add secret** ile kaydet

#### **Diğer Secrets için tekrarla:**
- `FTP_USERNAME`: `f017c2cc`
- `FTP_PASSWORD`: `01528797Mb##`

### **3. Workflow Test:**

#### **Manuel Test:**
1. **Actions** sekmesine git
2. **Deploy to FTP** workflow'unu bul
3. **Run workflow** butonuna tıkla
4. **main** branch seç
5. **Run workflow** ile başlat

### **4. Log Kontrolü:**

#### **Actions Sekmesinde:**
1. **Son çalıştırma**ya tıkla
2. **deploy** job'una tıkla
3. **Logs** kontrol et
4. **Hata mesajını** oku

#### **Beklenen Başarılı Log:**
```
✅ Checkout code
✅ List files before deploy
✅ Deploy to FTP
✅ Process completed with exit code 0
```

### **5. FTP Bağlantı Testi:**

#### **FTP Bilgileri Kontrolü:**
- **Server**: `w01dc0ea.kasserver.com`
- **Username**: `f017c2cc`
- **Password**: `01528797Mb##`
- **Port**: 21 (varsayılan)

#### **FTP Client ile Test:**
- FileZilla, WinSCP gibi FTP client ile test et
- Bağlantı başarılı mı kontrol et

### **6. Alternatif Çözümler:**

#### **A. Farklı FTP Action Kullan:**
```yaml
- name: Deploy to FTP
  uses: SamKirkland/FTP-Deploy-Action@4.3.4
```

#### **B. FTP Port Belirt:**
```yaml
with:
  server: ${{ secrets.FTP_SERVER }}
  username: ${{ secrets.FTP_USERNAME }}
  password: ${{ secrets.FTP_PASSWORD }}
  port: 21
```

#### **C. Passive Mode:**
```yaml
with:
  server: ${{ secrets.FTP_SERVER }}
  username: ${{ secrets.FTP_USERNAME }}
  password: ${{ secrets.FTP_PASSWORD }}
  passive: true
```

## 🚨 **Acil Çözüm:**

### **1. Secrets Kontrol Et:**
- GitHub Repository → Settings → Secrets → Actions
- 3 secret var mı kontrol et

### **2. Manuel Test:**
- Actions → Deploy to FTP → Run workflow
- Logs kontrol et

### **3. FTP Test:**
- FTP client ile bağlantı test et
- Bilgiler doğru mu kontrol et

## 🎯 **Sonuç:**

**En muhtemel neden: GitHub Secrets ayarlanmamış.**

**Çözüm: Secrets'ları ekle ve tekrar test et.**
