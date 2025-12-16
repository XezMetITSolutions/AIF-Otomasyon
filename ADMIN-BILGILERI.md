# 🔐 Admin Kullanıcı Bilgileri

## 📧 Giriş Bilgileri

**Site URL:** [https://aifcrm.metechnik.at/](https://aifcrm.metechnik.at/)

### Admin Hesabı

- **E-posta:** `egitim@islamfederasyonu.at`
- **Şifre:** `01528797Mb##`
- **Rol:** Ana Yönetici (super_admin)
- **İlk Giriş:** Şifre değiştirme zorunlu değil (ilk_giris_zorunlu = 0)

## 🗄️ Veritabanı Bilgileri

- **Host:** `localhost`
- **Veritabanı:** `d0451622`
- **Kullanıcı:** `d0451622`
- **Şifre:** `01528797Mb##`

## 📝 Admin Kullanıcısını Güncelleme

### Yöntem 1: SQL Dosyası ile (phpMyAdmin)

1. phpMyAdmin açın
2. `d0451622` veritabanını seçin
3. **SQL** sekmesine tıklayın
4. `database/update-admin-user.sql` dosyasını açın
5. İçeriği kopyalayın ve SQL sekmesine yapıştırın
6. **Git** butonuna tıklayın

### Yöntem 2: Manuel SQL Komutu

```sql
UPDATE `kullanicilar` 
SET 
    `email` = 'egitim@islamfederasyonu.at',
    `sifre` = '$2y$10$O0iBgiQbOTMfJej52oXPu.AT/xXXMF6HVOQDyxtxpHCZbO9AkhIX.',
    `ilk_giris_zorunlu` = 0
WHERE `kullanici_id` = 1;
```

### Yöntem 3: Admin Panelinden

1. Giriş yapın (eski bilgilerle)
2. Admin panelinde kullanıcı yönetimi bölümüne gidin
3. Admin kullanıcısını düzenleyin
4. E-posta ve şifreyi güncelleyin

## ✅ Kontrol

### Giriş Testi

1. https://aifcrm.metechnik.at/ adresine gidin
2. **E-posta:** `egitim@islamfederasyonu.at`
3. **Şifre:** `01528797Mb##`
4. **Giriş Yap** butonuna tıklayın
5. ✅ Admin paneline yönlendirilmelisiniz

### Veritabanı Kontrolü

phpMyAdmin'de kontrol edin:

```sql
SELECT `kullanici_id`, `email`, `ad`, `soyad`, `rol_id`, `ilk_giris_zorunlu`, `aktif` 
FROM `kullanicilar` 
WHERE `email` = 'egitim@islamfederasyonu.at';
```

**Beklenen Sonuç:**
- `email`: `egitim@islamfederasyonu.at`
- `ilk_giris_zorunlu`: `0`
- `aktif`: `1`
- `rol_id`: `1` (super_admin)

## 🔒 Güvenlik Notları

1. ⚠️ Bu bilgiler hassas bilgilerdir, paylaşmayın
2. ✅ İlk kurulumdan sonra şifreyi değiştirmeniz önerilir
3. ✅ Düzenli olarak şifre değiştirin
4. ✅ Güçlü şifre kullanın (en az 8 karakter, büyük/küçük harf, rakam, özel karakter)

## 📞 Sorun Giderme

### Giriş Yapamıyorum

1. E-posta adresinin doğru olduğundan emin olun: `egitim@islamfederasyonu.at`
2. Şifrenin doğru olduğundan emin olun: `01528797Mb##`
3. Veritabanında kullanıcının var olduğunu kontrol edin
4. `aktif` alanının `1` olduğundan emin olun

### Şifre Hash Hatası

Eğer şifre çalışmıyorsa, yeni hash oluşturun:

```php
<?php
echo password_hash('01528797Mb##', PASSWORD_DEFAULT);
?>
```

Sonra SQL ile güncelleyin.

---

**Son Güncelleme:** Kasım 2025

