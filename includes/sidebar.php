<?php
/**
 * Sidebar Bileşeni (Admin ve Başkan panelleri için)
 */
$auth = new Auth();
$user = $auth->getUser();
if (!$user)
    return;

$isSuperAdmin = $user['role'] === 'super_admin';
$isBaskan = $user['role'] === 'uye';
$isUye = $user['role'] === 'uye';
$currentPath = $_SERVER['PHP_SELF'];

// Muhasebe Başkanı veya AT üyesi olup olmadığını kontrol et
$isMuhasebeBaskani = false;
$isAT = false;
if ($user) {
    $db = Database::getInstance();
    try {
        $checkMuhasebe = $db->fetch("SELECT count(*) as cnt FROM byk WHERE muhasebe_baskani_id = ?", [$user['id']]);
        if ($checkMuhasebe && $checkMuhasebe['cnt'] > 0) {
            $isMuhasebeBaskani = true;
        }

        // AT birimi kontrolü
        $checkAT = $db->fetch("SELECT b.byk_kodu FROM byk b JOIN kullanicilar k ON b.byk_id = k.byk_id WHERE k.kullanici_id = ?", [$user['kullanici_id']]);
        if ($checkAT && $checkAT['byk_kodu'] === 'AT') {
            $isAT = true;
        }
    } catch (Exception $e) {
        // Tablo kolonu yoksa veya hata varsa yoksay
    }
}
?>

