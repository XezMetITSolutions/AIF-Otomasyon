<?php
require_once 'auth.php';

// Login kontrolü
SessionManager::requireRole(['superadmin', 'manager']);
$currentUser = SessionManager::getCurrentUser();

// Gerçek verileri veritabanından çek
require_once 'includes/user_manager_db.php';

try {
    $users = UserManager::getAllUsers();
    $userStats = UserManager::getUserStats();
    
    // Dashboard istatistikleri
    $dashboardStats = [
        'total_users' => $userStats['total'],
        'active_users' => $userStats['active'],
        'total_events' => 0, // Bu veri henüz yok
        'pending_approvals' => 0, // Bu veri henüz yok
        'monthly_revenue' => 0, // Bu veri henüz yok
        'growth_rate' => 0 // Bu veri henüz yok
    ];
    
    // Son aktiviteler (şimdilik boş)
    $recentActivities = [];
    
} catch (Exception $e) {
    // Hata durumunda varsayılan veriler
    $users = [];
    $userStats = ['total' => 0, 'active' => 0];
    $dashboardStats = [
        'total_users' => 0,
        'active_users' => 0,
        'total_events' => 0,
        'pending_approvals' => 0,
        'monthly_revenue' => 0,
        'growth_rate' => 0
    ];
    $recentActivities = [];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Modern Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        /* Dashboard specific styles */
        :root {
            --primary-color: #009872;
            --primary-dark: #007a5e;
            --primary-light: #00b085;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            --dark-color: #212529;
            --light-color: #f8f9fa;
            --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-medium: 0 4px 20px rgba(0,0,0,0.15);
            --shadow-heavy: 0 8px 30px rgba(0,0,0,0.2);
        }

        /* Modern Header */
        .modern-header {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .header-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
            margin-top: 5px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .user-info h6 {
            margin: 0;
            font-weight: 600;
            color: var(--dark-color);
        }

        .user-info small {
            color: #6c757d;
        }

        /* Modern Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
        }

        .stat-card.success::before {
            background: var(--success-color);
        }

        .stat-card.warning::before {
            background: var(--warning-color);
        }

        .stat-card.danger::before {
            background: var(--danger-color);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: var(--primary-color);
        }

        .stat-icon.success {
            background: var(--success-color);
        }

        .stat-icon.warning {
            background: var(--warning-color);
        }

        .stat-icon.danger {
            background: var(--danger-color);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 1rem;
            margin: 5px 0 0 0;
        }

        .stat-change {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .stat-change.positive {
            color: #28a745;
        }

        .stat-change.negative {
            color: #dc3545;
        }

        /* Modern Content Cards */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary-color);
        }

        /* Activity Feed */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
        }

        .activity-icon.success {
            background: var(--success-color);
        }

        .activity-icon.info {
            background: var(--info-color);
        }

        .activity-icon.warning {
            background: var(--warning-color);
        }

        .activity-icon.primary {
            background: var(--primary-color);
        }

        .activity-content {
            flex: 1;
        }

        .activity-message {
            font-weight: 500;
            color: var(--dark-color);
            margin: 0;
        }

        .activity-time {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .action-btn {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--dark-color);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
            color: var(--dark-color);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.2rem;
        }

        .action-title {
            font-weight: 600;
            margin: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .header-title h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .content-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in-left {
            animation: slideInLeft 0.6s ease-out;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .slide-in-right {
            animation: slideInRight 0.6s ease-out;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Modern Header -->
        <div class="modern-header fade-in">
            <div class="header-content">
                <div class="header-title">
                    <h1>Modern Dashboard</h1>
                    <div class="header-subtitle">AIF Otomasyon Sistemi - Genel Bakış</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <h6><?php echo htmlspecialchars($currentUser['full_name']); ?></h6>
                        <small><?php echo ucfirst($currentUser['role']); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card success slide-in-left" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-header">
                    <div class="stat-icon success">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $dashboardStats['total_users']; ?></h3>
                <p class="stat-label">Toplam Kullanıcı</p>
            </div>

            <div class="stat-card warning slide-in-left" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-header">
                    <div class="stat-icon warning">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $dashboardStats['total_events']; ?></h3>
                <p class="stat-label">Aktif Etkinlik</p>
            </div>

            <div class="stat-card danger slide-in-right" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-header">
                    <div class="stat-icon danger">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $dashboardStats['pending_approvals']; ?></h3>
                <p class="stat-label">Bekleyen Onay</p>
            </div>

            <div class="stat-card slide-in-right" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <h3 class="stat-value">₺<?php echo number_format($dashboardStats['monthly_revenue']); ?></h3>
                <p class="stat-label">Aylık Gelir</p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Recent Activities -->
            <div class="content-card fade-in" data-aos="fade-right">
                <h5 class="card-title">
                    <i class="fas fa-history"></i>
                    Son Aktiviteler
                </h5>
                <div class="activity-feed">
                    <?php if (empty($recentActivities)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-history fa-2x mb-2"></i><br>
                        Henüz aktivite bulunmamaktadır.
                    </div>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $activity['color']; ?>">
                                <i class="<?php echo $activity['icon']; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p class="activity-message"><?php echo $activity['message']; ?></p>
                                <p class="activity-time"><?php echo $activity['time']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="content-card fade-in" data-aos="fade-left">
                <h5 class="card-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Sistem Durumu
                </h5>
                <div class="system-status">
                    <div class="status-item">
                        <div class="status-indicator success"></div>
                        <span>Veritabanı Bağlantısı</span>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator success"></div>
                        <span>E-posta Servisi</span>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator warning"></div>
                        <span>Yedekleme Servisi</span>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator success"></div>
                        <span>Güvenlik Sistemi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <div class="chart-card fade-in" data-aos="fade-up" data-aos-delay="100">
                <h5 class="card-title">
                    <i class="fas fa-chart-bar"></i>
                    Kullanıcı Büyümesi
                </h5>
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>

            <div class="chart-card fade-in" data-aos="fade-up" data-aos-delay="200">
                <h5 class="card-title">
                    <i class="fas fa-chart-pie"></i>
                    Etkinlik Dağılımı
                </h5>
                <div class="chart-container">
                    <canvas id="eventDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="calendar.php" class="action-btn" data-aos="zoom-in" data-aos-delay="100">
                <div class="action-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <h6 class="action-title">Takvim</h6>
            </a>
            <a href="settings.php" class="action-btn" data-aos="zoom-in" data-aos-delay="200">
                <div class="action-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h6 class="action-title">Sistem Ayarları</h6>
            </a>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'bar',
            data: {
                labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                datasets: [{
                    label: 'Yeni Kullanıcılar',
                    data: [12, 19, 15, 25, 22, 30],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Event Distribution Chart
        const eventDistributionCtx = document.getElementById('eventDistributionChart').getContext('2d');
        new Chart(eventDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['AT', 'KT', 'GT', 'KGT'],
                datasets: [{
                    data: [35, 25, 20, 20],
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(79, 172, 254, 0.8)',
                        'rgba(240, 147, 251, 0.8)',
                        'rgba(245, 87, 108, 0.8)'
                    ],
                    borderColor: [
                        'rgba(102, 126, 234, 1)',
                        'rgba(79, 172, 254, 1)',
                        'rgba(240, 147, 251, 1)',
                        'rgba(245, 87, 108, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Add system status styles
        const style = document.createElement('style');
        style.textContent = `
            .status-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 0;
                border-bottom: 1px solid #f1f3f4;
            }
            .status-item:last-child {
                border-bottom: none;
            }
            .status-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
            }
            .status-indicator.success {
                background: #28a745;
                box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
            }
            .status-indicator.warning {
                background: #ffc107;
                box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
            }
            .status-indicator.danger {
                background: #dc3545;
                box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
            }
        `;
        document.head.appendChild(style);

        // Add smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add loading animation
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });

    </script>
</body>
</html>
</html>
<?php
require_once 'auth.php';

// Login kontrolü kaldırıldı - direkt erişim
$currentUser = SessionManager::getCurrentUser();

// Varsayılan veriler
$users = [
    ['id' => 1, 'username' => 'admin', 'full_name' => 'Sistem Yöneticisi', 'role' => 'superadmin', 'status' => 'active'],
    ['id' => 2, 'username' => 'AIF-Admin', 'full_name' => 'AIF Yöneticisi', 'role' => 'superadmin', 'status' => 'active']
];

$bykStats = [
    'total_users' => 2,
    'active_users' => 2,
    'by_role' => ['superadmin' => 2],
    'by_byk' => []
];

// Dashboard istatistikleri
$dashboardStats = [
    'total_users' => 2,
    'active_users' => 2,
    'total_events' => 15,
    'pending_approvals' => 3,
    'monthly_revenue' => 12500,
    'growth_rate' => 12.5
];

// Son aktiviteler
$recentActivities = [
    ['type' => 'user', 'message' => 'Yeni kullanıcı kaydı', 'time' => '2 saat önce', 'icon' => 'fas fa-user-plus', 'color' => 'success'],
    ['type' => 'event', 'message' => 'Yeni etkinlik eklendi', 'time' => '4 saat önce', 'icon' => 'fas fa-calendar-plus', 'color' => 'info'],
    ['type' => 'approval', 'message' => 'Onay bekleyen talep', 'time' => '6 saat önce', 'icon' => 'fas fa-clock', 'color' => 'warning'],
    ['type' => 'system', 'message' => 'Sistem güncellemesi', 'time' => '1 gün önce', 'icon' => 'fas fa-cog', 'color' => 'primary']
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Modern Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        /* Dashboard specific styles */
        :root {
            --primary-color: #009872;
            --primary-dark: #007a5e;
            --primary-light: #00b085;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            --dark-color: #212529;
            --light-color: #f8f9fa;
            --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-medium: 0 4px 20px rgba(0,0,0,0.15);
            --shadow-heavy: 0 8px 30px rgba(0,0,0,0.2);
        }

        /* Modern Header */
        .modern-header {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .header-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
            margin-top: 5px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .user-info h6 {
            margin: 0;
            font-weight: 600;
            color: var(--dark-color);
        }

        .user-info small {
            color: #6c757d;
        }

        /* Modern Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
        }

        .stat-card.success::before {
            background: var(--success-color);
        }

        .stat-card.warning::before {
            background: var(--warning-color);
        }

        .stat-card.danger::before {
            background: var(--danger-color);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: var(--primary-color);
        }

        .stat-icon.success {
            background: var(--success-color);
        }

        .stat-icon.warning {
            background: var(--warning-color);
        }

        .stat-icon.danger {
            background: var(--danger-color);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 1rem;
            margin: 5px 0 0 0;
        }

        .stat-change {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .stat-change.positive {
            color: #28a745;
        }

        .stat-change.negative {
            color: #dc3545;
        }

        /* Modern Content Cards */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary-color);
        }

        /* Activity Feed */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
        }

        .activity-icon.success {
            background: var(--success-color);
        }

        .activity-icon.info {
            background: var(--info-color);
        }

        .activity-icon.warning {
            background: var(--warning-color);
        }

        .activity-icon.primary {
            background: var(--primary-color);
        }

        .activity-content {
            flex: 1;
        }

        .activity-message {
            font-weight: 500;
            color: var(--dark-color);
            margin: 0;
        }

        .activity-time {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .action-btn {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--dark-color);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
            color: var(--dark-color);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.2rem;
        }

        .action-title {
            font-weight: 600;
            margin: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .header-title h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .content-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in-left {
            animation: slideInLeft 0.6s ease-out;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .slide-in-right {
            animation: slideInRight 0.6s ease-out;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Modern Header -->
        <div class="modern-header fade-in">
            <div class="header-content">
                <div class="header-title">
                    <h1>Modern Dashboard</h1>
                    <div class="header-subtitle">AIF Otomasyon Sistemi - Genel Bakış</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <h6><?php echo htmlspecialchars($currentUser['full_name']); ?></h6>
                        <small><?php echo ucfirst($currentUser['role']); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card success slide-in-left" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-header">
                    <div class="stat-icon success">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $dashboardStats['total_users']; ?></h3>
                <p class="stat-label">Toplam Kullanıcı</p>
            </div>

            <div class="stat-card warning slide-in-left" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-header">
                    <div class="stat-icon warning">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $dashboardStats['total_events']; ?></h3>
                <p class="stat-label">Aktif Etkinlik</p>
            </div>

            <div class="stat-card danger slide-in-right" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-header">
                    <div class="stat-icon danger">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $dashboardStats['pending_approvals']; ?></h3>
                <p class="stat-label">Bekleyen Onay</p>
            </div>

            <div class="stat-card slide-in-right" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <h3 class="stat-value">₺<?php echo number_format($dashboardStats['monthly_revenue']); ?></h3>
                <p class="stat-label">Aylık Gelir</p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Recent Activities -->
            <div class="content-card fade-in" data-aos="fade-right">
                <h5 class="card-title">
                    <i class="fas fa-history"></i>
                    Son Aktiviteler
                </h5>
                <div class="activity-feed">
                    <?php foreach ($recentActivities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?php echo $activity['color']; ?>">
                            <i class="<?php echo $activity['icon']; ?>"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-message"><?php echo $activity['message']; ?></p>
                            <p class="activity-time"><?php echo $activity['time']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="content-card fade-in" data-aos="fade-left">
                <h5 class="card-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Sistem Durumu
                </h5>
                <div class="system-status">
                    <div class="status-item">
                        <div class="status-indicator success"></div>
                        <span>Veritabanı Bağlantısı</span>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator success"></div>
                        <span>E-posta Servisi</span>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator warning"></div>
                        <span>Yedekleme Servisi</span>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator success"></div>
                        <span>Güvenlik Sistemi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <div class="chart-card fade-in" data-aos="fade-up" data-aos-delay="100">
                <h5 class="card-title">
                    <i class="fas fa-chart-bar"></i>
                    Kullanıcı Büyümesi
                </h5>
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>

            <div class="chart-card fade-in" data-aos="fade-up" data-aos-delay="200">
                <h5 class="card-title">
                    <i class="fas fa-chart-pie"></i>
                    Etkinlik Dağılımı
                </h5>
                <div class="chart-container">
                    <canvas id="eventDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="users.php" class="action-btn" data-aos="zoom-in" data-aos-delay="100">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h6 class="action-title">Kullanıcı Yönetimi</h6>
            </a>
            <a href="events.php" class="action-btn" data-aos="zoom-in" data-aos-delay="200">
                <div class="action-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h6 class="action-title">Etkinlik Ekle</h6>
            </a>
            <a href="reports.php" class="action-btn" data-aos="zoom-in" data-aos-delay="300">
                <div class="action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h6 class="action-title">Raporlar</h6>
            </a>
            <a href="settings.php" class="action-btn" data-aos="zoom-in" data-aos-delay="400">
                <div class="action-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h6 class="action-title">Sistem Ayarları</h6>
            </a>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'bar',
            data: {
                labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                datasets: [{
                    label: 'Yeni Kullanıcılar',
                    data: [12, 19, 15, 25, 22, 30],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Event Distribution Chart
        const eventDistributionCtx = document.getElementById('eventDistributionChart').getContext('2d');
        new Chart(eventDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['AT', 'KT', 'GT', 'KGT'],
                datasets: [{
                    data: [35, 25, 20, 20],
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(79, 172, 254, 0.8)',
                        'rgba(240, 147, 251, 0.8)',
                        'rgba(245, 87, 108, 0.8)'
                    ],
                    borderColor: [
                        'rgba(102, 126, 234, 1)',
                        'rgba(79, 172, 254, 1)',
                        'rgba(240, 147, 251, 1)',
                        'rgba(245, 87, 108, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Add system status styles
        const style = document.createElement('style');
        style.textContent = `
            .status-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 0;
                border-bottom: 1px solid #f1f3f4;
            }
            .status-item:last-child {
                border-bottom: none;
            }
            .status-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
            }
            .status-indicator.success {
                background: #28a745;
                box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
            }
            .status-indicator.warning {
                background: #ffc107;
                box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
            }
            .status-indicator.danger {
                background: #dc3545;
                box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
            }
        `;
        document.head.appendChild(style);

        // Add smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add loading animation
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });

    </script>
</body>
</html>