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
    <title>AIF Otomasyon - Yönetici Paneli</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        .admin-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            transition: transform 0.3s ease;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
        }
        
        .admin-stats {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .admin-feature {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .admin-management {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }
        
        .admin-reports {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
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
                    <h1>Yönetici Paneli</h1>
                    <p>Hoş geldiniz, <?php echo $currentUser['name'] ?? 'Yönetici'; ?>! Sistem yönetimi ve raporlama işlemlerinizi buradan gerçekleştirebilirsiniz.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline-light" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Yenile
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- İstatistik Kartları -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card admin-stats">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h3>89</h3>
                            <p class="mb-0">Aktif Kullanıcı</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card admin-stats">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-check fa-2x mb-2"></i>
                            <h3>15</h3>
                            <p class="mb-0">Bu Hafta Toplantı</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card admin-stats">
                        <div class="card-body text-center">
                            <i class="fas fa-euro-sign fa-2x mb-2"></i>
                            <h3>€1,250</h3>
                            <p class="mb-0">Bu Hafta Harcama</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card admin-stats">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <h3>%92</h3>
                            <p class="mb-0">Sistem Performansı</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yönetici Özellikleri -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="page-card admin-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calendar-alt fa-3x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="card-title">Rezervasyon Yönetimi</h5>
                                    <p class="card-text">Raggal rezervasyon başvurularını yönetin ve onaylayın</p>
                                    <a href="reservations.php" class="btn btn-light">
                                        <i class="fas fa-arrow-right"></i> Yönet
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="page-card admin-feature">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-file-invoice fa-3x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="card-title">Harcama Onayları</h5>
                                    <p class="card-text">Bekleyen harcama başvurularını inceleyin ve onaylayın</p>
                                    <a href="../admin/expenses.php" class="btn btn-light">
                                        <i class="fas fa-arrow-right"></i> Yönet
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sistem Yönetimi -->
            <div class="row mb-4">
                <div class="col-lg-4 mb-3">
                    <div class="page-card admin-management">
                        <div class="card-body text-center">
                            <i class="fas fa-user-friends fa-2x mb-3"></i>
                            <h5>Kullanıcı Raporları</h5>
                            <p class="mb-3">Kullanıcı aktivitelerini ve performanslarını görüntüleyin</p>
                            <button class="btn btn-light" onclick="generateUserReport()">
                                <i class="fas fa-chart-bar"></i> Rapor Oluştur
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="page-card admin-reports">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-week fa-2x mb-3"></i>
                            <h5>Toplantı Raporları</h5>
                            <p class="mb-3">Toplantı istatistikleri ve katılım raporları</p>
                            <a href="../admin/meeting_reports.php" class="btn btn-light">
                                <i class="fas fa-chart-pie"></i> Raporlar
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="page-card admin-feature">
                        <div class="card-body text-center">
                            <i class="fas fa-bell fa-2x mb-3"></i>
                            <h5>Bildirimler</h5>
                            <p class="mb-3">Sistem bildirimlerini yönetin</p>
                            <button class="btn btn-light" onclick="showNotifications()">
                                <i class="fas fa-envelope"></i> Görüntüle
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hızlı İşlemler -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-bolt"></i> Hızlı İşlemler</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-primary w-100" onclick="quickAction('approveReservation')">
                                <i class="fas fa-check-circle"></i><br>
                                <small>Rezervasyon Onayla</small>
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-success w-100" onclick="quickAction('viewMeetings')">
                                <i class="fas fa-calendar-check"></i><br>
                                <small>Toplantıları Görüntüle</small>
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-warning w-100" onclick="quickAction('approveExpenses')">
                                <i class="fas fa-euro-sign"></i><br>
                                <small>Harcama Onayla</small>
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-info w-100" onclick="quickAction('systemStatus')">
                                <i class="fas fa-server"></i><br>
                                <small>Sistem Durumu</small>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bekleyen İşlemler -->
            <div class="page-card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> Bekleyen İşlemler</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>5 Rezervasyon Başvurusu</strong> onay bekliyor
                                <button class="btn btn-sm btn-warning float-end" onclick="viewPendingReservations()">Görüntüle</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <i class="fas fa-euro-sign me-2"></i>
                                <strong>3 Harcama Başvurusu</strong> onay bekliyor
                                <button class="btn btn-sm btn-info float-end" onclick="viewPendingExpenses()">Görüntüle</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function refreshDashboard() {
            location.reload();
        }
        
        function quickAction(action) {
            switch(action) {
                case 'approveReservation':
                    window.location.href = 'reservations.php';
                    break;
                case 'viewMeetings':
                    window.location.href = '../admin/meeting_reports.php';
                    break;
                case 'approveExpenses':
                    window.location.href = '../admin/expenses.php';
                    break;
                case 'systemStatus':
                    showAlert('Sistem durumu: Tüm servisler çalışıyor', 'success');
                    break;
            }
        }
        
        function generateUserReport() {
            showAlert('Kullanıcı raporu oluşturuluyor...', 'info');
        }
        
        function showNotifications() {
            showAlert('3 yeni bildiriminiz var', 'info');
        }
        
        function viewPendingReservations() {
            window.location.href = 'reservations.php';
        }
        
        function viewPendingExpenses() {
            window.location.href = '../admin/expenses.php';
        }
        
        function showAlert(message, type) {
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
            
            // Auto remove after 3 seconds
            setTimeout(function() {
                alert.alert('close');
            }, 3000);
        }
        
        // Sayfa yüklendiğinde
        $(document).ready(function() {
            console.log('Yönetici Paneli (Users) yüklendi');
        });
    </script>
</body>
</html>

