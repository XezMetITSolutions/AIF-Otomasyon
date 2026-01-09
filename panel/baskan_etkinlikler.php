<?php
/**
 * Başkan - Çalışma Takvimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';


// Module permission check if needed (Assuming 'baskan_etkinlikler' covers viewing)
// Middleware::requireModulePermission('baskan_etkinlikler');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Çalışma Takvimi';

// Filtreleme
$search = $_GET['search'] ?? '';
$monthFilter = $_GET['ay'] ?? '';
$yearFilter = $_GET['yil'] ?? '';

// Force BYK filter for security
// Force BYK filter for security
$userByk = $db->fetch("SELECT * FROM byk WHERE byk_id = ?", [$user['byk_id']]);
$isAT = ($userByk && $userByk['byk_kodu'] === 'AT');
$regionPrefix = '';

if ($isAT) {
    // Extract region name by removing all known unit types and suffixes
    $removals = [
        ' Ana Teşkilat', ' AT', '(AT)', ' (AT)',
        ' Kadınlar Teşkilatı', ' KT', '(KT)', ' (KT)',
        ' Gençlik Teşkilatı', ' GT', '(GT)', ' (GT)',
        ' Kadınlar Gençlik Teşkilatı', ' KGT', '(KGT)', ' (KGT)',
        ' Üniversiteliler', ' ÜNİ', '(ÜNİ)', ' (ÜNİ)'
    ];
    
    // Case insensitive replace might be safer but standard replace is fine if data is consistent
    $regionName = str_ireplace($removals, '', $userByk['byk_adi']);
    $regionName = trim($regionName); // Clean whitespace
    $regionPrefix = $regionName;
    
    // Find all related BYK IDs with a looser search
    // We search for BYKs that start with the region name
    $relatedByks = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk WHERE byk_adi LIKE ?", ["$regionName%"]);
    $relatedBykIds = array_column($relatedByks, 'byk_id');
    
    // Fallback: If stripping resulted in empty string (unlikely) or no matches
    if (empty($relatedBykIds) || empty($regionName)) {
        $relatedBykIds = [$user['byk_id']];
    }
    
    $where = ["e.byk_id IN (" . implode(',', $relatedBykIds) . ")"];
    $params = [];
} else {
    $where = ["e.byk_id = ?"];
    $params = [$user['byk_id']];
}

if ($search) {
    $where[] = "(e.baslik LIKE ? OR e.aciklama LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($monthFilter) {
    $where[] = "MONTH(e.baslangic_tarihi) = ?";
    $params[] = $monthFilter;
}

if ($yearFilter) {
    $where[] = "YEAR(e.baslangic_tarihi) = ?";
    $params[] = $yearFilter;
}

// Birim Filtresi (Sadece AT için)
$birimFilter = $_GET['birim'] ?? '';
if ($isAT && $birimFilter) {
    // Filter by creating a subquery or join? No, e.byk_id must match the selected unit type's byk_id
    // We need to find the specific BYK ID for the selected unit code within this region
    // Or simpler: Join BYK table and filter by byk_kodu
    // We already do a left join below.
    // NOTE: We can't add this to $where easily because the JOIN happens in the main query.
    // Let's add the condition to the WHERE clause using the alias 'b'
    $where[] = "b.byk_kodu = ?";
    $params[] = $birimFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Etkinlikler
try {
    $etkinlikler = $db->fetchAll("
        SELECT e.*, 
               COALESCE(b.byk_adi, '-') as byk_adi,
               COALESCE(b.byk_kodu, '') as byk_kodu,
               COALESCE(b.renk_kodu, e.renk_kodu, '#009872') as byk_renk,
               COALESCE(CONCAT(u.ad, ' ', u.soyad), '-') as olusturan
        FROM etkinlikler e
        LEFT JOIN byk b ON e.byk_id = b.byk_id
        LEFT JOIN kullanicilar u ON e.olusturan_id = u.kullanici_id
        $whereClause
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 500
    ", $params);
    
    // Process colors logic similar to admin (simplified or full copy)
    // For Baskan, mostly just their own colors matter, which likely fall back to default or BYK color.
    // I'll preserve the robust color logic just in case.
    
    if (!empty($etkinlikler)) {
        try {
            $bykCategories = $db->fetchAll("SELECT code, name, color FROM byk_categories");
            $bykCategoryMap = [];
            foreach ($bykCategories as $cat) {
                $bykCategoryMap[$cat['code']] = [
                    'name' => $cat['name'],
                    'color' => $cat['color']
                ];
            }
            
            foreach ($etkinlikler as &$etkinlik) {
                $bykKodu = $etkinlik['byk_kodu'] ?? '';
                if (!empty($bykKodu) && isset($bykCategoryMap[$bykKodu])) {
                    $etkinlik['byk_adi'] = $bykCategoryMap[$bykKodu]['name'];
                    if (!empty($bykCategoryMap[$bykKodu]['color'])) {
                        $etkinlik['byk_renk'] = $bykCategoryMap[$bykKodu]['color'];
                    }
                }
                if (empty($etkinlik['byk_renk']) || $etkinlik['byk_renk'] == '#009872') {
                    if (!empty($etkinlik['renk_kodu'])) {
                        $etkinlik['byk_renk'] = $etkinlik['renk_kodu'];
                    }
                }
            }
            unset($etkinlik);
        } catch (Exception $e) { /* Ignore */ }
    }
} catch (Exception $e) {
    $etkinlikler = [];
}

