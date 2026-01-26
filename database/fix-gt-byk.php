<?php
/**
 * GT (Gençlik Teşkilatı) BYK Kaydını Oluşturma ve Kullanıcıları Düzeltme Scripti
 * byk_categories'de GT varsa, byk tablosunda da oluşturur
 * GT_birimler.json'daki kullanıcıların BYK'sını düzeltir
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>GT BYK Düzeltme</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h3><i class='fas fa-tools'></i> GT BYK Düzeltme</h3>
            </div>
            <div class='card-body'>
";

// 1. byk_categories'den GT'yi bul
$gtCategory = null;
try {
    $gtCategory = $db->fetch("SELECT id, code, name, color FROM byk_categories WHERE code = 'GT'");
    if ($gtCategory) {
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>GT kategorisi bulundu:</strong> {$gtCategory['name']} (ID: {$gtCategory['id']})</div>";
    } else {
        echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> byk_categories tablosunda GT kategorisi bulunamadı!</div>";
        echo "</div></div></div></body></html>";
        exit;
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> byk_categories tablosu okunamadı: " . $e->getMessage() . "</div>";
    echo "</div></div></div></body></html>";
    exit;
}

// 2. byk tablosunda GT var mı kontrol et
$gtByk = null;
try {
    $gtByk = $db->fetch("SELECT byk_id, byk_adi, byk_kodu FROM byk WHERE byk_kodu = 'GT'");
    if ($gtByk) {
        echo "<div class='alert alert-info'><i class='fas fa-info'></i> <strong>byk tablosunda GT kaydı mevcut:</strong> {$gtByk['byk_adi']} (ID: {$gtByk['byk_id']})</div>";
        $gtBykId = $gtByk['byk_id'];
    } else {
        // GT kaydı yoksa oluştur
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> <strong>byk tablosunda GT kaydı yok, oluşturuluyor...</strong></div>";
        
        try {
            $db->query("
                INSERT INTO byk (byk_adi, byk_kodu, renk_kodu, aktif, olusturma_tarihi)
                VALUES (?, ?, ?, 1, NOW())
            ", [$gtCategory['name'], 'GT', $gtCategory['color'] ?? '#0d6efd']);
            $gtBykId = $db->lastInsertId();
            echo "<div class='alert alert-success'><i class='fas fa-plus'></i> <strong>GT BYK kaydı oluşturuldu:</strong> {$gtCategory['name']} (ID: {$gtBykId})</div>";
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> GT BYK kaydı oluşturulamadı: " . $e->getMessage() . "</div>";
            echo "</div></div></div></body></html>";
            exit;
        }
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> byk tablosu okunamadı: " . $e->getMessage() . "</div>";
    echo "</div></div></div></body></html>";
    exit;
}

echo "<hr>";

// 3. GT_birimler.json dosyasını oku ve kullanıcıların BYK'sını düzelt
$filepath = __DIR__ . '/../GT_birimler.json';
if (!file_exists($filepath)) {
    $filepath = dirname(__DIR__) . '/GT_birimler.json';
}

if (!file_exists($filepath)) {
    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> GT_birimler.json dosyası bulunamadı.</div>";
    echo "</div></div></div></body></html>";
    exit;
}

$jsonContent = file_get_contents($filepath);
$data = json_decode($jsonContent, true);

if ($data === null) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> JSON parse hatası!</div>";
    echo "</div></div></div></body></html>";
    exit;
}

$birimler = $data['birimler'] ?? [];
echo "<h5 class='mt-4'><i class='fas fa-file-code'></i> GT_birimler.json İşleniyor...</h5>";
echo "<p><strong>Toplam Kayıt:</strong> " . count($birimler) . "</p>";

$totalUpdated = 0;
$totalSkipped = 0;
$totalErrors = 0;
$errors = [];

foreach ($birimler as $birim) {
    $mail = trim($birim['mail'] ?? '');
    $kisiAdiSoyadi = trim($birim['kisi_adi_soyadi'] ?? '');
    $gorevAdi = trim($birim['gorev_adi'] ?? '');
    
    if (empty($mail)) {
        $totalSkipped++;
        continue;
    }
    
    try {
        // Kullanıcıyı email ile bul
        $user = $db->fetch("SELECT kullanici_id, ad, soyad, byk_id FROM kullanicilar WHERE email = ?", [$mail]);
        
        if (!$user) {
            echo "<div class='alert alert-warning small'><i class='fas fa-exclamation-triangle'></i> Kullanıcı bulunamadı: {$mail} ({$kisiAdiSoyadi})</div>";
            $totalSkipped++;
            continue;
        }
        
        // BYK zaten GT mi kontrol et
        if ($user['byk_id'] == $gtBykId) {
            echo "<div class='alert alert-info small'><i class='fas fa-check'></i> BYK zaten doğru: {$mail} ({$kisiAdiSoyadi})</div>";
            $totalSkipped++;
            continue;
        }
        
        // BYK'yı GT olarak güncelle
        $db->query("UPDATE kullanicilar SET byk_id = ? WHERE email = ?", [$gtBykId, $mail]);
        
        $currentByk = 'Bilinmiyor';
        if ($user['byk_id']) {
            $currentBykInfo = $db->fetch("SELECT byk_adi FROM byk WHERE byk_id = ?", [$user['byk_id']]);
            if ($currentBykInfo) {
                $currentByk = $currentBykInfo['byk_adi'];
            }
        }
        
        echo "<div class='alert alert-success small'><i class='fas fa-sync'></i> <strong>Güncellendi:</strong> {$mail} - {$kisiAdiSoyadi} ({$gorevAdi}) → {$currentByk} → GT (Gençlik Teşkilatı)</div>";
        $totalUpdated++;
        
    } catch (Exception $e) {
        $errorMsg = "{$mail} - {$kisiAdiSoyadi}: " . $e->getMessage();
        echo "<div class='alert alert-danger small'><i class='fas fa-times'></i> <strong>Hata:</strong> {$errorMsg}</div>";
        $errors[] = $errorMsg;
        $totalErrors++;
    }
}

// Genel özet
echo "<hr>";
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

