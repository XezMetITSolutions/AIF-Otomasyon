<?php
/**
 * Ana Yönetici - Harcama Talepleri Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Harcama Talepleri';

// Filtre
$durum = $_GET['durum'] ?? '';

$where = [];
$params = [];

if ($durum) {
    $where[] = "ht.durum = ?";
    $params[] = $durum;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Harcama talepleri
$harcamaTalepleri = $db->fetchAll("
    SELECT ht.*, CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, b.byk_adi
    FROM harcama_talepleri ht
    INNER JOIN kullanicilar k ON ht.kullanici_id = k.kullanici_id
    INNER JOIN byk b ON ht.byk_id = b.byk_id
    $whereClause
    ORDER BY ht.olusturma_tarihi DESC
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
                    <i class="fas fa-money-bill-wave me-2"></i>Harcama Talepleri
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
                    Toplam: <strong><?php echo count($harcamaTalepleri); ?></strong> harcama talebi
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Başlık</th>
                                    <th>Kullanıcı</th>
                                    <th>BYK</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($harcamaTalepleri)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Henüz harcama talebi bulunmamaktadır.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($harcamaTalepleri as $talep): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($talep['baslik']); ?></td>
                                            <td><?php echo htmlspecialchars($talep['kullanici_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($talep['byk_adi']); ?></td>
                                            <td><strong><?php echo number_format($talep['tutar'], 2, ',', '.'); ?> TL</strong></td>
                                            <td>
                                                <?php 
                                                    $statusClass = 'warning';
                                                    $statusText = htmlspecialchars($talep['durum']);
                                                    
                                                    if ($talep['durum'] === 'onaylandi') {
                                                        $statusClass = 'success';
                                                    } elseif ($talep['durum'] === 'reddedildi') {
                                                        $statusClass = 'danger';
                                                    } elseif ($talep['durum'] === 'beklemede') {
                                                        $stage = $talep['onay_asamasi'] ?? 1;
                                                        if ($stage == 1) $statusText .= ' (Birim)';
                                                        if ($stage == 2) $statusText .= ' (AT)';
                                                    }
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($talep['olusturma_tarihi'])); ?></td>
                                            <td>
                                                <a href="/admin/harcama-detay.php?id=<?php echo $talep['talep_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
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

