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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReportModal">
                        <i class="fas fa-plus"></i> Rapor Ekle
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Report Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary">24</h3>
                            <p class="text-muted mb-0">Toplam Rapor</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success">18</h3>
                            <p class="text-muted mb-0">Onaylanan</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">4</h3>
                            <p class="text-muted mb-0">İncelemede</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-info">2</h3>
                            <p class="text-muted mb-0">Bu Ay</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Reports -->
            <div class="row mb-4">
                <div class="col-lg-4 mb-3">
                    <div class="report-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="report-icon meeting">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Haftalık Toplantı</h6>
                                    <p class="text-muted mb-1">15.01.2024</p>
                                    <span class="badge status-approved">Onaylandı</span>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="report-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="report-icon decision">
                                    <i class="fas fa-gavel"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Karar Toplantısı</h6>
                                    <p class="text-muted mb-1">12.01.2024</p>
                                    <span class="badge status-review">İncelemede</span>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="report-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="report-icon action">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Aksiyon Planı</h6>
                                    <p class="text-muted mb-1">10.01.2024</p>
                                    <span class="badge status-approved">Onaylandı</span>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Management -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-file-alt"></i> Toplantı Raporları Listesi</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Rapor ara...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <select class="form-select">
                                    <option>Tüm Durumlar</option>
                                    <option>Taslak</option>
                                    <option>İncelemede</option>
                                    <option>Onaylandı</option>
                                    <option>Reddedildi</option>
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
                                    <th>Rapor Başlığı</th>
                                    <th>Toplantı Türü</th>
                                    <th>Tarih</th>
                                    <th>Katılımcılar</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Haftalık Değerlendirme</div>
                                        <small class="text-muted">#001</small>
                                    </td>
                                    <td>Haftalık Toplantı</td>
                                    <td>15.01.2024</td>
                                    <td>
                                        <div class="d-flex">
                                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-1" style="width: 25px; height: 25px;">
                                                <i class="fas fa-user" style="font-size: 0.7rem;"></i>
                                            </div>
                                            <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center text-white me-1" style="width: 25px; height: 25px;">
                                                <i class="fas fa-user" style="font-size: 0.7rem;"></i>
                                            </div>
                                            <div class="avatar-sm bg-warning rounded-circle d-flex align-items-center justify-content-center text-white me-1" style="width: 25px; height: 25px;">
                                                <i class="fas fa-user" style="font-size: 0.7rem;"></i>
                                            </div>
                                            <span class="badge bg-info">+2</span>
                                        </div>
                                    </td>
                                    <td><span class="badge status-approved">Onaylandı</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success me-1">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Proje Kararları</div>
                                        <small class="text-muted">#002</small>
                                    </td>
                                    <td>Karar Toplantısı</td>
                                    <td>12.01.2024</td>
                                    <td>
                                        <div class="d-flex">
                                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-1" style="width: 25px; height: 25px;">
                                                <i class="fas fa-user" style="font-size: 0.7rem;"></i>
                                            </div>
                                            <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center text-white me-1" style="width: 25px; height: 25px;">
                                                <i class="fas fa-user" style="font-size: 0.7rem;"></i>
                                            </div>
                                            <span class="badge bg-info">+1</span>
                                        </div>
                                    </td>
                                    <td><span class="badge status-review">İncelemede</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Aksiyon Planı</div>
                                        <small class="text-muted">#003</small>
                                    </td>
                                    <td>Planlama Toplantısı</td>
                                    <td>10.01.2024</td>
                                    <td>
                                        <div class="d-flex">
                                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-1" style="width: 25px; height: 25px;">
                                                <i class="fas fa-user" style="font-size: 0.7rem;"></i>
                                            </div>
                                            <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center text-white me-1" style="width: 25px; height: 25px;">
                                                <i class="fas fa-user" style="font-size: 0.7rem;"></i>
                                            </div>
                                            <div class="avatar-sm bg-warning rounded-circle d-flex align-items-center justify-content-center text-white me-1" style="width: 25px; height: 25px;">
                                                <i class="fas fa-user" style="font-size: 0.7rem;"></i>
                                            </div>
                                            <div class="avatar-sm bg-danger rounded-circle d-flex align-items-center justify-content-center text-white me-1" style="width: 25px; height: 25px;">
                                                <i class="fas fa-user" style="font-size: 0.7rem;"></i>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge status-approved">Onaylandı</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success me-1">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
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

    <!-- Add Report Modal -->
    <div class="modal fade" id="addReportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Toplantı Raporu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addReportForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rapor Başlığı</label>
                                <input type="text" class="form-control" id="reportTitle" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Toplantı Türü</label>
                                <select class="form-select" id="meetingType" required>
                                    <option value="">Seçiniz</option>
                                    <option value="weekly">Haftalık Toplantı</option>
                                    <option value="decision">Karar Toplantısı</option>
                                    <option value="planning">Planlama Toplantısı</option>
                                    <option value="review">Değerlendirme Toplantısı</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Toplantı Tarihi</label>
                                <input type="datetime-local" class="form-control" id="meetingDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Toplantı Yeri</label>
                                <input type="text" class="form-control" id="meetingLocation">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Katılımcılar</label>
                                <select class="form-select" id="participants" multiple>
                                    <option value="Ahmet Yılmaz">Ahmet Yılmaz</option>
                                    <option value="Fatma Demir">Fatma Demir</option>
                                    <option value="Mehmet Kaya">Mehmet Kaya</option>
                                    <option value="Ayşe Özkan">Ayşe Özkan</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Toplantı Konuları</label>
                                <textarea class="form-control" id="meetingTopics" rows="3" placeholder="Toplantıda ele alınan konular..."></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Alınan Kararlar</label>
                                <textarea class="form-control" id="decisions" rows="3" placeholder="Toplantıda alınan kararlar..."></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Aksiyonlar</label>
                                <textarea class="form-control" id="actions" rows="3" placeholder="Belirlenen aksiyonlar ve sorumlular..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="saveReport">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Search functionality
            $('input[type="text"]').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('tbody tr').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(searchTerm));
                });
            });
            
            // Save Report
            $('#saveReport').click(function() {
                const reportTitle = $('#reportTitle').val();
                const meetingType = $('#meetingType').val();
                const meetingDate = $('#meetingDate').val();
                
                if (reportTitle && meetingType && meetingDate) {
                    $('#addReportModal').modal('hide');
                    $('#addReportForm')[0].reset();
                    showNotification('Toplantı raporu başarıyla eklendi!', 'success');
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
