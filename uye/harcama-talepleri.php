<?php
/**
 * Üye - Harcama Taleplerim
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireUye();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

$pageTitle = 'Harcama Taleplerim';
$csrfTokenName = $appConfig['security']['csrf_token_name'] ?? 'csrf_token';
$csrfToken = Middleware::generateCSRF();
$errors = [];
$messages = [];
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$durumBadgeMap = [
    'beklemede' => 'warning',
    'onaylandi' => 'success',
    'reddedildi' => 'danger',
    'odenmistir' => 'primary'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Oturum doğrulaması başarısız oldu. Lütfen sayfayı yenileyin.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'yeni_harcama') {
            $baslik = trim($_POST['baslik'] ?? '');
            $tutar = $_POST['tutar'] ?? '';
            $aciklama = trim($_POST['aciklama'] ?? '');
            
            if ($baslik === '') {
                $errors[] = 'Talep başlığı zorunludur.';
            }
            if (!is_numeric($tutar) || (float)$tutar <= 0) {
                $errors[] = 'Geçerli bir tutar giriniz.';
            }
            
            if (empty($errors)) {
                $db->query("
                    INSERT INTO harcama_talepleri (kullanici_id, byk_id, baslik, aciklama, tutar, durum)
                    VALUES (?, ?, ?, ?, ?, 'beklemede')
                ", [
                    $user['id'],
                    $user['byk_id'],
                    $baslik,
                    $aciklama ?: null,
                    number_format((float)$tutar, 2, '.', '')
                ]);
                
                $messages[] = 'Harcama talebiniz başarıyla oluşturuldu.';
            }
        }
    }
}

$talepler = $db->fetchAll("
    SELECT *
    FROM harcama_talepleri
    WHERE kullanici_id = ?
    ORDER BY olusturma_tarihi DESC
", [$user['id']]);

$seciliTalep = null;
if ($selectedId) {
    $seciliTalep = $db->fetch("
        SELECT *
        FROM harcama_talepleri
        WHERE talep_id = ? AND kullanici_id = ?
    ", [$selectedId, $user['id']]);
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-wallet me-2"></i>Harcama Taleplerim
                </h1>
                <small class="text-muted">Gider taleplerini oluşturun ve süreci takip edin.</small>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success">
                <?php foreach ($messages as $message): ?>
                    <div><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>Yeni Harcama Talebi</strong>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="yeni_harcama">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Talep Başlığı</label>
                                <input type="text" name="baslik" class="form-control" required placeholder="Örn. Eğitim Malzemesi Alımı">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tutar (EUR)</label>
                                <input type="number" name="tutar" step="0.01" min="0" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="aciklama" class="form-control" rows="4" placeholder="Detaylı açıklama (opsiyonel)"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-1"></i>Talep Gönder
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-7">
                <?php if ($seciliTalep): ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong><?php echo htmlspecialchars($seciliTalep['baslik']); ?></strong>
                            <span class="badge bg-secondary"><?php echo date('d.m.Y H:i', strtotime($seciliTalep['olusturma_tarihi'])); ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Tutar</small>
                                    <strong><?php echo number_format($seciliTalep['tutar'], 2, ',', '.'); ?> €</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Durum</small>
                                    <?php $badgeColor = $durumBadgeMap[$seciliTalep['durum']] ?? 'secondary'; ?>
                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                        <?php echo ucfirst($seciliTalep['durum']); ?>
                                    </span>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Onay Açıklaması</small>
                                    <strong><?php echo htmlspecialchars($seciliTalep['onay_aciklama'] ?? '-'); ?></strong>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Açıklama</small>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($seciliTalep['aciklama'] ?? '-')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($selectedId): ?>
                    <div class="alert alert-warning">Talep bulunamadı veya yetkiniz yok.</div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Toplam: <strong><?php echo count($talepler); ?></strong> talep</span>
                        <?php if ($selectedId): ?>
                            <a href="/uye/harcama-talepleri.php" class="btn btn-sm btn-outline-secondary">Seçimi Temizle</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($talepler)): ?>
                            <p class="text-center text-muted mb-0">Henüz harcama talebi oluşturmadınız.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Başlık</th>
                                            <th>Tutar</th>
                                            <th>Durum</th>
                                            <th>Oluşturulma</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($talepler as $talep): ?>
                                            <?php
                                                $badgeColor = $durumBadgeMap[$talep['durum']] ?? 'secondary';
                                                $rowSelected = $selectedId === (int) $talep['talep_id'] ? 'table-primary' : '';
                                            ?>
                                            <tr class="<?php echo $rowSelected; ?>">
                                                <td><?php echo htmlspecialchars($talep['baslik']); ?></td>
                                                <td><?php echo number_format($talep['tutar'], 2, ',', '.'); ?> €</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                                        <?php echo ucfirst($talep['durum']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($talep['olusturma_tarihi'])); ?></td>
                                                <td class="text-end">
                                                    <a href="/uye/harcama-talepleri.php?id=<?php echo $talep['talep_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        Detay
                                                    </a>
                                                </td>
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


