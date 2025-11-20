<?php
/**
 * Sidebar Bileşeni (Admin ve Başkan panelleri için)
 */
$auth = new Auth();
$user = $auth->getUser();
if (!$user) return;

$isSuperAdmin = $user['role'] === 'super_admin';
$isBaskan = $user['role'] === 'baskan';
$isUye = $user['role'] === 'uye';
$currentPath = $_SERVER['PHP_SELF'];
?>

<div class="sidebar bg-light border-end">
    <?php
    // Define Baskan Sidebar Sections globally so they can be used by both Baskan and Uye (if granted)
    $baskanSidebarSections = [
        [
            'title' => null,
            'links' => [
                [
                    'key' => 'baskan_dashboard',
                    'path' => '/baskan/dashboard.php',
                    'icon' => 'fas fa-tachometer-alt',
                    'label' => 'Yönetici Paneli', // Changed label to distinguish from Member Dashboard
                    'match' => 'baskan/dashboard', // More specific match
                ],
            ],
        ],
        [
            'title' => 'YÖNETİM',
            'links' => [
                [
                    'key' => 'baskan_uyeler',
                    'path' => '/baskan/uyeler.php',
                    'icon' => 'fas fa-users',
                    'label' => 'Üye Yönetimi',
                    'match' => 'uyeler',
                ],
            ],
        ],
        [
            'title' => 'İÇERİK',
            'links' => [
                [
                    'key' => 'baskan_etkinlikler',
                    'path' => '/baskan/etkinlikler.php',
                    'icon' => 'fas fa-calendar',
                    'label' => 'Etkinlik Yönetimi',
                    'match' => 'baskan/etkinlikler',
                ],
                [
                    'key' => 'baskan_toplantilar',
                    'path' => '/baskan/toplantilar.php',
                    'icon' => 'fas fa-users-cog',
                    'label' => 'Toplantı Yönetimi',
                    'match' => 'baskan/toplantilar',
                ],
                [
                    'key' => 'baskan_duyurular',
                    'path' => '/baskan/duyurular.php',
                    'icon' => 'fas fa-bullhorn',
                    'label' => 'Duyuru Yönetimi',
                    'match' => 'baskan/duyurular',
                ],
            ],
        ],
        [
            'title' => 'İŞLEMLER',
            'links' => [
                [
                    'key' => 'baskan_izin_talepleri',
                    'path' => '/baskan/izin-talepleri.php',
                    'icon' => 'fas fa-calendar-check',
                    'label' => 'İzin Onayları',
                    'match' => 'baskan/izin-talepleri',
                    'badge' => ['id' => 'pendingIzinCount', 'class' => 'bg-danger'],
                ],
                [
                    'key' => 'baskan_harcama_talepleri',
                    'path' => '/baskan/harcama-talepleri.php',
                    'icon' => 'fas fa-money-bill-wave',
                    'label' => 'Harcama Onayları',
                    'match' => 'baskan/harcama-talepleri',
                    'badge' => ['id' => 'pendingHarcamaCount', 'class' => 'bg-warning'],
                ],
                [
                    'key' => 'baskan_iade_formlari',
                    'path' => '/baskan/iade-formlari.php',
                    'icon' => 'fas fa-hand-holding-usd',
                    'label' => 'İade Onayları',
                    'match' => 'baskan/iade-formlari',
                ],
            ],
        ],
        [
            'title' => 'RAPORLAR',
            'links' => [
                [
                    'key' => 'baskan_raporlar',
                    'path' => '/baskan/raporlar.php',
                    'icon' => 'fas fa-chart-bar',
                    'label' => 'Raporlar',
                    'match' => 'raporlar',
                ],
            ],
        ],
    ];

    $uyeSidebarLinks = [
        [
            'key' => 'uye_dashboard',
            'path' => '/uye/dashboard.php',
            'icon' => 'fas fa-gauge',
            'label' => 'Üye Kontrol Paneli',
            'match' => 'uye/dashboard',
        ],
        [
            'key' => 'uye_duyurular',
            'path' => '/uye/duyurular.php',
            'icon' => 'fas fa-bullhorn',
            'label' => 'Üye Duyuruları',
            'match' => 'uye/duyurular',
        ],
        [
            'key' => 'uye_etkinlikler',
            'path' => '/uye/etkinlikler.php',
            'icon' => 'fas fa-calendar',
            'label' => 'Üye Etkinlikleri',
            'match' => 'uye/etkinlikler',
        ],
        [
            'key' => 'uye_toplantilar',
            'path' => '/uye/toplantilar.php',
            'icon' => 'fas fa-users-cog',
            'label' => 'Üye Toplantıları',
            'match' => 'uye/toplantilar',
        ],
        [
            'key' => 'uye_izin_talepleri',
            'path' => '/uye/izin-talepleri.php',
            'icon' => 'fas fa-person-walking',
            'label' => 'Üye İzin Talepleri',
            'match' => 'uye/izin-talepleri',
        ],
        [
            'key' => 'uye_harcama_talepleri',
            'path' => '/uye/harcama-talepleri.php',
            'icon' => 'fas fa-wallet',
            'label' => 'Üye Harcama Talepleri',
            'match' => 'uye/harcama-talepleri',
        ],
        [
            'key' => 'uye_iade_formu',
            'path' => '/uye/iade-formu.php',
            'icon' => 'fas fa-file-invoice-dollar',
            'label' => 'Üye İade Formu',
            'match' => 'uye/iade-formu',
        ],
    ];

    // Map of Uye modules to hide if corresponding Baskan module is active
    $exclusionMap = [
        'uye_dashboard' => 'baskan_dashboard',
        'uye_duyurular' => 'baskan_duyurular',
        'uye_etkinlikler' => 'baskan_etkinlikler',
        'uye_toplantilar' => 'baskan_toplantilar',
        'uye_izin_talepleri' => 'baskan_izin_talepleri',
        'uye_harcama_talepleri' => 'baskan_harcama_talepleri',
        'uye_iade_formu' => 'baskan_iade_formlari',
    ];
    ?>
    <div class="list-group list-group-flush sidebar-scroll">
        <?php if ($isSuperAdmin): ?>
            <!-- Ana Yönetici Menüsü -->
            <a href="/admin/dashboard.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'dashboard') !== false ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i>Kontrol Paneli
            </a>
            
            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">YÖNETİM</div>
            
            <a href="/admin/kullanicilar.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'kullanicilar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users me-2"></i>Kullanıcı Yönetimi
            </a>
            <a href="/admin/byk.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'byk') !== false ? 'active' : ''; ?>">
                <i class="fas fa-building me-2"></i>BYK Yönetimi
            </a>
            <a href="/admin/alt-birimler.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'alt-birimler') !== false ? 'active' : ''; ?>">
                <i class="fas fa-sitemap me-2"></i>Alt Birimler
            </a>
            <a href="/admin/panel-yetkileri.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'panel-yetkileri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-sliders me-2"></i>Panel Yetkilendirme
            </a>
            
            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">İÇERİK</div>
            
            <a href="/admin/etkinlikler.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'etkinlikler') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt me-2"></i>Çalışma Takvimi
            </a>
            <a href="/admin/toplantilar.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'toplantilar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users-cog me-2"></i>Toplantı Yönetimi
            </a>
            <a href="/admin/projeler.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'projeler') !== false ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram me-2"></i>Proje Takibi
            </a>
            <a href="/admin/duyurular.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'duyurular') !== false ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn me-2"></i>Duyurular
            </a>
            
            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">İŞLEMLER</div>
            
            <a href="/admin/izin-talepleri.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'izin-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check me-2"></i>İzin Talepleri
                <span class="badge bg-danger float-end" id="pendingIzinCount">0</span>
            </a>
            <a href="/admin/harcama-talepleri.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'harcama-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave me-2"></i>Harcama Talepleri
                <span class="badge bg-warning float-end" id="pendingHarcamaCount">0</span>
            </a>
            <a href="/admin/demirbaslar.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'demirbaslar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-box me-2"></i>Demirbaş Yönetimi
            </a>
            
            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">RAPORLAR</div>
            
            <a href="/admin/raporlar.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'raporlar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar me-2"></i>Raporlar & Analiz
            </a>
            
            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">AYARLAR</div>
            
            <a href="/admin/ayarlar.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'ayarlar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-cog me-2"></i>Sistem Ayarları
            </a>
            
        <?php elseif ($isBaskan): ?>
            <?php
                // Baskan sidebar logic uses the global $baskanSidebarSections defined above
                foreach ($baskanSidebarSections as $section) {
                    $visibleLinks = array_filter($section['links'], function ($link) use ($auth) {
                        return $auth->hasModulePermission($link['key']);
                    });

                    if (empty($visibleLinks)) {
                        continue;
                    }

                    if (!empty($section['title'])) {
                        echo '<div class="list-group-item fw-bold text-muted small" style="cursor: default;">' . htmlspecialchars($section['title']) . '</div>';
                    }

                    foreach ($visibleLinks as $link) {
                        $isActive = strpos($currentPath, $link['match']) !== false;
                        ?>
                        <a href="<?php echo $link['path']; ?>" class="list-group-item list-group-item-action <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="<?php echo $link['icon']; ?> me-2"></i><?php echo htmlspecialchars($link['label']); ?>
                            <?php if (!empty($link['badge'])): ?>
                                <span class="badge <?php echo $link['badge']['class']; ?> float-end" id="<?php echo $link['badge']['id']; ?>">0</span>
                            <?php endif; ?>
                        </a>
                        <?php
                    }
                }



                $hasUyeLinks = false;
                foreach ($uyeSidebarLinks as $link) {
                    if ($auth->hasModulePermission($link['key'])) {
                        $hasUyeLinks = true;
                        break;
                    }
                }

                if ($hasUyeLinks): ?>
                    <div class="list-group-item fw-bold text-muted small" style="cursor: default;">ÜYE MODÜLLERİ</div>
                    <?php foreach ($uyeSidebarLinks as $link): ?>
                        <?php if ($auth->hasModulePermission($link['key'])): ?>
                            <a href="<?php echo $link['path']; ?>" class="list-group-item list-group-item-action <?php echo strpos($currentPath, $link['match']) !== false ? 'active' : ''; ?>">
                                <i class="<?php echo $link['icon']; ?> me-2"></i><?php echo htmlspecialchars($link['label']); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
        <?php elseif ($isUye): ?>
            <!-- Üye Menüsü -->


            <?php
            // Render Authorized Baskan Modules for Uye
            foreach ($baskanSidebarSections as $section) {
                $visibleLinks = array_filter($section['links'], function ($link) use ($auth) {
                    return $auth->hasModulePermission($link['key']);
                });

                if (empty($visibleLinks)) {
                    continue;
                }

                // Add a separator/header if not present
                if (!empty($section['title'])) {
                    echo '<div class="list-group-item fw-bold text-muted small" style="cursor: default;">' . htmlspecialchars($section['title']) . ' (YETKİLİ)</div>';
                } else {
                     echo '<div class="list-group-item fw-bold text-muted small" style="cursor: default;">YÖNETİM</div>';
                }

                foreach ($visibleLinks as $link) {
                    $isActive = strpos($currentPath, $link['match']) !== false;
                    ?>
                    <a href="<?php echo $link['path']; ?>" class="list-group-item list-group-item-action <?php echo $isActive ? 'active' : ''; ?> list-group-item-warning">
                        <i class="<?php echo $link['icon']; ?> me-2"></i><?php echo htmlspecialchars($link['label']); ?>
                        <?php if (!empty($link['badge'])): ?>
                            <span class="badge <?php echo $link['badge']['class']; ?> float-end" id="<?php echo $link['badge']['id']; ?>">0</span>
                        <?php endif; ?>
                    </a>
                    <?php
                }
            }
            ?>

            <?php
            $hasUyeLinks = false;
            foreach ($uyeSidebarLinks as $link) {
                // Check exclusion
                if (isset($exclusionMap[$link['key']]) && $auth->hasModulePermission($exclusionMap[$link['key']])) {
                    continue;
                }
                if ($auth->hasModulePermission($link['key'])) {
                    $hasUyeLinks = true;
                    break;
                }
            }

            if ($hasUyeLinks): ?>
                <div class="list-group-item fw-bold text-muted small" style="cursor: default;">GÜNCEL</div>
                <?php foreach ($uyeSidebarLinks as $link): ?>
                    <?php 
                    // Re-check exclusion for rendering
                    if (isset($exclusionMap[$link['key']]) && $auth->hasModulePermission($exclusionMap[$link['key']])) {
                        continue;
                    }
                    
                    if ($auth->hasModulePermission($link['key'])): 
                    ?>
                        <a href="<?php echo $link['path']; ?>" class="list-group-item list-group-item-action <?php echo strpos($currentPath, $link['match']) !== false ? 'active' : ''; ?>">
                            <i class="<?php echo $link['icon']; ?> me-2"></i><?php echo htmlspecialchars($link['label']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">HESAP</div>

            <a href="/uye/profil.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'profil') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-circle me-2"></i>Profilim
            </a>
        <?php endif; ?>
    </div>
</div>

