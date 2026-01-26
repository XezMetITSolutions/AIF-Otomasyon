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
$isUye = $user && $user['role'] === 'uye';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>
        <?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo Config::get('app_name', 'AİFNET'); ?>
    </title>

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

    <!-- Dynamic Theme Color -->
    <style>
        :root {
            --primary-color:
                <?php echo Config::get('theme_color', '#009872'); ?>
            ;
            --secondary-color:
                <?php echo Config::get('theme_color', '#009872'); ?>
            ;
            --accent-color:
                <?php echo Config::get('theme_color', '#009872'); ?>
            ;
            --primary-gradient: linear-gradient(135deg,
                    <?php echo Config::get('theme_color', '#009872'); ?>
                    0%,
                    <?php
                    // Tema renginden biraz daha koyu bir renk üretelim (basitçe)
                    echo Config::get('theme_color', '#009872');
                    ?>
                    100%);
        }
    </style>

    <?php if (isset($pageSpecificCSS)): ?>
        <?php foreach ($pageSpecificCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body>
    <?php if ($user): ?>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary"
            style="position: fixed; top: 0; left: 0; right: 0; z-index: 1030; width: 100%;">
            <div class="container-fluid">
                <?php
                $homeLink = '/index.php';
                if ($user['role'] === 'super_admin') {
                    $homeLink = '/admin/dashboard.php';
                } else {
                    $homeLink = '/panel/dashboard.php';
                }
                ?>
                <a class="navbar-brand d-flex align-items-center" href="<?php echo $homeLink; ?>">
                    <img src="/assets/img/AIF.png" alt="AIF Logo" style="height: 40px; width: auto;">
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <?php
                        // Mobil Menü için Sidebar Mantığı (Desktop'ta gizli: d-lg-none)
                    
                        // DB ve Yetki Kontrolleri
                        $dbHeader = Database::getInstance();
                        $isMuhasebeBaskaniHeader = false;
                        try {
                            if ($user) {
                                $checkMuhasebe = $dbHeader->fetch("SELECT count(*) as cnt FROM byk WHERE muhasebe_baskani_id = ?", [$user['id']]);
                                if ($checkMuhasebe && $checkMuhasebe['cnt'] > 0) {
                                    $isMuhasebeBaskaniHeader = true;
                                }
                            }
                        } catch (Exception $e) {
                        }

                        // Common Links
                        $commonLinks = [
                            ['path' => '/panel/dashboard.php', 'icon' => 'fas fa-gauge', 'label' => 'Kontrol Paneli', 'match' => 'panel/dashboard'],
                            ['path' => '/panel/duyurular.php', 'icon' => 'fas fa-bullhorn', 'label' => 'Duyurular', 'match' => 'panel/duyurular'],
                            ['path' => '/panel/etkinlikler.php', 'icon' => 'fas fa-calendar', 'label' => 'Çalışma Takvimi', 'match' => 'panel/etkinlikler'],
                            ['path' => '/panel/toplantilar.php', 'icon' => 'fas fa-users-cog', 'label' => 'Toplantılar', 'match' => 'panel/toplantilar'],
                            ['path' => '/panel/uyeler.php', 'icon' => 'fas fa-users', 'label' => 'Üyeler', 'match' => 'panel/uyeler'],
                        ];

                        // Management Sections
                        $baskanSidebarSections = [
                            [
                                'title' => 'İŞLEMLER (ONAY)',
                                'links' => [
                                    ['key' => 'baskan_izin_talepleri', 'path' => '/panel/izin-talepleri.php?tab=onay', 'icon' => 'fas fa-calendar-check', 'label' => 'İzin Onayları', 'match' => 'panel/izin-talepleri'],
                                    ['key' => 'baskan_harcama_talepleri', 'path' => '/panel/harcama-talepleri.php?tab=onay', 'icon' => 'fas fa-money-bill-wave', 'label' => 'Harcama Onayları', 'match' => 'panel/harcama-talepleri'],
                                    ['key' => 'baskan_iade_formlari', 'path' => '/panel/iade-formlari.php?tab=yonetim', 'icon' => 'fas fa-hand-holding-usd', 'label' => 'İade Onayları', 'match' => 'panel/iade-formlari'],
                                    ['key' => 'baskan_demirbas_talepleri', 'path' => '/panel/demirbas-talepleri.php?tab=onay', 'icon' => 'fas fa-box', 'label' => 'Demirbaş Talepleri', 'match' => 'panel/demirbas-talepleri'],
                                    ['key' => 'baskan_raggal_talepleri', 'path' => '/panel/raggal-talepleri.php?tab=yonetim', 'icon' => 'fas fa-calendar-check', 'label' => 'Raggal Talepleri', 'match' => 'panel/raggal-talepleri'],
                                ],
                            ],
                            [
                                'title' => 'RAPORLAR',
                                'links' => [
                                    ['key' => 'baskan_raporlar', 'path' => '/panel/baskan_raporlar.php', 'icon' => 'fas fa-chart-bar', 'label' => 'Raporlar', 'match' => 'raporlar'],
                                ],
                            ],
                        ];

                        // Uye Links
                        $uyeSidebarLinks = [
                            ['key' => 'uye_izin_talepleri', 'path' => '/panel/izin-talepleri.php?tab=talebim', 'icon' => 'fas fa-person-walking', 'label' => 'İzin Taleplerim', 'match' => 'panel/izin-talepleri'],
                            ['key' => 'uye_harcama_talepleri', 'path' => '/panel/harcama-talepleri.php?tab=talebim', 'icon' => 'fas fa-wallet', 'label' => 'Harcama Taleplerim', 'match' => 'panel/harcama-talepleri'],
                            ['key' => 'uye_iade_formu', 'path' => '/panel/iade-formlari.php?tab=form', 'icon' => 'fas fa-file-invoice-dollar', 'label' => 'İade Talebi Oluştur', 'match' => 'panel/iade-formlari'],
                            ['key' => 'uye_demirbas_talep', 'path' => '/panel/demirbas-talepleri.php?tab=talep', 'icon' => 'fas fa-box', 'label' => 'Demirbaş Talep', 'match' => 'panel/demirbas-talepleri'],
                            ['key' => 'uye_raggal_talep', 'path' => '/panel/raggal-talepleri.php?tab=takvim', 'icon' => 'fas fa-calendar-plus', 'label' => 'Raggal Rezervasyon', 'match' => 'panel/raggal-talepleri'],
                        ];

                        $currentPath = $_SERVER['PHP_SELF'];
                        ?>

                        <!-- List Wrapper with d-lg-none to hide on desktop -->
                        <ul class="navbar-nav me-auto d-lg-none">
                            <?php if ($isSuperAdmin): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/admin/dashboard.php"><i
                                            class="fas fa-tachometer-alt me-1"></i>Kontrol Paneli</a>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i
                                            class="fas fa-users me-1"></i>Yönetim</a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="/admin/kullanicilar.php"><i
                                                    class="fas fa-user me-2"></i>Kullanıcılar</a></li>
                                        <li><a class="dropdown-item" href="/admin/byk.php"><i
                                                    class="fas fa-building me-2"></i>BYK Yönetimi</a></li>
                                        <li><a class="dropdown-item" href="/admin/alt-birimler.php"><i
                                                    class="fas fa-sitemap me-2"></i>Alt Birimler</a></li>
                                        <li><a class="dropdown-item" href="/admin/baskan-yetkileri.php"><i
                                                    class="fas fa-sliders me-2"></i>Üye Yetkileri</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i
                                            class="fas fa-calendar me-1"></i>İçerik</a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="/admin/etkinlikler.php"><i
                                                    class="fas fa-calendar-alt me-2"></i>Çalışma Takvimi</a></li>
                                        <li><a class="dropdown-item" href="/admin/toplantilar.php"><i
                                                    class="fas fa-users-cog me-2"></i>Toplantı Yönetimi</a></li>
                                        <li><a class="dropdown-item" href="/admin/projeler.php"><i
                                                    class="fas fa-project-diagram me-2"></i>Proje Takibi</a></li>
                                        <li><a class="dropdown-item" href="/admin/duyurular.php"><i
                                                    class="fas fa-bullhorn me-2"></i>Duyurular</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i
                                            class="fas fa-briefcase me-1"></i>İşlemler</a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="/admin/izin-talepleri.php"><i
                                                    class="fas fa-calendar-check me-2"></i>İzin Talepleri</a></li>
                                        <li><a class="dropdown-item" href="/admin/harcama-talepleri.php"><i
                                                    class="fas fa-money-bill-wave me-2"></i>Harcama Talepleri</a></li>
                                        <li><a class="dropdown-item" href="/admin/demirbaslar.php"><i
                                                    class="fas fa-box me-2"></i>Demirbaş Yönetimi</a></li>
                                        <li><a class="dropdown-item" href="/admin/demirbas-talepleri.php"><i
                                                    class="fas fa-box-open me-2"></i>Demirbaş Talepleri</a></li>
                                        <li><a class="dropdown-item" href="/admin/raggal-talepleri.php"><i
                                                    class="fas fa-calendar-check me-2"></i>Raggal Talepleri</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/admin/raporlar.php"><i
                                            class="fas fa-chart-bar me-1"></i>Raporlar</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/admin/ayarlar.php"><i class="fas fa-cog me-1"></i>Ayarlar</a>
                                </li>

                            <?php else: // Üye (Yetkili/Normal) ?>

                                <!-- Common Links -->
                                <?php foreach ($commonLinks as $link): ?>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo $link['path']; ?>">
                                            <i
                                                class="<?php echo $link['icon']; ?> me-1"></i><?php echo htmlspecialchars($link['label']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>

                                <!-- Baskan (Management) -->
                                <?php
                                foreach ($baskanSidebarSections as $section) {
                                    $visibleManageLinks = array_filter($section['links'], function ($link) use ($auth, $isMuhasebeBaskaniHeader) {
                                        if ($isMuhasebeBaskaniHeader && in_array($link['key'], ['baskan_harcama_talepleri', 'baskan_iade_formlari'])) {
                                            return true;
                                        }
                                        return $auth->hasModulePermission($link['key']);
                                    });

                                    if (!empty($visibleManageLinks)) {
                                        echo '<li class="nav-item"><hr class="dropdown-divider text-light border-secondary"></li>';
                                        echo '<li class="nav-item"><span class="nav-link text-uppercase small disabled text-white-50 ms-2">' . $section['title'] . '</span></li>';
                                        foreach ($visibleManageLinks as $link) {
                                            ?>
                                            <li class="nav-item">
                                                <a class="nav-link" href="<?php echo $link['path']; ?>">
                                                    <i
                                                        class="<?php echo $link['icon']; ?> me-1"></i><?php echo htmlspecialchars($link['label']); ?>
                                                </a>
                                            </li>
                                            <?php
                                        }
                                    }
                                }
                                ?>

                                <!-- Uye (Personal) -->
                                <?php
                                $hasAnyPersonal = false;
                                $firstPersonal = true;
                                foreach ($uyeSidebarLinks as $link):
                                    if ($auth->hasModulePermission($link['key'])):
                                        if ($firstPersonal) {
                                            echo '<li class="nav-item"><hr class="dropdown-divider text-light border-secondary"></li>';
                                            echo '<li class="nav-item"><span class="nav-link text-uppercase small disabled text-white-50 ms-2">KİŞİSEL</span></li>';
                                            $firstPersonal = false;
                                        }
                                        ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="<?php echo $link['path']; ?>">
                                                <i
                                                    class="<?php echo $link['icon']; ?> me-1"></i><?php echo htmlspecialchars($link['label']); ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                            <?php endif; ?>
                        </ul>

                    </ul>

                    <!-- Bildirimler ve Kullanıcı Menüsü -->
                    <ul class="navbar-nav">
                        <?php if ($user): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-bell"></i>
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                        id="notificationCount">0</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" id="notificationsList"
                                    style="width: 350px; max-height: 400px; overflow-y: auto;">
                                    <li>
                                        <h6 class="dropdown-header">Bildirimler</h6>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li class="text-center p-3"><small class="text-muted">Bildirim bulunmamaktadır</small></li>
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php
                                    $profileLink = '/panel/profil.php';
                                    if ($user['role'] === 'super_admin') {
                                        $profileLink = '/admin/profil.php';
                                    }
                                    ?>
                                    <li><a class="dropdown-item" href="<?php echo $profileLink; ?>"><i
                                                class="fas fa-user me-2"></i>Profil</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="/logout.php"><i
                                                class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>