<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Raporlar</title>
    
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
                    <h1>Raporlar ve Analizler</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="downloadReport()">
                        <i class="fas fa-download"></i> Rapor İndir
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Report Filters -->
            <div class="page-card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-filter"></i> Rapor Filtreleri</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tarih Aralığı</label>
                            <select class="form-select">
                                <option>Son 7 Gün</option>
                                <option>Son 30 Gün</option>
                                <option>Son 3 Ay</option>
                                <option>Son 1 Yıl</option>
                                <option>Özel Tarih</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Rapor Türü</label>
                            <select class="form-select">
                                <option>Tüm Raporlar</option>
                                <option>Kullanıcı Raporları</option>
                                <option>Finansal Raporlar</option>
                                <option>Etkinlik Raporları</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Format</label>
                            <select class="form-select">
                                <option>PDF</option>
                                <option>Excel</option>
                                <option>CSV</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Rapor Oluştur
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <!-- User Growth Chart -->
                <div class="col-lg-6 mb-3">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> Kullanıcı Büyüme Analizi</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="userGrowthChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="col-lg-6 mb-3">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar"></i> Gelir Analizi</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Charts -->
            <div class="row mb-4">
                <!-- Event Participation -->
                <div class="col-lg-4 mb-3">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-pie"></i> Etkinlik Katılımı</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="eventParticipationChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Refund Analysis -->
                <div class="col-lg-4 mb-3">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-area"></i> İade Analizi</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="refundAnalysisChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <!-- System Performance -->
                <div class="col-lg-4 mb-3">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-gauge"></i> Sistem Performansı</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="systemPerformanceChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary">₺45,230</h3>
                            <p class="text-muted mb-0">Toplam Gelir</p>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> +15%</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success">1,247</h3>
                            <p class="text-muted mb-0">Aktif Kullanıcı</p>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> +8%</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-info">156</h3>
                            <p class="text-muted mb-0">Etkinlik Katılımcısı</p>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> +12%</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">₺2,340</h3>
                            <p class="text-muted mb-0">İade Tutarı</p>
                            <small class="text-danger"><i class="fas fa-arrow-down"></i> -5%</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function downloadReport() {
            showAlert('Rapor indirme özelliği aktif!', 'success');
            console.log('Downloading report...');
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
        }
        
        $(document).ready(function() {
            // Initialize charts
            initializeCharts();
            
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
        });

        // Initialize Charts
        function initializeCharts() {
            // User Growth Chart
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                    datasets: [{
                        label: 'Yeni Kullanıcılar',
                        data: [120, 150, 180, 200, 220, 250],
                        borderColor: 'rgba(0, 152, 114, 1)',
                        backgroundColor: 'rgba(0, 152, 114, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                    datasets: [{
                        label: 'Gelir (₺)',
                        data: [7500, 8200, 9100, 9800, 10500, 11200],
                        backgroundColor: 'rgba(0, 152, 114, 0.8)',
                        borderColor: 'rgba(0, 152, 114, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Event Participation Chart
            const eventParticipationCtx = document.getElementById('eventParticipationChart').getContext('2d');
            new Chart(eventParticipationCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Teknoloji', 'Eğitim', 'Networking', 'Diğer'],
                    datasets: [{
                        data: [45, 30, 20, 5],
                        backgroundColor: [
                            'rgba(0, 152, 114, 0.8)',
                            'rgba(0, 152, 114, 0.6)',
                            'rgba(0, 152, 114, 0.4)',
                            'rgba(0, 152, 114, 0.2)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Refund Analysis Chart
            const refundAnalysisCtx = document.getElementById('refundAnalysisChart').getContext('2d');
            new Chart(refundAnalysisCtx, {
                type: 'line',
                data: {
                    labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                    datasets: [{
                        label: 'İade Talepleri',
                        data: [25, 30, 22, 35, 28, 32],
                        borderColor: 'rgba(220, 53, 69, 1)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // System Performance Chart
            const systemPerformanceCtx = document.getElementById('systemPerformanceChart').getContext('2d');
            new Chart(systemPerformanceCtx, {
                type: 'radar',
                data: {
                    labels: ['Hız', 'Güvenlik', 'Kullanılabilirlik', 'Performans', 'Güvenilirlik'],
                    datasets: [{
                        label: 'Sistem Skoru',
                        data: [85, 92, 78, 88, 95],
                        borderColor: 'rgba(0, 152, 114, 1)',
                        backgroundColor: 'rgba(0, 152, 114, 0.2)',
                        borderWidth: 2
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
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
