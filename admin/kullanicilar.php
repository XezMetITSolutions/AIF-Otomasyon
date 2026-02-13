<?php
/**
 * Ana Yönetici - Kullanıcı Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Kullanıcı Yönetimi';

// Sayfalama
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filtreleme
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['rol']) ? trim($_GET['rol']) : '';
$bykFilter = isset($_GET['byk']) ? trim($_GET['byk']) : '';
$statusFilter = $_GET['status'] ?? '1'; // Varsayılan olarak aktifleri göster

// Filtreleme parametrelerini temizle ve hazırla
$params = [];
$where = [];

if ($statusFilter !== 'all') {
    $where[] = "k.aktif = " . (int) $statusFilter;
} else {
    $where[] = "1=1";
}

if ($search) {
    $where[] = "(LOWER(k.ad) LIKE LOWER(?) OR LOWER(k.soyad) LIKE LOWER(?) OR LOWER(k.email) LIKE LOWER(?) OR LOWER(CONCAT(k.ad, ' ', k.soyad)) LIKE LOWER(?))";
    $searchVal = "%" . mb_strtolower($search, 'UTF-8') . "%";
    $params[] = $searchVal;
    $params[] = $searchVal;
    $params[] = $searchVal;
    $params[] = $searchVal;
}

if ($roleFilter) {
    $where[] = "r.rol_adi = ?";
    $params[] = $roleFilter;
}

if ($bykFilter) {
    $where[] = "EXISTS (SELECT 1 FROM kullanici_byklar kb WHERE kb.kullanici_id = k.kullanici_id AND kb.byk_id = ?)";
    $params[] = $bykFilter;
}

$whereClause = implode(' AND ', $where);

// Toplam kayıt için joinler (filtre tutarlılığı için)
$totalQuery = "SELECT COUNT(*) as count 
               FROM kullanicilar k
               LEFT JOIN roller r ON k.rol_id = r.rol_id
               LEFT JOIN byk b ON k.byk_id = b.byk_id
               WHERE $whereClause";
$total = $db->fetch($totalQuery, $params)['count'];

// Kullanıcılar - BYK bilgisini byk_categories'den al
try {
    // Önce byk_categories tablosunu kullanarak kullanıcıları çek
    $kullanicilar = $db->fetchAll("
        SELECT k.*, 
               COALESCE(r.rol_adi, 'Tanımsız') as rol_adi,
               (SELECT GROUP_CONCAT(COALESCE(bc.name, b2.byk_adi) SEPARATOR ', ') 
                FROM kullanici_byklar kb 
                JOIN byk b2 ON kb.byk_id = b2.byk_id 
                LEFT JOIN byk_categories bc ON b2.byk_kodu = bc.code
                WHERE kb.kullanici_id = k.kullanici_id) as tum_byklar,
               COALESCE(bc_dir.name, bc_via_b.name, b.byk_adi, '-') as byk_adi,
               COALESCE(bc_dir.code, bc_via_b.code, b.byk_kodu, '') as byk_kodu,
               COALESCE(bc_dir.color, bc_via_b.color, b.renk_kodu, '#009872') as byk_renk,
               ab.alt_birim_adi AS gorev_adi
        FROM kullanicilar k
        LEFT JOIN roller r ON k.rol_id = r.rol_id
        LEFT JOIN byk b ON k.byk_id = b.byk_id
        LEFT JOIN byk_categories bc_dir ON k.byk_id = bc_dir.id
        LEFT JOIN byk_categories bc_via_b ON b.byk_kodu = bc_via_b.code
        LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
        WHERE $whereClause
        ORDER BY k.olusturma_tarihi DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$perPage, $offset]));
} catch (Exception $e) {
    // byk_categories yoksa eski sorguyu kullan
    $kullanicilar = $db->fetchAll(
        "SELECT k.*, COALESCE(r.rol_adi, 'Tanımsız') as rol_adi, b.byk_adi, ab.alt_birim_adi AS gorev_adi
         FROM kullanicilar k
         LEFT JOIN roller r ON k.rol_id = r.rol_id
         LEFT JOIN byk b ON k.byk_id = b.byk_id
         LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
         WHERE $whereClause
         ORDER BY k.olusturma_tarihi DESC
         LIMIT ? OFFSET ?",
        array_merge($params, [$perPage, $offset])
    );
}

// Roller (filtre için)
$roller = $db->fetchAll("SELECT * FROM roller ORDER BY rol_yetki_seviyesi DESC");

// BYK'lar (filtre için) - Önce byk_categories'i kontrol et
try {
    $bykList = $db->fetchAll("SELECT id as byk_id, name as byk_adi, code as byk_kodu FROM byk_categories WHERE code IN ('AT', 'GT', 'KGT', 'gt', 'KT') ORDER BY code");
} catch (Exception $e) {
    // byk_categories yoksa eski byk tablosunu kullan
    $bykList = $db->fetchAll("SELECT * FROM byk WHERE aktif = 1 AND byk_kodu IN ('AT', 'GT', 'KGT', 'gt', 'KT') ORDER BY byk_adi");
}

$totalPages = ceil($total / $perPage);

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-users me-2"></i>Kullanıcı Yönetimi
            </h1>
            <a href="/admin/kullanici-ekle.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Yeni Kullanıcı Ekle
            </a>
        </div>



        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>İşlem başarıyla tamamlandı.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php
                $errorMessages = [
                    'notfound' => 'Kullanıcı bulunamadı.',
                    'permission' => 'Bu işlem için yetkiniz bulunmamaktadır.'
                ];
                echo $errorMessages[$_GET['error']] ?? 'Bir hata oluştu.';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filtreler -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Arama</label>
                        <input type="text" class="form-control" name="search"
                            value="<?php echo htmlspecialchars($search); ?>" placeholder="Ad, Soyad, E-posta...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Rol</label>
                        <select class="form-select" name="rol">
                            <option value="">Tüm Roller</option>
                            <?php foreach ($roller as $rol): ?>
                                <option value="<?php echo $rol['rol_adi']; ?>" <?php echo $roleFilter === $rol['rol_adi'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rol['rol_adi']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">BYK</label>
                        <select class="form-select" name="byk">
                            <option value="">Tüm BYK'lar</option>
                            <?php foreach ($bykList as $byk): ?>
                                <option value="<?php echo $byk['byk_id']; ?>" <?php echo $bykFilter == $byk['byk_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($byk['byk_adi']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Durum</label>
                        <select class="form-select" name="status">
                            <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Pasif</option>
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tümü</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" title="Ara">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <?php if ($search || $roleFilter || $bykFilter || $statusFilter !== '1'): ?>
                        <div class="col-md-1 d-flex align-items-end">
                            <a href="/admin/kullanicilar.php" class="btn btn-outline-secondary w-100" title="Temizle">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Kullanıcı Listesi -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Toplam: <strong><?php echo $total; ?></strong> kullanıcı</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>E-posta</th>
                                <th>Rol</th>
                                <th>BYK</th>
                                <th>Görev</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kullanicilar)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Kullanıcı bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($kullanicilar as $kullanici): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($kullanici['ad'] . ' ' . $kullanici['soyad']); ?></td>
                                        <td><?php echo htmlspecialchars($kullanici['email']); ?></td>
                                        <td>
                                            <?php if ($kullanici['rol_adi'] === Auth::ROLE_SUPER_ADMIN): ?>
                                                <span class="badge bg-danger">Süper Admin</span>
                                            <?php else: ?>
                                                <span
                                                    class="badge bg-info"><?php echo htmlspecialchars($kullanici['rol_adi']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($kullanici['tum_byklar'])): ?>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($kullanici['tum_byklar']); ?>
                                                </span>
                                            <?php elseif (!empty($kullanici['byk_adi']) && $kullanici['byk_adi'] !== '-'): ?>
                                                <span class="badge"
                                                    style="background-color: <?php echo htmlspecialchars($kullanici['byk_renk'] ?? '#009872'); ?>; color: white;">
                                                    <?php echo htmlspecialchars($kullanici['byk_adi']); ?>
                                                </span>
                                                <?php if (!empty($kullanici['byk_kodu'])): ?>
                                                    <small
                                                        class="text-muted ms-1">(<?php echo htmlspecialchars($kullanici['byk_kodu']); ?>)</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($kullanici['gorev_adi'])): ?>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($kullanici['gorev_adi']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($kullanici['divan_uyesi'])): ?>
                                                <span class="badge bg-warning text-dark ms-1">
                                                    <i class="fas fa-star me-1"></i>Divan
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="/admin/kullanici-duzenle.php?id=<?php echo $kullanici['kullanici_id']; ?>"
                                                    class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($kullanici['rol_adi'] !== Auth::ROLE_SUPER_ADMIN): ?>
                                                    <a href="/admin/baskan-yetkileri.php?id=<?php echo $kullanici['kullanici_id']; ?>"
                                                        class="btn btn-sm btn-outline-warning" title="Yetkiler">
                                                        <i class="fas fa-user-shield"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger confirm-delete"
                                                    data-id="<?php echo $kullanici['kullanici_id']; ?>" data-type="kullanici"
                                                    data-name="<?php echo htmlspecialchars($kullanici['ad'] . ' ' . $kullanici['soyad']); ?>"
                                                    title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Sayfalama -->
                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&rol=<?php echo urlencode($roleFilter); ?>&byk=<?php echo urlencode($bykFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>