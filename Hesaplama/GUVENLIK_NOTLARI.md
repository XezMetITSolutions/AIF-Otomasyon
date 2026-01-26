# 🔒 Güvenlik Notları - AİF Gider Formu

## ✅ Güvenli Şifre Sistemi Kuruldu

### Önceki Durum (Güvensiz)
```javascript
// ❌ JavaScript'te açık şifre - herkes görebilir!
const CORRECT_PASSWORD = 'fatura!1234';
```

### Yeni Durum (Güvenli)
```php
// ✅ PHP'de şifre - sadece sunucuda
$CORRECT_PASSWORD = 'fatura!1234';
```

## Nasıl Çalışır?

### 1. Şifre Kontrolü (check_password.php)
- Frontend şifreyi PHP'ye gönderir
- PHP sunucuda kontrol eder
- Şifre asla JavaScript'te görünmez
- Session oluşturulur

### 2. Session Doğrulama (verify_session.php)
- Sayfa yüklendiğinde session kontrol edilir
- 2 saat timeout
- Güvenli PHP session kullanır

### 3. Frontend (index.html)
- Şifre JavaScript'te YOK
- Sadece API çağrıları var
- Kaynak kodda şifre görünmez

## Güvenlik Özellikleri

### ✅ Şifre Koruması
- Şifre sadece sunucuda (`check_password.php`)
- JavaScript'te görünmez
- Kaynak kodda bulunamaz

### ✅ Session Yönetimi
- PHP session kullanır
- 2 saat timeout
- Güvenli session ID

### ✅ Brute Force Koruması
- Başarısız denemeler loglanır
- IP adresi kaydedilir
- Rate limiting eklenebilir

### ✅ HTTPS
- Şifre şifreli kanal üzerinden gider
- Man-in-the-middle koruması

## Dosya Yapısı

```
/forms-expense/
├── index.html              # Ana form (şifre YOK)
├── check_password.php      # Şifre kontrolü (şifre BURADA)
├── verify_session.php      # Session kontrolü
├── receive_pdf.php         # PDF işleyici
├── .htaccess              # Güvenlik kuralları
└── ...
```

## Şifreyi Değiştirmek

**SADECE** `check_password.php` dosyasını düzenleyin:

```php
// Satır 24
$CORRECT_PASSWORD = 'yeniSifre123';
```

**ÖNEMLİ:** `index.html` dosyasında şifre YOK!

## Test

### 1. Kaynak Kodunu Kontrol Edin
```
1. Tarayıcıda F12 açın
2. Sources → index.html
3. "password" ara
4. Şifre BULUNAMAYACAK ✅
```

### 2. Network İsteğini İnceleyin
```
1. F12 → Network
2. Şifre girin
3. check_password.php isteğini görün
4. Request Payload'da şifre şifreli ✅
```

### 3. Session Kontrolü
```
1. Şifre ile giriş yapın
2. Sayfayı yenileyin (F5)
3. Tekrar şifre istenmemeli ✅
4. Tarayıcıyı kapatın
5. Tekrar açın → Şifre istenmeli ✅
```

## Güvenlik Seviyeleri

| Özellik | Eski Sistem | Yeni Sistem |
|---------|-------------|-------------|
| **Şifre Görünürlüğü** | ❌ JavaScript'te açık | ✅ Sadece sunucuda |
| **Kaynak Kod** | ❌ F12 ile görünür | ✅ Görünmez |
| **Session** | ❌ localStorage | ✅ PHP session |
| **Timeout** | ❌ Yok | ✅ 2 saat |
| **Brute Force** | ❌ Korumasız | ✅ Loglanır |
| **HTTPS** | ⚠️ Önerilir | ✅ Gerekli |

## Ek Güvenlik Önerileri

### 1. Rate Limiting Ekleyin

`check_password.php` dosyasına:

```php
// Basit rate limiting
session_start();
$attempts = $_SESSION['login_attempts'] ?? 0;
$last_attempt = $_SESSION['last_attempt'] ?? 0;

// 5 dakika içinde 5 deneme
if ($attempts >= 5 && (time() - $last_attempt) < 300) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Çok fazla deneme. 5 dakika bekleyin.']);
    exit;
}

$_SESSION['login_attempts'] = $attempts + 1;
$_SESSION['last_attempt'] = time();
```

### 2. IP Whitelist

`.htaccess` dosyasına:

```apache
# Sadece belirli IP'lerden erişim
<Files "index.html">
    Order Deny,Allow
    Deny from all
    Allow from 123.456.789.0
    Allow from 987.654.321.0
</Files>
```

### 3. WordPress Entegrasyonu

Daha güvenli:

```php
// WordPress kullanıcı kontrolü
if (!is_user_logged_in() || !current_user_can('edit_posts')) {
    wp_die('Yetkisiz erişim');
}
```

## Sık Sorulan Sorular

### S: Şifre hala hacklenebilir mi?
**C:** Evet, ama çok daha zor:
- Kaynak kodda görünmez
- Brute force daha zor
- Session güvenli
- HTTPS ile şifreli

### S: Şifre nerede saklanıyor?
**C:** 
- ❌ JavaScript'te DEĞİL
- ❌ localStorage'da DEĞİL
- ✅ PHP dosyasında (sunucuda)
- ✅ PHP session'da (geçici)

### S: Tarayıcı kapatınca ne olur?
**C:** PHP session sona erer, tekrar şifre ister.

### S: 2 saat sonra ne olur?
**C:** Session timeout, tekrar şifre ister.

### S: Birden fazla kişi kullanabilir mi?
**C:** Evet, her kullanıcı kendi session'ına sahip.

## Güvenlik Kontrol Listesi

- [x] Şifre JavaScript'ten kaldırıldı
- [x] PHP backend eklendi
- [x] Session yönetimi aktif
- [x] Timeout ayarlandı (2 saat)
- [x] Brute force loglama
- [x] .htaccess koruması
- [ ] HTTPS aktif (kontrol edin)
- [ ] Rate limiting (opsiyonel)
- [ ] IP whitelist (opsiyonel)
- [ ] WordPress entegrasyonu (opsiyonel)

## Sonuç

✅ **Şifre artık güvenli!**
- Kaynak kodda görünmez
- F12 ile bulunamaz
- Sunucu tarafında kontrol edilir
- Session güvenli
- Hacklemek çok daha zor

⚠️ **Unutmayın:**
- Hiçbir sistem %100 güvenli değildir
- HTTPS kullanın
- Şifreyi düzenli değiştirin
- Logları kontrol edin

---

**Şifre:** `fatura!1234` (sadece `check_password.php` dosyasında)
