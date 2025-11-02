# AIF Otomasyon Sistemi - Özellikler Listesi

## 📋 Genel Özellikler

### 🔐 Güvenlik ve Kimlik Doğrulama
- Kullanıcı girişi ve oturum yönetimi
- Şifre hashleme (password_hash)
- Oturum zaman aşımı kontrolü
- CSRF koruması
- Role-based access control (RBAC)
- Modül bazlı yetkilendirme sistemi

### 👥 Kullanıcı Yönetimi
- Kullanıcı ekleme, düzenleme, silme
- BYK (Bölge Yönetim Kurulu) bazlı kullanıcı yapısı
  - AT (Ana Teşkilat)
  - KT (Kadınlar Teşkilatı)
  - KGT (Kadınlar Gençlik Teşkilatı)
  - GT (Gençlik Teşkilatı)
- Alt birim bazlı kullanıcı organizasyonu (17 alt birim)
- Kullanıcı rol yönetimi (superadmin, manager, member)
- İlk girişte şifre değiştirme zorunluluğu
- Kullanıcı durumu yönetimi (aktif/pasif)
- Kullanıcı profil yönetimi

### 🛡️ Yetki Yönetimi
- Modül bazlı yetkilendirme
- Yetki seviyeleri:
  - **None**: Erişim yok
  - **Read**: Sadece okuma
  - **Write**: Okuma ve yazma
  - **Admin**: Tam yetki (silme dahil)
- Kullanıcı bazında özelleştirilebilir yetkiler
- Modül erişim kontrolü

### 📊 Dashboard (Kontrol Paneli)
- **Superadmin Dashboard**:
  - Toplam kullanıcı sayısı
  - Aktif kullanıcı istatistikleri
  - Kullanıcı büyüme grafiği
  - Sistem durumu göstergeleri
  - Son aktiviteler
  - Quick actions (hızlı erişimler)
  
- **Member Dashboard**:
  - Kişisel bilgiler
  - Atanan görevler
  - Duyurular
  - Yaklaşan etkinlikler

### 📅 Takvim ve Etkinlik Yönetimi
- Etkinlik oluşturma, düzenleme, silme
- Takvim görüntüleme (aylık/günlük)
- BYK bazlı etkinlik filtreleme
- Tekrarlayan etkinlikler (recurring events)
- Etkinlik renk kodlaması (BYK bazlı)
- Etkinlik detay görüntüleme
- 2026 yılı program listesi (önceden tanımlı etkinlikler)
- Etkinlik arama ve filtreleme

### 📢 Duyuru Yönetimi
- Duyuru oluşturma, düzenleme, silme
- Duyuru önceliklendirme
- BYK bazlı duyuru hedefleme
- Duyuru durumu (aktif/pasif)
- Duyuru tarih yönetimi
- Duyuru listeleme ve görüntüleme

### 📝 Toplantı Yönetimi ve Raporları
- Toplantı oluşturma ve planlama
- Toplantı katılımcı yönetimi
- Toplantı gündem maddeleri
- Toplantı kararları ve görevler
- Toplantı raporu oluşturma
- Toplantı durumu takibi (planned, ongoing, completed, cancelled)
- Toplantı türleri (regular, emergency, special)
- Toplantı dosyaları yönetimi

### 📧 Toplantı Katılım Yönetim Sistemi (YENİ!)
- **Email Bildirimi**:
  - Toplantı oluşturulduğunda otomatik email gönderme
  - Profesyonel HTML email şablonları
  - Benzersiz token ile güvenli link oluşturma
  
- **Katılım Yanıtı Sistemi**:
  - "Katılacağım" butonu
  - "Katılmayacağım" butonu
  - Online katılım yanıtı alma
  - Katılım durumu takibi (pending, accepted, declined)
  
- **Mazeret Bildirme**:
  - Mazeret nedenini yazma
  - Mazeret kayıt sistemi
  - Mazeret geçmişi
  
- **Hatırlatma Emaili**:
  - Toplantıdan 24 saat önce otomatik hatırlatma
  - Cron job ile çalıştırılabilir sistem
  
