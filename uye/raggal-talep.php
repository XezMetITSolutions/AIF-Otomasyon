<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireAuth();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Raggal Rezervasyon Takvimi';

$success = '';
$error = '';

// Form Gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslangic = $_POST['baslangic'] ?? '';
    $bitis = $_POST['bitis'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';

    if (empty($baslangic) || empty($bitis)) {
        $error = 'Lütfen başlangıç ve bitiş tarihlerini giriniz.';
    } else {
        try {
            $db->query(
                "INSERT INTO raggal_talepleri (kullanici_id, baslangic_tarihi, bitis_tarihi, aciklama) VALUES (?, ?, ?, ?)",
                [$user['id'], $baslangic, $bitis, $aciklama]
            );
            $success = 'Rezervasyon talebiniz başarıyla oluşturuldu.';
        } catch (Exception $e) {
            $error = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

// Takvim için mevcut rezervasyonları getir
$events = $db->fetchAll("
    SELECT 
        r.*, 
        CONCAT(u.ad, ' ', u.soyad) as title,
        CASE 
            WHEN r.durum = 'onaylandi' THEN '#28a745'
            WHEN r.durum = 'reddedildi' THEN '#dc3545'
            ELSE '#ffc107'
        END as color
    FROM raggal_talepleri r
    JOIN kullanicilar u ON r.kullanici_id = u.kullanici_id
");

// JSON formatına dönüştür
$calendarEvents = [];
foreach ($events as $event) {
    $calendarEvents[] = [
        'title' => $event['title'] . ' (' . ucfirst($event['durum']) . ')',
        'start' => $event['baslangic_tarihi'],
        'end' => $event['bitis_tarihi'],
        'color' => $event['color'],
        'textColor' => $event['durum'] === 'beklemede' ? '#000' : '#fff'
    ];
}

include __DIR__ . '/../includes/header.php';
?>

<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-calendar-alt me-2"></i>Raggal Rezervasyon Takvimi
            </h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reservationModal">
                <i class="fas fa-plus me-2"></i>Yeni Rezervasyon
            </button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div id='calendar' style="height: 700px;"></div>
            </div>
        </div>
    </div>
</main>

<!-- Rezervasyon Modal -->
<div class="modal fade" id="reservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Rezervasyon Talebi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Tarihi ve Saati</label>
                        <input type="datetime-local" class="form-control" name="baslangic" id="modal_start" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş Tarihi ve Saati</label>
                        <input type="datetime-local" class="form-control" name="bitis" id="modal_end" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama / Amaç</label>
                        <textarea class="form-control" name="aciklama" rows="3" placeholder="Rezervasyon amacı..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Talebi Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
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
            // Tarih seçildiğinde modalı aç ve tarihleri doldur
            var modal = new bootstrap.Modal(document.getElementById('reservationModal'));
            
            // Tarih formatını input datetime-local için ayarla (YYYY-MM-DDTHH:mm)
            var start = new Date(info.startStr);
            var end = new Date(info.endStr || info.startStr); // Bitiş yoksa başlangıcı kullan
            
            // Saat dilimi farkını düzelt (basitçe yerel ISO string al)
            start.setMinutes(start.getMinutes() - start.getTimezoneOffset());
            end.setMinutes(end.getMinutes() - end.getTimezoneOffset());
            
            document.getElementById('modal_start').value = start.toISOString().slice(0, 16);
            document.getElementById('modal_end').value = end.toISOString().slice(0, 16);
            
            modal.show();
        }
    });
    calendar.render();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
