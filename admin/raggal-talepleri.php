<?php
/**
 * Ana Yönetici - Raggal Rezervasyon Talepleri Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Raggal Rezervasyon Talepleri';

// Filtre
$durum = $_GET['durum'] ?? '';

$where = [];
$params = [];

if ($durum) {
    $where[] = "r.durum = ?";
    $params[] = $durum;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Raggal talepleri
$talepler = $db->fetchAll("
    SELECT r.*, CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, k.email, b.byk_adi
    FROM raggal_talepleri r
    INNER JOIN kullanicilar k ON r.kullanici_id = k.kullanici_id
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    $whereClause
    ORDER BY r.created_at DESC
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
                    <i class="fas fa-calendar-alt me-2"></i>Raggal Rezervasyon Talepleri
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
                    Toplam: <strong><?php echo count($talepler); ?></strong> rezervasyon talebi
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
                                    <th>Açıklama</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($talepler)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Henüz rezervasyon talebi bulunmamaktadır.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($talepler as $talep): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($talep['kullanici_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($talep['byk_adi'] ?? '-'); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($talep['baslangic_tarihi'])); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($talep['bitis_tarihi'])); ?></td>
                                            <td><?php echo htmlspecialchars($talep['aciklama'] ?? '-'); ?></td>
                                            <td>
                                                <?php 
                                                $badgeClass = 'warning text-dark';
                                                if ($talep['durum'] === 'onaylandi') $badgeClass = 'success';
                                                if ($talep['durum'] === 'reddedildi') $badgeClass = 'danger';
                                                ?>
                                                <span class="badge bg-<?php echo $badgeClass; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($talep['durum'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $talep['id']; ?>, 'onaylandi')"><i class="fas fa-check"></i></button>
                                                <button class="btn btn-sm btn-danger" onclick="updateStatus(<?php echo $talep['id']; ?>, 'reddedildi')"><i class="fas fa-times"></i></button>
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

<script>
function updateStatus(id, status) {
    if (!confirm('Bu rezervasyonun durumunu ' + status + ' olarak değiştirmek istediğinize emin misiniz?')) return;
    alert('Durum güncelleme özelliği henüz API tarafında eklenmedi.');
}
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>
