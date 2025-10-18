<?php
require_once 'auth.php';
require_once 'includes/byk_manager.php';
require_once 'includes/permission_manager.php';

// Session kontrolü - giriş yapmış kullanıcı
SessionManager::requireLogin();

$currentUser = SessionManager::getCurrentUser();
$username = $currentUser['username'] ?? '';

// Dashboard yetkisi kontrolü
if (!PermissionManager::hasPermission($username, 'dashboard')) {
    header('Location: ../index.php?error=no_permission');
    exit();
}

$userManager = new UserManager();
$users = $userManager->getAllUsers();
$bykStats = BYKManager::getBYKStats();
$userPermissions = PermissionManager::getUserPermissions($username);
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
            <!-- Kullanıcı Yetkileri -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-shield-alt"></i> Yetkileriniz</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($userPermissions as $module => $level): ?>
                                <?php if ($level !== 'none'): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="<?php echo PermissionManager::getModules()[$module]['icon']; ?>"></i>
                                                        <?php echo PermissionManager::getModules()[$module]['name']; ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo PermissionManager::getModules()[$module]['description']; ?></small>
                                                </div>
                                                <div>
                                                    <?php echo PermissionManager::generatePermissionBadge($level); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BYK Bilgileri -->
            <?php if (isset($currentUser['byk'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-card byk-<?php echo strtolower($currentUser['byk']); ?>-border">
                        <div class="card-header">
                            <h5><i class="fas fa-building"></i> BYK Bilgileriniz</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>BYK:</strong> <span class="badge byk-<?php echo strtolower($currentUser['byk']); ?>"><?php echo $currentUser['byk']; ?></span></p>
                                    <p><strong>BYK Adı:</strong> <?php echo BYKManager::getBYKName($currentUser['byk']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Alt Birim:</strong> <?php echo htmlspecialchars($currentUser['sub_unit'] ?? 'Belirtilmemiş'); ?></p>
                                    <p><strong>Rol:</strong> <span class="badge bg-primary"><?php echo ucfirst($currentUser['role']); ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
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
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <h3>0</h3>
                        <p>Aktif Rezervasyon</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="icon projects">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <h3>0</h3>
                        <p>Aktif Proje</p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-history"></i> Son İşlemleriniz</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Henüz işlem bulunmuyor</h5>
                                <p class="text-muted">Yaptığınız işlemler burada görünecek</p>
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