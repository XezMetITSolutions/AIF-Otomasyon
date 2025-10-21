# AIF Otomasyon - Kullanıcı ve Yetki Yönetimi Sistemi

## Genel Bakış

Bu sistem, AIF Otomasyon projesi için kullanıcı yönetimi ve yetki kontrolünü birleştiren kapsamlı bir çözümdür. Sistem, kullanıcıların modül bazında detaylı yetkilendirilmesini sağlar.

## Özellikler

### 🔐 Kullanıcı Yönetimi
- Kullanıcı ekleme, düzenleme ve silme
- BYK (Birim) bazında kullanıcı organizasyonu
- Rol bazlı erişim kontrolü (Superadmin, Admin, Üye)
- Kullanıcı istatistikleri ve filtreleme

### 🛡️ Yetki Yönetimi
- Modül bazında detaylı yetki kontrolü
- 4 seviyeli yetki sistemi:
  - **Erişim Yok**: Modüle hiç erişim yok
  - **Sadece Okuma**: Sadece görüntüleme yetkisi
  - **Okuma ve Yazma**: Görüntüleme ve düzenleme yetkisi
  - **Tam Yetki**: Tüm yetkiler (silme dahil)

### 📊 Modül Yönetimi
- 13 farklı modül desteği
- Modül bazında kullanıcı sayısı takibi
- Modül durumu ve açıklamaları

## Dosya Yapısı

```
admin/
├── user_permissions.php          # Ana birleşik arayüz
├── manage_user_permissions.php    # Yetki yönetimi API
├── init_modules.php              # Modül başlatma API
├── setup_permissions.php         # Kurulum scripti
├── includes/
│   ├── permission_manager.php    # Yetki yönetimi sınıfı
│   ├── user_manager_db.php      # Kullanıcı veritabanı işlemleri
│   └── sidebar.php              # Güncellenmiş sidebar
└── [mevcut kullanıcı dosyaları]
```

## Kurulum

### 1. Veritabanı Hazırlığı
```bash
# Kurulum scriptini çalıştırın
http://your-domain/admin/setup_permissions.php
```

Bu script:
- `modules` tablosunu oluşturur
- `user_permissions` tablosunu oluşturur
- Tüm modülleri veritabanına yükler

### 2. Sistem Kullanımı
1. **Ana Sayfa**: `user_permissions.php` - Birleşik kullanıcı ve yetki yönetimi
2. **Kullanıcılar Sekmesi**: Kullanıcı CRUD işlemleri
3. **Yetki Yönetimi Sekmesi**: Modül bazında yetki atama
4. **Modül Ayarları Sekmesi**: Modül durumu ve istatistikleri

## API Endpoints

### Yetki Yönetimi API (`manage_user_permissions.php`)

#### Yetkileri Getir
```javascript
POST /admin/manage_user_permissions.php
{
    "action": "get",
    "username": "kullanici_adi"
}
```

#### Yetkileri Güncelle
```javascript
POST /admin/manage_user_permissions.php
{
    "action": "update",
    "username": "kullanici_adi",
    "permissions": {
        "dashboard": "admin",
        "users": "write",
        "events": "read"
    }
}
```

#### Yetkileri Sıfırla
```javascript
POST /admin/manage_user_permissions.php
{
    "action": "reset",
    "username": "kullanici_adi"
}
```

## Veritabanı Yapısı

### modules Tablosu
```sql
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### user_permissions Tablosu
```sql
CREATE TABLE user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    can_read BOOLEAN DEFAULT FALSE,
    can_write BOOLEAN DEFAULT FALSE,
    can_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_module (user_id, module_id)
);
```

## Modüller

Sistem aşağıdaki modülleri destekler:

| Modül | Açıklama | İkon |
|-------|----------|------|
| dashboard | Ana kontrol paneli | fas fa-tachometer-alt |
| users | Kullanıcı yönetimi | fas fa-users |
| permissions | Yetki yönetimi | fas fa-shield-alt |
| announcements | Duyuru yönetimi | fas fa-bullhorn |
| events | Etkinlik yönetimi | fas fa-calendar-alt |
| calendar | Takvim görüntüleme | fas fa-calendar |
| inventory | Demirbaş yönetimi | fas fa-boxes |
| meeting_reports | Toplantı raporları | fas fa-file-alt |
| reservations | Rezervasyon yönetimi | fas fa-bookmark |
| expenses | İade talepleri | fas fa-undo |
| projects | Proje yönetimi | fas fa-project-diagram |
| reports | Raporlar ve analizler | fas fa-chart-bar |
| settings | Sistem ayarları | fas fa-cog |

## Güvenlik

- Sadece admin ve superadmin kullanıcıları yetki yönetimi yapabilir
- Tüm API istekleri JSON formatında ve POST metodu ile
- SQL injection koruması için prepared statements kullanılır
- CSRF koruması için session kontrolü yapılır

## Geliştirme Notları

### Yeni Modül Ekleme
1. `includes/permission_manager.php` dosyasındaki `MODULES` sabitine yeni modülü ekleyin
2. `setup_permissions.php` scriptini çalıştırarak veritabanını güncelleyin
3. Sidebar'a yeni modül linkini ekleyin

### Yetki Kontrolü
```php
// Kullanıcının modül yetkisini kontrol et
if (UserManager::hasModulePermission($userId, 'module_name', 'read')) {
    // Okuma yetkisi var
}

if (UserManager::hasModulePermission($userId, 'module_name', 'write')) {
    // Yazma yetkisi var
}

if (UserManager::hasModulePermission($userId, 'module_name', 'admin')) {
    // Admin yetkisi var
}
```

## Sorun Giderme

### Yaygın Sorunlar

1. **Modüller yüklenmiyor**
   - `setup_permissions.php` scriptini çalıştırın
   - Veritabanı bağlantısını kontrol edin

2. **Yetkiler kaydedilmiyor**
   - `user_permissions` tablosunun oluşturulduğundan emin olun
   - Foreign key kısıtlamalarını kontrol edin

3. **Sidebar güncellenmiyor**
   - `includes/sidebar.php` dosyasının güncellendiğinden emin olun
   - Browser cache'ini temizleyin

## Gelecek Geliştirmeler

- [ ] Toplu yetki atama
- [ ] Yetki şablonları
- [ ] Yetki geçmişi takibi
- [ ] Otomatik yetki süresi
- [ ] Yetki delegasyonu
- [ ] API rate limiting
- [ ] Yetki raporları

## Destek

Herhangi bir sorun yaşarsanız:
1. Hata loglarını kontrol edin
2. Veritabanı bağlantısını test edin
3. Kurulum scriptini tekrar çalıştırın
4. Sistem gereksinimlerini kontrol edin

---

**Not**: Bu sistem AIF Otomasyon projesi için özel olarak geliştirilmiştir ve mevcut kullanıcı yönetimi sistemini tamamen entegre eder.

