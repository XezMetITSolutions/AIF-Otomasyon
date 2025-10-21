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
    <title>AIF Otomasyon - Proje Takibi</title>
    
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
                    <h1>Proje Takibi</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                        <i class="fas fa-plus"></i> Proje Ekle
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Project Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary">12</h3>
                            <p class="text-muted mb-0">Toplam Proje</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success">8</h3>
                            <p class="text-muted mb-0">Aktif</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">2</h3>
                            <p class="text-muted mb-0">Beklemede</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-info">2</h3>
                            <p class="text-muted mb-0">Tamamlanan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Projects -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="project-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="project-icon web">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">Web Sitesi Yenileme</h5>
                                    <p class="text-muted mb-1">Proje Yöneticisi: Ahmet Yılmaz</p>
                                    <div class="d-flex align-items-center">
                                        <span class="badge status-active me-2">Aktif</span>
                                        <span class="badge priority-high">Yüksek</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>İlerleme</small>
                                    <small>75%</small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 75%"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Başlangıç: 01.01.2024</small>
                                <small class="text-muted">Bitiş: 31.03.2024</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="project-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="project-icon mobile">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">Mobil Uygulama</h5>
                                    <p class="text-muted mb-1">Proje Yöneticisi: Fatma Demir</p>
                                    <div class="d-flex align-items-center">
                                        <span class="badge status-active me-2">Aktif</span>
                                        <span class="badge priority-medium">Orta</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>İlerleme</small>
                                    <small>45%</small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 45%"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Başlangıç: 15.01.2024</small>
                                <small class="text-muted">Bitiş: 30.04.2024</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Management -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-project-diagram"></i> Proje Listesi</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Proje ara...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <select class="form-select">
                                    <option>Tüm Durumlar</option>
                                    <option>Planlama</option>
                                    <option>Aktif</option>
                                    <option>Beklemede</option>
                                    <option>Tamamlandı</option>
                                    <option>İptal Edildi</option>
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
                                    <th>Proje</th>
                                    <th>Yönetici</th>
                                    <th>Durum</th>
                                    <th>Öncelik</th>
                                    <th>İlerleme</th>
                                    <th>Bitiş Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Web Sitesi Yenileme</div>
                                        <small class="text-muted">#001</small>
                                    </td>
                                    <td>Ahmet Yılmaz</td>
                                    <td><span class="badge status-active">Aktif</span></td>
                                    <td><span class="badge priority-high">Yüksek</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 100px; height: 6px;">
                                                <div class="progress-bar" style="width: 75%"></div>
                                            </div>
                                            <small>75%</small>
                                        </div>
                                    </td>
                                    <td>31.03.2024</td>
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
                                        <div class="fw-bold">Mobil Uygulama</div>
                                        <small class="text-muted">#002</small>
                                    </td>
                                    <td>Fatma Demir</td>
                                    <td><span class="badge status-active">Aktif</span></td>
                                    <td><span class="badge priority-medium">Orta</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 100px; height: 6px;">
                                                <div class="progress-bar" style="width: 45%"></div>
                                            </div>
                                            <small>45%</small>
                                        </div>
                                    </td>
                                    <td>30.04.2024</td>
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
                                        <div class="fw-bold">Logo Tasarımı</div>
                                        <small class="text-muted">#003</small>
                                    </td>
                                    <td>Mehmet Kaya</td>
                                    <td><span class="badge status-completed">Tamamlandı</span></td>
                                    <td><span class="badge priority-low">Düşük</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 100px; height: 6px;">
                                                <div class="progress-bar" style="width: 100%"></div>
                                            </div>
                                            <small>100%</small>
                                        </div>
                                    </td>
                                    <td>15.01.2024</td>
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

            <!-- Project Tasks -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-tasks"></i> Proje Görevleri</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Web Sitesi Yenileme</h6>
                            <div class="task-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Ana sayfa tasarımı</strong>
                                        <br><small class="text-muted">Ahmet Yılmaz</small>
                                    </div>
                                    <span class="badge status-active">Devam Ediyor</span>
                                </div>
                            </div>
                            <div class="task-item completed">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Wireframe oluşturma</strong>
                                        <br><small class="text-muted">Fatma Demir</small>
                                    </div>
                                    <span class="badge status-completed">Tamamlandı</span>
                                </div>
                            </div>
                            <div class="task-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Responsive tasarım</strong>
                                        <br><small class="text-muted">Mehmet Kaya</small>
                                    </div>
                                    <span class="badge status-planning">Planlandı</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Mobil Uygulama</h6>
                            <div class="task-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>UI/UX tasarımı</strong>
                                        <br><small class="text-muted">Ayşe Özkan</small>
                                    </div>
                                    <span class="badge status-active">Devam Ediyor</span>
                                </div>
                            </div>
                            <div class="task-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Backend API</strong>
                                        <br><small class="text-muted">Can Yılmaz</small>
                                    </div>
                                    <span class="badge status-planning">Planlandı</span>
                                </div>
                            </div>
                            <div class="task-item completed">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Proje analizi</strong>
                                        <br><small class="text-muted">Fatma Demir</small>
                                    </div>
                                    <span class="badge status-completed">Tamamlandı</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Project Modal -->
    <div class="modal fade" id="addProjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Proje Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProjectForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Proje Adı</label>
                                <input type="text" class="form-control" id="projectName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Proje Yöneticisi</label>
                                <select class="form-select" id="projectManager" required>
                                    <option value="">Seçiniz</option>
                                    <option value="Ahmet Yılmaz">Ahmet Yılmaz</option>
                                    <option value="Fatma Demir">Fatma Demir</option>
                                    <option value="Mehmet Kaya">Mehmet Kaya</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="startDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="endDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" id="projectStatus" required>
                                    <option value="planning">Planlama</option>
                                    <option value="active">Aktif</option>
                                    <option value="on-hold">Beklemede</option>
                                    <option value="completed">Tamamlandı</option>
                                    <option value="cancelled">İptal Edildi</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Öncelik</label>
                                <select class="form-select" id="projectPriority" required>
                                    <option value="low">Düşük</option>
                                    <option value="medium">Orta</option>
                                    <option value="high">Yüksek</option>
                                    <option value="critical">Kritik</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bütçe</label>
                                <input type="number" class="form-control" id="projectBudget" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İlerleme (%)</label>
                                <input type="number" class="form-control" id="projectProgress" min="0" max="100" value="0">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Proje Açıklaması</label>
                                <textarea class="form-control" id="projectDescription" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="saveProject">Kaydet</button>
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
            
            // Save Project
            $('#saveProject').click(function() {
                const projectName = $('#projectName').val();
                const projectManager = $('#projectManager').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                const projectStatus = $('#projectStatus').val();
                
                if (projectName && projectManager && startDate && endDate && projectStatus) {
                    $('#addProjectModal').modal('hide');
                    $('#addProjectForm')[0].reset();
                    showNotification('Proje başarıyla eklendi!', 'success');
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
