<?php
/**
 * AIF Otomasyon Konfigürasyon Dosyası
 * Web hosting'e yüklerken bu dosyayı düzenleyin
 */

// Veritabanı Ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'd0451622'); // Web hosting'deki veritabanı adı
define('DB_USER', 'd0451622'); // Web hosting'deki kullanıcı adı
define('DB_PASS', '01528797Mb##'); // Web hosting'deki şifre
define('DB_CHARSET', 'utf8mb4');

// Site Ayarları
define('SITE_NAME', 'AIF Otomasyon');
define('SITE_URL', 'https://aifcrm.metechnik.at'); // Web sitesi URL'i
define('SITE_DESCRIPTION', 'AIF Otomasyon Sistemi');

// Güvenlik Ayarları
define('SESSION_TIMEOUT', 60); // Dakika
define('MAX_LOGIN_ATTEMPTS', 5);
define('PASSWORD_MIN_LENGTH', 8);
define('CSRF_TOKEN_EXPIRE', 3600); // Saniye

// Dosya Yükleme Ayarları
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', 'uploads/');

// E-posta Ayarları (SMTP)
define('SMTP_HOST', 'smtp.gmail.com'); // Web hosting'de değiştirilecek
define('SMTP_PORT', 587);
define('SMTP_USERNAME', ''); // E-posta kullanıcı adı
define('SMTP_PASSWORD', ''); // E-posta şifresi
define('SMTP_FROM_EMAIL', 'noreply@aifcrm.metechnik.at');
define('SMTP_FROM_NAME', 'AIF Otomasyon');

// Hata Raporlama
define('DEBUG_MODE', false); // Production'da false yapın
define('LOG_ERRORS', true);
define('LOG_PATH', 'logs/');

// Zaman Dilimi
date_default_timezone_set('Europe/Vienna');

// Hata Raporlama Ayarları
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Log klasörünü oluştur
if (LOG_ERRORS && !is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Upload klasörünü oluştur
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Güvenlik başlıkları
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS ayarları (gerekirse)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');

/**
 * Web hosting'e yüklerken yapılacaklar:
 * 
 * 1. Veritabanı ayarlarını güncelleyin:
 *    - DB_HOST: Genellikle 'localhost'
 *    - DB_NAME: Hosting sağlayıcınızdan aldığınız veritabanı adı
 *    - DB_USER: Veritabanı kullanıcı adı
 *    - DB_PASS: Veritabanı şifresi
 * 
 * 2. E-posta ayarlarını güncelleyin:
 *    - SMTP_HOST: Hosting sağlayıcınızın SMTP sunucusu
 *    - SMTP_USERNAME: E-posta kullanıcı adı
 *    - SMTP_PASSWORD: E-posta şifresi
 * 
 * 3. Site URL'ini güncelleyin:
 *    - SITE_URL: Gerçek web sitesi adresi
 * 
 * 4. DEBUG_MODE'u false yapın
 * 
 * 5. Veritabanını oluşturun:
 *    - database_schema.sql dosyasını çalıştırın
 *    - database_seed.sql dosyasını çalıştırın
 */
?>
