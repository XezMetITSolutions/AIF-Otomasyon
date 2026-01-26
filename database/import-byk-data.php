<?php
/**
 * BYK Verilerini Import Etme Scripti
 * byk_categories tablosuna veri ekler
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>BYK Veri Import</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h3><i class='fas fa-database'></i> BYK Veri Import</h3>
            </div>
            <div class='card-body'>
";

// Varsayılan BYK verileri (d0451622.sql dosyasından)
$bykData = [
    ['code' => 'AT', 'name' => 'Ana Teşkilat', 'color' => '#dc3545', 'description' => 'Ana teşkilat birimi'],
    ['code' => 'KT', 'name' => 'Kadınlar Teşkilatı', 'color' => '#6f42c1', 'description' => 'Kadınlar teşkilatı birimi'],
    ['code' => 'KGT', 'name' => 'Kadınlar Gençlik Teşkilatı', 'color' => '#198754', 'description' => 'Kadınlar gençlik teşkilatı birimi'],
    ['code' => 'GT', 'name' => 'Gençlik Teşkilatı', 'color' => '#0d6efd', 'description' => 'Gençlik teşkilatı birimi'],
];

$imported = 0;
$skipped = 0;
$errors = [];

echo "<h5>BYK Kategorileri Import Ediliyor...</h5><hr>";

foreach ($bykData as $byk) {
    try {
        // Aynı kod var mı kontrol et
        $existing = $db->fetch("SELECT id FROM byk_categories WHERE code = ?", [$byk['code']]);
        
        if ($existing) {
            echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> <strong>{$byk['code']}</strong> - {$byk['name']} zaten mevcut, atlandı.</div>";
            $skipped++;
        } else {
            // Yeni BYK ekle
            $db->query("
                INSERT INTO byk_categories (code, name, color, description, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ", [$byk['code'], $byk['name'], $byk['color'], $byk['description']]);
            
            echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>{$byk['code']}</strong> - {$byk['name']} başarıyla eklendi.</div>";
            $imported++;
        }
    } catch (Exception $e) {
        $errorMsg = "{$byk['code']} - {$byk['name']}: " . $e->getMessage();
        echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> {$errorMsg}</div>";
        $errors[] = $errorMsg;
    }
}

echo "<hr>";

// Alt birimleri de import et
echo "<h5>Alt Birimler Import Ediliyor...</h5><hr>";

$subUnitsData = [
    // Ana Teşkilat (AT - byk_category_id = 1)
    ['byk_category_id' => 1, 'name' => 'Genel Merkez', 'description' => 'Ana teşkilat genel merkez'],
    ['byk_category_id' => 1, 'name' => 'Bölge Temsilciliği', 'description' => 'Bölge temsilcilikleri'],
    ['byk_category_id' => 1, 'name' => 'Şube Temsilciliği', 'description' => 'Şube temsilcilikleri'],
    
    // Kadınlar Teşkilatı (KT - byk_category_id = 2)
    ['byk_category_id' => 2, 'name' => 'KT Genel Merkez', 'description' => 'Kadınlar teşkilatı genel merkez'],
    ['byk_category_id' => 2, 'name' => 'KT Bölge', 'description' => 'Kadınlar teşkilatı bölge'],
    ['byk_category_id' => 2, 'name' => 'KT Şube', 'description' => 'Kadınlar teşkilatı şube'],
    
    // Kadınlar Gençlik Teşkilatı (KGT - byk_category_id = 3)
    ['byk_category_id' => 3, 'name' => 'KGT Genel Merkez', 'description' => 'Kadınlar gençlik teşkilatı genel merkez'],
    ['byk_category_id' => 3, 'name' => 'KGT Bölge', 'description' => 'Kadınlar gençlik teşkilatı bölge'],
    ['byk_category_id' => 3, 'name' => 'KGT Şube', 'description' => 'Kadınlar gençlik teşkilatı şube'],
    
    // Gençlik Teşkilatı (GT - byk_category_id = 4)
    ['byk_category_id' => 4, 'name' => 'GT Genel Merkez', 'description' => 'Gençlik teşkilatı genel merkez'],
    ['byk_category_id' => 4, 'name' => 'GT Bölge', 'description' => 'Gençlik teşkilatı bölge'],
    ['byk_category_id' => 4, 'name' => 'GT Şube', 'description' => 'Gençlik teşkilatı şube'],
];

$subUnitsImported = 0;
$subUnitsSkipped = 0;

foreach ($subUnitsData as $subUnit) {
    try {
        // Önce byk_category_id'nin var olup olmadığını kontrol et
        $bykCategory = $db->fetch("SELECT id FROM byk_categories WHERE id = ?", [$subUnit['byk_category_id']]);
        
        if (!$bykCategory) {
            echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> BYK Kategori ID {$subUnit['byk_category_id']} bulunamadı, alt birim atlandı: {$subUnit['name']}</div>";
            $subUnitsSkipped++;
            continue;
        }
        
        // Aynı alt birim var mı kontrol et
        $existing = $db->fetch("
            SELECT id FROM byk_sub_units 
            WHERE byk_category_id = ? AND name = ?
        ", [$subUnit['byk_category_id'], $subUnit['name']]);
        
        if ($existing) {
            echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Alt birim zaten mevcut: {$subUnit['name']}</div>";
            $subUnitsSkipped++;
        } else {
            // Yeni alt birim ekle
            $db->query("
                INSERT INTO byk_sub_units (byk_category_id, name, description, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ", [$subUnit['byk_category_id'], $subUnit['name'], $subUnit['description']]);
            
            echo "<div class='alert alert-success'><i class='fas fa-check'></i> Alt birim eklendi: {$subUnit['name']}</div>";
            $subUnitsImported++;
        }
    } catch (Exception $e) {
        $errorMsg = "{$subUnit['name']}: " . $e->getMessage();
        echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> {$errorMsg}</div>";
        $errors[] = $errorMsg;
    }
}

echo "<hr>";

// Özet
echo "<div class='card mt-4'>
    <div class='card-header bg-info text-white'>
        <h5><i class='fas fa-chart-bar'></i> Import Özeti</h5>
    </div>
    <div class='card-body'>
        <table class='table table-bordered'>
            <tr>
                <td><strong>BYK Kategorileri:</strong></td>
                <td><span class='badge bg-success'>{$imported} eklendi</span>, <span class='badge bg-warning'>{$skipped} atlandı</span></td>
            </tr>
            <tr>
                <td><strong>Alt Birimler:</strong></td>
                <td><span class='badge bg-success'>{$subUnitsImported} eklendi</span>, <span class='badge bg-warning'>{$subUnitsSkipped} atlandı</span></td>
            </tr>
            <tr>
                <td><strong>Hatalar:</strong></td>
                <td><span class='badge bg-danger'>" . count($errors) . " hata</span></td>
            </tr>
        </table>
    </div>
</div>";

if (!empty($errors)) {
    echo "<div class='alert alert-danger mt-3'>
        <h6><i class='fas fa-exclamation-circle'></i> Hatalar:</h6>
        <ul>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul></div>";
}

echo "<div class='mt-4'>
    <a href='/admin/byk.php' class='btn btn-primary'><i class='fas fa-arrow-left'></i> BYK Yönetimine Dön</a>
    <a href='/admin/alt-birimler.php' class='btn btn-secondary'><i class='fas fa-list'></i> Alt Birimlere Git</a>
</div>";

echo "
            </div>
        </div>
    </div>
</body>
</html>";
?>

