# 📤 FTP Upload Scripts

Bu klasörde manuel FTP yükleme için script'ler bulunmaktadır.

## 🚀 Kullanım

### PowerShell Script (Önerilen)

1. **PowerShell'i Yönetici olarak açın**

2. **Script'i çalıştırın:**
   ```powershell
   cd "C:\Users\IT Admin\Documents\Otomasyon"
   .\scripts\ftp-upload.ps1
   ```

3. **FTP şifresini girin** (güvenli şekilde)

### Batch Script (Kolay)

1. **Çift tıklayarak çalıştırın:**
   - `scripts\ftp-upload.bat`

## ⚙️ Yapılandırma

`ftp-upload.ps1` dosyasını açın ve FTP bilgilerini düzenleyin:

```powershell
$FTP_SERVER = "aifcrm.metechnik.at"  # veya "w01dc0ea.kasserver.com"
$FTP_USERNAME = "d0451622"  # veya "f017c2cc"
```

## 📋 Exclude Listesi

Aşağıdaki dosya ve klasörler otomatik olarak atlanır:

- `.git*` dosyaları
- `.github/` klasörü
- `node_modules/` klasörü
- `.env` dosyaları
- `README.md` dosyaları
- `database/*.sql` dosyaları
- Log dosyaları
- IDE ayarları

## 🔒 Güvenlik

- FTP şifresi güvenli şekilde sorulur (görünmez)
- Şifreler script'te saklanmaz
- Hassas dosyalar exclude edilir

## 📝 Notlar

- İlk yükleme biraz zaman alabilir
- Büyük dosyalar için sabırlı olun
- Hata durumunda logları kontrol edin

---

**Alternatif:** GitHub Actions ile otomatik deployment kullanabilirsiniz.
GitHub Secrets'ları tanımlayın ve `main` branch'e push yaptığınızda otomatik deploy edilir.

