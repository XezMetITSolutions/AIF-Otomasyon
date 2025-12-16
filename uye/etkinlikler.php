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

$pageTitle = 'Takvim ve Etkinlikler';
$userBykId = $user['byk_id'];

// Eğer üyenin BYK kaydı yoksa uyarı ver
if (!$userBykId) {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<main class="container-fluid mt-4"><div class="content-wrapper"><div class="alert alert-warning">Etkinlikleri görüntülemek için bir BYK birimine atanmış olmanız gerekir.</div></div></main>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Filtreleme Parametreleri
$search = $_GET['search'] ?? '';
$monthFilter = $_GET['ay'] ?? '';
$yearFilter = $_GET['yil'] ?? date('Y');

// Sorgu Koşulları
$where = ["e.byk_id = ?"];
$params = [$userBykId];

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

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Etkinlikleri Çek
$etkinlikler = [];
try {
    // Üye ekranında detaylı BYK verisine veya oluşturan kişi detayına derinlemesine ihtiyaç yok,
    // ancak admin görünümüyle tutarlılık için benzer yapıyı koruyoruz.
    $etkinlikler = $db->fetchAll("
        SELECT e.*, 
               'Size Özel' as byk_adi, 
               '' as byk_kodu,
               COALESCE(e.renk_kodu, '#009872') as byk_renk
        FROM etkinlikler e
        $whereClause
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 500
    ", $params);

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
                'aciklama' => $etkinlik['aciklama'] ?? ''
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

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>Etkinlik Takvimi
                </h1>
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

<!-- Etkinlik Detay Modal -->
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
