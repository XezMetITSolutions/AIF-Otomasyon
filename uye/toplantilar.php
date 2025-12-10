<?php
/**
 * Üye - Toplantılarım
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireUye();
Middleware::requireModulePermission('uye_toplantilar');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

$pageTitle = 'Toplantılarım';
$durumFiltre = $_GET['durum'] ?? 'yaklasan';
$katilimFiltre = $_GET['katilim'] ?? '';
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$allowedDurumFiltresi = ['yaklasan', 'gecmis', 'tum'];
$allowedKatilimDurumlari = ['beklemede', 'katilacak', 'katilmayacak', 'mazeret'];

if (!in_array($durumFiltre, $allowedDurumFiltresi, true)) {
    $durumFiltre = 'yaklasan';
}
if ($katilimFiltre && !in_array($katilimFiltre, $allowedKatilimDurumlari, true)) {
    $katilimFiltre = '';
}

$csrfTokenName = $appConfig['security']['csrf_token_name'] ?? 'csrf_token';
$csrfToken = Middleware::generateCSRF();
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Oturum doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'katilim_guncelle') {
            $katilimciId = isset($_POST['katilimci_id']) ? (int) $_POST['katilimci_id'] : 0;
            $yeniDurum = $_POST['katilim_durumu'] ?? 'beklemede';
            $mazeretAciklama = trim($_POST['mazeret_aciklama'] ?? '');
            
            if (!in_array($yeniDurum, $allowedKatilimDurumlari, true)) {
                $errors[] = 'Geçersiz katılım durumu seçildi.';
            } else {
                $katilimci = $db->fetch("
                    SELECT * FROM toplanti_katilimcilar
                    WHERE katilimci_id = ? AND kullanici_id = ?
                ", [$katilimciId, $user['id']]);
                
                if (!$katilimci) {
                    $errors[] = 'Katılım kaydı bulunamadı.';
                } else {
                    if ($yeniDurum === 'mazeret' && $mazeretAciklama === '') {
                        $errors[] = 'Mazeret açıklaması zorunludur.';
                    }
                    
                    if (empty($errors)) {
                        $db->query("
                            UPDATE toplanti_katilimcilar
                            SET katilim_durumu = ?, mazeret_aciklama = ?, yanit_tarihi = NOW()
                            WHERE katilimci_id = ?
                        ", [$yeniDurum, $mazeretAciklama ?: null, $katilimciId]);
                        
                        $messages[] = 'Katılım durumunuz güncellendi.';
                    }
                }
            }
        }
    }
}

$conditions = ["tk.kullanici_id = ?"];
$params = [$user['id']];
$orderBy = "t.toplanti_tarihi ASC";

switch ($durumFiltre) {
    case 'gecmis':
        $conditions[] = "t.toplanti_tarihi < NOW()";
        $orderBy = "t.toplanti_tarihi DESC";
        break;
    case 'tum':
        // filtre yok
        break;
    default:
        $conditions[] = "t.toplanti_tarihi >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        break;
}

if ($katilimFiltre) {
    $conditions[] = "tk.katilim_durumu = ?";
    $params[] = $katilimFiltre;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

$toplantilar = $db->fetchAll("
    SELECT t.*, tk.katilim_durumu, tk.katilimci_id, tk.mazeret_aciklama
    FROM toplanti_katilimcilar tk
    INNER JOIN toplantilar t ON tk.toplanti_id = t.toplanti_id
    $whereClause
    ORDER BY $orderBy
    LIMIT 100
", $params);

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <!-- Header & Filters -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1 text-dark">
                    <i class="fas fa-calendar-check me-2 text-primary"></i>Toplantılarım
                </h1>
                <p class="text-muted mb-0">Katılmanız beklenen toplantıları yönetin.</p>
            </div>
            
            <div class="bg-white p-1 rounded-pill shadow-sm d-inline-flex">
                <a href="?durum=yaklasan" class="btn btn-sm rounded-pill px-3 <?php echo $durumFiltre === 'yaklasan' ? 'btn-primary' : 'btn-light text-muted'; ?>">
                    Yaklaşan
                </a>
                <a href="?durum=gecmis" class="btn btn-sm rounded-pill px-3 <?php echo $durumFiltre === 'gecmis' ? 'btn-primary' : 'btn-light text-muted'; ?>">
                    Geçmiş
                </a>
                <a href="?durum=tum" class="btn btn-sm rounded-pill px-3 <?php echo $durumFiltre === 'tum' ? 'btn-primary' : 'btn-light text-muted'; ?>">
                    Tümü
                </a>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm border-0 border-start border-4 border-danger rounded-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle fa-lg me-3"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success shadow-sm border-0 border-start border-4 border-success rounded-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-lg me-3"></i>
                    <div>
                        <?php foreach ($messages as $message): ?>
                            <div><?php echo htmlspecialchars($message); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Meeting Grid -->
        <?php if (empty($toplantilar)): ?>
            <div class="text-center py-5 bg-white rounded-3 shadow-sm">
                <div class="mb-3 text-muted opacity-50">
                    <i class="fas fa-calendar-times fa-4x"></i>
                </div>
                <h5 class="text-muted">Listelenecek toplantı bulunamadı</h5>
                <p class="text-muted small">Şu an için gösterilecek bir toplantı kaydı mevcut değil.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($toplantilar as $toplanti): ?>
                    <?php
                        $tarih = new DateTime($toplanti['toplanti_tarihi']);
                        $durumClass = match ($toplanti['katilim_durumu']) {
                            'katilacak' => 'success',
                            'katilmayacak' => 'danger',
                            'mazeret' => 'warning',
                            default => 'secondary',
                        };
                        $durumText = ucfirst($toplanti['katilim_durumu']);
                        
                        // Card Border Color based on status
                        $borderClass = $toplanti['katilim_durumu'] === 'beklemede' ? 'border-warning' : 'border-0';
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 shadow-sm <?php echo $borderClass; ?> hover-shadow transition-all">
                            <div class="card-body">
                                <div class="d-flex gap-3 mb-3">
                                    <!-- Date Badge -->
                                    <div class="d-flex flex-column align-items-center justify-content-center bg-light rounded-3 p-2 text-center" style="min-width: 70px; height: 70px;">
                                        <span class="h4 mb-0 fw-bold text-dark"><?php echo $tarih->format('d'); ?></span>
                                        <span class="small text-uppercase text-muted"><?php echo $tarih->format('M'); ?></span>
                                    </div>
                                    
                                    <!-- Title & Meta -->
                                    <div class="flex-grow-1">
                                        <h5 class="card-title fw-bold mb-1 text-truncate-2">
                                            <?php echo htmlspecialchars($toplanti['baslik']); ?>
                                        </h5>
                                        <div class="d-flex align-items-center text-muted small mt-1">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo $tarih->format('H:i'); ?>
                                            
                                            <?php if($toplanti['konum']): ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($toplanti['konum']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description Excerpt -->
                                <?php if($toplanti['aciklama']): ?>
                                    <p class="text-muted small mb-3 text-truncate-3">
                                        <?php echo htmlspecialchars($toplanti['aciklama']); ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Current Status Badge (Top right absolute or inline) -->
                                <div class="mb-3">
                                    <span class="badge rounded-pill bg-<?php echo $durumClass; ?> bg-opacity-10 text-<?php echo $durumClass; ?> px-3 py-2">
                                        <i class="fas fa-circle small me-1"></i>
                                        <?php echo $durumText; ?>
                                    </span>
                                    <?php if($toplanti['mazeret_aciklama']): ?>
                                        <small class="d-block mt-1 text-muted fst-italic">
                                            "<?php echo htmlspecialchars($toplanti['mazeret_aciklama']); ?>"
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Action Footer -->
                            <div class="card-footer bg-light bg-opacity-50 border-top-0 p-3">
                                <form method="post">
                                    <input type="hidden" name="action" value="katilim_guncelle">
                                    <input type="hidden" name="katilimci_id" value="<?php echo $toplanti['katilimci_id']; ?>">
                                    <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                    
                                    <label class="form-label small fw-bold text-uppercase text-muted mb-2">Durum Güncelle</label>
                                    <div class="input-group input-group-sm">
                                        <select name="katilim_durumu" class="form-select status-select" data-target="mazeret-<?php echo $toplanti['katilimci_id']; ?>">
                                            <?php foreach ($allowedKatilimDurumlari as $durum): ?>
                                                <option value="<?php echo $durum; ?>" <?php echo $toplanti['katilim_durumu'] === $durum ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($durum); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Mazeret Input (Hidden by default unless mazeret selected) -->
                                    <div id="mazeret-<?php echo $toplanti['katilimci_id']; ?>" class="mt-2 <?php echo $toplanti['katilim_durumu'] === 'mazeret' ? '' : 'd-none'; ?>">
                                        <input type="text" name="mazeret_aciklama" class="form-control form-control-sm" 
                                               placeholder="Mazeretiniz nedir?" 
                                               value="<?php echo htmlspecialchars($toplanti['mazeret_aciklama'] ?? ''); ?>">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
/* Custom Utilities */
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.text-truncate-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.hover-shadow:hover {
    transform: translateY(-3px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
.transition-all {
    transition: all 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/Hide Mazeret input based on selection
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const mazeretDiv = document.getElementById(this.dataset.target);
            if (this.value === 'mazeret') {
                mazeretDiv.classList.remove('d-none');
                mazeretDiv.querySelector('input').required = true;
                mazeretDiv.querySelector('input').focus();
            } else {
                mazeretDiv.classList.add('d-none');
                mazeretDiv.querySelector('input').required = false;
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
