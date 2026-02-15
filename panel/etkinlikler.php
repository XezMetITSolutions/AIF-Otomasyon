<?php
/**
 * Çalışma Takvimi (Ortak Panel)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';


// Everyone can view, but managing requires permission
// Middleware::requireModulePermission('baskan_etkinlikler'); 

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

// Filtreleme
$search = $_GET['search'] ?? '';
$monthFilter = $_GET['ay'] ?? '';
$yearFilter = $_GET['yil'] ?? '';

// Permission check for management actions
$canManage = $auth->hasModulePermission('baskan_etkinlikler');

// Herkes bütün birimlerin etkinliklerini görebilir
$where = [];
$params = [];

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

// Birim Filtresi (Herkes için)
$birimFilter = $_GET['birim'] ?? '';
if ($birimFilter) {
    $where[] = "b.byk_kodu = ?";
    $params[] = $birimFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Check if user is Super Admin for full filter access
$isAdmin = $auth->isSuperAdmin();
$userByk = $db->fetch("SELECT b.* FROM byk b JOIN kullanicilar k ON b.byk_id = k.byk_id WHERE k.kullanici_id = ?", [$user['id']]);
$userBykKodu = $userByk['byk_kodu'] ?? '';

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
        } catch (Exception $e) { /* Ignore */
        }
    }
} catch (Exception $e) {
    $etkinlikler = [];
}

if (!is_array($etkinlikler)) {
    $etkinlikler = [];
}

// Calendar Events mapping
$calendarEvents = [];
foreach ($etkinlikler as $etkinlik) {
    if (empty($etkinlik['baslangic_tarihi']) || empty($etkinlik['bitis_tarihi']) || empty($etkinlik['baslik'])) {
        continue;
    }

    try {
        $baslangic = new DateTime($etkinlik['baslangic_tarihi']);
        $bitis = new DateTime($etkinlik['bitis_tarihi']);
    } catch (Exception $e) {
        continue;
    }

    $bykRenk = $etkinlik['byk_renk'] ?? '#009872';
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
        ],
        // Extra fields for list view rendering in JS (if needed)
        '_raw' => [
            'baslangic_format' => date('d.m.Y', strtotime($etkinlik['baslangic_tarihi'])),
            'baslangic_saat' => date('H:i', strtotime($etkinlik['baslangic_tarihi'])),
            'bitis_saat' => date('H:i', strtotime($etkinlik['bitis_tarihi'])),
            'id' => $etkinlik['etkinlik_id']
        ]
    ];
}

// AJAX RESPONSE
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'events' => $calendarEvents,
        'count' => count($etkinlikler),
        'canManage' => $canManage
    ]);
    exit;
}

$pageTitle = 'Çalışma Takvimi';
include __DIR__ . '/../includes/header.php';
?>

