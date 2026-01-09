<?php
/**
 * Kontrol Paneli (Başkan ve Üye)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$user = $auth->getUser();

if (!$user) {
    header('Location: /index.php');
    exit;
}

// Redirect super_admin
if ($user['role'] === 'super_admin') {
    header('Location: /admin/dashboard.php');
    exit;
}

$isBaskan = $user['role'] === 'uye';
$isUye = $user['role'] === 'uye';

if (!$isBaskan && !$isUye) {
    header('Location: /access-denied.php');
    exit;
}

// Only require specific dashboard permission if strict mode is on, 
// but generally roles imply dashboard access.
// Middleware::requireModulePermission($isBaskan ? 'baskan_dashboard' : 'uye_dashboard');

$db = Database::getInstance();
$pageTitle = 'Kontrol Paneli';

$stats = [];
$son_aktiviteler = [];
$yaklasan_etkinlikler = [];
$yaklasan_toplantilar = [];
$son_izinler = [];
$byk = [];
$kullanici = [];

if ($isBaskan) {
    // BAŞKAN VERİLERİ
    $byk = $db->fetch("SELECT b.* FROM byk b WHERE b.byk_id = ?", [$user['byk_id']]);

    $stats['toplam_uye'] = $db->fetch("
        SELECT COUNT(*) as count 
        FROM kullanicilar 
        WHERE byk_id = ? AND aktif = 1 AND rol_id = (SELECT rol_id FROM roller WHERE rol_adi = 'uye')
    ", [$user['byk_id']])['count'];

    $stats['toplam_etkinlik'] = $auth->hasModulePermission('baskan_etkinlikler') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM etkinlikler 
        WHERE byk_id = ? AND baslangic_tarihi >= CURDATE() 
        AND baslangic_tarihi <= LAST_DAY(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
    ", [$user['byk_id']])['count'] : 0;

    $stats['toplam_toplanti'] = $auth->hasModulePermission('baskan_toplantilar') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM toplantilar 
        WHERE byk_id = ? AND durum = 'planlandi'
    ", [$user['byk_id']])['count'] : 0;

    $stats['bekleyen_izin'] = $auth->hasModulePermission('baskan_izin_talepleri') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM izin_talepleri it
        INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
        WHERE k.byk_id = ? AND it.durum = 'beklemede'
    ", [$user['byk_id']])['count'] : 0;

    $stats['bekleyen_harcama'] = $auth->hasModulePermission('baskan_harcama_talepleri') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM harcama_talepleri 
        WHERE byk_id = ? AND durum = 'beklemede'
    ", [$user['byk_id']])['count'] : 0;

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

} else {
    // ÜYE VERİLERİ
    $kullanici = $db->fetch("
        SELECT k.*, b.byk_adi, r.rol_adi
        FROM kullanicilar k
        LEFT JOIN byk b ON k.byk_id = b.byk_id
        INNER JOIN roller r ON k.rol_id = r.rol_id
        WHERE k.kullanici_id = ?
    ", [$user['id']]);

    $stats['aktif_izin'] = $auth->hasModulePermission('uye_izin_talepleri') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM izin_talepleri 
        WHERE kullanici_id = ? AND durum = 'onaylandi' 
        AND baslangic_tarihi <= CURDATE() AND bitis_tarihi >= CURDATE()
    ", [$user['id']])['count'] : 0;

    $stats['bekleyen_izin'] = $auth->hasModulePermission('uye_izin_talepleri') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM izin_talepleri 
        WHERE kullanici_id = ? AND durum = 'beklemede'
    ", [$user['id']])['count'] : 0;

    $stats['yaklasan_etkinlik'] = $auth->hasModulePermission('uye_etkinlikler') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM etkinlikler 
        WHERE byk_id = ? AND baslangic_tarihi >= CURDATE() 
        AND baslangic_tarihi <= LAST_DAY(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
    ", [$user['byk_id']])['count'] : 0;

    $stats['yaklasan_toplanti'] = $auth->hasModulePermission('uye_toplantilar') ? $db->fetch("
        SELECT COUNT(*) as count 
        FROM toplanti_katilimcilar tk
        INNER JOIN toplantilar t ON tk.toplanti_id = t.toplanti_id
        WHERE tk.kullanici_id = ? AND t.toplanti_tarihi >= CURDATE()
        AND t.toplanti_tarihi <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND tk.katilim_durumu = 'katilacak'
    ", [$user['id']])['count'] : 0;

    $yaklasan_etkinlikler = $auth->hasModulePermission('uye_etkinlikler') ? $db->fetchAll("
        SELECT e.*
        FROM etkinlikler e
        WHERE e.byk_id = ? AND e.baslangic_tarihi >= CURDATE()
        AND e.baslangic_tarihi <= LAST_DAY(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 5
    ", [$user['byk_id']]) : [];

    $yaklasan_toplantilar = $auth->hasModulePermission('uye_toplantilar') ? $db->fetchAll("
        SELECT t.*, tk.katilim_durumu
        FROM toplantilar t
        INNER JOIN toplanti_katilimcilar tk ON t.toplanti_id = tk.toplanti_id
        WHERE tk.kullanici_id = ? AND t.toplanti_tarihi >= CURDATE()
        ORDER BY t.toplanti_tarihi ASC
        LIMIT 5
    ", [$user['id']]) : [];

    $son_izinler = $auth->hasModulePermission('uye_izin_talepleri') ? $db->fetchAll("
        SELECT *
        FROM izin_talepleri
        WHERE kullanici_id = ?
        ORDER BY olusturma_tarihi DESC
        LIMIT 5
    ", [$user['id']]) : [];
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    :root {
        --primary: #009872;
        --primary-light: rgba(0, 152, 114, 0.1);
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --card-bg: rgba(255, 255, 255, 0.9);
        --glass-border: 1px solid rgba(255, 255, 255, 0.5);
    }

    body {
        font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at 0% 0%, rgba(0, 152, 114, 0.08) 0%, transparent 50%),
                    radial-gradient(circle at 100% 100%, rgba(0, 152, 114, 0.05) 0%, transparent 50%),
                    #f8fafc;
        color: var(--text-dark);
    }

    /* Glass Cards */
    .card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border: var(--glass-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border-radius: 1rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }

    .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1.25rem 1.5rem;
        font-size: 1rem;
        color: var(--text-dark);
    }

    .dashboard-layout {
        display: block;
    }

    .sidebar-wrapper {
        display: none;
    }

    .content-wrapper {
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
            padding: 1.5rem 2rem !important;
            max-width: 1400px !important;
            margin: 0 auto !important;
        }
    }
