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
    <title>AIF Otomasyon - Üye Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
                    <h1>Hoş Geldiniz, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</h1>
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
            <!-- Statistics Cards -->
            <div class="row mb-4">
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
                        <h3>5</h3>
                        <p>Yaklaşan Etkinlik</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon reservations">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <h3>3</h3>
                        <p>Aktif Rezervasyon</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon users">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h3>2</h3>
                        <p>Bekleyen İade</p>
                    </div>
                </div>
            </div>

            <!-- Recent Announcements -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-bullhorn"></i> Son Duyurular</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-card">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Başlık</th>
                                            <th>Kategori</th>
                                            <th>Tarih</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Yeni Çalışma Saatleri</div>
                                                <small class="text-muted">Çalışma saatleri güncellendi</small>
                                            </td>
                                            <td><span class="badge bg-info">Genel</span></td>
                                            <td>20.01.2024</td>
                                            <td><span class="status-badge status-active">Aktif</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Toplantı Odası Rezervasyonu</div>
                                                <small class="text-muted">Yeni rezervasyon kuralları</small>
                                            </td>
                                            <td><span class="badge bg-warning">Önemli</span></td>
                                            <td>18.01.2024</td>
                                            <td><span class="status-badge status-active">Aktif</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Sistem Bakımı</div>
                                                <small class="text-muted">Hafta sonu bakım çalışması</small>
                                            </td>
                                            <td><span class="badge bg-danger">Acil</span></td>
                                            <td>15.01.2024</td>
                                            <td><span class="status-badge status-active">Aktif</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="col-lg-4">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-calendar-alt"></i> Yaklaşan Etkinlikler</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Haftalık Toplantı</h6>
                                    <small class="text-muted">25 Ocak 2024, 14:00</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Eğitim Semineri</h6>
                                    <small class="text-muted">28 Ocak 2024, 10:00</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-birthday-cake"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Doğum Günü Partisi</h6>
                                    <small class="text-muted">30 Ocak 2024, 16:00</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Aylık Rapor</h6>
                                    <small class="text-muted">1 Şubat 2024, 09:00</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Reservations and Refunds -->
            <div class="row">
                <!-- My Reservations -->
                <div class="col-lg-6">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-bookmark"></i> Rezervasyonlarım</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-card">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Rezervasyon</th>
                                            <th>Tarih</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Toplantı Odası A</div>
                                                <small class="text-muted">2 saat</small>
                                            </td>
                                            <td>22.01.2024</td>
                                            <td><span class="status-badge status-approved">Onaylandı</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Projeksiyon Cihazı</div>
                                                <small class="text-muted">1 gün</small>
                                            </td>
                                            <td>25.01.2024</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Konferans Salonu</div>
                                                <small class="text-muted">4 saat</small>
                                            </td>
                                            <td>28.01.2024</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- My Refund Requests -->
                <div class="col-lg-6">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-undo"></i> İade Taleplerim</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-card">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tutar</th>
                                            <th>Açıklama</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">₺150</div>
                                                <small class="text-muted">Ulaşım</small>
                                            </td>
                                            <td>Taksi ücreti</td>
                                            <td><span class="status-badge status-approved">Onaylandı</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">₺85</div>
                                                <small class="text-muted">Yemek</small>
                                            </td>
                                            <td>İş yemeği</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">₺200</div>
                                                <small class="text-muted">Konaklama</small>
                                            </td>
                                            <td>Otel ücreti</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                        </tr>
                                    </tbody>
                                </table>
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
                // Refresh statistics and tables
                console.log('Data refreshed');
            }, 300000);
        });
    </script>
</body>
</html>


// Login kontrolü kaldırıldı - direkt erişim
$currentUser = SessionManager::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Üye Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
                    <h1>Hoş Geldiniz, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</h1>
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
            <!-- Statistics Cards -->
            <div class="row mb-4">
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
                        <h3>5</h3>
                        <p>Yaklaşan Etkinlik</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon reservations">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <h3>3</h3>
                        <p>Aktif Rezervasyon</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon users">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h3>2</h3>
                        <p>Bekleyen İade</p>
                    </div>
                </div>
            </div>

            <!-- Recent Announcements -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-bullhorn"></i> Son Duyurular</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-card">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Başlık</th>
                                            <th>Kategori</th>
                                            <th>Tarih</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Yeni Çalışma Saatleri</div>
                                                <small class="text-muted">Çalışma saatleri güncellendi</small>
                                            </td>
                                            <td><span class="badge bg-info">Genel</span></td>
                                            <td>20.01.2024</td>
                                            <td><span class="status-badge status-active">Aktif</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Toplantı Odası Rezervasyonu</div>
                                                <small class="text-muted">Yeni rezervasyon kuralları</small>
                                            </td>
                                            <td><span class="badge bg-warning">Önemli</span></td>
                                            <td>18.01.2024</td>
                                            <td><span class="status-badge status-active">Aktif</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Sistem Bakımı</div>
                                                <small class="text-muted">Hafta sonu bakım çalışması</small>
                                            </td>
                                            <td><span class="badge bg-danger">Acil</span></td>
                                            <td>15.01.2024</td>
                                            <td><span class="status-badge status-active">Aktif</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="col-lg-4">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-calendar-alt"></i> Yaklaşan Etkinlikler</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Haftalık Toplantı</h6>
                                    <small class="text-muted">25 Ocak 2024, 14:00</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Eğitim Semineri</h6>
                                    <small class="text-muted">28 Ocak 2024, 10:00</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-birthday-cake"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Doğum Günü Partisi</h6>
                                    <small class="text-muted">30 Ocak 2024, 16:00</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Aylık Rapor</h6>
                                    <small class="text-muted">1 Şubat 2024, 09:00</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Reservations and Refunds -->
            <div class="row">
                <!-- My Reservations -->
                <div class="col-lg-6">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-bookmark"></i> Rezervasyonlarım</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-card">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Rezervasyon</th>
                                            <th>Tarih</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Toplantı Odası A</div>
                                                <small class="text-muted">2 saat</small>
                                            </td>
                                            <td>22.01.2024</td>
                                            <td><span class="status-badge status-approved">Onaylandı</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Projeksiyon Cihazı</div>
                                                <small class="text-muted">1 gün</small>
                                            </td>
                                            <td>25.01.2024</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Konferans Salonu</div>
                                                <small class="text-muted">4 saat</small>
                                            </td>
                                            <td>28.01.2024</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- My Refund Requests -->
                <div class="col-lg-6">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-undo"></i> İade Taleplerim</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-card">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tutar</th>
                                            <th>Açıklama</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">₺150</div>
                                                <small class="text-muted">Ulaşım</small>
                                            </td>
                                            <td>Taksi ücreti</td>
                                            <td><span class="status-badge status-approved">Onaylandı</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">₺85</div>
                                                <small class="text-muted">Yemek</small>
                                            </td>
                                            <td>İş yemeği</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">₺200</div>
                                                <small class="text-muted">Konaklama</small>
                                            </td>
                                            <td>Otel ücreti</td>
                                            <td><span class="status-badge status-pending">Beklemede</span></td>
                                        </tr>
                                    </tbody>
                                </table>
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
                // Refresh statistics and tables
                console.log('Data refreshed');
            }, 300000);
        });
    </script>
</body>
</html>

