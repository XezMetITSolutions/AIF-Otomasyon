<?php
/**
 * İstişare Oylama Formu
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Yetki kontrolü (Üye ve Super Admin erişebilir)
Middleware::requireRole(['super_admin', 'uye']);

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$sessionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($sessionId <= 0) {
    header('Location: istisareler.php');
    exit;
}

$session = $db->fetch("SELECT * FROM istisare_sessions WHERE id = ?", [$sessionId]);
if (!$session) {
    header('Location: istisareler.php');
    exit;
}

$pageTitle = 'İstişare Detayı: ' . $session['baslik'];

// Tabloyu oluştur (eğer yoksa)
$db->query("
    CREATE TABLE IF NOT EXISTS istisare_oylama (
        id INT AUTO_INCREMENT PRIMARY KEY,
        voter_id VARCHAR(255) NOT NULL,
        sube_ismi VARCHAR(255) NULL,
        secilen_1 VARCHAR(255) NOT NULL,
        secilen_2 VARCHAR(255) NOT NULL,
        secilen_3 VARCHAR(255) NOT NULL,
        secilen_4 VARCHAR(255) NOT NULL,
        secilen_5 VARCHAR(255) NOT NULL,
        notlar TEXT NULL,
        tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Eski UNIQUE KEY'i kaldır (Çoklu kayda izin vermek için)
try {
    $db->query("ALTER TABLE istisare_oylama DROP INDEX unique_vote");
} catch (Exception $e) { }

// Sütun tiplerini güncelle (INT'den VARCHAR'a geçiş ve eksik sütunlar)
$cols = $db->fetchAll("DESC istisare_oylama");
$colNames = array_column($cols, 'Field');
$db->query("ALTER TABLE istisare_oylama MODIFY voter_id VARCHAR(255) NOT NULL");
if (!in_array('sube_ismi', $colNames)) $db->query("ALTER TABLE istisare_oylama ADD COLUMN sube_ismi VARCHAR(255) NULL AFTER voter_id");
$db->query("ALTER TABLE istisare_oylama MODIFY secilen_1 VARCHAR(255) NOT NULL");
$db->query("ALTER TABLE istisare_oylama MODIFY secilen_2 VARCHAR(255) NOT NULL");
$db->query("ALTER TABLE istisare_oylama MODIFY secilen_3 VARCHAR(255) NOT NULL");
if (!in_array('secilen_4', $colNames)) {
    $db->query("ALTER TABLE istisare_oylama ADD COLUMN secilen_4 VARCHAR(255) NOT NULL AFTER secilen_3");
} else {
    $db->query("ALTER TABLE istisare_oylama MODIFY secilen_4 VARCHAR(255) NOT NULL");
}
if (!in_array('secilen_5', $colNames)) {
    $db->query("ALTER TABLE istisare_oylama ADD COLUMN secilen_5 VARCHAR(255) NOT NULL AFTER secilen_4");
} else {
    $db->query("ALTER TABLE istisare_oylama MODIFY secilen_5 VARCHAR(255) NOT NULL");
}

$message = '';
$error = '';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voter_id = trim($_POST['voter_id'] ?? '');
    $sube_ismi = trim($_POST['sube_ismi'] ?? '');
    $s = [];
    for($i=1; $i<=4; $i++) {
        $s[$i] = trim($_POST['secilen_'.$i] ?? '');
    }
    $s[5] = ''; // 5. tercih kaldırıldı, varsa temizle
    $notlar = $_POST['notlar'] ?? '';

    $secimler = array_filter($s);

    if (empty($voter_id)) {
        $error = 'Lütfen oy veren kişiyi giriniz.';
    } elseif (empty($secimler)) {
        $error = 'Lütfen en az bir aday ismi giriniz.';
    } elseif (count(array_unique($secimler)) < count($secimler)) {
        $error = 'Lütfen aynı ismi birden fazla kez girmeyiniz.';
    } else {
        try {
            $existing = $db->fetch("SELECT id FROM istisare_oylama WHERE voter_id = ? AND session_id = ?", [$voter_id, $sessionId]);
            if ($existing) {
                $db->query("
                    UPDATE istisare_oylama 
                    SET sube_ismi=?, secilen_1=?, secilen_2=?, secilen_3=?, secilen_4=?, secilen_5=?, notlar=?, tarih=CURRENT_TIMESTAMP 
                    WHERE id=?
                ", [$sube_ismi, $s[1], $s[2], $s[3], $s[4], $s[5], $notlar, $existing['id']]);
            } else {
                $db->query("
                    INSERT INTO istisare_oylama (voter_id, session_id, sube_ismi, secilen_1, secilen_2, secilen_3, secilen_4, secilen_5, notlar)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [$voter_id, $sessionId, $sube_ismi, $s[1], $s[2], $s[3], $s[4], $s[5], $notlar]);
            }

            $message = 'İşlem başarıyla kaydedildi.';
        } catch (Exception $e) {
            $error = 'Hata oluştu: ' . $e->getMessage();
        }
    }
}

// Tüm adayları ve oy verenleri (kullanıcıları) getir
$tumKullanicilar = $db->fetchAll("SELECT kullanici_id, ad, soyad FROM kullanicilar WHERE aktif = 1 ORDER BY ad, soyad");

// Mevcut oyunu getir (Son girilen kaydı referans al)
$mevcutOy = $db->fetch("SELECT * FROM istisare_oylama WHERE voter_id = ? AND session_id = ? ORDER BY tarih DESC LIMIT 1", [$user['name'], $sessionId]);

// Sonuçları hesapla
$sonuclar = [];
$votes = $db->fetchAll("
    SELECT t1.secilen_1, t1.secilen_2, t1.secilen_3, t1.secilen_4, t1.secilen_5 
    FROM istisare_oylama t1
    INNER JOIN (
        SELECT voter_id, MAX(id) AS latest_id
        FROM istisare_oylama
        WHERE session_id = ?
        GROUP BY voter_id
    ) t2 ON t1.id = t2.latest_id
    WHERE t1.session_id = ?
", [$sessionId, $sessionId]);
$stats = [];
foreach ($votes as $v) {
    for($i=1; $i<=4; $i++) {
        $name = trim($v['secilen_'.$i]);
        if (!empty($name)) {
            if (!isset($stats[$name])) {
                $stats[$name] = [
                    'total' => 0,
                    'score' => 0,
                    'ranks' => [1 => 0, 2 => 0, 3 => 0, 4 => 0]
                ];
            }
            $stats[$name]['total']++;
            $stats[$name]['ranks'][$i]++;
            
            // Puanlamaya göre ağırlıklı skor: 1: 1, 2: 0.5, 3: 0.33, 4: 0.25
            if ($i == 1) $stats[$name]['score'] += 1;
            elseif ($i == 2) $stats[$name]['score'] += 0.50;
            elseif ($i == 3) $stats[$name]['score'] += 0.33;
            elseif ($i == 4) $stats[$name]['score'] += 0.25;
        }
    }
}

// Ağırlıklı puana göre sırala
uasort($stats, function($a, $b) {
    if (abs($a['score'] - $b['score']) < 0.001) {
        // Puan eşitse toplam oy önceliği
        if ($a['total'] == $b['total']) return 0;
        return ($a['total'] < $b['total']) ? 1 : -1;
    }
    return ($a['score'] < $b['score']) ? 1 : -1;
});

foreach ($stats as $name => $data) {
    $sonuclar[] = [
        'name' => $name,
        'votes' => $data['total'],
        'score' => $data['score'],
        'ranks' => $data['ranks']
    ];
}


include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-vote-yea me-2"></i>Başkanlık İstişare Oylaması
            </h1>
            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></i>Güncel Sonuçları Gör
            </button>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info d-flex justify-content-between align-items-center shadow-sm">
                    <div>
                        <h6 class="mb-1"><i class="fas fa-info-circle me-2"></i>Aktif İstişare: <b><?php echo htmlspecialchars($session['baslik']); ?></b></h6>
                        <div class="small">
                            <span class="me-3"><b>Şube:</b> <?php echo htmlspecialchars($session['sube_ismi']); ?></span>
                            <span><b>İstişare Kurulu:</b> <?php echo htmlspecialchars($session['kurul_uyeleri']); ?></span>
                        </div>
                    </div>
                    <a href="istisareler.php" class="btn btn-sm btn-light border">
                        <i class="fas fa-list me-1"></i> Tüm Liste
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Oylama Formu</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success mt-2"><?php echo $message; ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger mt-2"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Şube İsmi</label>
                                <input type="text" name="sube_ismi" class="form-control" placeholder="Şube ismini giriniz" value="<?php echo htmlspecialchars($mevcutOy['sube_ismi'] ?? $session['sube_ismi']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Oy Veren Kişi</label>
                                <input type="text" name="voter_id" class="form-control" placeholder="Oy veren kişinin ismini giriniz" value="<?php echo htmlspecialchars($mevcutOy['voter_id'] ?? ($auth->isSuperAdmin() ? '' : $user['name'])); ?>" required>
                                <div class="form-text">Kim adına oy kullanıldığını belirtiniz.</div>
                            </div>

                            <hr>

                            <?php 
                            // Aday önerileri için datalist
                            if (!empty($stats)): ?>
                            <datalist id="adayOnerileri">
                                <?php foreach (array_keys($stats) as $aday): ?>
                                    <option value="<?php echo htmlspecialchars($aday); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <?php endif; ?>

                            <?php for($i=1; $i<=4; $i++): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><?php echo $i; ?>. Aday Tercihi</label>
                                <input type="text" name="secilen_<?php echo $i; ?>" list="adayOnerileri" class="form-control" placeholder="Adayın ismini giriniz" value="<?php echo htmlspecialchars($mevcutOy['secilen_'.$i] ?? ''); ?>">
                            </div>
                            <?php endfor; ?>

                            <div class="mb-3">
                                <label class="form-label">Notlar</label>
                                <textarea name="notlar" class="form-control" rows="2"></textarea>
                            </div>

                            <button type="submit" name="submit_vote" class="btn btn-primary btn-lg w-100 mt-2">
                                <i class="fas fa-check-circle me-2"></i>Oyu Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Güncel Sonuçlar</h5>
                        <div>
                            <span class="badge bg-warning text-dark me-2"><?php echo count($votes); ?> Toplam Oy</span>
                            <a href="istisare-pdf.php?id=<?php echo $sessionId; ?>" target="_blank" class="btn btn-sm btn-outline-light" title="Sonuçları PDF olarak indir"><i class="fas fa-file-pdf"></i> PDF</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Puanlama Bilgisi -->
                        <div class="bg-light p-2 small border-bottom text-center">
                            <span class="badge bg-secondary">Puanlama Sistemi:</span>
                            <span class="mx-2">1. Tercih: <b>1 Puan</b></span> | 
                            <span class="mx-2">2. Tercih: <b>0.50 Puan</b></span> | 
                            <span class="mx-2">3. Tercih: <b>0.33 Puan</b></span> | 
                            <span class="mx-2">4. Tercih: <b>0.25 Puan</b></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Aday</th>
                                        <th class="text-center" width="100">Puan</th>
                                        <th class="text-center" width="100">Toplam Oy</th>
                                        <th class="text-center" width="220">Sıralama (1. - 4.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($sonuclar)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center p-5 text-muted">
                                                <i class="fas fa-info-circle mb-2 d-block fa-2x"></i>
                                                Henüz veri girilmemiş.
                                            </td>
                                        </tr>
                                    <?php else: 
                                        $rank = 1;
                                        foreach ($sonuclar as $s): 
                                    ?>
                                        <tr>
                                            <td><?php echo $rank++; ?></td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($s['name']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-success fs-6"><?php echo number_format($s['score'], 2); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?php echo $s['votes']; ?></span>
                                            </td>

                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1 small">
                                                    <?php if($s['ranks'][1] > 0) echo '<span class="badge bg-success" title="1. Sıra">1: '.$s['ranks'][1].'</span>'; ?>
                                                    <?php if($s['ranks'][2] > 0) echo '<span class="badge bg-primary" title="2. Sıra">2: '.$s['ranks'][2].'</span>'; ?>
                                                    <?php if($s['ranks'][3] > 0) echo '<span class="badge bg-info text-dark" title="3. Sıra">3: '.$s['ranks'][3].'</span>'; ?>
                                                    <?php if($s['ranks'][4] > 0) echo '<span class="badge bg-secondary" title="4. Sıra">4: '.$s['ranks'][4].'</span>'; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <?php if ($auth->isSuperAdmin() && !empty($votes)): ?>
                <div class="mt-4">
                    <h6><i class="fas fa-history me-2"></i>Son Kaydedilen Oylar (Sadece Admin Görür)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered bg-white">
                            <thead>
                                <tr class="table-light small">
                                    <th width="30">#</th>
                                    <th>Oy Veren</th>
                                    <th>Şube</th>
                                    <th>1. Tercih</th>
                                    <th>2. Tercih</th>
                                    <th>3. Tercih</th>
                                    <th>4. Tercih</th>
                                    <th>Notlar</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $lastVotes = $db->fetchAll("
                                    SELECT t1.* 
                                    FROM istisare_oylama t1
                                    INNER JOIN (
                                        SELECT voter_id, MAX(id) AS latest_id
                                        FROM istisare_oylama
                                        WHERE session_id = ?
                                        GROUP BY voter_id
                                    ) t2 ON t1.id = t2.latest_id
                                    WHERE t1.session_id = ?
                                    ORDER BY t1.tarih DESC LIMIT 100
                                ", [$sessionId, $sessionId]);
                                $voterNo = 1;
                                foreach ($lastVotes as $lv): ?>
                                    <tr class="small">
                                        <td><?php echo $voterNo++; ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($lv['voter_id']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['sube_ismi'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($lv['secilen_1']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['secilen_2']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['secilen_3']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['secilen_4']); ?></td>
                                        <td class="text-muted small italic"><?php echo htmlspecialchars($lv['notlar'] ?? '-'); ?></td>
                                        <td><?php echo date('H:i', strtotime($lv['tarih'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-5 pt-4 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Tüm İstişareler</h5>
                        <a href="istisareler.php" class="btn btn-sm btn-outline-primary">Tümünü Yönet</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm border card-table">
                            <thead class="table-light">
                                <tr class="small">
                                    <th>İstişare Başlığı / Şube</th>
                                    <th class="text-center">Durum</th>
                                    <th class="text-right text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $allSessions = $db->fetchAll("SELECT * FROM istisare_sessions ORDER BY eklenme_tarihi DESC LIMIT 10");
                                foreach ($allSessions as $as): ?>
                                    <tr class="<?php echo $as['id'] == $sessionId ? 'table-primary' : ''; ?> small">
                                        <td>
                                            <b><?php echo htmlspecialchars($as['baslik']); ?></b><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($as['sube_ismi']); ?></span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($as['durum'] === 'aktif'): ?>
                                                <span class="badge bg-success">AKTİF</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">KAPALI</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end align-middle">
                                            <a href="istisare-formu.php?id=<?php echo $as['id']; ?>" class="btn btn-xs btn-primary py-0 px-2 fw-bold" style="font-size: 10px;">GİT</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2').select2({
            placeholder: "İsim seçiniz...",
            allowClear: true,
            width: '100%'
        });
    }
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>
