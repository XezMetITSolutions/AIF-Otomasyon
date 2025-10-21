# Code List Yönetimi Kurulum Rehberi

## 📋 Genel Bakış

Code List Yönetimi, sistemdeki tüm dropdown seçeneklerini merkezi olarak yönetmenizi sağlayan bir modüldür. BYK'lar, görevler, birimler, gider seçenekleri gibi tüm seçenekleri tek bir yerden düzenleyebilirsiniz.

## 🚀 Kurulum Adımları

### 1. Veritabanı Tablolarını Oluşturun

```bash
# Tarayıcıda şu URL'yi açın:
http://aifcrm.metechnik.at/admin/setup_code_list_tables.php
```

Bu script şu tabloları oluşturacak:
- `byk_categories` - BYK Kategorileri
- `positions` - Görevler
- `sub_units` - Alt Birimler
- `expense_types` - Gider Türleri
- `announcement_types` - Duyuru Türleri
- `event_types` - Etkinlik Türleri

### 2. Code List Sayfasına Erişin

```bash
# Tarayıcıda şu URL'yi açın:
http://aifcrm.metechnik.at/admin/code_list.php
```

## 📊 Özellikler

### Kategoriler

1. **BYK Kategorileri** 🏢
   - Ana Teşkilat (AT)
   - Kadınlar Teşkilatı (KT)
   - Kadınlar Gençlik Teşkilatı (KGT)
   - Gençlik Teşkilatı (GT)

2. **Görevler** 👔
   - Bölge Başkanı
   - Teşkilatlanma Başkanı
   - Eğitim Başkanı
   - İrşad Başkanı
   - Ve daha fazlası...

3. **Alt Birimler** 🏗️
   - Yazılım Geliştirme
   - Sistem Yönetimi
   - Ağ Güvenliği
   - Veritabanı Yönetimi

4. **Gider Türleri** 💰
   - Ulaşım
   - Yemek
   - Konaklama
   - Malzeme

5. **Duyuru Türleri** 📢
   - Genel Duyuru
   - Acil Duyuru
   - Toplantı Duyurusu
   - Etkinlik Duyurusu

6. **Etkinlik Türleri** 📅
   - Toplantı
   - Eğitim
   - Sosyal Etkinlik
   - Kongre

### İşlemler

- ✅ **Ekleme**: Yeni öğe ekleme
- ✅ **Düzenleme**: Mevcut öğeleri düzenleme
- ✅ **Silme**: Öğeleri silme
- ✅ **Filtreleme**: Kategori bazında filtreleme
- ✅ **Arama**: Öğe arama
- ✅ **İstatistikler**: Kategori istatistikleri

## 🎨 Arayüz Özellikleri

### Modern Tasarım
- Responsive tasarım
- Bootstrap 5 kullanımı
- Font Awesome ikonları
- Gradient renkler
- Hover efektleri

### Kullanıcı Deneyimi
- Floating Action Button
- Modal pencereler
- Toast bildirimleri
- Drag & drop (gelecek sürümde)
- Bulk işlemler (gelecek sürümde)

## 🔧 Teknik Detaylar

### Dosya Yapısı
```
admin/
├── code_list.php                 # Ana sayfa
├── api/
│   └── code_list_api.php        # API endpoint'leri
├── setup_code_list_tables.php   # Veritabanı kurulumu
└── CODE_LIST_KURULUM.md         # Bu dosya
```

### API Endpoint'leri
- `GET ?action=get_items&category=byk` - Öğeleri listele
- `POST ?action=add_item` - Yeni öğe ekle
- `POST ?action=update_item` - Öğe güncelle
- `DELETE ?action=delete_item&id=1` - Öğe sil
- `GET ?action=get_item&id=1` - Tek öğe getir

### Veritabanı Yapısı
Her kategori için ayrı tablo:
- `id` - Primary key
- `name` - Öğe adı
- `description` - Açıklama
- `category/level/priority` - Kategoriye özel alanlar
- `created_at` - Oluşturulma tarihi
- `updated_at` - Güncellenme tarihi

## 🚨 Sorun Giderme

### Yaygın Sorunlar

1. **Tablolar oluşturulmadı**
   - `setup_code_list_tables.php` dosyasını çalıştırın
   - Veritabanı bağlantısını kontrol edin

2. **API çalışmıyor**
   - `api/code_list_api.php` dosyasının varlığını kontrol edin
   - PHP hata loglarını kontrol edin

3. **Modal açılmıyor**
   - Bootstrap JavaScript'inin yüklendiğini kontrol edin
   - Console hatalarını kontrol edin

### Hata Kodları
- `400` - Geçersiz istek
- `404` - Öğe bulunamadı
- `500` - Sunucu hatası

## 📈 Gelecek Özellikler

- [ ] Bulk işlemler (toplu silme, güncelleme)
- [ ] Drag & drop sıralama
- [ ] Excel import/export
- [ ] Gelişmiş filtreleme
- [ ] Kategori bazında yetkilendirme
- [ ] Audit log (değişiklik geçmişi)
- [ ] API rate limiting
- [ ] Caching sistemi

## 🤝 Destek

Herhangi bir sorun yaşarsanız:
1. Bu rehberi tekrar okuyun
2. Hata loglarını kontrol edin
3. Veritabanı bağlantısını test edin
4. Gerekirse sistem yöneticisi ile iletişime geçin

---

**Not**: Bu modül, sistemdeki tüm dropdown seçeneklerini merkezi olarak yönetmenizi sağlar. Değişiklikler anında tüm sistemde geçerli olur.

