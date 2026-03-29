<?php
/**
 * İstişareler Listesi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireRole(['super_admin', 'uye']);

$db = Database::getInstance();
$auth = new Auth();
$user = $auth->getUser();

$message = '';
$error = '';

// Yeni İstişare Ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['baslik'])) {
    $baslik = trim($_POST['baslik']);
    $sube = trim($_POST['sube_ismi']);
    $kurul = trim($_POST['kurul_uyeleri']);
    
    if (!empty($baslik) && !empty($sube)) {
        try {
            $db->query("INSERT INTO istisare_sessions (baslik, sube_ismi, kurul_uyeleri) VALUES (?, ?, ?)", [$baslik, $sube, $kurul]);
            header("Location: istisareler.php?msg=" . urlencode('Yeni istişare başarıyla oluşturuldu.'));
            exit;
        } catch (Exception $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    } else {
        $error = 'Lütfen başlık ve şube ismini doldurunuz.';
    }
}

if (isset($_GET['msg'])) $message = $_GET['msg'];
if (isset($_GET['err'])) $error = $_GET['err'];

$sessions = $db->fetchAll("
    SELECT s.*, 
    (SELECT COUNT(DISTINCT voter_id) FROM istisare_oylama WHERE session_id = s.id) as voter_count
    FROM istisare_sessions s
    ORDER BY s.eklenme_tarihi DESC
");

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="fas fa-vote-yea me-2 text-primary"></i>İstişareler Listesi</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#yeniIstisareModal">
                <i class="fas fa-plus me-2"></i>Yeni İstişare Başlat
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="50" class="text-center">#</th>
                                <th>İstişare Başlığı</th>
                                <th>Şube</th>
                                <th>İstişare Kurulu</th>
                                <th class="text-center">Katılım</th>
                                <th>Başlangıç Tarihi</th>
                                <th class="text-center">Durum</th>
                                <th width="150" class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($sessions as $s): ?>
                                <tr>
                                    <td class="text-center text-muted fw-bold"><?php echo $no++; ?></td>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($s['baslik']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($s['sube_ismi']); ?></span>
                                    </td>
                                    <td class="small">
                                        <?php echo htmlspecialchars($s['kurul_uyeleri'] ?: '-'); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-info text-dark">
                                            <i class="fas fa-user-check me-1"></i><?php echo $s['voter_count']; ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($s['eklenme_tarihi'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($s['durum'] === 'aktif'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success px-3">AKTİF</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary px-3">KAPALI</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="istisare-formu.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-primary shadow-sm">
                                            <i class="fas fa-external-link-alt me-1"></i> Yönet
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($sessions)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted italic">
                                        Henüz bir istişare oturumu başlatılmamış.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Yeni İstişare Modal -->
<div class="modal fade" id="yeniIstisareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni İstişare Oturumu Başlat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">İstişare Başlığı</label>
                        <input type="text" name="baslik" class="form-control" placeholder="Örn: 2026 Başkanlık Seçimi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şube İsmi</label>
                        <input type="text" name="sube_ismi" class="form-control" placeholder="Örn: AIF Innsbruck" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İstişare Kurulu Üyeleri</label>
                        <textarea name="kurul_uyeleri" class="form-control" rows="2" placeholder="İsimleri virgülle ayırarak yazınız"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">İstişareyi Başlat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
