# Onay Akışı Sistemi - Kullanım Kılavuzu

Bu belge, sistemdeki izin ve harcama talepleri için oluşturulan iki seviyeli onay akışını açıklar.

## Onay Akışı Yapısı

### 1. İzin Talepleri (Tek Seviye)
İzin talepleri tek seviye onay sistemiyle çalışır:
- **Onaylayıcı**: Yasin Çakmak
- **Akış**: Talep → Yasin Çakmak → Onay/Red

### 2. Harcama Talepleri (İki Seviye)
Harcama talepleri iki seviye onay sistemiyle çalışır:
- **1. Onaylayıcı**: Yasin Çakmak
- **2. Onaylayıcı**: Muhammed Enes Sivrikaya (AT Muhasebe Başkanı)
- **Akış**: Talep → Yasin Çakmak (1. Onay) → Muhammed Enes Sivrikaya (2. Onay) → Tamamen Onaylandı

## Kurulum Adımları

### 1. Veritabanı Güncelleme
Migration scriptini çalıştırın:
```bash
php database/update_approval_workflow.php
```

Bu script:
- `harcama_talepleri` tablosuna yeni kolonlar ekler:
  - `onay_seviyesi` (0=beklemede, 1=ilk onay, 2=tamamen onaylandı)
  - `ilk_onaylayan_id`, `ilk_onay_tarihi`, `ilk_onay_aciklama`
  - `ikinci_onaylayan_id`, `ikinci_onay_tarihi`, `ikinci_onay_aciklama`
- Yasin Çakmak ve Muhammed Enes Sivrikaya kullanıcılarını bulur
- `config/approval_workflow.php` dosyasını oluşturur

### 2. Kullanıcı Bilgilerini Kontrol
Migration script çalıştırıldıktan sonra çıktıyı kontrol edin:
- Yasin Çakmak kullanıcısı bulundu mu?
- Muhammed Enes Sivrikaya kullanıcısı bulundu mu?

Eğer kullanıcılar bulunamadıysa, manuel olarak `config/approval_workflow.php` dosyasını düzenleyin ve kullanıcı ID'lerini ekleyin.

## Durum Kodları

### Harcama Talepleri
- `beklemede`: Henüz kimse onaylamamış (İlk onayı bekliyor)
- `ilk_onay`: Yasin Çakmak onayladı, ikinci onayı bekliyor
- `onaylandi`: Her iki onaylayıcı da onayladı
- `reddedildi`: Herhangi bir seviyede reddedildi
- `odenmistir`: Ödeme yapıldı (muhasebe tarafından işaretlenir)

### İzin Talepleri
- `beklemede`: Yasin Çakmak onayını bekliyor
- `onaylandi`: Yasin Çakmak onayladı
- `reddedildi`: Yasin Çakmak reddetti

## Kullanıcı Arayüzü

### İlk Onaylayıcı (Yasin Çakmak)
- **Göreceği Talepler**: Varsayılan olarak "Beklemede" durumundaki harcama talepleri
- **Butonlar**: "1. Onay" (mavi) ve "Reddet" (kırmızı)
- **İşlem Sonrası**: 
  - Onaylarsa → Durum "ilk_onay" olur, ikinci onaylayıcıya bildirim gider
  - Reddederse → Durum "reddedildi" olur, talep sahibine bildirim gider

### İkinci Onaylayıcı (Muhammed Enes Sivrikaya)
- **Göreceği Talepler**: Varsayılan olarak "İlk Onay" durumundaki harcama talepleri
- **Butonlar**: "2. Onay" (yeşil) ve "Reddet" (kırmızı)
- **İşlem Sonrası**:
  - Onaylarsa → Durum "onaylandi" olur, talep sahibine bildirim gider
  - Reddederse → Durum "reddedildi" olur, talep sahibine bildirim gider

### Talep Sahibi
- Kendi taleplerim bölümünde şunları görür:
  - Durum badge'i (Beklemede, İlk Onay ✓, Onaylandı ✓✓, Reddedildi)
  - Eğer onaylanmışsa, hangi onaylayıcıların onayladığı

