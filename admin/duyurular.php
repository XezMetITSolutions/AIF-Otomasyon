<?php
/**
 * Ana Yönetici - Duyuru Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Duyuru Yönetimi';

// Duyurular
$duyurular = $db->fetchAll("
    SELECT d.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as olusturan
    FROM duyurular d
    INNER JOIN byk b ON d.byk_id = b.byk_id
    INNER JOIN kullanicilar u ON d.olusturan_id = u.kullanici_id
    ORDER BY d.olusturma_tarihi DESC
    LIMIT 50
");

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-bullhorn me-2"></i>Duyuru Yönetimi
                </h1>
                <a href="/admin/duyuru-ekle.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Yeni Duyuru Ekle
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($duyurular); ?></strong> duyuru
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Başlık</th>
                                    <th>BYK</th>
                                    <th>Durum</th>
                                    <th>Oluşturan</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($duyurular)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Henüz duyuru eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($duyurular as $duyuru): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($duyuru['baslik']); ?></td>
                                            <td><?php echo htmlspecialchars($duyuru['byk_adi']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $duyuru['aktif'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $duyuru['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($duyuru['olusturan']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($duyuru['olusturma_tarihi'])); ?></td>
                                            <td>
                                                <a href="/admin/duyuru-duzenle.php?id=<?php echo $duyuru['duyuru_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
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

