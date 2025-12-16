<?php
/**
 * Ana Yönetici - Proje Takibi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Proje Takibi';

// Projeler
$projeler = $db->fetchAll("
    SELECT p.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as sorumlu
    FROM projeler p
    INNER JOIN byk b ON p.byk_id = b.byk_id
    LEFT JOIN kullanicilar u ON p.sorumlu_id = u.kullanici_id
    ORDER BY p.olusturma_tarihi DESC
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
                    <i class="fas fa-project-diagram me-2"></i>Proje Takibi
                </h1>
                <a href="/admin/proje-ekle.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Yeni Proje Ekle
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($projeler); ?></strong> proje
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Proje Adı</th>
                                    <th>BYK</th>
                                    <th>Sorumlu</th>
                                    <th>Durum</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($projeler)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Henüz proje eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($projeler as $proje): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($proje['baslik']); ?></td>
                                            <td><?php echo htmlspecialchars($proje['byk_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($proje['sorumlu'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $proje['durum'] === 'tamamlandi' ? 'success' : ($proje['durum'] === 'aktif' ? 'info' : 'warning'); ?>">
                                                    <?php echo htmlspecialchars($proje['durum']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $proje['baslangic_tarihi'] ? date('d.m.Y', strtotime($proje['baslangic_tarihi'])) : '-'; ?></td>
                                            <td><?php echo $proje['bitis_tarihi'] ? date('d.m.Y', strtotime($proje['bitis_tarihi'])) : '-'; ?></td>
                                            <td>
                                                <a href="/admin/proje-duzenle.php?id=<?php echo $proje['proje_id']; ?>" class="btn btn-sm btn-outline-primary">
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

