<?php
session_start();

// Ana uygulamadan gelen oturumu destekle
$mainAppRole = $_SESSION['user_role'] ?? null;
$mainAppUserId = $_SESSION['user_id'] ?? null;
$mainAppUserName = $_SESSION['user_name'] ?? null;

if (!isset($_SESSION['user']) && $mainAppRole) {
    if ($mainAppRole === 'super_admin') {
        $_SESSION['user'] = 'UserB'; // tam yetki
    } elseif ($mainAppRole === 'uye') {
        $_SESSION['user'] = 'UserA'; // onaylayıcı
    }
}

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$displayName = $mainAppUserName ?? $user;
$defaultRegion = '';

if ($mainAppUserId) {
    require_once __DIR__ . '/../classes/Database.php';
    $db = Database::getInstance();
    $uyeDetay = $db->fetch("
        SELECT b.byk_kodu
        FROM kullanicilar k
        LEFT JOIN byk b ON k.byk_id = b.byk_id
        WHERE k.kullanici_id = ?
    ", [$mainAppUserId]);
    $defaultRegion = strtoupper($uyeDetay['byk_kodu'] ?? '');
}

if ($mainAppRole === 'uye' && $defaultRegion && empty($_GET['region'])) {
    $_GET['region'] = $defaultRegion;
}

// JSON-Datei, in der die Daten gespeichert werden
$dataFile = 'submissions.json';
$pageviewsFile = 'pageviews.json';

// Dosya izinlerini kontrol et (debug için)
if (file_exists($dataFile)) {
    $perms = fileperms($dataFile);
    error_log("submissions.json permissions: " . decoct($perms & 0777));
}

// Basit pageview kaydı
$pageviews = [];
if (file_exists($pageviewsFile)) {
    $pageviews = json_decode(file_get_contents($pageviewsFile), true);
    if (!is_array($pageviews)) { $pageviews = []; }
}
$pageviews[] = [
    'user' => $user,
    'path' => 'admin_dashboard',
    'ts' => date('c')
];
file_put_contents($pageviewsFile, json_encode($pageviews, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// CSV dışa aktarım (filtrelerle)
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    // Ay/Yıl filtresi
    $filterYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;
    $filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
    $dateFrom = '';
    $dateTo = '';
    if ($filterYear > 0 && $filterMonth > 0) {
        $dateFrom = sprintf('%04d-%02d-01', $filterYear, $filterMonth);
        $dateTo = date('Y-m-t', strtotime($dateFrom));
    } elseif ($filterYear > 0) {
        $dateFrom = sprintf('%04d-01-01', $filterYear);
        $dateTo = sprintf('%04d-12-31', $filterYear);
    }
    $birimFilter = $_GET['birim'] ?? '';
    $regionFilter = $_GET['region'] ?? '';

    $subs = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
    if (!is_array($subs)) { $subs = []; }

    $outRows = [];
    $outRows[] = ['GiderNo','İsim','Soyisim','IBAN','Toplam','Durum','Gönderim','Kalem Tarih','Birim','BYK','Kategori','Tutar','Açıklama','Rota','Km'];

    $within = function($createdAt, $from, $to) {
        if (!$createdAt) return false;
        $ts = strtotime($createdAt);
        if ($from && $ts < strtotime($from.' 00:00:00')) return false;
        if ($to && $ts > strtotime($to.' 23:59:59')) return false;
        return true;
    };

    foreach ($subs as $s) {
        $createdAt = $s['created_at'] ?? '';
        if ($dateFrom || $dateTo) { if (!$within($createdAt, $dateFrom, $dateTo)) continue; }
        $items = $s['items'] ?? [];
        foreach ($items as $it) {
            if ($birimFilter && (($it['birim'] ?? '') !== $birimFilter)) continue;
            if ($regionFilter && (($it['region'] ?? '') !== $regionFilter)) continue;
            $outRows[] = [
                $s['gider_no'] ?? '',
                $s['isim'] ?? '',
                $s['soyisim'] ?? '',
                $s['iban'] ?? '',
                $s['total'] ?? '',
                statusToTr($s['status'] ?? ''),
                (!empty($s['created_at']) ? date('d.m.Y H:i', strtotime($s['created_at'])) : ''),
                (!empty($it['tarih']) ? date('d.m.Y', strtotime($it['tarih'])) : ''),
                ($it['birim_label'] ?? ($it['birim'] ?? '')),
                $it['region'] ?? '',
                ($it['gider_turu_label'] ?? ($it['gider_turu'] ?? '')),
                $it['tutar'] ?? '',
                $it['aciklama'] ?? '',
                $it['rota'] ?? '',
                $it['km'] ?? ''
            ];
        }
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="harcamalar.csv"');
    $out = fopen('php://output', 'w');
    // UTF-8 BOM
    fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
    foreach ($outRows as $row) { fputcsv($out, $row, ';'); }
    fclose($out);
    exit;
}

// Daten aus der JSON-Datei laden
$submissions = [];
if (file_exists($dataFile)) {
    $submissions = json_decode(file_get_contents($dataFile), true);
}

// Funktion zum Ändern des Status innerhalb des Dashboards
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    foreach ($submissions as &$submission) {
        if (($submission['id'] ?? null) === $id) {
            if ($action === 'approve' && $user === 'UserA') {
                $submission['status'] = 'Freigegeben';
                $submission['approved_at'] = date('c');
            } elseif ($action === 'pay' && $user === 'UserB') {
                $submission['status'] = 'Bezahlt';
                $submission['paid_at'] = date('c');
                $submission['paid_by'] = $user;
            } elseif ($action === 'reject' && ($user === 'UserA' || $user === 'UserB')) {
                // Reddedildi / iptal edildi
                $submission['status'] = 'Reddedildi';
                $submission['rejected_at'] = date('c');
                $submission['rejected_by'] = $user;
            }
        }
    }

    file_put_contents($dataFile, json_encode($submissions, JSON_PRETTY_PRINT));

    header('Location: admin_dashboard.php');
    exit;
}

// Funktion zum Leeren der Liste und Löschen der Dateien im Uploads-Verzeichnis
if (isset($_GET['action']) && $_GET['action'] === 'clear' && $user === 'UserB') {
    $uploadDir = 'uploads/';
    $files = glob($uploadDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

    file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));

    header('Location: admin_dashboard.php');
    exit;
}

// Funktion zum Löschen einer einzelnen Submission
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && $user === 'UserB') {
    $id = $_GET['id'];
    
    // Debug log
    error_log("Silme işlemi başlatıldı - ID: " . $id);
    error_log("Toplam submission sayısı: " . count($submissions));
    
    // Submission'ı bul ve sil
    $found = false;
    $newSubmissions = [];
    
    foreach ($submissions as $submission) {
        if (($submission['id'] ?? null) === $id) {
            // PDF dosyasını da sil
            if (!empty($submission['pdf_link'])) {
                $pdfPath = $submission['pdf_link'];
                // Tam yol oluştur
                $fullPdfPath = __DIR__ . '/' . $pdfPath;
                if (file_exists($fullPdfPath)) {
                    unlink($fullPdfPath);
                    error_log("PDF dosyası silindi: " . $fullPdfPath);
                }
            }
            
            $found = true;
            error_log("Submission bulundu ve silindi - ID: " . $id);
        } else {
            // Silinmeyecek submission'ları yeni array'e ekle
            $newSubmissions[] = $submission;
        }
    }
    
    if ($found) {
        // JSON dosyasını güncelle
        $result = file_put_contents($dataFile, json_encode($newSubmissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        error_log("JSON dosyası yazma sonucu: " . ($result !== false ? 'Başarılı' : 'Başarısız'));
        error_log("Yeni submission sayısı: " . count($newSubmissions));
        
        if ($result !== false) {
            // Başarılı silme mesajı
            header('Location: admin_dashboard.php?deleted=1');
        } else {
            // Dosya yazma hatası
            header('Location: admin_dashboard.php?error=delete_failed');
        }
    } else {
        // Submission bulunamadı
        error_log("Submission bulunamadı - ID: " . $id);
        header('Location: admin_dashboard.php?error=not_found');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gider Panosu</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 10px; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; background-color: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 20px; font-size: 24px; }
        
        
        /* Mobile-first filters */
        .filters { 
            display: grid; 
            grid-template-columns: 1fr; 
            gap: 12px; 
            margin-bottom: 16px; 
            align-items: end; 
        }
        .filters label { font-size: 12px; color: #666; display: block; margin-bottom: 4px; font-weight: 600; }
        .filters input, .filters select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px;
            box-sizing: border-box;
        }
        
        /* Mobile table wrapper */
        .table-wrapper { 
            border: 1px solid #e5e7eb; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,.06); 
            margin-bottom: 20px;
            overflow-x: auto;
        }
        .table-wrapper table { 
            border: 0; 
            min-width: 800px;
            width: 100%;
        }
        .table-wrapper th { 
            position: sticky; 
            top: 0; 
            z-index: 1; 
            background: linear-gradient(180deg,#22c55e,#16a34a); 
            color: #fff; 
            padding: 12px 8px;
            font-size: 12px;
            white-space: nowrap;
        }
        .table-wrapper td {
            padding: 10px 8px;
            font-size: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .table-wrapper tr:hover { background: #f6fff9; }
        
        
        /* Mobile buttons */
        .export-row { 
            text-align: center; 
            margin: 12px 0 16px; 
        }
        .action-button { 
            padding: 10px 16px; 
            background-color: #4CAF50; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 14px;
            margin: 4px;
            min-width: 120px;
        }
        .action-button:hover { background-color: #45a049; }
        
        /* Mobile logout buttons */
        .logout-button, .clear-button { 
            background-color: #f44336; 
            color: white; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            display: block; 
            width: 100%; 
            margin: 10px auto; 
            font-size: 16px;
            max-width: 200px;
        }
        .clear-button { background-color: #f39c12; }
        .logout-button:hover { background-color: #e53935; }
        .clear-button:hover { background-color: #e67e22; }
        
        /* Mobile badges */
        .badge { 
            display: inline-block; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 10px; 
            font-weight: 600; 
            color: #fff; 
            white-space: nowrap;
        }
        .badge.pending { background: #2563eb; }
        .badge.approved { background: #16a34a; }
        .badge.paid { background: #0ea5e9; }
        .badge.unpaid { background: #b91c1c; }
        
        /* Mobile actions */
        .actions { 
            display: flex; 
            flex-direction: column;
            gap: 6px; 
        }
        .actions .action-button {
            font-size: 12px;
            padding: 8px 12px;
            min-width: auto;
        }
        
        
        /* Mobile table responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 10px;
                margin: 5px;
            }
            
            h1 {
                font-size: 20px;
                margin-bottom: 15px;
            }
            
            
            .filters {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .table-wrapper {
                font-size: 11px;
            }
            
            .table-wrapper th,
            .table-wrapper td {
                padding: 8px 6px;
                font-size: 11px;
            }
            
        }
        
        /* Very small screens */
        @media (max-width: 480px) {
            .dashboard-container {
                padding: 8px;
                margin: 2px;
            }
            
            h1 {
                font-size: 18px;
            }
            
            
            .table-wrapper th,
            .table-wrapper td {
                padding: 6px 4px;
                font-size: 10px;
            }
            
            .action-button {
                font-size: 11px;
                padding: 6px 10px;
            }
        }
        
        /* Landscape orientation for tablets */
        @media (min-width: 768px) and (max-width: 1024px) {
            .filters {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Desktop */
        @media (min-width: 1025px) {
            .filters {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>Gider Panosu</h1>
        <p style="text-align:center;color:#4b5563;margin-top:-10px;">
            Giriş yapan: <strong><?php echo htmlspecialchars($displayName); ?></strong>
            <?php if ($defaultRegion): ?>
                <span style="margin-left:8px;">(BYK: <?php echo htmlspecialchars($defaultRegion); ?>)</span>
            <?php endif; ?>
        </p>
        
        <?php
        // Silme mesajları
        if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
            echo '<div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">✅ Gider başarıyla silindi!</div>';
        }
        if (isset($_GET['error'])) {
            $errorMessages = [
                'delete_failed' => '❌ Silme işlemi başarısız! Dosya yazma hatası.',
                'not_found' => '❌ Silinecek gider bulunamadı!'
            ];
            $errorMsg = $errorMessages[$_GET['error']] ?? '❌ Bilinmeyen hata!';
            echo '<div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">' . $errorMsg . '</div>';
        }
        ?>
        
        <?php
            // Filtreler
            // Yeni: Ay/Yıl filtresi
            $filterYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;
            $filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
            $dateFrom = '';
            $dateTo = '';
            if ($filterYear > 0 && $filterMonth > 0) {
                $dateFrom = sprintf('%04d-%02d-01', $filterYear, $filterMonth);
                $dateTo = date('Y-m-t', strtotime($dateFrom));
            } elseif ($filterYear > 0) {
                $dateFrom = sprintf('%04d-01-01', $filterYear);
                $dateTo = sprintf('%04d-12-31', $filterYear);
            }
            $birimFilter = $_GET['birim'] ?? '';
            $regionFilter = $_GET['region'] ?? '';
            $statusFilter = $_GET['status'] ?? '';
            $kisiFilter = $_GET['kisi'] ?? '';

            // Yardımcılar
            function withinDateRange($createdAt, $from, $to) {
                if (!$createdAt) return false;
                $ts = strtotime($createdAt);
                if ($from && $ts < strtotime($from.' 00:00:00')) return false;
                if ($to && $ts > strtotime($to.' 23:59:59')) return false;
                return true;
            }

            // Kod -> Görsel etiket eşlemesi (eski kayıtlar için)
            function prettyLabel(string $code = '', string $label = '', array $map = []) : string {
                if ($label !== '') return $label; // yeni kayıtlar label ile gelir
                if ($code === '') return 'Belirtilmedi';
                if (isset($map[$code])) return $map[$code];
                // İlk harf büyük yap (ascii)
                return mb_convert_case($code, MB_CASE_TITLE, 'UTF-8');
            }

            $birimMap = [
                'uye' => 'Başkan', 'byk' => 'BYK Üyesi', 'egitim' => 'Eğitim', 'fuar' => 'Fuar',
                'gob' => 'Spor/Gezi (GOB)', 'hacumre' => 'Hac/Umre', 'idair' => 'İdari İşler', 'irsad' => 'İrşad',
                'kurumsal' => 'Kurumsal İletişim', 'muhasebe' => 'Muhasebe', 'ortaogretim' => 'Orta Öğretim',
                'raggal' => 'Raggal', 'sosyal' => 'Sosyal Hizmetler', 'tanitma' => 'Tanıtma', 'teftis' => 'Teftiş',
                'teskilatlanma' => 'Teşkilatlanma', 'universiteler' => 'Üniversiteler', 'baska' => 'Başka'
            ];
            $kategoriMap = [ 'genel' => 'Genel', 'ikram' => 'İkram', 'ulasim' => 'Ulaşım', 'yakit' => 'Yakıt', 'malzeme' => 'Malzeme', 'konaklama' => 'Konaklama', 'buro' => 'Büro', 'diger' => 'Diğer' ];

            function statusToTr($status) {
                $map = [
                    'Eingereicht' => 'Ödeme Bekliyor',
                    'Freigegeben' => 'Ödeme Bekliyor',
                    'Bezahlt' => 'Ödendi'
                ];
                return $map[$status] ?? $status;
            }

            $filtered = [];
            foreach ($submissions as $s) {
                $createdAt = $s['created_at'] ?? '';
                if ($dateFrom || $dateTo) {
                    if (!withinDateRange($createdAt, $dateFrom, $dateTo)) continue;
                }
                // kisi filtresi
                if ($kisiFilter) {
                    $fullName = trim(($s['isim'] ?? '').' '.($s['soyisim'] ?? ''));
                    if ($fullName !== $kisiFilter) continue;
                }
                // status filtresi
                if ($statusFilter && (($s['status'] ?? '') !== $statusFilter)) {
                    continue;
                }
                // item bazlı filtreler
                if ($birimFilter || $regionFilter) {
                    $items = $s['items'] ?? [];
                    $hasMatch = false;
                    foreach ($items as $it) {
                        if ($birimFilter && (($it['birim'] ?? '') !== $birimFilter)) continue;
                        if ($regionFilter && (($it['region'] ?? '') !== $regionFilter)) continue;
                        $hasMatch = true; break;
                    }
                    if (!$hasMatch) continue;
                }
                $filtered[] = $s;
            }

            // Önce ödenmemiş başvurular (Eingereicht, sonra Freigegeben), en sonda Bezahlt
            usort($filtered, function($a, $b) {
                $rank = ['Eingereicht' => 0, 'Freigegeben' => 1, 'Bezahlt' => 2];
                $ra = $rank[$a['status'] ?? 'Bezahlt'] ?? 2;
                $rb = $rank[$b['status'] ?? 'Bezahlt'] ?? 2;
                if ($ra === $rb) {
                    $ta = strtotime($a['created_at'] ?? '1970-01-01');
                    $tb = strtotime($b['created_at'] ?? '1970-01-01');
                    return $tb <=> $ta; // yeni en üstte
                }
                return $ra <=> $rb;
            });
        ?>

        <!-- Başvuru listesi EN ÜSTE alındı -->
        <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Gider No</th>
                    <th>İsim Soyisim</th>
                    <th>IBAN</th>
                    <th>Toplam (€)</th>
                    <th>BYK</th>
                    <th>Birim</th>
                    <th>Gönderim Tarihi</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($filtered)): ?>
                    <?php foreach ($filtered as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['gider_no'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(($submission["isim"] ?? "")) . " " . htmlspecialchars(($submission["soyisim"] ?? "")); ?></td>
                            <td><?php echo htmlspecialchars($submission["iban"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($submission["total"] ?? ""); ?> €</td>
                            <td>
                                <?php 
                                // BYK bilgisini items'dan al (en çok kullanılan BYK)
                                $items = $submission['items'] ?? [];
                                $bykCounts = [];
                                foreach ($items as $item) {
                                    $region = $item['region'] ?? '';
                                    if ($region) {
                                        $bykCounts[$region] = ($bykCounts[$region] ?? 0) + 1;
                                    }
                                }
                                $mostUsedByk = !empty($bykCounts) ? array_keys($bykCounts, max($bykCounts))[0] : '-';
                                echo htmlspecialchars($mostUsedByk);
                                ?>
                            </td>
                            <td>
                                <?php 
                                // Birim bilgisini items'dan al (en çok kullanılan birim)
                                $birimCounts = [];
                                foreach ($items as $item) {
                                    $birim = $item['birim'] ?? '';
                                    if ($birim) {
                                        $birimCounts[$birim] = ($birimCounts[$birim] ?? 0) + 1;
                                    }
                                }
                                $mostUsedBirim = !empty($birimCounts) ? array_keys($birimCounts, max($birimCounts))[0] : '-';
                                $birimLabel = $birimMap[$mostUsedBirim] ?? $mostUsedBirim;
                                echo htmlspecialchars($birimLabel);
                                ?>
                            </td>
                            <td><?php echo !empty($submission['created_at']) ? htmlspecialchars(date('d.m.Y H:i', strtotime($submission['created_at']))) : '-'; ?></td>
                            <td>
                                <?php $st = $submission["status"] ?? ""; ?>
                                <?php if ($st === 'Eingereicht'): ?>
                                    <span class="badge pending">Ödeme Bekliyor</span>
                                <?php elseif ($st === 'Freigegeben'): ?>
                                    <span class="badge approved">Ödeme Bekliyor</span>
                                <?php elseif ($st === 'Bezahlt'): ?>
                                    <span class="badge paid">Ödendi</span>
                                <?php elseif ($st === 'Reddedildi'): ?>
                                    <span class="badge unpaid">Reddedildi</span>
                                <?php else: ?>
                                    <span class="badge unpaid">Bilinmiyor</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <?php $st = $submission["status"] ?? ""; ?>
                                <?php if ($user === "UserA" && $st === "Eingereicht"): ?>
                                    <button class="action-button" onclick="window.location.href='?action=approve&id=<?php echo htmlspecialchars($submission['id'] ?? ''); ?>'">Onayla</button>
                                <?php endif; ?>
                                <?php if ($user === "UserB" && ($st === "Freigegeben" || $st === "Eingereicht")): ?>
                                    <button class="action-button" onclick="window.location.href='?action=pay&id=<?php echo htmlspecialchars($submission['id'] ?? ''); ?>'">Ödendi</button>
                                <?php endif; ?>
                                <?php if ($user === 'UserB'): ?>
                                    <button class="action-button" style="background:#f59e0b" onclick="editSubmission('<?php echo htmlspecialchars($submission['id'] ?? ''); ?>')">Düzenle</button>
                                    <button class="action-button" style="background:#dc2626" onclick="deleteSubmission('<?php echo htmlspecialchars($submission['id'] ?? ''); ?>')">Sil</button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($submission["pdf_link"])): ?>
                                    <?php 
                                        // Cache'i önlemek için timestamp ekle
                                        $pdfUrl = htmlspecialchars($submission["pdf_link"]);
                                        $cacheBuster = '?t=' . time();
                                        // Eğer regenerated_at varsa onu kullan
                                        if (!empty($submission['regenerated_at'])) {
                                            $cacheBuster = '?t=' . strtotime($submission['regenerated_at']);
                                        } elseif (!empty($submission['created_at'])) {
                                            $cacheBuster = '?t=' . strtotime($submission['created_at']);
                                        }
                                    ?>
                                    <a href="<?php echo $pdfUrl . $cacheBuster; ?>" class="pdf-link" target="_blank">PDF görüntüle</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">Veri yok</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <div class="filters">
            <div>
                <label>Yıl</label>
                <select id="year">
                    <option value="">Hepsi</option>
                    <?php
                        $years = [];
                        foreach ($submissions as $s) {
                            $y = (int)date('Y', strtotime($s['created_at'] ?? date('c')));
                            $years[$y] = true;
                        }
                        $years = array_keys($years);
                        rsort($years);
                        foreach ($years as $y) {
                            $sel = ($filterYear === (int)$y) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($y).'" '.$sel.'>'.htmlspecialchars($y).'</option>';
                        }
                    ?>
                </select>
            </div>
            <div>
                <label>Ay</label>
                <select id="month">
                    <option value="">Hepsi</option>
                    <?php
                        $aylar = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
                        foreach ($aylar as $num=>$nm) {
                            $sel = ($filterMonth === (int)$num) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($num).'" '.$sel.'>'.htmlspecialchars($nm).'</option>';
                        }
                    ?>
                </select>
            </div>
            <div>
                <label>Kişi</label>
                <select id="kisi">
                    <option value="">Hepsi</option>
                    <?php
                        // Benzersiz isim listesi (tüm submissions üzerinden)
                        $kisiList = [];
                        foreach ($submissions as $s) {
                            $nm = trim(($s['isim'] ?? '').' '.($s['soyisim'] ?? ''));
                            if ($nm !== '') { $kisiList[$nm] = true; }
                        }
                        $kisiNames = array_keys($kisiList);
                        sort($kisiNames, SORT_LOCALE_STRING);
                        foreach ($kisiNames as $nm) {
                            $sel = ($kisiFilter === $nm) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($nm).'" '.$sel.'>'.htmlspecialchars($nm).'</option>';
                        }
                    ?>
                </select>
            </div>
            <div>
                <label>Birim</label>
                <select id="birim">
                    <option value="">Hepsi</option>
                    <?php
                        foreach ($birimMap as $code => $label) {
                            $sel = ($birimFilter === $code) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($code).'" '.$sel.'>'.htmlspecialchars($label).'</option>';
                        }
                    ?>
                </select>
            </div>
            <div>
                <label>BYK</label>
                <select id="region">
                    <option value="">Hepsi</option>
                    <?php
                        $bolgeler = ['AT','KT','GT','KGT'];
                        foreach ($bolgeler as $r) {
                            $sel = ($regionFilter === $r) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($r).'" '.$sel.'>'.htmlspecialchars($r).'</option>';
                        }
                    ?>
                </select>
            </div>
            <div>
                <label>Durum</label>
                <select id="status">
                    <?php
                        $statuses = [
                            '' => 'Hepsi',
                            'Eingereicht' => 'Ödeme Bekliyor',
                            'Freigegeben' => 'Ödeme Bekliyor',
                            'Bezahlt' => 'Ödendi'
                        ];
                        foreach ($statuses as $val => $label) {
                            $sel = ($statusFilter === $val) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($val).'" '.$sel.'>'.htmlspecialchars($label).'</option>';
                        }
                    ?>
                </select>
            </div>
            <div style="grid-column: 1 / -1; text-align:center; margin-top: 10px;">
                <button class="action-button" onclick="applyFilters()">Filtrele</button>
                <button class="action-button" style="background:#2d6cdf" onclick="exportCSV()">CSV Dışa Aktar</button>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="generate_pdf_pdfmake.js"></script>
        <script>
            // PHP'den gelen submission verilerini JavaScript'e aktar
            window.submissionsData = <?php echo json_encode($submissions, JSON_UNESCAPED_UNICODE); ?>;
        </script>

        <script>
            function applyFilters() {
                const year = document.getElementById('year').value;
                const month = document.getElementById('month').value;
                const kisi = document.getElementById('kisi').value;
                const birim = document.getElementById('birim').value;
                const region = document.getElementById('region').value;
                const status = document.getElementById('status').value;
                const params = new URLSearchParams(window.location.search);
                if (year) params.set('year', year); else params.delete('year');
                if (month) params.set('month', month); else params.delete('month');
                if (kisi) params.set('kisi', kisi); else params.delete('kisi');
                if (birim) params.set('birim', birim); else params.delete('birim');
                if (region) params.set('region', region); else params.delete('region');
                if (status) params.set('status', status); else params.delete('status');
                window.location.search = params.toString();
            }
            function exportCSV() {
                const year = document.getElementById('year').value;
                const month = document.getElementById('month').value;
                const kisi = document.getElementById('kisi').value;
                const birim = document.getElementById('birim').value;
                const region = document.getElementById('region').value;
                const status = document.getElementById('status').value;
                const params = new URLSearchParams(window.location.search);
                if (year) params.set('year', year); else params.delete('year');
                if (month) params.set('month', month); else params.delete('month');
                if (kisi) params.set('kisi', kisi); else params.delete('kisi');
                if (birim) params.set('birim', birim); else params.delete('birim');
                if (region) params.set('region', region); else params.delete('region');
                if (status) params.set('status', status); else params.delete('status');
                params.set('action','export_csv');
                window.location.search = params.toString();
            }
            
            async function deleteSubmission(id) {
                console.log('Silme işlemi başlatıldı - ID:', id);
                
                if (confirm('Bu gider başvurusunu kalıcı olarak silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz ve PDF dosyası da silinecektir.')) {
                    // Loading göstergesi
                    const button = event.target;
                    const originalText = button.textContent;
                    button.disabled = true;
                    button.textContent = 'Siliniyor...';
                    
                    try {
                        // AJAX ile silme işlemi
                        const response = await fetch('delete_submission.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ id: id })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Başarılı silme - satırı DOM'dan kaldır
                            const row = button.closest('tr');
                            if (row) {
                                row.style.opacity = '0.5';
                                row.style.transition = 'opacity 0.3s';
                                setTimeout(() => {
                                    row.remove();
                                }, 300);
                            }
                            
                            // Başarı mesajı
                            showMessage('✅ Gider başarıyla silindi!', 'success');
                        } else {
                            // Hata mesajı
                            showMessage('❌ Silme hatası: ' + (result.message || 'Bilinmeyen hata'), 'error');
                            button.disabled = false;
                            button.textContent = originalText;
                        }
                    } catch (error) {
                        console.error('Silme hatası:', error);
                        showMessage('❌ Bağlantı hatası!', 'error');
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                }
            }
            
            function showMessage(message, type) {
                // Mevcut mesajları temizle
                const existingMessages = document.querySelectorAll('.flash-message');
                existingMessages.forEach(msg => msg.remove());
                
                // Yeni mesaj oluştur
                const messageDiv = document.createElement('div');
                messageDiv.className = 'flash-message';
                messageDiv.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 600;
                    z-index: 10000;
                    max-width: 400px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    ${type === 'success' ? 'background: #10b981;' : 'background: #ef4444;'}
                `;
                messageDiv.textContent = message;
                
                document.body.appendChild(messageDiv);
                
                // 3 saniye sonra kaldır
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                    messageDiv.style.transition = 'opacity 0.3s';
                    setTimeout(() => {
                        if (messageDiv.parentNode) {
                            messageDiv.parentNode.removeChild(messageDiv);
                        }
                    }, 300);
                }, 3000);
            }
            
        </script>
        

        

        <?php if ($user === 'UserB'): ?>
            <button class="clear-button" onclick="window.location.href='?action=clear'">Listeyi Temizle</button>
        <?php endif; ?>
        <button class="logout-button" onclick="window.location.href='logout.php'">Çıkış</button>
    </div>

    <!-- Düzenleme Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; overflow-y: auto;">
        <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 800px; width: 95%; max-height: 90vh; overflow-y: auto;">
            <h2 style="margin: 0 0 20px; text-align: center; color: #009872;">✏️ Gider Düzenleme</h2>
            
            <form id="editForm">
                <input type="hidden" id="editSubmissionId" name="submission_id">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">İsim</label>
                        <input type="text" id="editName" name="name" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Soyisim</label>
                        <input type="text" id="editSurname" name="surname" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">IBAN</label>
                        <input type="text" id="editIban" name="iban" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Toplam (€) <span style="font-size: 11px; color: #666; font-weight: normal;">(Otomatik hesaplanır)</span></label>
                        <input type="number" id="editTotal" name="total" step="0.01" style="width: 100%; padding: 10px; border: 2px solid #009872; border-radius: 8px; background: #f0fdf4; font-weight: 600; font-size: 16px; color: #009872;" readonly required>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">Gider Kalemleri</label>
                    <div id="editItemsContainer" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #f9f9f9;">
                        <!-- Kalemler buraya dinamik olarak yüklenecek -->
                    </div>
                    <button type="button" onclick="addEditItem()" style="margin-top: 10px; padding: 8px 15px; background: #009872; color: white; border: none; border-radius: 8px; cursor: pointer;">+ Kalem Ekle</button>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">📎 Mevcut Ekler</label>
                    <div id="editAttachmentsContainer" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #f9f9f9; max-height: 200px; overflow-y: auto;">
                        <!-- Ekler buraya yüklenecek -->
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">📎 Yeni Ek Ekle</label>
                    <input type="file" id="editNewAttachments" accept=".jpg,.jpeg,.png,.webp,.heic,.pdf" multiple style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Desteklenen formatlar: JPG, PNG, WebP, HEIC, PDF (Çoklu seçim)</div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="button" onclick="saveEdit()" style="padding: 12px 30px; background: #009872; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; margin-right: 10px;">💾 Kaydet</button>
                    <button type="button" onclick="closeEditModal()" style="padding: 12px 30px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600;">❌ İptal</button>
                </div>
                
                <div id="editError" style="color: #dc2626; text-align: center; margin-top: 15px; font-size: 14px; display: none;"></div>
            </form>
        </div>
    </div>

    <script>
        let currentSubmission = null;
        
        function editSubmission(id) {
            // Submission verilerini al
            const submission = getSubmissionById(id);
            if (!submission) {
                alert('Gider bulunamadı!');
                return;
            }
            
            currentSubmission = submission;
            
            // Modal'ı doldur
            document.getElementById('editSubmissionId').value = id;
            document.getElementById('editName').value = submission.isim || '';
            document.getElementById('editSurname').value = submission.soyisim || '';
            document.getElementById('editIban').value = submission.iban || '';
            document.getElementById('editTotal').value = submission.total || '';
            
            // Kalemleri yükle
            loadEditItems(submission.items || []);
            
            // Ekleri yükle
            loadEditAttachments(submission);
            
            // Toplam tutarı hesapla
            setTimeout(() => calculateEditTotal(), 100); // Kısa bir gecikmeyle input'lar hazır olduğunda hesapla
            
            // Modal'ı göster
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function getSubmissionById(id) {
            // PHP'den gelen submission verilerini JavaScript'te kullanabilmek için
            // Bu veriler sayfa yüklendiğinde JavaScript'e aktarılmalı
            return window.submissionsData ? window.submissionsData.find(s => s.id === id) : null;
        }
        
        function loadEditAttachments(submission) {
            const container = document.getElementById('editAttachmentsContainer');
            container.innerHTML = '';
            
            console.log('Submission verisi:', submission);
            
            // PDF dosyasını kontrol et
            if (submission && submission.pdf_link) {
                const pdfDiv = document.createElement('div');
                pdfDiv.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 8px; margin-bottom: 8px; background: white; border-radius: 6px; border: 1px solid #ddd;';
                
                pdfDiv.innerHTML = `
                    <div style="display: flex; align-items: center; flex: 1;">
                        <span style="margin-right: 8px; font-size: 16px;">📄</span>
                        <span style="font-size: 14px;">Gider Formu PDF</span>
                    </div>
                    <div style="display: flex; gap: 5px;">
                        <button type="button" onclick="previewAttachment('${submission.pdf_link}', 'Gider Formu')" style="padding: 4px 8px; background: #009872; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">👁️ Görüntüle</button>
                        <button type="button" onclick="downloadAttachment('${submission.pdf_link}', 'Gider Formu.pdf')" style="padding: 4px 8px; background: #6b7280; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">⬇️ İndir</button>
                    </div>
                `;
                
                container.appendChild(pdfDiv);
            }
            
            // Items'daki ekleri kontrol et
            if (submission && submission.items) {
                let hasItemAttachments = false;
                
                submission.items.forEach((item, itemIndex) => {
                    if (item.attachments && item.attachments.length > 0) {
                        hasItemAttachments = true;
                        item.attachments.forEach((attachment, attachIndex) => {
                            const attachmentDiv = document.createElement('div');
                            attachmentDiv.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 8px; margin-bottom: 8px; background: white; border-radius: 6px; border: 1px solid #ddd;';
                            
                            const fileName = attachment.name || `Ek ${itemIndex + 1}-${attachIndex + 1}`;
                            const fileSize = attachment.size ? ` (${formatFileSize(attachment.size)})` : '';
                            const fileType = getFileTypeIcon(attachment.type || '');
                            
                            attachmentDiv.innerHTML = `
                                <div style="display: flex; align-items: center; flex: 1;">
                                    <span style="margin-right: 8px; font-size: 16px;">${fileType}</span>
                                    <span style="font-size: 14px;">${fileName}${fileSize}</span>
                                </div>
                                <div style="display: flex; gap: 5px;">
                                    <button type="button" onclick="previewAttachment('${attachment.url || ''}', '${fileName}')" style="padding: 4px 8px; background: #009872; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">👁️ Görüntüle</button>
                                    <button type="button" onclick="downloadAttachment('${attachment.url || ''}', '${fileName}')" style="padding: 4px 8px; background: #6b7280; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">⬇️ İndir</button>
                                </div>
                            `;
                            
                            container.appendChild(attachmentDiv);
                        });
                    }
                });
                
                if (!submission.pdf_link && !hasItemAttachments) {
                    container.innerHTML = '<div style="color: #666; font-style: italic;">Ek dosya bulunmuyor</div>';
                }
            } else if (!submission || !submission.pdf_link) {
                container.innerHTML = '<div style="color: #666; font-style: italic;">Ek dosya bulunmuyor</div>';
            }
        }
        
        function formatFileSize(bytes) {
            if (!bytes) return '';
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        function getFileTypeIcon(type) {
            if (type.includes('pdf')) return '📄';
            if (type.includes('image')) return '🖼️';
            return '📎';
        }
        
        function previewAttachment(url, fileName) {
            if (!url) {
                alert('Dosya URL\'si bulunamadı!');
                return;
            }
            
            // Yeni sekmede aç
            window.open(url, '_blank');
        }
        
        function downloadAttachment(url, fileName) {
            if (!url) {
                alert('Dosya URL\'si bulunamadı!');
                return;
            }
            
            // İndirme linki oluştur
            const link = document.createElement('a');
            link.href = url;
            link.download = fileName;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function loadEditItems(items) {
            const container = document.getElementById('editItemsContainer');
            container.innerHTML = '';
            
            items.forEach((item, index) => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'edit-item';
                itemDiv.style.cssText = 'border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white; position: relative;';
                
                itemDiv.innerHTML = `
                    <button type="button" onclick="removeEditItem(this)" style="position: absolute; top: 5px; right: 5px; background: #dc2626; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 12px;">Sil</button>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                        <div>
                            <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Tarih</label>
                            <input type="date" name="edit_items[${index}][tarih]" value="${item.tarih || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">BYK</label>
                            <select name="edit_items[${index}][region]" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required>
                                <option value="AT" ${item.region === 'AT' ? 'selected' : ''}>AT</option>
                                <option value="KT" ${item.region === 'KT' ? 'selected' : ''}>KT</option>
                                <option value="GT" ${item.region === 'GT' ? 'selected' : ''}>GT</option>
                                <option value="KGT" ${item.region === 'KGT' ? 'selected' : ''}>KGT</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                        <div>
                            <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Birim</label>
                            <select name="edit_items[${index}][birim]" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required>
                                <option value="baskan" ${item.birim === 'uye' ? 'selected' : ''}>Başkan</option>
                                <option value="byk" ${item.birim === 'byk' ? 'selected' : ''}>BYK Üyesi</option>
                                <option value="egitim" ${item.birim === 'egitim' ? 'selected' : ''}>Eğitim</option>
                                <option value="fuar" ${item.birim === 'fuar' ? 'selected' : ''}>Fuar</option>
                                <option value="gob" ${item.birim === 'gob' ? 'selected' : ''}>Spor/Gezi (GOB)</option>
                                <option value="hacumre" ${item.birim === 'hacumre' ? 'selected' : ''}>Hac/Umre</option>
                                <option value="idair" ${item.birim === 'idair' ? 'selected' : ''}>İdari İşler</option>
                                <option value="irsad" ${item.birim === 'irsad' ? 'selected' : ''}>İrşad</option>
                                <option value="kurumsal" ${item.birim === 'kurumsal' ? 'selected' : ''}>Kurumsal İletişim</option>
                                <option value="muhasebe" ${item.birim === 'muhasebe' ? 'selected' : ''}>Muhasebe</option>
                                <option value="ortaogretim" ${item.birim === 'ortaogretim' ? 'selected' : ''}>Orta Öğretim</option>
                                <option value="raggal" ${item.birim === 'raggal' ? 'selected' : ''}>Raggal</option>
                                <option value="sosyal" ${item.birim === 'sosyal' ? 'selected' : ''}>Sosyal Hizmetler</option>
                                <option value="tanitma" ${item.birim === 'tanitma' ? 'selected' : ''}>Tanıtma</option>
                                <option value="teftis" ${item.birim === 'teftis' ? 'selected' : ''}>Teftiş</option>
                                <option value="teskilatlanma" ${item.birim === 'teskilatlanma' ? 'selected' : ''}>Teşkilatlanma</option>
                                <option value="universiteler" ${item.birim === 'universiteler' ? 'selected' : ''}>Üniversiteler</option>
                                <option value="baska" ${item.birim === 'baska' ? 'selected' : ''}>Başka</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Kategori</label>
                            <select name="edit_items[${index}][gider_turu]" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required>
                                <option value="genel" ${item.gider_turu === 'genel' ? 'selected' : ''}>Genel</option>
                                <option value="ikram" ${item.gider_turu === 'ikram' ? 'selected' : ''}>İkram</option>
                                <option value="ulasim" ${item.gider_turu === 'ulasim' ? 'selected' : ''}>Ulaşım</option>
                                <option value="yakit" ${item.gider_turu === 'yakit' ? 'selected' : ''}>Yakıt</option>
                                <option value="malzeme" ${item.gider_turu === 'malzeme' ? 'selected' : ''}>Malzeme</option>
                                <option value="konaklama" ${item.gider_turu === 'konaklama' ? 'selected' : ''}>Konaklama</option>
                                <option value="buro" ${item.gider_turu === 'buro' ? 'selected' : ''}>Büro</option>
                                <option value="diger" ${item.gider_turu === 'diger' ? 'selected' : ''}>Diğer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Tutar (€)</label>
                        <input type="number" name="edit_items[${index}][tutar]" value="${item.tutar || ''}" step="0.01" class="item-tutar-input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required oninput="calculateEditTotal()">
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Açıklama</label>
                        <textarea name="edit_items[${index}][aciklama]" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; resize: vertical;">${item.aciklama || ''}</textarea>
                    </div>
                    
                    ${item.rota ? `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Rota</label>
                            <input type="text" name="edit_items[${index}][rota]" value="${item.rota || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Km</label>
                            <input type="number" name="edit_items[${index}][km]" value="${item.km || ''}" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                        </div>
                    </div>
                    ` : ''}
                `;
                
                container.appendChild(itemDiv);
            });
        }
        
        function addEditItem() {
            const container = document.getElementById('editItemsContainer');
            const index = container.children.length;
            
            const itemDiv = document.createElement('div');
            itemDiv.className = 'edit-item';
            itemDiv.style.cssText = 'border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white; position: relative;';
            
            itemDiv.innerHTML = `
                <button type="button" onclick="removeEditItem(this)" style="position: absolute; top: 5px; right: 5px; background: #dc2626; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 12px;">Sil</button>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Tarih</label>
                        <input type="date" name="edit_items[${index}][tarih]" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">BYK</label>
                        <select name="edit_items[${index}][region]" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required>
                            <option value="AT">AT</option>
                            <option value="KT">KT</option>
                            <option value="GT">GT</option>
                            <option value="KGT">KGT</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Birim</label>
                        <select name="edit_items[${index}][birim]" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required>
                            <option value="baskan">Başkan</option>
                            <option value="byk">BYK Üyesi</option>
                            <option value="egitim">Eğitim</option>
                            <option value="fuar">Fuar</option>
                            <option value="gob">Spor/Gezi (GOB)</option>
                            <option value="hacumre">Hac/Umre</option>
                            <option value="idair">İdari İşler</option>
                            <option value="irsad">İrşad</option>
                            <option value="kurumsal">Kurumsal İletişim</option>
                            <option value="muhasebe">Muhasebe</option>
                            <option value="ortaogretim">Orta Öğretim</option>
                            <option value="raggal">Raggal</option>
                            <option value="sosyal">Sosyal Hizmetler</option>
                            <option value="tanitma">Tanıtma</option>
                            <option value="teftis">Teftiş</option>
                            <option value="teskilatlanma">Teşkilatlanma</option>
                            <option value="universiteler">Üniversiteler</option>
                            <option value="baska">Başka</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Kategori</label>
                        <select name="edit_items[${index}][gider_turu]" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required>
                            <option value="genel">Genel</option>
                            <option value="ikram">İkram</option>
                            <option value="ulasim">Ulaşım</option>
                            <option value="yakit">Yakıt</option>
                            <option value="malzeme">Malzeme</option>
                            <option value="konaklama">Konaklama</option>
                            <option value="buro">Büro</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Tutar (€)</label>
                    <input type="number" name="edit_items[${index}][tutar]" step="0.01" class="item-tutar-input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required oninput="calculateEditTotal()">
                </div>
                
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600;">Açıklama</label>
                    <textarea name="edit_items[${index}][aciklama]" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; resize: vertical;"></textarea>
                </div>
            `;
            
            container.appendChild(itemDiv);
            
            // Yeni kalem eklenince toplam tutarı hesapla
            setTimeout(() => calculateEditTotal(), 50);
        }
        
        function removeEditItem(button) {
            button.closest('.edit-item').remove();
            calculateEditTotal(); // Toplam tutarı güncelle
        }
        
        // Düzenleme modal'ındaki toplam tutarı hesapla
        function calculateEditTotal() {
            const tutarInputs = document.querySelectorAll('.item-tutar-input');
            let total = 0;
            
            tutarInputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
            });
            
            // Toplam tutar input'unu güncelle
            const totalInput = document.getElementById('editTotal');
            if (totalInput) {
                totalInput.value = total.toFixed(2);
            }
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            currentSubmission = null;
        }
        
        async function saveEdit() {
            const formData = new FormData(document.getElementById('editForm'));
            const submissionId = formData.get('submission_id');
            
            // Kalemleri topla ve toplam tutarı hesapla
            const items = [];
            let calculatedTotal = 0;
            const itemElements = document.querySelectorAll('.edit-item');
            
            itemElements.forEach((itemEl, index) => {
                const tutar = parseFloat(itemEl.querySelector(`input[name="edit_items[${index}][tutar]"]`).value) || 0;
                calculatedTotal += tutar;
                
                const item = {
                    tarih: itemEl.querySelector(`input[name="edit_items[${index}][tarih]"]`).value,
                    region: itemEl.querySelector(`select[name="edit_items[${index}][region]"]`).value,
                    birim: itemEl.querySelector(`select[name="edit_items[${index}][birim]"]`).value,
                    gider_turu: itemEl.querySelector(`select[name="edit_items[${index}][gider_turu]"]`).value,
                    tutar: tutar,
                    aciklama: itemEl.querySelector(`textarea[name="edit_items[${index}][aciklama]"]`).value,
                };
                
                // Rota ve km varsa ekle
                const rotaInput = itemEl.querySelector(`input[name="edit_items[${index}][rota]"]`);
                const kmInput = itemEl.querySelector(`input[name="edit_items[${index}][km]"]`);
                if (rotaInput && kmInput) {
                    item.rota = rotaInput.value;
                    item.km = parseFloat(kmInput.value) || 0;
                }
                
                items.push(item);
            });
            
            // Yeni ekleri kontrol et
            const newAttachmentsInput = document.getElementById('editNewAttachments');
            let hasNewAttachments = false;
            let newAttachmentsFormData = new FormData();
            
            if (newAttachmentsInput && newAttachmentsInput.files && newAttachmentsInput.files.length > 0) {
                hasNewAttachments = true;
                newAttachmentsFormData.append('submission_id', submissionId);
                newAttachmentsFormData.append('name', formData.get('name'));
                newAttachmentsFormData.append('surname', formData.get('surname'));
                newAttachmentsFormData.append('iban', formData.get('iban'));
                newAttachmentsFormData.append('total', calculatedTotal.toFixed(2)); // Hesaplanan toplam
                newAttachmentsFormData.append('items_json', JSON.stringify(items));
                
                // Yeni dosyaları ekle
                for (let i = 0; i < newAttachmentsInput.files.length; i++) {
                    newAttachmentsFormData.append('new_attachments[]', newAttachmentsInput.files[i]);
                }
            }
            
            try {
                let response, result;
                
                if (hasNewAttachments) {
                    // Yeni eklerle birlikte güncelle
                    response = await fetch('update_submission_with_attachments.php', {
                        method: 'POST',
                        body: newAttachmentsFormData
                    });
                } else {
                    // Sadece veri güncelle
                    const data = {
                        submission_id: submissionId,
                        name: formData.get('name'),
                        surname: formData.get('surname'),
                        iban: formData.get('iban'),
                        total: parseFloat(calculatedTotal.toFixed(2)), // Hesaplanan toplam
                        items: items
                    };
                    
                    response = await fetch('update_submission.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                }
                
                result = await response.json();
                
                if (result.success) {
                    alert('Gider başarıyla güncellendi!' + (hasNewAttachments ? ' Yeni ekler eklendi.' : ''));
                    closeEditModal();
                    location.reload(); // Sayfayı yenile
                } else {
                    document.getElementById('editError').textContent = result.message || 'Güncelleme başarısız!';
                    document.getElementById('editError').style.display = 'block';
                }
            } catch (error) {
                console.error('Güncelleme hatası:', error);
                document.getElementById('editError').textContent = 'Bağlantı hatası!';
                document.getElementById('editError').style.display = 'block';
            }
        }
    </script>
</body>
</html>
