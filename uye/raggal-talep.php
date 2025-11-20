<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireLoggedIn();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Raggal Rezervasyon Formu';

$success = '';
$error = '';

// Form Gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslangic = $_POST['baslangic'] ?? '';
    $bitis = $_POST['bitis'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';

    if (empty($baslangic) || empty($bitis)) {
        $error = 'Lütfen başlangıç ve bitiş tarihlerini giriniz.';
    } else {
        try {
            $db->query(
                "INSERT INTO raggal_talepleri (kullanici_id, baslangic_tarihi, bitis_tarihi, aciklama) VALUES (?, ?, ?, ?)",
                [$user['id'], $baslangic, $bitis, $aciklama]
            );
            $success = 'Rezervasyon talebiniz başarıyla oluşturuldu.';
        } catch (Exception $e) {
            $error = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

// Geçmiş Talepler
$talepler = $db->fetchAll(
    "SELECT * FROM raggal_talepleri WHERE kullanici_id = ? ORDER BY created_at DESC",
    [$user['id']]
);

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-calendar-plus me-2"></i>Raggal Rezervasyon Formu
            </h1>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Talep Formu -->
            <div class="col-md-5">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Yeni Rezervasyon Talebi</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="baslangic" class="form-label">Başlangıç Tarihi ve Saati</label>
                                <input type="datetime-local" class="form-control" id="baslangic" name="baslangic" required>
                            </div>
                            <div class="mb-3">
                                <label for="bitis" class="form-label">Bitiş Tarihi ve Saati</label>
                                <input type="datetime-local" class="form-control" id="bitis" name="bitis" required>
                            </div>
                            <div class="mb-3">
                                <label for="aciklama" class="form-label">Açıklama / Amaç</label>
                                <textarea class="form-control" id="aciklama" name="aciklama" rows="3" placeholder="Rezervasyon amacı..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Talebi Gönder
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Geçmiş Talepler -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Taleplerim</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarih Aralığı</th>
                                        <th>Açıklama</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($talepler)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Henüz bir talebiniz bulunmuyor.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($talepler as $talep): ?>
                                            <tr>
                                                <td>
                                                    <?php echo date('d.m.Y H:i', strtotime($talep['baslangic_tarihi'])); ?> - <br>
                                                    <?php echo date('d.m.Y H:i', strtotime($talep['bitis_tarihi'])); ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($talep['aciklama'])): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($talep['aciklama']); ?></small>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($talep['durum'] === 'bekliyor'): ?>
                                                        <span class="badge bg-warning text-dark">Bekliyor</span>
                                                    <?php elseif ($talep['durum'] === 'onaylandi'): ?>
                                                        <span class="badge bg-success">Onaylandı</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Reddedildi</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
