<?php
/**
 * Uygulama Yapılandırması
 * AIF Otomasyon Sistemi
 */

return [
    'app_name' => 'AIFNET',
    'app_version' => '1.0.1',
    'app_url' => 'http://aifnet.islamfederasyonu.at',
    'timezone' => 'Europe/Vienna',
    'locale' => 'tr_TR',
    'date_format' => 'd.m.Y',
    'datetime_format' => 'd.m.Y H:i',
    
    // SMTP Ayarları
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_email' => 'noreply@aif.org',
        'from_name' => 'AIF Otomasyon Sistemi'
    ],
    
    // Güvenlik Ayarları
    'security' => [
        'session_lifetime' => 7200, // 2 saat (saniye)
        'password_min_length' => 8,
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

