<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
Middleware::requireModulePermission('baskan_raggal_talepleri');

$db = Database::getInstance();
$pageTitle = 'Raggal Rezervasyon Talepleri';

// İşlem (Onay/Red)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = $_POST['id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'onaylandi' : 'reddedildi';

    try {
        $db->query("UPDATE raggal_talepleri SET durum = ? WHERE id = ?", [$status, $id]);
        $success = 'Talep durumu güncellendi.';
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Talepleri Listele
$talepler = $db->fetchAll("
    SELECT t.*, CONCAT(u.ad, ' ', u.soyad) as kullanici_adi 
    FROM raggal_talepleri t 
    JOIN kullanicilar u ON t.kullanici_id = u.kullanici_id 
    ORDER BY t.created_at DESC
");

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-calendar-check me-2"></i>Raggal Rezervasyon Talepleri
            </h1>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tarih Aralığı</th>
                                <th>Kullanıcı</th>
                                <th>Açıklama</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($talepler)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Henüz talep bulunmuyor.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($talepler as $talep): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('d.m.Y H:i', strtotime($talep['baslangic_tarihi'])); ?> - <br>
                                            <?php echo date('d.m.Y H:i', strtotime($talep['bitis_tarihi'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($talep['kullanici_adi']); ?></td>
                                        <td><?php echo htmlspecialchars($talep['aciklama']); ?></td>
                                        <td>
                                            <?php if ($talep['durum'] === 'bekliyor'): ?>
                                                <span class="badge bg-warning text-dark">Bekliyor</span>
                                            <?php elseif ($talep['durum'] === 'onaylandi'): ?>
                                                <span class="badge bg-success">Onaylandı</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Reddedildi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($talep['durum'] === 'bekliyor'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="id" value="<?php echo $talep['id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" title="Onayla">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" title="Reddet">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