</style>

<div class="dashboard-layout">
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <main class="main-content">
        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <?php if ($isBaskan): ?>
                            <i class="fas fa-tachometer-alt me-2"></i>Kontrol Paneli
                        <?php else: ?>
                            <i class="fas fa-user-circle me-2"></i>Hoş Geldiniz, <?php echo htmlspecialchars($user['name']); ?>
                        <?php endif; ?>
                    </h1>
                    <small class="text-muted">
                        <?php if ($isBaskan): ?>
                            <?php echo htmlspecialchars($byk['byk_adi']); ?> - Başkan Paneli
                        <?php else: ?>
                            <?php echo htmlspecialchars($kullanici['byk_adi'] ?? 'BYK'); ?> - Üye Paneli
                        <?php endif; ?>
                    </small>
                </div>
                <div>
                    <small class="text-muted">Son güncelleme: <?php echo date('d.m.Y H:i'); ?></small>
                </div>
            </div>
            
            <?php if ($isBaskan): ?>
                <!-- BAŞKAN GÖRÜNÜMÜ -->
                
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
                    
                    <?php if ($auth->hasModulePermission('baskan_etkinlikler')): ?>
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
                    <?php endif; ?>
                    
                    <?php if ($auth->hasModulePermission('baskan_toplantilar')): ?>
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
                    <?php endif; ?>
                    
                    <?php if ($auth->hasModulePermission('baskan_izin_talepleri')): ?>
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
                    <?php endif; ?>
                </div>
                
                <!-- Bekleyen İşlemler -->
                <div class="row mb-4">
                    <?php if ($auth->hasModulePermission('baskan_izin_talepleri')): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>Bekleyen İzin Talepleri
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="mb-0"><?php echo $stats['bekleyen_izin']; ?></h3>
                                    <a href="/panel/baskan_izin-talepleri.php?durum=beklemede" class="btn btn-sm btn-primary">
                                        İncele <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasModulePermission('baskan_harcama_talepleri')): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-money-bill-wave text-danger me-2"></i>Bekleyen Harcama Talepleri
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="mb-0"><?php echo $stats['bekleyen_harcama']; ?></h3>
                                    <a href="/panel/baskan_harcama-talepleri.php?durum=beklemede" class="btn btn-sm btn-primary">
                                        İncele <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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

            <?php else: ?>
                <!-- ÜYE GÖRÜNÜMÜ -->

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
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
