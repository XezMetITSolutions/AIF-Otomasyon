<?php
/**
 * AİFNET - Veritabanı Kurulum Scripti
 * Bu script veritabanını oluşturur ve şemayı yükler
 */

// Veritabanı Bilgileri
$DB_HOST = 'localhost';
$DB_NAME = 'd0451622';
$DB_USER = 'd0451622';
$DB_PASS = '01528797Mb##';

// Schema dosyası
$SCHEMA_FILE = __DIR__ . '/../database/schema.sql';

echo "🚀 AİFNET - Veritabanı Kurulum\n";
echo "==========================================\n\n";

try {
    // 1. MySQL bağlantısı (veritabanı olmadan)
    echo "📡 MySQL sunucusuna bağlanılıyor...\n";
    $pdo = new PDO(
        "mysql:host={$DB_HOST};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✅ MySQL bağlantısı başarılı!\n\n";

    // 2. Veritabanının var olup olmadığını kontrol et
    echo "🔍 Veritabanı kontrol ediliyor: {$DB_NAME}...\n";
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$DB_NAME}'");
    $dbExists = $stmt->rowCount() > 0;

    if ($dbExists) {
        echo "⚠️  Veritabanı zaten mevcut!\n";
        $response = readline("Silmek ve yeniden oluşturmak ister misiniz? (e/h): ");
        if (strtolower($response) === 'e' || strtolower($response) === 'y' || strtolower($response) === 'yes') {
            echo "🗑️  Mevcut veritabanı siliniyor...\n";
            $pdo->exec("DROP DATABASE IF EXISTS `{$DB_NAME}`");
            echo "✅ Veritabanı silindi.\n\n";
            $dbExists = false;
        } else {
            echo "ℹ️  Mevcut veritabanı kullanılacak.\n\n";
        }
    }

    // 3. Veritabanını oluştur
    if (!$dbExists) {
        echo "📦 Veritabanı oluşturuluyor: {$DB_NAME}...\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Veritabanı oluşturuldu!\n\n";
    }

    // 4. Veritabanını seç
    $pdo->exec("USE `{$DB_NAME}`");
    echo "✅ Veritabanı seçildi: {$DB_NAME}\n\n";

    // 5. Schema dosyasını yükle
    if (!file_exists($SCHEMA_FILE)) {
        throw new Exception("Schema dosyası bulunamadı: {$SCHEMA_FILE}");
    }

    echo "📂 Schema dosyası okunuyor: {$SCHEMA_FILE}...\n";
    $schema = file_get_contents($SCHEMA_FILE);

    // Veritabanı oluşturma satırını çıkar
    $schema = preg_replace('/CREATE DATABASE.*?;/i', '', $schema);
    $schema = preg_replace('/USE.*?;/i', '', $schema);

    echo "⚙️  SQL komutları çalıştırılıyor...\n";

    // SQL komutlarını böl ve çalıştır
    $statements = explode(';', $schema);
    $executed = 0;
    $skipped = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);

        // Boş veya yorum satırlarını atla
        if (
            empty($statement) ||
            preg_match('/^\s*--/', $statement) ||
            preg_match('/^\s*\/\*/', $statement) ||
            preg_match('/^\s*SET/', $statement)
        ) {
            $skipped++;
            continue;
        }

        try {
            $pdo->exec($statement);
            $executed++;

            // Tablo oluşturma mesajları
            if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches)) {
                echo "   ✅ Tablo oluşturuldu: {$matches[1]}\n";
            }

            // INSERT mesajları
            if (preg_match('/INSERT INTO.*?`(\w+)`/i', $statement, $matches)) {
                echo "   ✅ Veri eklendi: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            // Bazı hatalar normal olabilir (tablo zaten varsa vb.)
            if (
                strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'Duplicate') === false
            ) {
                echo "   ⚠️  Uyarı: " . substr($e->getMessage(), 0, 100) . "\n";
            }
        }
    }

    echo "\n✅ Schema yükleme tamamlandı!\n";
    echo "   📊 Çalıştırılan: {$executed} komut\n";
    echo "   ⏭️  Atlanan: {$skipped} satır\n\n";

    // 6. Kontrol ve özet
    echo "🔍 Veritabanı durumu kontrol ediliyor...\n";

    // Tablo sayısı
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   📋 Toplam tablo sayısı: " . count($tables) . "\n";

    // Kullanıcı sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM kullanicilar");
    $userCount = $stmt->fetchColumn();
    echo "   👥 Kullanıcı sayısı: {$userCount}\n";

    // BYK sayısı
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM byk");
        $bykCount = $stmt->fetchColumn();
        echo "   🏢 BYK sayısı: {$bykCount}\n";
    } catch (PDOException $e) {
        // Tablo yoksa atla
    }

    // Rol sayısı
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM roller");
        $roleCount = $stmt->fetchColumn();
        echo "   🔐 Rol sayısı: {$roleCount}\n";
    } catch (PDOException $e) {
        // Tablo yoksa atla
    }

    echo "\n";
    echo "🎉 Veritabanı kurulumu başarıyla tamamlandı!\n\n";
    echo "📝 Önemli Bilgiler:\n";
    echo "   🗄️  Veritabanı: {$DB_NAME}\n";
    echo "   🌐 Host: {$DB_HOST}\n";
    echo "   👤 Kullanıcı: {$DB_USER}\n";
    echo "\n";
    echo "🔐 Varsayılan Admin Hesabı:\n";
    echo "   📧 E-posta: admin@aif.org\n";
    echo "   🔑 Şifre: Admin123!\n";
    echo "   ⚠️  İlk girişte şifre değiştirme zorunludur!\n\n";

    // 7. config/database.php dosyasını güncelle
    $configFile = __DIR__ . '/../config/database.php';
    if (file_exists($configFile)) {
        echo "⚙️  Yapılandırma dosyası güncelleniyor...\n";

        $configContent = file_get_contents($configFile);

        // Mevcut değerleri güncelle
        $configContent = preg_replace(
            "/'host' => '.*?'/",
            "'host' => '{$DB_HOST}'",
            $configContent
        );
        $configContent = preg_replace(
            "/'dbname' => '.*?'/",
            "'dbname' => '{$DB_NAME}'",
            $configContent
        );
        $configContent = preg_replace(
            "/'username' => '.*?'/",
            "'username' => '{$DB_USER}'",
            $configContent
        );
        $configContent = preg_replace(
            "/'password' => '.*?'/",
            "'password' => '{$DB_PASS}'",
            $configContent
        );

        file_put_contents($configFile, $configContent);
        echo "✅ Yapılandırma dosyası güncellendi: {$configFile}\n\n";
    }

    echo "✅ Tüm işlemler tamamlandı!\n";
    echo "🚀 Sistem kullanıma hazır!\n\n";

} catch (PDOException $e) {
    echo "\n❌ Veritabanı Hatası:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);

} catch (Exception $e) {
    echo "\n❌ Hata:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}

