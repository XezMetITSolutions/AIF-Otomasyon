<?php
/**
 * Başkan - Harcama Talepleri
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireBaskan();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

$pageTitle = 'Harcama Talepleri';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$csrfToken = Middleware::generateCSRF();
$durum = $_GET['durum'] ?? '';
$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız.';
        $messageType = 'danger';
    } else {
        $talepId = (int)($_POST['talep_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $aciklama = trim($_POST['aciklama'] ?? '');

        $talep = $db->fetch("
            SELECT * FROM harcama_talepleri
            WHERE talep_id = ? AND byk_id = ?
        ", [$talepId, $user['byk_id']]);

        if (!$talep) {
            $message = 'Talep bulunamadı.';
            $messageType = 'danger';
        } elseif ($talep['durum'] !== 'beklemede') {
            $message = 'Bu talep zaten yanıtlanmış.';
            $messageType = 'warning';
        } else {
            if ($action === 'approve') {
                $db->query("
                    UPDATE harcama_talepleri
                    SET durum = 'onaylandi',
                        onaylayan_id = ?,
                        onay_tarihi = NOW(),
                        onay_aciklama = ?
                    WHERE talep_id = ?
                ", [$user['id'], $aciklama ?: null, $talepId]);
                $message = 'Harcama talebi onaylandı.';
            } elseif ($action === 'reject') {
                $db->query("
                    UPDATE harcama_talepleri
                    SET durum = 'reddedildi',
                        onaylayan_id = ?,
                        onay_tarihi = NOW(),
                        onay_aciklama = ?
                    WHERE talep_id = ?
                ", [$user['id'], $aciklama ?: null, $talepId]);
                $message = 'Harcama talebi reddedildi.';
            } else {
                $message = 'Geçersiz işlem.';
                $messageType = 'danger';
            }
        }
    }
}

$filters = ['ht.byk_id = ?'];
$params = [$user['byk_id']];
if ($durum) {
    $filters[] = 'ht.durum = ?';
    $params[] = $durum;
}

$where = 'WHERE ' . implode(' AND ', $filters);

$harcamaTalepleri = $db->fetchAll("
    SELECT ht.*, CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, k.email, k.telefon
    FROM harcama_talepleri ht
    INNER JOIN kullanicilar k ON ht.kullanici_id = k.kullanici_id
    $where
    ORDER BY ht.olusturma_tarihi DESC
    LIMIT 100
", $params);

$statusBadges = [
    'beklemede' => ['label' => 'Beklemede', 'class' => 'warning'],
    'onaylandi' => ['label' => 'Onaylandı', 'class' => 'success'],
    'reddedildi' => ['label' => 'Reddedildi', 'class' => 'danger'],
    'odenmistir' => ['label' => 'Ödendi', 'class' => 'primary']
];

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-wallet me-2"></i>Harcama Talepleri
                </h1>
                <p class="text-muted mb-0">Onay bekleyen tüm masraf taleplerini tek ekranda yönetin.</p>
            </div>
            <div class="btn-group">
                <a href="?durum=beklemede" class="btn btn-outline-warning btn-sm <?php echo $durum === 'beklemede' ? 'active' : ''; ?>"><i class="fas fa-hourglass-half me-1"></i>Bekleyenler</a>
                <a href="?durum=onaylandi" class="btn btn-outline-success btn-sm <?php echo $durum === 'onaylandi' ? 'active' : ''; ?>"><i class="fas fa-check me-1"></i>Onaylananlar</a>
                <a href="?durum=reddedildi" class="btn btn-outline-danger btn-sm <?php echo $durum === 'reddedildi' ? 'active' : ''; ?>"><i class="fas fa-times me-1"></i>Reddedilenler</a>
                <a href="?durum=odenmistir" class="btn btn-outline-primary btn-sm <?php echo $durum === 'odenmistir' ? 'active' : ''; ?>"><i class="fas fa-money-bill-wave me-1"></i>Ödenenler</a>
                <a href="/baskan/harcama-talepleri.php" class="btn btn-outline-secondary btn-sm <?php echo $durum === '' ? 'active' : ''; ?>"><i class="fas fa-layer-group me-1"></i>Tümü</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Toplam <strong><?php echo count($harcamaTalepleri); ?></strong> kayıt
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Kullanıcı</th>
                                <th>Talep Başlığı</th>
                                <th>Tutar</th>
                                <th>Dosya</th>
                                <th>Açıklama</th>
                                <th>Durum</th>
                                <th class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($harcamaTalepleri)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        Masraf talebi bulunamadı.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($harcamaTalepleri as $talep):
                                    $badge = $statusBadges[$talep['durum']] ?? ['label' => ucfirst($talep['durum']), 'class' => 'secondary'];
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($talep['kullanici_adi']); ?></div>
                                            <div class="small text-muted">
                                                <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($talep['email']); ?>
                                                <?php if ($talep['telefon']): ?>
                                                    <span class="ms-2"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($talep['telefon']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($talep['baslik']); ?></strong>
                                            <div class="small text-muted"><?php echo date('d.m.Y H:i', strtotime($talep['olusturma_tarihi'])); ?></div>
                                        </td>
                                        <td class="fw-semibold text-success"><?php echo number_format($talep['tutar'], 2, ',', '.'); ?> €</td>
                                        <td>
                                            <?php if ($talep['dosya_yolu']): ?>
                                                <a href="<?php echo htmlspecialchars($talep['dosya_yolu']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-paperclip me-1"></i>Ek
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Ek yok</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo nl2br(htmlspecialchars($talep['aciklama'] ?? '-')); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $badge['class']; ?>"><?php echo $badge['label']; ?></span>
                                            <?php if ($talep['onay_aciklama']): ?>
                                                <div class="small text-muted mt-1"><?php echo htmlspecialchars($talep['onay_aciklama']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($talep['durum'] === 'beklemede'): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="talep_id" value="<?php echo $talep['talep_id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button class="btn btn-sm btn-success mb-2" type="submit">
                                                        <i class="fas fa-check me-1"></i>Onayla
                                                    </button>
                                                </form>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectExpenseModal" data-talep="<?php echo $talep['talep_id']; ?>">
                                                    <i class="fas fa-times me-1"></i>Reddet
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">İşlem yok</span>
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
</main>

<!-- Reddetme Modal -->
<div class="modal fade" id="rejectExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-times text-danger me-2"></i>Harcama Talebini Reddet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="talep_id" id="rejectTalepId">
                <input type="hidden" name="action" value="reject">
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <textarea name="aciklama" class="form-control" rows="3" placeholder="Karar gerekçenizi yazabilirsiniz."></textarea>
                </div>
                <p class="small text-muted mb-0">Reddedilen talep üyeye bildirilecektir.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-danger">Talebi Reddet</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const rejectModal = document.getElementById('rejectExpenseModal');
    rejectModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const talepId = button.getAttribute('data-talep');
        rejectModal.querySelector('#rejectTalepId').value = talepId;
    });
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>


