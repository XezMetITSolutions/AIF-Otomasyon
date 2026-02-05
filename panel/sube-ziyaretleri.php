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
    'Jan' => 'Ocak', 'Feb' => 'Şubat', 'Mar' => 'Mart', 'Apr' => 'Nisan',
    'May' => 'Mayıs', 'Jun' => 'Haziran', 'Jul' => 'Temmuz', 'Aug' => 'Ağustos',
    'Sep' => 'Eylül', 'Oct' => 'Ekim', 'Nov' => 'Kasım', 'Dec' => 'Aralık'
];

$canManage = $auth->hasModulePermission('baskan_sube_ziyaretleri');

// Filters
$tab = $_GET['tab'] ?? 'planlanan'; // planlanan, tamamlanan
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

// Global Admin (AT) değilse sadece kendi ziyaretlerini veya grubunun ziyaretlerini görebilir mi?
// Kullanıcının "Sube Ziyaretleri" yetkisi varsa genellikle herkesinkini görebilir (Raporlama için).

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$ziyaretler = $db->fetchAll("
    SELECT z.*, b.byk_adi, g.grup_adi, g.renk_kodu, CONCAT(u.ad, ' ', u.soyad) as olusturan
    FROM sube_ziyaretleri z
    INNER JOIN byk b ON z.byk_id = b.byk_id
    INNER JOIN ziyaret_gruplari g ON z.grup_id = g.grup_id
    INNER JOIN kullanicilar u ON z.olusturan_id = u.kullanici_id
    $whereClause
    ORDER BY z.ziyaret_tarihi DESC
    LIMIT 100
", $params);

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
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

    .dashboard-layout { display: flex; }
    .sidebar-wrapper { width: 250px; flex-shrink: 0; }
    .main-content { flex-grow: 1; padding: 1.5rem 2rem; max-width: 1400px; margin: 0 auto; }

    .date-badge {
        width: 60px;
        height: 60px;
        background: #f1f5f9;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 991px) {
        .dashboard-layout { display: block; }
        .sidebar-wrapper { display: none; }
        .main-content { padding: 1rem; }
    }
</style>

<div class="dashboard-layout">
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <main class="main-content">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">
                    <i class="fas fa-map-location-dot me-2 text-primary"></i>Şube Ziyaretleri
                </h1>
                <p class="text-muted mb-0">Haftalık şube ziyaretleri ve raporlama sistemi.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="ziyaret-gruplari.php" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="fas fa-users-rectangle me-2"></i>Grup Yönetimi
                </a>
                <?php if ($canManage): ?>
                    <a href="yeni-ziyaret.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fas fa-plus me-2"></i>Ziyaret Planla
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabs and Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-auto">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'planlanan' ? 'active' : ''; ?>" href="?tab=planlanan&grup=<?php echo $grupFilter; ?>&byk=<?php echo $bykFilter; ?>">
                                    <i class="fas fa-calendar-alt me-2"></i>Planlanan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'tamamlanan' ? 'active' : ''; ?>" href="?tab=tamamlanan&grup=<?php echo $grupFilter; ?>&byk=<?php echo $bykFilter; ?>">
                                    <i class="fas fa-check-circle me-2"></i>Tamamlanan
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-12 col-md">
                        <div class="d-flex gap-2 flex-wrap">
                            <select class="form-select form-select-sm" style="width: auto;" onchange="applyFilter('grup', this.value)">
                                <option value="">Tüm Gruplar</option>
                                <?php foreach ($gruplar as $g): ?>
                                    <option value="<?php echo $g['grup_id']; ?>" <?php echo $grupFilter == $g['grup_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($g['grup_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select class="form-select form-select-sm" style="width: auto;" onchange="applyFilter('byk', this.value)">
                                <option value="">Tüm Şubeler</option>
                                <?php foreach ($bykList as $b): ?>
                                    <option value="<?php echo $b['byk_id']; ?>" <?php echo $bykFilter == $b['byk_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['byk_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if ($grupFilter || $bykFilter): ?>
                                <a href="sube-ziyaretleri.php?tab=<?php echo $tab; ?>" class="btn btn-sm btn-light border">
                                    <i class="fas fa-times me-1"></i>Temizle
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visit List -->
        <div class="row g-4">
            <?php if (empty($ziyaretler)): ?>
                <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm border">
                    <i class="fas fa-calendar-day fa-4x text-muted opacity-25 mb-4"></i>
                    <h5 class="text-muted">Kayıtlı ziyaret bulunamadı.</h5>
                    <p class="text-muted small">Seçilen kriterlere göre görüntülenecek kayıt yok.</p>
                </div>
            <?php else: ?>
                <?php foreach ($ziyaretler as $ziyaret): ?>
                    <?php 
                        $dt = new DateTime($ziyaret['ziyaret_tarihi']);
                        $isPast = $dt < new DateTime('today');
                        $isToday = $dt->format('Y-m-d') === date('Y-m-d');
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex gap-3 mb-3">
                                    <div class="date-badge border">
                                        <span class="fw-bold fs-5 mb-0"><?php echo $dt->format('d'); ?></span>
                                        <span class="small text-uppercase text-muted" style="font-size: 0.7rem;">
                                            <?php echo $trMonths[$dt->format('M')] ?? $dt->format('M'); ?>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h5 class="fw-bold mb-1 text-truncate"><?php echo htmlspecialchars($ziyaret['byk_adi']); ?></h5>
                                        <span class="badge rounded-pill mb-2" style="background-color: <?php echo $ziyaret['renk_kodu']; ?>20; color: <?php echo $ziyaret['renk_kodu']; ?>; border: 1px solid <?php echo $ziyaret['renk_kodu']; ?>40;">
                                            <i class="fas fa-users-rectangle me-1"></i> <?php echo htmlspecialchars($ziyaret['grup_adi']); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-3 small flex-column d-flex gap-1">
                                    <div class="text-muted">
                                        <i class="fas fa-user-edit me-2"></i>Planlayan: <strong><?php echo htmlspecialchars($ziyaret['olusturan']); ?></strong>
                                    </div>
                                    <?php if ($ziyaret['notlar']): ?>
                                        <div class="text-muted text-truncate-2 mt-1 italic">
                                            <i class="fas fa-sticky-note me-2"></i><?php echo htmlspecialchars($ziyaret['notlar']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top">
                                    <?php if ($ziyaret['durum'] === 'planlandi'): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-3 border border-warning border-opacity-25">
                                            <i class="fas fa-clock me-1"></i> Beklemede
                                        </span>
                                        <div class="btn-group">
                                            <a href="yeni-ziyaret.php?edit=<?php echo $ziyaret['ziyaret_id']; ?>" class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="yeni-ziyaret.php?rapor=<?php echo $ziyaret['ziyaret_id']; ?>" class="btn btn-sm btn-primary" title="Raporu Doldur">
                                                <i class="fas fa-file-pen me-1"></i> Raporla
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-3 border border-success border-opacity-25">
                                            <i class="fas fa-check-circle me-1"></i> Tamamlandı
                                        </span>
                                        <a href="ziyaret-detay.php?id=<?php echo $ziyaret['ziyaret_id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye me-1"></i> Raporu Gör
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
