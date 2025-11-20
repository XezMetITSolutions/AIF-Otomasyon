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

$seciliToplanti = null;
if ($selectedId) {
    $seciliToplanti = $db->fetch("
        SELECT t.*, tk.katilim_durumu, tk.mazeret_aciklama
        FROM toplanti_katilimcilar tk
        INNER JOIN toplantilar t ON tk.toplanti_id = t.toplanti_id
        WHERE tk.kullanici_id = ? AND t.toplanti_id = ?
    ", [$user['id'], $selectedId]);
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
                    <i class="fas fa-users-cog me-2"></i>Toplantılarım
                </h1>
                <small class="text-muted">Katılmanız beklenen toplantıları görüntüleyin ve yanıtlayın</small>
            </div>
            <div class="btn-group">
                <a href="?durum=yaklasan" class="btn btn-outline-primary <?php echo $durumFiltre === 'yaklasan' ? 'active' : ''; ?>">
                    Yaklaşan
                </a>
                <a href="?durum=gecmis" class="btn btn-outline-primary <?php echo $durumFiltre === 'gecmis' ? 'active' : ''; ?>">
                    Geçmiş
                </a>
                <a href="?durum=tum" class="btn btn-outline-primary <?php echo $durumFiltre === 'tum' ? 'active' : ''; ?>">
                    Tümü
                </a>
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
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end">
                    <input type="hidden" name="durum" value="<?php echo htmlspecialchars($durumFiltre); ?>">
                    <div class="col-md-4">
                        <label class="form-label">Katılım Durumuna Göre Filtrele</label>
                        <select name="katilim" class="form-select">
                            <option value="">Tümü</option>
                            <?php foreach ($allowedKatilimDurumlari as $durum): ?>
                                <option value="<?php echo $durum; ?>" <?php echo $katilimFiltre === $durum ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($durum); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i>Filtre Uygula
                        </button>
                    </div>
                    <?php if ($katilimFiltre): ?>
                        <div class="col-md-3">
                            <a href="?durum=<?php echo urlencode($durumFiltre); ?>" class="btn btn-outline-secondary w-100">Filtreyi Temizle</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <?php if ($seciliToplanti): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><?php echo htmlspecialchars($seciliToplanti['baslik']); ?></strong>
                    <span class="badge bg-info"><?php echo date('d.m.Y H:i', strtotime($seciliToplanti['toplanti_tarihi'])); ?></span>
                </div>
                <div class="card-body">
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($seciliToplanti['aciklama'] ?? 'Açıklama bulunmuyor.')); ?></p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Konum</small>
                                <strong><?php echo htmlspecialchars($seciliToplanti['konum'] ?? '-'); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Durum</small>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($seciliToplanti['katilim_durumu']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Mazeret</small>
                                <strong><?php echo htmlspecialchars($seciliToplanti['mazeret_aciklama'] ?? '-'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($selectedId): ?>
            <div class="alert alert-warning">
                Seçili toplantı bulunamadı veya yetkiniz yok.
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Toplam: <strong><?php echo count($toplantilar); ?></strong> toplantı</span>
                <?php if ($selectedId): ?>
                    <a href="/uye/toplantilar.php?durum=<?php echo urlencode($durumFiltre); ?>&katilim=<?php echo urlencode($katilimFiltre); ?>" class="btn btn-sm btn-outline-secondary">
                        Seçimi Temizle
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($toplantilar)): ?>
                    <p class="text-center text-muted mb-0">Listelenecek toplantı bulunmamaktadır.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Başlık</th>
                                    <th>Tarih</th>
                                    <th>Konum</th>
                                    <th>Katılım Durumu</th>
                                    <th>Mazeret</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($toplantilar as $toplanti): ?>
                                    <?php
                                        $rowSelected = $selectedId === (int) $toplanti['toplanti_id'] ? 'table-primary' : '';
                                        $durumBadge = match ($toplanti['katilim_durumu']) {
                                            'katilacak' => 'success',
                                            'katilmayacak' => 'danger',
                                            'mazeret' => 'warning',
                                            default => 'secondary',
                                        };
                                    ?>
                                    <tr class="<?php echo $rowSelected; ?>">
                                        <td>
                                            <a href="/uye/toplantilar.php?id=<?php echo $toplanti['toplanti_id']; ?>&durum=<?php echo urlencode($durumFiltre); ?>&katilim=<?php echo urlencode($katilimFiltre); ?>">
                                                <?php echo htmlspecialchars($toplanti['baslik']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi'])); ?></td>
                                        <td><?php echo htmlspecialchars($toplanti['konum'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $durumBadge; ?>">
                                                <?php echo ucfirst($toplanti['katilim_durumu']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($toplanti['mazeret_aciklama'] ?? '-'); ?></td>
                                        <td>
                                            <form method="post" class="row g-2 align-items-center">
                                                <input type="hidden" name="action" value="katilim_guncelle">
                                                <input type="hidden" name="katilimci_id" value="<?php echo $toplanti['katilimci_id']; ?>">
                                                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                <div class="col-lg-5">
                                                    <select name="katilim_durumu" class="form-select form-select-sm">
                                                        <?php foreach ($allowedKatilimDurumlari as $durum): ?>
                                                            <option value="<?php echo $durum; ?>" <?php echo $toplanti['katilim_durumu'] === $durum ? 'selected' : ''; ?>>
                                                                <?php echo ucfirst($durum); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-5">
                                                    <input type="text" name="mazeret_aciklama" class="form-control form-control-sm" placeholder="Mazeret (opsiyonel)" value="<?php echo htmlspecialchars($toplanti['mazeret_aciklama'] ?? ''); ?>">
                                                </div>
                                                <div class="col-lg-2 d-grid">
                                                    <button type="submit" class="btn btn-sm btn-primary">Kaydet</button>
                                                </div>
                                            </form>
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
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>


