<?php
require_once 'auth.php';
require_once 'config.php';

// Login kontrolü kaldırıldı - direkt erişim
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

// BYK kategorileri
$bykCategories = [
    'AT' => 'Ana Teşkilat',
    'KT' => 'Kadınlar Teşkilatı', 
    'KGT' => 'Kadınlar Gençlik Teşkilatı',
    'GT' => 'Gençlik Teşkilatı'
];

// Veritabanından toplantıları çek
$meetings = [];
if ($pdo) {
    try {
        $sql = "
            SELECT m.*, 
                   COUNT(DISTINCT mp.id) as participants,
                   COUNT(DISTINCT ma.id) as agenda_count,
                   COUNT(DISTINCT md.id) as decisions_count
            FROM meetings m
            LEFT JOIN meeting_participants mp ON m.id = mp.meeting_id
            LEFT JOIN meeting_agenda ma ON m.id = ma.meeting_id
            LEFT JOIN meeting_decisions md ON m.id = md.meeting_id
            GROUP BY m.id
            ORDER BY m.meeting_date DESC, m.meeting_time DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $meetings = $stmt->fetchAll();
        
        // Alan adlarını düzenle
        foreach ($meetings as &$meeting) {
            $meeting['byk'] = $meeting['byk_code'];
            $meeting['date'] = $meeting['meeting_date'];
            $meeting['time'] = $meeting['meeting_time'];
        }
    } catch (Exception $e) {
        // Hata durumunda boş array
        $meetings = [];
    }
} else {
    // Veritabanı bağlantısı yoksa boş array
    $meetings = [];
}

// Durum metinleri
$statusTexts = [
    'planned' => 'Planlandı',
    'ongoing' => 'Devam Ediyor',
    'completed' => 'Tamamlandı',
    'cancelled' => 'İptal Edildi'
];

