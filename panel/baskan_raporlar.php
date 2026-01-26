<?php
/**
 * Başkan - Raporlar
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';


Middleware::requireModulePermission('baskan_raporlar');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Raporlar';

$toplamUye = $db->fetch("SELECT COUNT(*) AS c FROM kullanicilar WHERE byk_id = ?", [$user['byk_id']])['c'];
$toplamEtkinlik = $db->fetch("SELECT COUNT(*) AS c FROM etkinlikler WHERE byk_id = ?", [$user['byk_id']])['c'];
$toplamToplanti = $db->fetch("SELECT COUNT(*) AS c FROM toplantilar WHERE byk_id = ?", [$user['byk_id']])['c'];
$toplamHarcama = $db->fetch("SELECT COALESCE(SUM(tutar),0) AS toplam FROM harcama_talepleri WHERE byk_id = ? AND durum = 'onaylandi'", [$user['byk_id']])['toplam'];

$izinDurum = $db->fetchAll("
    SELECT durum, COUNT(*) AS adet
    FROM izin_talepleri it
    INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
    WHERE k.byk_id = ?
    GROUP BY durum
", [$user['byk_id']]);

$harcamaDurum = $db->fetchAll("
    SELECT durum, COUNT(*) AS adet
    FROM harcama_talepleri
    WHERE byk_id = ?
    GROUP BY durum
", [$user['byk_id']]);

$aylikHarcama = $db->fetchAll("
    SELECT DATE_FORMAT(olusturma_tarihi, '%Y-%m') AS ay, SUM(tutar) AS toplam
    FROM harcama_talepleri
    WHERE byk_id = ? AND durum IN ('onaylandi', 'odenmistir')
    GROUP BY ay
    ORDER BY ay ASC
    LIMIT 12
", [$user['byk_id']]);

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-chart-pie me-2"></i>Raporlar
                </h1>
                <p class="text-muted mb-0">BYK’nızın aktivite, izin ve harcama özetleri.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div>
                        <div class="stat-label">Üye Sayısı</div>
                        <div class="stat-value"><?php echo $toplamUye; ?></div>
                    </div>
                    <i class="stat-icon fas fa-user-friends"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card success">
                    <div>
                        <div class="stat-label">Toplam Etkinlik</div>
                        <div class="stat-value"><?php echo $toplamEtkinlik; ?></div>
                    </div>
                    <i class="stat-icon fas fa-calendar-alt"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card info">
                    <div>
                        <div class="stat-label">Toplantılar</div>
                        <div class="stat-value"><?php echo $toplamToplanti; ?></div>
                    </div>
                    <i class="stat-icon fas fa-handshake"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card warning">
                    <div>
                        <div class="stat-label">Onaylı Harcamalar</div>
                        <div class="stat-value"><?php echo number_format($toplamHarcama, 2, ',', '.'); ?> €</div>
                    </div>
                    <i class="stat-icon fas fa-euro-sign"></i>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-user-clock me-2"></i>İzin Talepleri Dağılımı
                    </div>
                    <div class="card-body">
                        <?php if (empty($izinDurum)): ?>
                            <p class="text-muted mb-0">Henüz izin talebi bulunmuyor.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($izinDurum as $row): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span class="text-capitalize"><?php echo $row['durum']; ?></span>
                                        <span class="badge bg-light text-dark"><?php echo $row['adet']; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-money-bill-wave me-2"></i>Harcama Talepleri Dağılımı
                    </div>
                    <div class="card-body">
                        <?php if (empty($harcamaDurum)): ?>
                            <p class="text-muted mb-0">Henüz harcama talebi bulunmuyor.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($harcamaDurum as $row): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span class="text-capitalize"><?php echo $row['durum']; ?></span>
                                        <span class="badge bg-light text-dark"><?php echo $row['adet']; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-chart-line me-2"></i>Aylık Onaylanan Harcamalar</span>
                <span class="text-muted small">Son 12 ay</span>
            </div>
            <div class="card-body">
                <?php if (empty($aylikHarcama)): ?>
                    <p class="text-muted mb-0">Henüz onaylanan harcama verisi yok.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Ay</th>
                                    <th>Tutar (€)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aylikHarcama as $row): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($row['ay'] . '-01')); ?></td>
                                        <td><?php echo number_format($row['toplam'], 2, ',', '.'); ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>


