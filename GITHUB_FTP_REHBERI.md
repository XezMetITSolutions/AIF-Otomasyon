# GitHub-FTP Entegrasyon Rehberi

## 🚀 GitHub Actions ile Otomatik Deployment

### 1. GitHub Repository Ayarları

#### Secrets Ekleme:
1. **GitHub Repository** → **Settings** → **Secrets and variables** → **Actions**
2. **New repository secret** butonuna tıkla
3. Aşağıdaki secrets'ları ekle:

```
FTP_SERVER: aifcrm.metechnik.at
FTP_USERNAME: d0451622
FTP_PASSWORD: 01528797Mb##
```

### 2. Workflow Dosyası

`.github/workflows/deploy.yml` dosyası oluşturuldu ve şunları yapar:

- ✅ **Push** olduğunda otomatik çalışır
- ✅ **PHP 8.2** kurulumu
- ✅ **Composer** bağımlılıkları
- ✅ **FTP'ye yükleme**
- ✅ **Güvenlik dosyalarını hariç tutma**

### 3. Kurulum Adımları

#### A. GitHub Repository Oluştur:
```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/kullaniciadi/aif-otomasyon.git
git push -u origin main
```

#### B. Secrets Ayarla:
- `FTP_SERVER`: `aifcrm.metechnik.at`
- `FTP_USERNAME`: `d0451622` 
- `FTP_PASSWORD`: `01528797Mb##`

#### C. Test Et:
```bash
# Herhangi bir değişiklik yap ve push et
git add .
git commit -m "Test deployment"
git push
```

### 4. Alternatif Yöntemler

#### A. Webhook Tabanlı:
```php
// webhook.php
<?php
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if ($data['ref'] === 'refs/heads/main') {
    // FTP upload kodu
    exec('git pull origin main');
}
?>
```

#### B. Cron Job:
```bash
# Her 5 dakikada kontrol et
*/5 * * * * cd /path/to/repo && git pull origin main
```

#### C. GitHub App:
- **DeployBot**
- **Netlify**
- **Vercel**

### 5. Güvenlik Önerileri

#### A. Exclude Edilen Dosyalar:
- `.env` dosyaları
- Debug dosyaları
- SQL dosyaları
- Test dosyaları
- `.git` klasörü

#### B. Environment Variables:
```yaml
env:
  FTP_SERVER: ${{ secrets.FTP_SERVER }}
  FTP_USERNAME: ${{ secrets.FTP_USERNAME }}
  FTP_PASSWORD: ${{ secrets.FTP_PASSWORD }}
```

### 6. Troubleshooting

#### A. FTP Bağlantı Hatası:
```yaml
- name: Test FTP Connection
  run: |
    ftp -n ${{ secrets.FTP_SERVER }} <<EOF
    user ${{ secrets.FTP_USERNAME }} ${{ secrets.FTP_PASSWORD }}
    quit
    EOF
```

#### B. Dosya İzinleri:
```yaml
- name: Set Permissions
  run: |
    chmod -R 755 ./
    chmod -R 777 uploads/
    chmod -R 777 logs/
```

### 7. Avantajlar

✅ **Otomatik Deployment**
✅ **Version Control**
✅ **Rollback İmkanı**
✅ **Team Collaboration**
✅ **Backup**
✅ **History Tracking**

### 8. Dezavantajlar

❌ **GitHub Actions Limitleri** (2000 dakika/ay)
❌ **FTP Güvenlik Riski**
❌ **Bağımlılık** (GitHub'a)

## 🎯 Sonuç

GitHub Actions ile FTP entegrasyonu **en pratik çözüm**! Push ettiğinde otomatik olarak web sitesine yüklenir.
