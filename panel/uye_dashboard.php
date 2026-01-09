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

<style>
    /* CSS Overrides for Dashboard */
    
    /* Reset layout for container */
    .dashboard-layout {
        display: block; /* Default to block for mobile */
        /* margin-top: 56px; removed because body has padding-top: 56px in style.css */
    }

    .sidebar-wrapper {
        display: none; /* Hide custom wrapper on mobile */
    }

    /* Fix Content Width and Spacing */
    .content-wrapper {
        /* Override style.css global styles that cause squashing */
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        padding: 1rem !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .main-content {
        width: 100%;
    }

    /* Desktop View */
    @media (min-width: 992px) {
        .dashboard-layout {
            display: flex;
            flex-direction: row;
        }

        .sidebar-wrapper {
            display: block;
            width: 250px;
            flex-shrink: 0;
            z-index: 1000;
        }
        
        .main-content {
            flex-grow: 1;
            width: auto;
        }

        .content-wrapper {
            /* On desktop, we want comfortable padding but full width of the main area */
            padding: 1.5rem 2rem !important;
            max-width: 1400px !important; /* Cap width on very large screens */
            margin: 0 auto !important; /* Center content */
        }
    }
</style>

<div class="dashboard-layout">
    <!-- Sidebar Wrapper -->
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <main class="main-content">
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
        <div class="row g-4 mb-4">
            <?php if ($auth->hasModulePermission('uye_izin_talepleri')): ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card primary">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Aktif İzin</div>
                            <div class="stat-value"><?php echo $stats['aktif_izin']; ?></div>
                        </div>
                        <div class="stat-icon-wrapper">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card warning">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Bekleyen İzin</div>
                            <div class="stat-value"><?php echo $stats['bekleyen_izin']; ?></div>
                        </div>
                        <div class="stat-icon-wrapper">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($auth->hasModulePermission('uye_etkinlikler')): ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card info">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Yaklaşan Etkinlikler</div>
                            <div class="stat-value"><?php echo $stats['yaklasan_etkinlik']; ?></div>
                        </div>
                        <div class="stat-icon-wrapper">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($auth->hasModulePermission('uye_toplantilar')): ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card success">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Yaklaşan Toplantılar</div>
                            <div class="stat-value"><?php echo $stats['yaklasan_toplanti']; ?></div>
                        </div>
                        <div class="stat-icon-wrapper">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Yaklaşan Etkinlikler ve Toplantılar -->
        <div class="row g-4 mb-4">
            <?php if ($auth->hasModulePermission('uye_etkinlikler')): ?>
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>Yaklaşan Etkinlikler
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($yaklasan_etkinlikler)): ?>
                            <div class="p-4 text-center text-muted small">
                                <i class="far fa-calendar-times mb-2 d-block fa-2x opacity-25"></i>
                                Yaklaşan etkinlik bulunmamaktadır.
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($yaklasan_etkinlikler as $etkinlik): ?>
                                    <div class="list-group-item d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 text-center me-3" style="min-width: 50px;">
                                                <div class="fw-bold text-dark"><?php echo date('d', strtotime($etkinlik['baslangic_tarihi'])); ?></div>
                                                <div class="small text-muted text-uppercase" style="font-size: 0.65rem;"><?php echo date('M', strtotime($etkinlik['baslangic_tarihi'])); ?></div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark"><?php echo htmlspecialchars($etkinlik['baslik']); ?></h6>
                                                <!-- Time removed as per request -->
                                            </div>
                                        </div>
                                        <a href="/panel/uye_etkinlikler.php?id=<?php echo $etkinlik['etkinlik_id']; ?>" class="btn btn-sm btn-light rounded-pill px-3">
                                            Detay
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($auth->hasModulePermission('uye_toplantilar')): ?>
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-users me-2 text-success"></i>Yaklaşan Toplantılar
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($yaklasan_toplantilar)): ?>
                            <div class="p-4 text-center text-muted small">
                                <i class="fas fa-users-slash mb-2 d-block fa-2x opacity-25"></i>
                                Yaklaşan toplantı bulunmamaktadır.
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($yaklasan_toplantilar as $toplanti): ?>
                                    <div class="list-group-item d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 text-center me-3" style="min-width: 50px;">
                                                <div class="fw-bold text-dark"><?php echo date('d', strtotime($toplanti['toplanti_tarihi'])); ?></div>
                                                <div class="small text-muted text-uppercase" style="font-size: 0.65rem;"><?php echo date('M', strtotime($toplanti['toplanti_tarihi'])); ?></div>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-semibold text-dark"><?php echo htmlspecialchars($toplanti['baslik']); ?></h6>
                                                <div class="d-flex gap-2">
                                                    <span class="badge bg-light text-dark border fw-normal py-1">
                                                        <i class="far fa-clock me-1 small"></i><?php echo date('H:i', strtotime($toplanti['toplanti_tarihi'])); ?>
                                                    </span>
                                                    <?php 
                                                    $statusClass = match($toplanti['katilim_durumu']) {
                                                        'katilacak' => 'success',
                                                        'katilmayacak' => 'danger',
                                                        'mazeret' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                    $statusText = match($toplanti['katilim_durumu']) {
                                                        'katilacak' => 'Katılacak',
                                                        'katilmayacak' => 'Katılmayacak',
                                                        'mazeret' => 'Mazeretli',
                                                        default => 'Beklemede'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?> bg-opacity-10 text-<?php echo $statusClass; ?> border border-<?php echo $statusClass; ?> border-opacity-10 py-1">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/panel/uye_toplantilar.php?id=<?php echo $toplanti['toplanti_id']; ?>" class="btn btn-sm btn-light rounded-pill px-3">
                                            Detay
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-calendar-check me-2 text-warning"></i>Son İzin Talepleri
                        </div>
                        <a href="/panel/uye_izin-talepleri.php" class="btn btn-sm btn-primary rounded-pill px-3">
                            Yeni Talep <i class="fas fa-plus ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 px-4 py-3 fw-semibold text-muted small text-uppercase">Tarih</th>
                                        <th class="border-0 px-4 py-3 fw-semibold text-muted small text-uppercase">İzin Nedeni</th>
                                        <th class="border-0 px-4 py-3 fw-semibold text-muted small text-uppercase">Durum</th>
                                        <th class="border-0 px-4 py-3 fw-semibold text-muted small text-uppercase text-end">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($son_izinler)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-5">
                                                <i class="fas fa-inbox fa-2x opacity-25 mb-3 d-block"></i>
                                                Henüz izin talebi bulunmamaktadır.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($son_izinler as $izin): ?>
                                            <tr>
                                                <td class="px-4">
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-medium text-dark"><?php echo date('d.m.Y', strtotime($izin['baslangic_tarihi'])); ?></span>
                                                        <span class="small text-muted"><?php echo date('d.m.Y', strtotime($izin['bitis_tarihi'])); ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-4 text-muted"><?php echo htmlspecialchars($izin['izin_nedeni'] ?? '-'); ?></td>
                                                <td class="px-4">
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
                                                    <span class="badge bg-<?php echo $renk; ?> bg-opacity-10 text-<?php echo $renk; ?> border border-<?php echo $renk; ?> border-opacity-10 px-3 py-2 rounded-pill">
                                                        <?php echo $durumText[$izin['durum']] ?? $izin['durum']; ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 text-end">
                                                    <a href="/panel/uye_izin-talepleri.php?id=<?php echo $izin['izin_id']; ?>" class="btn btn-sm btn-light rounded-pill px-3">
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
</div>

<?php
include __DIR__ . '/../includes/footer.php';
?>


