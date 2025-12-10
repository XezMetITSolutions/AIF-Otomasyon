<?php
/**
 * Görev Adlarını Alt Birimlere Import Etme Scripti
 * Her BYK için görev listesini alt birim olarak ekler
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Görevler Alt Birimlere Import</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h3><i class='fas fa-tasks'></i> Görevler Alt Birimlere Import</h3>
            </div>
            <div class='card-body'>
";

// Görev listesi (doğrudan PHP array olarak tanımlandı)
$gorevler = [
    'KGT' => [
        'Abla Kardeş Sorumlusu',
        'BYK Üyesi',
        'Başkan Danışmanı',
        'Bölge Kadınlar Gençlik Teşkilatı Başkanı',
        'Eğitim Başkan Yrd.',
        'Eğitim Başkanı',
        'Genel Merkez Üyelik Başkan Yrd.',
        'Hilal Kursu Sorumlusu',
        'Komisyon Üyesi',
        'Kurumsal İletişim Başkan Yrd.',
        'Kurumsal İletişim Başkanı',
        'Muhasebe ve Gençlik Organize Başkanı',
        'Ortaöğretim Başkan Yrd.',
        'Ortaöğretim Başkanı',
        'Sekreter Yardımcısı',
        'Tanıtım Kültür Hizmetleri Başkan Yrd.',
        'Tanıtım Kültür Hizmetleri Başkanı',
        'Teftiş Başkan Yrd.',
        'Teftiş Başkanı',
        'Teşkilatlanma Başkan Yrd.',
        'Teşkilatlanma Başkanı',
        'Teşkilatlanma Bşk. Yrd. ve Sekreterya ',
        'Üniversiteliler Başkan Yrd.',
        'Üniversiteliler Başkanı',
        'İnsani Yardım ve  Sosyal Hizmetler Başkanı',
        'İnsani Yardım ve Sosyal Hizmetler Başkan Yrd.',
        'İrfan Evleri Sorumlusu',
        'İrşad Başkan Yrd.',
        'İrşad Başkanı'
    ],
    'GT' => [
        'Abi Kardeş Sorumlusu',
        'BYK Üyesi',
        'Başkan Danışmanı',
        'Bölge Gençlik Teskilatı Başkanı',
        'Gençlik Organize ve Spor Gezi Başkan Yrd.',
        'Gençlik Organize ve Spor Gezi Başkanı',
        'Komisyon Üyesi',
        'Kurumsal İletişim Başkan Yrd.',
        'Kurumsal İletişim Başkanı',
        'Muhasebe Başkan Yrd.',
        'Muhasebe Başkanı',
        'Ortaöğretim Başkan Yrd.',
        'Ortaöğretim Başkanı',
        'Teftiş Başkan Yrd.',
        'Teftiş Başkanı',
        'Teşkilatlanma Başkan Yrd.',
        'Teşkilatlanma Başkanı',
        'Teşkilatlanma Bşk. Yrd. ve Sekreterya ',
        'Yıldız Müdürü',
        'Üniversiteliler Başkan Yrd.',
        'Üniversiteliler Başkanı',
        'İnsani Yardım ve  Sosyal Hizmetler Başkanı',
        'İnsani Yardım ve Sosyal Hizmetler Başkan Yrd.',
        'İrfan Evleri Sorumlusu',
        'İrşad Eğitim Başkan Yrd.',
        'İrşad Eğitim Başkanı'
    ],
    'KT' => [
        'Aile Eğitim Sorumlusu',
        'Ana Sınıf Sorumlusu',
        'BYK Üyesi',
        'Basın Yayın Başkan Yrd.',
        'Başkan Danışmanı',
        'Bölge Kadınlar Teşkilatı Başkanı',
        'Cenaze Hizmetleri Başkan Yrd.',
        'Cenaze Hizmetleri Başkanı',
        'Eğitim Başkan Yrd.',
        'Eğitim Başkanı',
        'Genel Merkez Üyelik Başkan Yrd.',
        'Genel Merkez Üyelik Başkanı',
        'Hac-Umre Sey. İşleri Başkan Yrd.',
        'Hac-Umre Sey. İşleri Başkanı',
        'Komisyon Üyesi',
        'Kurumsal İletişim Başkan Yrd.',
        'Kurumsal İletişim Başkanı',
        'Muhasebe Başkan Yrd.',
        'Muhasebe Başkanı',
        'Sekreter',
        'Sekreter Yardımcısı',
        'Tanıtım Kültür Hizmetleri Başkan Yrd.',
        'Tanıtım Kültür Hizmetleri Başkanı',
        'Teftiş Başkan Yrd.',
        'Teftiş Başkanı',
        'Teşkilatlanma Başkan Yrd.',
        'Teşkilatlanma Başkanı',
        'Teşkilatlanma Bşk. Yrd. ve Sekreterya ',
        'Yetiskinler Egitim Kursu Sorumlusu',
        'Çocuk Kulübü Sorumlusu',
        'İdari İşler Başkan Yrd.',
        'İdari İşler Başkanı',
        'İdari İşler ve Organize Başkan Yrd.',
        'İdari İşler ve Organize Başkanı',
        'İhsan Sohbetleri Başkan Yrd.',
        'İhsan Sohbetleri Başkanı',
        'İnsani Yardım ve  Sosyal Hizmetler Başkanı',
        'İnsani Yardım ve Sosyal Hizmetler Başkan Yrd.',
        'İrşad Başkan Yrd.',
        'İrşad Başkanı',
        'İslami İlimler Sorumlusu'
    ],
    'AT' => [
        'Aile Eğitim Sorumlusu',
        'Ana Sınıf Sorumlusu',
        'BYK Üyesi',
        'Basın Yayın Başkan Yrd.',
        'Basın Yayın Başkanı',
        'Başkan Danışmanı',
        'Bölge Başkanı',
        'Bölge Gençlik Teskilatı Başkanı',
        'Bölge Kadınlar Gençlik Teşkilatı Başkanı',
        'Bölge Kadınlar Teşkilatı Başkanı',
        'Cenaze Hizmetleri Başkan Yrd.',
        'Cenaze Hizmetleri Başkanı',
        'Emlak Başkan Yrd.',
        'Emlak Başkanı',
        'Eğitim Başkan Yrd.',
        'Eğitim Başkanı',
        'Genel Merkez Üyelik Başkan Yrd.',
        'Genel Merkez Üyelik Başkanı',
        'Hac-Umre Sey. İşleri Başkan Yrd.',
        'Hac-Umre Sey. İşleri Başkanı',
        'Halkla İlişkiler Başkan Yrd.',
        'Halkla İlişkiler Başkanı',
        'Komisyon Üyesi',
        'Kurumsal İletişim Başkan Yrd.',
        'Kurumsal İletişim Başkanı',
        'Muhasebe Başkan Yrd.',
        'Muhasebe Başkanı',
        'Sekreter',
        'Sekreter Yardımcısı',
        'Tanıtım Kültür Hizmetleri Başkan Yrd.',
        'Tanıtım Kültür Hizmetleri Başkanı',
        'Teftiş Başkan Yrd.',
        'Teftiş Başkanı',
        'Teşkilatlanma Başkan Yrd.',
        'Teşkilatlanma Başkanı',
        'Yetiskinler Egitim Kursu Sorumlusu',
        'Çocuk Kulübü Sorumlusu',
        'İdari İşler Başkan Yrd.',
        'İdari İşler Başkanı',
        'İhsan Sohbetleri Başkan Yrd.',
        'İhsan Sohbetleri Başkanı',
        'İnsani Yardım ve  Sosyal Hizmetler Başkanı',
        'İnsani Yardım ve Sosyal Hizmetler Başkan Yrd.',
        'İrşad Başkan Yrd.',
        'İrşad Başkanı',
        'İslami İlimler Sorumlusu'
    ]
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

echo "<hr>";

// Her BYK için görevleri alt birim olarak ekle
$totalImported = 0;
$totalSkipped = 0;
$totalErrors = 0;
$errors = [];

foreach ($gorevler as $bykCode => $gorevListesi) {
    $bykCategory = $bykCategories[$bykCode] ?? null;
    if (!$bykCategory || !isset($bykCategory['id'])) {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> BYK kategorisi bulunamadı: {$bykCode}</div>";
        continue;
    }
    
    $bykColor = isset($bykCategory['color']) ? $bykCategory['color'] : '#009872';
    $bykName = isset($bykCategory['name']) ? $bykCategory['name'] : $bykCode;
    
    echo "<h5 class='mt-4'><i class='fas fa-building'></i> <span class='badge' style='background-color: {$bykColor}; color: white;'>{$bykName}</span> ({$bykCode}) Görevleri İşleniyor...</h5>";
    echo "<p><strong>Toplam Görev:</strong> " . count($gorevListesi) . "</p>";
    
    $fileImported = 0;
    $fileSkipped = 0;
    $fileErrors = 0;
    
    foreach ($gorevListesi as $gorevAdi) {
        $gorevAdi = trim($gorevAdi);
        if (empty($gorevAdi)) {
            continue;
        }
        
        // Aynı BYK ve görev adı zaten var mı kontrol et
        try {
            $existing = $db->fetch("
                SELECT id FROM byk_sub_units 
                WHERE byk_category_id = ? AND name = ?
            ", [$bykCategory['id'], $gorevAdi]);
            
            if ($existing) {
                echo "<div class='alert alert-warning small'><i class='fas fa-exclamation-triangle'></i> Alt birim zaten mevcut: {$gorevAdi}</div>";
                $fileSkipped++;
                continue;
            }
            
            // Yeni alt birim ekle
            $description = $bykName . " - {$gorevAdi} görevi";
            $db->query("
                INSERT INTO byk_sub_units (byk_category_id, name, description, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ", [$bykCategory['id'], $gorevAdi, $description]);
            
            echo "<div class='alert alert-success small'><i class='fas fa-plus'></i> <strong>Alt birim eklendi:</strong> <span class='badge' style='background-color: {$bykColor}; color: white;'>{$bykName}</span> - {$gorevAdi}</div>";
            $fileImported++;
            
        } catch (Exception $e) {
            $errorMsg = "{$gorevAdi} - {$bykName}: " . $e->getMessage();
            echo "<div class='alert alert-danger small'><i class='fas fa-times'></i> <strong>Hata:</strong> {$errorMsg}</div>";
            $errors[] = $errorMsg;
            $fileErrors++;
            $totalErrors++;
        }
    }
    
    $totalImported += $fileImported;
    $totalSkipped += $fileSkipped;
    
    echo "<div class='alert alert-info'><strong>Özet ({$bykCode}):</strong> {$fileImported} alt birim eklendi, {$fileSkipped} atlandı, {$fileErrors} hata</div>";
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
