<?php
/**
 * Başkan - İzin Talepleri
 * Kendi BYK'sına ait talepleri listeler ve onay/red işlemi yapar
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireBaskan();
Middleware::requireModulePermission('baskan_izin_talepleri');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$appConfig = require __DIR__ . '/../config/app.php';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$pageTitle = 'İzin Talepleri';
$durum = $_GET['durum'] ?? '';
$message = null;
$messageType = 'success';
$csrfToken = Middleware::generateCSRF();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyin.';
        $messageType = 'danger';
    } else {
        $izinId = (int)($_POST['izin_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $aciklama = trim($_POST['aciklama'] ?? '');

        $izin = $db->fetch("
            SELECT it.*, k.byk_id
            FROM izin_talepleri it
            INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
            WHERE it.izin_id = ? AND k.byk_id = ?
        ", [$izinId, $user['byk_id']]);

        if (!$izin) {
            $message = 'İzin talebi bulunamadı.';
            $messageType = 'danger';
        } elseif ($izin['durum'] !== 'beklemede') {
            $message = 'Bu talep zaten yanıtlanmış.';
            $messageType = 'warning';
        } else {
            if ($action === 'approve') {
                $db->query("
                    UPDATE izin_talepleri
                    SET durum = 'onaylandi',
                        onaylayan_id = ?,
                        onay_tarihi = NOW(),
                        onay_aciklama = ?
                    WHERE izin_id = ?
                ", [$user['id'], $aciklama ?: null, $izinId]);
                $message = 'İzin talebi onaylandı.';
            } elseif ($action === 'reject') {
                $db->query("
                    UPDATE izin_talepleri
                    SET durum = 'reddedildi',
                        onaylayan_id = ?,
                        onay_tarihi = NOW(),
                        onay_aciklama = ?
                    WHERE izin_id = ?
                ", [$user['id'], $aciklama ?: null, $izinId]);
                $message = 'İzin talebi reddedildi.';
            } else {
                $message = 'Geçersiz işlem.';
                $messageType = 'danger';
            }
        }
    }
}

$filters = ['k.byk_id = ?'];
$params = [$user['byk_id']];

if ($durum) {
    $filters[] = "it.durum = ?";
    $params[] = $durum;
}

$where = 'WHERE ' . implode(' AND ', $filters);

$izinTalepleri = $db->fetchAll("
    SELECT it.*, CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, k.email, k.telefon
    FROM izin_talepleri it
    INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
    $where
    ORDER BY it.olusturma_tarihi DESC
    LIMIT 100
", $params);

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-calendar-check me-2"></i>İzin Talepleri
                </h1>
                <p class="text-muted mb-0">BYK üyelerinizden gelen tüm izin taleplerini görüntüleyin ve yanıtlayın.</p>
            </div>
            <div class="btn-group">
                <a href="?durum=beklemede" class="btn btn-outline-warning btn-sm <?php echo $durum === 'beklemede' ? 'active' : ''; ?>">
                    <i class="fas fa-hourglass-half me-1"></i>Bekleyenler
                </a>
                <a href="?durum=onaylandi" class="btn btn-outline-success btn-sm <?php echo $durum === 'onaylandi' ? 'active' : ''; ?>">
                    <i class="fas fa-check me-1"></i>Onaylananlar
                </a>
                <a href="?durum=reddedildi" class="btn btn-outline-danger btn-sm <?php echo $durum === 'reddedildi' ? 'active' : ''; ?>">
                    <i class="fas fa-times me-1"></i>Reddedilenler
                </a>
                <a href="/panel/baskan_izin-talepleri.php" class="btn btn-outline-secondary btn-sm <?php echo $durum === '' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group me-1"></i>Tümü
                </a>
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
                Toplam <strong><?php echo count($izinTalepleri); ?></strong> kayıt
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Kullanıcı</th>
                                <th>Başlangıç</th>
                                <th>Bitiş</th>
                                <th>Gün</th>
                                <th>İzin Nedeni</th>
                                <th>Durum</th>
                                <th class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($izinTalepleri)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-snooze fa-2x mb-2"></i><br>
                                        Gösterilecek izin talebi bulunmuyor.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($izinTalepleri as $izin):
                                    $gunSayisi = (new DateTime($izin['baslangic_tarihi']))->diff(new DateTime($izin['bitis_tarihi']))->days + 1;
                                    $badgeMap = [
                                        'beklemede' => 'warning',
                                        'onaylandi' => 'success',
                                        'reddedildi' => 'danger'
                                    ];
                                    $badge = $badgeMap[$izin['durum']] ?? 'secondary';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($izin['kullanici_adi']); ?></div>
                                            <div class="small text-muted">
                                                <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($izin['email']); ?>
                                                <?php if ($izin['telefon']): ?>
                                                    <span class="ms-2"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($izin['telefon']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($izin['baslangic_tarihi'])); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($izin['bitis_tarihi'])); ?></td>
                                        <td><?php echo $gunSayisi; ?> gün</td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($izin['izin_nedeni'] ?? '-'); ?></div>
                                            <?php if ($izin['aciklama']): ?>
                                                <div class="small text-muted"><?php echo htmlspecialchars($izin['aciklama']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $badge; ?>">
                                                <?php echo $izin['durum'] === 'beklemede' ? 'Beklemede' : ($izin['durum'] === 'onaylandi' ? 'Onaylandı' : 'Reddedildi'); ?>
                                            </span>
                                            <?php if ($izin['onay_aciklama']): ?>
                                                <div class="small text-muted mt-1">
                                                    <?php echo htmlspecialchars($izin['onay_aciklama']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($izin['durum'] === 'beklemede'): ?>
                                                <div class="d-flex flex-column gap-2">
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                        <input type="hidden" name="izin_id" value="<?php echo $izin['izin_id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button class="btn btn-sm btn-success w-100" type="submit">
                                                            <i class="fas fa-check me-1"></i>Onayla
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal" data-izin="<?php echo $izin['izin_id']; ?>">
                                                        <i class="fas fa-times me-1"></i>Reddet
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <a href="/panel/baskan_izin-detay.php?id=<?php echo $izin['izin_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>Detay
                                                </a>
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
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-times text-danger me-2"></i>İzin Talebini Reddet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="izin_id" id="rejectIzinId">
                <input type="hidden" name="action" value="reject">
                <div class="mb-3">
                    <label class="form-label">Açıklama (zorunlu değil)</label>
                    <textarea name="aciklama" class="form-control" rows="3" placeholder="Neden reddedildiğini kısa bir notla belirtin."></textarea>
                </div>
                <p class="small text-muted mb-0">
                    Reddetme işlemi üyeye bildirilir ve talep geri alınamaz.
                </p>
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
    const rejectModal = document.getElementById('rejectModal');
    rejectModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const izinId = button.getAttribute('data-izin');
        rejectModal.querySelector('#rejectIzinId').value = izinId;
    });
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>


