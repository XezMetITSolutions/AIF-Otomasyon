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

        <div class="row">
            <?php foreach ($sessions as $s): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="card-header <?php echo $s['durum'] === 'aktif' ? 'bg-primary' : 'bg-secondary'; ?> text-white d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-uppercase"><?php echo htmlspecialchars($s['sube_ismi']); ?></span>
                            <?php if ($s['durum'] === 'aktif'): ?>
                                <span class="badge bg-success">AKTİF</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark">KAPALI</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($s['baslik']); ?></h5>
                            <div class="small text-muted mb-2">
                                <i class="fas fa-users me-2"></i><b>Kurul:</b> <?php echo htmlspecialchars($s['kurul_uyeleri'] ?: '-'); ?>
                            </div>
                            <div class="small text-muted mb-3">
                                <i class="fas fa-id-card me-2"></i><b>Katılım:</b> <?php echo $s['voter_count']; ?> Kişi Oy Kullandı
                            </div>
                            <div class="d-grid">
                                <a href="istisare-formu.php?id=<?php echo $s['id']; ?>" class="btn <?php echo $s['durum'] === 'aktif' ? 'btn-outline-primary' : 'btn-outline-secondary'; ?>">
                                    <i class="fas fa-external-link-alt me-2"></i>İstişareye Git & Yönet
                                </a>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-muted small border-top-0">
                            <i class="far fa-clock me-1"></i> Başlangıç: <?php echo date('d.m.Y H:i', strtotime($s['eklenme_tarihi'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
