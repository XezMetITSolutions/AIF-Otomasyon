<?php
/**
 * Uygulama Başlatma Dosyası
 * Tüm sayfalarda en üstte include edilmelidir
 */

// Hata raporlama (geliştirme ortamı için)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Environment variables loader
require_once __DIR__ . '/load_env.php';

// Core sınıfları yükle
// Bu sınıflar autoloader tarafından yüklenmeyebilir veya erken yüklenmesi gerekebilir.
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Config.php';

// Zaman dilimi ayarı
date_default_timezone_set('Europe/Vienna');

// Autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Yapılandırma dosyalarını yükle
require_once __DIR__ . '/../config/app.php';

