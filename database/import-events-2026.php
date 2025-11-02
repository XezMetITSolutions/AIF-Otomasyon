<?php
/**
 * 2026 Etkinliklerini Import Etme Scripti
 * events_2026_backup.php dosyasındaki etkinlikleri veritabanına aktarır
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>2026 Etkinlikleri Import</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h3><i class='fas fa-calendar-alt'></i> 2026 Etkinlikleri Import</h3>
            </div>
            <div class='card-body'>
";

// events_2026_backup.php dosyasını dahil et
$backupFile = __DIR__ . '/../admin/events_2026_backup.php';
if (!file_exists($backupFile)) {
    $backupFile = dirname(__DIR__) . '/admin/events_2026_backup.php';
}

if (!file_exists($backupFile)) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> events_2026_backup.php dosyası bulunamadı.</div>";
    echo "</div></div></div></body></html>";
    exit;
}

require_once $backupFile;

if (!isset($events_2026_backup) || !is_array($events_2026_backup)) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Hata:</strong> Etkinlik verisi bulunamadı.</div>";
    echo "</div></div></div></body></html>";
    exit;
}

echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> <strong>Toplam Etkinlik:</strong> " . count($events_2026_backup) . "</div><hr>";

// BYK kodlarından byk_id'ye mapping oluştur
$bykMapping = [];
try {
    $bykCategories = $db->fetchAll("SELECT id, code FROM byk_categories");
    foreach ($bykCategories as $cat) {
        $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ? LIMIT 1", [$cat['code']]);
        if ($byk) {
            $bykMapping[$cat['code']] = $byk['byk_id'];
        } else {
            // byk tablosunda yoksa oluştur
            $db->query("
                INSERT INTO byk (byk_adi, byk_kodu, renk_kodu, aktif, olusturma_tarihi)
                VALUES (?, ?, '#009872', 1, NOW())
            ", [$cat['code'], $cat['code']]);
            $bykMapping[$cat['code']] = $db->lastInsertId();
        }
    }
} catch (Exception $e) {
    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> BYK mapping oluşturulurken hata: " . $e->getMessage() . "</div>";
}

// Kullanıcı ID'sini al (oluşturan için)
$olusturanId = $user['id'] ?? 1;

$totalImported = 0;
$totalSkipped = 0;
$totalErrors = 0;
$errors = [];

foreach ($events_2026_backup as $index => $event) {
    $date = $event['date'] ?? '';
    $title = $event['title'] ?? '';
    $bykCode = $event['byk'] ?? '';
    $color = $event['color'] ?? '#009872';
    
    if (empty($date) || empty($title) || empty($bykCode)) {
        $totalSkipped++;
        continue;
    }
    
    // BYK ID'sini bul
    $bykId = $bykMapping[$bykCode] ?? null;
    if (!$bykId) {
        $errors[] = "BYK bulunamadı: {$bykCode} - {$title}";
        $totalErrors++;
        continue;
    }
    
    // Tarihleri ayarla - date'ten datetime oluştur
    $baslangicTarihi = $date . ' 00:00:00';
    $bitisTarihi = $date . ' 23:59:59';
    
    try {
        // Aynı başlık ve tarihli etkinlik var mı kontrol et
        $existing = $db->fetch("
            SELECT etkinlik_id 
            FROM etkinlikler 
            WHERE baslik = ? AND DATE(baslangic_tarihi) = ?
            LIMIT 1
        ", [$title, $date]);
        
        if ($existing) {
            echo "<div class='alert alert-warning small'><i class='fas fa-info'></i> <strong>Atlandı:</strong> {$title} ({$date}) - Zaten mevcut</div>";
            $totalSkipped++;
            continue;
        }
        
        // Etkinliği ekle
        $db->query("
            INSERT INTO etkinlikler (
                byk_id, baslik, baslangic_tarihi, bitis_tarihi, renk_kodu, olusturan_id, olusturma_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [$bykId, $title, $baslangicTarihi, $bitisTarihi, $color, $olusturanId]);
        
        echo "<div class='alert alert-success small'><i class='fas fa-check'></i> <strong>Eklendi:</strong> {$title} ({$date}) - <span class='badge' style='background-color: {$color}; color: white;'>{$bykCode}</span></div>";
        $totalImported++;
        
    } catch (Exception $e) {
        $errorMsg = "{$title} ({$date}): " . $e->getMessage();
        echo "<div class='alert alert-danger small'><i class='fas fa-times'></i> <strong>Hata:</strong> {$errorMsg}</div>";
        $errors[] = $errorMsg;
        $totalErrors++;
    }
}

// Genel özet
echo "<hr>";
echo "<div class='card mt-4'>
    <div class='card-header bg-success text-white'>
        <h5><i class='fas fa-chart-bar'></i> Import Özeti</h5>
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
    foreach (array_slice($errors, 0, 10) as $error) {
        echo "<li>{$error}</li>";
    }
    if (count($errors) > 10) {
        echo "<li><em>... ve " . (count($errors) - 10) . " hata daha</em></li>";
    }
    echo "</ul></div>";
}

echo "<div class='mt-4'>
    <a href='/admin/etkinlikler.php' class='btn btn-primary'><i class='fas fa-calendar'></i> Çalışma Takvimine Git</a>
</div>";

echo "
            </div>
        </div>
    </div>
</body>
</html>";
?>

