<?php
/**
 * Şube Ziyaretleri Listesi ve Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../includes/ensure_sube_ziyaretleri_tables.php';

$auth = new Auth();
$user = $auth->getUser();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$db = Database::getInstance();

// AT Birimi veya Super Admin Kontrolü
$isAT = false;
$userByk = $db->fetch("SELECT b.byk_kodu FROM byk b WHERE b.byk_id = ?", [$user['byk_id']]);
if ($userByk && $userByk['byk_kodu'] === 'AT') {
    $isAT = true;
}

if (!$isAT && $user['role'] !== 'super_admin') {
    header('Location: /access-denied.php');
    exit;
}
$pageTitle = 'Şube Ziyaretleri';

// Turkish Date Helper
$trMonths = [
    'Jan' => 'Ocak',
    'Feb' => 'Şubat',
    'Mar' => 'Mart',
    'Apr' => 'Nisan',
    'May' => 'Mayıs',
    'Jun' => 'Haziran',
    'Jul' => 'Temmuz',
    'Aug' => 'Ağustos',
    'Sep' => 'Eylül',
    'Oct' => 'Ekim',
    'Nov' => 'Kasım',
    'Dec' => 'Aralık'
];

$canManage = $auth->hasModulePermission('baskan_sube_ziyaretleri');

// Filters
$tab = $_GET['tab'] ?? 'planlanan'; // planlanan, tamamlanan
$scope = $_GET['scope'] ?? 'my_groups'; // my_groups, all
$grupFilter = $_GET['grup'] ?? '';
$bykFilter = $_GET['byk'] ?? '';

// Grupları ve BYK'ları getir (filtre için)
$gruplar = $db->fetchAll("SELECT grup_id, grup_adi, renk_kodu FROM ziyaret_gruplari ORDER BY grup_adi");
$bykList = $db->fetchAll("SELECT byk_id, byk_adi FROM byk ORDER BY byk_adi");

// Build Query
$where = [];
$params = [];

if ($tab === 'planlanan') {
    $where[] = "z.durum = 'planlandi'";
} else {
    $where[] = "z.durum = 'tamamlandi'";
}

if ($grupFilter) {
    $where[] = "z.grup_id = ?";
    $params[] = $grupFilter;
}

if ($bykFilter) {
    $where[] = "z.byk_id = ?";
    $params[] = $bykFilter;
}

// Scope Filter
if ($scope === 'my_groups') {
    $where[] = "(g.baskan_id = ? OR EXISTS (SELECT 1 FROM ziyaret_grup_uyeleri gu WHERE gu.grup_id = z.grup_id AND gu.kullanici_id = ?))";
    $params[] = $user['id'];
    $params[] = $user['id'];
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$ziyaretler = $db->fetchAll("
    SELECT z.*, b.byk_adi, g.grup_adi, g.renk_kodu, g.baskan_id, CONCAT(u.ad, ' ', u.soyad) as olusturan
    FROM sube_ziyaretleri z
    INNER JOIN byk b ON z.byk_id = b.byk_id
    INNER JOIN ziyaret_gruplari g ON z.grup_id = g.grup_id
    INNER JOIN kullanicilar u ON z.olusturan_id = u.kullanici_id
    $whereClause
    ORDER BY z.ziyaret_tarihi ASC
    LIMIT 100
", $params);

// Grupların üyelerini topluca çekelim (N+1 engellemek için)
$grupIds = array_unique(array_column($ziyaretler, 'grup_id'));
$grupUyeleri = [];
if (!empty($grupIds)) {
    $idsStr = implode(',', $grupIds);
    $allMembers = $db->fetchAll("
        SELECT gu.grup_id, k.kullanici_id, k.ad, k.soyad 
        FROM ziyaret_grup_uyeleri gu 
        JOIN kullanicilar k ON gu.kullanici_id = k.kullanici_id 
        WHERE gu.grup_id IN ($idsStr)
    ");
    foreach ($allMembers as $m) {
        $grupUyeleri[$m['grup_id']][] = $m;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #009872;
        --secondary: #64748b;
        --accent: #f59e0b;
        --text-dark: #1e293b;
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

    .card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border: var(--glass-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border-radius: 1rem;
    }

    .nav-pills .nav-link {
        border-radius: 10px;
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        color: var(--secondary);
    }

    .nav-pills .nav-link.active {
        background-color: var(--primary);
        color: white;
    }

    .dashboard-layout {
        display: flex;
    }

    .sidebar-wrapper {
        width: 250px;
        flex-shrink: 0;
    }

    .main-content {
        flex-grow: 1;
        padding: 0.5rem;
    }

    .content-wrapper {
        width: 100% !important;
        margin-left: 0 !important;
        padding: 1.5rem 2rem !important;
        max-width: 1400px !important;
        margin: 0 auto !important;
    }

    .table-container {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        border: var(--glass-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .table thead th {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.025em;
        padding: 1rem;
    }

    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }

    .table tbody tr:hover {
        background-color: #f8fafc;
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.75rem;
        border-radius: 6px;
        font-weight: 500;
    }

    @media (max-width: 991px) {
        .dashboard-layout {
            display: block;
        }

        .sidebar-wrapper {
            display: none;
        }

        .main-content {
            padding: 1rem;
        }
    }
</style>

<div class="dashboard-layout">
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <main class="main-content">
        <div class="content-wrapper">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h1 class="h3 fw-bold mb-1">
                        <i class="fas fa-map-location-dot me-2 text-primary"></i>Şube Ziyaretleri
                    </h1>
                    <p class="text-muted mb-0">Haftalık şube ziyaretleri ve raporlama sistemi.</p>
                </div>
                <div class="d-flex gap-2 w-100 w-md-auto">
                    <a href="ziyaret-gruplari.php" class="btn btn-outline-primary rounded-pill px-4 flex-fill flex-md-grow-0 text-center">
                        <i class="fas fa-users-rectangle me-2"></i>Grup Ynt.
                    </a>
                    <?php if ($canManage): ?>
                        <a href="yeni-ziyaret.php" class="btn btn-primary rounded-pill px-4 shadow-sm flex-fill flex-md-grow-0 text-center">
                            <i class="fas fa-plus me-2"></i>Ziyaret Planla
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabs and Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <!-- Scope Tabs -->
                    <div class="d-flex justify-content-center mb-3">
                        <div class="bg-light p-1 rounded-pill d-inline-flex">
                            <a href="?scope=my_groups&tab=<?php echo $tab; ?>" class="btn btn-sm rounded-pill px-4 <?php echo $scope === 'my_groups' ? 'btn-white shadow-sm fw-bold text-primary' : 'text-muted'; ?>">Grubum</a>
                            <a href="?scope=all&tab=<?php echo $tab; ?>" class="btn btn-sm rounded-pill px-4 <?php echo $scope === 'all' ? 'btn-white shadow-sm fw-bold text-primary' : 'text-muted'; ?>">Bütün Gruplar</a>
                        </div>
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-auto">
                            <ul class="nav nav-pills w-100 nav-fill nav-md-start">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $tab === 'planlanan' ? 'active' : ''; ?>"
                                        href="?scope=<?php echo $scope; ?>&tab=planlanan&grup=<?php echo $grupFilter; ?>&byk=<?php echo $bykFilter; ?>">
                                        <i class="fas fa-calendar-alt me-2"></i>Planlanan
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $tab === 'tamamlanan' ? 'active' : ''; ?>"
                                        href="?scope=<?php echo $scope; ?>&tab=tamamlanan&grup=<?php echo $grupFilter; ?>&byk=<?php echo $bykFilter; ?>">
                                        <i class="fas fa-check-circle me-2"></i>Tamamlanan
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="col-12 col-md">
                            <div class="d-flex gap-2 flex-wrap justify-content-md-end">
                                <select class="form-select form-select-sm flex-fill flex-md-grow-0" style="width: auto;"
                                    onchange="applyFilter('grup', this.value)">
                                    <option value="">Tüm Gruplar</option>
                                    <?php foreach ($gruplar as $g): ?>
                                        <option value="<?php echo $g['grup_id']; ?>" <?php echo $grupFilter == $g['grup_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($g['grup_adi']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select class="form-select form-select-sm flex-fill flex-md-grow-0" style="width: auto;"
                                    onchange="applyFilter('byk', this.value)">
                                    <option value="">Tüm Şubeler</option>
                                    <?php foreach ($bykList as $b): ?>
                                        <option value="<?php echo $b['byk_id']; ?>" <?php echo $bykFilter == $b['byk_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($b['byk_adi']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <?php if ($grupFilter || $bykFilter): ?>
                                    <a href="sube-ziyaretleri.php?scope=<?php echo $scope; ?>&tab=<?php echo $tab; ?>"
                                        class="btn btn-sm btn-light border">
                                        <i class="fas fa-times me-1"></i>Temizle
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visit Table list -->
            <div class="table-container shadow-sm">
                <?php if (empty($ziyaretler)): ?>
                    <div class="text-center py-5 bg-white border-0">
                        <i class="fas fa-calendar-day fa-4x text-muted opacity-25 mb-4"></i>
                        <h5 class="text-muted">Kayıtlı ziyaret bulunamadı.</h5>
                        <p class="text-muted small">Seçilen kriterlere göre görüntülenecek kayıt yok.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 mobile-table">
                            <thead class="d-none d-md-table-header-group">
                                <tr>
                                    <th class="ps-4">Tarih</th>
                                    <th>Şube</th>
                                    <th>Grup / Ekip</th>
                                    <th>Durum</th>
                                    <th class="text-end pe-4">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="d-block d-md-table-row-group">
                                <?php foreach ($ziyaretler as $ziyaret): ?>
                                    <?php
                                    $dt = new DateTime($ziyaret['ziyaret_tarihi']);
                                    $dateStr = $dt->format('d.m.Y');
                                    $dayStr = $trMonths[$dt->format('M')] ?? $dt->format('M');
                                    $members = $grupUyeleri[$ziyaret['grup_id']] ?? [];
                                    
                                    // Başkanı bul
                                    $baskanAd = '';
                                    foreach ($members as $m) {
                                        if ($m['kullanici_id'] == $ziyaret['baskan_id']) {
                                            $baskanAd = $m['ad'] . ' ' . $m['soyad'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <tr class="d-block d-md-table-row border-bottom border-md-0 position-relative mb-3 bg-white rounded-3 shadow-sm mx-0 mx-md-0 p-3 p-md-0">
                                        
                                        <!-- Mobil Başlık ve Tarih -->
                                        <td class="d-flex d-md-none justify-content-between align-items-center border-0 pb-0 px-3 pt-3">
                                            <div class="fw-bold text-dark fs-5"><?php echo htmlspecialchars($ziyaret['byk_adi']); ?></div>
                                            <div>
                                                 <?php if ($ziyaret['durum'] === 'planlandi'): ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1">Planlandı</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success px-2 py-1">Tamamlandı</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Masaüstü Tarih -->
                                        <td class="ps-4 d-none d-md-table-cell">
                                            <div class="fw-bold"><?php echo $dateStr; ?></div>
                                            <div class="small text-muted"><?php echo $dayStr; ?></div>
                                        </td>

                                        <!-- Mobil Detay Satırı: Tarih ve Grup -->
                                        <td class="d-block d-md-none border-0 px-3 py-2">
                                            <div class="d-flex align-items-center text-muted small mb-2">
                                                 <i class="fas fa-calendar-alt me-2 text-primary opacity-50"></i>
                                                 <span><?php echo $dateStr; ?> (<?php echo $dayStr; ?>)</span>
                                            </div>
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <span class="badge rounded-pill" style="background-color: <?php echo $ziyaret['renk_kodu']; ?>15; color: <?php echo $ziyaret['renk_kodu']; ?>;">
                                                    <?php echo htmlspecialchars($ziyaret['grup_adi']); ?>
                                                </span>
                                                <?php if ($baskanAd): ?>
                                                    <span class="small text-muted"><i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($baskanAd); ?></span>
                                                <?php else: ?>
                                                    <span class="small text-muted"><?php echo count($members); ?> Ziyaretçi</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Masaüstü Şube -->
                                        <td class="d-none d-md-table-cell">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($ziyaret['byk_adi']); ?></div>
                                        </td>

                                        <!-- Masaüstü Grup -->
                                        <td class="d-none d-md-table-cell">
                                            <div class="mb-1">
                                                <span class="badge rounded-pill" style="background-color: <?php echo $ziyaret['renk_kodu']; ?>15; color: <?php echo $ziyaret['renk_kodu']; ?>; border: 1px solid <?php echo $ziyaret['renk_kodu']; ?>30;">
                                                    <?php echo htmlspecialchars($ziyaret['grup_adi']); ?>
                                                </span>
                                                <span class="small text-muted ms-1">(<?php echo count($members); ?> Kişi)</span>
                                            </div>
                                            <div class="small text-muted d-flex flex-wrap gap-1">
                                                <?php
                                                $names = [];
                                                foreach ($members as $m) {
                                                    $isBaskan = $m['kullanici_id'] == $ziyaret['baskan_id'];
                                                    $nameClass = $isBaskan ? 'text-danger fw-bold' : '';
                                                    $names[] = '<span class="' . $nameClass . '">' . htmlspecialchars($m['ad'] . ' ' . $m['soyad']) . '</span>';
                                                }
                                                echo implode(', ', $names);
                                                ?>
                                            </div>
                                        </td>

                                        <!-- Masaüstü Durum -->
                                        <td class="d-none d-md-table-cell">
                                            <?php if ($ziyaret['durum'] === 'planlandi'): ?>
                                                <span class="status-badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Planlandı</span>
                                            <?php else: ?>
                                                <span class="status-badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Tamamlandı</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- İşlemler Butonları (Responsive) -->
                                        <td class="text-end pe-md-4 d-block d-md-table-cell border-0 px-3 pb-3 pt-0">
                                            <div class="d-grid d-md-inline-flex gap-2">
                                                <?php if ($ziyaret['durum'] === 'planlandi'): ?>
                                                    <a href="yeni-ziyaret.php?edit=<?php echo $ziyaret['ziyaret_id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary d-md-inline-block flex-fill" title="Düzenle">
                                                        <i class="fas fa-edit me-1 d-md-none"></i>Düzenle
                                                    </a>
                                                    <a href="yeni-ziyaret.php?rapor=<?php echo $ziyaret['ziyaret_id']; ?>" 
                                                       class="btn btn-sm btn-primary d-md-inline-block flex-fill" title="Raporla">
                                                        <i class="fas fa-file-pen me-1"></i> Raporla
                                                    </a>
                                                <?php else: ?>
                                                    <a href="ziyaret-detay.php?id=<?php echo $ziyaret['ziyaret_id']; ?>" 
                                                       class="btn btn-sm btn-outline-info d-md-inline-block flex-fill" title="Raporu Gör">
                                                        <i class="fas fa-eye me-1"></i> Rapor
                                                    </a>
                                                    <button onclick="printReport(<?php echo $ziyaret['ziyaret_id']; ?>)" 
                                                            class="btn btn-sm btn-outline-dark d-none d-md-inline-block" title="PDF / Yazdır">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    function applyFilter(filter, value) {
        const url = new URL(window.location);
        if (value) url.searchParams.set(filter, value);
        else url.searchParams.delete(filter);
        window.location = url;
    }

    function printReport(id) {
        const printWin = window.open('ziyaret-pdf.php?id=' + id, '_blank');
        printWin.onload = function () {
            // printWin.print(); // Removed auto-print to let user see the premium preview first
        };
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>