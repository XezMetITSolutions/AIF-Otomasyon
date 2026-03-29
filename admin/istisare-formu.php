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

$pageTitle = 'Başkanlık İstişare Formu';

// Tabloyu oluştur (eğer yoksa)
$db->query("
    CREATE TABLE IF NOT EXISTS istisare_oylama (
        id INT AUTO_INCREMENT PRIMARY KEY,
        voter_id INT NOT NULL,
        sube_ismi VARCHAR(255) NULL,
        secilen_1 INT NOT NULL,
        secilen_2 INT NOT NULL,
        secilen_3 INT NOT NULL,
        secilen_4 INT NOT NULL,
        secilen_5 INT NOT NULL,
        notlar TEXT NULL,
        tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_vote (voter_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Eksik sütunları ekle (mevcut tablo varsa)
$cols = $db->fetchAll("DESC istisare_oylama");
$colNames = array_column($cols, 'Field');
if (!in_array('sube_ismi', $colNames)) $db->query("ALTER TABLE istisare_oylama ADD COLUMN sube_ismi VARCHAR(255) NULL AFTER voter_id");
if (!in_array('secilen_4', $colNames)) $db->query("ALTER TABLE istisare_oylama ADD COLUMN secilen_4 INT NOT NULL DEFAULT 0 AFTER secilen_3");
if (!in_array('secilen_5', $colNames)) $db->query("ALTER TABLE istisare_oylama ADD COLUMN secilen_5 INT NOT NULL DEFAULT 0 AFTER secilen_4");

$message = '';
$error = '';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vote'])) {
    $voter_id = $auth->isSuperAdmin() ? (int)$_POST['voter_id'] : $user['id'];
    $sube_ismi = $_POST['sube_ismi'] ?? '';
    $s1 = (int)$_POST['secilen_1'];
    $s2 = (int)$_POST['secilen_2'];
    $s3 = (int)$_POST['secilen_3'];
    $s4 = (int)$_POST['secilen_4'];
    $s5 = (int)$_POST['secilen_5'];
    $notlar = $_POST['notlar'] ?? '';

    $secimler = array_filter([$s1, $s2, $s3, $s4, $s5]);

    if ($voter_id === 0) {
        $error = 'Lütfen oy veren kişiyi seçiniz.';
    } elseif (count($secimler) < 5) {
        $error = 'Lütfen 5 isim de seçiniz.';
    } elseif (count(array_unique($secimler)) < 5) {
        $error = 'Lütfen birbirinden farklı 5 isim seçiniz.';
    } else {
        try {
            $db->query("
                INSERT INTO istisare_oylama (voter_id, sube_ismi, secilen_1, secilen_2, secilen_3, secilen_4, secilen_5, notlar)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    sube_ismi = VALUES(sube_ismi),
                    secilen_1 = VALUES(secilen_1),
                    secilen_2 = VALUES(secilen_2),
                    secilen_3 = VALUES(secilen_3),
                    secilen_4 = VALUES(secilen_4),
                    secilen_5 = VALUES(secilen_5),
                    notlar = VALUES(notlar),
                    tarih = CURRENT_TIMESTAMP
            ", [$voter_id, $sube_ismi, $s1, $s2, $s3, $s4, $s5, $notlar]);
            $message = 'İşlem başarıyla kaydedildi.';
        } catch (Exception $e) {
            $error = 'Hata oluştu: ' . $e->getMessage();
        }
    }
}

// Tüm adayları ve oy verenleri (kullanıcıları) getir
$tumKullanicilar = $db->fetchAll("SELECT kullanici_id, ad, soyad FROM kullanicilar WHERE aktif = 1 ORDER BY ad, soyad");

// Mevcut oyunu getir (Sadece admin değilse veya admin kendi oyuna bakıyorsa)
$mevcutOy = $db->fetch("SELECT * FROM istisare_oylama WHERE voter_id = ?", [$user['id']]);

// Sonuçları hesapla
$sonuclar = [];
$votes = $db->fetchAll("SELECT secilen_1, secilen_2, secilen_3, secilen_4, secilen_5 FROM istisare_oylama");
$counts = [];
foreach ($votes as $v) {
    for($i=1; $i<=5; $i++) {
        if ($v['secilen_'.$i]) {
            $counts[$v['secilen_'.$i]] = ($counts[$v['secilen_'.$i]] ?? 0) + 1;
        }
    }
}
arsort($counts);

foreach ($counts as $uid => $count) {
    $uInfo = $db->fetch("SELECT ad, soyad FROM kullanicilar WHERE kullanici_id = ?", [$uid]);
    if ($uInfo) {
        $sonuclar[] = [
            'name' => $uInfo['ad'] . ' ' . $uInfo['soyad'],
            'votes' => $count
        ];
    }
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
                                <input type="text" name="sube_ismi" class="form-control" placeholder="Şube ismini giriniz" value="<?php echo htmlspecialchars($mevcutOy['sube_ismi'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Oy Veren Kişi</label>
                                <?php if ($auth->isSuperAdmin()): ?>
                                    <select name="voter_id" class="form-select select2" required>
                                        <option value="">Kişi Seçiniz...</option>
                                        <?php foreach ($tumKullanicilar as $k): ?>
                                            <option value="<?php echo $k['kullanici_id']; ?>" <?php echo ($mevcutOy && $mevcutOy['voter_id'] == $k['kullanici_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($k['ad'] . ' ' . $k['soyad']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Admin olarak başkası adına oy girebilirsiniz.</div>
                                <?php else: ?>
                                    <div class="form-control bg-light"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <input type="hidden" name="voter_id" value="<?php echo $user['id']; ?>">
                                <?php endif; ?>
                            </div>

                            <hr>

                            <?php for($i=1; $i<=5; $i++): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><?php echo $i; ?>. Aday Tercihi</label>
                                <select name="secilen_<?php echo $i; ?>" class="form-select select2" required>
                                    <option value="">Seçiniz...</option>
                                    <?php foreach ($tumKullanicilar as $k): ?>
                                        <option value="<?php echo $k['kullanici_id']; ?>" <?php echo ($mevcutOy && $mevcutOy['secilen_'.$i] == $k['kullanici_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($k['ad'] . ' ' . $k['soyad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                        <span class="badge bg-warning text-dark"><?php echo count($votes); ?> Toplam Oy</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Aday</th>
                                        <th class="text-center" width="150">Alınan Oy Sayısı</th>
                                        <th class="text-center" width="100">Oran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($sonuclar)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center p-5 text-muted">
                                                <i class="fas fa-info-circle mb-2 d-block fa-2x"></i>
                                                Henüz veri girilmemiş.
                                            </td>
                                        </tr>
                                    <?php else: 
                                        $totalMentions = count($votes) * 5;
                                        $rank = 1;
                                        foreach ($sonuclar as $s): 
                                            $percent = round(($s['votes'] / ($totalMentions > 0 ? $totalMentions : 1)) * 100, 1);
                                    ?>
                                        <tr>
                                            <td><?php echo $rank++; ?></td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($s['name']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-primary fs-6"><?php echo $s['votes']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percent; ?>%;" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $percent; ?>%
                                                    </div>
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
                                    <th>Oy Veren</th>
                                    <th>Şube</th>
                                    <th>1. Tercih</th>
                                    <th>2. Tercih</th>
                                    <th>3. Tercih</th>
                                    <th>4. Tercih</th>
                                    <th>5. Tercih</th>
                                    <th>Notlar</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $lastVotes = $db->fetchAll("
                                    SELECT i.*, 
                                           CONCAT(v.ad, ' ', v.soyad) as voter_name,
                                           CONCAT(s1.ad, ' ', s1.soyad) as s1_name,
                                           CONCAT(s2.ad, ' ', s2.soyad) as s2_name,
                                           CONCAT(s3.ad, ' ', s3.soyad) as s3_name,
                                           CONCAT(s4.ad, ' ', s4.soyad) as s4_name,
                                           CONCAT(s5.ad, ' ', s5.soyad) as s5_name
                                    FROM istisare_oylama i
                                    JOIN kullanicilar v ON i.voter_id = v.kullanici_id
                                    LEFT JOIN kullanicilar s1 ON i.secilen_1 = s1.kullanici_id
                                    LEFT JOIN kullanicilar s2 ON i.secilen_2 = s2.kullanici_id
                                    LEFT JOIN kullanicilar s3 ON i.secilen_3 = s3.kullanici_id
                                    LEFT JOIN kullanicilar s4 ON i.secilen_4 = s4.kullanici_id
                                    LEFT JOIN kullanicilar s5 ON i.secilen_5 = s5.kullanici_id
                                    ORDER BY i.tarih DESC LIMIT 50
                                ");
                                foreach ($lastVotes as $lv): ?>
                                    <tr class="small">
                                        <td class="fw-bold"><?php echo htmlspecialchars($lv['voter_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['sube_ismi'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($lv['s1_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['s2_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['s3_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['s4_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['s5_name']); ?></td>
                                        <td class="text-muted small italic"><?php echo htmlspecialchars($lv['notlar'] ?? '-'); ?></td>
                                        <td><?php echo date('H:i', strtotime($lv['tarih'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
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