// Ensure array
if (!is_array($etkinlikler)) {
    $etkinlikler = [];
}

// Calendar Events mapping
$bykDefaultColors = [
    'AT' => '#dc3545',
    'KT' => '#6f42c1',
    'KGT' => '#198754',
    'GT' => '#0d6efd'
];
$bykColorMap = []; // Simplified

$calendarEvents = [];
foreach ($etkinlikler as $etkinlik) {
    if (empty($etkinlik['baslangic_tarihi']) || empty($etkinlik['bitis_tarihi']) || empty($etkinlik['baslik'])) {
        continue;
    }
    
    try {
        $baslangic = new DateTime($etkinlik['baslangic_tarihi']);
        $bitis = new DateTime($etkinlik['bitis_tarihi']);
    } catch (Exception $e) { continue; }
    
    $bykRenk = $etkinlik['byk_renk'] ?? '#009872';
    // Simplified color logic
    if (empty($bykRenk) || !preg_match('/^#[0-9A-Fa-f]{6}$/i', $bykRenk)) {
         $bykRenk = '#009872';
    }
    
    $calendarEvents[] = [
        'id' => $etkinlik['etkinlik_id'],
        'title' => $etkinlik['baslik'],
        'start' => $baslangic->format('Y-m-d\TH:i:s'),
        'end' => $bitis->format('Y-m-d\TH:i:s'),
        'allDay' => (date('H:i:s', strtotime($etkinlik['baslangic_tarihi'])) == '00:00:00' && 
                     date('H:i:s', strtotime($etkinlik['bitis_tarihi'])) == '23:59:59'),
        'backgroundColor' => $bykRenk,
        'borderColor' => $bykRenk,
        'textColor' => '#ffffff',
        'extendedProps' => [
            'byk' => $etkinlik['byk_adi'] ?? '',
            'konum' => $etkinlik['konum'] ?? '',
            'aciklama' => $etkinlik['aciklama'] ?? '',
            'olusturan' => $etkinlik['olusturan'] ?? ''
        ]
    ];
}

$pageSpecificCSS = [
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css'
];

$pageSpecificJS = [
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'
];

include __DIR__ . '/../includes/header.php';
?>

