<?php
/**
 * Başkan - Kontrol Paneli
 * Kendi BYK'sının yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Yetki kontrolü
Middleware::requireBaskan();
Middleware::requireModulePermission('baskan_dashboard');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Kontrol Paneli';

// BYK bilgilerini al
$byk = $db->fetch("
    SELECT b.* 
    FROM byk b 
    WHERE b.byk_id = ?
", [$user['byk_id']]);

// İstatistikleri al (sadece kendi BYK'sı için)
$stats = [
    'toplam_uye' => $db->fetch("
        SELECT COUNT(*) as count 
        FROM kullanicilar 
        WHERE byk_id = ? AND aktif = 1 AND rol_id = (SELECT rol_id FROM roller WHERE rol_adi = 'uye')
    ", [$user['byk_id']])['count'],
    'toplam_etkinlik' => $db->fetch("
        SELECT COUNT(*) as count 
        FROM etkinlikler 
        WHERE byk_id = ? AND baslangic_tarihi >= CURDATE()
    ", [$user['byk_id']])['count'],
    'toplam_toplanti' => $db->fetch("
        SELECT COUNT(*) as count 
        FROM toplantilar 
        WHERE byk_id = ? AND durum = 'planlandi'
    ", [$user['byk_id']])['count'],
    'bekleyen_izin' => $db->fetch("
        SELECT COUNT(*) as count 
        FROM izin_talepleri it
        INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
        WHERE k.byk_id = ? AND it.durum = 'beklemede'
    ", [$user['byk_id']])['count'],
    'bekleyen_harcama' => $db->fetch("
        SELECT COUNT(*) as count 
        FROM harcama_talepleri 
        WHERE byk_id = ? AND durum = 'beklemede'
    ", [$user['byk_id']])['count'],
];

// Son aktiviteler (kendi BYK'sı için)
$son_aktiviteler = $db->fetchAll("
    SELECT 
        'toplanti' as tip,
        t.baslik as baslik,
        t.olusturma_tarihi as tarih,
        CONCAT(u.ad, ' ', u.soyad) as kullanici
    FROM toplantilar t
    INNER JOIN kullanicilar u ON t.olusturan_id = u.kullanici_id
    WHERE t.byk_id = ?
    ORDER BY t.olusturma_tarihi DESC
    LIMIT 10
", [$user['byk_id']]);

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>Kontrol Paneli
                    </h1>
                    <small class="text-muted"><?php echo htmlspecialchars($byk['byk_adi']); ?> - Başkan Paneli</small>
                </div>
                <div>
                    <small class="text-muted">Son güncelleme: <?php echo date('d.m.Y H:i'); ?></small>
                </div>
            </div>
            
            <!-- İstatistik Kartları -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Toplam Üye</div>
                                <div class="stat-value"><?php echo $stats['toplam_uye']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Yaklaşan Etkinlikler</div>
                                <div class="stat-value"><?php echo $stats['toplam_etkinlik']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Planlanan Toplantılar</div>
                                <div class="stat-value"><?php echo $stats['toplam_toplanti']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Bekleyen İzin Talepleri</div>
                                <div class="stat-value"><?php echo $stats['bekleyen_izin']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bekleyen İşlemler -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>Bekleyen İzin Talepleri
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="mb-0"><?php echo $stats['bekleyen_izin']; ?></h3>
                                <a href="/baskan/izin-talepleri.php?durum=beklemede" class="btn btn-sm btn-primary">
                                    İncele <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-money-bill-wave text-danger me-2"></i>Bekleyen Harcama Talepleri
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="mb-0"><?php echo $stats['bekleyen_harcama']; ?></h3>
                                <a href="/baskan/harcama-talepleri.php?durum=beklemede" class="btn btn-sm btn-primary">
                                    İncele <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Son Aktiviteler -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clock me-2"></i>Son Aktiviteler
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tip</th>
                                            <th>Başlık</th>
                                            <th>Kullanıcı</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($son_aktiviteler)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Henüz aktivite bulunmamaktadır.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($son_aktiviteler as $aktivite): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?php echo ucfirst($aktivite['tip']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($aktivite['baslik']); ?></td>
                                                    <td><?php echo htmlspecialchars($aktivite['kullanici']); ?></td>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($aktivite['tarih'])); ?></td>
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
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>

