<?php
/**
 * Ana Yönetici - Toplantı Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Toplantı Yönetimi';

// Toplantılar
$toplantilar = $db->fetchAll("
    SELECT t.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as olusturan
    FROM toplantilar t
    INNER JOIN byk b ON t.byk_id = b.byk_id
    INNER JOIN kullanicilar u ON t.olusturan_id = u.kullanici_id
    ORDER BY t.toplanti_tarihi DESC
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
                    <i class="fas fa-users-cog me-2"></i>Toplantı Yönetimi
                </h1>
                <a href="/admin/toplanti-ekle.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Yeni Toplantı Ekle
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($toplantilar); ?></strong> toplantı
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Başlık</th>
                                    <th>BYK</th>
                                    <th>Tarih</th>
                                    <th>Tür</th>
                                    <th>Durum</th>
                                    <th>Oluşturan</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($toplantilar)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Henüz toplantı eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($toplantilar as $toplanti): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($toplanti['baslik']); ?></td>
                                            <td><?php echo htmlspecialchars($toplanti['byk_adi']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi'])); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($toplanti['toplanti_turu']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $toplanti['durum'] === 'tamamlandi' ? 'success' : ($toplanti['durum'] === 'devam_ediyor' ? 'warning' : 'info'); ?>">
                                                    <?php echo htmlspecialchars($toplanti['durum']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($toplanti['olusturan']); ?></td>
                                            <td>
                                                <a href="/admin/toplanti-duzenle.php?id=<?php echo $toplanti['toplanti_id']; ?>" class="btn btn-sm btn-outline-primary">
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