<!-- Modern Design Assets -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>Çalışma Takvimi
                </h1>
                <div class="btn-group">
                    <a href="/api/export-calendar.php?<?php echo http_build_query($_GET); ?>" class="btn btn-outline-success">
                        <i class="fas fa-file-export me-2"></i>Takvime Aktar
                    </a>
                    <a href="/panel/baskan_etkinlik-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Yeni Etkinlik Ekle
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>İşlem başarıyla tamamlandı.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtreler -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Arama</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Etkinlik adı, açıklama...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ay</label>
                            <select class="form-select" name="ay">
                                <option value="">Tüm Aylar</option>
                                <?php
                                $aylar = [
                                    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                                    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                                    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
                                ];
                                foreach ($aylar as $num => $ayAdi):
                                ?>
                                    <option value="<?php echo $num; ?>" <?php echo $monthFilter == $num ? 'selected' : ''; ?>>
                                        <?php echo $ayAdi; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Yıl</label>
                            <input type="number" class="form-control" name="yil" value="<?php echo htmlspecialchars($yearFilter); ?>" min="2020" max="2030">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filtrele
                            </button>
                            <?php if ($search || $monthFilter || $yearFilter != date('Y') || ($isAT && $birimFilter)): ?>
                                <a href="/panel/baskan_etkinlikler.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($isAT): ?>
                        <div class="col-12 border-top pt-3 mt-3">
                            <label class="form-label d-block text-muted small fw-bold mb-2">BİRİM GÖSTERİMİ (Bölge: <?php echo htmlspecialchars($regionName); ?>)</label>
                            <div class="btn-group" role="group">
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['birim' => ''])); ?>" class="btn btn-sm btn-outline-secondary <?php echo $birimFilter == '' ? 'active' : ''; ?>">Tümü</a>
                                <?php
                                // Get available unit types in this region
                                $availableTypes = $db->fetchAll("SELECT DISTINCT byk_kodu FROM byk WHERE byk_adi LIKE ? ORDER BY byk_kodu", ["$regionPrefix%"]);
                                foreach ($availableTypes as $type):
                                    if(empty($type['byk_kodu'])) continue;
                                    $label = $type['byk_kodu'];
                                    $isActive = ($birimFilter == $type['byk_kodu']);
                                ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['birim' => $type['byk_kodu']])); ?>" class="btn btn-sm btn-outline-primary <?php echo $isActive ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Görünüm Seçici -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-info me-2">Toplam: <strong><?php echo count($etkinlikler); ?></strong> etkinlik</span>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="calendarViewBtn">
                                <i class="fas fa-calendar-alt me-1"></i>Takvim
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="listViewBtn">
                                <i class="fas fa-list me-1"></i>Liste
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Takvim Görünümü -->
            <div class="card" id="calendarView">
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
            
            <!-- Liste Görünümü -->
            <div class="card d-none" id="listView">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Başlık</th>
                                    <th>Konum</th>
                                    <th>Oluşturan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($etkinlikler)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Henüz etkinlik eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($etkinlikler as $etkinlik): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('d.m.Y', strtotime($etkinlik['baslangic_tarihi'])); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php 
                                                        $baslangicSaat = date('H:i', strtotime($etkinlik['baslangic_tarihi']));
                                                        $bitisSaat = date('H:i', strtotime($etkinlik['bitis_tarihi']));
                                                        if ($baslangicSaat != '00:00' || $bitisSaat != '23:59') {
                                                            echo $baslangicSaat . ' - ' . $bitisSaat;
                                                        } else {
                                                            echo 'Tüm gün';
                                                        }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($etkinlik['baslik']); ?></strong>
                                                <?php if (!empty($etkinlik['aciklama'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($etkinlik['aciklama'], 0, 50)); ?><?php echo strlen($etkinlik['aciklama']) > 50 ? '...' : ''; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($etkinlik['konum']) ? htmlspecialchars($etkinlik['konum']) : '<span class="text-muted">-</span>'; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($etkinlik['olusturan']) ? htmlspecialchars($etkinlik['olusturan']) : '<span class="text-muted">-</span>'; ?>
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
    </main>
</div>

<!-- Etkinlik Detay Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Etkinlik Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarViewBtn = document.getElementById('calendarViewBtn');
    const listViewBtn = document.getElementById('listViewBtn');
    const calendarView = document.getElementById('calendarView');
    const listView = document.getElementById('listView');
    
    calendarViewBtn.addEventListener('click', function() {
        calendarViewBtn.classList.add('active');
        listViewBtn.classList.remove('active');
        calendarView.classList.remove('d-none');
        listView.classList.add('d-none');
        calendar.render(); 
    });
    
    listViewBtn.addEventListener('click', function() {
        listViewBtn.classList.add('active');
        calendarViewBtn.classList.remove('active');
        listView.classList.remove('d-none');
        calendarView.classList.add('d-none');
    });
    
    const calendarEvents = <?php echo json_encode($calendarEvents, JSON_UNESCAPED_UNICODE); ?>;
    const calendarEl = document.getElementById('calendar');
    
    if (calendarEl) {
        window.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'tr',
            firstDay: 1,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            buttonText: {
                today: 'Bugün',
                month: 'Ay',
                week: 'Hafta',
                day: 'Gün',
                list: 'Liste'
            },
            events: calendarEvents,
            eventClick: function(info) {
                const event = info.event;
                const extendedProps = event.extendedProps;
                
                const modalBody = document.getElementById('eventModalBody');
                const modalTitle = document.getElementById('eventModalLabel');
                
                modalTitle.textContent = event.title;
                
                let html = '<div class="mb-3"><strong>Başlık:</strong> ' + event.title + '</div>';
                
                if (extendedProps.byk) {
                    html += '<div class="mb-3"><strong>BYK:</strong> <span class="badge" style="background-color: ' + event.backgroundColor + '; color: white;">' + extendedProps.byk + '</span></div>';
                }
                
                html += '<div class="mb-3"><strong>Başlangıç:</strong> ' + event.start.toLocaleString('tr-TR') + '</div>';
                html += '<div class="mb-3"><strong>Bitiş:</strong> ' + event.end.toLocaleString('tr-TR') + '</div>';
                
                if (extendedProps.konum) html += '<div class="mb-3"><strong>Konum:</strong> ' + extendedProps.konum + '</div>';
                if (extendedProps.aciklama) html += '<div class="mb-3"><strong>Açıklama:</strong><br>' + extendedProps.aciklama + '</div>';
                if (extendedProps.olusturan) html += '<div class="mb-3"><strong>Oluşturan:</strong> ' + extendedProps.olusturan + '</div>';
                
                modalBody.innerHTML = html;
                new bootstrap.Modal(document.getElementById('eventModal')).show();
            },
            eventDisplay: 'block',
            height: 'auto'
        });
        
        window.calendar.render();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
