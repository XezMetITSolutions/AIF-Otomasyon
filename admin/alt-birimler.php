<?php
/**
 * Ana Yönetici - Alt Birimler Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Alt Birimler Yönetimi';

// Alt birimler
$altBirimler = $db->fetchAll("
    SELECT ab.*, b.byk_adi
    FROM alt_birimler ab
    INNER JOIN byk b ON ab.byk_id = b.byk_id
    ORDER BY b.byk_adi, ab.alt_birim_adi
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
                    <i class="fas fa-sitemap me-2"></i>Alt Birimler Yönetimi
                </h1>
                <a href="/admin/alt-birim-ekle.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Yeni Alt Birim Ekle
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($altBirimler); ?></strong> alt birim
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>BYK</th>
                                    <th>Alt Birim Adı</th>
                                    <th>Alt Birim Kodu</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($altBirimler)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Henüz alt birim eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($altBirimler as $altBirim): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($altBirim['byk_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($altBirim['alt_birim_adi']); ?></td>
                                            <td><code><?php echo htmlspecialchars($altBirim['alt_birim_kodu'] ?? '-'); ?></code></td>
                                            <td>
                                                <span class="badge bg-<?php echo $altBirim['aktif'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $altBirim['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/admin/alt-birim-duzenle.php?id=<?php echo $altBirim['alt_birim_id']; ?>" class="btn btn-sm btn-outline-primary">
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

