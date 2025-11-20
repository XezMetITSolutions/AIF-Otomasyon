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
            <a href="/admin/roller.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'roller') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-shield me-2"></i>Rol & Yetki Yönetimi
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
            <!-- Başkan Menüsü -->
            <a href="/baskan/dashboard.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'dashboard') !== false ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i>Kontrol Paneli
            </a>
            
            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">YÖNETİM</div>
            
            <a href="/baskan/uyeler.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'uyeler') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users me-2"></i>Üye Yönetimi
            </a>
            
            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">İÇERİK</div>
            
            <a href="/baskan/etkinlikler.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'etkinlikler') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar me-2"></i>Etkinlikler
            </a>
            <a href="/baskan/toplantilar.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'toplantilar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users-cog me-2"></i>Toplantılar
            </a>
            <a href="/baskan/duyurular.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'duyurular') !== false ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn me-2"></i>Duyurular
            </a>
            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">İŞLEMLER</div>
            
            <a href="/baskan/izin-talepleri.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'izin-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check me-2"></i>İzin Talepleri
                <span class="badge bg-danger float-end" id="pendingIzinCount">0</span>
            </a>
            <a href="/baskan/harcama-talepleri.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'harcama-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave me-2"></i>Harcama Talepleri
                <span class="badge bg-warning float-end" id="pendingHarcamaCount">0</span>
            </a>
            <a href="/baskan/iade-formlari.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'iade-formlari') !== false ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding-usd me-2"></i>İade Formları
            </a>
            
            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">RAPORLAR</div>
            
            <a href="/baskan/raporlar.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'raporlar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar me-2"></i>Raporlar
            </a>
        <?php elseif ($isUye): ?>
            <!-- Üye Menüsü -->
            <a href="/uye/dashboard.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'dashboard') !== false ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i>Kontrol Paneli
            </a>

            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">GÜNCEL</div>

            <a href="/uye/duyurular.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'duyurular') !== false ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn me-2"></i>Duyurular
            </a>
            <a href="/uye/etkinlikler.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'etkinlikler') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar me-2"></i>Etkinlikler
            </a>
            <a href="/uye/toplantilar.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'toplantilar') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users-cog me-2"></i>Toplantılar
            </a>

            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">İŞLEMLER</div>

            <a href="/uye/izin-talepleri.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'izin-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check me-2"></i>İzin Taleplerim
            </a>
            <a href="/uye/harcama-talepleri.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'harcama-talepleri') !== false ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave me-2"></i>Harcama Taleplerim
            </a>
            <a href="/uye/iade-formu.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'iade-formu') !== false ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding-usd me-2"></i>İade Talebi Formu
            </a>

            <div class="list-group-item fw-bold text-muted small" style="cursor: default;">HESAP</div>

            <a href="/uye/profil.php" class="list-group-item list-group-item-action <?php echo strpos($currentPath, 'profil') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-circle me-2"></i>Profilim
            </a>
        <?php endif; ?>
    </div>
</div>

