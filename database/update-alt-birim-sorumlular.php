<?php
/**
 * Alt Birimlerin Sorumlu Kişilerini Belirleme Scripti
 * JSON dosyalarından alt birim adları ile görev adlarını eşleştirip sorumlu kişileri bulur
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Alt Birim Sorumluları Güncelleme</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h3><i class='fas fa-user-tie'></i> Alt Birim Sorumluları Güncelleme</h3>
            </div>
            <div class='card-body'>
";

$jsonFiles = [
    'AT_birimler.json' => 'AT',
    'KGT_birimler.json' => 'KGT',
    'KT_birimler.json' => 'KT',
    'GT_birimler.json' => 'GT',
];

// Alt birimler ve sorumlularını eşleştir
$altBirimSorumlulari = [];

foreach ($jsonFiles as $filename => $bykCode) {
    $filepath = __DIR__ . '/../' . $filename;
    if (!file_exists($filepath)) {
        $filepath = dirname(__DIR__) . '/' . $filename;
    }
    
    if (!file_exists($filepath)) {
        continue;
    }
    
    $jsonContent = file_get_contents($filepath);
    if ($jsonContent === false) {
        continue;
    }
    
    $data = json_decode($jsonContent, true);
    if ($data === null) {
        continue;
    }
    
    $birimler = $data['birimler'] ?? [];
    
    foreach ($birimler as $birim) {
        $gorevAdi = trim($birim['gorev_adi'] ?? '');
        $kisiAdiSoyadi = trim($birim['kisi_adi_soyadi'] ?? '');
        $mail = trim($birim['mail'] ?? '');
        
        if (!empty($gorevAdi) && !empty($kisiAdiSoyadi)) {
            // Virgülle ayrılan kişiler için ilk kişiyi al
            $kisiler = array_map('trim', explode(',', $kisiAdiSoyadi));
            $kisi = $kisiler[0] ?? '';
            
            if (!empty($kisi)) {
                $key = $bykCode . '_' . $gorevAdi;
                if (!isset($altBirimSorumlulari[$key])) {
                    $altBirimSorumlulari[$key] = [
                        'byk_code' => $bykCode,
                        'alt_birim_adi' => $gorevAdi,
                        'sorumlu_adi' => $kisi,
                        'sorumlu_email' => !empty($mail) ? (array_map('trim', explode(',', $mail))[0] ?? '') : ''
                    ];
                }
            }
        }
    }
}

echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> <strong>Bulunan Alt Birim Sorumluları:</strong> " . count($altBirimSorumlulari) . "</div><hr>";

// Alt birimleri güncelle
$totalUpdated = 0;
$totalSkipped = 0;
$totalErrors = 0;
$errors = [];

foreach ($altBirimSorumlulari as $key => $data) {
    $bykCode = $data['byk_code'];
    $altBirimAdi = $data['alt_birim_adi'];
    $sorumluAdi = $data['sorumlu_adi'];
    $sorumluEmail = $data['sorumlu_email'];
    
    try {
        // BYK ID'yi bul
        $bykCategory = $db->fetch("SELECT id FROM byk_categories WHERE code = ?", [$bykCode]);
        if (!$bykCategory) {
            echo "<div class='alert alert-warning small'><i class='fas fa-exclamation-triangle'></i> BYK kategorisi bulunamadı: {$bykCode} - {$altBirimAdi}</div>";
            $totalSkipped++;
            continue;
        }
        
        // Alt birimi bul
        $altBirim = $db->fetch("
            SELECT id, name, description 
            FROM byk_sub_units 
            WHERE byk_category_id = ? AND name = ?
        ", [$bykCategory['id'], $altBirimAdi]);
        
        if (!$altBirim) {
            // Alt birim bulunamazsa oluştur
            $db->query("
                INSERT INTO byk_sub_units (byk_category_id, name, description, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ", [$bykCategory['id'], $altBirimAdi, "{$bykCode} - {$altBirimAdi}"]);
            $altBirimId = $db->lastInsertId();
            echo "<div class='alert alert-info small'><i class='fas fa-plus'></i> Alt birim oluşturuldu: {$altBirimAdi} ({$bykCode})</div>";
        } else {
            $altBirimId = $altBirim['id'];
        }
        
        // Sorumlu kişiyi email ile bul
        if (!empty($sorumluEmail)) {
            $sorumluKullanici = $db->fetch("SELECT kullanici_id, ad, soyad FROM kullanicilar WHERE email = ?", [$sorumluEmail]);
            if ($sorumluKullanici) {
                $sorumluAdiGoster = $sorumluKullanici['ad'] . ' ' . $sorumluKullanici['soyad'];
                
                // Alt birim description'ını güncelle (sorumlu bilgisi ile)
                $newDescription = "{$bykCode} - {$altBirimAdi} | Sorumlu: {$sorumluAdiGoster}";
                $db->query("
                    UPDATE byk_sub_units 
                    SET description = ?, updated_at = NOW()
                    WHERE id = ?
                ", [$newDescription, $altBirimId]);
                
                echo "<div class='alert alert-success small'><i class='fas fa-check'></i> <strong>{$altBirimAdi}</strong> ({$bykCode}) → Sorumlu: {$sorumluAdiGoster}</div>";
                $totalUpdated++;
            } else {
                echo "<div class='alert alert-warning small'><i class='fas fa-exclamation-triangle'></i> Sorumlu kullanıcı bulunamadı: {$sorumluEmail} - {$altBirimAdi}</div>";
                $totalSkipped++;
            }
        } else {
            echo "<div class='alert alert-warning small'><i class='fas fa-exclamation-triangle'></i> Email yok: {$altBirimAdi} - Sorumlu: {$sorumluAdi}</div>";
            $totalSkipped++;
        }
        
    } catch (Exception $e) {
        $errorMsg = "{$altBirimAdi} ({$bykCode}): " . $e->getMessage();
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

echo "<div class='mt-4'>
    <a href='/admin/alt-birimler.php' class='btn btn-primary'><i class='fas fa-list'></i> Alt Birimlere Git</a>
</div>";

echo "
            </div>
        </div>
    </div>
</body>
</html>";
?>

