<?php
require_once 'admin/includes/database.php';

echo "<h2>Birimler Tablosu Oluşturma</h2>";

try {
    $db = Database::getInstance();
    
    // Tablo var mı kontrol et
    $tableExists = false;
    try {
        $db->query("SELECT 1 FROM units LIMIT 1");
        $tableExists = true;
        echo "<p style='color: blue;'>ℹ️ 'units' tablosu zaten mevcut.</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ 'units' tablosu bulunamadı, oluşturuluyor...</p>";
    }
    
    if (!$tableExists) {
        // Tabloyu oluştur
        $sql = "
        CREATE TABLE units (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(10) NOT NULL UNIQUE COMMENT 'Birim Kodu',
            name VARCHAR(100) NOT NULL COMMENT 'Birim Adı',
            description TEXT COMMENT 'Birim Açıklaması',
            is_active BOOLEAN DEFAULT TRUE COMMENT 'Aktif Durumu',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Oluşturma Tarihi',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Güncelleme Tarihi'
        );";
        
        $db->query($sql);
        echo "<p style='color: green;'>✅ 'units' tablosu başarıyla oluşturuldu!</p>";
    }
    
    // Örnek veri ekleme (sadece tablo boşsa)
    $count = $db->fetchOne("SELECT COUNT(*) FROM units")['COUNT(*)'];
    if ($count == 0) {
        echo "<h3>Örnek Veri Ekleme:</h3>";
        
        $sampleUnits = [
            [
                'code' => 'GB',
                'name' => 'Genel Başkanlık',
                'description' => 'Kuruluşun en üst düzey yönetim birimidir.'
            ],
            [
                'code' => 'T',
                'name' => 'Teşkilatlanma',
                'description' => 'Üye ve şube yapılanmasını koordine eder.'
            ],
            [
                'code' => 'E',
                'name' => 'Eğitim',
                'description' => 'Eğitim programları ve materyallerini hazırlar.'
            ],
            [
                'code' => 'I',
                'name' => 'İrşad',
                'description' => 'Manevi rehberlik ve irşat faaliyetlerinden sorumludur.'
            ],
            [
                'code' => 'KI',
                'name' => 'Kurumsal İletişim',
                'description' => 'Kurumun dış paydaşlarla iletişimini sağlar.'
            ],
            [
                'code' => 'SH',
                'name' => 'Sosyal Hizmetler',
                'description' => 'Sosyal yardım ve hizmet faaliyetlerini yürütür.'
            ],
            [
                'code' => 'KT',
                'name' => 'Kadınlar Teşkilatı',
                'description' => 'Kadınlara yönelik faaliyetler ve organizasyonları düzenler.'
            ],
            [
                'code' => 'KGT',
                'name' => 'Kadınlar Gençlik Teşkilatı',
                'description' => 'Kadın gençlere özel faaliyetler yürütür.'
            ],
            [
                'code' => 'GT',
                'name' => 'Gençlik Teşkilatı',
                'description' => 'Gençlere yönelik faaliyetler ve organizasyonları koordine eder.'
            ]
        ];
        
        foreach ($sampleUnits as $unit) {
            $db->insert('units', [
                'code' => $unit['code'],
                'name' => $unit['name'],
                'description' => $unit['description'],
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        echo "<p style='color: green;'>✅ " . count($sampleUnits) . " örnek birim eklendi.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Tablo zaten veri içeriyor (" . $count . " birim), örnek veri eklenmedi.</p>";
    }
    
    // Tablo yapısını göster
    echo "<h3>Tablo Yapısı:</h3>";
    $columns = $db->fetchAll("SHOW COLUMNS FROM units");
    
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Kolon Adı</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
    echo "<tbody>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    
    // Mevcut birimleri göster
    echo "<h3>Mevcut Birimler:</h3>";
    $units = $db->fetchAll("SELECT * FROM units WHERE is_active = 1 ORDER BY code");
    
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Kod</th><th>Ad</th><th>Açıklama</th></tr></thead>";
    echo "<tbody>";
    foreach ($units as $unit) {
        echo "<tr>";
        echo "<td><strong>" . $unit['code'] . "</strong></td>";
        echo "<td>" . $unit['name'] . "</td>";
        echo "<td>" . $unit['description'] . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    
    echo "<hr>";
    echo "<h3>Sonraki Adımlar:</h3>";
    echo "<p><a href='admin/reservations.php' class='btn btn-primary'>Rezervasyon Sayfasını Test Et</a></p>";
    echo "<p><a href='admin/code_list.php' class='btn btn-secondary'>Code List Sayfasını Görüntüle</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Genel hata: " . $e->getMessage() . "</p>";
}
?>
