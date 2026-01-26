<?php
/**
 * Uygulama Yapılandırması
 * AIF Otomasyon Sistemi
 */

// Versiyon kontrolü
$appVersion = (class_exists('Config')) ? Config::get('app_version', '1.0.1') : '1.0.1';
$appName = (class_exists('Config')) ? Config::get('app_name', 'AIF Otomasyon Sistemi') : 'AIF Otomasyon Sistemi';
$appUrl = (class_exists('Config')) ? Config::get('app_url', 'https://aifnet.islamfederasyonu.at') : 'https://aifnet.islamfederasyonu.at';

return [
    'app_name' => $appName,
    'app_version' => $appVersion,
    'app_url' => $appUrl,
    'timezone' => 'Europe/Vienna',
    'locale' => 'tr_TR',
    'date_format' => 'd.m.Y',
    'datetime_format' => 'd.m.Y H:i',

    // SMTP Ayarları
    'smtp' => [
        'host' => (class_exists('Config')) ? Config::get('smtp_host', 'w0072b78.kasserver.com') : 'w0072b78.kasserver.com',
        'port' => (class_exists('Config')) ? Config::get('smtp_port', 587) : 587,
        'username' => (class_exists('Config')) ? Config::get('smtp_user', 'sitzung@islamischefoederation.at') : 'sitzung@islamischefoederation.at',
        'password' => getenv('MAIL_PASS') ?: '', // Hassas bilgi .env'den
        'secure' => (class_exists('Config')) ? Config::get('smtp_secure', 'tls') : 'tls',
        'from_email' => (class_exists('Config')) ? Config::get('smtp_from_email', 'sitzung@islamischefoederation.at') : 'sitzung@islamischefoederation.at',
        'from_name' => (class_exists('Config')) ? Config::get('smtp_from_name', 'AİF Otomasyon') : 'AİF Otomasyon'
    ],

    // Güvenlik Ayarları
    'security' => [
        'session_lifetime' => (class_exists('Config')) ? Config::get('session_lifetime', 7200) : 7200,
        'password_min_length' => (class_exists('Config')) ? Config::get('min_password_length', 8) : 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 dakika
        'csrf_token_name' => 'csrf_token'
    ],

    // Dosya Yükleme Ayarları
    'upload' => [
        'max_file_size' => 5242880, // 5MB
        'allowed_image_types' => ['image/jpeg', 'image/png', 'image/gif'],
        'allowed_document_types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    ],

    // Sayfalama
    'pagination' => [
        'items_per_page' => 20
    ]
];

