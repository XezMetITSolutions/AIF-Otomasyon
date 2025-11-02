<?php
/**
 * Ana Yönetici - Raporlar & Analiz
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Raporlar & Analiz';

// İstatistikler
$stats = [
    'toplam_kullanici' => $db->fetch("SELECT COUNT(*) as count FROM kullanicilar WHERE aktif = 1")['count'],
    'toplam_byk' => $db->fetch("SELECT COUNT(*) as count FROM byk WHERE aktif = 1")['count'],
    'toplam_etkinlik' => $db->fetch("SELECT COUNT(*) as count FROM etkinlikler")['count'],
    'toplam_toplanti' => $db->fetch("SELECT COUNT(*) as count FROM toplantilar")['count'],
    'toplam_proje' => $db->fetch("SELECT COUNT(*) as count FROM projeler")['count'],
    'onaylanan_izin' => $db->fetch("SELECT COUNT(*) as count FROM izin_talepleri WHERE durum = 'onaylandi'")['count'],
    'bekleyen_izin' => $db->fetch("SELECT COUNT(*) as count FROM izin_talepleri WHERE durum = 'beklemede'")['count'],
];

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Raporlar & Analiz
                </h1>
                <div>
                    <button class="btn btn-sm btn-success" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Yazdır
                    </button>
                </div>
            </div>
            
            <!-- İstatistik Kartları -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Toplam Kullanıcı</div>
                                <div class="stat-value"><?php echo $stats['toplam_kullanici']; ?></div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Toplam BYK</div>
                                <div class="stat-value"><?php echo $stats['toplam_byk']; ?></div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-building"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Toplam Etkinlik</div>
                                <div class="stat-value"><?php echo $stats['toplam_etkinlik']; ?></div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-calendar"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Toplam Toplantı</div>
                                <div class="stat-value"><?php echo $stats['toplam_toplanti']; ?></div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-users-cog"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line me-2"></i>Genel İstatistikler
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Kullanıcı Dağılımı</h5>
                            <p>Toplam Kullanıcı: <strong><?php echo $stats['toplam_kullanici']; ?></strong></p>
                            <p>Toplam BYK: <strong><?php echo $stats['toplam_byk']; ?></strong></p>
                            <p>Toplam Proje: <strong><?php echo $stats['toplam_proje']; ?></strong></p>
                        </div>
                        <div class="col-md-6">
                            <h5>İzin Talepleri</h5>
                            <p>Onaylanan İzinler: <strong class="text-success"><?php echo $stats['onaylanan_izin']; ?></strong></p>
                            <p>Bekleyen İzinler: <strong class="text-warning"><?php echo $stats['bekleyen_izin']; ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>

