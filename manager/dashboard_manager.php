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
                            <h3>156</h3>
                            <p class="mb-0">Toplam Kullanıcı</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card admin-stats">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-check fa-2x mb-2"></i>
                            <h3>23</h3>
                            <p class="mb-0">Bu Ay Toplantı</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card admin-stats">
                        <div class="card-body text-center">
                            <i class="fas fa-euro-sign fa-2x mb-2"></i>
                            <h3>€2,450</h3>
                            <p class="mb-0">Bu Ay Harcama</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card admin-stats">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <h3>%87</h3>
                            <p class="mb-0">Sistem Aktiflik</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yönetici Özellikleri -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="page-card admin-feature">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-file-alt fa-3x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="card-title">Toplantı Yönetimi</h5>
                                    <p class="card-text">Toplantıları planlayın, yönetin ve raporlarını görüntüleyin</p>
                                    <a href="meeting_reports.php" class="btn btn-light">
                                        <i class="fas fa-arrow-right"></i> Yönet
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="page-card admin-reports">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-euro-sign fa-3x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="card-title">Harcama Yönetimi</h5>
                                    <p class="card-text">Giderleri takip edin ve onaylayın</p>
                                    <a href="expenses.php" class="btn btn-light">
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
                <div class="col-lg-6 mb-3">
                    <div class="page-card admin-feature">
                        <div class="card-body text-center">
                            <i class="fas fa-database fa-2x mb-3"></i>
                            <h5>Sistem Ayarları</h5>
                            <p class="mb-3">Veritabanı ve sistem konfigürasyonları</p>
                            <a href="setup.php" class="btn btn-light">
                                <i class="fas fa-tools"></i> Ayarlar
                            </a>
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
                        <div class="col-md-4 mb-3">
                            <button class="btn btn-outline-success w-100" onclick="quickAction('newMeeting')">
                                <i class="fas fa-calendar-plus"></i><br>
                                <small>Yeni Toplantı</small>
                            </button>
                        </div>
                        <div class="col-md-4 mb-3">
                            <button class="btn btn-outline-warning w-100" onclick="quickAction('approveExpenses')">
                                <i class="fas fa-check-circle"></i><br>
                                <small>Harcama Onayla</small>
                            </button>
                        </div>
                        <div class="col-md-4 mb-3">
                            <button class="btn btn-outline-info w-100" onclick="quickAction('systemReport')">
                                <i class="fas fa-chart-pie"></i><br>
                                <small>Sistem Raporu</small>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Son Aktiviteler -->
            <div class="page-card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> Son Aktiviteler</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6>Yeni kullanıcı eklendi</h6>
                                <p class="text-muted mb-1">Ahmet Yılmaz sisteme eklendi</p>
                                <small class="text-muted">2 saat önce</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6>Toplantı oluşturuldu</h6>
                                <p class="text-muted mb-1">AT BYK Mart Toplantısı planlandı</p>
                                <small class="text-muted">4 saat önce</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6>Harcama onaylandı</h6>
                                <p class="text-muted mb-1">€150 ofis malzemeleri harcaması onaylandı</p>
                                <small class="text-muted">6 saat önce</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6>Sistem güncellemesi</h6>
                                <p class="text-muted mb-1">Code List yönetimi güncellendi</p>
                                <small class="text-muted">1 gün önce</small>
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
                case 'newMeeting':
                    window.location.href = 'meeting_reports.php';
                    break;
                case 'approveExpenses':
                    window.location.href = 'expenses.php';
                    break;
                case 'systemReport':
                    showAlert('Sistem raporu hazırlanıyor...', 'info');
                    break;
            }
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
            console.log('Yönetici Paneli yüklendi');
        });
    </script>
    
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -35px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #dee2e6;
        }
        
        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: -29px;
            top: 17px;
            width: 2px;
            height: calc(100% + 20px);
            background: #dee2e6;
        }
    </style>
</body>
</html>
