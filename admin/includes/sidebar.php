<?php
// Sidebar component - admin klasörü için (Sadece Superadmin Paneli)
$currentPage = basename($_SERVER['PHP_SELF']);
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
            <!-- Superadmin Sidebar -->
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard_superadmin.php' ? 'active' : ''; ?>" href="dashboard_superadmin.php">
                    <i class="fas fa-tachometer-alt"></i> Superadmin Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard_admin.php' ? 'active' : ''; ?>" href="dashboard_admin.php">
                    <i class="fas fa-user-shield"></i> Yönetici Paneli
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'calendar.php' ? 'active' : ''; ?>" href="calendar.php">
                    <i class="fas fa-calendar"></i> Takvim
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'meeting_reports.php' ? 'active' : ''; ?>" href="meeting_reports.php">
                    <i class="fas fa-file-alt"></i> Toplantı Raporları
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'reservations.php' ? 'active' : ''; ?>" href="reservations.php">
                    <i class="fas fa-bookmark"></i> Rezervasyon
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'expenses.php' ? 'active' : ''; ?>" href="expenses.php">
                    <i class="fas fa-undo"></i> Para İadesi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'code_list.php' ? 'active' : ''; ?>" href="code_list.php">
                    <i class="fas fa-list-alt"></i> Code List Yönetimi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i> Kullanıcı Yönetimi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'debug_users_page.php' ? 'active' : ''; ?>" href="debug_users_page.php">
                    <i class="fas fa-bug"></i> Debug Sayfası
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'permissions.php' ? 'active' : ''; ?>" href="permissions.php">
                    <i class="fas fa-key"></i> Yetki Yönetimi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i> Ayarlar
                </a>
            </li>
            
            <!-- Çıkış -->
            <li class="nav-item mt-3">
                <a class="nav-link" href="logout.php">
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
