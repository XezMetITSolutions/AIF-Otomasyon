<?php
require_once 'auth.php';
require_once 'config.php';

// Login kontrolü
$currentUser = SessionManager::getCurrentUser();

// Veritabanı bağlantısı
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Exception $e) {
    $pdo = null;
}

// BYK birimlerini çek
$bykUnits = [];
if ($pdo) {
    try {
        $sql = "SELECT * FROM byk_units WHERE is_active = 1 ORDER BY code";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $bykUnits = $stmt->fetchAll();
    } catch (Exception $e) {
        $bykUnits = [];
    }
}

// Durum metinleri ve renkleri
$statusTexts = [
    'planned' => 'Planlandı',
    'ongoing' => 'Devam Ediyor',
    'completed' => 'Tamamlandı',
    'cancelled' => 'İptal Edildi'
];

$statusColors = [
    'planned' => 'warning',
    'ongoing' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
];

$priorityTexts = [
    'low' => 'Düşük',
    'medium' => 'Orta',
    'high' => 'Yüksek',
    'urgent' => 'Acil'
];

$priorityColors = [
    'low' => 'secondary',
    'medium' => 'primary',
    'high' => 'warning',
    'urgent' => 'danger'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Toplantı Yönetimi</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        /* Toplantı Yönetimi Özel Stilleri */
        .meeting-card {
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
            position: relative;
        }
        
        .meeting-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .byk-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .byk-at { background-color: #e3f2fd; color: #1976d2; }
        .byk-kt { background-color: #fce4ec; color: #c2185b; }
        .byk-kgt { background-color: #f3e5f5; color: #7b1fa2; }
        .byk-gt { background-color: #e8f5e8; color: #388e3c; }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }
        
        .priority-badge {
            font-size: 0.7rem;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
        }
        
        .meeting-stats {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .meeting-stats::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .meeting-stats h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .meeting-stats p {
            margin: 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .stats-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 3rem;
            opacity: 0.3;
        }
        
        .decision-item {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .decision-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .decision-item.completed {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .decision-item.urgent {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .agenda-item {
            border-left: 3px solid var(--primary-color);
            padding-left: 1rem;
            margin-bottom: 1rem;
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .participant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .filter-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .progress-ring {
            width: 60px;
            height: 60px;
            transform: rotate(-90deg);
        }
        
        .progress-ring-circle {
            fill: none;
            stroke: #e9ecef;
            stroke-width: 4;
        }
        
        .progress-ring-progress {
            fill: none;
            stroke: var(--primary-color);
            stroke-width: 4;
            stroke-linecap: round;
            transition: stroke-dasharray 0.3s ease;
        }
        
        .tab-content {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-height: 400px;
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 1rem 1.5rem;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background: none;
            border-bottom-color: var(--primary-color);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .meeting-timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .meeting-timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--primary-color);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.75rem;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--primary-color);
        }
        
        /* Tooltip Düzeltmeleri */
        .meeting-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 10;
            display: flex;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.95);
            padding: 0.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .meeting-card:hover .meeting-actions {
            opacity: 1;
        }
        
        .meeting-actions .btn {
            padding: 0.375rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            position: relative;
        }
        
        .meeting-actions .btn:hover {
            transform: scale(1.05);
        }
        
        /* Bootstrap tooltip override */
        .tooltip {
            z-index: 9999 !important;
        }
        
        .tooltip-inner {
            background-color: #333;
            color: white;
            font-size: 0.8rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            max-width: 200px;
        }
        
        .tooltip.bs-tooltip-top .tooltip-arrow::before {
            border-top-color: #333;
        }
        
        .tooltip.bs-tooltip-bottom .tooltip-arrow::before {
            border-bottom-color: #333;
        }
        
        .tooltip.bs-tooltip-start .tooltip-arrow::before {
            border-left-color: #333;
        }
        
        .tooltip.bs-tooltip-end .tooltip-arrow::before {
            border-right-color: #333;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-calendar-alt me-2"></i>Toplantı Yönetimi</h1>
                    <p>BYK toplantılarını planlayın, yürütün ve kararları takip edin</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddMeetingModal()">
                        <i class="fas fa-plus"></i> Yeni Toplantı
                    </button>
                    <button class="btn btn-outline-primary" onclick="exportMeetings()">
                        <i class="fas fa-download"></i> Dışa Aktar
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- İstatistikler -->
            <div class="row mb-4" id="meetingStats">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="meeting-stats">
                        <i class="fas fa-calendar-check stats-icon"></i>
                        <h3 id="totalMeetings">0</h3>
                        <p>Toplam Toplantı</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="meeting-stats" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-check-circle stats-icon"></i>
                        <h3 id="completedMeetings">0</h3>
                        <p>Tamamlanan</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="meeting-stats" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-play-circle stats-icon"></i>
                        <h3 id="ongoingMeetings">0</h3>
                        <p>Devam Eden</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="meeting-stats" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-exclamation-triangle stats-icon"></i>
                        <h3 id="pendingDecisions">0</h3>
                        <p>Bekleyen Karar</p>
                    </div>
                </div>
            </div>

            <!-- Filtreler -->
            <div class="filter-card">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-filter me-1"></i>BYK Seçin</label>
                        <select class="form-select" id="bykFilter">
                            <option value="">Tüm BYK'lar</option>
                            <?php foreach ($bykUnits as $unit): ?>
                            <option value="<?php echo $unit['code']; ?>"><?php echo $unit['code']; ?> - <?php echo $unit['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-flag me-1"></i>Durum</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">Tüm Durumlar</option>
                            <?php foreach ($statusTexts as $key => $text): ?>
                            <option value="<?php echo $key; ?>"><?php echo $text; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-calendar me-1"></i>Tarih Başlangıç</label>
                        <input type="date" class="form-control" id="dateFrom">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-calendar me-1"></i>Tarih Bitiş</label>
                        <input type="date" class="form-control" id="dateTo">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Filtrele
                        </button>
                        <button class="btn btn-outline-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Temizle
                        </button>
                        <button class="btn btn-outline-info" onclick="showPendingDecisions()">
                            <i class="fas fa-tasks"></i> Bekleyen Kararlar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Toplantı Listesi -->
            <div class="page-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-list me-2"></i>Toplantı Listesi</h5>
                    <div class="btn-group" role="group">
                        <button class="btn btn-outline-primary btn-sm" onclick="loadMeetings()">
                            <i class="fas fa-sync"></i> Yenile
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="meetingsContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Yükleniyor...</span>
                            </div>
                            <p class="mt-3 text-muted">Toplantılar yükleniyor...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Yeni Toplantı Modal -->
    <div class="modal fade" id="addMeetingModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Yeni Toplantı Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addMeetingForm">
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="meetingTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                                    <i class="fas fa-info-circle me-2"></i>Genel Bilgi
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="participants-tab" data-bs-toggle="tab" data-bs-target="#participants" type="button">
                                    <i class="fas fa-users me-2"></i>Katılımcılar
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="agenda-tab" data-bs-toggle="tab" data-bs-target="#agenda" type="button">
                                    <i class="fas fa-list me-2"></i>Gündem Maddeleri
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="meetingTabContent">
                            <!-- Genel Bilgi -->
                            <div class="tab-pane fade show active" id="general">
                                <div class="p-4">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Toplantı Başlığı <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="title" required placeholder="Örn: AT BYK Nisan Toplantısı">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">BYK Türü <span class="text-danger">*</span></label>
                                            <select class="form-select" name="byk" required onchange="loadParticipantsByBYK(this.value)">
                                                <option value="">BYK Seçin</option>
                                                <?php foreach ($bykUnits as $unit): ?>
                                                <option value="<?php echo $unit['code']; ?>"><?php echo $unit['code']; ?> - <?php echo $unit['name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tarih <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="date" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Başlangıç Saati <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" name="time" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Yer / Platform <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="location" required placeholder="AIF Genel Merkez veya Zoom linki">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Toplantı Türü</label>
                                            <select class="form-select" name="meeting_type">
                                                <option value="regular">Normal Toplantı</option>
                                                <option value="emergency">Acil Toplantı</option>
                                                <option value="special">Özel Toplantı</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Başkan <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="chairman" required placeholder="Toplantıyı yöneten kişi">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sekreter</label>
                                            <input type="text" class="form-control" name="secretary" placeholder="Tutanak sorumlusu">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Notlar</label>
                                        <textarea class="form-control" name="notes" rows="3" placeholder="Toplantı hakkında özel notlar..."></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Katılımcılar -->
                            <div class="tab-pane fade" id="participants">
                                <div class="p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6>Katılımcı Listesi</h6>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="toggleAddParticipantForm()">
                                            <i class="fas fa-plus"></i> Katılımcı Ekle
                                        </button>
                                    </div>
                                    
                                    <!-- Katılımcı Ekleme Formu -->
                                    <div id="addParticipantForm" class="card mb-3" style="display: none;">
                                        <div class="card-body">
                                            <h6 class="card-title">Yeni Katılımcı Ekle</h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <input type="text" class="form-control" id="participantName" placeholder="Katılımcı Adı">
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <select class="form-select" id="participantRole">
                                                        <option value="member">Üye</option>
                                                        <option value="chairman">Başkan</option>
                                                        <option value="secretary">Sekreter</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 mb-2">
                                                    <button type="button" class="btn btn-success w-100" onclick="addParticipantFromForm()">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="participantsList">
                                        <!-- Katılımcılar buraya eklenecek -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Gündem Maddeleri -->
                            <div class="tab-pane fade" id="agenda">
                                <div class="p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6>Gündem Maddeleri</h6>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="toggleAddAgendaForm()">
                                            <i class="fas fa-plus"></i> Gündem Ekle
                                        </button>
                                    </div>
                                    
                                    <!-- Gündem Ekleme Formu -->
                                    <div id="addAgendaForm" class="card mb-3" style="display: none;">
                                        <div class="card-body">
                                            <h6 class="card-title">Yeni Gündem Maddesi Ekle</h6>
                                            <div class="row">
                                                <div class="col-md-8 mb-2">
                                                    <input type="text" class="form-control" id="agendaTitle" placeholder="Gündem Maddesi Başlığı">
                                                </div>
                                                <div class="col-md-3 mb-2">
                                                    <input type="text" class="form-control" id="agendaResponsible" placeholder="Sorumlu Kişi">
                                                </div>
                                                <div class="col-md-1 mb-2">
                                                    <button type="button" class="btn btn-success w-100" onclick="addAgendaFromForm()">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="agendaItemsList">
                                        <!-- Gündem maddeleri buraya eklenecek -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Toplantı Oluştur
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toplantı Detay Modal -->
    <div class="modal fade" id="meetingDetailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Toplantı Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="detailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="detail-general-tab" data-bs-toggle="tab" data-bs-target="#detail-general" type="button">
                                <i class="fas fa-info-circle me-2"></i>Genel Bilgi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="detail-agenda-tab" data-bs-toggle="tab" data-bs-target="#detail-agenda" type="button">
                                <i class="fas fa-list me-2"></i>Gündem Maddeleri
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="detail-decisions-tab" data-bs-toggle="tab" data-bs-target="#detail-decisions" type="button">
                                <i class="fas fa-gavel me-2"></i>Kararlar & Görevler
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="detail-participants-tab" data-bs-toggle="tab" data-bs-target="#detail-participants" type="button">
                                <i class="fas fa-users me-2"></i>Katılımcılar
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="detailTabContent">
                        <!-- Genel Bilgi -->
                        <div class="tab-pane fade show active" id="detail-general">
                            <div class="p-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Toplantı Bilgileri</h6>
                                        <p><strong>Başlık:</strong> <span id="detailTitle">-</span></p>
                                        <p><strong>BYK:</strong> <span id="detailByk">-</span></p>
                                        <p><strong>Tarih:</strong> <span id="detailDate">-</span></p>
                                        <p><strong>Saat:</strong> <span id="detailTime">-</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Yer:</strong> <span id="detailLocation">-</span></p>
                                        <p><strong>Başkan:</strong> <span id="detailChairman">-</span></p>
                                        <p><strong>Sekreter:</strong> <span id="detailSecretary">-</span></p>
                                        <p><strong>Durum:</strong> <span id="detailStatus">-</span></p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <h6>Notlar</h6>
                                    <p id="detailNotes">-</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gündem Maddeleri -->
                        <div class="tab-pane fade" id="detail-agenda">
                            <div class="p-4">
                                <div id="detailAgendaList">
                                    <!-- Gündem maddeleri buraya yüklenecek -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Kararlar & Görevler -->
                        <div class="tab-pane fade" id="detail-decisions">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6>Kararlar & Görevler</h6>
                                    <button class="btn btn-primary btn-sm" onclick="addDecision()">
                                        <i class="fas fa-plus"></i> Karar Ekle
                                    </button>
                                </div>
                                <div id="detailDecisionsList">
                                    <!-- Kararlar buraya yüklenecek -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Katılımcılar -->
                        <div class="tab-pane fade" id="detail-participants">
                            <div class="p-4">
                                <div id="detailParticipantsList">
                                    <!-- Katılımcılar buraya yüklenecek -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-primary" onclick="generateMeetingReport()">
                        <i class="fas fa-file-pdf"></i> Rapor Oluştur
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bekleyen Kararlar Modal -->
    <div class="modal fade" id="pendingDecisionsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-tasks me-2"></i>Bekleyen Kararlar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="pendingDecisionsList">
                        <!-- Bekleyen kararlar buraya yüklenecek -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global değişkenler
        let meetings = [];
        let bykUnits = <?php echo json_encode($bykUnits); ?>;
        let statusTexts = <?php echo json_encode($statusTexts); ?>;
        let statusColors = <?php echo json_encode($statusColors); ?>;
        let priorityTexts = <?php echo json_encode($priorityTexts); ?>;
        let priorityColors = <?php echo json_encode($priorityColors); ?>;
        let currentMeetingId = null;
        
        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', function() {
            loadMeetingStats();
            loadMeetings();
        });
        
        // Toplantı istatistiklerini yükle
        function loadMeetingStats() {
            fetch('api/meeting_api.php?action=get_meeting_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalMeetings').textContent = data.data.total_meetings;
                        document.getElementById('completedMeetings').textContent = data.data.completed_meetings;
                        document.getElementById('ongoingMeetings').textContent = data.data.ongoing_meetings;
                        document.getElementById('pendingDecisions').textContent = data.data.pending_decisions;
                    }
                })
                .catch(error => {
                    console.error('Error loading stats:', error);
                });
        }
        
        // Toplantıları yükle
        function loadMeetings() {
            fetch('api/meeting_api.php?action=get_meetings')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        meetings = data.data;
                        renderMeetings(meetings);
                    } else {
                        showAlert('Toplantılar yüklenemedi: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error loading meetings:', error);
                    showAlert('Toplantılar yüklenirken hata oluştu', 'danger');
                });
        }
        
        // Toplantıları render et
        function renderMeetings(meetings) {
            const container = document.getElementById('meetingsContainer');
            
            if (meetings.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Henüz toplantı bulunmuyor</h4>
                        <p class="text-muted">İlk toplantınızı planlamak için yukarıdaki butonu kullanın.</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="row">';
            
            meetings.forEach(meeting => {
                const bykUnit = bykUnits.find(u => u.code === meeting.byk_code);
                const bykName = bykUnit ? bykUnit.name : meeting.byk_code;
                const bykColor = bykUnit ? bykUnit.color : '#007bff';
                
                html += `
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card meeting-card h-100">
                            <div class="meeting-actions">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="viewMeeting(${meeting.id})" title="Görüntüle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="editMeeting(${meeting.id})" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteMeeting(${meeting.id})" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="card-title mb-1">${meeting.title}</h6>
                                        <span class="byk-badge byk-${meeting.byk_code.toLowerCase()}" style="background-color: ${bykColor}20; color: ${bykColor};">
                                            ${meeting.byk_code}
                                        </span>
                                    </div>
                                    <span class="status-badge badge bg-${statusColors[meeting.status]}">
                                        ${statusTexts[meeting.status]}
                                    </span>
                                </div>
                                
                                <div class="meeting-info mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-calendar text-primary me-2"></i>
                                        <span>${formatDate(meeting.meeting_date)} - ${meeting.meeting_time}</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <span>${meeting.location}</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-user-tie text-primary me-2"></i>
                                        <span>${meeting.chairman}</span>
                                    </div>
                                </div>
                                
                                <div class="meeting-stats-small d-flex justify-content-between mb-3">
                                    <div class="text-center">
                                        <div class="fw-bold text-primary">${meeting.participant_count || 0}</div>
                                        <small class="text-muted">Katılımcı</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="fw-bold text-success">${meeting.agenda_count || 0}</div>
                                        <small class="text-muted">Gündem</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="fw-bold text-warning">${meeting.decision_count || 0}</div>
                                        <small class="text-muted">Karar</small>
                                    </div>
                                </div>
                                
                                ${(meeting.pending_decisions || 0) > 0 ? `
                                    <div class="alert alert-warning alert-sm mb-3">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        ${meeting.pending_decisions} bekleyen karar
                                    </div>
                                ` : ''}
                                
                                <div class="d-flex gap-1">
                                    <button class="btn btn-primary btn-sm flex-fill" onclick="viewMeeting(${meeting.id})">
                                        <i class="fas fa-eye me-1"></i>Detay
                                    </button>
                                    ${meeting.status === 'planned' ? `
                                        <button class="btn btn-success btn-sm" onclick="startMeeting(${meeting.id})">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        // Toplantı detayını görüntüle
        function viewMeeting(id) {
            fetch(`api/meeting_api.php?action=get_meeting&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const meeting = data.data;
                        currentMeetingId = id;
                        
                        // Genel bilgileri doldur
                        document.getElementById('detailTitle').textContent = meeting.title;
                        document.getElementById('detailByk').textContent = meeting.byk_code + ' - ' + (bykUnits.find(u => u.code === meeting.byk_code)?.name || meeting.byk_code);
                        document.getElementById('detailDate').textContent = formatDate(meeting.meeting_date);
                        document.getElementById('detailTime').textContent = meeting.meeting_time;
                        document.getElementById('detailLocation').textContent = meeting.location;
                        document.getElementById('detailChairman').textContent = meeting.chairman;
                        document.getElementById('detailSecretary').textContent = meeting.secretary || 'Belirtilmemiş';
                        document.getElementById('detailStatus').textContent = statusTexts[meeting.status];
                        document.getElementById('detailNotes').textContent = meeting.notes || 'Not bulunmuyor';
                        
                        // Katılımcıları render et
                        renderParticipants(meeting.participants || []);
                        
                        // Gündem maddelerini render et
                        renderAgendaItems(meeting.agenda || []);
                        
                        // Kararları render et
                        renderDecisions(meeting.decisions || []);
                        
                        // Modal'ı aç
                        new bootstrap.Modal(document.getElementById('meetingDetailModal')).show();
                    } else {
                        showAlert('Toplantı bulunamadı!', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Bir hata oluştu!', 'danger');
                });
        }
        
        // Katılımcıları render et
        function renderParticipants(participants) {
            const container = document.getElementById('detailParticipantsList');
            
            // Array kontrolü
            if (!Array.isArray(participants) || participants.length === 0) {
                container.innerHTML = '<p class="text-muted">Katılımcı bulunmuyor.</p>';
                return;
            }
            
            let html = '';
            participants.forEach(participant => {
                const statusClass = {
                    'attended': 'success',
                    'absent': 'danger',
                    'excused': 'warning',
                    'invited': 'secondary'
                }[participant.attendance_status] || 'secondary';
                
                const statusText = {
                    'attended': 'Katıldı',
                    'absent': 'Katılmadı',
                    'excused': 'Mazeretli',
                    'invited': 'Davetli'
                }[participant.attendance_status] || 'Bilinmiyor';
                
                html += `
                    <div class="d-flex align-items-center mb-2 p-2 border rounded">
                        <div class="participant-avatar me-3">
                            ${participant.participant_name.charAt(0)}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">${participant.participant_name}</div>
                            <small class="text-muted">${participant.participant_role}</small>
                        </div>
                        <span class="badge bg-${statusClass}">${statusText}</span>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Gündem maddelerini render et
        function renderAgendaItems(agendaItems) {
            const container = document.getElementById('detailAgendaList');
            
            // Array kontrolü
            if (!Array.isArray(agendaItems) || agendaItems.length === 0) {
                container.innerHTML = '<p class="text-muted">Gündem maddesi bulunmuyor.</p>';
                return;
            }
            
            let html = '';
            agendaItems.forEach(item => {
                const statusClass = {
                    'pending': 'warning',
                    'discussed': 'info',
                    'completed': 'success',
                    'postponed': 'secondary'
                }[item.status] || 'secondary';
                
                const statusText = {
                    'pending': 'Bekliyor',
                    'discussed': 'Tartışıldı',
                    'completed': 'Tamamlandı',
                    'postponed': 'Ertelendi'
                }[item.status] || 'Bilinmiyor';
                
                html += `
                    <div class="agenda-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${item.agenda_order}. ${item.title}</h6>
                                <p class="text-muted mb-1">${item.description || ''}</p>
                                <small class="text-muted">
                                    <strong>Sorumlu:</strong> ${item.responsible_person || '-'} | 
                                    <strong>Süre:</strong> ${item.estimated_duration} dk
                                </small>
                            </div>
                            <span class="badge bg-${statusClass}">${statusText}</span>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Kararları render et
        function renderDecisions(decisions) {
            const container = document.getElementById('detailDecisionsList');
            
            // Array kontrolü
            if (!Array.isArray(decisions) || decisions.length === 0) {
                container.innerHTML = '<p class="text-muted">Karar bulunmuyor.</p>';
                return;
            }
            
            let html = '';
            decisions.forEach(decision => {
                const statusClass = {
                    'pending': 'warning',
                    'in_progress': 'info',
                    'completed': 'success',
                    'cancelled': 'danger'
                }[decision.status] || 'secondary';
                
                const statusText = {
                    'pending': 'Bekliyor',
                    'in_progress': 'Devam Ediyor',
                    'completed': 'Tamamlandı',
                    'cancelled': 'İptal Edildi'
                }[decision.status] || 'Bilinmiyor';
                
                const priorityClass = priorityColors[decision.priority] || 'secondary';
                const priorityText = priorityTexts[decision.priority] || 'Bilinmiyor';
                
                const isOverdue = decision.deadline && new Date(decision.deadline) < new Date() && decision.status !== 'completed';
                
                html += `
                    <div class="decision-item ${decision.status === 'completed' ? 'completed' : ''} ${decision.priority === 'urgent' ? 'urgent' : ''}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${decision.decision_number}</h6>
                                <p class="mb-1">${decision.decision_text}</p>
                                <small class="text-muted">
                                    <strong>Sorumlu:</strong> ${decision.responsible_person} | 
                                    <strong>Termin:</strong> ${decision.deadline ? formatDate(decision.deadline) : '-'}
                                    ${isOverdue ? ' <span class="text-danger">(Gecikmiş)</span>' : ''}
                                </small>
                                ${decision.progress_notes ? `<p class="mt-2 text-muted"><em>${decision.progress_notes}</em></p>` : ''}
                            </div>
                            <div class="text-end">
                                <span class="badge bg-${priorityClass} mb-1">${priorityText}</span><br>
                                <span class="badge bg-${statusClass}">${statusText}</span>
                                ${decision.status !== 'completed' ? `
                                    <div class="mt-2">
                                        <button class="btn btn-success btn-sm" onclick="updateDecisionStatus(${decision.id}, 'completed')" title="Tamamlandı">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-info btn-sm" onclick="updateDecisionStatus(${decision.id}, 'in_progress')" title="Devam Ediyor">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Toplantı düzenle
        function editMeeting(id) {
            // Bu fonksiyon daha sonra implement edilecek
            showAlert('Düzenleme özelliği yakında eklenecek!', 'info');
        }
        
        // Toplantıyı başlat
        function startMeeting(id) {
            if (confirm('Bu toplantıyı başlatmak istediğinizden emin misiniz?')) {
                fetch('api/meeting_api.php?action=update_meeting', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        status: 'ongoing'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Toplantı başlatıldı!', 'success');
                        loadMeetings();
                        loadMeetingStats();
                    } else {
                        showAlert('Hata: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Bir hata oluştu!', 'danger');
                });
            }
        }
        
        // Toplantı sil
        function deleteMeeting(id) {
            if (confirm('Bu toplantıyı silmek istediğinizden emin misiniz?')) {
                fetch('api/meeting_api.php?action=delete_meeting', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({id: id})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Toplantı başarıyla silindi!', 'success');
                        loadMeetings();
                        loadMeetingStats();
                    } else {
                        showAlert('Hata: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Bir hata oluştu!', 'danger');
                });
            }
        }
        
        // Yeni toplantı modal'ını aç
        function openAddMeetingModal() {
            // Formu temizle
            document.getElementById('addMeetingForm').reset();
            document.getElementById('participantsList').innerHTML = '';
            document.getElementById('agendaItemsList').innerHTML = '';
            
            // Modal'ı aç
            new bootstrap.Modal(document.getElementById('addMeetingModal')).show();
        }
        
        // BYK seçildiğinde otomatik katılımcıları yükle
        function loadParticipantsByBYK(bykCode) {
            if (!bykCode) return;
            
            // BYK'ya göre örnek katılımcılar (gerçek sistemde API'den gelecek)
            const participantsByBYK = {
                'AT': [
                    {name: 'Ahmet Yılmaz', role: 'Başkan'},
                    {name: 'Mehmet Kaya', role: 'Sekreter'},
                    {name: 'Ali Demir', role: 'Üye'},
                    {name: 'Fatma Özkan', role: 'Üye'},
                    {name: 'Hasan Yıldız', role: 'Üye'}
                ],
                'KT': [
                    {name: 'Zeynep Kaya', role: 'Başkan'},
                    {name: 'Elif Demir', role: 'Sekreter'},
                    {name: 'Ayşe Yılmaz', role: 'Üye'},
                    {name: 'Fatma Özkan', role: 'Üye'},
                    {name: 'Hatice Korkmaz', role: 'Üye'}
                ],
                'KGT': [
                    {name: 'Selin Yılmaz', role: 'Başkan'},
                    {name: 'Büşra Demir', role: 'Sekreter'},
                    {name: 'Merve Kaya', role: 'Üye'},
                    {name: 'Esra Özkan', role: 'Üye'},
                    {name: 'Gamze Yıldız', role: 'Üye'}
                ],
                'GT': [
                    {name: 'Emre Yılmaz', role: 'Başkan'},
                    {name: 'Can Demir', role: 'Sekreter'},
                    {name: 'Burak Kaya', role: 'Üye'},
                    {name: 'Oğuz Özkan', role: 'Üye'},
                    {name: 'Serkan Yıldız', role: 'Üye'}
                ]
            };
            
            const participants = participantsByBYK[bykCode] || [];
            const container = document.getElementById('participantsList');
            
            // Mevcut katılımcıları temizle
            container.innerHTML = '';
            
            // Yeni katılımcıları ekle (hepsi geldi olarak işaretle)
            participants.forEach(participant => {
                const newParticipantHtml = `
                    <div class="d-flex align-items-center mb-2 p-2 border rounded">
                        <div class="participant-avatar me-3">
                            ${participant.name.charAt(0)}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">${participant.name}</div>
                            <small class="text-muted">${participant.role}</small>
                        </div>
                        <div class="form-check me-2">
                            <input class="form-check-input" type="checkbox" checked onchange="toggleParticipantAttendance(this)">
                            <label class="form-check-label">
                                <small>Geldi</small>
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeParticipant(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                container.innerHTML += newParticipantHtml;
            });
            
            showAlert(`${participants.length} katılımcı otomatik eklendi`, 'success');
        }
        
        // Katılımcı ekleme formunu göster/gizle
        function toggleAddParticipantForm() {
            const form = document.getElementById('addParticipantForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            
            if (form.style.display === 'block') {
                document.getElementById('participantName').focus();
            }
        }
        
        // Formdan katılımcı ekle
        function addParticipantFromForm() {
            const name = document.getElementById('participantName').value.trim();
            const role = document.getElementById('participantRole').value;
            
            if (!name) {
                showAlert('Lütfen katılımcı adını girin!', 'warning');
                return;
            }
            
            const container = document.getElementById('participantsList');
            const newParticipantHtml = `
                <div class="d-flex align-items-center mb-2 p-2 border rounded">
                    <div class="participant-avatar me-3">
                        ${name.charAt(0)}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${name}</div>
                        <small class="text-muted">${role}</small>
                    </div>
                    <div class="form-check me-2">
                        <input class="form-check-input" type="checkbox" checked onchange="toggleParticipantAttendance(this)">
                        <label class="form-check-label">
                            <small>Geldi</small>
                        </label>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeParticipant(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.innerHTML += newParticipantHtml;
            
            // Formu temizle ve gizle
            document.getElementById('participantName').value = '';
            document.getElementById('participantRole').value = 'member';
            document.getElementById('addParticipantForm').style.display = 'none';
            
            showAlert('Katılımcı eklendi!', 'success');
        }
        
        // Katılımcı katılım durumunu değiştir
        function toggleParticipantAttendance(checkbox) {
            const label = checkbox.nextElementSibling;
            if (checkbox.checked) {
                label.textContent = 'Geldi';
                label.classList.remove('text-danger');
                label.classList.add('text-success');
            } else {
                label.textContent = 'Gelmedi';
                label.classList.remove('text-success');
                label.classList.add('text-danger');
            }
        }
        
        // Katılımcı kaldır
        function removeParticipant(button) {
            button.closest('.d-flex').remove();
        }
        
        // Gündem ekleme formunu göster/gizle
        function toggleAddAgendaForm() {
            const form = document.getElementById('addAgendaForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            
            if (form.style.display === 'block') {
                document.getElementById('agendaTitle').focus();
            }
        }
        
        // Formdan gündem maddesi ekle
        function addAgendaFromForm() {
            const title = document.getElementById('agendaTitle').value.trim();
            const responsible = document.getElementById('agendaResponsible').value.trim();
            
            if (!title) {
                showAlert('Lütfen gündem maddesi başlığını girin!', 'warning');
                return;
            }
            
            const container = document.getElementById('agendaItemsList');
            const order = container.children.length + 1;
            const newItemHtml = `
                <div class="agenda-item mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${order}. ${title}</h6>
                            <small class="text-muted">
                                <strong>Sorumlu:</strong> ${responsible || '-'}
                            </small>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeAgendaItem(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            container.innerHTML += newItemHtml;
            
            // Formu temizle ve gizle
            document.getElementById('agendaTitle').value = '';
            document.getElementById('agendaResponsible').value = '';
            document.getElementById('addAgendaForm').style.display = 'none';
            
            showAlert('Gündem maddesi eklendi!', 'success');
        }
        
        // Gündem maddesi kaldır
        function removeAgendaItem(button) {
            button.closest('.agenda-item').remove();
            // Sıra numaralarını yeniden düzenle
            const items = document.querySelectorAll('#agendaItemsList .agenda-item');
            items.forEach((item, index) => {
                const title = item.querySelector('h6');
                title.textContent = title.textContent.replace(/^\d+\./, `${index + 1}.`);
            });
        }
        
        // Karar ekle
        function addDecision() {
            if (!currentMeetingId) {
                showAlert('Önce bir toplantı seçin!', 'warning');
                return;
            }
            
            const decisionText = prompt('Karar metnini girin:');
            if (decisionText) {
                const responsible = prompt('Sorumlu kişiyi girin:');
                const deadline = prompt('Termin tarihi (YYYY-MM-DD):');
                const priority = prompt('Öncelik (low/medium/high/urgent):') || 'medium';
                
                const data = {
                    meeting_id: currentMeetingId,
                    decision_text: decisionText,
                    responsible: responsible,
                    deadline: deadline,
                    priority: priority
                };
                
                fetch('api/meeting_api.php?action=add_decision', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Karar eklendi!', 'success');
                        // Detay modal'ını yenile
                        viewMeeting(currentMeetingId);
                        loadMeetingStats();
                    } else {
                        showAlert('Hata: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Bir hata oluştu!', 'danger');
                });
            }
        }
        
        // Karar durumu güncelle
        function updateDecisionStatus(decisionId, status) {
            const progressNotes = prompt('İlerleme notları (opsiyonel):') || '';
            
            fetch('api/meeting_api.php?action=update_decision_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: decisionId,
                    status: status,
                    progress_notes: progressNotes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Karar durumu güncellendi!', 'success');
                    // Detay modal'ını yenile
                    viewMeeting(currentMeetingId);
                    loadMeetingStats();
                } else {
                    showAlert('Hata: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Bir hata oluştu!', 'danger');
            });
        }
        
        // Bekleyen kararları göster
        function showPendingDecisions() {
            fetch('api/meeting_api.php?action=get_pending_decisions')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('pendingDecisionsList');
                        
                        if (data.data.length === 0) {
                            container.innerHTML = '<p class="text-muted text-center py-4">Bekleyen karar bulunmuyor.</p>';
                        } else {
                            let html = '';
                            data.data.forEach(decision => {
                                const isOverdue = decision.deadline && new Date(decision.deadline) < new Date();
                                const priorityClass = priorityColors[decision.priority] || 'secondary';
                                const priorityText = priorityTexts[decision.priority] || 'Bilinmiyor';
                                
                                html += `
                                    <div class="decision-item ${decision.priority === 'urgent' ? 'urgent' : ''}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">${decision.decision_number}</h6>
                                                <p class="mb-1">${decision.decision_text}</p>
                                                <small class="text-muted">
                                                    <strong>Toplantı:</strong> ${decision.meeting_title} (${formatDate(decision.meeting_date)})<br>
                                                    <strong>Sorumlu:</strong> ${decision.responsible_person} | 
                                                    <strong>Termin:</strong> ${decision.deadline ? formatDate(decision.deadline) : '-'}
                                                    ${isOverdue ? ' <span class="text-danger">(Gecikmiş)</span>' : ''}
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-${priorityClass} mb-1">${priorityText}</span><br>
                                                <span class="badge bg-${bykUnits.find(u => u.code === decision.byk_name)?.color || '#007bff'}">${decision.byk_name}</span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                        }
                        
                        // Modal'ı aç
                        new bootstrap.Modal(document.getElementById('pendingDecisionsModal')).show();
                    } else {
                        showAlert('Bekleyen kararlar yüklenemedi: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Bir hata oluştu!', 'danger');
                });
        }
        
        // Filtreleri uygula
        function applyFilters() {
            const byk = document.getElementById('bykFilter').value;
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            let filteredMeetings = meetings;
            
            if (byk) {
                filteredMeetings = filteredMeetings.filter(m => m.byk_code === byk);
            }
            
            if (status) {
                filteredMeetings = filteredMeetings.filter(m => m.status === status);
            }
            
            if (dateFrom) {
                filteredMeetings = filteredMeetings.filter(m => m.meeting_date >= dateFrom);
            }
            
            if (dateTo) {
                filteredMeetings = filteredMeetings.filter(m => m.meeting_date <= dateTo);
            }
            
            renderMeetings(filteredMeetings);
            showAlert(`${filteredMeetings.length} toplantı bulundu`, 'info');
        }
        
        // Filtreleri temizle
        function clearFilters() {
            document.getElementById('bykFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            
            renderMeetings(meetings);
            showAlert('Filtreler temizlendi', 'info');
        }
        
        // Toplantı raporu oluştur
        function generateMeetingReport() {
            showAlert('Rapor oluşturma özelliği yakında eklenecek!', 'info');
        }
        
        // Dışa aktar
        function exportMeetings() {
            showAlert('Dışa aktarma özelliği yakında eklenecek!', 'info');
        }
        
        // Form gönderimi
        document.getElementById('addMeetingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const meetingData = Object.fromEntries(formData);
            
            // Katılımcıları topla
            const participants = [];
            document.querySelectorAll('#participantsList .d-flex').forEach(item => {
                const name = item.querySelector('.fw-bold').textContent;
                const role = item.querySelector('.text-muted').textContent;
                const isAttending = item.querySelector('.form-check-input').checked;
                participants.push({
                    name: name, 
                    role: role, 
                    status: isAttending ? 'attended' : 'absent'
                });
            });
            
            // Gündem maddelerini topla
            const agenda = [];
            document.querySelectorAll('#agendaItemsList .agenda-item').forEach((item, index) => {
                const title = item.querySelector('h6').textContent.replace(/^\d+\.\s*/, '');
                const responsible = item.querySelector('.text-muted').textContent.replace('Sorumlu: ', '').trim();
                
                agenda.push({
                    title: title,
                    description: '',
                    responsible: responsible,
                    duration: 15
                });
            });
            
            meetingData.participants = participants;
            meetingData.agenda = agenda;
            
            // Debug için console.log
            console.log('Toplantı verisi:', meetingData);
            console.log('Katılımcılar:', participants);
            console.log('Gündem:', agenda);
            
            // API'ye gönder
            fetch('api/meeting_api.php?action=add_meeting', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(meetingData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Toplantı başarıyla oluşturuldu!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('addMeetingModal')).hide();
                    loadMeetings();
                    loadMeetingStats();
                } else {
                    showAlert('Hata: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Bir hata oluştu!', 'danger');
            });
        });
        
        // Yardımcı fonksiyonlar
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('tr-TR');
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                      type === 'warning' ? 'exclamation-triangle' : 
                                      type === 'danger' ? 'times-circle' : 'info-circle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }
    </script>
</body>
</html>
