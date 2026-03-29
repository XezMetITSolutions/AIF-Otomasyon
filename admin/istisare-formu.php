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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vote'])) {
    $voter_id = trim($_POST['voter_id'] ?? '');
    $sube_ismi = trim($_POST['sube_ismi'] ?? '');
    $s = [];
    for($i=1; $i<=5; $i++) {
        $s[$i] = trim($_POST['secilen_'.$i] ?? '');
    }
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
            $db->query("
                INSERT INTO istisare_oylama (voter_id, sube_ismi, secilen_1, secilen_2, secilen_3, secilen_4, secilen_5, notlar)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ", [$voter_id, $sube_ismi, $s[1], $s[2], $s[3], $s[4], $s[5], $notlar]);
            $message = 'İşlem başarıyla kaydedildi.';
        } catch (Exception $e) {
            $error = 'Hata oluştu: ' . $e->getMessage();
        }
    }
}

// Tüm adayları ve oy verenleri (kullanıcıları) getir
$tumKullanicilar = $db->fetchAll("SELECT kullanici_id, ad, soyad FROM kullanicilar WHERE aktif = 1 ORDER BY ad, soyad");

// Mevcut oyunu getir (Son girilen kaydı referans al)
$mevcutOy = $db->fetch("SELECT * FROM istisare_oylama WHERE voter_id = ? ORDER BY tarih DESC LIMIT 1", [$user['name']]);

// Sonuçları hesapla
$sonuclar = [];
$votes = $db->fetchAll("SELECT secilen_1, secilen_2, secilen_3, secilen_4, secilen_5 FROM istisare_oylama");
$counts = [];
foreach ($votes as $v) {
    for($i=1; $i<=5; $i++) {
        $name = trim($v['secilen_'.$i]);
        if ($name) {
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }
    }
}
arsort($counts);

foreach ($counts as $name => $count) {
    $sonuclar[] = [
        'name' => $name,
        'votes' => $count
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
                                <input type="text" name="voter_id" class="form-control" placeholder="Oy veren kişinin ismini giriniz" value="<?php echo htmlspecialchars($mevcutOy['voter_id'] ?? ($auth->isSuperAdmin() ? '' : $user['name'])); ?>" required>
                                <div class="form-text">Kim adına oy kullanıldığını belirtiniz.</div>
                            </div>

                            <hr>

                            <?php for($i=1; $i<=5; $i++): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><?php echo $i; ?>. Aday Tercihi</label>
                                <input type="text" name="secilen_<?php echo $i; ?>" class="form-control" placeholder="Adayın ismini giriniz" value="<?php echo htmlspecialchars($mevcutOy['secilen_'.$i] ?? ''); ?>">
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
                                    SELECT * FROM istisare_oylama
                                    ORDER BY tarih DESC LIMIT 50
                                ");
                                foreach ($lastVotes as $lv): ?>
                                    <tr class="small">
                                        <td class="fw-bold"><?php echo htmlspecialchars($lv['voter_id']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['sube_ismi'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($lv['secilen_1']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['secilen_2']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['secilen_3']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['secilen_4']); ?></td>
                                        <td><?php echo htmlspecialchars($lv['secilen_5']); ?></td>
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
