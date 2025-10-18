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
                        <h3><?php echo count($users); ?></h3>
                        <p>Toplam Kullanıcı</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon announcements">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <h3>0</h3>
                        <p>Aktif Duyuru</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon events">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>0</h3>
                        <p>Yaklaşan Etkinlik</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon reservations">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h3>0</h3>
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
                        <div class="card-body">
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Henüz bekleyen talep bulunmuyor</h5>
                                <p class="text-muted">Yeni talepler burada görünecek</p>
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
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Henüz işlem bulunmuyor</h5>
                                <p class="text-muted">Sistem aktiviteleri burada görünecek</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <!-- User Growth Chart -->
                <div class="col-lg-6 mb-4">
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
                <div class="col-lg-6 mb-4">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> Aylık İade Talepleri</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="refundChart"></canvas>
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
        // Charts
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'bar',
            data: {
                labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                datasets: [{
                    label: 'Yeni Kullanıcılar',
                    data: [0, 0, 0, 0, 0, 0],
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

        const refundCtx = document.getElementById('refundChart').getContext('2d');
        new Chart(refundCtx, {
            type: 'line',
            data: {
                labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                datasets: [{
                    label: 'İade Talepleri',
                    data: [0, 0, 0, 0, 0, 0],
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

        // Logout function
        function logout() {
            if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                $.ajax({
                    url: 'auth.php',
                    method: 'POST',
                    data: { action: 'logout' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.redirect;
                        }
                    }
                });
            }
        }

        // Mobile sidebar toggle
        $('.navbar-toggler').click(function() {
            $('.sidebar').toggleClass('show');
        });
    </script>
</body>
</html>