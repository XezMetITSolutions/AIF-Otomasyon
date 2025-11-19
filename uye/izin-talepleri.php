<?php
/**
 * Üye - İzin Taleplerim
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

$pageTitle = 'İzin Taleplerim';
$csrfTokenName = $appConfig['security']['csrf_token_name'] ?? 'csrf_token';
$csrfToken = Middleware::generateCSRF();
$errors = [];
$messages = [];
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$durumBadgeMap = [
    'beklemede' => 'warning',
    'onaylandi' => 'success',
    'reddedildi' => 'danger'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Oturum doğrulaması başarısız oldu. Lütfen sayfayı yenileyin.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'yeni_izin') {
            $baslangic = $_POST['baslangic_tarihi'] ?? '';
            $bitis = $_POST['bitis_tarihi'] ?? '';
            $izinNedeni = trim($_POST['izin_nedeni'] ?? '');
            $aciklama = trim($_POST['aciklama'] ?? '');
            
            if (!$baslangic || !$bitis) {
                $errors[] = 'Başlangıç ve bitiş tarihleri zorunludur.';
            } else {
                $baslangicDate = DateTime::createFromFormat('Y-m-d', $baslangic);
                $bitisDate = DateTime::createFromFormat('Y-m-d', $bitis);
                if (!$baslangicDate || !$bitisDate) {
                    $errors[] = 'Geçerli tarih formatı kullanın.';
                } elseif ($bitisDate < $baslangicDate) {
                    $errors[] = 'Bitiş tarihi başlangıç tarihinden önce olamaz.';
                }
            }
            
            if (strlen($izinNedeni) > 255) {
                $errors[] = 'İzin nedeni 255 karakteri aşamaz.';
            }
            
            if (empty($errors)) {
                $db->query("
                    INSERT INTO izin_talepleri (kullanici_id, baslangic_tarihi, bitis_tarihi, izin_nedeni, aciklama, durum)
                    VALUES (?, ?, ?, ?, ?, 'beklemede')
                ", [
                    $user['id'],
                    $baslangic,
                    $bitis,
                    $izinNedeni ?: null,
                    $aciklama ?: null
                ]);
                
                $messages[] = 'İzin talebiniz başarıyla oluşturuldu.';
            }
        }
    }
}

$izinler = $db->fetchAll("
    SELECT *
    FROM izin_talepleri
    WHERE kullanici_id = ?
    ORDER BY olusturma_tarihi DESC
", [$user['id']]);

$seciliIzin = null;
if ($selectedId) {
    $seciliIzin = $db->fetch("
        SELECT *
        FROM izin_talepleri
        WHERE izin_id = ? AND kullanici_id = ?
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
                    <i class="fas fa-calendar-check me-2"></i>İzin Taleplerim
                </h1>
                <small class="text-muted">Yeni izin talebi oluşturun, mevcut taleplerinizi takip edin.</small>
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
                        <strong>Yeni İzin Talebi</strong>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="yeni_izin">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Başlangıç Tarihi</label>
                                <input type="date" name="baslangic_tarihi" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bitiş Tarihi</label>
                                <input type="date" name="bitis_tarihi" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">İzin Nedeni</label>
                                <input type="text" name="izin_nedeni" class="form-control" maxlength="255" placeholder="Örn. Tatil, sağlık vb.">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="aciklama" class="form-control" rows="4" placeholder="Detaylı açıklama (opsiyonel)"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-1"></i>İzin Talebi Gönder
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-7">
                <?php if ($seciliIzin): ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Talep Detayı</strong>
                            <span class="badge bg-secondary"><?php echo date('d.m.Y H:i', strtotime($seciliIzin['olusturma_tarihi'])); ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Başlangıç</small>
                                    <strong><?php echo date('d.m.Y', strtotime($seciliIzin['baslangic_tarihi'])); ?></strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Bitiş</small>
                                    <strong><?php echo date('d.m.Y', strtotime($seciliIzin['bitis_tarihi'])); ?></strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Durum</small>
                                    <?php $badgeColor = $durumBadgeMap[$seciliIzin['durum']] ?? 'secondary'; ?>
                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                        <?php echo ucfirst($seciliIzin['durum']); ?>
                                    </span>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">İzin Nedeni</small>
                                    <p class="mb-0"><?php echo htmlspecialchars($seciliIzin['izin_nedeni'] ?? '-'); ?></p>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Açıklama</small>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($seciliIzin['aciklama'] ?? '-')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($selectedId): ?>
                    <div class="alert alert-warning">Talep bulunamadı veya erişim yetkiniz yok.</div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Toplam: <strong><?php echo count($izinler); ?></strong> talep</span>
                        <?php if ($selectedId): ?>
                            <a href="/uye/izin-talepleri.php" class="btn btn-sm btn-outline-secondary">Seçimi Temizle</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($izinler)): ?>
                            <p class="text-center text-muted mb-0">Henüz izin talebi oluşturmadınız.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Başlangıç</th>
                                            <th>Bitiş</th>
                                            <th>Neden</th>
                                            <th>Durum</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($izinler as $izin): ?>
                                            <?php
                                                $badgeColor = $durumBadgeMap[$izin['durum']] ?? 'secondary';
                                                $rowSelected = $selectedId === (int) $izin['izin_id'] ? 'table-primary' : '';
                                            ?>
                                            <tr class="<?php echo $rowSelected; ?>">
                                                <td><?php echo date('d.m.Y', strtotime($izin['baslangic_tarihi'])); ?></td>
                                                <td><?php echo date('d.m.Y', strtotime($izin['bitis_tarihi'])); ?></td>
                                                <td><?php echo htmlspecialchars($izin['izin_nedeni'] ?? '-'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                                        <?php echo ucfirst($izin['durum']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="/uye/izin-talepleri.php?id=<?php echo $izin['izin_id']; ?>" class="btn btn-sm btn-outline-primary">
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


