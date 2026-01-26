<?php
/**
 * Ana Yönetici - İzin Talepleri Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'İzin Talepleri';

// Filtre
$durum = $_GET['durum'] ?? '';

$where = [];
$params = [];

if ($durum) {
    $where[] = "it.durum = ?";
    $params[] = $durum;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// İzin talepleri
$izinTalepleri = $db->fetchAll("
    SELECT it.*, CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, k.email, b.byk_adi
    FROM izin_talepleri it
    INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    $whereClause
    ORDER BY it.olusturma_tarihi DESC
    LIMIT 50
", $params);

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-calendar-check me-2"></i>İzin Talepleri
                </h1>
                <div>
                    <a href="?durum=beklemede" class="btn btn-sm btn-warning">Bekleyenler</a>
                    <a href="?durum=onaylandi" class="btn btn-sm btn-success">Onaylananlar</a>
                    <a href="?durum=reddedildi" class="btn btn-sm btn-danger">Reddedilenler</a>
                    <a href="?" class="btn btn-sm btn-secondary">Tümü</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($izinTalepleri); ?></strong> izin talebi
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>BYK</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                    <th>İzin Nedeni</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($izinTalepleri)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Henüz izin talebi bulunmamaktadır.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($izinTalepleri as $izin): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($izin['kullanici_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($izin['byk_adi'] ?? '-'); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($izin['baslangic_tarihi'])); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($izin['bitis_tarihi'])); ?></td>
                                            <td><?php echo htmlspecialchars($izin['izin_nedeni'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $izin['durum'] === 'onaylandi' ? 'success' : ($izin['durum'] === 'reddedildi' ? 'danger' : 'warning'); ?>">
                                                    <?php echo htmlspecialchars($izin['durum']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/admin/izin-detay.php?id=<?php echo $izin['izin_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>

