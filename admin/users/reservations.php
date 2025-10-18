<?php
require_once 'auth.php';

// Session kontrolü - sadece superadmin giriş yapabilir
SessionManager::requireRole('superadmin');

$currentUser = SessionManager::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Rezervasyon</title>
    
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
                    <h1>Rezervasyon</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReservationModal">
                        <i class="fas fa-plus"></i> Rezervasyon Ekle
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Reservation Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary">45</h3>
                            <p class="text-muted mb-0">Toplam Rezervasyon</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success">38</h3>
                            <p class="text-muted mb-0">Onaylanan</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">5</h3>
                            <p class="text-muted mb-0">Beklemede</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-info">12</h3>
                            <p class="text-muted mb-0">Bu Hafta</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservation Categories -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="reservation-card">
                        <div class="card-body text-center">
                            <div class="reservation-icon room">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <h5>Oda Rezervasyonu</h5>
                            <p class="text-muted mb-0">18 rezervasyon</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="reservation-card">
                        <div class="card-body text-center">
                            <div class="reservation-icon vehicle">
                                <i class="fas fa-car"></i>
                            </div>
                            <h5>Araç Rezervasyonu</h5>
                            <p class="text-muted mb-0">12 rezervasyon</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="reservation-card">
                        <div class="card-body text-center">
                            <div class="reservation-icon equipment">
                                <i class="fas fa-tools"></i>
                            </div>
                            <h5>Ekipman Rezervasyonu</h5>
                            <p class="text-muted mb-0">10 rezervasyon</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="reservation-card">
                        <div class="card-body text-center">
                            <div class="reservation-icon event">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h5>Etkinlik Rezervasyonu</h5>
                            <p class="text-muted mb-0">5 rezervasyon</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservation Management -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-bookmark"></i> Rezervasyon Listesi</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Rezervasyon ara...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <select class="form-select">
                                    <option>Tüm Türler</option>
                                    <option>Oda</option>
                                    <option>Araç</option>
                                    <option>Ekipman</option>
                                    <option>Etkinlik</option>
                                </select>
                                <button class="btn btn-outline-primary">
                                    <i class="fas fa-filter"></i> Filtrele
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rezervasyon</th>
                                    <th>Tür</th>
                                    <th>Tarih</th>
                                    <th>Süre</th>
                                    <th>Rezervasyon Yapan</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Toplantı Odası A</div>
                                        <small class="text-muted">#001</small>
                                    </td>
                                    <td>Oda</td>
                                    <td>
                                        <div>20.01.2024</div>
                                        <small class="text-muted">14:00 - 16:00</small>
                                    </td>
                                    <td>2 saat</td>
                                    <td>Ahmet Yılmaz</td>
                                    <td><span class="badge status-confirmed">Onaylandı</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Araç #001</div>
                                        <small class="text-muted">#002</small>
                                    </td>
                                    <td>Araç</td>
                                    <td>
                                        <div>22.01.2024</div>
                                        <small class="text-muted">09:00 - 17:00</small>
                                    </td>
                                    <td>8 saat</td>
                                    <td>Fatma Demir</td>
                                    <td><span class="badge status-pending">Beklemede</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-success me-1">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Projeksiyon Cihazı</div>
                                        <small class="text-muted">#003</small>
                                    </td>
                                    <td>Ekipman</td>
                                    <td>
                                        <div>25.01.2024</div>
                                        <small class="text-muted">10:00 - 12:00</small>
                                    </td>
                                    <td>2 saat</td>
                                    <td>Mehmet Kaya</td>
                                    <td><span class="badge status-confirmed">Onaylandı</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">Önceki</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Sonraki</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Reservation Modal -->
    <div class="modal fade" id="addReservationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Rezervasyon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addReservationForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rezervasyon Türü</label>
                                <select class="form-select" id="reservationType" required>
                                    <option value="">Seçiniz</option>
                                    <option value="room">Oda</option>
                                    <option value="vehicle">Araç</option>
                                    <option value="equipment">Ekipman</option>
                                    <option value="event">Etkinlik</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kaynak</label>
                                <select class="form-select" id="resource" required>
                                    <option value="">Önce tür seçiniz</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Başlangıç Tarihi</label>
                                <input type="datetime-local" class="form-control" id="startDateTime" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bitiş Tarihi</label>
                                <input type="datetime-local" class="form-control" id="endDateTime" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rezervasyon Yapan</label>
                                <select class="form-select" id="reservedBy" required>
                                    <option value="">Seçiniz</option>
                                    <option value="Ahmet Yılmaz">Ahmet Yılmaz</option>
                                    <option value="Fatma Demir">Fatma Demir</option>
                                    <option value="Mehmet Kaya">Mehmet Kaya</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" id="reservationStatus" required>
                                    <option value="pending">Beklemede</option>
                                    <option value="confirmed">Onaylandı</option>
                                    <option value="cancelled">İptal Edildi</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" id="reservationDescription" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="saveReservation">Kaydet</button>
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
