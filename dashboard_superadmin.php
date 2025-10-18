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
        :root {
            --primary-color: #009872;
            --primary-dark: #007a5e;
            --primary-light: #00b085;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-weight: 600;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-left-color: white;
        }
        
        .sidebar-menu .nav-link i {
            width: 20px;
            margin-right: 10px;
            color: white;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .notification-badge {
            position: relative;
        }
        
        .notification-badge .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            font-size: 0.7rem;
        }
        
        /* Content Area */
        .content-area {
            padding: 2rem;
        }
        
        /* Statistics Cards */
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card .card-body {
            padding: 0;
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .stats-icon.users { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stats-icon.announcements { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stats-icon.events { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stats-icon.refunds { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Tables */
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 2rem;
        }
        
        .table-card .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
            border: none;
        }
        
        .table-card .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .table-card .card-body {
            padding: 0;
        }
        
        .table {
            margin: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            color: var(--primary-color);
            padding: 1rem;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e9ecef;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }
        
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-approved { background-color: #28a745; }
        .badge-rejected { background-color: #dc3545; }
        
        /* Charts */
        .chart-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 2rem;
        }
        
        .chart-card .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
            border: none;
        }
        
        .chart-card .card-body {
            padding: 1.5rem;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .content-area {
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
            }
            
            .header-title h1 {
                font-size: 1.5rem;
            }
        }
        
        /* Loading Animation */
        .loading {
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
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h4>AIF Otomasyon</h4>
        </div>
        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#users">
                        <i class="fas fa-users"></i> Kullanıcılar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#announcements">
                        <i class="fas fa-bullhorn"></i> Duyurular
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#events">
                        <i class="fas fa-calendar-alt"></i> Etkinlikler
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#refunds">
                        <i class="fas fa-undo"></i> İade Talepleri
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#reports">
                        <i class="fas fa-chart-bar"></i> Raporlar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#settings">
                        <i class="fas fa-cog"></i> Ayarlar
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link" href="#logout">
                        <i class="fas fa-sign-out-alt"></i> Çıkış
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Superadmin Dashboard</h1>
                </div>
                <div class="header-actions">
                    <div class="notification-badge">
                        <button class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-bell"></i>
                        </button>
                        <span class="badge">5</span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> Superadmin
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#profile"><i class="fas fa-user"></i> Profil</a></li>
                            <li><a class="dropdown-item" href="#settings"><i class="fas fa-cog"></i> Ayarlar</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#logout"><i class="fas fa-sign-out-alt"></i> Çıkış</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-number" id="totalUsers">1,247</div>
                        <div class="stats-label">Toplam Kullanıcı</div>
                        <small class="text-success"><i class="fas fa-arrow-up"></i> +12% bu ay</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon announcements">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="stats-number" id="activeAnnouncements">23</div>
                        <div class="stats-label">Aktif Duyuru</div>
                        <small class="text-info"><i class="fas fa-eye"></i> 1,456 görüntüleme</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon events">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stats-number" id="upcomingEvents">8</div>
                        <div class="stats-label">Yaklaşan Etkinlik</div>
                        <small class="text-warning"><i class="fas fa-clock"></i> 3 gün içinde</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon refunds">
                            <i class="fas fa-undo"></i>
                        </div>
                        <div class="stats-number" id="pendingRefunds">15</div>
                        <div class="stats-label">Bekleyen İade</div>
                        <small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Acil</small>
                    </div>
                </div>
            </div>

            <!-- Tables Row -->
            <div class="row mb-4">
                <!-- Pending Approvals Table -->
                <div class="col-lg-6 mb-3">
                    <div class="table-card">
                        <div class="card-header">
                            <h5><i class="fas fa-clock"></i> Bekleyen Onay Talepleri</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>Talep Türü</th>
                                            <th>Tarih</th>
                                            <th>Durum</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pendingApprovalsTable">
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-2">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Ahmet Yılmaz</div>
                                                        <small class="text-muted">ahmet@example.com</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Hesap Aktivasyonu</td>
                                            <td>2024-01-15</td>
                                            <td><span class="badge badge-pending">Beklemede</span></td>
                                            <td>
                                                <button class="btn btn-success btn-sm me-1" onclick="approveRequest(1)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="rejectRequest(1)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center text-white me-2">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Fatma Demir</div>
                                                        <small class="text-muted">fatma@example.com</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>İade Talebi</td>
                                            <td>2024-01-14</td>
                                            <td><span class="badge badge-pending">Beklemede</span></td>
                                            <td>
                                                <button class="btn btn-success btn-sm me-1" onclick="approveRequest(2)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="rejectRequest(2)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-warning rounded-circle d-flex align-items-center justify-content-center text-white me-2">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Mehmet Kaya</div>
                                                        <small class="text-muted">mehmet@example.com</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Etkinlik Kaydı</td>
                                            <td>2024-01-13</td>
                                            <td><span class="badge badge-pending">Beklemede</span></td>
                                            <td>
                                                <button class="btn btn-success btn-sm me-1" onclick="approveRequest(3)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="rejectRequest(3)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities Table -->
                <div class="col-lg-6 mb-3">
                    <div class="table-card">
                        <div class="card-header">
                            <h5><i class="fas fa-history"></i> Son İşlemler</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>İşlem</th>
                                            <th>Tarih</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentActivitiesTable">
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center text-white me-2">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Ali Veli</div>
                                                        <small class="text-muted">ali@example.com</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Hesap Oluşturma</td>
                                            <td>2024-01-15 14:30</td>
                                            <td><span class="badge badge-approved">Başarılı</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-danger rounded-circle d-flex align-items-center justify-content-center text-white me-2">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Ayşe Özkan</div>
                                                        <small class="text-muted">ayse@example.com</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>İade Talebi</td>
                                            <td>2024-01-15 13:45</td>
                                            <td><span class="badge badge-approved">Onaylandı</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white me-2">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Can Yılmaz</div>
                                                        <small class="text-muted">can@example.com</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Etkinlik Kaydı</td>
                                            <td>2024-01-15 12:20</td>
                                            <td><span class="badge badge-rejected">Reddedildi</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <!-- Monthly User Growth Chart -->
                <div class="col-lg-6 mb-3">
                    <div class="chart-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar"></i> Aylık Kullanıcı Artışı</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="userGrowthChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Monthly Refund Requests Chart -->
                <div class="col-lg-6 mb-3">
                    <div class="chart-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> Aylık İade Talepleri</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="refundRequestsChart" height="300"></canvas>
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
            // Initialize charts
            initializeCharts();
            
            // Auto-refresh data every 30 seconds
            setInterval(function() {
                refreshDashboardData();
            }, 30000);
            
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
            
            // Close sidebar when clicking outside on mobile
            $(document).click(function(e) {
                if ($(window).width() <= 768) {
                    if (!$(e.target).closest('.sidebar, .navbar-toggler').length) {
                        $('.sidebar').removeClass('show');
                    }
                }
            });
        });

        // Initialize Charts
        function initializeCharts() {
            // User Growth Chart (Bar Chart)
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(userGrowthCtx, {
                type: 'bar',
                data: {
                    labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                    datasets: [{
                        label: 'Yeni Kullanıcılar',
                        data: [120, 150, 180, 200, 220, 250],
                        backgroundColor: 'rgba(0, 152, 114, 0.8)',
                        borderColor: 'rgba(0, 152, 114, 1)',
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

            // Refund Requests Chart (Line Chart)
            const refundRequestsCtx = document.getElementById('refundRequestsChart').getContext('2d');
            new Chart(refundRequestsCtx, {
                type: 'line',
                data: {
                    labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                    datasets: [{
                        label: 'İade Talepleri',
                        data: [25, 30, 22, 35, 28, 32],
                        borderColor: 'rgba(0, 152, 114, 1)',
                        backgroundColor: 'rgba(0, 152, 114, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(0, 152, 114, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
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
        }

        // Approve Request
        function approveRequest(requestId) {
            if (confirm('Bu talebi onaylamak istediğinizden emin misiniz?')) {
                // Show loading
                const button = event.target.closest('button');
                const originalContent = button.innerHTML;
                button.innerHTML = '<span class="loading"></span>';
                button.disabled = true;
                
                // Simulate API call
                setTimeout(function() {
                    // Update UI
                    const row = button.closest('tr');
                    const statusBadge = row.querySelector('.badge');
                    statusBadge.className = 'badge badge-approved';
                    statusBadge.textContent = 'Onaylandı';
                    
                    // Remove action buttons
                    const actionCell = row.querySelector('td:last-child');
                    actionCell.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Onaylandı</span>';
                    
                    // Show success message
                    showNotification('Talep başarıyla onaylandı!', 'success');
                    
                    // Update statistics
                    updateStatistics();
                }, 1500);
            }
        }

        // Reject Request
        function rejectRequest(requestId) {
            if (confirm('Bu talebi reddetmek istediğinizden emin misiniz?')) {
                // Show loading
                const button = event.target.closest('button');
                const originalContent = button.innerHTML;
                button.innerHTML = '<span class="loading"></span>';
                button.disabled = true;
                
                // Simulate API call
                setTimeout(function() {
                    // Update UI
                    const row = button.closest('tr');
                    const statusBadge = row.querySelector('.badge');
                    statusBadge.className = 'badge badge-rejected';
                    statusBadge.textContent = 'Reddedildi';
                    
                    // Remove action buttons
                    const actionCell = row.querySelector('td:last-child');
                    actionCell.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Reddedildi</span>';
                    
                    // Show success message
                    showNotification('Talep reddedildi!', 'warning');
                    
                    // Update statistics
                    updateStatistics();
                }, 1500);
            }
        }

        // Show Notification
        function showNotification(message, type = 'info') {
            const alertClass = type === 'success' ? 'alert-success' : 
                              type === 'warning' ? 'alert-warning' : 
                              type === 'error' ? 'alert-danger' : 'alert-info';
            
            const notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                      type === 'warning' ? 'exclamation-triangle' : 
                                      type === 'error' ? 'times-circle' : 'info-circle'}"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto remove after 5 seconds
            setTimeout(function() {
                notification.alert('close');
            }, 5000);
        }

        // Update Statistics
        function updateStatistics() {
            // Simulate real-time data updates
            const totalUsers = parseInt($('#totalUsers').text().replace(',', ''));
            const activeAnnouncements = parseInt($('#activeAnnouncements').text());
            const upcomingEvents = parseInt($('#upcomingEvents').text());
            const pendingRefunds = parseInt($('#pendingRefunds').text());
            
            // Add some random variation to simulate real data
            $('#totalUsers').text((totalUsers + Math.floor(Math.random() * 3)).toLocaleString());
            $('#activeAnnouncements').text(activeAnnouncements + Math.floor(Math.random() * 2));
            $('#upcomingEvents').text(upcomingEvents + Math.floor(Math.random() * 2));
            $('#pendingRefunds').text(Math.max(0, pendingRefunds - Math.floor(Math.random() * 2)));
        }

        // Refresh Dashboard Data
        function refreshDashboardData() {
            // This would typically make AJAX calls to fetch fresh data
            console.log('Dashboard data refreshed at:', new Date().toLocaleTimeString());
            
            // Update statistics with slight variations
            updateStatistics();
        }

        // Sidebar Navigation
        $('.sidebar-menu .nav-link').click(function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            $('.sidebar-menu .nav-link').removeClass('active');
            
            // Add active class to clicked link
            $(this).addClass('active');
            
            // Here you would typically load the corresponding content
            const target = $(this).attr('href').substring(1);
            console.log('Navigating to:', target);
        });
    </script>
</body>
</html>
