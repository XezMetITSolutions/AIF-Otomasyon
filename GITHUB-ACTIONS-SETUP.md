# 🔧 GitHub Actions Kurulum Kılavuzu

## ⚠️ GitHub Actions Çalışmıyor Mu?

GitHub Actions'ın çalışması için **GitHub Secrets** tanımlanması gerekiyor!

## 🔐 GitHub Secrets Kurulumu (Zorunlu!)

### Adım 1: GitHub Repository'ye Gidin

1. https://github.com/XezMetITSolutions/AIF-Otomasyon adresine gidin
2. **Settings** sekmesine tıklayın (en üst menüde)
3. Sol menüden **Secrets and variables** → **Actions** seçin

### Adım 2: Secrets Ekleme

**New repository secret** butonuna tıklayın ve şu 3 secret'ı ekleyin:

#### 1. FTP_SERVER
- **Name:** `FTP_SERVER`
- **Secret:** `aifcrm.metechnik.at`
- **Add secret** butonuna tıklayın

#### 2. FTP_USERNAME
- **Name:** `FTP_USERNAME`
- **Secret:** `d0451622`
- **Add secret** butonuna tıklayın

#### 3. FTP_PASSWORD
- **Name:** `FTP_PASSWORD`
- **Secret:** (FTP şifreniz - `01528797Mb##` veya FTP hesabınızın şifresi)
- **Add secret** butonuna tıklayın

## ✅ Secrets Kontrolü

Secrets ekledikten sonra:

1. **Actions** sekmesine gidin
2. Sol menüden **🚀 FTP Deployment** workflow'unu seçin
3. **Run workflow** butonuna tıklayın
4. **Run workflow** ile onaylayın

## 🐛 Sorun Giderme

### ❌ "FTP_SERVER secret tanımlı değil!" Hatası

**Çözüm:**
1. GitHub → Settings → Secrets and variables → Actions
2. `FTP_SERVER` secret'ının var olduğundan emin olun
3. Secret adının **tam olarak** `FTP_SERVER` olduğundan emin olun (büyük/küçük harf önemli!)

### ❌ "FTP connection failed" Hatası

**Çözüm:**
1. `FTP_SERVER` değerinin doğru olduğundan emin olun: `aifcrm.metechnik.at`
2. `FTP_USERNAME` değerinin doğru olduğundan emin olun: `d0451622`
3. `FTP_PASSWORD` değerinin doğru olduğundan emin olun (FTP şifreniz)
4. FTP sunucusunun erişilebilir olduğundan emin olun

### ❌ Workflow Çalışmıyor / Actions Sekmesi Boş

**Çözüm:**
1. Repository'de **Actions** sekmesinin etkin olduğundan emin olun
2. `.github/workflows/deploy.yml` dosyasının mevcut olduğundan emin olun
3. Workflow dosyasının `main` veya `master` branch'inde olduğundan emin olun
4. GitHub repository ayarlarında Actions'ın etkin olduğundan emin olun

### ❌ "No workflow runs" Mesajı

**Çözüm:**
1. Manuel olarak tetiklemeyi deneyin:
   - Actions → 🚀 FTP Deployment → Run workflow
2. `main` branch'e bir push yapın (workflow otomatik tetiklenir)

## 📊 Workflow Durumu Kontrolü

1. **GitHub → Actions** sekmesine gidin
2. **🚀 FTP Deployment** workflow'unu seçin
3. Son çalıştırmayı seçin
4. Adım adım logları kontrol edin:
   - ✅ Yeşil işaret = Başarılı
   - ❌ Kırmızı işaret = Hata (logları inceleyin)

## 🚀 Manuel Tetikleme

Secrets tanımladıktan sonra workflow'u manuel tetikleyebilirsiniz:

1. **GitHub → Actions** sekmesine gidin
2. Sol menüden **🚀 FTP Deployment** workflow'unu seçin
3. Sağ üstteki **Run workflow** butonuna tıklayın
4. **Branch:** `main` seçin
5. **Run workflow** butonuna tıklayın

## 📝 Secrets Yapılandırma Özeti

| Secret Adı | Değer | Açıklama |
|-----------|-------|----------|
| `FTP_SERVER` | `aifcrm.metechnik.at` | FTP sunucu adresi |
| `FTP_USERNAME` | `d0451622` | FTP kullanıcı adı |
| `FTP_PASSWORD` | (FTP şifreniz) | FTP şifresi |

⚠️ **Önemli:** Secret adları **tam olarak** yukarıdaki gibi olmalı (büyük/küçük harf dahil)!

## ✅ Kurulum Kontrol Listesi

- [ ] GitHub repository'ye gittim
- [ ] Settings → Secrets and variables → Actions'a gittim
- [ ] `FTP_SERVER` secret'ını ekledim
- [ ] `FTP_USERNAME` secret'ını ekledim
- [ ] `FTP_PASSWORD` secret'ını ekledim
- [ ] Actions → FTP Deployment → Run workflow ile test ettim

## 🎯 Sonraki Adımlar

1. ✅ Secrets'ları tanımlayın
2. ✅ Workflow'u manuel tetikleyin
3. ✅ Deployment durumunu kontrol edin
4. ✅ Başarılı olursa artık `main` branch'e her push otomatik deploy edilir!

---

**Son Güncelleme:** Kasım 2025

