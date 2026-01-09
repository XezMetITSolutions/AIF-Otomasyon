<?php
/**
 * Üye - Etkinlikler (Takvim ve Liste Görünümü)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireUye();
Middleware::requireModulePermission('uye_etkinlikler');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Çalışma Takvimi';
$userBykId = $user['byk_id'];

// Eğer üyenin BYK kaydı yoksa uyarı ver
if (!$userBykId) {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<main class="container-fluid mt-4"><div class="content-wrapper"><div class="alert alert-warning">Etkinlikleri görüntülemek için bir BYK birimine atanmış olmanız gerekir.</div></div></main>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// BYK Koduna göre kontrol
$userBykCode = '';
// Fetch user's BYK code
if ($userBykId) {
    try {
        $bykInfo = $db->fetch("SELECT byk_kodu FROM byk WHERE byk_id = ?", [$userBykId]);
        if ($bykInfo) {
            $userBykCode = $bykInfo['byk_kodu'];
        }
    } catch (Exception $e) {}
}

// Filtreleme Parametreleri
$search = $_GET['search'] ?? '';
$monthFilter = $_GET['ay'] ?? '';
$yearFilter = $_GET['yil'] ?? '';
$bykFilter = $_GET['byk_id'] ?? ''; // AT üyeleri için birim filtresi

// Sorgu Koşulları
$where = [];
$params = [];

// AT birimi için tümünü gör, diğerleri sadece kendi birimini görsün
if ($userBykCode !== 'AT') {
    $where[] = "e.byk_id = ?";
    $params[] = $userBykId;
} elseif ($bykFilter) {
    // AT üyesi filtreleme yaparsa
    $where[] = "e.byk_id = ?";
    $params[] = $bykFilter;
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

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Etkinlikleri Çek
$etkinlikler = [];
try {
    // BYK bilgisi ile birleştirerek çek
    $sql = "
        SELECT e.*, 
               COALESCE(b.byk_adi, 'Genel') as byk_adi, 
               COALESCE(b.byk_kodu, '') as byk_kodu,
               COALESCE(e.renk_kodu, '#009872') as byk_renk
        FROM etkinlikler e
        LEFT JOIN byk b ON e.byk_id = b.byk_id
        $whereClause
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 500
    ";
    
    $etkinlikler = $db->fetchAll($sql, $params);

} catch (Exception $e) {
    $etkinlikler = [];
    error_log("Üye etkinlikleri çekilirken hata: " . $e->getMessage());
}

// FullCalendar Event Formatına Dönüştürme
$calendarEvents = [];
if (!empty($etkinlikler)) {
    foreach ($etkinlikler as $etkinlik) {
        // Tarih kontrolü
        if (empty($etkinlik['baslangic_tarihi']) || empty($etkinlik['bitis_tarihi'])) continue;

        try {
            $baslangic = new DateTime($etkinlik['baslangic_tarihi']);
            $bitis = new DateTime($etkinlik['bitis_tarihi']);
        } catch (Exception $e) { continue; }

        $renk = $etkinlik['renk_kodu'] ?: '#009872';

        $calendarEvents[] = [
            'id' => $etkinlik['etkinlik_id'],
            'title' => $etkinlik['baslik'],
            'start' => $baslangic->format('Y-m-d\TH:i:s'),
            'end' => $bitis->format('Y-m-d\TH:i:s'),
            'allDay' => ($baslangic->format('H:i') == '00:00' && $bitis->format('H:i') == '23:59'),
            'backgroundColor' => $renk,
            'borderColor' => $renk,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'konum' => $etkinlik['konum'] ?? '',
                'aciklama' => $etkinlik['aciklama'] ?? '',
                'byk_adi' => $etkinlik['byk_adi'] ?? ''
            ]
        ];
    }
}

// FullCalendar Assets
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
    
    .content-wrapper {
        background: transparent !important;
        box-shadow: none !important;
        max-width: 1400px;
        margin: 0 auto;
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
        font-weight: 600;
        color: var(--text-dark);
    }

    /* Badge tweaks */
    .badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
        border-radius: 6px;
    }

    /* CSS Overrides for Mobile Layout Fix */
    .dashboard-layout {
        display: block;
        /* margin-top: 56px; handled by global body padding */
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
                <h1 class="h3 mb-0 text-dark fw-bold">
                    <i class="fas fa-calendar-alt me-2 text-primary"></i>Çalışma Takvimi
                </h1>
                <a href="/api/export-calendar.php?<?php echo http_build_query($_GET); ?>" class="btn btn-outline-success">
                    <i class="fas fa-file-export me-2"></i>Takvime Aktar
                </a>
            </div>
            
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
                        <?php if ($userBykCode === 'AT'): 
                            $tumBirimler = $db->fetchAll("SELECT byk_id, byk_adi FROM byk ORDER BY byk_adi ASC");
                        ?>
                        <div class="col-md-2">
                            <label class="form-label">Birim</label>
                            <select class="form-select" name="byk_id">
                                <option value="">Tüm Birimler</option>
                                <?php foreach ($tumBirimler as $b): ?>
                                    <option value="<?php echo $b['byk_id']; ?>" <?php echo $bykFilter == $b['byk_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['byk_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-2">
                            <label class="form-label">Yıl</label>
                            <input type="number" class="form-control" name="yil" value="<?php echo htmlspecialchars($yearFilter); ?>" min="2020" max="2030">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filtrele
                            </button>
                            <?php if ($search || $monthFilter || $yearFilter != date('Y')): ?>
                                <a href="/uye/etkinlikler.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-times me-1"></i>Temizle
                                </a>
                            <?php endif; ?>
                        </div>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($etkinlikler)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Henüz etkinlik bulunmuyor.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($etkinlikler as $etkinlik): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('d.m.Y', strtotime($etkinlik['baslangic_tarihi'])); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($etkinlik['baslik']); ?></strong>
                                                <?php if (!empty($etkinlik['aciklama'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(mb_substr($etkinlik['aciklama'], 0, 80)); ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($etkinlik['konum']) ? htmlspecialchars($etkinlik['konum']) : '-'; ?>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Etkinlik Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <!-- İçerik JavaScript ile doldurulacak -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Görünüm değiştirme
    const calendarViewBtn = document.getElementById('calendarViewBtn');
    const listViewBtn = document.getElementById('listViewBtn');
    const calendarView = document.getElementById('calendarView');
    const listView = document.getElementById('listView');
    
    calendarViewBtn.addEventListener('click', function() {
        calendarViewBtn.classList.add('active');
        listViewBtn.classList.remove('active');
        calendarView.classList.remove('d-none');
        listView.classList.add('d-none');
        calendar.render(); // Takvim görünür olduğunda yeniden render et
    });
    
    listViewBtn.addEventListener('click', function() {
        listViewBtn.classList.add('active');
        calendarViewBtn.classList.remove('active');
        listView.classList.remove('d-none');
        calendarView.classList.add('d-none');
    });
    
    // Takvim verileri
    const calendarEvents = <?php echo json_encode($calendarEvents, JSON_UNESCAPED_UNICODE); ?>;
    
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'tr',
        firstDay: 1, // Pazartesi
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
            const props = event.extendedProps;
            
            const modalBody = document.getElementById('eventModalBody');
            const modalTitle = document.getElementById('eventModalLabel');
            
            modalTitle.textContent = event.title;
            
            let html = '<div class="mb-3"><strong>Tarih:</strong> ' + event.start.toLocaleDateString('tr-TR');
            if(event.end) {
                 html += ' - ' + event.end.toLocaleDateString('tr-TR');
            }
            html += '</div>';

            if (props.konum) {
                html += '<div class="mb-3"><strong>Konum:</strong> ' + props.konum + '</div>';
            }

            <?php if ($userBykCode === 'AT'): ?>
            // AT üyeleri için hangi birime ait olduğu
             if (event.extendedProps.byk_adi) {
                html += '<div class="mb-3"><strong>Birim:</strong> <span class="badge" style="background-color:' + event.backgroundColor + '">' + event.extendedProps.byk_adi + '</span></div>';
            }
            <?php endif; ?>
            
            if (props.aciklama) {
                html += '<div class="mb-3"><strong>Açıklama:</strong><p class="mt-1">' + props.aciklama.replace(/\n/g, '<br>') + '</p></div>';
            }
            
            modalBody.innerHTML = html;
            
            const modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();
            
            info.jsEvent.preventDefault();
        },
        height: 'auto',
        contentHeight: 'auto'
    });
    
    calendar.render();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
