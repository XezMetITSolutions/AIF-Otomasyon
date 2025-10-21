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
    <title>AIF Otomasyon - Yetki Yönetimi</title>
    
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
                    <h1>Yetki Yönetimi</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="fas fa-plus"></i> Rol Ekle
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Permission Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary">4</h3>
                            <p class="text-muted mb-0">Toplam Rol</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success">12</h3>
                            <p class="text-muted mb-0">Aktif Modül</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">48</h3>
                            <p class="text-muted mb-0">Toplam İzin</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-info">156</h3>
                            <p class="text-muted mb-0">Kullanıcı Ataması</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Cards -->
            <div class="row mb-4">
                <div class="col-lg-4 mb-3">
                    <div class="permission-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="permission-icon admin">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">Superadmin</h5>
                                    <p class="text-muted mb-1">Tam Yetki</p>
                                    <span class="badge status-active">Aktif</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Modül Sayısı: 12/12</small>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: 100%"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Kullanıcı: 2</small>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="permission-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="permission-icon member">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">Üye</h5>
                                    <p class="text-muted mb-1">Sınırlı Yetki</p>
                                    <span class="badge status-active">Aktif</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Modül Sayısı: 6/12</small>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: 50%"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Kullanıcı: 1245</small>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="permission-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="permission-icon guest">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">Misafir</h5>
                                    <p class="text-muted mb-1">Sadece Görüntüleme</p>
                                    <span class="badge status-inactive">Pasif</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Modül Sayısı: 2/12</small>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-secondary" style="width: 17%"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Kullanıcı: 0</small>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permission Matrix -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-shield-alt"></i> İzin Matrisi</h5>
                </div>
                <div class="card-body p-0">
                    <div class="permission-matrix">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Modül</th>
                                    <th>Superadmin</th>
                                    <th>Üye</th>
                                    <th>Misafir</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Dashboard</strong></td>
                                    <td>
                                        <span class="badge permission-admin">Admin</span>
                                    </td>
                                    <td>
                                        <span class="badge permission-read">Read</span>
                                    </td>
                                    <td>
                                        <span class="badge permission-read">Read</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Kullanıcılar</strong></td>
                                    <td>
                                        <span class="badge permission-admin">Admin</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Yok</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Yok</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Duyurular</strong></td>
                                    <td>
                                        <span class="badge permission-admin">Admin</span>
                                    </td>
                                    <td>
                                        <span class="badge permission-read">Read</span>
                                    </td>
                                    <td>
                                        <span class="badge permission-read">Read</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Takvim</strong></td>
                                    <td>
                                        <span class="badge permission-admin">Admin</span>
                                    </td>
                                    <td>
                                        <span class="badge permission-write">Write</span>
                                    </td>
                                    <td>
                                        <span class="badge permission-read">Read</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Rezervasyon</strong></td>
                                    <td>
                                        <span class="badge permission-admin">Admin</span>
                                    </td>
                                    <td>
                                        <span class="badge permission-write">Write</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Yok</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Proje Takibi</strong></td>
                                    <td>
                                        <span class="badge permission-admin">Admin</span>
                                    </td>
                                    <td>
                                        <span class="badge permission-read">Read</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Yok</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Role Management -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-users-cog"></i> Rol Yönetimi</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Rol ara...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <select class="form-select">
                                    <option>Tüm Durumlar</option>
                                    <option>Aktif</option>
                                    <option>Pasif</option>
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
                                    <th>Rol Adı</th>
                                    <th>Açıklama</th>
                                    <th>Modül Sayısı</th>
                                    <th>Kullanıcı Sayısı</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Superadmin</div>
                                        <small class="text-muted">Tam yetki</small>
                                    </td>
                                    <td>Sistem yöneticisi</td>
                                    <td><span class="badge bg-success">12/12</span></td>
                                    <td><span class="badge bg-primary">2</span></td>
                                    <td><span class="badge status-active">Aktif</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Üye</div>
                                        <small class="text-muted">Sınırlı yetki</small>
                                    </td>
                                    <td>Normal kullanıcı</td>
                                    <td><span class="badge bg-warning">6/12</span></td>
                                    <td><span class="badge bg-primary">1245</span></td>
                                    <td><span class="badge status-active">Aktif</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Misafir</div>
                                        <small class="text-muted">Sadece görüntüleme</small>
                                    </td>
                                    <td>Geçici erişim</td>
                                    <td><span class="badge bg-secondary">2/12</span></td>
                                    <td><span class="badge bg-primary">0</span></td>
                                    <td><span class="badge status-inactive">Pasif</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
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

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Rol Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addRoleForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol Adı</label>
                                <input type="text" class="form-control" id="roleName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol Kodu</label>
                                <input type="text" class="form-control" id="roleCode" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" id="roleDescription" rows="3"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" id="roleStatus" required>
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Pasif</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Renk</label>
                                <select class="form-select" id="roleColor" required>
                                    <option value="#dc3545">Kırmızı</option>
                                    <option value="#28a745">Yeşil</option>
                                    <option value="#007bff">Mavi</option>
                                    <option value="#ffc107">Sarı</option>
                                    <option value="#6f42c1">Mor</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <h6>Modül İzinleri</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permDashboard">
                                            <label class="form-check-label" for="permDashboard">Dashboard</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permUsers">
                                            <label class="form-check-label" for="permUsers">Kullanıcılar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permAnnouncements">
                                            <label class="form-check-label" for="permAnnouncements">Duyurular</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permCalendar">
                                            <label class="form-check-label" for="permCalendar">Takvim</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permReservations">
                                            <label class="form-check-label" for="permReservations">Rezervasyon</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permProjects">
                                            <label class="form-check-label" for="permProjects">Proje Takibi</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permReports">
                                            <label class="form-check-label" for="permReports">Raporlar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permSettings">
                                            <label class="form-check-label" for="permSettings">Ayarlar</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="saveRole">Kaydet</button>
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
            
            // Save Role
            $('#saveRole').click(function() {
                const roleName = $('#roleName').val();
                const roleCode = $('#roleCode').val();
                const roleStatus = $('#roleStatus').val();
                
                if (roleName && roleCode && roleStatus) {
                    $('#addRoleModal').modal('hide');
                    $('#addRoleForm')[0].reset();
                    showNotification('Rol başarıyla eklendi!', 'success');
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
