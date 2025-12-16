<?php
/**
 * JSON Dosyalarından Kullanıcıların BYK Bilgilerini Güncelleme Scripti
 * Mevcut kullanıcıların BYK'larını JSON dosyalarından günceller
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Kullanıcı BYK Güncelleme</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h3><i class='fas fa-users'></i> Kullanıcı BYK Güncelleme</h3>
            </div>
            <div class='card-body'>
";

$jsonFiles = [
    'AT_birimler.json' => 'AT',
    'KGT_birimler.json' => 'KGT',
    'KT_birimler.json' => 'KT',
    'GT_birimler.json' => 'GT',
];

// BYK kategorileri ve byk tablosu eşleştirmesi
$bykMapping = [];
try {
    // Önce byk_categories'den kodları al
    $categories = $db->fetchAll("SELECT id, code, name FROM byk_categories");
    foreach ($categories as $cat) {
        // Sonra byk tablosundan byk_id'yi bul (byk_kodu ile eşleştir)
        $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ? LIMIT 1", [$cat['code']]);
        if ($byk) {
            $bykMapping[$cat['code']] = $byk['byk_id'];
        } else {
            // byk tablosunda bulunamazsa, byk_id oluştur veya byk_categories.id'yi kullan
            // Önce byk tablosunda bu kodu içeren başka bir kayıt var mı kontrol et
            $bykAlt = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu LIKE ? OR byk_adi LIKE ? LIMIT 1", ["%{$cat['code']}%", "%{$cat['name']}%"]);
            if ($bykAlt) {
                $bykMapping[$cat['code']] = $bykAlt['byk_id'];
            } else {
                // Yeni byk kaydı oluştur
                try {
                    $db->query("
                        INSERT INTO byk (byk_adi, byk_kodu, renk_kodu, aktif, olusturma_tarihi)
                        VALUES (?, ?, '#009872', 1, NOW())
                    ", [$cat['name'], $cat['code']]);
                    $newBykId = $db->lastInsertId();
                    $bykMapping[$cat['code']] = $newBykId;
                    echo "<div class='alert alert-info small'><i class='fas fa-plus'></i> Yeni BYK kaydı oluşturuldu: {$cat['code']} (ID: {$newBykId})</div>";
                } catch (Exception $e2) {
                    echo "<div class='alert alert-warning small'><i class='fas fa-exclamation-triangle'></i> BYK kaydı oluşturulamadı: {$cat['code']} - " . $e2->getMessage() . "</div>";
                }
            }
        }
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> BYK mapping oluşturulamadı: " . $e->getMessage() . "</div>";
    echo "</div></div></div></body></html>";
    exit;
}

echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> <strong>BYK Eşleştirmeleri:</strong><br>";
foreach ($bykMapping as $code => $bykId) {
    echo "<span class='badge bg-secondary me-1'>{$code} → BYK ID: {$bykId}</span>";
}
echo "</div><hr>";

$totalUpdated = 0;
$totalSkipped = 0;
$totalErrors = 0;
$errors = [];

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
    
    $bykId = $bykMapping[$bykCode] ?? null;
    if (!$bykId) {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> BYK ID bulunamadı: {$bykCode}</div>";
        continue;
    }
    
    $birimler = $data['birimler'] ?? [];
    
    echo "<p><strong>Birim:</strong> {$data['birim_adi']} | <strong>Toplam Kayıt:</strong> " . count($birimler) . " | <strong>BYK ID:</strong> {$bykId}</p>";
    
    $fileUpdated = 0;
    $fileSkipped = 0;
    $fileErrors = 0;
    
    foreach ($birimler as $birim) {
        $mail = trim($birim['mail'] ?? '');
        $kisiAdiSoyadi = trim($birim['kisi_adi_soyadi'] ?? '');
        $gorevAdi = trim($birim['gorev_adi'] ?? '');
        
        if (empty($mail)) {
            $fileSkipped++;
            continue;
        }
        
        try {
            // Kullanıcıyı email ile bul
            $user = $db->fetch("SELECT kullanici_id, ad, soyad, byk_id FROM kullanicilar WHERE email = ?", [$mail]);
            
            if (!$user) {
                echo "<div class='alert alert-warning small'><i class='fas fa-exclamation-triangle'></i> Kullanıcı bulunamadı: {$mail} ({$kisiAdiSoyadi})</div>";
                $fileSkipped++;
                continue;
            }
            
            // BYK zaten doğru mu kontrol et
            if ($user['byk_id'] == $bykId) {
                echo "<div class='alert alert-info small'><i class='fas fa-check'></i> BYK zaten doğru: {$mail} ({$kisiAdiSoyadi})</div>";
                $fileSkipped++;
                continue;
            }
            
            // BYK'yı güncelle
            $db->query("UPDATE kullanicilar SET byk_id = ? WHERE email = ?", [$bykId, $mail]);
            
            echo "<div class='alert alert-success small'><i class='fas fa-sync'></i> <strong>Güncellendi:</strong> {$mail} - {$kisiAdiSoyadi} ({$gorevAdi}) → BYK: {$bykCode} (ID: {$bykId})</div>";
            $fileUpdated++;
            
        } catch (Exception $e) {
            $errorMsg = "{$mail} - {$kisiAdiSoyadi}: " . $e->getMessage();
            echo "<div class='alert alert-danger small'><i class='fas fa-times'></i> <strong>Hata:</strong> {$errorMsg}</div>";
            $errors[] = $errorMsg;
            $fileErrors++;
            $totalErrors++;
        }
    }
    
    $totalUpdated += $fileUpdated;
    $totalSkipped += $fileSkipped;
    
    echo "<div class='alert alert-info'><strong>Özet ({$filename}):</strong> {$fileUpdated} kullanıcı güncellendi, {$fileSkipped} atlandı, {$fileErrors} hata</div>";
    echo "<hr>";
}

// Genel özet
echo "<div class='card mt-4'>
    <div class='card-header bg-success text-white'>
        <h5><i class='fas fa-chart-bar'></i> Genel Güncelleme Özeti</h5>
    </div>
    <div class='card-body'>
        <table class='table table-bordered'>
            <tr>
                <td><strong>Toplam Güncellendi:</strong></td>
                <td><span class='badge bg-success'>{$totalUpdated}</span></td>
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
    <a href='/admin/kullanicilar.php' class='btn btn-primary'><i class='fas fa-users'></i> Kullanıcılara Git</a>
    <a href='/admin/byk.php' class='btn btn-secondary'><i class='fas fa-building'></i> BYK Yönetimine Git</a>
</div>";

echo "
            </div>
        </div>
    </div>
</body>
</html>";
?>

