<?php
/**
 * Üye - Kişisel Kontrol Paneli
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Yetki kontrolü
Middleware::requireUye();
Middleware::requireModulePermission('uye_dashboard');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Kontrol Paneli';

// Kullanıcı bilgilerini al
$kullanici = $db->fetch("
    SELECT k.*, b.byk_adi, r.rol_adi
    FROM kullanicilar k
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    INNER JOIN roller r ON k.rol_id = r.rol_id
    WHERE k.kullanici_id = ?
", [$user['id']]);

// İstatistikleri al (kişisel)
$stats = [
    'aktif_izin' => $auth->hasModulePermission('uye_izin_talepleri') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM izin_talepleri 
        WHERE kullanici_id = ? AND durum = 'onaylandi' 
        AND baslangic_tarihi <= CURDATE() AND bitis_tarihi >= CURDATE()
    ", [$user['id']])['count'] : 0,
    'bekleyen_izin' => $auth->hasModulePermission('uye_izin_talepleri') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM izin_talepleri 
        WHERE kullanici_id = ? AND durum = 'beklemede'
    ", [$user['id']])['count'] : 0,
    'yaklasan_etkinlik' => $auth->hasModulePermission('uye_etkinlikler') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM etkinlikler 
        WHERE byk_id = ? AND baslangic_tarihi >= CURDATE() 
        AND baslangic_tarihi <= LAST_DAY(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
    ", [$user['byk_id']])['count'] : 0,
    'yaklasan_toplanti' => $auth->hasModulePermission('uye_toplantilar') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM toplanti_katilimcilar tk
        INNER JOIN toplantilar t ON tk.toplanti_id = t.toplanti_id
        WHERE tk.kullanici_id = ? AND t.toplanti_tarihi >= CURDATE()
        AND t.toplanti_tarihi <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND tk.katilim_durumu = 'katilacak'
    ", [$user['id']])['count'] : 0,
];

// Yaklaşan etkinlikler
$yaklasan_etkinlikler = $auth->hasModulePermission('uye_etkinlikler') ? $db->fetchAll("
    SELECT e.*
    FROM etkinlikler e
    WHERE e.byk_id = ? AND e.baslangic_tarihi >= CURDATE()
    AND e.baslangic_tarihi <= LAST_DAY(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
    ORDER BY e.baslangic_tarihi ASC
    LIMIT 5
", [$user['byk_id']]) : [];

// Yaklaşan toplantılar
$yaklasan_toplantilar = $auth->hasModulePermission('uye_toplantilar') ? $db->fetchAll("
    SELECT t.*, tk.katilim_durumu
    FROM toplantilar t
    INNER JOIN toplanti_katilimcilar tk ON t.toplanti_id = tk.toplanti_id
    WHERE tk.kullanici_id = ? AND t.toplanti_tarihi >= CURDATE()
    ORDER BY t.toplanti_tarihi ASC
    LIMIT 5
", [$user['id']]) : [];

// Son izin talepleri
$son_izinler = $auth->hasModulePermission('uye_izin_talepleri') ? $db->fetchAll("
    SELECT *
    FROM izin_talepleri
    WHERE kullanici_id = ?
    ORDER BY olusturma_tarihi DESC
    LIMIT 5
", [$user['id']]) : [];

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-user-circle me-2"></i>Hoş Geldiniz, <?php echo htmlspecialchars($user['name']); ?>
                </h1>
                <small class="text-muted"><?php echo htmlspecialchars($kullanici['byk_adi'] ?? 'BYK'); ?> - Üye Paneli</small>
            </div>
        </div>
        
        <!-- İstatistik Kartları -->
        <div class="row mb-4">
            <?php if ($auth->hasModulePermission('uye_izin_talepleri')): ?>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Aktif İzin</div>
                            <div class="stat-value"><?php echo $stats['aktif_izin']; ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Bekleyen İzin Talebi</div>
                            <div class="stat-value"><?php echo $stats['bekleyen_izin']; ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($auth->hasModulePermission('uye_etkinlikler')): ?>
            <div class="col-md-3 mb-3">
                <div class="card stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Yaklaşan Etkinlikler</div>
                            <div class="stat-value"><?php echo $stats['yaklasan_etkinlik']; ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($auth->hasModulePermission('uye_toplantilar')): ?>
            <div class="col-md-3 mb-3">
                <div class="card stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Yaklaşan Toplantılar</div>
                            <div class="stat-value"><?php echo $stats['yaklasan_toplanti']; ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Yaklaşan Etkinlikler ve Toplantılar -->
        <div class="row mb-4">
            <?php if ($auth->hasModulePermission('uye_etkinlikler')): ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-calendar me-2"></i>Yaklaşan Etkinlikler
                    </div>
                    <div class="card-body">
                        <?php if (empty($yaklasan_etkinlikler)): ?>
                            <p class="text-muted text-center">Yaklaşan etkinlik bulunmamaktadır.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($yaklasan_etkinlikler as $etkinlik): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars($etkinlik['baslik']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('d.m.Y', strtotime($etkinlik['baslangic_tarihi'])); ?>
                                                </small>
                                            </div>
                                            <a href="/uye/etkinlikler.php?id=<?php echo $etkinlik['etkinlik_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                Detay
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($auth->hasModulePermission('uye_toplantilar')): ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users-cog me-2"></i>Yaklaşan Toplantılar
                    </div>
                    <div class="card-body">
                        <?php if (empty($yaklasan_toplantilar)): ?>
                            <p class="text-muted text-center">Yaklaşan toplantı bulunmamaktadır.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($yaklasan_toplantilar as $toplanti): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($toplanti['baslik']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi'])); ?>
                                                    <br>
                                                    <span class="badge bg-<?php echo $toplanti['katilim_durumu'] === 'katilacak' ? 'success' : ($toplanti['katilim_durumu'] === 'katilmayacak' ? 'danger' : 'warning'); ?>">
                                                        <?php 
                                                        echo $toplanti['katilim_durumu'] === 'katilacak' ? 'Katılacak' : 
                                                            ($toplanti['katilim_durumu'] === 'katilmayacak' ? 'Katılmayacak' : 'Beklemede');
                                                        ?>
                                                    </span>
                                                </small>
                                            </div>
                                            <a href="/uye/toplantilar.php?id=<?php echo $toplanti['toplanti_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                Detay
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Son İzin Talepleri -->
        <?php if ($auth->hasModulePermission('uye_izin_talepleri')): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-calendar-check me-2"></i>Son İzin Talepleri
                        </div>
                        <a href="/uye/izin-talepleri.php" class="btn btn-sm btn-primary">
                            Yeni İzin Talebi <i class="fas fa-plus ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Başlangıç Tarihi</th>
                                        <th>Bitiş Tarihi</th>
                                        <th>İzin Nedeni</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($son_izinler)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Henüz izin talebi bulunmamaktadır.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($son_izinler as $izin): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y', strtotime($izin['baslangic_tarihi'])); ?></td>
                                                <td><?php echo date('d.m.Y', strtotime($izin['bitis_tarihi'])); ?></td>
                                                <td><?php echo htmlspecialchars($izin['izin_nedeni'] ?? '-'); ?></td>
                                                <td>
                                                    <?php
                                                    $durumRenk = [
                                                        'beklemede' => 'warning',
                                                        'onaylandi' => 'success',
                                                        'reddedildi' => 'danger'
                                                    ];
                                                    $renk = $durumRenk[$izin['durum']] ?? 'secondary';
                                                    $durumText = [
                                                        'beklemede' => 'Beklemede',
                                                        'onaylandi' => 'Onaylandı',
                                                        'reddedildi' => 'Reddedildi'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $renk; ?>">
                                                        <?php echo $durumText[$izin['durum']] ?? $izin['durum']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="/uye/izin-talepleri.php?id=<?php echo $izin['izin_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        Detay
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
        <?php endif; ?>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>


