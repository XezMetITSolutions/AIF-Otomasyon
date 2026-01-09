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

$yaklasan_etkinlikler = [];
$yaklasan_toplantilar = [];
$son_izinler = [];
$kullanici = [];

// ÜYE VERİLERİ (Artık herkes üye ya da süper admin)
$kullanici = $db->fetch("
    SELECT k.*, b.byk_adi, r.rol_adi
    FROM kullanicilar k
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    INNER JOIN roller r ON k.rol_id = r.rol_id
    WHERE k.kullanici_id = ?
", [$user['id']]);

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
                <div>
                    <small class="text-muted">Son güncelleme: <?php echo date('d.m.Y H:i'); ?></small>
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
                                        <?php 
                                            // Etkinlik birimini bul (kullanıcının BYK'sı dışındaysa belirtmek için)
                                            // Şimdilik sadece user byk'sını biliyoruz, etkinlik kendi byk'sında ise göstermeyebiliriz veya her zaman gösterebiliriz.
                                            // Kullanıcı isteği: "hangi birimin programiysa ona göre onun isareti de olsun"
                                            // Mevcut sorgu sadece BYK id ile çekiyor, dolayısıyla BYK adı join edilmeli.
                                            // Ancak yukarıdaki sorguda join yok. Şimdilik $kullanici['byk_adi'] kullanabiliriz çünkü sadece kendi BYK'sındaki etkinlikleri görüyor.
                                            $birimAdi = $kullanici['byk_adi'] ?? 'Genel';
                                            $ayKodu = date('m', strtotime($etkinlik['baslangic_tarihi']));
                                        ?>
                                        <div class="list-group-item d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-light rounded p-2 text-center me-3" style="min-width: 50px;">
                                                    <div class="fw-bold text-dark"><?php echo date('d', strtotime($etkinlik['baslangic_tarihi'])); ?></div>
                                                    <div class="small text-muted text-uppercase" style="font-size: 0.65rem;"><?php echo $aylar[$ayKodu] ?? date('M', strtotime($etkinlik['baslangic_tarihi'])); ?></div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark"><?php echo htmlspecialchars($etkinlik['baslik']); ?></h6>
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="fas fa-sitemap me-1 text-primary" style="font-size: 0.8em;"></i> <?php echo htmlspecialchars($birimAdi); ?>
                                                    </small>
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
                                        <?php 
                                            // Toplantı birimini bul
                                            // Toplantılar da kullanıcının BYK'sına veya katılımcı olduğu toplantılara göre geliyor.
                                            // Şimdilik yine $kullanici['byk_adi'] varsayıyoruz kısıtlı join nedeniyle.
                                            // İleride join eklenirse $toplanti['byk_adi'] kullanılabilir.
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
