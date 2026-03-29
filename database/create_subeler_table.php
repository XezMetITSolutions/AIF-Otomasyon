<?php
/**
 * Şubeler tablosunu oluşturan ve verileri ekleyen migration scripti
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "🚀 Şubeler tablosu oluşturuluyor...\n";

try {
    // 1. Tabloyu oluştur
    $dsn = "mysql:host=127.0.0.1;dbname=d045d2b0;charset=utf8mb4";
    $pdo = new PDO($dsn, 'd045d2b0', '01528797Mb##');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->query("CREATE TABLE IF NOT EXISTS subeler (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sube_adi VARCHAR(255) NOT NULL,
        adres VARCHAR(500) NOT NULL,
        sehir VARCHAR(100),
        posta_kodu VARCHAR(20),
        aktif TINYINT(1) DEFAULT 1,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "✅ Tablo oluşturuldu.\n";

    // 2. Verileri ekle
    $subeler = [
        ['ad' => 'AİF Bludenz', 'adres' => 'Walserweg 1, 6700 Bludenz', 'sehir' => 'Bludenz', 'posta_kodu' => '6700'],
        ['ad' => 'AİF Bregenz', 'adres' => 'Arlbergstraße 114c, 6900 Bregenz', 'sehir' => 'Bregenz', 'posta_kodu' => '6900'],
        ['ad' => 'AİF Dornbirn', 'adres' => 'Schwefel 68, 6850 Dornbirn', 'sehir' => 'Dornbirn', 'posta_kodu' => '6850'],
        ['ad' => 'AİF Feldkirch', 'adres' => 'Amberggasse 10, 6800 Feldkirch', 'sehir' => 'Feldkirch', 'posta_kodu' => '6800'],
        ['ad' => 'AİF Lustenau', 'adres' => 'Kneippstraße 6, 6890 Lustenau', 'sehir' => 'Lustenau', 'posta_kodu' => '6890'],
        ['ad' => 'AİF Radfeld', 'adres' => 'Innstraße 27d, 6241 Radfeld', 'sehir' => 'Radfeld', 'posta_kodu' => '6241'],
        ['ad' => 'AİF Hall in Tirol', 'adres' => 'Beheimstraße 3, 6060 Hall in Tirol', 'sehir' => 'Hall in Tirol', 'posta_kodu' => '6060'],
        ['ad' => 'AİF Innsbruck', 'adres' => 'Sterzingerstraße 6, 6020 Innsbruck', 'sehir' => 'Innsbruck', 'posta_kodu' => '6020'],
        ['ad' => 'AİF Jenbach', 'adres' => 'Achenseestraße 67, 6200 Jenbach', 'sehir' => 'Jenbach', 'posta_kodu' => '6200'],
        ['ad' => 'AİF Reutte', 'adres' => 'Schulstraße 7a, 6600 Reutte', 'sehir' => 'Reutte', 'posta_kodu' => '6600'],
        ['ad' => 'AİF Vomp', 'adres' => 'Feldweg 16, 6134 Vomp', 'sehir' => 'Vomp', 'posta_kodu' => '6134'],
        ['ad' => 'AİF Wörgl', 'adres' => 'Peter-Anich-Straße 6, 6300 Wörgl', 'sehir' => 'Wörgl', 'posta_kodu' => '6300'],
        ['ad' => 'AİF Zirl', 'adres' => 'Meilstraße 28, 6171 Zirl', 'sehir' => 'Zirl', 'posta_kodu' => '6171'],
    ];

    $inserted = 0;
    foreach ($subeler as $s) {
        // Zaten var mı kontrol et
        $exists = $db->fetch("SELECT id FROM subeler WHERE sube_adi = ?", [$s['ad']]);
        if (!$exists) {
            $db->query("INSERT INTO subeler (sube_adi, adres, sehir, posta_kodu) VALUES (?, ?, ?, ?)", 
                [$s['ad'], $s['adres'], $s['sehir'], $s['posta_kodu']]);
            $inserted++;
        }
    }

    echo "✅ $inserted yeni şube eklendi.\n";
    echo "\n✨ İşlem başarıyla tamamlandı.\n";

} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