// Durum renkleri
$statusColors = [
    'planned' => 'warning',
    'ongoing' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
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
        }
        
        .meeting-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .byk-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
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
        
        .meeting-stats {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .meeting-stats h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        
        .meeting-stats p {
            margin: 0;
            opacity: 0.9;
        }
        
        .tab-content {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 1rem 1.5rem;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background: none;
            border-bottom-color: var(--primary-color);
        }
        
        .agenda-item {
            border-left: 3px solid var(--primary-color);
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        
        .decision-item {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
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
                    <h1>Toplantı Yönetimi</h1>
                    <p>BYK toplantılarını planlayın, yürütün ve kararları takip edin</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMeetingModal">
                        <i class="fas fa-plus"></i> Yeni Toplantı
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- İstatistikler -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="meeting-stats">
                        <h3><?php echo count($meetings); ?></h3>
                        <p>Toplam Toplantı</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="meeting-stats" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?php echo count(array_filter($meetings, function($m) { return $m['status'] === 'completed'; })); ?></h3>
                        <p>Tamamlanan</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="meeting-stats" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3><?php echo count(array_filter($meetings, function($m) { return $m['status'] === 'ongoing'; })); ?></h3>
                        <p>Devam Eden</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="meeting-stats" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3><?php echo count(array_filter($meetings, function($m) { return $m['status'] === 'planned'; })); ?></h3>
                        <p>Planlanan</p>
                    </div>
                </div>
            </div>

            <!-- Filtreler -->
            <div class="page-card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">BYK Seçin</label>
                            <select class="form-select" id="bykFilter">
                                <option value="">Tüm BYK'lar</option>
                                <?php foreach ($bykCategories as $code => $name): ?>
                                <option value="<?php echo $code; ?>"><?php echo $code; ?> - <?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Durum</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">Tüm Durumlar</option>
                                <?php foreach ($statusTexts as $key => $text): ?>
                                <option value="<?php echo $key; ?>"><?php echo $text; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tarih Başlangıç</label>
                            <input type="date" class="form-control" id="dateFrom">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tarih Bitiş</label>
                            <input type="date" class="form-control" id="dateTo">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button class="btn btn-primary" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Filtrele
                            </button>
                            <button class="btn btn-outline-primary" onclick="exportMeetings()">
                                <i class="fas fa-download"></i> Dışa Aktar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toplantı Listesi -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-alt"></i> Toplantı Listesi</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($meetings)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Henüz toplantı bulunmuyor</h4>
                            <p class="text-muted">İlk toplantınızı planlamak için yukarıdaki butonu kullanın.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($meetings as $meeting): ?>
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="card meeting-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($meeting['title']); ?></h6>
                                                <span class="byk-badge byk-<?php echo strtolower($meeting['byk']); ?>">
                                                    <?php echo $meeting['byk']; ?>
                                                </span>
                                            </div>
                                            <span class="status-badge badge bg-<?php echo $statusColors[$meeting['status']]; ?>">
                                                <?php echo $statusTexts[$meeting['status']]; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="meeting-info mb-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-calendar text-primary me-2"></i>
                                                <span><?php echo date('d.m.Y', strtotime($meeting['date'])); ?> - <?php echo $meeting['time']; ?></span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                                <span><?php echo htmlspecialchars($meeting['location']); ?></span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-user-tie text-primary me-2"></i>
                                                <span><?php echo htmlspecialchars($meeting['chairman']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="meeting-stats-small d-flex justify-content-between mb-3">
                                            <div class="text-center">
                                                <div class="fw-bold text-primary"><?php echo $meeting['participants']; ?></div>
                                                <small class="text-muted">Katılımcı</small>
                                            </div>
                                            <div class="text-center">
                                                <div class="fw-bold text-success"><?php echo $meeting['agenda_count']; ?></div>
                                                <small class="text-muted">Gündem</small>
                                            </div>
                                            <div class="text-center">
                                                <div class="fw-bold text-warning"><?php echo $meeting['decisions_count']; ?></div>
                                                <small class="text-muted">Karar</small>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-outline-primary btn-sm" onclick="viewMeeting(<?php echo $meeting['id']; ?>)" title="Görüntüle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success btn-sm" onclick="startMeeting(<?php echo $meeting['id']; ?>)" title="Toplantıyı Başlat">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <button class="btn btn-outline-warning btn-sm" onclick="editMeeting(<?php echo $meeting['id']; ?>)" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteMeeting(<?php echo $meeting['id']; ?>)" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Yeni Toplantı Modal -->
    <div class="modal fade" id="addMeetingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Toplantı Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addMeetingForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Toplantı Başlığı</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BYK</label>
                                <select class="form-select" name="byk" required>
                                    <option value="">BYK Seçin</option>
                                    <?php foreach ($bykCategories as $code => $name): ?>
                                    <option value="<?php echo $code; ?>"><?php echo $code; ?> - <?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" class="form-control" name="date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Saat</label>
                                <input type="time" class="form-control" name="time" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yer / Platform</label>
                                <input type="text" class="form-control" name="location" placeholder="AIF Genel Merkez veya Zoom linki" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" name="status">
                                    <option value="planned">Planlandı</option>
                                    <option value="ongoing">Devam Ediyor</option>
                                    <option value="completed">Tamamlandı</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birim</label>
                            <input type="text" class="form-control" name="unit" placeholder="Toplantı birimi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Toplantı Gündemleri</label>
                            <textarea class="form-control" name="agenda" rows="4" placeholder="Toplantı gündem maddelerini yazın"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Toplantı Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toplantıyı Başlat Modal -->
    <div class="modal fade" id="startMeetingModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-play me-2"></i>Toplantıyı Başlat
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Toplantı Bilgileri -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Toplantı Bilgileri</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Başlık:</strong> <span id="startMeetingTitle">-</span></p>
                                            <p><strong>BYK:</strong> <span id="startMeetingByk">-</span></p>
                                            <p><strong>Tarih:</strong> <span id="startMeetingDate">-</span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Saat:</strong> <span id="startMeetingTime">-</span></p>
                                            <p><strong>Yer:</strong> <span id="startMeetingLocation">-</span></p>
                                            <p><strong>Başkan:</strong> <span id="startMeetingChairman">-</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Gündem Maddeleri -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Gündem Maddeleri</h6>
                                    <button class="btn btn-primary btn-sm" onclick="addAgendaItem()">
                                        <i class="fas fa-plus"></i> Gündem Ekle
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div id="agendaItemsList">
                                        <!-- Gündem maddeleri buraya yüklenecek -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Kararlar -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Alınan Kararlar</h6>
                                    <button class="btn btn-success btn-sm" onclick="addDecision()">
                                        <i class="fas fa-plus"></i> Karar Ekle
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div id="decisionsList">
                                        <!-- Kararlar buraya yüklenecek -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Katılımcılar -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Katılımcılar</h6>
                                    <button class="btn btn-info btn-sm" onclick="addParticipant()">
                                        <i class="fas fa-plus"></i> Ekle
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div id="participantsList">
                                        <!-- Katılımcılar buraya yüklenecek -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Toplantı Notları -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Toplantı Notları</h6>
                                </div>
                                <div class="card-body">
                                    <textarea class="form-control" id="meetingNotes" rows="8" placeholder="Toplantı sırasında alınan notlar..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Toplantı Kontrolleri -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Toplantı Kontrolleri</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-success" onclick="pauseMeeting()">
                                            <i class="fas fa-pause"></i> Toplantıyı Duraklat
                                        </button>
                                        <button class="btn btn-warning" onclick="resumeMeeting()">
                                            <i class="fas fa-play"></i> Toplantıyı Devam Ettir
                                        </button>
                                        <button class="btn btn-danger" onclick="endMeeting()">
                                            <i class="fas fa-stop"></i> Toplantıyı Bitir
                                </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-primary" onclick="saveMeetingProgress()">
                        <i class="fas fa-save"></i> İlerlemeyi Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toplantı Detay Modal -->
    <div class="modal fade" id="meetingDetailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Toplantı Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="meetingTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                                <i class="fas fa-info-circle me-2"></i>Genel Bilgi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="agenda-tab" data-bs-toggle="tab" data-bs-target="#agenda" type="button">
                                <i class="fas fa-list me-2"></i>Gündem Maddeleri
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="decisions-tab" data-bs-toggle="tab" data-bs-target="#decisions" type="button">
                                <i class="fas fa-gavel me-2"></i>Kararlar & Görevler
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="participants-tab" data-bs-toggle="tab" data-bs-target="#participants" type="button">
                                <i class="fas fa-users me-2"></i>Katılımcılar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button">
                                <i class="fas fa-paperclip me-2"></i>Dosyalar
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="meetingTabContent">
                        <!-- Genel Bilgi -->
                        <div class="tab-pane fade show active" id="general">
                            <div class="p-4">
                                <h5>Toplantı Bilgileri</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Başlık:</strong> <span id="meetingTitle">-</span></p>
                                        <p><strong>BYK:</strong> <span id="meetingByk">-</span></p>
                                        <p><strong>Tarih:</strong> <span id="meetingDate">-</span></p>
                                        <p><strong>Saat:</strong> <span id="meetingTime">-</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Yer:</strong> <span id="meetingLocation">-</span></p>
                                        <p><strong>Başkan:</strong> <span id="meetingChairman">-</span></p>
                                        <p><strong>Sekreter:</strong> <span id="meetingSecretary">-</span></p>
                                        <p><strong>Durum:</strong> <span id="meetingStatus">-</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gündem Maddeleri -->
                        <div class="tab-pane fade" id="agenda">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Gündem Maddeleri</h5>
                                    <button class="btn btn-primary btn-sm" onclick="addAgendaItem()">
                                        <i class="fas fa-plus"></i> Gündem Ekle
                                    </button>
                                </div>
                                <div id="agendaList">
                                    <!-- Gündem maddeleri buraya yüklenecek -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Kararlar & Görevler -->
                        <div class="tab-pane fade" id="decisions">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Kararlar & Görevler</h5>
                                    <button class="btn btn-primary btn-sm" onclick="addDecision()">
                                        <i class="fas fa-plus"></i> Karar Ekle
                                    </button>
                                </div>
                                <div id="decisionsList">
                                    <!-- Kararlar buraya yüklenecek -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Katılımcılar -->
                        <div class="tab-pane fade" id="participants">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Katılımcı Listesi</h5>
                                    <button class="btn btn-primary btn-sm" onclick="addParticipant()">
                                        <i class="fas fa-plus"></i> Katılımcı Ekle
                                    </button>
                                </div>
                                <div id="participantsList">
                                    <!-- Katılımcılar buraya yüklenecek -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dosyalar -->
                        <div class="tab-pane fade" id="files">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Toplantı Dosyaları</h5>
                                    <button class="btn btn-primary btn-sm" onclick="uploadFile()">
                                        <i class="fas fa-upload"></i> Dosya Yükle
                                        </button>
                                </div>
                                <div id="filesList">
                                    <!-- Dosyalar buraya yüklenecek -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-primary" onclick="generateReport()">
                        <i class="fas fa-file-pdf"></i> Rapor Oluştur
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Veritabanından gelen toplantı verileri
        const bykCategories = <?php echo json_encode($bykCategories); ?>;
        const statusTexts = <?php echo json_encode($statusTexts); ?>;
        
        function addMeeting() {
            console.log('addMeeting fonksiyonu çağrıldı');
            console.log('Modal element:', document.getElementById('addMeetingModal'));
            
            // Modal'ı göster
            const modal = new bootstrap.Modal(document.getElementById('addMeetingModal'));
            console.log('Modal instance:', modal);
            modal.show();
        }
        
        function viewMeeting(id) {
            // API'den toplantı detaylarını çek
            fetch(`api/meeting_api.php?action=get_meeting&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const meeting = data.data;
                    // Modal'ı doldur
                    document.getElementById('meetingTitle').textContent = meeting.title;
                    document.getElementById('meetingByk').textContent = meeting.byk_code + ' - ' + bykCategories[meeting.byk_code];
                    document.getElementById('meetingDate').textContent = new Date(meeting.meeting_date).toLocaleDateString('tr-TR');
                    document.getElementById('meetingTime').textContent = meeting.meeting_time;
                    document.getElementById('meetingLocation').textContent = meeting.location;
                    document.getElementById('meetingChairman').textContent = meeting.unit || 'Belirtilmemiş';
                    document.getElementById('meetingSecretary').textContent = 'Belirtilmemiş';
                    document.getElementById('meetingStatus').textContent = statusTexts[meeting.status];
                    
                    // Modal'ı göster
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
        
        function startMeeting(id) {
            // API'den toplantı detaylarını çek
            fetch(`api/meeting_api.php?action=get_meeting&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const meeting = data.data;
                    // Toplantı bilgilerini doldur
                    document.getElementById('startMeetingTitle').textContent = meeting.title;
                    document.getElementById('startMeetingByk').textContent = meeting.byk_code + ' - ' + bykCategories[meeting.byk_code];
                    document.getElementById('startMeetingDate').textContent = new Date(meeting.meeting_date).toLocaleDateString('tr-TR');
                    document.getElementById('startMeetingTime').textContent = meeting.meeting_time;
                    document.getElementById('startMeetingLocation').textContent = meeting.location;
                    document.getElementById('startMeetingChairman').textContent = meeting.unit || 'Belirtilmemiş';
                    
                    // Gündem maddeleri ve katılımcılar veritabanından yüklenecek
                    
                    // Modal'ı göster
                    new bootstrap.Modal(document.getElementById('startMeetingModal')).show();
                    
                    // Toplantı durumunu güncelle
                    fetch('api/meeting_api.php?action=update_meeting', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: id,
                            status: 'ongoing'
                        })
                    });
                    showAlert('Toplantı başlatıldı!', 'success');
                } else {
                    showAlert('Toplantı bulunamadı!', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Bir hata oluştu!', 'danger');
            });
        }
        
        function editMeeting(id) {
            // Toplantı detaylarını API'den çek
            fetch(`api/meeting_api.php?action=get_meeting&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const meeting = data.data;
                    
                    // Form alanlarını doldur
                    document.querySelector('#addMeetingForm input[name="title"]').value = meeting.title;
                    document.querySelector('#addMeetingForm select[name="byk"]').value = meeting.byk_code;
                    document.querySelector('#addMeetingForm input[name="date"]').value = meeting.meeting_date;
                    document.querySelector('#addMeetingForm input[name="time"]').value = meeting.meeting_time;
                    document.querySelector('#addMeetingForm input[name="location"]').value = meeting.location;
                    document.querySelector('#addMeetingForm select[name="status"]').value = meeting.status;
                    document.querySelector('#addMeetingForm input[name="unit"]').value = meeting.unit;
                    document.querySelector('#addMeetingForm textarea[name="agenda"]').value = meeting.agenda || '';
                    
                    // Form'a güncelleme modunu işaretle
                    document.getElementById('addMeetingForm').setAttribute('data-edit-id', id);
                    
                    // Modal'ı göster
                    new bootstrap.Modal(document.getElementById('addMeetingModal')).show();
                } else {
                    showAlert('Toplantı bilgileri alınamadı!', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Bir hata oluştu!', 'danger');
            });
        }
        
        function deleteMeeting(id) {
            if (confirm('Bu toplantıyı silmek istediğinizden emin misiniz?')) {
                // API'ye silme isteği gönder
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
                        // Toplantı kartını DOM'dan kaldır
                        const meetingCards = document.querySelectorAll('.meeting-card');
                        meetingCards.forEach(card => {
                            const deleteBtn = card.querySelector(`button[onclick="deleteMeeting(${id})"]`);
                            if (deleteBtn) {
                                card.closest('.col-lg-6, .col-xl-4').remove();
                            }
                        });
                        
                        // Başarı mesajı
                        showAlert('Toplantı başarıyla silindi!', 'success');
                        
                        // Eğer hiç toplantı kalmadıysa boş mesaj göster
                        setTimeout(() => {
                            const remainingCards = document.querySelectorAll('.meeting-card');
                            if (remainingCards.length === 0) {
                                const container = document.querySelector('.row');
                                container.innerHTML = `
                                    <div class="col-12">
                                        <div class="text-center py-5">
                                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                            <h4 class="text-muted">Henüz toplantı bulunmuyor</h4>
                                            <p class="text-muted">İlk toplantınızı planlamak için yukarıdaki butonu kullanın.</p>
                                        </div>
                                    </div>
                                `;
                            }
                        }, 500);
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
        
        function applyFilters() {
            const byk = document.getElementById('bykFilter').value;
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            console.log('Applying filters:', { byk, status, dateFrom, dateTo });
            showAlert('Filtreler uygulandı!', 'success');
        }
        
        function exportMeetings() {
            showAlert('Toplantılar dışa aktarılıyor...', 'info');
            console.log('Exporting meetings...');
        }
        
        // loadDemoAgendaItems fonksiyonu kaldırıldı - veritabanından yüklenecek
        
        // loadDemoParticipants fonksiyonu kaldırıldı - veritabanından yüklenecek
        
        function addAgendaItem() {
            const title = prompt('Gündem maddesi başlığını girin:');
            if (title) {
                const responsible = prompt('Sorumlu kişiyi girin:');
                const notes = prompt('Notlar (opsiyonel):');
                
                const container = document.getElementById('agendaItemsList');
                const newItemHtml = `
                    <div class="agenda-item mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${container.children.length + 1}. ${title}</h6>
                                <p class="text-muted mb-1"><strong>Sorumlu:</strong> ${responsible || '-'}</p>
                                <p class="text-muted mb-0">${notes || '-'}</p>
                </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-success" onclick="markAgendaCompleted(this)" title="Tamamlandı">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-outline-primary" onclick="editAgendaItem(this)" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                    </button>
                </div>
                        </div>
                    </div>
                `;
                container.innerHTML += newItemHtml;
                showAlert('Gündem maddesi eklendi!', 'success');
            }
        }
        
        function addDecision() {
            const decision = prompt('Karar metnini girin:');
            if (decision) {
                const responsible = prompt('Sorumlu kişiyi girin:');
                const deadline = prompt('Termin tarihi (YYYY-MM-DD):');
                
                const container = document.getElementById('decisionsList');
                const newDecisionHtml = `
                    <div class="decision-item mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Karar #${container.children.length + 1}</h6>
                                <p class="mb-1">${decision}</p>
                                <small class="text-muted">
                                    <strong>Sorumlu:</strong> ${responsible || '-'} | 
                                    <strong>Termin:</strong> ${deadline || '-'}
                                </small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-success" onclick="markDecisionCompleted(this)" title="Tamamlandı">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-outline-primary" onclick="editDecision(this)" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += newDecisionHtml;
                showAlert('Karar eklendi!', 'success');
            }
        }
        
        function addParticipant() {
            const name = prompt('Katılımcı adını girin:');
            if (name) {
                const role = prompt('Rolü girin (Başkan, Sekreter, Üye):');
                
                const container = document.getElementById('participantsList');
                const newParticipantHtml = `
                    <div class="d-flex align-items-center mb-2 p-2 border rounded">
                        <div class="participant-avatar me-3">
                            ${name.charAt(0)}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">${name}</div>
                            <small class="text-muted">${role || 'Üye'}</small>
                        </div>
                        <span class="badge bg-success">Katıldı</span>
                    </div>
                `;
                container.innerHTML += newParticipantHtml;
                showAlert('Katılımcı eklendi!', 'success');
            }
        }
        
        function pauseMeeting() {
            showAlert('Toplantı duraklatıldı!', 'warning');
        }
        
        function resumeMeeting() {
            showAlert('Toplantı devam ediyor!', 'info');
        }
        
        function endMeeting() {
            if (confirm('Toplantıyı bitirmek istediğinizden emin misiniz?')) {
                showAlert('Toplantı tamamlandı!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('startMeetingModal')).hide();
            }
        }
        
        function saveMeetingProgress() {
            showAlert('Toplantı ilerlemesi kaydedildi!', 'success');
        }
        
        function markAgendaCompleted(element) {
            element.closest('.agenda-item').classList.add('bg-light', 'border-success');
            element.innerHTML = '<i class="fas fa-check-circle"></i>';
            element.classList.remove('btn-outline-success');
            element.classList.add('btn-success');
            showAlert('Gündem maddesi tamamlandı!', 'success');
        }
        
        function markDecisionCompleted(element) {
            element.closest('.decision-item').classList.add('bg-light', 'border-success');
            element.innerHTML = '<i class="fas fa-check-circle"></i>';
            element.classList.remove('btn-outline-success');
            element.classList.add('btn-success');
            showAlert('Karar tamamlandı!', 'success');
        }
        
        function uploadFile() {
            showAlert('Dosya yükleme özelliği aktif!', 'success');
        }
        
        function generateReport() {
            showAlert('Toplantı raporu oluşturuluyor...', 'info');
            console.log('Generating meeting report...');
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
        }
        
        function logout() {
            if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                window.location.href = '../logout.php';
            }
        }
        
        // Demo veriler için global meetings array
        let meetings = <?php echo json_encode($meetings); ?>;
        
        // Form gönderimi
        document.getElementById('addMeetingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const meetingData = Object.fromEntries(formData);
            
            // Güncelleme modu kontrolü
            const editId = this.getAttribute('data-edit-id');
            
            if (editId) {
                // API'ye güncelleme isteği gönder
                meetingData.id = editId;
                fetch('api/meeting_api.php?action=update_meeting', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(meetingData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Toplantı başarıyla güncellendi!', 'success');
                        // Sayfayı yenile
                        location.reload();
                    } else {
                        showAlert('Hata: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Bir hata oluştu!', 'danger');
                });
            } else {
                // API'ye yeni toplantı ekleme isteği gönder
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
                        // Sayfayı yenile
                        location.reload();
                    } else {
                        showAlert('Hata: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Bir hata oluştu!', 'danger');
                });
            }
            
            // Modal'ı kapat
            bootstrap.Modal.getInstance(document.getElementById('addMeetingModal')).hide();
            
            // Formu temizle ve edit modunu kaldır
            this.reset();
            this.removeAttribute('data-edit-id');
            
            // Sayfayı yenile
            setTimeout(() => {
                location.reload();
            }, 1000);
        });
    </script>
</body>
</body></html>

