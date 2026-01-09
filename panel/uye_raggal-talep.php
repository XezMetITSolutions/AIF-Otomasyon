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

<!-- Modern Design Assets -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />

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
</div>

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
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/locales/tr.global.min.js'></script>
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
