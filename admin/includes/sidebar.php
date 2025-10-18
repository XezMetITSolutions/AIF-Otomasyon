<?php
// Sidebar component - tüm admin sayfalarında kullanılacak
$currentPage = basename($_SERVER['PHP_SELF']);

// Üye kullanıcıları için sınırlı sidebar
$isMember = isset($currentUser) && $currentUser['role'] === 'member';
?>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-header">
        <h4>AIF Otomasyon</h4>
    </div>
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <?php if ($isMember): ?>
                <!-- Üye Sidebar -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard_member.php' ? 'active' : ''; ?>" href="dashboard_member.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                        <i class="fas fa-bullhorn"></i> Duyurular
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>" href="events.php">
                        <i class="fas fa-calendar-alt"></i> Etkinlikler
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'calendar.php' ? 'active' : ''; ?>" href="calendar.php">
                        <i class="fas fa-calendar"></i> Takvim
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
                    <a class="nav-link <?php echo $currentPage === 'projects.php' ? 'active' : ''; ?>" href="projects.php">
                        <i class="fas fa-project-diagram"></i> Proje Takibi
                    </a>
                </li>
            <?php else: ?>
                <!-- Superadmin Sidebar -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard_superadmin.php' ? 'active' : ''; ?>" href="dashboard_superadmin.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="users.php">
                        <i class="fas fa-users"></i> Kullanıcılar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'permissions.php' ? 'active' : ''; ?>" href="permissions.php">
                        <i class="fas fa-shield-alt"></i> Yetki Yönetimi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                        <i class="fas fa-bullhorn"></i> Duyurular
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>" href="events.php">
                        <i class="fas fa-calendar-alt"></i> Etkinlikler
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'calendar.php' ? 'active' : ''; ?>" href="calendar.php">
                        <i class="fas fa-calendar"></i> Takvim
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">
                        <i class="fas fa-boxes"></i> Demirbaş Listesi
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
                    <a class="nav-link <?php echo $currentPage === 'projects.php' ? 'active' : ''; ?>" href="projects.php">
                        <i class="fas fa-project-diagram"></i> Proje Takibi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                        <i class="fas fa-chart-bar"></i> Raporlar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                        <i class="fas fa-cog"></i> Ayarlar
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- Çıkış - Her iki rol için ortak -->
            <li class="nav-item mt-3">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </li>
        </ul>
    </div>
</nav>
