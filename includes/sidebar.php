<?php
/**
 * Sidebar Bileşeni (Admin ve Başkan panelleri için)
 */
$auth = new Auth();
$user = $auth->getUser();
if (!$user) return;

$isSuperAdmin = $user['role'] === 'super_admin';
$isBaskan = $user['role'] === 'uye';
$isUye = $user['role'] === 'uye';
$currentPath = $_SERVER['PHP_SELF'];

// Muhasebe Başkanı olup olmadığını kontrol et
$isMuhasebeBaskani = false;
if ($user) {
    $db = Database::getInstance();
    try {
        $checkMuhasebe = $db->fetch("SELECT count(*) as cnt FROM byk WHERE muhasebe_baskani_id = ?", [$user['id']]);
        if ($checkMuhasebe && $checkMuhasebe['cnt'] > 0) {
            $isMuhasebeBaskani = true;
        }
    } catch (Exception $e) {
        // Tablo kolonu yoksa veya hata varsa yoksay
    }
}
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
                    'path' => '/panel/baskan_dashboard.php',
                    'icon' => 'fas fa-tachometer-alt',
                    'label' => 'Yönetici Paneli', // Changed label to distinguish from Member Dashboard
                    'match' => 'panel/baskan_dashboard', // More specific match
                ],
            ],
        ],
        [
            'title' => 'YÖNETİM',
            'links' => [
                [
                    'key' => 'baskan_uyeler',
                    'path' => '/panel/baskan_uyeler.php',
                    'icon' => 'fas fa-users',
                    'label' => 'Üye Yönetimi',
                    'match' => 'baskan_uyeler',
                ],
            ],
        ],
        [
            'title' => 'İÇERİK',
            'links' => [
                [
                    'key' => 'baskan_etkinlikler',
                    'path' => '/panel/baskan_etkinlikler.php',
                    'icon' => 'fas fa-calendar',
                    'label' => 'Etkinlik Yönetimi',
                    'match' => 'panel/baskan_etkinlikler',
                ],
                [
                    'key' => 'baskan_toplantilar',
                    'path' => '/panel/baskan_toplantilar.php',
                    'icon' => 'fas fa-users-cog',
                    'label' => 'Toplantı Yönetimi',
                    'match' => 'panel/baskan_toplantilar',
                ],
                [
                    'key' => 'baskan_duyurular',
                    'path' => '/panel/baskan_duyurular.php',
                    'icon' => 'fas fa-bullhorn',
                    'label' => 'Duyuru Yönetimi',
                    'match' => 'panel/baskan_duyurular',
                ],
            ],
        ],
        [
            'title' => 'İŞLEMLER',
            'links' => [
                [
                    'key' => 'baskan_izin_talepleri',
                    'path' => '/panel/baskan_izin-talepleri.php',
                    'icon' => 'fas fa-calendar-check',
                    'label' => 'İzin Onayları',
                    'match' => 'panel/baskan_izin-talepleri',
                    'badge' => ['id' => 'pendingIzinCount', 'class' => 'bg-danger'],
                ],
                [
                    'key' => 'baskan_harcama_talepleri',
                    'path' => '/panel/baskan_harcama-talepleri.php',
                    'icon' => 'fas fa-money-bill-wave',
                    'label' => 'Harcama Onayları',
                    'match' => 'panel/baskan_harcama-talepleri',
                    'badge' => ['id' => 'pendingHarcamaCount', 'class' => 'bg-warning'],
                ],
                [
                    'key' => 'baskan_iade_formlari',
                    'path' => '/panel/baskan_iade-formlari.php',
                    'icon' => 'fas fa-hand-holding-usd',
                    'label' => 'İade Onayları',
                    'match' => 'panel/baskan_iade-formlari',
                ],
                [
                    'key' => 'baskan_demirbas_talepleri',
                    'path' => '/panel/baskan_demirbas-talepleri.php',
                    'icon' => 'fas fa-box',
                    'label' => 'Demirbaş Talepleri',
                    'match' => 'panel/baskan_demirbas-talepleri',
                ],
                [
                    'key' => 'baskan_raggal_talepleri',
                    'path' => '/panel/baskan_raggal-talepleri.php',
                    'icon' => 'fas fa-calendar-check',
                    'label' => 'Raggal Talepleri',
                    'match' => 'panel/baskan_raggal-talepleri',
                ],
            ],
        ],
        [
            'title' => 'RAPORLAR',
            'links' => [
                [
                    'key' => 'baskan_raporlar',
                    'path' => '/panel/baskan_raporlar.php',
                    'icon' => 'fas fa-chart-bar',
                    'label' => 'Raporlar',
                    'match' => 'raporlar',
                ],
            ],
        ],
    ];

    $uyeSidebarLinks = [
        [
            'key' => 'baskan_dashboard', // Unified permission
            'path' => '/panel/dashboard.php',
            'icon' => 'fas fa-gauge',
            'label' => 'Kontrol Paneli',
            'match' => 'panel/dashboard',
        ],
        [
            'key' => 'uye_duyurular',
            'path' => '/panel/uye_duyurular.php',
            'icon' => 'fas fa-bullhorn',
            'label' => 'Üye Duyuruları',
            'match' => 'panel/uye_duyurular',
        ],
        [
            'key' => 'uye_etkinlikler',
            'path' => '/panel/uye_etkinlikler.php',
            'icon' => 'fas fa-calendar',
            'label' => 'Çalışma Takvimi',
            'match' => 'panel/uye_etkinlikler',
        ],
        [
            'key' => 'uye_toplantilar',
            'path' => '/panel/uye_toplantilar.php',
            'icon' => 'fas fa-users-cog',
            'label' => 'Üye Toplantıları',
            'match' => 'panel/uye_toplantilar',
        ],
        [
            'key' => 'uye_izin_talepleri',
            'path' => '/panel/uye_izin-talepleri.php',
            'icon' => 'fas fa-person-walking',
            'label' => 'Üye İzin Talepleri',
            'match' => 'panel/uye_izin-talepleri',
        ],
        [
            'key' => 'uye_harcama_talepleri',
            'path' => '/panel/uye_harcama-talepleri.php',
            'icon' => 'fas fa-wallet',
            'label' => 'Üye Harcama Talepleri',
            'match' => 'panel/uye_harcama-talepleri',
        ],
        [
            'key' => 'uye_iade_formu',
            'path' => '/panel/uye_iade-formu.php',
            'icon' => 'fas fa-file-invoice-dollar',
            'label' => 'Üye İade Formu',
            'match' => 'panel/uye_iade-formu',
        ],
        [
            'key' => 'uye_demirbas_talep',
            'path' => '/panel/uye_demirbas-talep.php',
            'icon' => 'fas fa-box',
            'label' => 'Demirbaş Talep',
            'match' => 'panel/uye_demirbas-talep',
        ],
        [
            'key' => 'uye_raggal_talep',
            'path' => '/panel/uye_raggal-talep.php',
            'icon' => 'fas fa-calendar-plus',
            'label' => 'Raggal Rezervasyon',
            'match' => 'panel/uye_raggal-talep',
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
        'uye_demirbas_talep' => 'baskan_demirbas_talepleri',
        'uye_raggal_talep' => 'baskan_raggal_talepleri',
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
            <a href="/admin/demirbas-talepleri.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'demirbas-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-box-open me-2"></i>Demirbaş Talepleri
            </a>
            <a href="/admin/raggal-talepleri.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'raggal-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check me-2"></i>Raggal Talepleri
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
                    $visibleLinks = array_filter($section['links'], function ($link) use ($auth, $isMuhasebeBaskani) {
                        // Dashboard link update
                        if ($link['key'] === 'baskan_dashboard') {
                             $link['path'] = '/panel/dashboard.php';
                        }
                        
                        // Muhasebe başkanı ise harcama ve iade modüllerini görsün
                        if ($isMuhasebeBaskani && in_array($link['key'], ['baskan_harcama_talepleri', 'baskan_iade_formlari'])) {
                            return true;
                        }
                        return $auth->hasModulePermission($link['key']);
                    });

                    if (empty($visibleLinks)) {
                        continue;
                    }

                    // Section titles removed as per user request

                    foreach ($visibleLinks as $link) {
                        // Manual update for dashboard path in display loop if needed, though we updated filtering above, likely need to update $link structure or just hardcode checking.
                        if ($link['key'] === 'baskan_dashboard') {
                            $link['path'] = '/panel/dashboard.php';
                        }
                        
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
                    // EXCLUSION CHECK FOR BASKAN: Hide Uye links if Baskan has equivalent permissions or simply hide redundant ones.
                    if (isset($exclusionMap[$link['key']]) && $auth->hasModulePermission($exclusionMap[$link['key']])) {
                        continue;
                    }
                    
                    if ($auth->hasModulePermission($link['key'])) {
                        $hasUyeLinks = true;
                        break;
                    }
                }

                if ($hasUyeLinks): ?>
                    <!-- Section title removed -->
                    <?php foreach ($uyeSidebarLinks as $link): ?>
                        <?php 
                        // EXCLUSION CHECK REPEATED
                        if (isset($exclusionMap[$link['key']]) && $auth->hasModulePermission($exclusionMap[$link['key']])) {
                            continue;
                        }

                        if ($link['key'] === 'uye_dashboard') {
                            $link['path'] = '/panel/dashboard.php';
                        }
                        
                        if ($auth->hasModulePermission($link['key'])): ?>
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
                $visibleLinks = array_filter($section['links'], function ($link) use ($auth, $isMuhasebeBaskani) {
                    if ($isMuhasebeBaskani && in_array($link['key'], ['baskan_harcama_talepleri', 'baskan_iade_formlari'])) {
                        return true;
                    }
                    return $auth->hasModulePermission($link['key']);
                });

                if (empty($visibleLinks)) {
                    continue;
                }

                // Section titles removed

                foreach ($visibleLinks as $link) {
                    if ($link['key'] === 'baskan_dashboard') {
                        $link['path'] = '/panel/dashboard.php';
                    }
                    
                    $isActive = strpos($currentPath, $link['match']) !== false;
                    ?>
                    <!-- Baskan modules for Uye get 'list-group-item-warning' for distinct color -->
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
                <!-- Section title removed -->
                <?php foreach ($uyeSidebarLinks as $link): ?>
                    <?php 
                    // Re-check exclusion for rendering
                    if (isset($exclusionMap[$link['key']]) && $auth->hasModulePermission($exclusionMap[$link['key']])) {
                        continue;
                    }

                    if ($link['key'] === 'uye_dashboard') {
                        $link['path'] = '/panel/dashboard.php';
                    }
                    
                    if ($auth->hasModulePermission($link['key'])): 
                    ?>
                        <a href="<?php echo $link['path']; ?>" class="list-group-item list-group-item-action <?php echo strpos($currentPath, $link['match']) !== false ? 'active' : ''; ?>">
                            <i class="<?php echo $link['icon']; ?> me-2"></i><?php echo htmlspecialchars($link['label']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Section title removed -->

            <a href="/panel/uye_profil.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'profil') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-circle me-2"></i>Profilim
            </a>
        <?php endif; ?>
    </div>
</div>

