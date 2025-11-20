<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireLoggedIn();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Demirbaş Talep Formu';

$success = '';
$error = '';

// Form Gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = $_POST['baslik'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';

    if (empty($baslik)) {
        $error = 'Lütfen talep başlığını giriniz.';
    } else {
        try {
            $db->query(
                "INSERT INTO demirbas_talepleri (kullanici_id, baslik, aciklama) VALUES (?, ?, ?)",
                [$user['id'], $baslik, $aciklama]
            );
            $success = 'Talebiniz başarıyla oluşturuldu.';
        } catch (Exception $e) {
            $error = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

// Geçmiş Talepler
$talepler = $db->fetchAll(
    "SELECT * FROM demirbas_talepleri WHERE kullanici_id = ? ORDER BY created_at DESC",
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
                <i class="fas fa-box me-2"></i>Demirbaş Talep Formu
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
                        <h5 class="card-title mb-0">Yeni Talep Oluştur</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="baslik" class="form-label">Talep Başlığı</label>
                                <input type="text" class="form-control" id="baslik" name="baslik" required placeholder="Örn: Laptop İhtiyacı">
                            </div>
                            <div class="mb-3">
                                <label for="aciklama" class="form-label">Açıklama</label>
                                <textarea class="form-control" id="aciklama" name="aciklama" rows="4" placeholder="Detaylı açıklama..."></textarea>
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
                                        <th>Tarih</th>
                                        <th>Başlık</th>
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
                                                <td><?php echo date('d.m.Y H:i', strtotime($talep['created_at'])); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($talep['baslik']); ?></strong>
                                                    <?php if (!empty($talep['aciklama'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($talep['aciklama'], 0, 50)); ?></small>
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
