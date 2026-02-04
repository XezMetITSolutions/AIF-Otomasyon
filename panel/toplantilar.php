<?php
/**
 * Başkan - Toplantı Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';



$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Toplantı Yönetimi';

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

function formatTextAsList($text)
{
    if (empty($text))
        return '';
    $escaped = htmlspecialchars($text);
    $withBreaks = nl2br($escaped);
    $formatted = str_replace([' - ', ' • ', ' * '], ['<br>- ', '<br>• ', '<br>* '], $withBreaks);
    return $formatted;
}

// Permission check for management actions
$canManage = $auth->hasModulePermission('baskan_toplantilar');

// Check if user belongs to 'AT' (Global Admin Unit)
$userByk = $db->fetch("SELECT * FROM byk WHERE byk_id = ?", [$user['byk_id']]);
$isAdmin = ($userByk && $userByk['byk_kodu'] === 'AT');

// Filters
$tab = $_GET['tab'] ?? 'gelecek'; // gelecek, gecmis
$monthFilter = $_GET['ay'] ?? '';
$bykFilter = $_GET['byk'] ?? '';
$view = $_GET['view'] ?? 'list'; // list, card

// Get BYK list for filter (admin only)
$bykList = [];
if ($isAdmin) {
    $bykList = $db->fetchAll("SELECT byk_id, byk_adi FROM byk ORDER BY byk_adi");
}

// Generate month list (last 12 months + next 6 months)
$monthList = [];
for ($i = -12; $i <= 6; $i++) {
    $date = new DateTime();
    $date->modify("$i months");
    $monthList[] = [
        'value' => $date->format('Y-m'),
        'label' => $trMonths[$date->format('M')] . ' ' . $date->format('Y')
    ];
}

// Build WHERE clause
$where = [];
$params = [];

// BYK filter
if ($isAdmin) {
    if ($bykFilter) {
        $where[] = "t.byk_id = ?";
        $params[] = $bykFilter;
    }
    // Admin sees all BYKs if no filter
} else {
    $where[] = "EXISTS (SELECT 1 FROM toplanti_katilimcilar tk WHERE tk.toplanti_id = t.toplanti_id AND tk.kullanici_id = ?)";
    $params[] = $user['kullanici_id'];
}

// Date filter (future/past)
if ($tab === 'gelecek') {
    $where[] = "t.toplanti_tarihi >= CURDATE()";
} else {
    $where[] = "t.toplanti_tarihi < CURDATE()";
}

// Month filter
if ($monthFilter) {
    $where[] = "DATE_FORMAT(t.toplanti_tarihi, '%Y-%m') = ?";
    $params[] = $monthFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Toplantılar + Katılım İstatistikleri
$toplantilar = $db->fetchAll("
    SELECT t.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as olusturan,
           (SELECT COUNT(*) FROM toplanti_katilimcilar tk WHERE tk.toplanti_id = t.toplanti_id) as total_participants,
           (SELECT COUNT(*) FROM toplanti_katilimcilar tk WHERE tk.toplanti_id = t.toplanti_id AND tk.katilim_durumu = 'katilacak') as confirmed_participants
    FROM toplantilar t
    INNER JOIN byk b ON t.byk_id = b.byk_id
    INNER JOIN kullanicilar u ON t.olusturan_id = u.kullanici_id
    $whereClause
    ORDER BY t.toplanti_tarihi " . ($tab === 'gelecek' ? 'ASC' : 'DESC') . "
    LIMIT 100
", $params);

include __DIR__ . '/../includes/header.php';
?>

<!-- Modern Design Assets -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* ... (existing styles) ... */
    :root {
        --primary: #009872;
        --primary-light: rgba(0, 152, 114, 0.1);
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --card-bg: rgba(255, 255, 255, 0.9);
        --glass-border: 1px solid rgba(255, 255, 255, 0.5);
    }

    /* ... rest of styles ... */
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
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1.25rem 1.5rem;
        font-size: 1rem;
        color: var(--text-dark);
    }

    /* CSS Overrides for Mobile Layout Fix */
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
            padding: 1.5rem 2rem !important;
            max-width: 1400px !important;
            margin: 0 auto !important;
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
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h1 class="h3 fw-bold mb-1 text-dark">
                        <i class="fas fa-users-cog me-2 text-primary"></i>Toplantı Yönetimi
                    </h1>
                    <p class="text-muted mb-0">Bölge Yürütme Kurulu toplantılarını yönetin.</p>
                </div>
                <?php if ($canManage): ?>
                    <a href="/panel/toplanti-ekle.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fas fa-plus me-2"></i>Yeni Toplantı Ekle
                    </a>
                <?php endif; ?>
            </div>

            <!-- Tabs and Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <!-- Tabs -->
                        <div class="col-12 col-md-auto">
                            <ul class="nav nav-pills">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $tab === 'gelecek' ? 'active' : ''; ?>"
                                        href="?tab=gelecek&view=<?php echo $view; ?><?php echo $monthFilter ? '&ay=' . $monthFilter : ''; ?><?php echo $bykFilter ? '&byk=' . $bykFilter : ''; ?>">
                                        <i class="fas fa-calendar-plus me-2"></i>Gelecek
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $tab === 'gecmis' ? 'active' : ''; ?>"
                                        href="?tab=gecmis&view=<?php echo $view; ?><?php echo $monthFilter ? '&ay=' . $monthFilter : ''; ?><?php echo $bykFilter ? '&byk=' . $bykFilter : ''; ?>">
                                        <i class="fas fa-history me-2"></i>Geçmiş
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Filters -->
                        <div class="col-12 col-md">
                            <div class="d-flex gap-2 flex-wrap">
                                <!-- Month Filter -->
                                <select class="form-select form-select-sm" style="width: auto;"
                                    onchange="applyFilter('ay', this.value)">
                                    <option value="">Tüm Aylar</option>
                                    <?php foreach ($monthList as $month): ?>
                                        <option value="<?php echo $month['value']; ?>" <?php echo $monthFilter === $month['value'] ? 'selected' : ''; ?>>
                                            <?php echo $month['label']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- BYK Filter (Admin only) -->
                                <?php if ($isAdmin): ?>
                                    <select class="form-select form-select-sm" style="width: auto;"
                                        onchange="applyFilter('byk', this.value)">
                                        <option value="">Tüm Birimler</option>
                                        <?php foreach ($bykList as $byk): ?>
                                            <option value="<?php echo $byk['byk_id']; ?>" <?php echo $bykFilter == $byk['byk_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($byk['byk_adi']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>

                                <!-- Clear Filters -->
                                <?php if ($monthFilter || $bykFilter): ?>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()">
                                        <i class="fas fa-times me-1"></i>Temizle
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- View Toggle -->
                        <div class="col-12 col-md-auto">
                            <div class="btn-group">
                                <button
                                    class="btn btn-sm <?php echo $view === 'card' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                                    onclick="setView('card')" title="Card Görünümü">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button
                                    class="btn btn-sm <?php echo $view === 'list' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                                    onclick="setView('list')" title="Liste Görünümü">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($toplantilar)): ?>
                <div class="text-center py-5 bg-white rounded-3 shadow-sm">
                    <div class="mb-3 text-muted opacity-50">
                        <i class="fas fa-calendar-times fa-4x"></i>
                    </div>
                    <h5 class="text-muted">Toplantı bulunamadı</h5>
                    <p class="text-muted small">Seçili filtrelere uygun toplantı bulunmuyor.</p>
                </div>
            <?php else: ?>

                <!-- Card View -->
                <div id="cardView" style="display: <?php echo $view === 'card' ? 'block' : 'none'; ?>;">
                    <div class="row g-4">
                        <?php foreach ($toplantilar as $toplanti): ?>
                            <?php
                            $tarih = new DateTime($toplanti['toplanti_tarihi']);
                            $monthShort = $tarih->format('M');
                            $trMonth = $trMonths[$monthShort] ?? $monthShort;

                            $total = $toplanti['total_participants'];
                            $confirmed = $toplanti['confirmed_participants'];
                            $percent = $total > 0 ? round(($confirmed / $total) * 100) : 0;

                            $isPast = $tarih < new DateTime();
                            $isCancelled = $toplanti['durum'] === 'iptal';
                            $isActive = !$isPast && !$isCancelled;

                            $cardClass = $isCancelled ? 'border-danger bg-light' : ($isPast ? 'border-secondary' : 'border-primary');
                            $opacityClass = $isCancelled || $isPast ? 'opacity-75' : '';

                            $linkUrl = $canManage ? "/panel/toplanti-duzenle.php?id=" . $toplanti['toplanti_id'] : "#";
                            $linkClass = $canManage ? "text-dark text-decoration-none stretched-link" : "text-dark text-decoration-none";
                            ?>
                            <div class="col-md-6 col-xl-4">
                                <div
                                    class="card h-100 shadow-sm <?php echo $cardClass; ?> hover-shadow transition-all <?php echo $opacityClass; ?>">
                                    <div class="card-body">
                                        <div class="d-flex gap-3 mb-3">
                                            <div class="d-flex flex-column align-items-center justify-content-center bg-light rounded-3 p-2 text-center border"
                                                style="min-width: 70px; height: 70px;">
                                                <span
                                                    class="h4 mb-0 fw-bold text-dark"><?php echo $tarih->format('d'); ?></span>
                                                <span class="small text-uppercase text-muted"><?php echo $trMonth; ?></span>
                                            </div>

                                            <div class="flex-grow-1">
                                                <h5 class="card-title fw-bold mb-1 text-truncate-2">
                                                    <a href="<?php echo $linkUrl; ?>" class="<?php echo $linkClass; ?>">
                                                        <?php echo htmlspecialchars($toplanti['baslik']); ?>
                                                    </a>
                                                </h5>
                                                <div class="d-flex align-items-center text-muted small mt-1">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?php echo $tarih->format('H:i'); ?>

                                                    <?php if ($toplanti['konum']): ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars($toplanti['konum']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($isAdmin): ?>
                                                    <div class="small text-primary mt-1">
                                                        <i
                                                            class="fas fa-building me-1"></i><?php echo htmlspecialchars($toplanti['byk_adi']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if ($toplanti['aciklama']): ?>
                                            <div class="text-muted small mb-3 text-truncate-3">
                                                <?php echo formatTextAsList($toplanti['aciklama']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                                <span class="text-muted fw-bold">Katılım Durumu</span>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-users me-1"></i> <?php echo $confirmed; ?> /
                                                    <?php echo $total; ?>
                                                </span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: <?php echo $percent; ?>%"
                                                    aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="card-footer bg-white border-top-0 p-3 pt-0 d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i
                                                class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($toplanti['olusturan']); ?>
                                        </small>

                                        <?php if ($canManage): ?>
                                            <div class="btn-group" style="position: relative; z-index: 2;">
                                                <a href="/panel/toplanti-duzenle.php?id=<?php echo $toplanti['toplanti_id']; ?>"
                                                    class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <?php if (!$isCancelled && !$isPast): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                                        onclick="cancelMeeting(<?php echo $toplanti['toplanti_id']; ?>, '<?php echo htmlspecialchars(addslashes($toplanti['baslik'])); ?>')"
                                                        title="İptal Et">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteMeeting(<?php echo $toplanti['toplanti_id']; ?>, '<?php echo htmlspecialchars(addslashes($toplanti['baslik'])); ?>')"
                                                    title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- List View -->
                <div id="listView" style="display: <?php echo $view === 'list' ? 'block' : 'none'; ?>;">
                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Tarih</th>
                                        <th>Başlık</th>
                                        <?php if ($isAdmin): ?>
                                            <th>Birim</th><?php endif; ?>
                                        <th>Konum</th>
                                        <th>Katılım</th>
                                        <th>Durum</th>
                                        <?php if ($canManage): ?>
                                            <th class="text-end pe-4">İşlemler</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($toplantilar as $toplanti): ?>
                                        <?php
                                        $tarih = new DateTime($toplanti['toplanti_tarihi']);
                                        $monthShort = $tarih->format('M');
                                        $trMonth = $trMonths[$monthShort] ?? $monthShort;

                                        $total = $toplanti['total_participants'];
                                        $confirmed = $toplanti['confirmed_participants'];
                                        $percent = $total > 0 ? round(($confirmed / $total) * 100) : 0;

                                        $isPast = $tarih < new DateTime();
                                        $isCancelled = $toplanti['durum'] === 'iptal';

                                        $rowClass = $isCancelled ? 'table-danger' : ($isPast ? 'table-secondary' : '');
                                        ?>
                                        <tr class="<?php echo $rowClass; ?>">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="text-center me-2" style="min-width: 50px;">
                                                        <div class="fw-bold text-dark"><?php echo $tarih->format('d'); ?></div>
                                                        <small class="text-muted"><?php echo $trMonth; ?></small>
                                                    </div>
                                                    <div>
                                                        <div class="small text-muted"><?php echo $tarih->format('H:i'); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">
                                                    <?php if ($canManage): ?>
                                                        <a href="/panel/toplanti-duzenle.php?id=<?php echo $toplanti['toplanti_id']; ?>"
                                                            class="text-dark text-decoration-none">
                                                            <?php echo htmlspecialchars($toplanti['baslik']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($toplanti['baslik']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted">
                                                    <i
                                                        class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($toplanti['olusturan']); ?>
                                                </small>
                                            </td>
                                            <?php if ($isAdmin): ?>
                                                <td>
                                                    <span
                                                        class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10">
                                                        <?php echo htmlspecialchars($toplanti['byk_adi']); ?>
                                                    </span>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <?php if ($toplanti['konum']): ?>
                                                    <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                    <span class="small"><?php echo htmlspecialchars($toplanti['konum']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2" style="min-width: 120px;">
                                                    <div class="progress flex-grow-1" style="height: 8px;">
                                                        <div class="progress-bar bg-success"
                                                            style="width: <?php echo $percent; ?>%"></div>
                                                    </div>
                                                    <small
                                                        class="text-muted"><?php echo $confirmed; ?>/<?php echo $total; ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($isCancelled): ?>
                                                    <span class="badge bg-danger">İptal</span>
                                                <?php elseif ($isPast): ?>
                                                    <span class="badge bg-secondary">Geçmiş</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($canManage): ?>
                                                <td class="text-end pe-4">
                                                    <div class="btn-group">
                                                        <a href="/panel/toplanti-duzenle.php?id=<?php echo $toplanti['toplanti_id']; ?>"
                                                            class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if (!$isCancelled && !$isPast): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                                onclick="cancelMeeting(<?php echo $toplanti['toplanti_id']; ?>, '<?php echo htmlspecialchars(addslashes($toplanti['baslik'])); ?>')"
                                                                title="İptal Et">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteMeeting(<?php echo $toplanti['toplanti_id']; ?>, '<?php echo htmlspecialchars(addslashes($toplanti['baslik'])); ?>')"
                                                            title="Sil">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Using existing Cancel/Delete Modals & JS logic (retained) -->
<div class="modal fade" id="cancelMeetingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Toplantıyı İptal Et</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>⚠️ <strong id="meetingTitle"></strong> toplantısını iptal etmek istediğinize emin misiniz?</p>
                <p class="text-muted">Tüm katılımcılara iptal bildirimi e-postası gönderilecektir.</p>

                <div class="mb-3">
                    <label for="cancelReason" class="form-label">İptal Nedeni (Opsiyonel)</label>
                    <textarea class="form-control" id="cancelReason" rows="3"
                        placeholder="İptal nedenini buraya yazabilirsiniz..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                    <i class="fas fa-times-circle me-2"></i>Toplantıyı İptal Et
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Utilities */
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .text-truncate-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .hover-shadow:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
    }

    .transition-all {
        transition: all 0.3s ease;
    }
</style>

<script>
    // Filter and View Functions
    function applyFilter(filterType, value) {
        const url = new URL(window.location);
        if (value) {
            url.searchParams.set(filterType, value);
        } else {
            url.searchParams.delete(filterType);
        }
        window.location = url;
    }

    function clearFilters() {
        const url = new URL(window.location);
        url.searchParams.delete('ay');
        url.searchParams.delete('byk');
        window.location = url;
    }

    function setView(viewType) {
        const url = new URL(window.location);
        url.searchParams.set('view', viewType);
        window.location = url;
    }

    // Meeting Management Functions
    let currentMeetingId = null;

    function cancelMeeting(id, title) {
        currentMeetingId = id;
        document.getElementById('meetingTitle').textContent = title;
        document.getElementById('cancelReason').value = '';
        const modal = new bootstrap.Modal(document.getElementById('cancelMeetingModal'));
        modal.show();
    }

    document.getElementById('confirmCancelBtn').addEventListener('click', async function () {
        const reason = document.getElementById('cancelReason').value;
        const btn = this;
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>İptal Ediliyor...';

        try {
            const response = await fetch('/api/cancel-meeting.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    toplanti_id: currentMeetingId,
                    iptal_nedeni: reason
                })
            });

            const data = await response.json();

            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ Hata: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            alert('❌ Bir hata oluştu: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    function deleteMeeting(id, title) {
        if (!confirm(`⚠️ "${title}" toplantısını kalıcı olarak silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz ve tüm ilgili veriler (katılımcılar, gündem, kararlar) silinecektir.`)) {
            return;
        }

        if (!confirm(`Son uyarı: Toplantıyı silmek istediğinize %100 emin misiniz?`)) {
            return;
        }

        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch('/api/delete-meeting.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                toplanti_id: id
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Hata: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                alert('❌ Bir hata oluştu: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
    }
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>