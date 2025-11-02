<?php
/**
 * Ana Yönetici - Etkinlik Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Etkinlik Yönetimi';

// Etkinlikler
$etkinlikler = $db->fetchAll("
    SELECT e.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as olusturan
    FROM etkinlikler e
    INNER JOIN byk b ON e.byk_id = b.byk_id
    INNER JOIN kullanicilar u ON e.olusturan_id = u.kullanici_id
    ORDER BY e.baslangic_tarihi DESC
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
                    <i class="fas fa-calendar me-2"></i>Etkinlik Yönetimi
                </h1>
                <a href="/admin/etkinlik-ekle.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Yeni Etkinlik Ekle
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($etkinlikler); ?></strong> etkinlik
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Başlık</th>
                                    <th>BYK</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                    <th>Oluşturan</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($etkinlikler)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Henüz etkinlik eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($etkinlikler as $etkinlik): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($etkinlik['baslik']); ?></td>
                                            <td><?php echo htmlspecialchars($etkinlik['byk_adi']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($etkinlik['baslangic_tarihi'])); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($etkinlik['bitis_tarihi'])); ?></td>
                                            <td><?php echo htmlspecialchars($etkinlik['olusturan']); ?></td>
                                            <td>
                                                <a href="/admin/etkinlik-duzenle.php?id=<?php echo $etkinlik['etkinlik_id']; ?>" class="btn btn-sm btn-outline-primary">
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

