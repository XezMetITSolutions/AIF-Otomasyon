<?php
/**
 * Raggal Rezervasyon Talepleri
 * - Üye: Takvim görünümü, yeni rezervasyon
 * - Başkan: Rezervasyon onayı/reddi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireAuth();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$hasPermissionBaskan = $auth->hasModulePermission('baskan_raggal_talepleri');
$hasPermissionUye = true; // Raggal modülü genel erişime açık (veya 'uye_raggal_talep' yetkisi kontrol edilebilir)

$activeTab = $_GET['tab'] ?? 'takvim'; // Varsayılan: Takvim
// Eğer yönetici ise ve özellikle yönetim sekmesi istenmişse oraya git, yoksa takvime.

$pageTitle = 'Raggal Rezervasyonları';
$messages = [];
$errors = [];

// === POST İŞLEMLERİ ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Yeni Rezervasyon (Üye)
    if ($action === 'create_reservation') {
        $baslangic = $_POST['baslangic'] ?? '';
        $bitis = $_POST['bitis'] ?? '';
        $aciklama = trim($_POST['aciklama'] ?? '');

        if (!$baslangic || !$bitis) {
            $errors[] = 'Başlangıç ve bitiş tarihlerini giriniz.';
        } else {
            try {
                // Çakışma kontrolü yapılabilir (şimdilik basit insert)
                $db->query(
                    "INSERT INTO raggal_talepleri (kullanici_id, baslangic_tarihi, bitis_tarihi, aciklama, durum) VALUES (?, ?, ?, ?, 'bekliyor')",
                    [$user['id'], $baslangic, $bitis, $aciklama]
                );
                $messages[] = 'Rezervasyon talebiniz oluşturuldu, onay bekleniyor.';
            } catch (Exception $e) {
                $errors[] = 'Hata: ' . $e->getMessage();
            }
        }
    }

    // 2. Onay/Red (Yönetici)
    if (($action === 'approve' || $action === 'reject') && $hasPermissionBaskan) {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $status = ($action === 'approve') ? 'onaylandi' : 'reddedildi';
            $db->query("UPDATE raggal_talepleri SET durum = ? WHERE id = ?", [$status, $id]);
            $messages[] = 'Rezervasyon durumu güncellendi: ' . ucfirst($status);
            $activeTab = 'yonetim'; // İşlem sonrası yönetim sekmesinde kal
        }
    }
}

// === VERİLER ===

// 1. Takvim İçin Eventler (Onaylılar yeşil, bekleyenler sarı, reddedilenler kırmızı)
// Sadece Onaylıları ve Bekleyenleri takvimde gösterelim mi?
// Reddedilenleri takvimde göstermeye gerek yok (veya user kendine aitse görsün?)
// Şimdilik herkese açık: Onaylı ve Bekleyenler.
$events = $db->fetchAll("
    SELECT 
        r.*, 
        CONCAT(u.ad, ' ', u.soyad) as title,
        CASE 
            WHEN r.durum = 'onaylandi' THEN '#10b981'
            WHEN r.durum = 'reddedildi' THEN '#ef4444'
            ELSE '#f59e0b'
        END as color
    FROM raggal_talepleri r
    JOIN kullanicilar u ON r.kullanici_id = u.kullanici_id
    WHERE r.durum != 'reddedildi' OR r.kullanici_id = ?
", [$user['id']]);

$calendarEvents = [];
foreach ($events as $event) {
    // Sadece kendi redlerimi göreyim, başkasının reddini görmeme gerek yok.
    if ($event['durum'] === 'reddedildi' && $event['kullanici_id'] != $user['id']) continue;

    $calendarEvents[] = [
        'title' => $event['title'] . ' (' . ucfirst($event['durum']) . ')',
        'start' => $event['baslangic_tarihi'],
        'end' => $event['bitis_tarihi'],
        'color' => $event['color'],
        'textColor' => '#fff',
        'allDay' => false
    ];
}

// 2. Yönetim Listesi
$pendingRequests = []; 
if ($hasPermissionBaskan) {
    $pendingRequests = $db->fetchAll("
        SELECT r.*, CONCAT(u.ad, ' ', u.soyad) as kullanici_adi 
        FROM raggal_talepleri r
        JOIN kullanicilar u ON r.kullanici_id = u.kullanici_id 
        ORDER BY r.created_at DESC
        LIMIT 100
    ");
}

include __DIR__ . '/../includes/header.php';
?>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/locales/tr.global.min.js'></script>
<style>
    .nav-pills .nav-link { color: #495057; font-weight: 500; padding: 0.75rem 1.25rem; border-radius: 0.75rem; }
    .nav-pills .nav-link.active { background-color: #009872; color: white; }
    .fc-event { cursor: pointer; border: none; }
    .fc-toolbar-title { font-size: 1.25rem !important; }
    .fc-button { background-color: #009872 !important; border-color: #009872 !important; }
    .fc-button:hover { background-color: #007a5e !important; border-color: #007a5e !important; }
</style>

<div class="dashboard-layout">
    <div class="sidebar-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?></div>
    <main class="main-content">
        <div class="content-wrapper">
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                     <h1 class="h3 mb-1"><i class="fas fa-calendar-check me-2"></i>Raggal Rezervasyon</h1>
                     <p class="text-muted mb-0">Tesis rezervasyon durumu ve talep işlemleri.</p>
                </div>
                
                 <ul class="nav nav-pills bg-white p-1 rounded-4 border shadow-sm">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activeTab === 'takvim') ? 'active' : ''; ?>" href="?tab=takvim">
                            <i class="fas fa-calendar-alt me-2"></i>Takvim & Talep
                        </a>
                    </li>
                    <?php if ($hasPermissionBaskan): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activeTab === 'yonetim') ? 'active' : ''; ?>" href="?tab=yonetim">
                            <i class="fas fa-tasks me-2"></i>Yönetim
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if (!empty($messages)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php foreach($messages as $m) echo "<div>$m</div>"; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="tab-content">
                
                <!-- TAB 1: TAKVİM (HERKES) -->
                <div class="tab-pane fade <?php echo ($activeTab === 'takvim') ? 'show active' : ''; ?>">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Rezervasyon Durumu</h6>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reservationModal">
                                <i class="fas fa-plus me-1"></i>Yeni Rezervasyon
                            </button>
                        </div>
                        <div class="card-body p-3">
                             <div id='calendar' style="min-height: 600px;"></div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: YÖNETİM (BAŞKAN) -->
                <?php if ($hasPermissionBaskan): ?>
                <div class="tab-pane fade <?php echo ($activeTab === 'yonetim') ? 'show active' : ''; ?>">
                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Zaman</th>
                                        <th>Kullanıcı</th>
                                        <th>Açıklama</th>
                                        <th>Durum</th>
                                        <th class="text-end">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pendingRequests)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Kayıt bulunamadı.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($pendingRequests as $req): ?>
                                        <tr>
                                            <td>
                                                <div class="small fw-bold text-dark">
                                                    <?php echo date('d.m.Y H:i', strtotime($req['baslangic_tarihi'])); ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?php echo date('d.m.Y H:i', strtotime($req['bitis_tarihi'])); ?>
                                                    (<?php 
                                                        $diff = strtotime($req['bitis_tarihi']) - strtotime($req['baslangic_tarihi']); 
                                                        echo round($diff / 3600, 1);
                                                    ?> saat)
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($req['kullanici_adi']); ?></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($req['aciklama']); ?></small></td>
                                            <td>
                                                <?php if($req['durum']=='bekliyor'): ?><span class="badge bg-warning text-dark">Bekliyor</span>
                                                <?php elseif($req['durum']=='onaylandi'): ?><span class="badge bg-success">Onaylandı</span>
                                                <?php else: ?><span class="badge bg-danger">Reddedildi</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($req['durum'] === 'bekliyor'): ?>
                                                    <form method="POST" class="d-inline-block">
                                                        <input type="hidden" name="action" value="">
                                                        <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                                        <button type="submit" onclick="this.form.action.value='approve'" class="btn btn-sm btn-success" title="Onayla"><i class="fas fa-check"></i></button>
                                                        <button type="submit" onclick="this.form.action.value='reject'" class="btn btn-sm btn-danger" title="Reddet"><i class="fas fa-times"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </main>
</div>

<!-- Modal -->
<div class="modal fade" id="reservationModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create_reservation">
            <div class="modal-header">
                <h5 class="modal-title">Rezervasyon Yap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Başlangıç</label>
                    <input type="datetime-local" name="baslangic" id="modal_start" class="form-control" require>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Bitiş</label>
                    <input type="datetime-local" name="bitis" id="modal_end" class="form-control" required>
                </div>
                 <div class="mb-3">
                    <label class="form-label small fw-bold">Açıklama</label>
                    <textarea name="aciklama" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Gönder</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'tr',
            selectable: true,
            events: <?php echo json_encode($calendarEvents); ?>,
            select: function(info) {
                var modal = new bootstrap.Modal(document.getElementById('reservationModal'));
                
                var start = new Date(info.startStr);
                var end = new Date(info.endStr || info.startStr);
                
                // UTC offset fix
                start.setMinutes(start.getMinutes() - start.getTimezoneOffset());
                end.setMinutes(end.getMinutes() - end.getTimezoneOffset());
                
                document.getElementById('modal_start').value = start.toISOString().slice(0, 16);
                document.getElementById('modal_end').value = end.toISOString().slice(0, 16);
                
                modal.show();
            }
        });
        calendar.render();
        
        // Tab değişince takvimi güncelle (boyutlandırma sorunu için)
        var tabEl = document.querySelector('a[href="?tab=takvim"]');
        if (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (event) {
                calendar.render();
            });
        }
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