- **Bildirim Sistemi**:
  - Tarayıcı bildirimleri (Notification API)
  - In-app bildirimler
  - Bildirim geçmişi

### 📦 Demirbaş (Envanter) Yönetimi
- Demirbaş kayıt sistemi
- Demirbaş listesi görüntüleme
- Demirbaş kategorilendirme
- Demirbaş durumu takibi
- Demirbaş arama ve filtreleme

### 💰 Para İadesi (Expenses) Yönetimi
- İade talebi oluşturma
- İade talebi takibi
- İade durumu yönetimi (pending, approved, rejected)
- İade gerekçesi ve belgeler
- İade miktarı takibi

### 🏗️ Proje Takibi
- Proje oluşturma ve yönetimi
- Proje görev yönetimi
- Proje ilerleme takibi
- Proje durumu (planning, active, completed, cancelled)
- Proje sorumluları atama
- Proje deadline yönetimi

### 📋 Rezervasyon Sistemi
- Rezervasyon oluşturma
- Rezervasyon takvimi
- Rezervasyon durumu yönetimi
- Rezervasyon onay sistemi
- Rezervasyon iptal işlemleri

### 📝 İzin Belgesi Talep Formu Sistemi
- **Talep Formu Oluşturma**:
  - İzin belgesi talep formu doldurma
  - Adım adım form doldurma (wizard interface)
  - Talep taslağı kaydetme (draft) - sonra devam edebilme
  - Talep numarası otomatik oluşturma
  - Talep tarihi ve saat bilgisi otomatik kaydetme
  - Form validasyonu (zorunlu alan kontrolü)
  
- **Ulaşım Türü Seçimi**:
  - **Uçak**: Havayolu ile seyahat
    - Kalkış havalimanı seçimi/girişi
    - Varış havalimanı seçimi/girişi
    - Uçuş numarası (opsiyonel)
    - Hava yolu şirketi seçimi
    - Gidiş-dönüş uçuş bilgileri
  - **Tren**: Tren ile seyahat
    - Kalkış istasyonu
    - Varış istasyonu
    - Tren numarası
    - Vagon ve koltuk bilgisi (opsiyonel)
    - Gidiş-dönüş tren bilgileri
  - **Kiralık Araç**: Araç kiralama
    - Kiralama şirketi bilgisi
    - Araç tipi ve modeli
    - Plaka numarası (opsiyonel)
    - Sürücü bilgileri (isim, telefon)
    - Kiralama başlangıç-bitim tarihleri
  - **Otobüs**: Otobüs ile seyahat
    - Kalkış durağı/garaj
    - Varış durağı/garaj
    - Otobüs firması
    - Bilet numarası/bilgileri
    - Gidiş-dönüş otobüs bilgileri
  - **Özel Araç**: Kişisel araç ile seyahat
    - Araç plaka numarası
    - Araç sahibi bilgileri
    - Araç ruhsat bilgileri
  - **Diğer**: Diğer ulaşım türleri
    - Özel ulaşım türü açıklama
    - Özel not alanı
    
- **Seyahat Bilgileri Formu**:
  - **Kaç Kişi**: 
    - Yetişkin sayısı (zorunlu)
    - Çocuk sayısı (varsa, opsiyonel)
    - Toplam kişi sayısı (otomatik hesaplanır)
    - Katılımcı listesi ekleme (isim-soyisim, TC/Passaport)
    - Her katılımcı için detay bilgileri
  - **Nereye**: 
    - Gidilecek şehir/ülke seçimi (dropdown veya manuel giriş)
    - Detaylı adres (opsiyonel)
    - Seyahat amacı (detaylı açıklama - zorunlu)
    - Etkinlik/toplantı bilgisi (varsa, bağlantı)
    - Gidilecek yer öncelik sırası (eğer birden fazla yer varsa)
  - **Ne Zaman**: 
    - **Gidiş Tarihi ve Saati** (zorunlu)
      - Tarih seçici (date picker)
      - Saat seçimi
      - Kalkış yeri
    - **Dönüş Tarihi ve Saati** (zorunlu)
      - Tarih seçici (date picker)
      - Saat seçimi
      - Varış yeri
    - Seyahat süresi (otomatik hesaplanır - gün/saat)
    - Toplam gece sayısı (otomatik hesaplanır)
    - Tarih doğrulama (dönüş > gidiş kontrolü)
  
