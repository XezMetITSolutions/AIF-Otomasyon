<?php
/**
 * Başkan - Toplantılar
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

$pageTitle = 'Toplantılar';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$csrfToken = Middleware::generateCSRF();
$message = null;
$messageType = 'success';

$allowedStatuses = ['planlandi', 'devam_ediyor', 'tamamlandi', 'iptal'];
$allowedTypes = ['normal', 'acil', 'ozel'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $baslik = trim($_POST['baslik'] ?? '');
            $aciklama = trim($_POST['aciklama'] ?? '');
            $tarih = $_POST['toplanti_tarihi'] ?? '';
            $konum = trim($_POST['konum'] ?? '');
            $tur = $_POST['toplanti_turu'] ?? 'normal';

            if (!$baslik || !$tarih || !$konum) {
                $message = 'Başlık, tarih ve konum zorunludur.';
                $messageType = 'danger';
            } elseif (!in_array($tur, $allowedTypes, true)) {
                $message = 'Geçersiz toplantı türü.';
                $messageType = 'danger';
            } else {
                $db->query("
                    INSERT INTO toplantilar (byk_id, toplanti_turu, baslik, aciklama, toplanti_tarihi, konum, durum, olusturan_id)
                    VALUES (?, ?, ?, ?, ?, ?, 'planlandi', ?)
                ", [
                    $user['byk_id'],
                    $tur,
                    $baslik,
                    $aciklama ?: null,
                    $tarih,
                    $konum,
                    $user['id']
                ]);
                $message = 'Toplantı planlandı.';
            }
        } elseif ($action === 'status') {
            $toplantiId = (int)($_POST['toplanti_id'] ?? 0);
            $durum = $_POST['durum'] ?? '';
            if (!$toplantiId || !in_array($durum, $allowedStatuses, true)) {
                $message = 'Geçersiz toplantı veya durum.';
                $messageType = 'danger';
            } else {
                $meeting = $db->fetch("
                    SELECT toplanti_id FROM toplantilar
                    WHERE toplanti_id = ? AND byk_id = ?
                ", [$toplantiId, $user['byk_id']]);
                if (!$meeting) {
                    $message = 'Toplantı bulunamadı.';
                    $messageType = 'danger';
                } else {
                    $db->query("
                        UPDATE toplantilar
                        SET durum = ?, guncelleme_tarihi = NOW()
                        WHERE toplanti_id = ?
                    ", [$durum, $toplantiId]);
                    $message = 'Toplantı durumu güncellendi.';
                }
            }
        }
    }
}

$upcomingMeetings = $db->fetchAll("
    SELECT t.*, 
        (SELECT COUNT(*) FROM toplanti_katilimcilar tk WHERE tk.toplanti_id = t.toplanti_id) as katilimci_sayisi
    FROM toplantilar t
    WHERE t.byk_id = ? AND t.toplanti_tarihi >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY t.toplanti_tarihi ASC
    LIMIT 50
", [$user['byk_id']]);

$pastMeetings = $db->fetchAll("
    SELECT t.*
    FROM toplantilar t
    WHERE t.byk_id = ? AND t.toplanti_tarihi < NOW()
    ORDER BY t.toplanti_tarihi DESC
    LIMIT 20
", [$user['byk_id']]);

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="row g-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h1 class="h3 mb-1"><i class="fas fa-users-cog me-2"></i>Toplantılar</h1>
                        <p class="text-muted mb-0">Komite toplantılarınızı planlayın, durumlarını takip edin.</p>
                    </div>
                </div>
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-plus-circle me-2"></i>Yeni Toplantı Planla
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="create">
                            <div class="col-12">
                                <label class="form-label">Başlık</label>
                                <input type="text" name="baslik" class="form-control" required placeholder="Örn. Bölge Değerlendirme">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Açıklama</label>
                                <textarea name="aciklama" class="form-control" rows="3" placeholder="Gündem ve hedefler"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Konum</label>
                                <input type="text" name="konum" class="form-control" required placeholder="Örn. Merkez Ofis / Zoom">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tarih & Saat</label>
                                <input type="datetime-local" name="toplanti_tarihi" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Toplantı Türü</label>
                                <select name="toplanti_turu" class="form-select">
                                    <option value="normal">Normal</option>
                                    <option value="acil">Acil</option>
                                    <option value="ozel">Özel</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-calendar-plus me-1"></i>Planla
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-sun me-2 text-primary"></i>Yaklaşan Toplantılar</span>
                        <span class="badge bg-primary"><?php echo count($upcomingMeetings); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingMeetings)): ?>
                            <p class="text-muted mb-0">Yaklaşan toplantı bulunmuyor.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingMeetings as $meeting): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between flex-wrap gap-3">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($meeting['baslik']); ?></h6>
                                                <div class="small text-muted mb-1">
                                                    <i class="fas fa-clock me-1"></i><?php echo date('d.m.Y H:i', strtotime($meeting['toplanti_tarihi'])); ?>
                                                    <span class="mx-2">|</span>
                                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($meeting['konum']); ?>
                                                </div>
                                                <?php if ($meeting['aciklama']): ?>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($meeting['aciklama']); ?></div>
                                                <?php endif; ?>
                                                <div class="small">
                                                    <span class="badge bg-light text-dark me-2 text-uppercase"><?php echo $meeting['toplanti_turu']; ?></span>
                                                    <span class="badge bg-outline-secondary text-dark border">
                                                        <i class="fas fa-user-friends me-1"></i><?php echo $meeting['katilimci_sayisi']; ?> katılımcı
                                                    </span>
                                                </div>
                                            </div>
                                            <form method="post" class="d-flex align-items-center gap-2">
                                                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="status">
                                                <input type="hidden" name="toplanti_id" value="<?php echo $meeting['toplanti_id']; ?>">
                                                <select name="durum" class="form-select form-select-sm">
                                                    <?php foreach ($allowedStatuses as $status): ?>
                                                        <option value="<?php echo $status; ?>" <?php echo $meeting['durum'] === $status ? 'selected' : ''; ?>>
                                                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm btn-outline-secondary" type="submit">
                                                    <i class="fas fa-sync me-1"></i>Güncelle
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-history me-2 text-muted"></i>Geçmiş Toplantılar</span>
                        <span class="badge bg-secondary"><?php echo count($pastMeetings); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pastMeetings)): ?>
                            <p class="text-muted mb-0">Henüz geçmiş toplantı kaydı yok.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Başlık</th>
                                            <th>Tarih</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pastMeetings as $meeting): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($meeting['baslik']); ?></td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($meeting['toplanti_tarihi'])); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $meeting['durum'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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


