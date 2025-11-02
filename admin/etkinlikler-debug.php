<?php
/**
 * Etkinlikler Debug Sayfası
 * Bu sayfa etkinliklerin neden görünmediğini debug etmek için kullanılır
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
    <title>Etkinlikler Debug</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .debug-section { margin-bottom: 30px; }
        .alert { margin-top: 10px; }
    </style>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h3><i class='fas fa-bug'></i> Etkinlikler Debug Sayfası</h3>
            </div>
            <div class='card-body'>
";

// 1. Tablo Kontrolü
echo "<div class='debug-section'>
    <h5>1. Veritabanı Tabloları Kontrolü</h5>";

try {
    $tables = $db->fetchAll("SHOW TABLES LIKE 'etkinlikler'");
    if (empty($tables)) {
        echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HATA:</strong> etkinlikler tablosu bulunamadı!</div>";
    } else {
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>OK:</strong> etkinlikler tablosu mevcut</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HATA:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 2. Toplam Etkinlik Sayısı
echo "<div class='debug-section'>
    <h5>2. Toplam Etkinlik Sayısı</h5>";

try {
    $total = $db->fetch("SELECT COUNT(*) as total FROM etkinlikler");
    $totalCount = $total['total'] ?? 0;
    echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> <strong>Toplam Etkinlik:</strong> {$totalCount}</div>";
    
    if ($totalCount == 0) {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> <strong>UYARI:</strong> Veritabanında hiç etkinlik yok! Lütfen import scriptini çalıştırın.</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HATA:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 3. İlk 10 Etkinlik
echo "<div class='debug-section'>
    <h5>3. İlk 10 Etkinlik (Ham Veri)</h5>";

try {
    $etkinlikler = $db->fetchAll("SELECT * FROM etkinlikler ORDER BY baslangic_tarihi ASC LIMIT 10");
    if (empty($etkinlikler)) {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Hiç etkinlik bulunamadı.</div>";
    } else {
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>{$totalCount}</strong> etkinlik bulundu. İlk 10 tanesi:</div>";
        echo "<pre>" . print_r($etkinlikler, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HATA:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 4. BYK JOIN Kontrolü
echo "<div class='debug-section'>
    <h5>4. BYK JOIN Kontrolü</h5>";

try {
    $etkinliklerWithBYK = $db->fetchAll("
        SELECT e.*, 
               COALESCE(bc.name, b.byk_adi, '-') as byk_adi,
               COALESCE(bc.code, b.byk_kodu, '') as byk_kodu,
               COALESCE(bc.color, b.renk_kodu, '#009872') as byk_renk,
               COALESCE(CONCAT(u.ad, ' ', u.soyad), '-') as olusturan
        FROM etkinlikler e
        LEFT JOIN byk b ON e.byk_id = b.byk_id
        LEFT JOIN byk_categories bc ON b.byk_kodu = bc.code
        LEFT JOIN kullanicilar u ON e.olusturan_id = u.kullanici_id
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 5
    ");
    
    if (empty($etkinliklerWithBYK)) {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> JOIN sorgusu sonuç döndürmedi.</div>";
    } else {
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>" . count($etkinliklerWithBYK) . "</strong> etkinlik JOIN ile bulundu:</div>";
        echo "<pre>" . print_r($etkinliklerWithBYK, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HATA:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 5. CalendarEvents Array Kontrolü
echo "<div class='debug-section'>
    <h5>5. CalendarEvents Array Oluşturma</h5>";

try {
    $calendarEvents = [];
    $etkinlikler = $db->fetchAll("
        SELECT e.*, 
               COALESCE(bc.name, b.byk_adi, '-') as byk_adi,
               COALESCE(bc.code, b.byk_kodu, '') as byk_kodu,
               COALESCE(bc.color, b.renk_kodu, '#009872') as byk_renk,
               COALESCE(CONCAT(u.ad, ' ', u.soyad), '-') as olusturan
        FROM etkinlikler e
        LEFT JOIN byk b ON e.byk_id = b.byk_id
        LEFT JOIN byk_categories bc ON b.byk_kodu = bc.code
        LEFT JOIN kullanicilar u ON e.olusturan_id = u.kullanici_id
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 10
    ");
    
    if (!empty($etkinlikler) && is_array($etkinlikler)) {
        foreach ($etkinlikler as $etkinlik) {
            if (empty($etkinlik['baslangic_tarihi']) || empty($etkinlik['bitis_tarihi']) || empty($etkinlik['baslik'])) {
                continue;
            }
            
            try {
                $baslangic = new DateTime($etkinlik['baslangic_tarihi']);
                $bitis = new DateTime($etkinlik['bitis_tarihi']);
                
                $calendarEvents[] = [
                    'id' => $etkinlik['etkinlik_id'],
                    'title' => $etkinlik['baslik'],
                    'start' => $baslangic->format('Y-m-d\TH:i:s'),
                    'end' => $bitis->format('Y-m-d\TH:i:s'),
                ];
            } catch (Exception $e) {
                echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Tarih hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>" . count($calendarEvents) . "</strong> calendar event oluşturuldu</div>";
        echo "<pre>" . print_r($calendarEvents, true) . "</pre>";
    } else {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Etkinlikler array'i boş veya geçersiz.</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HATA:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 6. BYK Tabloları Kontrolü
echo "<div class='debug-section'>
    <h5>6. BYK Tabloları Kontrolü</h5>";

try {
    $bykCount = $db->fetch("SELECT COUNT(*) as total FROM byk");
    echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> <strong>byk tablosu:</strong> {$bykCount['total']} kayıt</div>";
} catch (Exception $e) {
    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> byk tablosu hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
}

try {
    $bykCategoriesCount = $db->fetch("SELECT COUNT(*) as total FROM byk_categories");
    echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> <strong>byk_categories tablosu:</strong> {$bykCategoriesCount['total']} kayıt</div>";
} catch (Exception $e) {
    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> byk_categories tablosu hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 7. Etkinliklerin BYK ID'leri
echo "<div class='debug-section'>
    <h5>7. Etkinliklerin BYK ID Dağılımı</h5>";

try {
    $bykDistribution = $db->fetchAll("
        SELECT e.byk_id, COUNT(*) as count 
        FROM etkinlikler e 
        GROUP BY e.byk_id
    ");
    echo "<pre>" . print_r($bykDistribution, true) . "</pre>";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HATA:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 8. Kullanıcılar Kontrolü
echo "<div class='debug-section'>
    <h5>8. Kullanıcılar Kontrolü</h5>";

try {
    $usersCount = $db->fetch("SELECT COUNT(*) as total FROM kullanicilar");
    echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> <strong>Toplam Kullanıcı:</strong> {$usersCount['total']}</div>";
    
    $usersWithId1 = $db->fetchAll("SELECT kullanici_id, ad, soyad, email FROM kullanicilar WHERE kullanici_id = 1 LIMIT 1");
    if (!empty($usersWithId1)) {
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> Kullanıcı ID 1 mevcut:</div>";
        echo "<pre>" . print_r($usersWithId1, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HATA:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 9. JSON Çıktısı
echo "<div class='debug-section'>
    <h5>9. JSON Çıktısı Kontrolü</h5>";

if (!empty($calendarEvents)) {
    $json = json_encode($calendarEvents, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HATA:</strong> JSON encode başarısız: " . json_last_error_msg() . "</div>";
    } else {
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> JSON encode başarılı</div>";
        echo "<pre style='max-height: 400px; overflow-y: auto;'>" . htmlspecialchars($json) . "</pre>";
    }
} else {
    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> CalendarEvents array'i boş, JSON oluşturulamadı.</div>";
}

echo "
            </div>
            <div class='card-footer'>
                <a href='/admin/etkinlikler.php' class='btn btn-primary'>
                    <i class='fas fa-arrow-left me-1'></i>Çalışma Takvimine Dön
                </a>
                <a href='/database/import-events-2026.php' class='btn btn-success'>
                    <i class='fas fa-file-import me-1'></i>2026 Etkinliklerini Import Et
                </a>
            </div>
        </div>
    </div>
</body>
</html>";
?>

