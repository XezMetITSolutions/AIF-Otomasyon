# 📤 FTP Manuel Yükleme Kılavuzu

## 🚀 Hızlı Başlangıç

### Seçenek 1: GitHub Actions ile Otomatik Deployment (Önerilen)

1. **GitHub Repository'ye gidin:** https://github.com/XezMetITSolutions/AIF-Otomasyon
2. **Actions** sekmesine tıklayın
3. Sol menüden **🚀 FTP Deployment** workflow'unu seçin
4. Sağ üstteki **Run workflow** butonuna tıklayın
5. **Run workflow** ile onaylayın

⚠️ **Önce GitHub Secrets tanımlamanız gerekir:**
- `FTP_SERVER`: `aifcrm.metechnik.at` veya `w01dc0ea.kasserver.com`
- `FTP_USERNAME`: `d0451622` veya `f017c2cc`
- `FTP_PASSWORD`: (FTP şifreniz)

### Seçenek 2: FileZilla ile Manuel Yükleme

1. **FileZilla'yı açın** (https://filezilla-project.org/download.php)
2. **Hızlı bağlantı** kısmına bilgileri girin:
   - **Host:** `aifcrm.metechnik.at` veya `w01dc0ea.kasserver.com`
   - **Username:** `d0451622` veya `f017c2cc`
   - **Password:** (FTP şifreniz)
   - **Port:** `21`
3. **Hızlı Bağlan** butonuna tıklayın
4. Sol tarafta **yerel klasörünüzü** açın: `C:\Users\IT Admin\Documents\Otomasyon`
5. Sağ tarafta **FTP sunucu klasörünü** açın (genellikle `/` veya `/public_html`)
6. **Yüklemek istemediğiniz dosyaları atlayın:**
   - `.git` klasörü
   - `.github` klasörü
   - `README.md`, `DEPLOYMENT.md`, `KONTROL_LISTESI.md`
   - `database/schema.sql`
   - `.env` dosyaları
7. Kalan tüm dosyaları seçin ve **sağ taraftaki klasöre sürükleyin**

## 📋 Yüklenecek Dosyalar Listesi

### ✅ Mutlaka Yüklenmesi Gerekenler:

```
✅ admin/
✅ api/
✅ assets/
✅ baskan/
✅ classes/
✅ config/
✅ includes/
✅ uye/
✅ .htaccess
✅ access-denied.php
✅ change-password.php
✅ index.php
✅ logout.php
```

### ❌ Yüklenmemesi Gerekenler:

```
❌ .git/
❌ .github/
❌ node_modules/
❌ database/schema.sql
❌ README.md
❌ DEPLOYMENT.md
❌ KONTROL_LISTESI.md
❌ .env dosyaları
❌ .gitignore
```

## 🔧 FileZilla Yükleme Adımları

### 1. FileZilla İndirme ve Kurulum

1. https://filezilla-project.org/download.php?type=client adresine gidin
2. Windows için **FileZilla Client** indirin
3. Kurulumu tamamlayın

### 2. FTP Bağlantısı

1. FileZilla'yı açın
2. Üst kısımdaki **Hızlı Bağlantı** alanına bilgileri girin:
   - **Sunucu:** `aifcrm.metechnik.at`
   - **Kullanıcı adı:** `d0451622`
   - **Şifre:** (FTP şifreniz)
   - **Port:** `21`
3. **Hızlı Bağlan** butonuna tıklayın

### 3. Dosya Yükleme

1. **Sol panel** (Yerel site): Yerel bilgisayarınızdaki dosyalar
   - `C:\Users\IT Admin\Documents\Otomasyon` klasörünü açın

2. **Sağ panel** (Uzak site): FTP sunucusundaki dosyalar
   - Ana dizini açın (genellikle `/` veya `/public_html` veya `/htdocs`)

3. **Dosya seçimi:**
   - Sol panelden yüklemek istediğiniz dosyaları seçin
   - **Ctrl+A** ile tümünü seçebilirsiniz
   - Sonra `.git`, `.github`, `README.md` gibi dosyaları seçimi kaldırın (Ctrl tuşuna basılı tutarak)

4. **Yükleme:**
   - Seçili dosyaları **sağ panele sürükleyin**
   - Veya sağ tıklayıp **Yükle** seçeneğini seçin

### 4. Yükleme İlerlemesi

- Alt kısımdaki **Başarılı Aktarımlar** sekmesinde ilerlemeyi görebilirsiniz
- Hata olursa **Başarısız Aktarımlar** sekmesinde görünür

## 🛠️ Alternatif: WinSCP Kullanımı

1. **WinSCP'yi indirin:** https://winscp.net/eng/download.php
2. **Yeni site** oluşturun:
   - **File protocol:** FTP
   - **Host name:** `aifcrm.metechnik.at`
   - **Port number:** `21`
   - **User name:** `d0451622`
   - **Password:** (FTP şifreniz)
3. **Kaydet** ve **Oturum Aç**
4. Sol tarafta yerel klasörünüzü, sağ tarafta FTP klasörünü açın
5. Dosyaları sürükleyip bırakın

## ⚠️ Önemli Notlar

1. **İlk Yükleme:** Tüm dosyaları yükleyin (exclude listesindekiler hariç)

2. **Güncelleme Yükleme:** Sadece değişen dosyaları yükleyin

3. **Dosya İzinleri:** Yükleme sonrası kontrol edin:
   - PHP dosyaları: `644` veya `755`
   - Klasörler: `755`
   - `.htaccess`: `644`

4. **Yapılandırma:** Yükleme sonrası sunucuda şu dosyaları düzenleyin:
   - `config/database.php` - Veritabanı bilgileri
   - `config/app.php` - SMTP ve diğer ayarlar

5. **Güvenlik:** Sunucuda asla şu dosyaları yüklemeyin:
   - `.env` dosyaları
   - `database/schema.sql`
   - `.git` klasörü

## 🔍 Sorun Giderme

### Bağlantı Hatası

- FTP sunucu adresini kontrol edin
- Port 21'in açık olduğundan emin olun
- Kullanıcı adı ve şifreyi doğrulayın
- Güvenlik duvarı ayarlarını kontrol edin

### Yükleme Hatası

- Dosya izinlerini kontrol edin
- Disk alanının yeterli olduğundan emin olun
- Dosya adlarında özel karakter olmamasına dikkat edin

### Dosya Çalışmıyor

- PHP versiyonunu kontrol edin (8.2+ gerekli)
- Dosya izinlerini kontrol edin
- `.htaccess` dosyasının yüklendiğinden emin olun

---

**En Kolay Yöntem:** GitHub Actions ile otomatik deployment kullanın! 🚀
Sadece GitHub Secrets'ları tanımlayın ve `main` branch'e push yaptığınızda otomatik deploy edilir.

