<?php
// Sidebar component - users klasörü için (Sadece Üye Paneli)
$currentPage = basename($_SERVER['PHP_SELF']);

// Yetki kontrolü için PermissionManager'ı dahil et
require_once 'permission_manager.php';

// Mevcut kullanıcının yetkilerini al
$currentUser = null;
if (isset($_SESSION['username'])) {
    $currentUser = $_SESSION['username'];
}

// Yetki kontrolü fonksiyonu
function hasModulePermission($module) {
    global $currentUser;
    if (!$currentUser) return false;
    
    return PermissionManager::hasPermission($currentUser, $module);
}
?>
<!-- Hamburger Menu Button (Mobilde görünür) -->
<button class="hamburger-menu" id="hamburgerMenu">
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
</button>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4>AIF Otomasyon</h4>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <!-- Üye Sidebar -->
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard_member.php' ? 'active' : ''; ?>" href="dashboard_member.php">
                    <i class="fas fa-tachometer-alt"></i> Üye Dashboard
                </a>
            </li>
            <?php if (hasModulePermission('announcements')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                    <i class="fas fa-bullhorn"></i> Duyurular
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasModulePermission('events')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>" href="events.php">
                    <i class="fas fa-calendar-alt"></i> Etkinlikler
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasModulePermission('calendar')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'calendar.php' ? 'active' : ''; ?>" href="calendar.php">
                    <i class="fas fa-calendar"></i> Takvim
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasModulePermission('reservations')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'reservations.php' ? 'active' : ''; ?>" href="reservations.php">
                    <i class="fas fa-bookmark"></i> Rezervasyon
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasModulePermission('expenses')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'expenses.php' ? 'active' : ''; ?>" href="expenses.php">
                    <i class="fas fa-undo"></i> Para İadesi
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasModulePermission('projects')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'projects.php' ? 'active' : ''; ?>" href="projects.php">
                    <i class="fas fa-project-diagram"></i> Proje Takibi
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasModulePermission('users')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i> Kullanıcı Yönetimi
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasModulePermission('permissions')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'permissions.php' ? 'active' : ''; ?>" href="permissions.php">
                    <i class="fas fa-key"></i> Yetki Yönetimi
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Çıkış -->
            <li class="nav-item mt-3">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Sidebar JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const sidebar = document.getElementById('sidebar');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const sidebarClose = document.getElementById('sidebarClose');
    
    // Hamburger menü tıklama
    hamburgerMenu.addEventListener('click', function() {
        sidebar.classList.add('sidebar-open');
        mobileOverlay.classList.add('overlay-active');
        document.body.classList.add('sidebar-open');
        hamburgerMenu.classList.add('active');
    });
    
    // Overlay tıklama
    mobileOverlay.addEventListener('click', function() {
        closeSidebar();
    });
    
    // Kapatma butonu
    sidebarClose.addEventListener('click', function() {
        closeSidebar();
    });
    
    // ESC tuşu ile kapatma
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        }
    });
    
    // Sidebar kapatma fonksiyonu
    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        mobileOverlay.classList.remove('overlay-active');
        document.body.classList.remove('sidebar-open');
        hamburgerMenu.classList.remove('active');
    }
    
    // Sidebar linklerine tıklandığında mobilde kapat
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
    
    // Ekran boyutu değiştiğinde sidebar'ı kontrol et
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
});
</script>
