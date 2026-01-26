<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Demirbaşlar Tablosu
    $sqlDemirbaslar = "CREATE TABLE IF NOT EXISTS demirbaslar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad VARCHAR(255) NOT NULL,
        kategori VARCHAR(100),
        konum VARCHAR(255),
        sorumlu_kisi_id INT,
        durum ENUM('musait', 'kirada', 'bakimda', 'arizali') DEFAULT 'musait',
        fotograf_yolu VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sorumlu_kisi_id) REFERENCES kullanicilar(kullanici_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $conn->exec($sqlDemirbaslar);
    echo "demirbaslar table created.\n";

    // Demirbaş Talepleri Tablosunu Güncelle
    // Önce sütunların var olup olmadığını kontrol et, yoksa ekle
    $columns = $conn->query("SHOW COLUMNS FROM demirbas_talepleri")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('baslangic_tarihi', $columns)) {
        $conn->exec("ALTER TABLE demirbas_talepleri ADD COLUMN baslangic_tarihi DATETIME NULL AFTER demirbas_id");
        echo "Added baslangic_tarihi to demirbas_talepleri.\n";
    }
    
    if (!in_array('bitis_tarihi', $columns)) {
        $conn->exec("ALTER TABLE demirbas_talepleri ADD COLUMN bitis_tarihi DATETIME NULL AFTER baslangic_tarihi");
        echo "Added bitis_tarihi to demirbas_talepleri.\n";
    }

    // demirbas_id foreign key ekle (eğer yoksa)
    // Not: Basitlik için burada constraint kontrolü yapmıyoruz, hata verirse zaten vardır.
    try {
        $conn->exec("ALTER TABLE demirbas_talepleri ADD CONSTRAINT fk_demirbas_id FOREIGN KEY (demirbas_id) REFERENCES demirbaslar(id) ON DELETE SET NULL");
        echo "Added foreign key constraint for demirbas_id.\n";
    } catch (PDOException $e) {
        // Constraint muhtemelen zaten var veya indeks sorunu
        echo "Foreign key constraint might already exist: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
