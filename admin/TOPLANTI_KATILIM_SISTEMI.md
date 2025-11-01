# Toplantı Katılım Yönetim Sistemi

## 🎯 Özellikler

### 1. Toplantı Öncesi Email Bildirimi
- Toplantı oluşturulduğunda katılımcılara otomatik email gönderilir
- Email içinde "Katılacağım" ve "Katılmayacağım" butonları bulunur
- Her katılımcı için benzersiz token ile güvenli link oluşturulur

### 2. Katılım Yanıtı Alma
- Katılımcılar email'deki linke tıklayarak katılım durumunu bildirebilir
- "Katılacağım" butonu: Toplantıya katılacağını belirtir
- "Katılmayacağım" butonu: Mazeret bildirme formu açar

### 3. Mazeret Bildirme
- Katılamayan katılımcılar mazeret nedenlerini yazabilir
- Mazeret bilgisi veritabanında saklanır

### 4. Hatırlatma Emaili
- Toplantıdan 24 saat önce otomatik hatırlatma emaili gönderilir
- `admin/send_meeting_reminders.php` dosyası cron job ile çalıştırılabilir

### 5. Bildirim Sistemi
- Tarayıcı bildirimleri (Notification API)
- In-app bildirimler (veritabanında saklanır)

## 📁 Oluşturulan/Güncellenen Dosyalar

### Yeni Dosyalar:
1. `admin/update_meeting_participants_table.php` - Tablo güncelleme scripti
2. `admin/includes/email_helper.php` - Email gönderme helper sınıfı
3. `admin/meeting_response.php` - Katılım yanıtı sayfası
4. `admin/send_meeting_reminders.php` - Hatırlatma email scripti
5. `admin/includes/notification_helper.php` - Bildirim helper sınıfı

### Güncellenen Dosyalar:
1. `admin/api/meeting_api.php` - Toplantı oluşturma ve email gönderme eklendi

## 🗄️ Veritabanı Değişiklikleri

### `meeting_participants` tablosuna eklenen kolonlar:
- `response_status` ENUM('pending', 'accepted', 'declined') - Katılım yanıt durumu
- `response_date` TIMESTAMP - Yanıt verilme tarihi
- `excuse_reason` TEXT - Mazeret nedeni
- `response_token` VARCHAR(100) UNIQUE - Güvenli link için token
- `participant_email` VARCHAR(200) - Katılımcı email adresi
- `user_id` INT - Kullanıcı ID'si (opsiyonel)

### `attendance_status` ENUM güncellendi:
- Artık şu değerleri alabilir: 'invited', 'accepted', 'declined', 'attended', 'absent', 'excused'

## 🚀 Kurulum

### 1. Veritabanı Güncelleme
```bash
php admin/update_meeting_participants_table.php
```

### 2. Email Ayarları
`admin/config.php` dosyasında SMTP ayarlarını güncelleyin:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'noreply@aifcrm.metechnik.at');
define('SMTP_FROM_NAME', 'AIF Otomasyon');
```

### 3. Hatırlatma Email Cron Job (Opsiyonel)
Sunucuda cron job ekleyin:
```bash
# Her gün saat 09:00'da çalıştır
0 9 * * * /usr/bin/php /path/to/admin/send_meeting_reminders.php
```

## 📧 Email Kullanımı

### Toplantı Oluştururken
Toplantı oluştururken katılımcıları `participants` array'i ile gönderin:
```javascript
{
    "title": "Toplantı Başlığı",
    "date": "2026-02-15",
    "time": "14:00:00",
    "location": "Toplantı Yeri",
    "participants": [
        {
            "participant_name": "Ahmet Yılmaz",
            "participant_email": "ahmet@example.com",
            "participant_role": "member"
        },
        {
            "user_id": 5,  // User ID varsa email otomatik çekilir
            "participant_role": "member"
        }
    ]
}
```

### Katılım Yanıtı Linkleri
Email'de gönderilen linkler:
- **Katılacağım**: `https://aifcrm.metechnik.at/admin/meeting_response.php?token=XXX&action=accept`
- **Katılmayacağım**: `https://aifcrm.metechnik.at/admin/meeting_response.php?token=XXX&action=decline`

## 🔧 API Endpoints

### Yeni Eklenen Endpoints:

#### 1. Davetiye Gönderme
```
POST /admin/api/meeting_api.php?action=send_invitations
Body: {
    "meeting_id": 1,
    "participants": [...]
}
```

#### 2. Katılımcı Yanıtı Getirme
```
GET /admin/api/meeting_api.php?action=get_participant_response&token=XXX
```

### Mevcut Endpoint'ler Güncellendi:

#### Toplantı Ekleme
Artık `participants` array'i gönderildiğinde otomatik email gönderir:
```
POST /admin/api/meeting_api.php?action=add_meeting
```

## 📊 Katılım Durumu Takibi

### Toplantı Listesinde:
- `accepted_count`: Katılacağını bildirenler
- `declined_count`: Katılamayacağını bildirenler
- `participants`: Toplam davet edilenler

### Katılımcı Detaylarında:
- `response_status`: 'pending', 'accepted', 'declined'
- `response_date`: Yanıt verilme tarihi
- `excuse_reason`: Mazeret nedeni (varsa)

## ⚠️ Notlar

1. **Email Gönderimi**: Şu anda PHP `mail()` fonksiyonu kullanılıyor. Production'da SMTP kütüphanesi (PHPMailer) kullanılması önerilir.

2. **Token Güvenliği**: Her katılımcı için benzersiz 32 byte token oluşturulur. Token'lar tek kullanımlık değildir (yanıt güncellenebilir).

3. **Bildirim Tablosu**: `notifications` tablosu henüz oluşturulmamışsa, `notification_helper.php` kullanılamaz. İsterseniz bu tabloyu da oluşturabiliriz.

4. **Cron Job**: Hatırlatma email'leri için cron job kurulumu opsiyoneldir. Manuel de çalıştırılabilir.

## 🔄 Gelecek İyileştirmeler

- [ ] PHPMailer entegrasyonu
- [ ] Email şablonları düzenlenebilir yapılabilir
- [ ] SMS bildirimi eklenebilir
- [ ] Toplantı iptal email'i
- [ ] Katılım listesi export (PDF/Excel)
- [ ] Toplantı güncelleme email'i

