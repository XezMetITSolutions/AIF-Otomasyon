<?php
// Veritabanı bağlantı ayarları - Farklı sunucu konfigürasyonları için
$db_configs = [
    // Canlı sunucu (metechnik.at) - GERÇEK BİLGİLER
    'production' => [
        'host' => 'localhost',
        'name' => 'd0451622',
        'user' => 'd0451622',
        'pass' => '01528797Mb##'
    ],
    // Yerel geliştirme ortamı
    'local' => [
        'host' => 'localhost',
        'name' => 'aif_otomasyon',
        'user' => 'root',
        'pass' => ''
    ],
    // Alternatif konfigürasyonlar
    'alternative1' => [
        'host' => 'localhost',
        'name' => 'aifcrm_db',
        'user' => 'aifcrm_user',
        'pass' => 'aifcrm_pass'
    ]
];

// Otomatik konfigürasyon seçimi
function getDBConfig() {
    global $db_configs;
    
    // Önce environment variable kontrol et
    $env = getenv('DB_ENV') ?: 'production';
    
    // Mevcut konfigürasyonları dene
    foreach ($db_configs as $key => $config) {
        try {
            $dsn = "mysql:host={$config['host']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Veritabanının var olup olmadığını kontrol et
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['name']}'");
            if ($stmt->rowCount() > 0) {
                return $config;
            }
        } catch (Exception $e) {
            continue; // Sonraki konfigürasyonu dene
        }
    }
    
    // Hiçbiri çalışmazsa varsayılan döndür
    return $db_configs['production'];
}

// PDO bağlantısı
function getDBConnection() {
    try {
        $config = getDBConfig();
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
        
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        return $pdo;
    } catch (PDOException $e) {
        // Detaylı hata mesajı
        $error_msg = "Veritabanı bağlantı hatası: " . $e->getMessage();
        error_log($error_msg);
        
        // Kullanıcı dostu hata mesajı
        throw new Exception("Veritabanı bağlantısı kurulamadı. Lütfen sistem yöneticisi ile iletişime geçin.");
    }
}

// Veritabanı ve tabloları oluştur
function createDatabaseAndTables() {
    try {
        $config = getDBConfig();
        
        // Önce veritabanına bağlan (veritabanı adı olmadan)
        $dsn = "mysql:host={$config['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Veritabanını oluştur (eğer yoksa)
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$config['name']}`");
        
        // Tabloları oluştur
        createTablesIfNotExists($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Veritabanı oluşturma hatası: " . $e->getMessage());
        throw new Exception("Veritabanı oluşturulamadı: " . $e->getMessage());
    }
}

// Giderler tablosu yapısı (eğer yoksa oluştur)
function createTablesIfNotExists($pdo) {
    // expenses tablosu
    $expensesTable = "
        CREATE TABLE IF NOT EXISTS expenses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            isim VARCHAR(100) NOT NULL,
            soyisim VARCHAR(100) NOT NULL,
            iban VARCHAR(34) NOT NULL,
            total DECIMAL(10,2) NOT NULL DEFAULT 0,
            status ENUM('pending', 'paid', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    // expense_items tablosu
    $expenseItemsTable = "
        CREATE TABLE IF NOT EXISTS expense_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            expense_id INT NOT NULL,
            tarih DATE NOT NULL,
            region VARCHAR(10),
            birim VARCHAR(50),
            birim_label VARCHAR(100),
            gider_turu VARCHAR(50),
            gider_turu_label VARCHAR(100),
            tutar DECIMAL(10,2) NOT NULL DEFAULT 0,
            aciklama TEXT,
            attachments JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
            INDEX idx_expense_id (expense_id),
            INDEX idx_tarih (tarih),
            INDEX idx_birim (birim)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    // users tablosu (eğer yoksa)
    $usersTable = "
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            full_name VARCHAR(200) NOT NULL,
            email VARCHAR(255) UNIQUE,
            phone VARCHAR(20),
            department VARCHAR(50),
            unit VARCHAR(50),
            byk VARCHAR(10),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_department (department)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    try {
        $pdo->exec($expensesTable);
        $pdo->exec($expenseItemsTable);
        $pdo->exec($usersTable);
        
        // Örnek veri ekle (eğer tablo boşsa)
        $stmt = $pdo->query("SELECT COUNT(*) FROM expenses");
        if ($stmt->fetchColumn() == 0) {
            // Örnek kullanıcı
            $pdo->exec("INSERT INTO users (full_name, email, department, unit, byk) VALUES ('Semra Yıldız', 'semra@example.com', 'İrşad', 'İrşad', 'KT')");
            
            // Örnek gider
            $pdo->exec("INSERT INTO expenses (user_id, isim, soyisim, iban, total, status) VALUES (1, 'Semra', 'Yıldız', 'AT862060200001095074', 96.98, 'pending')");
            
            // Örnek gider kalemi
            $pdo->exec("INSERT INTO expense_items (expense_id, tarih, region, birim, birim_label, gider_turu, gider_turu_label, tutar, aciklama) VALUES (1, '2025-10-15', 'KT', 'irsad', 'İrşad', 'diger', 'Diğer', 96.98, 'Çeşitli giderler')");
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Tablo oluşturma hatası: " . $e->getMessage());
        return false;
    }
}

// Bağlantı testi
function testConnection() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>