<?php
/**
 * Başkan - Etkinlikler
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

$pageTitle = 'Etkinlikler';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$csrfToken = Middleware::generateCSRF();
$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız.';
        $messageType = 'danger';
    } else {
        $baslik = trim($_POST['baslik'] ?? '');
        $aciklama = trim($_POST['aciklama'] ?? '');
        $baslangic = $_POST['baslangic_tarihi'] ?? '';
        $bitis = $_POST['bitis_tarihi'] ?? '';
        $konum = trim($_POST['konum'] ?? '');
        $renk = $_POST['renk_kodu'] ?? '#009872';

        if (!$baslik || !$baslangic || !$bitis) {
            $message = 'Başlık, başlangıç ve bitiş tarihleri zorunludur.';
            $messageType = 'danger';
        } elseif (strtotime($bitis) < strtotime($baslangic)) {
            $message = 'Bitiş tarihi başlangıç tarihinden önce olamaz.';
            $messageType = 'danger';
        } else {
            $db->query("
                INSERT INTO etkinlikler (byk_id, baslik, aciklama, baslangic_tarihi, bitis_tarihi, konum, renk_kodu, olusturan_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $user['byk_id'],
                $baslik,
                $aciklama ?: null,
                $baslangic,
                $bitis,
                $konum ?: null,
                $renk,
                $user['id']
            ]);
            $message = 'Etkinlik başarıyla oluşturuldu.';
        }
    }
}

$upcomingEvents = $db->fetchAll("
    SELECT * FROM etkinlikler
    WHERE byk_id = ? AND baslangic_tarihi >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY baslangic_tarihi ASC
    LIMIT 50
", [$user['byk_id']]);

$pastEvents = $db->fetchAll("
    SELECT * FROM etkinlikler
    WHERE byk_id = ? AND baslangic_tarihi < NOW()
    ORDER BY baslangic_tarihi DESC
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
                        <h1 class="h3 mb-1"><i class="fas fa-calendar me-2"></i>Etkinlikler</h1>
                        <p class="text-muted mb-0">BYK’nız için planlanan tüm aktiviteleri burada yönetin.</p>
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
                        <i class="fas fa-plus-circle me-2"></i>Yeni Etkinlik Oluştur
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            <div class="col-12">
                                <label class="form-label">Başlık</label>
                                <input type="text" name="baslik" class="form-control" required placeholder="Örn. Eğitim Kampı">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Açıklama</label>
                                <textarea name="aciklama" class="form-control" rows="3" placeholder="Etkinlik hakkında kısa bilgi"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Konum</label>
                                <input type="text" name="konum" class="form-control" placeholder="Adres veya çevrim içi bağlantı">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Başlangıç</label>
                                <input type="datetime-local" name="baslangic_tarihi" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bitiş</label>
                                <input type="datetime-local" name="bitis_tarihi" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Renk</label>
                                <input type="color" name="renk_kodu" class="form-control form-control-color" value="#009872">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-save me-1"></i>Etkinliği Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-sun me-2 text-success"></i>Yaklaşan Etkinlikler</span>
                        <span class="badge bg-success"><?php echo count($upcomingEvents); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingEvents)): ?>
                            <p class="text-muted mb-0">Yaklaşan etkinlik bulunmuyor.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingEvents as $event): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between flex-wrap gap-2">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($event['baslik']); ?></h6>
                                                <div class="small text-muted">
                                                    <i class="fas fa-clock me-1"></i><?php echo date('d.m.Y H:i', strtotime($event['baslangic_tarihi'])); ?>
                                                    -
                                                    <?php echo date('d.m.Y H:i', strtotime($event['bitis_tarihi'])); ?>
                                                </div>
                                                <?php if ($event['konum']): ?>
                                                    <div class="small"><i class="fas fa-map-marker-alt me-1 text-danger"></i><?php echo htmlspecialchars($event['konum']); ?></div>
                                                <?php endif; ?>
                                                <?php if ($event['aciklama']): ?>
                                                    <div class="small text-muted mt-1"><?php echo htmlspecialchars($event['aciklama']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge" style="background: <?php echo htmlspecialchars($event['renk_kodu']); ?>;">Etkinlik</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-history me-2 text-muted"></i>Geçmiş Etkinlikler</span>
                        <span class="badge bg-secondary"><?php echo count($pastEvents); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pastEvents)): ?>
                            <p class="text-muted mb-0">Geçmiş etkinlik bulunmuyor.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Başlık</th>
                                            <th>Tarih</th>
                                            <th>Konum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pastEvents as $event): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($event['baslik']); ?></td>
                                                <td><?php echo date('d.m.Y', strtotime($event['baslangic_tarihi'])); ?></td>
                                                <td><?php echo htmlspecialchars($event['konum'] ?? '-'); ?></td>
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


