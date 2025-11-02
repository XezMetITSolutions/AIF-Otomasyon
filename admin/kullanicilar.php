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
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filtreleme
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['rol'] ?? '';
$bykFilter = $_GET['byk'] ?? '';

$where = ["k.aktif = 1"];
$params = [];

if ($search) {
    $where[] = "(k.ad LIKE ? OR k.soyad LIKE ? OR k.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($roleFilter) {
    $where[] = "r.rol_adi = ?";
    $params[] = $roleFilter;
}

if ($bykFilter) {
    $where[] = "k.byk_id = ?";
    $params[] = $bykFilter;
}

$whereClause = implode(' AND ', $where);

// Toplam kayıt
$total = $db->fetch(
    "SELECT COUNT(*) as count 
     FROM kullanicilar k
     INNER JOIN roller r ON k.rol_id = r.rol_id
     WHERE $whereClause",
    $params
)['count'];

// Kullanıcılar
$kullanicilar = $db->fetchAll(
    "SELECT k.*, r.rol_adi, b.byk_adi
     FROM kullanicilar k
     INNER JOIN roller r ON k.rol_id = r.rol_id
     LEFT JOIN byk b ON k.byk_id = b.byk_id
     WHERE $whereClause
     ORDER BY k.olusturma_tarihi DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

// Roller (filtre için)
$roller = $db->fetchAll("SELECT * FROM roller ORDER BY rol_yetki_seviyesi DESC");

// BYK'lar (filtre için)
$bykList = $db->fetchAll("SELECT * FROM byk WHERE aktif = 1 ORDER BY byk_adi");

$totalPages = ceil($total / $perPage);

include __DIR__ . '/../includes/header.php';
?>

<main class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 p-0">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-users me-2"></i>Kullanıcı Yönetimi
                </h1>
                <a href="/admin/kullanici-ekle.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Yeni Kullanıcı Ekle
                </a>
            </div>
            
            <!-- Filtreler -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Arama</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Ad, Soyad, E-posta...">
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
                        <div class="col-md-3">
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
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filtrele
                            </button>
                        </div>
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
                                    <th>ID</th>
                                    <th>Ad Soyad</th>
                                    <th>E-posta</th>
                                    <th>Rol</th>
                                    <th>BYK</th>
                                    <th>Durum</th>
                                    <th>Son Giriş</th>
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
                                            <td><?php echo $kullanici['kullanici_id']; ?></td>
                                            <td><?php echo htmlspecialchars($kullanici['ad'] . ' ' . $kullanici['soyad']); ?></td>
                                            <td><?php echo htmlspecialchars($kullanici['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $kullanici['rol_adi'] === 'super_admin' ? 'danger' : ($kullanici['rol_adi'] === 'baskan' ? 'warning' : 'info'); ?>">
                                                    <?php echo htmlspecialchars($kullanici['rol_adi']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($kullanici['byk_adi'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $kullanici['aktif'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $kullanici['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $kullanici['son_giris'] ? date('d.m.Y H:i', strtotime($kullanici['son_giris'])) : '-'; ?></td>
                                            <td>
                                                <a href="/admin/kullanici-duzenle.php?id=<?php echo $kullanici['kullanici_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
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
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&rol=<?php echo urlencode($roleFilter); ?>&byk=<?php echo urlencode($bykFilter); ?>">
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
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>

