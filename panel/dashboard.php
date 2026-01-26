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

$db = Database::getInstance();
$pageTitle = 'Kontrol Paneli';

// --- Dashboard Preferences ---
require_once __DIR__ . '/../includes/ensure_preferences_table.php';
$prefRow = $db->fetch("SELECT ayar_degeri FROM kullanici_ayarlari WHERE kullanici_id = ? AND ayar_adi = 'dashboard_config'", [$user['id']]);
$dashboardPrefs = $prefRow ? json_decode($prefRow['ayar_degeri'], true) : [];

// Defaults (if not set, show them)
// Defaults (if not set, show them)
$showEtkinlik = $dashboardPrefs['etkinlik'] ?? true;
$showToplanti = $dashboardPrefs['toplanti'] ?? true;
$showIzin = $dashboardPrefs['izin'] ?? true;
$showDuyuru = $dashboardPrefs['duyuru'] ?? true;
$showHarcama = $dashboardPrefs['harcama'] ?? true;




$yaklasan_etkinlikler = [];
$yaklasan_toplantilar = [];
$son_izinler = [];
$son_duyurular = [];
$son_harcamalar = [];
$son_iadeler = [];

$kullanici = [];

// ÜYE VERİLERİ (Artık herkes üye ya da süper admin)
$kullanici = $db->fetch("
    SELECT k.*, b.byk_adi, r.rol_adi
    FROM kullanicilar k
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    INNER JOIN roller r ON k.rol_id = r.rol_id
    WHERE k.kullanici_id = ?
", [$user['id']]);

// Check AT (Headquarters)
$userByk = $db->fetch("SELECT * FROM byk WHERE byk_id = ?", [$user['byk_id']]);
$isAT = ($userByk && $userByk['byk_kodu'] === 'AT');
$eventScope = $_GET['event_scope'] ?? ($isAT ? 'all' : 'my_unit');

// Events Query
$eventWhere = ["e.baslangic_tarihi >= CURDATE()", "e.baslangic_tarihi <= LAST_DAY(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))"];
$eventParams = [];

if (!$isAT || $eventScope === 'my_unit') {
    $eventWhere[] = "e.byk_id = ?";
    $eventParams[] = $user['byk_id'];
}

$yaklasan_etkinlikler = $auth->hasModulePermission('uye_etkinlikler') ? $db->fetchAll("
    SELECT e.*, 
           COALESCE(b.byk_adi, 'Genel') as byk_adi,
           COALESCE(bc.color, b.renk_kodu, '#009872') as byk_renk
    FROM etkinlikler e
    LEFT JOIN byk b ON e.byk_id = b.byk_id
    LEFT JOIN byk_categories bc ON b.byk_kodu = bc.code
    WHERE " . implode(' AND ', $eventWhere) . "
    ORDER BY e.baslangic_tarihi ASC
    LIMIT 5
", $eventParams) : [];



$yaklasan_toplantilar = $auth->hasModulePermission('uye_toplantilar') ? $db->fetchAll("
    SELECT t.*, tk.katilim_durumu
    FROM toplantilar t
    INNER JOIN toplanti_katilimcilar tk ON t.toplanti_id = tk.toplanti_id
    WHERE tk.kullanici_id = ? AND t.toplanti_tarihi >= CURDATE()
    ORDER BY t.toplanti_tarihi ASC
    LIMIT 5
", [$user['id']]) : [];

$son_duyurular = $db->fetchAll("
    SELECT *
    FROM duyurular
    WHERE byk_id = ? AND aktif = 1
    ORDER BY olusturma_tarihi DESC
    LIMIT 5
", [$user['byk_id']]);


$son_harcamalar = ($auth->hasModulePermission('uye_harcama_talepleri') || $auth->hasModulePermission('baskan_harcama_talepleri')) ? $db->fetchAll("
    SELECT *
    FROM harcama_talepleri
    WHERE kullanici_id = ?
    ORDER BY olusturma_tarihi DESC
    LIMIT 5
", [$user['id']]) : [];

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
                        <i class="fas fa-user-circle me-2"></i>Hoş Geldiniz, <?php echo htmlspecialchars($user['name']); ?>
                    </h1>
                </div>
                <div class="d-none d-lg-flex align-items-center gap-3 customize-section">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#dashboardSettingsModal">
                        <i class="fas fa-cog me-1"></i>Özelleştir
                    </button>
                    <small class="text-muted son-guncelleme">Son güncelleme: <?php echo date('d.m.Y H:i'); ?></small>
                </div>
            </div>
            
            <!-- Yaklaşan Etkinlikler ve Toplantılar -->
            <div class="row g-4 mb-4">
                <?php
                // Türkçe ay isimleri
                $aylar = [
                    '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis', '05' => 'May', '06' => 'Haz',
                    '07' => 'Tem', '08' => 'Ağu', '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'
                ];
                ?>
                
                <?php if (($auth->hasModulePermission('uye_etkinlikler') || $auth->hasModulePermission('baskan_etkinlikler')) && $showEtkinlik): ?>
                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div><i class="fas fa-calendar-alt me-2 text-primary"></i>Yaklaşan Etkinlikler</div>
                            <?php if ($isAT): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle py-0" type="button" data-bs-toggle="dropdown">
                                    <?php echo ($eventScope === 'my_unit') ? 'Birimim' : 'Tümü'; ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item <?php echo $eventScope === 'all' ? 'active' : ''; ?>" href="?event_scope=all">Tümü</a></li>
                                    <li><a class="dropdown-item <?php echo $eventScope === 'my_unit' ? 'active' : ''; ?>" href="?event_scope=my_unit">Birimim</a></li>
                                </ul>
                            </div>
                            <?php endif; ?>
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
                                        <?php 
                                            // Etkinlik birimini bul
                                            $birimAdi = $etkinlik['byk_adi'];
                                            $ayKodu = date('m', strtotime($etkinlik['baslangic_tarihi']));
                                            $renk = $etkinlik['byk_renk'] ?? '#009872';
                                        ?>
                                        <div class="list-group-item d-flex align-items-center justify-content-between">

                                            <div class="d-flex align-items-center">
                                                <div class="rounded p-2 text-center me-3 text-white" style="min-width: 50px; background-color: <?php echo $renk; ?>;">
                                                    <div class="fw-bold"><?php echo date('d', strtotime($etkinlik['baslangic_tarihi'])); ?></div>
                                                    <div class="small text-uppercase" style="font-size: 0.65rem; opacity: 0.9;"><?php echo $aylar[$ayKodu] ?? date('M', strtotime($etkinlik['baslangic_tarihi'])); ?></div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark"><?php echo htmlspecialchars($etkinlik['baslik']); ?></h6>
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="fas fa-sitemap me-1" style="font-size: 0.8em; color: <?php echo $renk; ?>;"></i> <?php echo htmlspecialchars($birimAdi); ?>
                                                    </small>
                                                </div>
                                            </div>


                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (($auth->hasModulePermission('uye_toplantilar') || $auth->hasModulePermission('baskan_toplantilar')) && $showToplanti): ?>
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
                                        <?php 
                                            // Toplantı birimini bul
                                            $birimAdi = $kullanici['byk_adi'] ?? 'Genel';
                                            $ayKodu = date('m', strtotime($toplanti['toplanti_tarihi']));
                                        ?>
                                        <div class="list-group-item d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-light rounded p-2 text-center me-3" style="min-width: 50px;">
                                                    <div class="fw-bold text-dark"><?php echo date('d', strtotime($toplanti['toplanti_tarihi'])); ?></div>
                                                    <div class="small text-muted text-uppercase" style="font-size: 0.65rem;"><?php echo $aylar[$ayKodu] ?? date('M', strtotime($toplanti['toplanti_tarihi'])); ?></div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-semibold text-dark"><?php echo htmlspecialchars($toplanti['baslik']); ?></h6>
                                                     <div class="d-flex flex-column gap-1">
                                                        <small class="text-muted">
                                                            <i class="fas fa-sitemap me-1 text-success" style="font-size: 0.8em;"></i> <?php echo htmlspecialchars($birimAdi); ?>
                                                        </small>
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
                                            </div>
                                            <a href="/panel/toplantilar.php?id=<?php echo $toplanti['toplanti_id']; ?>" class="btn btn-sm btn-light rounded-pill px-3">
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


                <?php if ($showDuyuru): ?>
                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-bullhorn me-2 text-info"></i>Son Duyurular
                        </div>
                        <div class="card-body p-0">
                             <?php if (empty($son_duyurular)): ?>
                                <div class="p-4 text-center text-muted small">
                                    <i class="fas fa-bullhorn mb-2 d-block fa-2x opacity-25"></i>
                                    Aktif duyuru bulunmamaktadır.
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($son_duyurular as $duyuru): ?>
                                         <a href="/panel/duyurular.php?id=<?php echo $duyuru['duyuru_id']; ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark"><?php echo htmlspecialchars($duyuru['baslik']); ?></h6>
                                                <small class="text-muted text-truncate d-inline-block" style="max-width:300px; font-size: 0.8em;"><?php echo mb_substr(strip_tags($duyuru['icerik']), 0, 80); ?>...</small>
                                            </div>
                                            <small class="text-muted ms-2" style="white-space:nowrap; font-size:0.7em;"><?php echo date('d.m', strtotime($duyuru['olusturma_tarihi'])); ?></small>
                                         </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            
            <!-- Son İzin Talepleri -->
            <?php if ($auth->hasModulePermission('uye_izin_talepleri') && $showIzin): ?>
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
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                 <!-- Harcama Talepleri -->
                 <?php if (($auth->hasModulePermission('uye_harcama_talepleri') || $auth->hasModulePermission('baskan_harcama_talepleri')) && $showHarcama): ?>
                 <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-wallet me-2 text-danger"></i>Harcama Taleplerim
                            </div>
                            <a href="/panel/uye_harcama-talepleri.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-0 pb-1" style="font-size:0.8rem;">
                                Tümü
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if(empty($son_harcamalar)): ?>
                                    <li class="list-group-item text-center text-muted small py-4">Harcama talebi bulunmamaktadır.</li>
                                <?php else: ?>
                                    <?php foreach($son_harcamalar as $h): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold text-dark"><?php echo number_format($h['tutar'], 2); ?> <?php echo $h['para_birimi']; ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($h['aciklama']); ?></small>
                                            </div>
                                            <?php 
                                            // Status Badge Logic
                                            $stClass = match($h['durum']) { 'onaylandi' => 'success', 'reddedildi' => 'danger', default => 'warning' };
                                            $stText = match($h['durum']) { 'onaylandi' => 'Onaylandı', 'reddedildi' => 'Reddedildi', default => 'Beklemede' };
                                            ?>
                                            <span class="badge bg-<?php echo $stClass; ?> bg-opacity-10 text-<?php echo $stClass; ?> border border-<?php echo $stClass; ?> border-opacity-10 py-1"><?php echo $stText; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                 </div>
                 <?php endif; ?>



            

        </div>
    </main>
</div>

<!-- Dashboard Özelleştirme Modal -->
<div class="modal fade" id="dashboardSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Görünüm Ayarları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Kontrol panelinde görmek istediğiniz alanları seçiniz.</p>
                <form id="dashboardSettingsForm">
                    <div class="list-group">
                        <label class="list-group-item d-flex justify-content-between align-items-center pointer-cursor">
                            <div>
                                <i class="fas fa-calendar-alt text-primary me-2"></i>Yaklaşan Etkinlikler
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="etkinlik" <?php echo $showEtkinlik ? 'checked' : ''; ?>>
                            </div>
                        </label>
                        <label class="list-group-item d-flex justify-content-between align-items-center pointer-cursor">
                            <div>
                                <i class="fas fa-users text-success me-2"></i>Yaklaşan Toplantılar
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="toplanti" <?php echo $showToplanti ? 'checked' : ''; ?>>
                            </div>
                        </label>
                        <label class="list-group-item d-flex justify-content-between align-items-center pointer-cursor">
                            <div>
                                <i class="fas fa-calendar-check text-warning me-2"></i>Son İzin Talepleri
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="izin" <?php echo $showIzin ? 'checked' : ''; ?>>
                            </div>
                        </label>
                        <label class="list-group-item d-flex justify-content-between align-items-center pointer-cursor">
                            <div>
                                <i class="fas fa-bullhorn text-info me-2"></i>Son Duyurular
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="duyuru" <?php echo $showDuyuru ? 'checked' : ''; ?>>
                            </div>
                        </label>
                        <label class="list-group-item d-flex justify-content-between align-items-center pointer-cursor">
                            <div>
                                <i class="fas fa-wallet text-danger me-2"></i>Harcama Talepleri
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="harcama" <?php echo $showHarcama ? 'checked' : ''; ?>>
                            </div>
                        </label>
                        </label>

                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="saveDashboardSettings">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('saveDashboardSettings').addEventListener('click', function() {
    const form = document.getElementById('dashboardSettingsForm');
    const formData = new FormData(form);
    
    // Checkbox mapping
    const prefs = {
        etkinlik: formData.has('etkinlik'),
        toplanti: formData.has('toplanti'),
        izin: formData.has('izin'),
        duyuru: formData.has('duyuru'),
        harcama: formData.has('harcama')
    };

    
    const btn = this;
    const oldText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch('/api/save_dashboard_prefs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ widgets: prefs })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
            btn.disabled = false;
            btn.innerHTML = oldText;
        }
    })
    .catch(err => {
        console.error(err);
        alert('Bir hata oluştu.');
        btn.disabled = false;
        btn.innerHTML = oldText;
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