<!-- FullCalendar Library -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>

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

    #calendarLoadingOverlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.7);
        z-index: 10;
        display: none;
        align-items: center;
        justify-content: center;
        border-radius: 1rem;
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
                    <a href="/api/export-calendar.php?<?php echo http_build_query($_GET); ?>"
                        class="btn btn-outline-success" id="exportBtn">
                        <i class="fas fa-file-export me-2"></i>Takvime Aktar
                    </a>
                    <?php if ($canManage): ?>
                        <a href="/panel/etkinlik-ekle.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Yeni Etkinlik Ekle
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>İşlem başarıyla tamamlandı.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtreler -->
            <div class="card mb-4 position-relative">
                <div id="filterLoading"
                    style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.6); z-index:5; border-radius:1rem;">
                </div>
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Arama</label>
                            <input type="text" class="form-control" name="search" id="inputSearch"
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Etkinlik adı, açıklama...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ay</label>
                            <select class="form-select" name="ay" id="selectMonth">
                                <option value="">Tüm Aylar</option>
                                <?php
                                $aylar = [
                                    1 => 'Ocak',
                                    2 => 'Şubat',
                                    3 => 'Mart',
                                    4 => 'Nisan',
                                    5 => 'Mayıs',
                                    6 => 'Haziran',
                                    7 => 'Temmuz',
                                    8 => 'Ağustos',
                                    9 => 'Eylül',
                                    10 => 'Ekim',
                                    11 => 'Kasım',
                                    12 => 'Aralık'
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
                            <input type="number" class="form-control" name="yil" id="inputYear"
                                value="<?php echo htmlspecialchars($yearFilter); ?>" min="2020" max="2030">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filtrele
                            </button>
                            <button type="button" class="btn btn-secondary" id="resetFilters">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="col-12 border-top pt-3 mt-3">
                            <label class="form-label d-block text-muted small fw-bold mb-2">BİRİM FİLTRESİ (Tüm
                                Birimler)</label>
                            <div class="btn-group flex-wrap" role="group">
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary unit-filter <?php echo $birimFilter == '' ? 'active' : ''; ?>"
                                    data-unit="">Tümü</button>
                                <?php
                                $availableTypes = $db->fetchAll("SELECT DISTINCT byk_kodu FROM byk WHERE byk_kodu IN ('AT', 'KGT', 'KT', 'GT') ORDER BY FIELD(byk_kodu, 'AT', 'KGT', 'KT', 'GT')");
                                foreach ($availableTypes as $type):
                                    // Restrict filters for non-admins
                                    if (!$isAdmin && $type['byk_kodu'] !== $userBykKodu) continue;
                                    
                                    $label = $type['byk_kodu'];
                                    $isActive = ($birimFilter == $type['byk_kodu']);
                                    ?>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary unit-filter <?php echo $isActive ? 'active' : ''; ?>"
                                        data-unit="<?php echo htmlspecialchars($label); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="birim" id="inputBirim"
                                value="<?php echo htmlspecialchars($birimFilter); ?>">
                        </div>
                    </form>
                </div>
            </div>

            <!-- Görünüm Seçici -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-info me-2">Toplam: <strong
                                    id="totalCount"><?php echo count($etkinlikler); ?></strong> etkinlik</span>
                            <span id="loadingSpinner" class="spinner-border spinner-border-sm text-primary ms-2"
                                style="display:none;"></span>
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
                <div id="calendarLoadingOverlay" style="display:none;">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div class="card-body">
                    <div id="calendar" style="min-height: 600px;"></div>
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
                                    <?php if ($canManage): ?>
                                        <th>İşlemler</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="eventListBody">
                                <!-- JS Populated -->
                                <?php if (empty($etkinlikler)): ?>
                                    <tr>
                                        <td colspan="<?php echo $canManage ? 5 : 4; ?>" class="text-center text-muted">Henüz
                                            etkinlik eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($etkinlikler as $e): ?>
                                        <!-- Initial Render (Server Side) -->
                                        <tr>
                                            <td>
                                                <strong><?php echo date('d.m.Y', strtotime($e['baslangic_tarihi'])); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php
                                                    $s = date('H:i', strtotime($e['baslangic_tarihi']));
                                                    $end = date('H:i', strtotime($e['bitis_tarihi']));
                                                    echo ($s != '00:00' || $end != '23:59') ? "$s - $end" : 'Tüm gün';
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($e['baslik']); ?></strong>
                                                <?php if (!empty($e['aciklama'])): ?>
                                                    <br><small class="text-muted text-truncate d-inline-block"
                                                        style="max-width:200px;"><?php echo htmlspecialchars(substr($e['aciklama'], 0, 50)); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo !empty($e['konum']) ? htmlspecialchars($e['konum']) : '-'; ?></td>
                                            <td><?php echo !empty($e['olusturan']) ? htmlspecialchars($e['olusturan']) : '-'; ?>
                                            </td>
                                            <?php if ($canManage): ?>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="/panel/baskan_etkinlik-ekle.php?id=<?php echo $e['etkinlik_id']; ?>"
                                                            class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger confirm-delete"
                                                            data-id="<?php echo $e['etkinlik_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($e['baslik']); ?>"><i
                                                                class="fas fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
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
    function initCalendar() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl || calendarEl.dataset.initialized === 'true') return;
        calendarEl.dataset.initialized = 'true';
        
        console.log('AIF: Takvim modülü başlatılıyor...');

        const calendarViewBtn = document.getElementById('calendarViewBtn');
        const listViewBtn = document.getElementById('listViewBtn');
        const calendarView = document.getElementById('calendarView');
        const listView = document.getElementById('listView');

        if (calendarViewBtn && listViewBtn) {
            calendarViewBtn.onclick = () => {
                calendarViewBtn.classList.add('active');
                listViewBtn.classList.remove('active');
                calendarView.classList.remove('d-none');
                listView.classList.add('d-none');
                if (window.calendar) window.calendar.render();
            };

            listViewBtn.onclick = () => {
                listViewBtn.classList.add('active');
                calendarViewBtn.classList.remove('active');
                listView.classList.remove('d-none');
                calendarView.classList.add('d-none');
            };
        }

        // Calendar Init
        const initialEvents = <?php echo json_encode($calendarEvents, JSON_UNESCAPED_UNICODE) ?: '[]'; ?>;

        if (calendarEl) {
            try {
                if (typeof FullCalendar !== 'undefined') {
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
                        events: initialEvents,
                        eventClick: function (info) {
                            showEventModal(info.event);
                        },
                        eventDisplay: 'block',
                        height: 'auto'
                    });
                    window.calendar.render();
                } else {
                    console.error('FullCalendar library not loaded.');
                    calendarEl.innerHTML = '<div class="alert alert-danger">Takvim kütüphanesi yüklenemedi.</div>';
                }
            } catch (e) {
                console.error('Calendar init error:', e);
                calendarEl.innerHTML = '<div class="alert alert-danger">Takvim yüklenirken hata oluştu.</div>';
            }
        }
    }

    // Auto-init for SPA
    (function aggroCalendar() {
        initCalendar();
        let att = 0;
        const i = setInterval(() => {
            att++;
            const c = document.getElementById('calendar');
            if (c && c.dataset.initialized === 'true') clearInterval(i);
            else if (att > 20) clearInterval(i);
            else initCalendar();
        }, 500);
    })();

    function showEventModal(event) {
        const props = event.extendedProps;
        const modalBody = document.getElementById('eventModalBody');
        document.getElementById('eventModalLabel').textContent = event.title;

        let html = `
            <div class="mb-3"><strong>Başlık:</strong> ${event.title}</div>
            ${props.byk ? `<div class="mb-3"><strong>BYK:</strong> <span class="badge" style="background-color: ${event.backgroundColor};">${props.byk}</span></div>` : ''}
            <div class="mb-3"><strong>Başlangıç:</strong> ${event.start ? event.start.toLocaleString('tr-TR') : ''}</div>
            <div class="mb-3"><strong>Bitiş:</strong> ${event.end ? event.end.toLocaleString('tr-TR') : ''}</div>
            ${props.konum ? `<div class="mb-3"><strong>Konum:</strong> ${props.konum}</div>` : ''}
            ${props.aciklama ? `<div class="mb-3"><strong>Açıklama:</strong><br>${props.aciklama}</div>` : ''}
            ${props.olusturan ? `<div class="mb-3"><strong>Oluşturan:</strong> ${props.olusturan}</div>` : ''}
         `;

            if (<?php echo $canManage ? 'true' : 'false'; ?>) {
                html += `<div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
                <a href="/panel/etkinlik-ekle.php?id=${event.id}" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Düzenle</a>
             </div>`;
            }

            modalBody.innerHTML = html;
            new bootstrap.Modal(document.getElementById('eventModal')).show();
        }

        // --- AJAX FILTER LOGIC ---

        const filterForm = document.getElementById('filterForm');
        const unitButtons = document.querySelectorAll('.unit-filter');
        const inputBirim = document.getElementById('inputBirim');
        const resetBtn = document.getElementById('resetFilters');

        // Unit Buttons functionality
        unitButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                // UI Update
                unitButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Value Update
                if (inputBirim) inputBirim.value = this.dataset.unit;

                // Fetch
                fetchEvents();
            });
        });

        // Reset Button
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                document.getElementById('inputSearch').value = '';
                document.getElementById('selectMonth').value = '';
                document.getElementById('inputYear').value = new Date().getFullYear();
                if (inputBirim) inputBirim.value = '';

                unitButtons.forEach(b => b.classList.toggle('active', b.dataset.unit === ''));

                fetchEvents();
            });
        }

        // Form Submit
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            fetchEvents();
        });

        async function fetchEvents() {
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);
            params.append('ajax', '1');

            // Show Loaders
            const loader = document.getElementById('calendarLoadingOverlay');
            if (loader) loader.style.display = 'flex';
            document.getElementById('loadingSpinner').style.display = 'inline-block';

            // Update URL (PushState) without reloading
            const cleanParams = new URLSearchParams(formData);
            // Remove empty
            for (const [key, value] of [...cleanParams]) {
                if (value === '') cleanParams.delete(key);
            }
            const newUrl = window.location.pathname + '?' + cleanParams.toString();
            window.history.pushState({}, '', newUrl);

            // Update Export Link
            const exportBtn = document.getElementById('exportBtn');
            if (exportBtn) exportBtn.href = '/api/export-calendar.php?' + cleanParams.toString();

            try {
                const response = await fetch('/panel/etkinlikler.php?' + params.toString());
                const data = await response.json();

                // Update Calendar
                if (window.calendar) {
                    window.calendar.removeAllEvents();
                    window.calendar.addEventSource(data.events);
                }

                // Update Count
                document.getElementById('totalCount').innerText = data.count;

                // Update List View
                updateListView(data.events, data.canManage);

            } catch (error) {
                console.error('Error fetching events:', error);
                alert('Etkinlikler yüklenirken bir sorun oluştu.');
            } finally {
                const loader = document.getElementById('calendarLoadingOverlay');
                if (loader) loader.style.display = 'none';
                document.getElementById('loadingSpinner').style.display = 'none';
            }
        }

        function updateListView(events, canManage) {
            const tbody = document.getElementById('eventListBody');
            tbody.innerHTML = '';

            if (events.length === 0) {
                const colspan = canManage ? 5 : 4;
                tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-muted">Etkinlik bulunamadı.</td></tr>`;
                return;
            }

            // Sort events by date for list view (Calendar events object comes unsorted from JSON sometimes?)
            // Actually PHP sorted them by SQL, so array order should be preserved.

            events.forEach(e => {
                const raw = e._raw;
                const props = e.extendedProps;
                // Date formatting is simpler if we use logic similar to PHP or raw values
                const tr = document.createElement('tr');

                let actions = '';
                if (canManage) {
                    actions = `
                <td>
                    <div class="btn-group">
                        <a href="/panel/etkinlik-ekle.php?id=${raw.id}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <button type="button" class="btn btn-sm btn-outline-danger confirm-delete" data-id="${raw.id}" data-name="${e.title}"><i class="fas fa-trash"></i></button>
                    </div>
                </td>`;
                }

                let timeStr = (raw.baslangic_saat !== '00:00' || raw.bitis_saat !== '23:59')
                    ? `${raw.baslangic_saat} - ${raw.bitis_saat}`
                    : 'Tüm gün';

                tr.innerHTML = `
                <td>
                   <strong>${raw.baslangic_format}</strong><br>
                   <small class="text-muted">${timeStr}</small>
                </td>
                <td>
                   <strong>${e.title}</strong>
                   ${props.aciklama ? `<br><small class="text-muted text-truncate d-inline-block" style="max-width:200px;">${props.aciklama.substring(0, 50)}</small>` : ''}
                </td>
                <td>${props.konum || '-'}</td>
                <td>${props.olusturan || '-'}</td>
                ${actions}
            `;
                tbody.appendChild(tr);
            });

            // Re-bind delete events if needed (since they are new DOM elements)
            // You might need a global delegate listener for dynamic content or re-bind.
            // Assuming your main script uses delegates, if not, adding a quick delegate here:
        }

        // Global Delegate for Delete Buttons in the table
        document.getElementById('eventListBody').addEventListener('click', function (e) {
            const btn = e.target.closest('.confirm-delete');
            if (btn) {
                // Trigger your global delete confirmation logic here or standard alert
                if (confirm(btn.dataset.name + ' etkinliğini silmek istediğinize emin misiniz?')) {
                    // Proceed with delete... URL or Form submit
                }
            }
        });

    // Initialize on load and on SPA navigation
    document.addEventListener('DOMContentLoaded', initCalendar);
    $(document).on('page:loaded', initCalendar);
    
    // Immediate call if page:loaded already fired or if script is appended late
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initCalendar();
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>