<div class="sidebar bg-light border-end">
    <?php
    // Define Common Links (Visible to ALL 'uye' and 'baskan')
    $commonLinks = [
        [
            'path' => '/panel/dashboard.php',
            'icon' => 'fas fa-gauge',
            'label' => 'Kontrol Paneli',
            'match' => 'panel/dashboard',
        ],
        [
            'path' => '/panel/duyurular.php',
            'icon' => 'fas fa-bullhorn',
            'label' => 'Duyurular',
            'match' => 'panel/duyurular',
        ],
        [
            'path' => '/panel/etkinlikler.php',
            'icon' => 'fas fa-calendar',
            'label' => 'Çalışma Takvimi',
            'match' => 'panel/etkinlikler',
            'class' => 'no-ajax',
        ],
        [
            'path' => '/panel/toplantilar.php',
            'icon' => 'fas fa-users-cog',
            'label' => 'Toplantılar',
            'match' => 'panel/toplantilar',
        ],
        [
            'path' => '/panel/uyeler.php',
            'icon' => 'fas fa-users',
            'label' => 'Üyeler',
            'match' => 'panel/uyeler',
        ],
    ];

    // Define Management Sections (Requires strictly 'baskan' permissions)
    $baskanSidebarSections = [
        [
            'title' => 'YÖNETİM', // Empty now if we remove uyeler, or we can remove the title logic if empty
            'links' => [
                // 'baskan_uyeler' moved to common
            ],
        ],
        [
            'title' => 'İŞLEMLER (ONAY)',
            'links' => [
                [
                    'key' => 'baskan_izin_talepleri',
                    'path' => '/panel/izin-talepleri.php?tab=onay',
                    'icon' => 'fas fa-calendar-check',
                    'label' => 'İzin Onayları',
                    'match' => 'panel/izin-talepleri',
                    'badge' => ['id' => 'pendingIzinCount', 'class' => 'bg-danger'],
                ],
                [
                    'key' => 'baskan_harcama_talepleri',
                    'path' => '/panel/harcama-talepleri.php?tab=onay',
                    'icon' => 'fas fa-money-bill-wave',
                    'label' => 'Harcama Onayları',
                    'match' => 'panel/harcama-talepleri',
                    'badge' => ['id' => 'pendingHarcamaCount', 'class' => 'bg-warning'],
                ],
                [
                    'key' => 'baskan_iade_formlari',
                    'path' => '/panel/iade-formlari.php?tab=yonetim',
                    'icon' => 'fas fa-hand-holding-usd',
                    'label' => 'İade Onayları',
                    'match' => 'panel/iade-formlari',
                    'badge' => ['id' => 'pendingIadeCount', 'class' => 'bg-info'],
                ],
                [
                    'key' => 'baskan_demirbas_talepleri',
                    'path' => '/panel/demirbas-talepleri.php?tab=onay',
                    'icon' => 'fas fa-box',
                    'label' => 'Demirbaş Talepleri',
                    'match' => 'panel/demirbas-talepleri',
                ],
                [
                    'key' => 'baskan_raggal_talepleri',
                    'path' => '/panel/raggal-talepleri.php?tab=yonetim',
                    'icon' => 'fas fa-calendar-check',
                    'label' => 'Raggal Talepleri',
                    'match' => 'panel/raggal-talepleri',
                    'badge' => ['id' => 'pendingRaggalCount', 'class' => 'bg-primary'],
                    'class' => 'no-ajax',
                ],
                [
                    'key' => 'baskan_projeler',
                    'path' => '/panel/projelerim',
                    'icon' => 'fas fa-project-diagram',
                    'label' => 'Proje Yönetimi',
                    'match' => 'projeler',
                ],
                [
                    'key' => 'baskan_sube_ziyaretleri',
                    'path' => '/panel/sube-ziyaretleri.php',
                    'icon' => 'fas fa-map-location-dot',
                    'label' => 'Şube Ziyaretleri',
                    'match' => 'panel/sube-ziyaretleri',
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

    // Personal / Member Modules
    $uyeSidebarLinks = [
        [
            'key' => 'uye_izin_talepleri',
            'path' => '/panel/izin-talepleri.php?tab=talebim',
            'icon' => 'fas fa-person-walking',
            'label' => 'İzin Taleplerim',
            'match' => 'panel/izin-talepleri',
        ],
        [
            'key' => 'uye_harcama_talepleri',
            'path' => '/panel/harcama-talepleri.php?tab=talebim',
            'icon' => 'fas fa-wallet',
            'label' => 'Harcama Taleplerim',
            'match' => 'panel/harcama-talepleri',
        ],
        [
            'key' => 'uye_iade_formu',
            'path' => '/panel/iade-formlari.php?tab=form',
            'icon' => 'fas fa-file-invoice-dollar',
            'label' => 'İade Talebi Oluştur',
            'match' => 'panel/iade-formlari',
        ],
        [
            'key' => 'uye_demirbas_talep',
            'path' => '/panel/demirbas-talepleri.php?tab=talep',
            'icon' => 'fas fa-box',
            'label' => 'Demirbaş Talep',
            'match' => 'panel/demirbas-talepleri',
        ],
        [
            'key' => 'uye_raggal_talep',
            'path' => '/panel/raggal-talepleri.php?tab=takvim',
            'icon' => 'fas fa-calendar-plus',
            'label' => 'Raggal Rezervasyon',
            'match' => 'panel/raggal-talepleri',
            'class' => 'no-ajax',
        ],
        [
            'key' => 'uye_projeler',
            'path' => '/panel/projelerim',
            'icon' => 'fas fa-list-check',
            'label' => 'Projelerim',
            'match' => 'projeler',
        ],
    ];

    // Map of Uye modules to hide if corresponding Baskan module is active
    // (This might be redundant if we want them to see both their requests and approvals, 
    // but usually Baskan can see their own requests in Baskan panel? 
    // Actually no, Baskan panel is for approvals. Baskan also needs to make requests.
    // So excluding "Request" modules if you have "Approval" permission is WRONG.
    // You should be able to make requests AND approve others.
    // I will remove the Exclusion Map logic for the unified approach so Baskans can see "My Requests" too.)
    
    ?>
    <div class="list-group list-group-flush sidebar-scroll">
        <?php if ($isSuperAdmin): ?>
            <!-- Ana Yönetici Menüsü (Existing Code for Super Admin) -->
            <a href="/admin/dashboard.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'dashboard') !== false ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i>Kontrol Paneli
            </a>

            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">YÖNETİM</div>

            <a href="/admin/kullanicilar.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'kullanicilar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users me-2"></i>Kullanıcı Yönetimi
            </a>
            <a href="/admin/byk.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'byk') !== false ? 'active' : ''; ?>">
                <i class="fas fa-building me-2"></i>BYK Yönetimi
            </a>
            <a href="/admin/alt-birimler.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'alt-birimler') !== false ? 'active' : ''; ?>">
                <i class="fas fa-sitemap me-2"></i>Alt Birimler
            </a>
            <a href="/admin/baskan-yetkileri.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'baskan-yetkileri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-sliders me-2"></i>Üye Yetkileri
            </a>

            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">İÇERİK</div>

            <a href="/admin/etkinlikler.php"
                class="list-group-item list-group-item-action no-ajax <?php echo strpos($currentPath, 'etkinlikler') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt me-2"></i>Çalışma Takvimi
            </a>
            <a href="/admin/toplantilar.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'toplantilar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users-cog me-2"></i>Toplantı Yönetimi
            </a>
            <a href="/admin/projeler.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'projeler') !== false ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram me-2"></i>Proje Takibi
            </a>
            <a href="/admin/duyurular.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'duyurular') !== false ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn me-2"></i>Duyurular
            </a>
            <?php if ($isAT): ?>
            <a href="/admin/sube-ziyaretleri.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'sube-ziyaretleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-map-location-dot me-2"></i>Şube Ziyaretleri
            </a>
            <?php endif; ?>

            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">İŞLEMLER</div>

            <a href="/admin/izin-talepleri.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'izin-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check me-2"></i>İzin Talepleri
                <span class="badge bg-danger float-end" id="pendingIzinCount">0</span>
            </a>
            <a href="/admin/harcama-talepleri.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'harcama-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave me-2"></i>Harcama Talepleri
                <span class="badge bg-warning float-end" id="pendingHarcamaCount">0</span>
            </a>
            <a href="/admin/demirbaslar.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'demirbaslar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-box me-2"></i>Demirbaş Yönetimi
            </a>
            <a href="/admin/demirbas-talepleri.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'demirbas-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-box-open me-2"></i>Demirbaş Talepleri
            </a>
            <a href="/admin/raggal-talepleri.php"
                class="list-group-item list-group-item-action no-ajax <?php echo strpos($currentPath, 'raggal-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check me-2"></i>Raggal Talepleri
            </a>

            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">RAPORLAR</div>

            <a href="/admin/raporlar.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'raporlar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar me-2"></i>Raporlar & Analiz
            </a>

            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">AYARLAR</div>

            <a href="/admin/ayarlar.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'ayarlar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-cog me-2"></i>Sistem Ayarları
            </a>
            <a href="/admin/email-sablonlari.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'email-sablonlari') !== false ? 'active' : ''; ?>">
                <i class="fas fa-envelope-open-text me-2"></i>E-posta Şablonları
            </a>

        <?php else: // Normal Users & Baskans ?>

            <!-- ORTAK ALAN (Everyone sees these) -->
            <?php foreach ($commonLinks as $link):
                $isActive = strpos($currentPath, $link['match']) !== false;
                $extraClass = $link['class'] ?? '';
                ?>
                <a href="<?php echo $link['path']; ?>"
                    class="list-group-item list-group-item-action <?php echo $extraClass; ?> <?php echo $isActive ? 'active' : ''; ?>">
                    <i class="<?php echo $link['icon']; ?> me-2"></i><?php echo htmlspecialchars($link['label']); ?>
                </a>
            <?php endforeach; ?>

            <!-- MANAGE / BASKAN ALANI (Only if authorized/permissioned) -->
            <?php
            $hasAnyManagement = false;
            foreach ($baskanSidebarSections as $section) {
                // Check if user has ANY permission in this section
                $visibleManageLinks = array_filter($section['links'], function ($link) use ($auth, $isMuhasebeBaskani, $isAT) {
                    if ($isMuhasebeBaskani && in_array($link['key'], ['baskan_harcama_talepleri', 'baskan_iade_formlari'])) {
                        return true;
                    }
                    // Şube ziyaretleri sadece AT üyelerine özel
                    if ($link['key'] === 'baskan_sube_ziyaretleri' && !$isAT) {
                        return false;
                    }
                    return $auth->hasModulePermission($link['key']);
                });

                if (!empty($visibleManageLinks)) {
                    $hasAnyManagement = true;
                    // Render Section Title Removed
                    foreach ($visibleManageLinks as $link) {
                        $isActive = strpos($currentPath, $link['match']) !== false;
                        $extraClass = $link['class'] ?? '';
                        ?>
                        <!-- Management Links styled slightly differently or normal? Normal is fine -->
                        <a href="<?php echo $link['path']; ?>"
                            class="list-group-item list-group-item-action list-group-item-warning <?php echo $extraClass; ?> <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="<?php echo $link['icon']; ?> me-2"></i><?php echo htmlspecialchars($link['label']); ?>
                            <?php if (!empty($link['badge'])): ?>
                                <span class="badge <?php echo $link['badge']['class']; ?> float-end"
                                    id="<?php echo $link['badge']['id']; ?>">0</span>
                            <?php endif; ?>
                        </a>
                        <?php
                    }
                }
            }
            ?>

            <!-- KIŞISEL / UYE ALANI (Everyone sees their own request modules if authorized/default) -->
            <?php
            $hasAnyPersonal = false;
            foreach ($uyeSidebarLinks as $link):
                // Basic check if module exists/allowed for viewing personal
                if ($auth->hasModulePermission($link['key'])):
                    $hasAnyPersonal = true;
                    $isActive = strpos($currentPath, $link['match']) !== false;
                    $extraClass = $link['class'] ?? '';
                    ?>
                    <a href="<?php echo $link['path']; ?>"
                        class="list-group-item list-group-item-action <?php echo $extraClass; ?> <?php echo $isActive ? 'active' : ''; ?>">
                        <i class="<?php echo $link['icon']; ?> me-2"></i><?php echo htmlspecialchars($link['label']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>

            <a href="/panel/profil.php"
                class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'profil') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-circle me-2"></i>Profilim
            </a>

        <?php endif; ?>
    </div>
</div>