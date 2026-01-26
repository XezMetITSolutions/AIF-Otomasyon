<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "Updating database schema...\n";

try {
    // 1. Create raggal_talepleri table if not exists
    $db->query("
        CREATE TABLE IF NOT EXISTS raggal_talepleri (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kullanici_id INT NOT NULL,
            baslangic_tarihi DATETIME NOT NULL,
            bitis_tarihi DATETIME NOT NULL,
            aciklama TEXT,
            durum VARCHAR(50) DEFAULT 'beklemede',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(kullanici_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created/Checked raggal_talepleri table.\n";

    // 2. Check if demirbas_talepleri table exists, if not create it (it might be missing from schema.sql but used in code)
    // Based on previous file view, it seems it was being inserted into. Let's ensure it exists with correct columns.
    $db->query("
        CREATE TABLE IF NOT EXISTS demirbas_talepleri (
            talep_id INT AUTO_INCREMENT PRIMARY KEY,
            kullanici_id INT NOT NULL,
            demirbas_id INT DEFAULT NULL,
            baslik VARCHAR(255),
            aciklama TEXT,
            baslangic_tarihi DATETIME DEFAULT NULL,
            bitis_tarihi DATETIME DEFAULT NULL,
            durum VARCHAR(50) DEFAULT 'bekliyor',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(kullanici_id) ON DELETE CASCADE,
            FOREIGN KEY (demirbas_id) REFERENCES demirbaslar(demirbas_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created/Checked demirbas_talepleri table.\n";

    // 3. Add missing columns to demirbas_talepleri if they don't exist
    // We need to check if columns exist before adding to avoid errors, or use try-catch for each ALTER
    
    $columnsToAdd = [
        'demirbas_id' => "ADD COLUMN demirbas_id INT DEFAULT NULL AFTER kullanici_id",
        'baslangic_tarihi' => "ADD COLUMN baslangic_tarihi DATETIME DEFAULT NULL",
        'bitis_tarihi' => "ADD COLUMN bitis_tarihi DATETIME DEFAULT NULL"
    ];

    foreach ($columnsToAdd as $col => $sql) {
        try {
            $db->query("ALTER TABLE demirbas_talepleri $sql");
            echo "Added column $col to demirbas_talepleri.\n";
        } catch (Exception $e) {
            // Column likely exists
            echo "Column $col likely exists or error: " . $e->getMessage() . "\n";
        }
    }
    
    // Add FK for demirbas_id if it was just added
    try {
        $db->query("ALTER TABLE demirbas_talepleri ADD CONSTRAINT fk_demirbas_talep_item FOREIGN KEY (demirbas_id) REFERENCES demirbaslar(demirbas_id) ON DELETE SET NULL");
        echo "Added FK for demirbas_id.\n";
    } catch (Exception $e) {
        // FK likely exists
    }

    echo "Database schema update completed successfully.\n";

} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>
