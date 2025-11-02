<?php
/**
 * Ana Yönetici - BYK Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'BYK Yönetimi';

// BYK'lar
$bykList = $db->fetchAll("
    SELECT b.*, COUNT(k.kullanici_id) as kullanici_sayisi
    FROM byk b
    LEFT JOIN kullanicilar k ON b.byk_id = k.byk_id AND k.aktif = 1
    GROUP BY b.byk_id
    ORDER BY b.olusturma_tarihi DESC
");

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-building me-2"></i>BYK Yönetimi
                </h1>
                <a href="/admin/byk-ekle.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Yeni BYK Ekle
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($bykList); ?></strong> BYK
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>BYK Adı</th>
                                    <th>BYK Kodu</th>
                                    <th>Kullanıcı Sayısı</th>
                                    <th>Renk Kodu</th>
                                    <th>Durum</th>
                                    <th>Oluşturma Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bykList)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Henüz BYK eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bykList as $byk): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($byk['byk_adi']); ?></td>
                                            <td><code><?php echo htmlspecialchars($byk['byk_kodu']); ?></code></td>
                                            <td><?php echo $byk['kullanici_sayisi']; ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?php echo htmlspecialchars($byk['renk_kodu']); ?>;">
                                                    <?php echo htmlspecialchars($byk['renk_kodu']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $byk['aktif'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $byk['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($byk['olusturma_tarihi'])); ?></td>
                                            <td>
                                                <a href="/admin/byk-duzenle.php?id=<?php echo $byk['byk_id']; ?>" class="btn btn-sm btn-outline-primary">
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

