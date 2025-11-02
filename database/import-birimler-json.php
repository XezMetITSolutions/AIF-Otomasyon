<?php
/**
 * JSON Dosyalarından BYK Birimlerini Import Etme Scripti
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
    <title>BYK Birimleri Import</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h3><i class='fas fa-file-import'></i> BYK Birimleri JSON Import</h3>
            </div>
            <div class='card-body'>
";

$jsonFiles = [
    'AT_birimler.json' => 'AT',
    'KGT_birimler.json' => 'KGT',
    'KT_birimler.json' => 'KT',
    'GT_birimler.json' => 'GT',
];

$totalImported = 0;
$totalSkipped = 0;
$totalErrors = 0;
$errors = [];

// Önce byk_categories tablosundan BYK ID'lerini al
$bykCategories = [];
try {
    $categories = $db->fetchAll("SELECT id, code FROM byk_categories");
    foreach ($categories as $cat) {
        $bykCategories[$cat['code']] = $cat['id'];
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> byk_categories tablosu okunamadı: " . $e->getMessage() . "</div>";
    echo "</div></div></div></body></html>";
    exit;
}

echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> <strong>BYK Kategorileri:</strong><br>";
foreach ($bykCategories as $code => $id) {
    echo "<span class='badge bg-secondary me-1'>{$code} (ID: {$id})</span>";
}
echo "</div><hr>";

foreach ($jsonFiles as $filename => $bykCode) {
    // Önce proje kök dizininde ara, sonra database dizininde
    $filepath = __DIR__ . '/../' . $filename;
    if (!file_exists($filepath)) {
        // Proje kök dizininde bulunamazsa, script'in bulunduğu dizinde ara
        $filepath = __DIR__ . '/' . $filename;
    }
    if (!file_exists($filepath)) {
        // Veya absolute path ile dene
        $filepath = dirname(__DIR__) . '/' . $filename;
    }
    
    echo "<h5><i class='fas fa-file-code'></i> {$filename} İşleniyor...</h5>";
    
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
    
    $bykCategoryId = $bykCategories[$bykCode] ?? null;
    if (!$bykCategoryId) {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> BYK kategorisi bulunamadı: {$bykCode}</div>";
        continue;
    }
    
    $birimAdi = $data['birim_adi'] ?? $bykCode;
    $toplamKayit = $data['toplam_kayit'] ?? 0;
    $birimler = $data['birimler'] ?? [];
    
    echo "<p><strong>Birim:</strong> {$birimAdi} | <strong>Toplam Kayıt:</strong> {$toplamKayit}</p>";
    
    $fileImported = 0;
    $fileSkipped = 0;
    $fileErrors = 0;
    
    foreach ($birimler as $birim) {
        $bolge = trim($birim['bolge'] ?? '');
        $gorevAdi = trim($birim['gorev_adi'] ?? '');
        $kisiAdiSoyadi = trim($birim['kisi_adi_soyadi'] ?? '');
        $mail = trim($birim['mail'] ?? '');
        $telefonNumarasi = trim($birim['telefon_numarasi'] ?? '');
        
        // Boş kayıtları atla
        if (empty($gorevAdi)) {
            $fileSkipped++;
            continue;
        }
        
        // Email varsa kullanıcı olarak ekle/yenile
        if (!empty($mail)) {
            try {
                // Email kontrolü - varsa güncelle, yoksa ekle
                $existingUser = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = ?", [$mail]);
                
                if ($existingUser) {
                    // Kullanıcı varsa bilgilerini güncelle
                    $nameParts = explode(' ', $kisiAdiSoyadi, 2);
                    $ad = $nameParts[0] ?? '';
                    $soyad = $nameParts[1] ?? '';
                    
                    // Telefon numarasını temizle
                    $telefon = !empty($telefonNumarasi) ? preg_replace('/[^0-9]/', '', $telefonNumarasi) : null;
                    if (!empty($telefon) && strlen($telefon) > 20) {
                        $telefon = substr($telefon, 0, 20);
                    }
                    
                    // byk_id'yi byk_categories'den bul (byk_kodu ile eşleştir)
                    $bykId = null;
                    try {
                        $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ? LIMIT 1", [$bykCode]);
                        if ($byk) {
                            $bykId = $byk['byk_id'];
                        }
                    } catch (Exception $e) {
                        // byk tablosu yoksa devam et
                    }
                    
                    // Kullanıcıyı güncelle (şifreyi de güncelle - AIF571#)
                    $defaultPassword = password_hash('AIF571#', PASSWORD_DEFAULT);
                    $db->query("
                        UPDATE kullanicilar 
                        SET ad = ?, 
                            soyad = ?, 
                            telefon = ?,
                            byk_id = ?,
                            sifre = ?,
                            ilk_giris_zorunlu = 1
                        WHERE email = ?
                    ", [$ad, $soyad, $telefon, $bykId, $defaultPassword, $mail]);
                    
                    echo "<div class='alert alert-success small'><i class='fas fa-sync'></i> <strong>Güncellendi:</strong> {$mail} - {$kisiAdiSoyadi} ({$gorevAdi}) - Şifre: AIF571#</div>";
                    $fileImported++;
                } else {
                    // Yeni kullanıcı ekle
                    $nameParts = explode(' ', $kisiAdiSoyadi, 2);
                    $ad = $nameParts[0] ?? '';
                    $soyad = $nameParts[1] ?? '';
                    
                    // Telefon numarasını temizle
                    $telefon = !empty($telefonNumarasi) ? preg_replace('/[^0-9]/', '', $telefonNumarasi) : null;
                    if (!empty($telefon) && strlen($telefon) > 20) {
                        $telefon = substr($telefon, 0, 20);
                    }
                    
                    // Varsayılan şifre (AIF571#)
                    $defaultPassword = password_hash('AIF571#', PASSWORD_DEFAULT);
                    
                    // Rol belirleme (varsayılan: üye)
                    $rolId = 3; // Üye
                    if (stripos($gorevAdi, 'başkan') !== false) {
                        $rolId = 2; // Başkan
                    }
                    
                    // byk_id'yi byk_categories'den bul
                    $bykId = null;
                    try {
                        $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ? LIMIT 1", [$bykCode]);
                        if ($byk) {
                            $bykId = $byk['byk_id'];
                        }
                    } catch (Exception $e) {
                        // byk tablosu yoksa devam et
                    }
                    
                    // Yeni kullanıcı ekle
                    $db->query("
                        INSERT INTO kullanicilar (
                            rol_id, byk_id, email, sifre, ad, soyad, telefon, aktif, ilk_giris_zorunlu, olusturma_tarihi
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, NOW())
                    ", [$rolId, $bykId, $mail, $defaultPassword, $ad, $soyad, $telefon]);
                    
                    echo "<div class='alert alert-success small'><i class='fas fa-plus'></i> <strong>Eklendi:</strong> {$mail} - {$kisiAdiSoyadi} ({$gorevAdi}) - Şifre: AIF571#</div>";
                    $fileImported++;
                }
            } catch (Exception $e) {
                $errorMsg = "{$mail} - {$gorevAdi}: " . $e->getMessage();
                echo "<div class='alert alert-danger small'><i class='fas fa-times'></i> <strong>Hata:</strong> {$errorMsg}</div>";
                $errors[] = $errorMsg;
                $fileErrors++;
                $totalErrors++;
            }
        } else {
            // Email yoksa sadece log (kullanıcı olarak eklenemez)
            echo "<div class='alert alert-warning small'><i class='fas fa-info'></i> Email yok, kullanıcı olarak eklenemedi: {$gorevAdi}" . (!empty($kisiAdiSoyadi) ? " - {$kisiAdiSoyadi}" : "") . "</div>";
            $fileSkipped++;
        }
    }
    
    $totalImported += $fileImported;
    $totalSkipped += $fileSkipped;
    
    echo "<div class='alert alert-info'><strong>Özet ({$filename}):</strong> {$fileImported} eklendi/güncellendi, {$fileSkipped} atlandı, {$fileErrors} hata</div>";
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
                <td><strong>Toplam Eklendi/Güncellendi:</strong></td>
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
    <a href='/admin/kullanicilar.php' class='btn btn-primary'><i class='fas fa-arrow-left'></i> Kullanıcı Yönetimine Dön</a>
    <a href='/admin/byk.php' class='btn btn-secondary'><i class='fas fa-building'></i> BYK Yönetimine Git</a>
</div>";

echo "
            </div>
        </div>
    </div>
</body>
</html>";
?>

