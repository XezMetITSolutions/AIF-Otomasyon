<?php
/**
 * Veritabanı Bağlantı Yapılandırması
 * AIF Otomasyon Sistemi
 */

return [
    'host' => 'localhost',
    'dbname' => 'd045d2b0',
    'username' => 'd045d2b0',
    'password' => '01528797Mb##',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