### Başkan/Y Önetici
- Tüm talepleri görebilir
- Filtreleme yapabilir (Beklemede, İlk Onay, Onaylandı, Reddedilen, Tümü)
- Direkt onaylama yetkisi var (her iki seviyeyi de geçer)
- Düzenleme ve silme yetkisi var

## Bildirimler

### Yeni Talep Oluşturulduğunda
- **Kime**: Yasin Çakmak (1. Onaylayıcı)
- **E-posta**: "Yeni harcama talebi onayınızı bekliyor"

### İlk Onay Verildiğinde
- **Kime**: Muhammed Enes Sivrikaya (2. Onaylayıcı)
- **E-posta**: "Yasin Çakmak tarafından onaylanan bir harcama talebi sizin onayınızı bekliyor"

### Talep Tamamen Onaylandığında
- **Kime**: Talep Sahibi
- **E-posta**: "Harcama talebiniz hem Yasin Çakmak hem de AT Muhasebe Başkanı tarafından onaylandı"

### Talep Reddedildiğinde
- **Kime**: Talep Sahibi
- **E-posta**: "Harcama talebiniz reddedildi" + Red açıklaması

## Dosya Yapısı

```
Otomasyon/
├── config/
│   └── approval_workflow.php          # Onay akışı konfigürasyonu
├── database/
│   └── update_approval_workflow.php   # Migration script
└── panel/
    ├── harcama-talepleri.php          # İki seviyeli onay akışı
    └── izin-talepleri.php             # Tek seviyeli onay akışı
```

## Önemli Notlar

1. **Veritabanı Değişiklikleri Geri Alınamaz**: Migration script çalıştırıldıktan sonra yeni kolonlar eklenir. Önceden yedek alın.

2. **Mevcut Veriler**: Migration script mevcut `onaylayan_id` verilerini `ilk_onaylayan_id`'ye kopyalar.

3. **Kullanıcı Bulunamazsa**: Eğer Yasin Çakmak veya Muhammed Enes Sivrikaya kullanıcıları bulunamazsa, `config/approval_workflow.php` dosyasını manuel olarak düzenleyin.

4. **Yetkilendirme**: Sistem, kullanıcının `hasPermissionBaskan` yetkisi olup olmadığını veya konfigürasyonda tanımlı onaylayıcı olup olmadığını kontrol eder.

## Sorun Giderme

### Onay butonları görünmüyor
- Kullanıcının onaylayıcı olarak tanımlandığından emin olun
- `config/approval_workflow.php` dosyasında kullanıcı ID'lerinin doğru olduğunu kontrol edin
- Talebin uygun durumda olduğundan emin olun (beklemede veya ilk_onay)

### E-posta bildirimleri gönderilmiyor
- Mail sınıfının doğru yapılandırıldığını kontrol edin
- SMTP ayarlarını kontrol edin
- E-posta şablonlarının mevcut olduğundan emin olun

### Talep durumu güncellenmiyor
- JavaScript console'unda hata olup olmadığını kontrol edin
- Form submit işleminin başarılı olduğundan emin olun
- Veritabanı loglarını kontrol edin

## Geliştirme Notları

### Yeni Onay Seviyesi Ekleme
Eğer 3. seviye eklemek isterseniz:
1. Veritabanına yeni kolonlar ekleyin (`ucuncu_onaylayan_id`, vb.)
2. `harcama-talepleri.php` dosyasında yeni şartları ekleyin
3. Konfigürasyonda `third_approver_user_id` ekleyin
4. Durum kodlarını güncelleyin (örn: `ikinci_onay`)

### Onay Akışını Değiştirme
- `config/approval_workflow.php` dosyasını düzenleyin
- Kullanıcı ID'lerini güncelleyin
- Gerekirse migration script'i tekrar çalıştırın

---
**Son Güncelleme**: 2026-02-05
**Versiyon**: 1.0
