<?php
/**
 * Veritabanı Bağlantı Yapılandırması
 * AIF Otomasyon Sistemi
 */

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'dbname' => getenv('DB_NAME') ?: 'd045d2b0',
    'username' => getenv('DB_USER') ?: 'd045d2b0',
    'password' => getenv('DB_PASS') ?: '01528797Mb##',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
