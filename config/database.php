<?php
/**
 * Veritabanı Bağlantı Yapılandırması
 * AIF Otomasyon Sistemi
 */

return [
    'host' => 'localhost',
    'dbname' => 'd0451622',
    'username' => 'd0451622',
    'password' => '01528797Mb##',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
