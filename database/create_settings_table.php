<?php
/**
 * Sistem Ayarları Tablosu Oluşturma
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "Sistem Ayarları Tablosu Oluşturuluyor...\n";

try {
    $db->query("
        CREATE TABLE IF NOT EXISTS `sistem_ayarlari` (
            `ayar_key` VARCHAR(50) NOT NULL,
            `ayar_value` TEXT NULL,
            `ayar_grup` VARCHAR(20) DEFAULT 'genel',
            `aciklama` VARCHAR(255) NULL,
            PRIMARY KEY (`ayar_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $ayarlar = [
        ['app_name', 'AİFNET', 'genel', 'Uygulama Adı'],
        ['app_url', 'https://aifnet.islamfederasyonu.at', 'genel', 'Uygulama URL'],
        ['app_version', '1.0.1', 'genel', 'Versiyon'],
        ['smtp_host', 'w0072b78.kasserver.com', 'smtp', 'SMTP Sunucu'],
        ['smtp_port', '587', 'smtp', 'SMTP Port'],
        ['smtp_user', 'aifnet@islamischefoederation.at', 'smtp', 'SMTP Kullanıcı'],
        ['smtp_secure', 'tls', 'smtp', 'SMTP Güvenlik'],
        ['smtp_from_email', 'aifnet@islamischefoederation.at', 'smtp', 'Gönderen E-posta'],
        ['smtp_from_name', 'AİFNET', 'smtp', 'Gönderen Adı'],
        ['session_lifetime', '7200', 'guvenlik', 'Oturum Süresi'],
        ['min_password_length', '8', 'guvenlik', 'Min. Şifre Uzunluğu'],
        ['theme_color', '#00936F', 'tema', 'Tema Rengi']
    ];

    foreach ($ayarlar as $a) {
        $db->query("
            INSERT IGNORE INTO `sistem_ayarlari` (ayar_key, ayar_value, ayar_grup, aciklama)
            VALUES (?, ?, ?, ?)
        ", $a);
    }

    echo "✅ İşlem Başarıyla Tamamlandı.\n";
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
