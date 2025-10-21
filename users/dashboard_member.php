<?php
require_once 'auth.php';

// Login kontrolü
UserSessionManager::requireLogin();
$currentUser = UserSessionManager::getCurrentUser();

// Üye kullanıcı için varsayılan veriler
$userStats = [
    'total_events' => 15,
    'my_reservations' => 3,
    'pending_approvals' => 2,
    'completed_projects' => 8
];

$recentActivities = [
    ['type' => 'event', 'message' => 'Yeni etkinlik kaydı yaptınız', 'time' => '2 saat önce', 'icon' => 'fas fa-calendar-plus', 'color' => 'success'],
    ['type' => 'reservation', 'message' => 'Rezervasyon talebiniz onaylandı', 'time' => '4 saat önce', 'icon' => 'fas fa-check-circle', 'color' => 'info'],
    ['type' => 'project', 'message' => 'Proje göreviniz tamamlandı', 'time' => '1 gün önce', 'icon' => 'fas fa-tasks', 'color' => 'primary'],
    ['type' => 'announcement', 'message' => 'Yeni duyuru yayınlandı', 'time' => '2 gün önce', 'icon' => 'fas fa-bullhorn', 'color' => 'warning']
];

$upcomingEvents = [
    ['title' => 'AIF Genel Kurul Toplantısı', 'date' => '2025-01-15', 'time' => '14:00', 'location' => 'Merkez Camii'],
    ['title' => 'Gençlik Teşkilatı Toplantısı', 'date' => '2025-01-18', 'time' => '19:00', 'location' => 'Gençlik Merkezi'],
    ['title' => 'Kadınlar Teşkilatı Semineri', 'date' => '2025-01-22', 'time' => '10:00', 'location' => 'Kültür Merkezi']
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AIF Otomasyon - Üye Dashboard</title>
    
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
        
        /* Üye Dashboard specific styles */
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
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .header-subtitle {
            color: #6c757d;
            font-size: 1rem;
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
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

        .stat-card.info::before {
            background: var(--info-color);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            background: var(--primary-color);
        }

        .stat-icon.success {
            background: var(--success-color);
        }

        .stat-icon.warning {
            background: var(--warning-color);
        }

        .stat-icon.info {
            background: var(--info-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 5px 0 0 0;
        }

        /* Content Cards */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 20px;
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
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
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
            font-size: 0.9rem;
        }

        .activity-time {
            color: #6c757d;
            font-size: 0.8rem;
            margin: 0;
        }

        /* Event Cards */
        .event-card {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }

        .event-card:hover {
            background: #e9ecef;
            transform: translateX(3px);
        }

        .event-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .event-details {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .event-details i {
            margin-right: 5px;
            width: 12px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            color: white;
            font-size: 1.1rem;
        }

        .action-title {
            font-weight: 600;
            margin: 0;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .header-title h1 {
                font-size: 1.8rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .content-grid {
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
                    <h1>Hoş Geldiniz!</h1>
                    <div class="header-subtitle">AIF Otomasyon Sistemi - Üye Paneli</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <h6><?php echo htmlspecialchars($currentUser['full_name']); ?></h6>
                        <small>Üye Kullanıcı</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card success slide-in-left" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-header">
                    <div class="stat-icon success">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $userStats['total_events']; ?></h3>
                <p class="stat-label">Toplam Etkinlik</p>
            </div>

            <div class="stat-card info slide-in-left" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-header">
                    <div class="stat-icon info">
                        <i class="fas fa-bookmark"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $userStats['my_reservations']; ?></h3>
                <p class="stat-label">Rezervasyonlarım</p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Recent Activities -->
            <div class="content-card fade-in" data-aos="fade-right">
                <h5 class="card-title">
                    <i class="fas fa-history"></i>
                    Son Aktiviteleriniz
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

            <!-- Upcoming Events -->
            <div class="content-card fade-in" data-aos="fade-left">
                <h5 class="card-title">
                    <i class="fas fa-calendar-check"></i>
                    Yaklaşan Etkinlikler
                </h5>
                <div class="events-list">
                    <?php foreach ($upcomingEvents as $event): ?>
                    <div class="event-card">
                        <div class="event-title"><?php echo $event['title']; ?></div>
                        <div class="event-details">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d.m.Y', strtotime($event['date'])); ?> - <?php echo $event['time']; ?>
                        </div>
                        <div class="event-details">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo $event['location']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="announcements.php" class="action-btn" data-aos="zoom-in" data-aos-delay="200">
                <div class="action-icon">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <h6 class="action-title">Duyurular</h6>
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

        // Add smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add loading animation
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });

        // Logout function
        function logout() {
            if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                window.location.href = '../logout.php';
            }
        }
    </script>
</body>
</html>
