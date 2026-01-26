# JSON Import Script Çalıştırma Talimatları

## import-birimler-json.php Scripti

Bu script, JSON dosyalarından (AT_birimler.json, KGT_birimler.json, KT_birimler.json, GT_birimler.json) BYK birimlerini ve kullanıcılarını veritabanına import eder.

### Özellikler

- Virgülle ayrılan mailler için her mail ayrı kullanıcı olarak oluşturulur
- Tüm kullanıcılar aynı görev/alt birime atanır
- Kullanıcı varsa güncellenir, yoksa oluşturulur
- Varsayılan şifre: `AIF571#`
- Alt birimler otomatik oluşturulur (görev adına göre)

### Çalıştırma Yöntemi

#### 1. Web Tarayıcısından (Önerilen)

```
https://aifcrm.metechnik.at/database/import-birimler-json.php
```

**Adımlar:**
1. Tarayıcınızı açın
2. Yukarıdaki URL'yi adres çubuğuna yapıştırın
3. Enter'a basın
4. Script çalışacak ve sonuçları gösterecek

**Not:** Script çalışırken sayfa yüklenene kadar bekleyin. İşlem uzun sürebilir.

#### 2. Komut Satırından (SSH/Terminal)

Eğer sunucuya SSH ile erişiminiz varsa:

```bash
cd /www/htdocs/w01dc0ea/aifcrm.metechnik.at
php database/import-birimler-json.php
```

### Ön Hazırlık

1. **JSON Dosyalarının Hazır Olması:**
   - `AT_birimler.json`
   - `KGT_birimler.json`
   - `KT_birimler.json`
   - `GT_birimler.json`
   
   Bu dosyalar proje kök dizininde olmalıdır.

2. **Veritabanı Tablolarının Oluşturulması:**
   - `byk_categories` tablosu olmalı
   - `byk_sub_units` tablosu olmalı (script otomatik oluşturur)
   - `kullanicilar` tablosu olmalı
   - `byk` tablosu olmalı (eski sistem uyumluluğu için)

3. **BYK Kategorilerinin Oluşturulması:**
   - AT, KGT, KT, GT kategorileri `byk_categories` tablosunda olmalı
   - Eğer yoksa script hata verecektir

### Script Sonuçları

Script çalıştıktan sonra şunları gösterecek:

- ✅ **Eklendi:** Yeni kullanıcı oluşturuldu
- 🔄 **Güncellendi:** Mevcut kullanıcı güncellendi
- ⚠️ **Atlandı:** Email yok veya görev adı yok
- ❌ **Hata:** İşlem sırasında hata oluştu

### Örnek Çıktı

```
✅ Eklendi: mermer38@gmx.at - Ömer Mermer (Çocuk Kulübü Sorumlusu) - Şifre: AIF571#
✅ Eklendi: aydinomer61@outlook.de - Ömer Aydin (Çocuk Kulübü Sorumlusu) - Şifre: AIF571#
```

### Sorun Giderme

**Hata: "Dosya bulunamadı"**
- JSON dosyalarının proje kök dizininde olduğundan emin olun

**Hata: "BYK kategorisi bulunamadı"**
- Önce BYK kategorilerini oluşturun (`byk_categories` tablosuna AT, KGT, KT, GT kayıtlarını ekleyin)

**Hata: "Veritabanı bağlantı hatası"**
- `config/database.php` dosyasındaki veritabanı bilgilerini kontrol edin

### Güvenlik Notu

⚠️ **Önemli:** Script çalıştırıldıktan sonra kullanıcılar oluşturulacak/güncellenecektir. İşlem geri alınamaz. Script çalıştırmadan önce veritabanı yedeği alın.

### İlgili Scriptler

- `import-alt-birimler-json.php` - Alt birimleri (bölgeleri) import eder
- `import-gorevler-alt-birimler.php` - Görevleri alt birim olarak ekler
- `update-alt-birim-sorumlular.php` - Alt birim sorumlularını günceller
