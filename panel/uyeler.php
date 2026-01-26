<?php
/**
 * Başkan - Üye Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';


// Permission check for viewing sensitive info (management view)
$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$canManage = $auth->hasModulePermission('baskan_uyeler');

// Note: We allow everyone to VIEW the list (Basic Info), but only managers see Details (Email, Phone, etc.)

$q = trim($_GET['q'] ?? '');
$params = [$user['byk_id']];
$where = 'WHERE k.byk_id = ?';

if ($q !== '') {
    $where .= " AND (CONCAT(k.ad, ' ', k.soyad) LIKE ? OR k.email LIKE ? OR k.telefon LIKE ?)";
    $keyword = '%' . $q . '%';
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}

$uyeler = $db->fetchAll("
    SELECT k.*, r.rol_adi
    FROM kullanicilar k
    INNER JOIN roller r ON k.rol_id = r.rol_id
    $where
    ORDER BY k.ad ASC
", $params);

$aktifSayisi = array_reduce($uyeler, fn($carry, $item) => $carry + ($item['aktif'] ? 1 : 0), 0);

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-users me-2"></i>Üyeler</h1>
                <p class="text-muted mb-0">BYK’nıza bağlı aktif tüm üyeleri görüntüleyin.</p>
            </div>
            <div class="text-muted">
                <span class="badge bg-success me-2">Aktif: <?php echo $aktifSayisi; ?></span>
                <span class="badge bg-secondary">Toplam: <?php echo count($uyeler); ?></span>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form class="row g-3" method="get">
                    <div class="col-md-8">
                        <label class="form-label">Arama</label>
                        <input type="text" name="q" class="form-control" placeholder="İsim<?php echo $canManage ? ', e-posta veya telefon' : ''; ?>" value="<?php echo htmlspecialchars($q); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Filtrele
                        </button>
                        <?php if ($q !== ''): ?>
                            <a href="/panel/uyeler.php" class="btn btn-outline-secondary">
                                Temizle
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3">
            <?php if (empty($uyeler)): ?>
                <div class="col-12">
                    <div class="alert alert-light border text-center">
                        <i class="fas fa-user-slash fa-2x mb-2 text-muted"></i>
                        <p class="mb-0 text-muted">Gösterilecek üye bulunamadı.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($uyeler as $uye): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad']); ?></h5>
                                        <span class="badge bg-light text-dark mt-1">
                                            <?php echo $uye['rol_adi'] === 'uye' ? 'Üye' : ucfirst($uye['rol_adi']); ?>
                                        </span>
                                    </div>
                                    <span class="badge bg-<?php echo $uye['aktif'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $uye['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </div>
                                <ul class="list-unstyled small mb-0">
                                    <?php if ($canManage): ?>
                                    <li class="mb-1">
                                        <i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($uye['email']); ?>
                                    </li>
                                    <?php if ($uye['telefon']): ?>
                                        <li class="mb-1">
                                            <i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($uye['telefon']); ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($uye['son_giris']): ?>
                                        <li class="mb-1 text-muted">
                                            <i class="fas fa-sign-in-alt me-2"></i>Son giriş: <?php echo date('d.m.Y H:i', strtotime($uye['son_giris'])); ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php else: ?>
                                        <li class="text-muted fst-italic">İletişim bilgileri gizli</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>


