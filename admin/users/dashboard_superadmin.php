<?php
require_once 'auth.php';
require_once 'includes/byk_manager.php';

// Session kontrolü - sadece superadmin giriş yapabilir
SessionManager::requireRole('superadmin');

$currentUser = SessionManager::getCurrentUser();
$userManager = new UserManager();
$users = $userManager->getAllUsers();
$bykStats = BYKManager::getBYKStats();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Superadmin Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        /* Dashboard Specific Styles */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
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
                    <h1>Superadmin Dashboard</h1>
                </div>
                <div class="header-actions">
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['full_name']); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Ayarlar</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Çıkış</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- BYK Statistics -->
            <div class="row mb-4">
                <?php foreach ($bykStats as $bykCode => $bykData): ?>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card byk-<?php echo strtolower($bykCode); ?>-border">
                        <div class="icon" style="background: <?php echo BYKManager::getBYKPrimaryColor($bykCode); ?>;">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3 style="color: <?php echo BYKManager::getBYKPrimaryColor($bykCode); ?>;">
                            <?php echo count(array_filter($users, function($user) use ($bykCode) { return isset($user['byk']) && $user['byk'] === $bykCode; })); ?>
                        </h3>
                        <p><?php echo $bykCode; ?> - <?php echo $bykData['name']; ?></p>
                        <small class="text-muted"><?php echo $bykData['sub_units_count']; ?> Alt Birim</small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>156</h3>
                        <p>Toplam Kullanıcı</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon announcements">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <h3>12</h3>
                        <p>Aktif Duyuru</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon events">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>8</h3>
                        <p>Yaklaşan Etkinlik</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon reservations">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h3>5</h3>
                        <p>Bekleyen İade</p>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row mb-4">
                <!-- Pending Approvals Table -->
                <div class="col-lg-8">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-clock"></i> Bekleyen Onay Talepleri</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-card">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>Talep Türü</th>
                                            <th>Tarih</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-2">AY</div>
                                                    <div>
                                                        <div class="fw-bold">Ahmet Yılmaz</div>
                                                        <small class="text-muted">ahmet@example.com</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Hesap Aktivasyonu</td>
                                            <td>20.01.2024</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-success me-1">Onayla</button>
                                                <button class="btn btn-sm btn-danger">Reddet</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-2">FD</div>
                                                    <div>
                                                        <div class="fw-bold">Fatma Demir</div>
                                                        <small class="text-muted">fatma@example.com</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Yetki Değişikliği</td>
                                            <td>19.01.2024</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-success me-1">Onayla</button>
                                                <button class="btn btn-sm btn-danger">Reddet</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-2">MK</div>
                                                    <div>
                                                        <div class="fw-bold">Mehmet Kaya</div>
                                                        <small class="text-muted">mehmet@example.com</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>İade Talebi</td>
                                            <td>18.01.2024</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-success me-1">Onayla</button>
                                                <button class="btn btn-sm btn-danger">Reddet</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="col-lg-4">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-history"></i> Son İşlemler</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Yeni Kullanıcı Eklendi</h6>
                                    <small class="text-muted">Ayşe Özkan - 2 saat önce</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-bullhorn"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Duyuru Yayınlandı</h6>
                                    <small class="text-muted">Sistem Bakımı - 4 saat önce</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Etkinlik Oluşturuldu</h6>
                                    <small class="text-muted">Haftalık Toplantı - 6 saat önce</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Sistem Uyarısı</h6>
                                    <small class="text-muted">Yüksek CPU Kullanımı - 8 saat önce</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <!-- User Growth Chart -->
                <div class="col-lg-6">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar"></i> Aylık Kullanıcı Artışı</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="userGrowthChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Refund Requests Chart -->
                <div class="col-lg-6">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> Aylık İade Talepleri</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="refundRequestsChart"></canvas>
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
        $(document).ready(function() {
            // Initialize Charts
            initializeCharts();
            
            // Logout function
            window.logout = function() {
                if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                    $.ajax({
                        url: 'auth.php',
                        type: 'POST',
                        data: { action: 'logout' },
                        success: function(response) {
                            window.location.href = '../index.php';
                        },
                        error: function() {
                            window.location.href = '../index.php';
                        }
                    });
                }
            };
            
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
            
            // Auto refresh data every 5 minutes
            setInterval(function() {
                // Refresh statistics and charts
                console.log('Data refreshed');
            }, 300000);
        });
        
        // Initialize Charts
        function initializeCharts() {
            // User Growth Chart
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(userGrowthCtx, {
                type: 'bar',
                data: {
                    labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                    datasets: [{
                        label: 'Yeni Kullanıcılar',
                        data: [12, 19, 15, 25, 22, 30],
                        backgroundColor: 'rgba(0, 152, 114, 0.8)',
                        borderColor: 'rgba(0, 152, 114, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Refund Requests Chart
            const refundRequestsCtx = document.getElementById('refundRequestsChart').getContext('2d');
            new Chart(refundRequestsCtx, {
                type: 'line',
                data: {
                    labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                    datasets: [{
                        label: 'İade Talepleri',
                        data: [5, 8, 12, 6, 9, 15],
                        borderColor: 'rgba(220, 53, 69, 1)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>