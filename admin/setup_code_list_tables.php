<?php
/**
 * Code List Tabloları Kurulum Scripti
 * Veritabanı bilgileri config.php'den alınır
 */

// Hata raporlamayı aç
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Code List Tabloları Kurulumu</h2>";
echo "<p><strong>Domain:</strong> aifcrm.metechnik.at</p>";

try {
    // Config dosyasını yükle
    require_once 'config.php';
    
    // Veritabanı bağlantısını test et
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "<div class='success'>✓ Veritabanı bağlantısı başarılı</div>";
    
    // Database sınıfını oluştur
    class Database {
        private static $instance = null;
        private $pdo;
        
        private function __construct() {
            global $pdo;
            $this->pdo = $pdo;
        }
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function query($sql, $params = []) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        }
        
        public function fetch($sql, $params = []) {
            $stmt = $this->query($sql, $params);
            return $stmt->fetch();
        }
        
        public function fetchAll($sql, $params = []) {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll();
        }
        
        public function insert($table, $data) {
            $fields = array_keys($data);
            $placeholders = ':' . implode(', :', $fields);
            $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        }
        
        public function update($table, $data, $where) {
            $fields = [];
            foreach ($data as $key => $value) {
                $fields[] = "{$key} = :{$key}";
            }
            
            $whereClause = [];
            foreach ($where as $key => $value) {
                $whereClause[] = "{$key} = :where_{$key}";
            }
            
            $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE " . implode(' AND ', $whereClause);
            
            $params = array_merge($data, array_combine(
                array_map(function($key) { return "where_{$key}"; }, array_keys($where)),
                array_values($where)
            ));
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        }
        
        public function delete($table, $where) {
            $whereClause = [];
            foreach ($where as $key => $value) {
                $whereClause[] = "{$key} = :{$key}";
            }
            
            $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClause);
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($where);
        }
    }
    
    $db = Database::getInstance();
    
    // BYK Kategorileri tablosu
    $db->query("
        CREATE TABLE IF NOT EXISTS byk_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(10) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Görevler tablosu
    $db->query("
        CREATE TABLE IF NOT EXISTS positions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            level ENUM('1', '2', '3') DEFAULT '2',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Alt Birimler tablosu
    $db->query("
        CREATE TABLE IF NOT EXISTS sub_units (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            byk_category_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (byk_category_id) REFERENCES byk_categories(id) ON DELETE SET NULL
        )
    ");
    
    // Gider Türleri tablosu
    $db->query("
        CREATE TABLE IF NOT EXISTS expense_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            category VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    
    // Varsayılan verileri ekle
    $defaultData = [
        'byk_categories' => [
            ['code' => 'AT', 'name' => 'Ana Teşkilat', 'description' => 'Ana teşkilat birimi'],
            ['code' => 'KT', 'name' => 'Kadınlar Teşkilatı', 'description' => 'Kadınlar teşkilatı'],
            ['code' => 'KGT', 'name' => 'Kadınlar Gençlik Teşkilatı', 'description' => 'Kadınlar gençlik teşkilatı'],
            ['code' => 'GT', 'name' => 'Gençlik Teşkilatı', 'description' => 'Gençlik teşkilatı']
        ],
        'positions' => [
            ['name' => 'Bölge Başkanı', 'description' => 'Bölge başkanı görevi', 'level' => '1'],
            ['name' => 'Teşkilatlanma Başkanı', 'description' => 'Teşkilatlanma başkanı görevi', 'level' => '2'],
            ['name' => 'Eğitim Başkanı', 'description' => 'Eğitim başkanı görevi', 'level' => '2'],
            ['name' => 'İrşad Başkanı', 'description' => 'İrşad başkanı görevi', 'level' => '2'],
            ['name' => 'Kurumsal İletişim Başkanı', 'description' => 'Kurumsal iletişim başkanı görevi', 'level' => '2'],
            ['name' => 'İnsani Yardım ve Sosyal Hizmetler Başkanı', 'description' => 'İnsani yardım ve sosyal hizmetler başkanı görevi', 'level' => '2'],
            ['name' => 'Bölge Kadınlar Teşkilatı Başkanı', 'description' => 'Bölge kadınlar teşkilatı başkanı görevi', 'level' => '2'],
            ['name' => 'Bölge Gençlik Teşkilatı Başkanı', 'description' => 'Bölge gençlik teşkilatı başkanı görevi', 'level' => '2'],
            ['name' => 'Bölge Kadınlar Gençlik Teşkilatı Başkanı', 'description' => 'Bölge kadınlar gençlik teşkilatı başkanı görevi', 'level' => '2'],
            ['name' => 'Teftiş Başkanı', 'description' => 'Teftiş başkanı görevi', 'level' => '2'],
            ['name' => 'Muhasebe Başkanı', 'description' => 'Muhasebe başkanı görevi', 'level' => '2'],
            ['name' => 'Sekreter', 'description' => 'Sekreter görevi', 'level' => '3'],
            ['name' => 'İdari İşler Başkanı', 'description' => 'İdari işler başkanı görevi', 'level' => '2'],
            ['name' => 'Hac-Umre Sey. İşleri Başkanı', 'description' => 'Hac-Umre seyahat işleri başkanı görevi', 'level' => '2'],
            ['name' => 'Emlak Başkanı', 'description' => 'Emlak başkanı görevi', 'level' => '2'],
            ['name' => 'Cenaze Hizmetleri Başkanı', 'description' => 'Cenaze hizmetleri başkanı görevi', 'level' => '2'],
            ['name' => 'Genel Merkez Üyelik Başkanı', 'description' => 'Genel merkez üyelik başkanı görevi', 'level' => '2'],
            ['name' => 'Tanıtım Kültür Hizmetleri Başkanı', 'description' => 'Tanıtım kültür hizmetleri başkanı görevi', 'level' => '2']
        ],
        'sub_units' => [
            ['name' => 'Yazılım Geliştirme', 'description' => 'Yazılım geliştirme birimi', 'byk_category_id' => 1],
            ['name' => 'Sistem Yönetimi', 'description' => 'Sistem yönetimi birimi', 'byk_category_id' => 1],
            ['name' => 'Ağ Güvenliği', 'description' => 'Ağ güvenliği birimi', 'byk_category_id' => 1],
            ['name' => 'Veritabanı Yönetimi', 'description' => 'Veritabanı yönetimi birimi', 'byk_category_id' => 1],
            ['name' => 'Eğitim ve Öğretim', 'description' => 'Eğitim ve öğretim birimi', 'byk_category_id' => 2],
            ['name' => 'Sosyal Hizmetler', 'description' => 'Sosyal hizmetler birimi', 'byk_category_id' => 2],
            ['name' => 'Gençlik Faaliyetleri', 'description' => 'Gençlik faaliyetleri birimi', 'byk_category_id' => 4],
            ['name' => 'Kültürel Etkinlikler', 'description' => 'Kültürel etkinlikler birimi', 'byk_category_id' => 4]
        ],
        'expense_types' => [
            ['name' => 'Ulaşım', 'description' => 'Ulaşım giderleri', 'category' => 'Genel'],
            ['name' => 'Yemek', 'description' => 'Yemek giderleri', 'category' => 'Genel'],
            ['name' => 'Konaklama', 'description' => 'Konaklama giderleri', 'category' => 'Seyahat'],
            ['name' => 'Malzeme', 'description' => 'Malzeme giderleri', 'category' => 'Operasyon'],
            ['name' => 'Eğitim', 'description' => 'Eğitim giderleri', 'category' => 'Eğitim'],
            ['name' => 'Etkinlik', 'description' => 'Etkinlik giderleri', 'category' => 'Etkinlik'],
            ['name' => 'Ofis', 'description' => 'Ofis giderleri', 'category' => 'Operasyon'],
            ['name' => 'Teknoloji', 'description' => 'Teknoloji giderleri', 'category' => 'Operasyon']
        ]
    ];
    
    // Varsayılan verileri ekle
    foreach ($defaultData as $table => $data) {
        foreach ($data as $row) {
            try {
                $db->insert($table, $row);
            } catch (Exception $e) {
                // Zaten var olan veriler için hata verme
                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                    throw $e;
                }
            }
        }
    }
    
    echo "<div class='success'>✓ Code List tabloları başarıyla oluşturuldu ve varsayılan veriler eklendi!</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Hata: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Dosya: " . $e->getFile() . "</div>";
    echo "<div class='error'>Satır: " . $e->getLine() . "</div>";
}
?>

<style>
.success { 
    color: #28a745; 
    background: #d4edda; 
    border: 1px solid #c3e6cb; 
    padding: 10px; 
    border-radius: 5px; 
    margin: 10px 0; 
}
.error { 
    color: #dc3545; 
    background: #f8d7da; 
    border: 1px solid #f5c6cb; 
    padding: 10px; 
    border-radius: 5px; 
    margin: 10px 0; 
}
h2 { color: #333; }
p { color: #666; }
</style>
