<?php
/**
 * Ortak Header Bileşeni
 * Tüm sayfalarda kullanılır
 */
$appConfig = require __DIR__ . '/../config/app.php';
$auth = new Auth();
$user = $auth->getUser();
$enableCharts = $enableCharts ?? false;
$enableAnimations = $enableAnimations ?? false;

// Rol bazlı menü görünürlüğü
$isSuperAdmin = $user && $user['role'] === 'super_admin';
$isBaskan = $user && $user['role'] === 'uye';
$isUye = $user && $user['role'] === 'uye';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo $appConfig['app_name']; ?></title>
    
    <!-- Bootstrap 5.3.0 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.4.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if ($enableAnimations): ?>
        <!-- AOS (Animate On Scroll) -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <?php endif; ?>
    
    <?php if ($enableCharts): ?>
        <!-- Chart.js (yalnizca gerekli sayfalarda yuklenir) -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <?php if (isset($pageSpecificCSS)): ?>
        <?php foreach ($pageSpecificCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php if ($user): ?>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1030; width: 100%;">
            <div class="container-fluid">
                <?php
                $homeLink = '/index.php';
                if ($user['role'] === 'super_admin') {
                    $homeLink = '/admin/dashboard.php';
                } elseif ($user['role'] === 'uye') {
                    $homeLink = '/panel/baskan_dashboard.php';
                } elseif ($user['role'] === 'uye') {
                    $homeLink = '/panel/uye_dashboard.php';
                }
                ?>
                <a class="navbar-brand" href="<?php echo $homeLink; ?>">
                    <i class="fas fa-home me-2"></i><?php echo $appConfig['app_name']; ?>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <?php if ($isSuperAdmin): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Kontrol Paneli</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-users me-1"></i>Yönetim
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="/admin/kullanicilar.php"><i class="fas fa-user me-2"></i>Kullanıcılar</a></li>
                                    <li><a class="dropdown-item" href="/admin/byk.php"><i class="fas fa-building me-2"></i>BYK Yönetimi</a></li>
                                    <li><a class="dropdown-item" href="/admin/alt-birimler.php"><i class="fas fa-sitemap me-2"></i>Alt Birimler</a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/etkinlikler.php"><i class="fas fa-calendar me-1"></i>Etkinlikler</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/toplantilar.php"><i class="fas fa-users-cog me-1"></i>Toplantılar</a>
                            </li>
                        <?php elseif ($isBaskan): ?>
                            <?php
                                $baskanHeaderNav = [
                                    'baskan_dashboard' => ['/panel/baskan_dashboard.php', 'fas fa-tachometer-alt', 'Kontrol Paneli'],
                                    'baskan_uyeler' => ['/panel/baskan_uyeler.php', 'fas fa-users', 'Üyeler'],
                                    'baskan_etkinlikler' => ['/panel/baskan_etkinlikler.php', 'fas fa-calendar', 'Etkinlikler'],
                                    'baskan_toplantilar' => ['/panel/baskan_toplantilar.php', 'fas fa-users-cog', 'Toplantılar'],
                                    'baskan_izin_talepleri' => ['/panel/baskan_izin-talepleri.php', 'fas fa-calendar-check', 'İzin Talepleri'],
                                    'baskan_harcama_talepleri' => ['/panel/baskan_harcama-talepleri.php', 'fas fa-money-bill', 'Harcama Talepleri'],
                                    'baskan_iade_formlari' => ['/panel/baskan_iade-formlari.php', 'fas fa-hand-holding-usd', 'İade Formları'],
                                ];
                                $uyeHeaderNav = [
                                    'uye_dashboard' => ['/panel/uye_dashboard.php', 'fas fa-gauge', 'Üye Paneli'],
                                    'uye_izin_talepleri' => ['/panel/uye_izin-talepleri.php', 'fas fa-person-walking', 'Üye İzin Talepleri'],
                                    'uye_harcama_talepleri' => ['/panel/uye_harcama-talepleri.php', 'fas fa-wallet', 'Üye Harcama Talepleri'],
                                    'uye_iade_formu' => ['/panel/uye_iade-formu.php', 'fas fa-file-invoice-dollar', 'Üye İade Formu'],
                                ];
                                $hasUyeHeaderNav = false;
                                foreach ($uyeHeaderNav as $key => $data) {
                                    if ($auth->hasModulePermission($key)) {
                                        $hasUyeHeaderNav = true;
                                        break;
                                    }
                                }
                            ?>
                            <?php foreach ($baskanHeaderNav as $moduleKey => $navData): ?>
                                <?php if ($auth->hasModulePermission($moduleKey)): ?>
                            <li class="nav-item">
                                        <a class="nav-link" href="<?php echo $navData[0]; ?>"><i class="<?php echo $navData[1]; ?> me-1"></i><?php echo $navData[2]; ?></a>
                            </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($hasUyeHeaderNav): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-tag me-1"></i>Üye Modülleri
                                    </a>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($uyeHeaderNav as $moduleKey => $navData): ?>
                                            <?php if ($auth->hasModulePermission($moduleKey)): ?>
                                                <li><a class="dropdown-item" href="<?php echo $navData[0]; ?>"><i class="<?php echo $navData[1]; ?> me-2"></i><?php echo $navData[2]; ?></a></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                            </li>
                            <?php endif; ?>
                        <?php elseif ($isUye): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/panel/uye_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Kontrol Paneli</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/panel/uye_duyurular.php"><i class="fas fa-bullhorn me-1"></i>Duyurular</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/panel/uye_etkinlikler.php"><i class="fas fa-calendar me-1"></i>Etkinlikler</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/panel/uye_toplantilar.php"><i class="fas fa-users-cog me-1"></i>Toplantılar</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/panel/uye_izin-talepleri.php"><i class="fas fa-calendar-check me-1"></i>İzin Taleplerim</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/panel/uye_harcama-talepleri.php"><i class="fas fa-wallet me-1"></i>Harcama Taleplerim</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/panel/uye_iade-formu.php"><i class="fas fa-hand-holding-usd me-1"></i>İade Talebi</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- Bildirimler ve Kullanıcı Menüsü -->
                    <ul class="navbar-nav">
                        <?php if ($user): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-bell"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationCount">0</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" id="notificationsList" style="width: 350px; max-height: 400px; overflow-y: auto;">
                                    <li><h6 class="dropdown-header">Bildirimler</h6></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="text-center p-3"><small class="text-muted">Bildirim bulunmamaktadır</small></li>
                                </ul>
                            </li>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php
                                    $profileLink = '/index.php';
                                    if ($user['role'] === 'super_admin') {
                                        $profileLink = '/admin/profil.php'; // Assuming admin has one
                                    } elseif ($user['role'] === 'uye') {
                                        $profileLink = '/panel/baskan_profil.php';
                                    } elseif ($user['role'] === 'uye') {
                                        $profileLink = '/panel/uye_profil.php';
                                    }
                                    ?>
                                    <li><a class="dropdown-item" href="<?php echo $profileLink; ?>"><i class="fas fa-user me-2"></i>Profil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>

