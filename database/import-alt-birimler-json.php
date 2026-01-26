<?php
/**
 * JSON Dosyalarından Alt Birimleri Import Etme Scripti
 * Mevcut alt birimleri silip JSON'dan yeni birimleri ekler
 * AT_birimler.json, KGT_birimler.json, KT_birimler.json, GT_birimler.json
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Alt Birimler Import</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h3><i class='fas fa-file-import'></i> Alt Birimler JSON Import</h3>
            </div>
            <div class='card-body'>
";

$jsonFiles = [
    'AT_birimler.json' => 'AT',
    'KGT_birimler.json' => 'KGT',
    'KT_birimler.json' => 'KT',
    'GT_birimler.json' => 'GT',
];

// Önce byk_categories tablosundan BYK ID'lerini al
$bykCategories = [];
try {
    $categories = $db->fetchAll("SELECT id, code, name, color FROM byk_categories");
    foreach ($categories as $cat) {
        $bykCategories[$cat['code']] = $cat;
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> byk_categories tablosu okunamadı: " . $e->getMessage() . "</div>";
    echo "</div></div></div></body></html>";
    exit;
}

echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> <strong>BYK Kategorileri:</strong><br>";
foreach ($bykCategories as $code => $cat) {
    echo "<span class='badge bg-secondary me-1'>{$code} (ID: {$cat['id']}) - {$cat['name']}</span>";
}
echo "</div>";

// ÖNEMLİ: Mevcut alt birimleri sil
echo "<h5 class='mt-4'><i class='fas fa-trash'></i> Mevcut Alt Birimler Temizleniyor...</h5>";
try {
    // Önce byk_sub_units tablosunu temizle
    $db->query("DELETE FROM byk_sub_units");
    echo "<div class='alert alert-warning'><i class='fas fa-check'></i> <strong>byk_sub_units</strong> tablosu temizlendi.</div>";
} catch (Exception $e) {
    echo "<div class='alert alert-info'><i class='fas fa-info'></i> byk_sub_units tablosu bulunamadı veya zaten boş.</div>";
}

try {
    // Eski alt_birimler tablosunu da temizle
    $db->query("DELETE FROM alt_birimler");
    echo "<div class='alert alert-warning'><i class='fas fa-check'></i> <strong>alt_birimler</strong> tablosu temizlendi.</div>";
} catch (Exception $e) {
    echo "<div class='alert alert-info'><i class='fas fa-info'></i> alt_birimler tablosu bulunamadı veya zaten boş.</div>";
}

echo "<hr>";

// JSON dosyalarını işle
$totalImported = 0;
$totalSkipped = 0;
$totalErrors = 0;
$errors = [];
$uniqueBolgeler = [];

foreach ($jsonFiles as $filename => $bykCode) {
    $filepath = __DIR__ . '/../' . $filename;
    if (!file_exists($filepath)) {
        $filepath = dirname(__DIR__) . '/' . $filename;
    }
    
    echo "<h5 class='mt-4'><i class='fas fa-file-code'></i> {$filename} İşleniyor...</h5>";
    
    if (!file_exists($filepath)) {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Dosya bulunamadı: {$filename}</div>";
        continue;
    }
    
    $jsonContent = file_get_contents($filepath);
    if ($jsonContent === false) {
        echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Dosya okunamadı: {$filename}</div>";
        continue;
    }
    
    $data = json_decode($jsonContent, true);
    if ($data === null) {
        echo "<div class='alert alert-danger'><i class='fas fa-times'></i> JSON parse hatası: {$filename}</div>";
        continue;
    }
    
    $bykCategory = $bykCategories[$bykCode] ?? null;
    if (!$bykCategory || !isset($bykCategory['id'])) {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> BYK kategorisi bulunamadı: {$bykCode}</div>";
        continue;
    }
    
    $birimler = $data['birimler'] ?? [];
    
    // Her birim için bölge bilgisini topla (benzersiz bölgeler)
    $bolgeler = [];
    foreach ($birimler as $birim) {
        $bolge = trim($birim['bolge'] ?? '');
        if (!empty($bolge) && !in_array($bolge, $bolgeler)) {
            $bolgeler[] = $bolge;
        }
    }
    
    echo "<p><strong>Birim:</strong> {$data['birim_adi']} | <strong>Toplam Bölge:</strong> " . count($bolgeler) . "</p>";
    
    $fileImported = 0;
    $fileSkipped = 0;
    $fileErrors = 0;
    
    // Her benzersiz bölge için alt birim oluştur
    foreach ($bolgeler as $bolge) {
        if (empty($bolge)) {
            continue;
        }
        
        // Aynı BYK ve bölge zaten var mı kontrol et
        try {
            $existing = $db->fetch("
                SELECT id FROM byk_sub_units 
                WHERE byk_category_id = ? AND name = ?
            ", [$bykCategory['id'], $bolge]);
            
            if ($existing) {
                echo "<div class='alert alert-warning small'><i class='fas fa-exclamation-triangle'></i> Alt birim zaten mevcut: {$bolge}</div>";
                $fileSkipped++;
                continue;
            }
            
            // Yeni alt birim ekle
            $description = (isset($bykCategory['name']) ? $bykCategory['name'] : $bykCode) . " - {$bolge} bölgesi";
            $db->query("
                INSERT INTO byk_sub_units (byk_category_id, name, description, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ", [$bykCategory['id'], $bolge, $description]);
            
            $bykName = $bykCategory['name'] ?? $bykCode;
            $bykColor = $bykCategory['color'] ?? '#009872';
            echo "<div class='alert alert-success small'><i class='fas fa-plus'></i> <strong>Alt birim eklendi:</strong> <span class='badge' style='background-color: {$bykColor}; color: white;'>{$bykName}</span> - {$bolge}</div>";
            $fileImported++;
            
        } catch (Exception $e) {
            $bykNameForError = $bykCategory['name'] ?? $bykCode;
            $errorMsg = "{$bolge} - {$bykNameForError}: " . $e->getMessage();
            echo "<div class='alert alert-danger small'><i class='fas fa-times'></i> <strong>Hata:</strong> {$errorMsg}</div>";
            $errors[] = $errorMsg;
            $fileErrors++;
            $totalErrors++;
        }
    }
    
    $totalImported += $fileImported;
    $totalSkipped += $fileSkipped;
    
    echo "<div class='alert alert-info'><strong>Özet ({$filename}):</strong> {$fileImported} alt birim eklendi, {$fileSkipped} atlandı, {$fileErrors} hata</div>";
    echo "<hr>";
}

// Genel özet
echo "<div class='card mt-4'>
    <div class='card-header bg-success text-white'>
        <h5><i class='fas fa-chart-bar'></i> Genel Import Özeti</h5>
    </div>
    <div class='card-body'>
        <table class='table table-bordered'>
            <tr>
                <td><strong>Toplam Eklendi:</strong></td>
                <td><span class='badge bg-success'>{$totalImported}</span></td>
            </tr>
            <tr>
                <td><strong>Toplam Atlandı:</strong></td>
                <td><span class='badge bg-warning'>{$totalSkipped}</span></td>
            </tr>
            <tr>
                <td><strong>Toplam Hata:</strong></td>
                <td><span class='badge bg-danger'>{$totalErrors}</span></td>
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
    <a href='/admin/alt-birimler.php' class='btn btn-primary'><i class='fas fa-list'></i> Alt Birimlere Git</a>
    <a href='/admin/byk.php' class='btn btn-secondary'><i class='fas fa-building'></i> BYK Yönetimine Git</a>
</div>";

echo "
            </div>
        </div>
    </div>
</body>
</html>";
?>