- **Ek Bilgiler Formu**:
  - Seyahat gerekçesi detaylı açıklama (zorunlu, min 50 karakter)
  - Seyahat bütçesi/tahmini maliyet (opsiyonel)
  - Konaklama bilgileri (varsa)
    - Otel/konaklama yeri adı
    - Adres
    - İletişim bilgileri
  - Acil durum iletişim bilgileri
    - İletişim kişisi adı
    - Telefon numarası
    - Email adresi (opsiyonel)
  - Özel notlar/alıntılar (opsiyonel)
  
- **Belge Yükleme Formu**:
  - Gerekli belgeleri yükleme
    - Kimlik belgesi (TC kimlik veya pasaport) - zorunlu
    - Bilet/bilet faturaları (ulaşım için) - opsiyonel
    - Konaklama belgeleri (varsa) - opsiyonel
    - Diğer ek belgeler (rapor, davetiye vb.) - opsiyonel
  - Dosya formatları: PDF, JPG, PNG (maksimum 10MB)
  - Belge önizleme
  - Belge silme (onaylanmadan önce)
  - Yüklenen belgelerin listesi
  
- **Talep Gönderme ve Durum Takibi**:
  - Form validasyonu (tüm zorunlu alanlar kontrol edilir)
  - Talep özeti görüntüleme (göndermeden önce)
  - Talep gönderme onayı
  - Talep durumu takibi:
    - **Draft**: Taslak (kaydedildi ama gönderilmedi)
    - **Submitted**: Gönderildi (admin'e iletildi)
    - **Pending**: Onay bekleniyor (admin inceleme aşamasında)
    - **Approved**: Onaylandı (izin belgesi oluşturuldu)
    - **Rejected**: Reddedildi (gerekçe ile)
    - **Revised**: Revize edildi (kullanıcı düzeltme yaptı)
  
- **Talep Listesi ve Görüntüleme**:
  - Kendi taleplerini görüntüleme
  - Talep detay sayfası
  - Talep durumu görüntüleme (badge ile)
  - Talep geçmişi
  - Talep arama (talep numarası, tarih, yer)
  - Tarih aralığı ile filtreleme
  - Durum bazlı filtreleme
  - Ulaşım türü bazlı filtreleme
  - Talep düzenleme (sadece draft ve rejected durumunda)
  - Talep silme (sadece draft durumunda)
  - Talep iptal etme (pending durumunda)
  
- **Admin/Manager Talep Yönetimi Özellikleri**:
  - Tüm izin taleplerini görüntüleme
  - Talep detayını inceleme
  - Yüklenen belgeleri görüntüleme ve indirme
  - Talep onaylama/reddetme
  - Onay/red gerekçesi yazma
  - Talep revize etme (geri gönderme kullanıcıya düzeltme için)
  - Toplu onay işlemleri
  - Talep istatistikleri ve raporları
  - Talep geçmişi ve log kayıtları
  - Talep arama ve filtreleme (tüm kullanıcılar için)
  - BYK bazlı talep filtreleme
  - Excel/PDF rapor oluşturma
  
- **Talep Bildirim Sistemi**:
  - Talep gönderildiğinde admin'e bildirim
  - Talep onaylandığında kullanıcıya email (izin belgesi oluşturuldu bilgisi ile)
  - Talep reddedildiğinde kullanıcıya email (gerekçe ile)
  - Talep revize edildiğinde kullanıcıya bildirim
  - Admin tarafından talep durumu değiştirildiğinde bildirim

### 🎫 İzin Belgesi (Permit) Oluşturma ve Yönetim Sistemi
- **İzin Belgesi Oluşturma**:
  - Onaylanan talepten otomatik izin belgesi oluşturma
  - Manuel izin belgesi oluşturma (admin için)
  - İzin belgesi şablonu kullanma
  - Otomatik PDF oluşturma (FPDF veya TCPDF)
  
- **İzin Belgesi İçeriği**:
  - Belge başlığı ve logo
  - **Belge Numarası**: Benzersiz belge numarası (örn: AIF-2025-00123)
  - **Barkod**: Otomatik barkod oluşturma
  - **QR Kod**: Doğrulama için QR kod
  - **Kullanıcı Bilgileri**:
    - Ad-Soyad
    - TC Kimlik No / Pasaport No
    - BYK bilgisi
    - Alt birim bilgisi
  - **Seyahat Bilgileri**:
    - Ulaşım türü
    - Gidiş tarihi ve saati
    - Dönüş tarihi ve saati
    - Gidilecek yer (şehir/ülke)
    - Seyahat amacı
  - **Katılımcı Bilgileri** (eğer grup seyahati ise):
    - Tüm katılımcıların listesi
    - Her katılımcının bilgileri
  - **Onay Bilgileri**:
    - Onaylayan kişi (admin/manager)
    - Onay tarihi ve saati
    - Resmi imza alanı
    - Mühür alanı
  - **Belge Geçerlilik**:
    - Belge oluşturulma tarihi
    - Belge geçerlilik süresi
    - İptal durumu (varsa)
  
- **İzin Belgesi Yönetimi**:
  - İzin belgesi listesi görüntüleme
  - İzin belgesi detay sayfası
  - İzin belgesi PDF indirme
  - İzin belgesi yazdırma (print-friendly)
  - İzin belgesi email ile gönderme
  - İzin belgesi geçerliliğini kontrol etme
  - QR kod ile doğrulama
  - İzin belgesi iptal etme (varsa iptal gerekiyorsa)
  
- **Admin/Manager İzin Belgesi Özellikleri**:
  - Tüm izin belgelerini görüntüleme
  - İzin belgesi oluşturma (manuel veya otomatik)
  - İzin belgesi düzenleme
  - İzin belgesi iptal etme
  - Toplu izin belgesi oluşturma (grup seyahatleri için)
  - İzin belgesi şablonları yönetimi
  - İzin belgesi numarası formatı ayarlama
  - Barkod ve QR kod ayarları
  
- **İzin Belgesi Doğrulama**:
  - QR kod ile doğrulama (mobil uygulama veya web)
  - Belge numarası ile sorgulama
  - Belge geçerlilik kontrolü
  - İptal edilen belgelerin kontrolü
  - Doğrulama geçmişi
  
- **Raporlama ve İstatistikler**:
  - Toplam izin belgesi sayısı
  - Aylık/haftalık izin belgesi istatistikleri
  - BYK bazlı izin belgesi dağılımı
  - Ulaşım türü bazlı istatistikler
  - En çok izin belgesi verilen yerler
  - İzin belgesi kullanım oranları
  - Excel/PDF rapor oluşturma
  
- **Bildirim ve Email**:
  - İzin belgesi oluşturulduğunda kullanıcıya email
  - PDF izin belgesi email ekiyle gönderme
  - İzin belgesi iptal edildiğinde bildirim
  - İzin belgesi yaklaşan tarih hatırlatması

### 📊 Raporlama Sistemi
- Sistem geneli raporlar
- Kullanıcı bazlı raporlar
- Etkinlik raporları
- Toplantı raporları
- İzin belgesi talep formları raporları
- İzin belgesi oluşturulma raporları
- İstatistiksel analizler
- Grafik ve chart görüntüleme (Chart.js)
- Excel/PDF export özellikleri

### ⚙️ Sistem Ayarları
- Genel sistem ayarları
- E-posta (SMTP) ayarları
- Güvenlik ayarları
- Dosya yükleme ayarları
- Logo ve tema ayarları
- Sistem bildirim ayarları

### 📱 Arayüz Özellikleri
- **Responsive Tasarım**: Mobil, tablet ve masaüstü uyumlu
- **Modern UI**: Bootstrap 5 tabanlı arayüz
- **Sidebar Navigasyon**: Rol bazlı menü yapısı
- **Dark/Light Mode**: (Gelecekte eklenecek)
- **Animasyonlar**: AOS (Animate On Scroll) ile smooth animasyonlar
- **Font Awesome İkonları**: Profesyonel ikon seti
- **Chart.js Grafikleri**: İnteraktif veri görselleştirme

### 🔄 API Özellikleri
- RESTful API yapısı
- CORS desteği
- JSON veri formatı
- API endpoint'leri:
  - Meeting API (toplantı yönetimi)
  - Calendar API (takvim işlemleri)
  - User API (kullanıcı yönetimi)
  - Permit API (izin belgesi talep ve onay işlemleri)

### 📂 Dosya Yönetimi
- Dosya yükleme sistemi
- Desteklenen formatlar: JPG, PNG, PDF, DOC, DOCX
- Maksimum dosya boyutu: 10MB
- Güvenli dosya saklama
- Toplantı dosyaları yönetimi
- İzin belgesi ek belgeleri (bilet, fatura, vb.)
- PDF izin belgesi oluşturma ve saklama
- QR kod oluşturma ve belge üzerine ekleme

### 🗄️ Veritabanı Özellikleri
- MySQL veritabanı
- PDO ile güvenli sorgu yapısı
- Prepared statements (SQL injection koruması)
- Transaction desteği
- İndeks optimizasyonu
- Foreign key ilişkileri

### 🔍 Arama ve Filtreleme
- Kullanıcı arama
- Etkinlik filtreleme
- Toplantı arama
- BYK bazlı filtreleme
- Tarih aralığı filtreleme
- Durum bazlı filtreleme

### 📧 E-posta Sistemi
- SMTP desteği
- HTML email şablonları
- Toplantı daveti email'leri
- Toplantı hatırlatma email'leri
- İzin belgesi talep onay/red email'leri
- İzin belgesi hazır olduğunda bildirim email'i
- PDF izin belgesi email ile gönderme
- Otomatik email gönderimi
- Email gönderim logları

### 🔔 Bildirim Sistemi
- Tarayıcı bildirimleri
- In-app bildirimler
- Bildirim geçmişi
- Okunmamış bildirim sayacı
- Bildirim kategorilendirme

### 🌐 Deployment ve GitHub Özellikleri

#### 🚀 GitHub Actions ile Otomatik Deployment
- **Otomatik Deployment**:
  - `main` branch'e push olduğunda otomatik FTP deployment
  - Manuel deployment tetikleme (workflow_dispatch)
  - Ubuntu latest runner kullanımı
  - Checkout action ile kod çekme
  
- **FTP Deployment**:
  - FTP-Deploy-Action kullanımı (SamKirkland/FTP-Deploy-Action@4.3.3)
  - GitHub Secrets ile güvenli FTP bilgileri
  - Local ve server directory yapılandırması
  - Dosya exclude listesi (güvenlik dosyaları hariç)
  
- **GitHub Secrets Yönetimi**:
  - **FTP_SERVER**: FTP sunucu adresi (`aifcrm.metechnik.at` veya `w01dc0ea.kasserver.com`)
  - **FTP_USERNAME**: FTP kullanıcı adı (`d0451622` veya `f017c2cc`)
  - **FTP_PASSWORD**: FTP şifresi (güvenli saklama)
  - Secrets otomatik şifreleme ve güvenli erişim
  
- **Deployment Exclude Listesi**:
  - `.git*` dosyaları ve klasörleri
  - `node_modules/` klasörü
  - `.env` dosyaları
  - `README.md` dosyaları
  - `composer.json/lock` dosyaları
  - `package.json/lock` dosyaları
  - `.github/` workflow klasörü
  - Debug ve test dosyaları
  - SQL schema dosyaları
  - Güvenlik ve yapılandırma dokümantasyonları
  
- **Deployment Adımları**:
  1. **Checkout code**: Repository'den kod çekme
  2. **List files**: Deployment öncesi dosya listeleme
  3. **Deploy to FTP**: FTP'ye dosya yükleme
  
- **Workflow Yapılandırması**:
  - Workflow dosyası: `.github/workflows/deploy.yml`
  - Trigger: `push` (main branch)
  - Manuel çalıştırma: `workflow_dispatch`
  - Runner: `ubuntu-latest`
  
- **Git Repository Yönetimi**:
  - GitHub repository: `https://github.com/XezMetITSolutions/AIF-Otomasyon`
  - Branch yönetimi: `main` ve `master` branch'leri
  - Git commit ve push işlemleri
  - Branch koruma kuralları (gelecekte)
  
- **Deployment Güvenliği**:
  - Secrets kullanımı (şifreler açıkta değil)
  - Exclude listesi ile hassas dosya koruması
  - HTTPS bağlantı desteği
  - Git repository access control
  
- **Deployment Monitoring**:
  - GitHub Actions workflow durumu takibi
  - Deployment başarı/hata logları
  - Actions sekmesinde görüntüleme
  - Email bildirimleri (gelecekte)
  
- **Alternatif Deployment Yöntemleri**:
  - **Webhook Tabanlı**: GitHub webhook ile PHP script tetikleme
  - **Cron Job**: Zamanlanmış Git pull işlemleri
  - **GitHub App**: DeployBot, Netlify, Vercel gibi servisler
  - **Manuel FTP**: FileZilla gibi FTP client'lar ile manuel yükleme
  
- **Deployment Troubleshooting**:
  - FTP bağlantı hata kontrolü
  - Secrets doğrulama
  - Workflow log analizi
  - Dosya yolu kontrolü
  - Server directory kontrolü
  
#### 🔐 SSL ve Güvenlik
- SSL sertifika desteği (Let's Encrypt)
- HTTPS zorunlu yönlendirme
- Güvenlik başlıkları (Security headers)
- Mixed content kontrolü
  
#### 📦 Environment Konfigürasyonu
- Production/Development ortam ayarları
- Environment variables yönetimi
- Config dosyası ayrımı
- Debug mode kontrolü
  
#### 💾 Yedekleme Özellikleri (Gelecekte)
- Otomatik veritabanı yedekleme
- Dosya yedekleme
- Yedekleme zamanlaması
- Yedekleme saklama politikası

### 📈 İstatistikler ve Analizler
- Kullanıcı istatistikleri
- Etkinlik istatistikleri
- Toplantı katılım istatistikleri
- İzin belgesi talep istatistikleri
  - Ulaşım türü bazlı dağılım
  - Aylık/haftalık talep trendleri
  - En çok seyahat edilen yerler
  - Talep onay/red oranları
- Sistem kullanım analizi
- Büyüme trendleri

### 🔐 Güvenlik Özellikleri
- XSS koruması
- SQL Injection koruması
- CSRF token koruması
- Şifre güç kontrolü
- Session timeout
- Maksimum giriş denemesi kontrolü
- Güvenlik başlıkları (Security headers)

### 🎨 Tema ve Özelleştirme
- BYK bazlı renk kodlaması
- Özelleştirilebilir logo
- Renk şemaları
- Responsive grid sistemi

### 📝 Log ve Hata Yönetimi
- Hata loglama sistemi
- Debug modu
- Hata raporlama
- Sistem log dosyaları

### 🔄 Otomasyon Özellikleri
- Otomatik email gönderimi
- Otomatik hatırlatmalar
- Cron job desteği
- Zamanlanmış görevler

## 🏢 BYK Yapısı

Sistem 4 ana BYK (Bölge Yönetim Kurulu) kategorisi üzerine kuruludur:

1. **AT - Ana Teşkilat** (Kırmızı #dc3545)
2. **KT - Kadınlar Teşkilatı** (Mor #6f42c1)
3. **KGT - Kadınlar Gençlik Teşkilatı** (Koyu Yeşil #198754)
4. **GT - Gençlik Teşkilatı** (Mavi #0d6efd)

Her BYK'nın 17 alt birimi bulunmaktadır.

## 👤 Kullanıcı Rolleri

1. **Superadmin**: Tam yetkili yönetici
   - Tüm modüllere erişim
   - Kullanıcı yönetimi
   - Yetki yönetimi
   - Sistem ayarları

2. **Manager**: BYK yöneticisi
   - Kendi BYK'sına özel yetkiler
   - Kullanıcı yönetimi (sınırlı)
   - Rapor görüntüleme

3. **Member**: Üye
   - Sınırlı erişim
   - Kendi bilgilerini görüntüleme
   - Duyuru ve etkinlik görüntüleme
   - Rezervasyon yapma
   - İade talebi oluşturma
   - İzin belgesi talep formu doldurma ve gönderme
   - Kendi izin taleplerini görüntüleme ve takip etme
   - Onaylanan izin belgelerini görüntüleme ve indirme

## 📦 Teknolojiler

- **Backend**: PHP 8.2
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3.0
- **JavaScript Libraries**: jQuery 3.7.1
- **Charts**: Chart.js
- **Icons**: Font Awesome 6.4.0
- **Animations**: AOS (Animate On Scroll)
- **Database**: MySQL 8.0+
- **Email**: SMTP (PHP mail())
- **Version Control**: Git, GitHub
- **Deployment**: GitHub Actions, FTP

## 🚀 Gelecek Özellikler

- [ ] SMS bildirimleri
- [ ] Mobil uygulama (React Native)
- [ ] WhatsApp entegrasyonu
- [ ] Video konferans entegrasyonu
- [ ] OCR ile belge okuma
- [ ] AI destekli raporlama
- [ ] Çoklu dil desteği
- [ ] Gelişmiş analitik dashboard
- [ ] API dokümantasyonu (Swagger)
- [ ] Unit ve integration testleri
- [ ] İzin belgesi mobil uygulama entegrasyonu
- [ ] İzin belgesi dijital imza desteği
- [ ] Toplu izin belgesi oluşturma (grup seyahatleri için)

## 📞 Destek ve Dokümantasyon

- **Kurulum Rehberi**: `admin/KURULUM_REHBERI.md`
- **Toplantı Katılım Sistemi**: `admin/TOPLANTI_KATILIM_SISTEMI.md`
- **İzin Belgesi Sistemi**: (Yakında eklenecek)
- **GitHub-FTP Entegrasyon Rehberi**: `GITHUB_FTP_REHBERI.md`
- **GitHub Actions Troubleshooting**: `GITHUB_ACTIONS_FIX.md`
- **GitHub Secrets Rehberi**: `GITHUB_SECRETS_REHBERI.md`
- **FTP Error Fix**: `FTP_ERROR_FIX.md`
- **Deployment Test**: `DEPLOYMENT_TEST.md`
- **GitHub Actions Workflow**: `.github/workflows/deploy.yml`
- **README**: `README.md`

## 🔧 GitHub Repository Bilgileri

- **Repository URL**: `https://github.com/XezMetITSolutions/AIF-Otomasyon`
- **Default Branch**: `main`
- **Workflow File**: `.github/workflows/deploy.yml`
- **GitHub Actions**: Otomatik FTP deployment aktif

### 📋 GitHub Secrets Ayarları

GitHub repository'de aşağıdaki secrets'ların ayarlanması gereklidir:

1. **FTP_SERVER**: 
   - `aifcrm.metechnik.at` veya
   - `w01dc0ea.kasserver.com`

2. **FTP_USERNAME**: 
   - `d0451622` veya
   - `f017c2cc`

3. **FTP_PASSWORD**: 
   - FTP şifresi (güvenli saklama)

### 🚀 Deployment Kullanımı

#### Otomatik Deployment:
```bash
git add .
git commit -m "Deployment mesajı"
git push origin main
```

#### Manuel Deployment:
1. GitHub Repository → **Actions** sekmesi
2. **Deploy to FTP** workflow'unu bul
3. **Run workflow** butonuna tıkla
4. **main** branch seç
5. **Run workflow** ile başlat

### 📊 Deployment Durumu

- GitHub Actions sekmesinden deployment durumu takip edilebilir
- Başarılı deployment'lar yeşil ✅ işareti ile gösterilir
- Hatalı deployment'lar kırmızı ❌ işareti ile gösterilir
- Log kayıtları detaylı hata mesajları içerir

---

**Son Güncelleme**: Kasım 2025
**Versiyon**: 1.0.0

