<?php
/**
 * Üye - Duyurular Listesi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireUye();
Middleware::requireModulePermission('uye_duyurular');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Duyurular';
$bykId = $user['byk_id'];

$duyurular = [];
$toplamDuyuru = 0;

if ($bykId) {
    $duyurular = $db->fetchAll("
        SELECT d.*, CONCAT(k.ad, ' ', k.soyad) as olusturan_adi
        FROM duyurular d
        LEFT JOIN kullanicilar k ON d.olusturan_id = k.kullanici_id
        WHERE d.byk_id = ? AND d.aktif = 1
        ORDER BY d.olusturma_tarihi DESC
        LIMIT 50
    ", [$bykId]);
    $toplamDuyuru = count($duyurular);
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-bullhorn me-2"></i>Duyurular
                </h1>
                <small class="text-muted">BYK duyuruları</small>
            </div>
            <span class="badge bg-primary fs-6">Aktif: <?php echo $toplamDuyuru; ?></span>
        </div>
        
        <div class="row">
            <?php if (!$bykId): ?>
                <div class="col-12">
                    <div class="alert alert-warning">
                        BYK bilgisi bulunamadığı için duyurular listelenemiyor. Lütfen sistem yöneticinizle iletişime geçin.
                    </div>
                </div>
            <?php elseif (empty($duyurular)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center text-muted py-5">
                            <i class="fas fa-bullhorn fa-2x mb-3"></i>
                            <p class="mb-0">Bu BYK için aktif duyuru bulunmamaktadır.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($duyurular as $duyuru): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <strong><?php echo htmlspecialchars($duyuru['baslik']); ?></strong>
                                <span class="badge bg-success"><?php echo date('d.m.Y', strtotime($duyuru['olusturma_tarihi'])); ?></span>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    <?php echo nl2br(htmlspecialchars($duyuru['icerik'])); ?>
                                </p>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($duyuru['olusturan_adi'] ?? 'Sistem'); ?></span>
                                    <span><i class="fas fa-clock me-1"></i><?php echo date('d.m.Y H:i', strtotime($duyuru['olusturma_tarihi'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>


