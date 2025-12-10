<?php
/**
 * Ana Yönetici - Demirbaş Talepleri Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Demirbaş Talepleri';

// Filtre
$durum = $_GET['durum'] ?? '';

$where = [];
$params = [];

if ($durum) {
    $where[] = "dt.durum = ?";
    $params[] = $durum;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Demirbaş talepleri
$talepler = $db->fetchAll("
    SELECT dt.*, CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, k.email, b.byk_adi, d.ad as demirbas_adi
    FROM demirbas_talepleri dt
    INNER JOIN kullanicilar k ON dt.kullanici_id = k.kullanici_id
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    LEFT JOIN demirbaslar d ON dt.demirbas_id = d.id
    $whereClause
    ORDER BY dt.created_at DESC
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
                    <i class="fas fa-box me-2"></i>Demirbaş Talepleri
                </h1>
                <div>
                    <a href="?durum=bekliyor" class="btn btn-sm btn-warning">Bekleyenler</a>
                    <a href="?durum=onaylandi" class="btn btn-sm btn-success">Onaylananlar</a>
                    <a href="?durum=reddedildi" class="btn btn-sm btn-danger">Reddedilenler</a>
                    <a href="?" class="btn btn-sm btn-secondary">Tümü</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($talepler); ?></strong> talep
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>BYK</th>
                                    <th>Başlık / Demirbaş</th>
                                    <th>Tarih Aralığı</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($talepler)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Henüz demirbaş talebi bulunmamaktadır.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($talepler as $talep): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($talep['kullanici_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($talep['byk_adi'] ?? '-'); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($talep['baslik']); ?></strong>
                                                <?php if ($talep['demirbas_adi']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($talep['demirbas_adi']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($talep['baslangic_tarihi']): ?>
                                                    <?php echo date('d.m.Y', strtotime($talep['baslangic_tarihi'])); ?> - 
                                                    <?php echo date('d.m.Y', strtotime($talep['bitis_tarihi'])); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $badgeClass = match($talep['durum']) {
                                                    'onaylandi' => 'success',
                                                    'reddedildi' => 'danger',
                                                    default => 'warning text-dark'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo $badgeClass; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($talep['durum'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <!-- İşlem butonları eklenebilir (onay/red modal vb.) -->
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
    if (!confirm('Bu talebin durumunu ' + status + ' olarak değiştirmek istediğinize emin misiniz?')) return;
    
    // AJAX ile durum güncelleme (api/update_request_status.php gibi bir endpoint gerekebilir)
    // Şimdilik basitçe alert verelim, gerçek implementasyonda API çağrısı yapılmalı
    alert('Durum güncelleme özelliği henüz API tarafında eklenmedi.');
}
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>
