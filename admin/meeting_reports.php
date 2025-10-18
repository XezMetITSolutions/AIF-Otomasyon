<?php
require_once 'auth.php';
require_once 'includes/database.php';
require_once 'includes/byk_manager_db.php';

// Session kontrolü - sadece superadmin giriş yapabilir
SessionManager::requireRole('superadmin');

$currentUser = SessionManager::getCurrentUser();
$db = Database::getInstance();
$bykManager = new BYKManagerDB();

// AJAX istekleri için
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add_meeting':
            $byk_id = $_POST['byk_id'] ?? '';
            $title = $_POST['title'] ?? '';
            $meeting_date = $_POST['meeting_date'] ?? '';
            $location = $_POST['location'] ?? '';
            $platform = $_POST['platform'] ?? '';
            $chairman_id = $_POST['chairman_id'] ?? '';
            $secretary_id = $_POST['secretary_id'] ?? '';
            
            if (!$byk_id || !$title || !$meeting_date) {
                echo json_encode(['success' => false, 'message' => 'Zorunlu alanları doldurun.']);
                exit;
            }
            
            try {
                $meeting_id = $db->query(
                    "INSERT INTO meetings (byk_category_id, title, meeting_date, location, platform, chairman_id, secretary_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'planned')",
                    [$byk_id, $title, $meeting_date, $location, $platform, $chairman_id, $secretary_id]
                );
                
                echo json_encode(['success' => true, 'message' => 'Toplantı başarıyla eklendi.', 'meeting_id' => $meeting_id]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_meetings':
            $byk_filter = $_POST['byk_filter'] ?? '';
            $status_filter = $_POST['status_filter'] ?? '';
            
            $sql = "SELECT m.*, bc.name as byk_name, bc.color as byk_color, 
                           u1.full_name as chairman_name, u2.full_name as secretary_name,
                           (SELECT COUNT(*) FROM meeting_participants mp WHERE mp.meeting_id = m.id) as participant_count
                    FROM meetings m 
                    LEFT JOIN byk_categories bc ON m.byk_category_id = bc.id
                    LEFT JOIN users u1 ON m.chairman_id = u1.id
                    LEFT JOIN users u2 ON m.secretary_id = u2.id";
            
            $params = [];
            $conditions = [];
            
            if ($byk_filter) {
                $conditions[] = "m.byk_category_id = ?";
                $params[] = $byk_filter;
            }
            
            if ($status_filter) {
                $conditions[] = "m.status = ?";
                $params[] = $status_filter;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY m.meeting_date DESC";
            
            $meetings = $db->fetchAll($sql, $params);
            echo json_encode(['success' => true, 'meetings' => $meetings]);
            exit;
            
        case 'get_meeting_details':
            $meeting_id = $_POST['meeting_id'] ?? '';
            
            if (!$meeting_id) {
                echo json_encode(['success' => false, 'message' => 'Toplantı ID gerekli.']);
                exit;
            }
            
            $meeting = $db->fetchOne(
                "SELECT m.*, bc.name as byk_name, bc.color as byk_color,
                        u1.full_name as chairman_name, u2.full_name as secretary_name
                 FROM meetings m 
                 LEFT JOIN byk_categories bc ON m.byk_category_id = bc.id
                 LEFT JOIN users u1 ON m.chairman_id = u1.id
                 LEFT JOIN users u2 ON m.secretary_id = u2.id
                 WHERE m.id = ?",
                [$meeting_id]
            );
            
            $participants = $db->fetchAll(
                "SELECT mp.*, u.full_name, u.email, bc.name as byk_name
                 FROM meeting_participants mp
                 LEFT JOIN users u ON mp.user_id = u.id
                 LEFT JOIN byk_categories bc ON u.byk_id = bc.id
                 WHERE mp.meeting_id = ?",
                [$meeting_id]
            );
            
            $agenda = $db->fetchAll(
                "SELECT ma.*, u.full_name as responsible_name
                 FROM meeting_agenda ma
                 LEFT JOIN users u ON ma.responsible_user_id = u.id
                 WHERE ma.meeting_id = ?
                 ORDER BY ma.order_number",
                [$meeting_id]
            );
            
            $decisions = $db->fetchAll(
                "SELECT md.*, u.full_name as responsible_name
                 FROM meeting_decisions md
                 LEFT JOIN users u ON md.responsible_user_id = u.id
                 WHERE md.meeting_id = ?
                 ORDER BY md.decision_number",
                [$meeting_id]
            );
            
            echo json_encode([
                'success' => true,
                'meeting' => $meeting,
                'participants' => $participants,
                'agenda' => $agenda,
                'decisions' => $decisions
            ]);
            exit;
    }
}

// Sayfa verilerini al
$byk_categories = $bykManager->getBYKCategories();
$users = $db->fetchAll("SELECT * FROM users WHERE status = 'active' ORDER BY full_name");

// İstatistikler
$total_meetings = $db->fetchOne("SELECT COUNT(*) as count FROM meetings")['count'];
$this_month_meetings = $db->fetchOne("SELECT COUNT(*) as count FROM meetings WHERE MONTH(meeting_date) = MONTH(NOW()) AND YEAR(meeting_date) = YEAR(NOW())")['count'];
$pending_meetings = $db->fetchOne("SELECT COUNT(*) as count FROM meetings WHERE status = 'planned'")['count'];
$completed_meetings = $db->fetchOne("SELECT COUNT(*) as count FROM meetings WHERE status = 'completed'")['count'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Toplantı Raporları</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
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
                    <h1>Toplantı Raporları</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="addMeeting()">
                        <i class="fas fa-plus"></i> Yeni Toplantı
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Meeting Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary"><?php echo $total_meetings; ?></h3>
                            <p class="text-muted mb-0">Toplam Toplantı</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success"><?php echo $this_month_meetings; ?></h3>
                            <p class="text-muted mb-0">Bu Ay</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning"><?php echo $pending_meetings; ?></h3>
                            <p class="text-muted mb-0">Planlanan</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-info"><?php echo $completed_meetings; ?></h3>
                            <p class="text-muted mb-0">Tamamlanan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Management -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-file-alt"></i> Toplantı Raporları</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="searchInput" placeholder="Toplantı ara...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="bykFilter">
                                <option value="">Tüm BYK'lar</option>
                                <?php foreach ($byk_categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo $category['code']; ?> - <?php echo $category['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">Tüm Durumlar</option>
                                <option value="planned">Planlanan</option>
                                <option value="ongoing">Devam Eden</option>
                                <option value="completed">Tamamlanan</option>
                                <option value="cancelled">İptal Edilen</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" onclick="filterMeetings()">
                                <i class="fas fa-filter"></i> Filtrele
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Toplantı Başlığı</th>
                                    <th>BYK</th>
                                    <th>Tarih & Saat</th>
                                    <th>Yer/Platform</th>
                                    <th>Başkan</th>
                                    <th>Katılımcı Sayısı</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="meetingsTableBody">
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-calendar-alt fa-2x mb-2"></i><br>
                                        Henüz toplantı bulunmamaktadır.
                                        <br><br>
                                        <button class="btn btn-primary" onclick="addMeeting()">
                                            <i class="fas fa-plus"></i> İlk Toplantıyı Ekle
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Meeting Modal -->
    <div class="modal fade" id="addMeetingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Toplantı Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addMeetingForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Toplantı Başlığı *</label>
                                <input type="text" class="form-control" id="meetingTitle" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BYK *</label>
                                <select class="form-select" id="meetingBYK" required>
                                    <option value="">BYK Seçiniz</option>
                                    <?php foreach ($byk_categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo $category['code']; ?> - <?php echo $category['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tarih & Saat *</label>
                                <input type="datetime-local" class="form-control" id="meetingDateTime" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yer/Platform</label>
                                <input type="text" class="form-control" id="meetingLocation" placeholder="Örn: AIF Genel Merkez, Zoom">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Başkan</label>
                                <select class="form-select" id="meetingChairman">
                                    <option value="">Başkan Seçiniz</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo $user['full_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sekreter</label>
                                <select class="form-select" id="meetingSecretary">
                                    <option value="">Sekreter Seçiniz</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo $user['full_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notlar</label>
                            <textarea class="form-control" id="meetingNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" onclick="saveMeeting()">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Meeting Details Modal -->
    <div class="modal fade" id="meetingDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Toplantı Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="meetingDetailsContent">
                        <!-- Meeting details will be loaded here -->
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
        // Global variables
        let meetings = [];
        
        // Initialize page
        $(document).ready(function() {
            loadMeetings();
        });
        
        // Load meetings from server
        function loadMeetings() {
            $.post('meeting_reports.php', {
                action: 'get_meetings'
            }, function(response) {
                if (response.success) {
                    meetings = response.meetings;
                    displayMeetings(meetings);
                }
            }, 'json');
        }
        
        // Display meetings in table
        function displayMeetings(meetingsToShow) {
            const tbody = $('#meetingsTableBody');
            
            if (meetingsToShow.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-calendar-alt fa-2x mb-2"></i><br>
                            Henüz toplantı bulunmamaktadır.
                            <br><br>
                            <button class="btn btn-primary" onclick="addMeeting()">
                                <i class="fas fa-plus"></i> İlk Toplantıyı Ekle
                            </button>
                        </td>
                    </tr>
                `);
                return;
            }
            
            let html = '';
            meetingsToShow.forEach(meeting => {
                const statusBadge = getStatusBadge(meeting.status);
                const bykBadge = `<span class="badge" style="background-color: ${meeting.byk_color}">${meeting.byk_name}</span>`;
                
                html += `
                    <tr>
                        <td><strong>${meeting.title}</strong></td>
                        <td>${bykBadge}</td>
                        <td>${formatDateTime(meeting.meeting_date)}</td>
                        <td>${meeting.location || meeting.platform || '-'}</td>
                        <td>${meeting.chairman_name || '-'}</td>
                        <td><span class="badge bg-info">${meeting.participant_count}</span></td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewMeeting(${meeting.id})" title="Detayları Görüntüle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning" onclick="editMeeting(${meeting.id})" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteMeeting(${meeting.id})" title="Sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            tbody.html(html);
        }
        
        // Get status badge HTML
        function getStatusBadge(status) {
            const badges = {
                'planned': '<span class="badge bg-warning">Planlanan</span>',
                'ongoing': '<span class="badge bg-primary">Devam Eden</span>',
                'completed': '<span class="badge bg-success">Tamamlanan</span>',
                'cancelled': '<span class="badge bg-danger">İptal Edilen</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Bilinmeyen</span>';
        }
        
        // Format datetime
        function formatDateTime(dateTime) {
            const date = new Date(dateTime);
            return date.toLocaleDateString('tr-TR') + ' ' + date.toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'});
        }
        
        // Add new meeting
        function addMeeting() {
            $('#addMeetingModal').modal('show');
        }
        
        // Save meeting
        function saveMeeting() {
            const formData = {
                action: 'add_meeting',
                byk_id: $('#meetingBYK').val(),
                title: $('#meetingTitle').val(),
                meeting_date: $('#meetingDateTime').val(),
                location: $('#meetingLocation').val(),
                platform: $('#meetingLocation').val(), // Same as location for now
                chairman_id: $('#meetingChairman').val(),
                secretary_id: $('#meetingSecretary').val()
            };
            
            if (!formData.byk_id || !formData.title || !formData.meeting_date) {
                showAlert('Lütfen zorunlu alanları doldurun.', 'warning');
                return;
            }
            
            $.post('meeting_reports.php', formData, function(response) {
                if (response.success) {
                    showAlert('Toplantı başarıyla eklendi!', 'success');
                    $('#addMeetingModal').modal('hide');
                    $('#addMeetingForm')[0].reset();
                    loadMeetings();
                } else {
                    showAlert(response.message, 'danger');
                }
            }, 'json');
        }
        
        // View meeting details
        function viewMeeting(meetingId) {
            $.post('meeting_reports.php', {
                action: 'get_meeting_details',
                meeting_id: meetingId
            }, function(response) {
                if (response.success) {
                    displayMeetingDetails(response);
                    $('#meetingDetailsModal').modal('show');
                } else {
                    showAlert(response.message, 'danger');
                }
            }, 'json');
        }
        
        // Display meeting details
        function displayMeetingDetails(data) {
            const meeting = data.meeting;
            const participants = data.participants;
            const agenda = data.agenda;
            const decisions = data.decisions;
            
            let html = `
                <div class="row">
                    <div class="col-md-8">
                        <h4>${meeting.title}</h4>
                        <p class="text-muted">${meeting.byk_name} - ${formatDateTime(meeting.meeting_date)}</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge" style="background-color: ${meeting.byk_color}">${meeting.byk_name}</span>
                        ${getStatusBadge(meeting.status)}
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-map-marker-alt"></i> Yer/Platform</h6>
                        <p>${meeting.location || meeting.platform || 'Belirtilmemiş'}</p>
                        
                        <h6><i class="fas fa-user-tie"></i> Başkan</h6>
                        <p>${meeting.chairman_name || 'Belirtilmemiş'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-user-edit"></i> Sekreter</h6>
                        <p>${meeting.secretary_name || 'Belirtilmemiş'}</p>
                        
                        <h6><i class="fas fa-users"></i> Katılımcı Sayısı</h6>
                        <p>${participants.length} kişi</p>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <h6><i class="fas fa-sticky-note"></i> Notlar</h6>
                        <p>${meeting.notes || 'Not bulunmuyor.'}</p>
                    </div>
                </div>
            `;
            
            $('#meetingDetailsContent').html(html);
        }
        
        // Edit meeting
        function editMeeting(meetingId) {
            showAlert('Toplantı düzenleme özelliği yakında eklenecek!', 'info');
        }
        
        // Delete meeting
        function deleteMeeting(meetingId) {
            if (confirm('Bu toplantıyı silmek istediğinizden emin misiniz?')) {
                showAlert('Toplantı silme özelliği yakında eklenecek!', 'info');
            }
        }
        
        // Filter meetings
        function filterMeetings() {
            const bykFilter = $('#bykFilter').val();
            const statusFilter = $('#statusFilter').val();
            const searchTerm = $('#searchInput').val().toLowerCase();
            
            $.post('meeting_reports.php', {
                action: 'get_meetings',
                byk_filter: bykFilter,
                status_filter: statusFilter
            }, function(response) {
                if (response.success) {
                    let filteredMeetings = response.meetings;
                    
                    // Client-side search
                    if (searchTerm) {
                        filteredMeetings = filteredMeetings.filter(meeting => 
                            meeting.title.toLowerCase().includes(searchTerm) ||
                            meeting.byk_name.toLowerCase().includes(searchTerm) ||
                            (meeting.chairman_name && meeting.chairman_name.toLowerCase().includes(searchTerm))
                        );
                    }
                    
                    displayMeetings(filteredMeetings);
                }
            }, 'json');
        }
        
        // Show alert
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
        
        // Logout function
        function logout() {
            if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=logout'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '../index.php';
                    }
                });
            }
        }
    </script>
</body>
</html>