<?php
require_once 'auth.php';

// Login kontrolü kaldırıldı - direkt erişim
$currentUser = SessionManager::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Raggal Rezervasyon Başvurusu</title>
    
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
                    <h1>Raggal Rezervasyon Başvurusu</h1>
                    <p>Etkinlik tarihi ve katılımcı sayısını seçerek müsaitlik kontrolü yapın</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReservationModal">
                        <i class="fas fa-calendar-plus"></i> Yeni Başvuru
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Rezervasyon Filtreleri -->
            <div class="page-card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-filter"></i> Rezervasyon Filtreleri</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tarih Aralığı</label>
                            <input type="date" class="form-control" id="dateFilter">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Etkinlik Türü</label>
                            <select class="form-select" id="eventTypeFilter">
                                <option value="">Tüm Etkinlikler</option>
                                <option value="toplanti">Toplantı</option>
                                <option value="egitim">Eğitim</option>
                                <option value="konferans">Konferans</option>
                                <option value="seminer">Seminer</option>
                                <option value="diger">Diğer</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Katılımcı Sayısı</label>
                            <select class="form-select" id="participantCountFilter">
                                <option value="">Tüm Kapasiteler</option>
                                <option value="1-10">1-10 kişi</option>
                                <option value="11-25">11-25 kişi</option>
                                <option value="26-50">26-50 kişi</option>
                                <option value="51+">51+ kişi</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" onclick="checkAvailability()">
                                <i class="fas fa-search"></i> Müsaitlik Kontrol Et
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Takvim Görünümü -->
            <div class="page-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-calendar-alt"></i> Takvim Görünümü</h5>
                    <div class="btn-group" role="group">
                        <button class="btn btn-outline-primary btn-sm" onclick="changeView('month')">Ay</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="changeView('week')">Hafta</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="changeView('day')">Gün</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="calendarView">
                        <!-- Takvim burada yüklenecek -->
                        <div class="text-center py-5">
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Takvim yükleniyor...</h4>
                            <p class="text-muted">Lütfen filtreleri seçip "Müsaitlik Kontrol Et" butonuna tıklayın</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Müsaitlik Sonuçları -->
            <div class="page-card" id="availabilityResults" style="display: none;">
                <div class="card-header">
                    <h5><i class="fas fa-check-circle"></i> Müsaitlik Sonuçları</h5>
                </div>
                <div class="card-body">
                    <div id="availabilityContent">
                        <!-- Müsaitlik sonuçları burada gösterilecek -->
                    </div>
                </div>
            </div>

            <!-- Mevcut Rezervasyonlar -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Mevcut Rezervasyonlarım</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Etkinlik</th>
                                    <th>Tarih</th>
                                    <th>Saat</th>
                                    <th>Katılımcı Sayısı</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="reservationsTable">
                                <tr>
                                    <td>
                                        <div class="fw-bold">AT BYK Toplantısı</div>
                                        <small class="text-muted">Toplantı</small>
                                    </td>
                                    <td>20.01.2024</td>
                                    <td>14:00 - 16:00</td>
                                    <td>15 kişi</td>
                                    <td><span class="badge bg-success">Onaylandı</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="viewReservation(1)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning me-1" onclick="editReservation(1)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelReservation(1)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Eğitim Semineri</div>
                                        <small class="text-muted">Eğitim</small>
                                    </td>
                                    <td>25.01.2024</td>
                                    <td>10:00 - 12:00</td>
                                    <td>30 kişi</td>
                                    <td><span class="badge bg-warning">Beklemede</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="viewReservation(2)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning me-1" onclick="editReservation(2)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelReservation(2)">
                                            <i class="fas fa-times"></i>
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

    <!-- Add Reservation Modal -->
    <div class="modal fade" id="addReservationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Raggal Rezervasyon Başvurusu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addReservationForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Etkinlik Türü</label>
                                <select class="form-select" id="eventType" required>
                                    <option value="">Seçiniz</option>
                                    <option value="toplanti">Toplantı</option>
                                    <option value="egitim">Eğitim</option>
                                    <option value="konferans">Konferans</option>
                                    <option value="seminer">Seminer</option>
                                    <option value="diger">Diğer</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Etkinlik Başlığı</label>
                                <input type="text" class="form-control" id="eventTitle" placeholder="Etkinlik başlığını girin" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" class="form-control" id="eventDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Saat</label>
                                <input type="time" class="form-control" id="eventTime" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Süre (saat)</label>
                                <select class="form-select" id="eventDuration" required>
                                    <option value="">Seçiniz</option>
                                    <option value="1">1 saat</option>
                                    <option value="2">2 saat</option>
                                    <option value="3">3 saat</option>
                                    <option value="4">4 saat</option>
                                    <option value="5">5 saat</option>
                                    <option value="6">6 saat</option>
                                    <option value="8">8 saat (tam gün)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Katılımcı Sayısı</label>
                                <input type="number" class="form-control" id="participantCount" placeholder="Tahmini katılımcı sayısı" min="1" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" id="eventDescription" rows="3" placeholder="Etkinlik hakkında detaylı bilgi"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="saveReservation">Başvuru Yap</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Reservation type change
            $('#reservationType').change(function() {
                const type = $(this).val();
                const resourceSelect = $('#resource');
                
                resourceSelect.empty();
                resourceSelect.append('<option value="">Seçiniz</option>');
                
                if (type === 'room') {
                    resourceSelect.append('<option value="room-a">Toplantı Odası A</option>');
                    resourceSelect.append('<option value="room-b">Toplantı Odası B</option>');
                    resourceSelect.append('<option value="room-c">Toplantı Odası C</option>');
                } else if (type === 'vehicle') {
                    resourceSelect.append('<option value="car-001">Araç #001</option>');
                    resourceSelect.append('<option value="car-002">Araç #002</option>');
                    resourceSelect.append('<option value="van-001">Minibüs #001</option>');
                } else if (type === 'equipment') {
                    resourceSelect.append('<option value="projector-001">Projeksiyon Cihazı</option>');
                    resourceSelect.append('<option value="laptop-001">Laptop</option>');
                    resourceSelect.append('<option value="camera-001">Kamera</option>');
                } else if (type === 'event') {
                    resourceSelect.append('<option value="conference-hall">Konferans Salonu</option>');
                    resourceSelect.append('<option value="training-room">Eğitim Salonu</option>');
                }
            });
            
            // Search functionality
            $('input[type="text"]').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('tbody tr').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(searchTerm));
                });
            });
            
            // Save Reservation
            $('#saveReservation').click(function() {
                const reservationType = $('#reservationType').val();
                const resource = $('#resource').val();
                const startDateTime = $('#startDateTime').val();
                const endDateTime = $('#endDateTime').val();
                const reservedBy = $('#reservedBy').val();
                
                if (reservationType && resource && startDateTime && endDateTime && reservedBy) {
                    $('#addReservationModal').modal('hide');
                    $('#addReservationForm')[0].reset();
                    showNotification('Rezervasyon başarıyla eklendi!', 'success');
                } else {
                    showNotification('Lütfen tüm zorunlu alanları doldurun!', 'warning');
                }
            });
            
            // Show notification function
            function showNotification(message, type) {
                const alertClass = type === 'success' ? 'alert-success' : 
                                  type === 'warning' ? 'alert-warning' : 
                                  type === 'danger' ? 'alert-danger' : 'alert-info';
                
                const alert = $(`
                    <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                         style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                          type === 'warning' ? 'exclamation-triangle' : 
                                          type === 'danger' ? 'times-circle' : 'info-circle'}"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                $('body').append(alert);
                
                // Auto remove after 5 seconds
                setTimeout(function() {
                    alert.alert('close');
                }, 5000);
            }
            
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
        });
    </script>
</body>
</html>
