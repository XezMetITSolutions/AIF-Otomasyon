<?php
/**
 * Başkan - Duyurular
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';


// Permission check for management actions
$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$canManage = $auth->hasModulePermission('baskan_duyurular');

$appConfig = require __DIR__ . '/../config/app.php';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$csrfToken = Middleware::generateCSRF();
$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canManage) {
        $message = 'Bu işlem için yetkiniz bulunmamaktadır.';
        $messageType = 'danger';
    } elseif (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $baslik = trim($_POST['baslik'] ?? '');
            $icerik = trim($_POST['icerik'] ?? '');
            if (!$baslik || !$icerik) {
                $message = 'Başlık ve içerik zorunludur.';
                $messageType = 'danger';
            } else {
                $db->query("
                    INSERT INTO duyurular (byk_id, baslik, icerik, olusturan_id, aktif)
                    VALUES (?, ?, ?, ?, 1)
                ", [$user['byk_id'], $baslik, $icerik, $user['id']]);
                $message = 'Duyuru yayınlandı.';
            }
        } elseif ($action === 'toggle') {
            $duyuruId = (int)($_POST['duyuru_id'] ?? 0);
            $duyuru = $db->fetch("
                SELECT duyuru_id, aktif FROM duyurular
                WHERE duyuru_id = ? AND byk_id = ?
            ", [$duyuruId, $user['byk_id']]);

            if (!$duyuru) {
                $message = 'Duyuru bulunamadı.';
                $messageType = 'danger';
            } else {
                $yeniDurum = $duyuru['aktif'] ? 0 : 1;
                $db->query("
                    UPDATE duyurular SET aktif = ? WHERE duyuru_id = ?
                ", [$yeniDurum, $duyuruId]);
                $message = $yeniDurum ? 'Duyuru yeniden yayınlandı.' : 'Duyuru taslağa alındı.';
            }
        }
    }
}

// Filter logic: Managers see all, others see only active
// Filter logic: Managers see all, others see only active
$whereClause = "d.byk_id = " . (int)$user['byk_id'];
if (!$canManage) {
    $whereClause .= " AND d.aktif = 1";
}

$duyurular = $db->fetchAll("
    SELECT d.*, CONCAT(k.ad, ' ', k.soyad) as olusturan
    FROM duyurular d
    LEFT JOIN kullanicilar k ON d.olusturan_id = k.kullanici_id
    WHERE $whereClause
    ORDER BY d.olusturma_tarihi DESC
");

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-bullhorn me-2"></i>Duyurular
                </h1>
                <p class="text-muted mb-0">BYK üyelerinize hızlıca kısa mesajlar yayınlayın.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if ($canManage): ?>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-pen me-2"></i>Yeni Duyuru
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="create">
                            <div class="col-12">
                                <label class="form-label">Başlık</label>
                                <input type="text" name="baslik" class="form-control" required placeholder="Örn. Haftalık Toplantı">
                            </div>
                            <div class="col-12">
                                <label class="form-label">İçerik</label>
                                <textarea name="icerik" class="form-control" rows="4" required placeholder="Duyuru metni..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-paper-plane me-1"></i>Yayınla
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="<?php echo $canManage ? 'col-lg-8' : 'col-12'; ?>">
                <div class="card">
                    <div class="card-header">
                        Yayında Olan Duyurular
                    </div>
                    <div class="card-body">
                        <?php if (empty($duyurular)): ?>
                            <p class="text-muted mb-0">Henüz bir duyuru paylaşmadınız.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($duyurular as $duyuru): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php echo htmlspecialchars($duyuru['baslik']); ?>
                                                    <?php if (!$duyuru['aktif']): ?>
                                                        <span class="badge bg-secondary ms-2">Taslak</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <div class="small text-muted mb-2">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($duyuru['olusturan'] ?? 'Sistem'); ?>
                                                    <span class="mx-2">|</span>
                                                    <i class="fas fa-clock me-1"></i><?php echo date('d.m.Y H:i', strtotime($duyuru['olusturma_tarihi'])); ?>
                                                </div>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($duyuru['icerik'])); ?></p>
                                            </div>
                                            <?php if ($canManage): ?>
                                            <form method="post">
                                                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="duyuru_id" value="<?php echo $duyuru['duyuru_id']; ?>">
                                                <button class="btn btn-sm btn-<?php echo $duyuru['aktif'] ? 'outline-secondary' : 'outline-success'; ?>">
                                                    <?php echo $duyuru['aktif'] ? 'Taslağa Al' : 'Yayınla'; ?>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>


