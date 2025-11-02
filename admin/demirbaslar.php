<?php
/**
 * Ana Yönetici - Demirbaş Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Demirbaş Yönetimi';

// Demirbaşlar
$demirbaslar = $db->fetchAll("
    SELECT d.*, b.byk_adi
    FROM demirbaslar d
    LEFT JOIN byk b ON d.byk_id = b.byk_id
    ORDER BY d.olusturma_tarihi DESC
    LIMIT 50
");

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
                    <i class="fas fa-box me-2"></i>Demirbaş Yönetimi
                </h1>
                <a href="/admin/demirbas-ekle.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Yeni Demirbaş Ekle
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($demirbaslar); ?></strong> demirbaş
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Demirbaş Adı</th>
                                    <th>Kategori</th>
                                    <th>Seri No</th>
                                    <th>BYK</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($demirbaslar)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Henüz demirbaş eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($demirbaslar as $demirbas): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($demirbas['demirbas_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($demirbas['kategori'] ?? '-'); ?></td>
                                            <td><code><?php echo htmlspecialchars($demirbas['seri_no'] ?? '-'); ?></code></td>
                                            <td><?php echo htmlspecialchars($demirbas['byk_adi'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $demirbas['durum'] === 'kullanimda' ? 'success' : ($demirbas['durum'] === 'arizali' ? 'danger' : 'warning'); ?>">
                                                    <?php echo htmlspecialchars($demirbas['durum']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/admin/demirbas-duzenle.php?id=<?php echo $demirbas['demirbas_id']; ?>" class="btn btn-sm btn-outline-primary">
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
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>

