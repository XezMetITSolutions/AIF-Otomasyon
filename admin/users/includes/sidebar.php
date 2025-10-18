<?php
// Sidebar component - kullanıcı yetkilerine göre modülleri gösterir
require_once 'permission_manager.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$currentUser = SessionManager::getCurrentUser();
$username = $currentUser['username'] ?? '';

// Kullanıcının görüntüleyebileceği modülleri al
$visibleModules = PermissionManager::getVisibleModules($username);
$sidebarLinks = PermissionManager::generateSidebarLinks($username);
?>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-header">
        <h4>AIF Otomasyon</h4>
        <small class="text-white-50">
            <?php echo htmlspecialchars($currentUser['full_name'] ?? 'Kullanıcı'); ?>
        </small>
    </div>
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <?php foreach ($sidebarLinks as $link): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === $link['file'] ? 'active' : ''; ?>" href="<?php echo $link['file']; ?>">
                    <i class="<?php echo $link['icon']; ?>"></i> <?php echo $link['name']; ?>
                    <?php if ($link['permission_level'] !== 'read'): ?>
                    <small class="text-white-50 ms-1">
                        (<?php echo $link['permission_data']['name']; ?>)
                    </small>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
            
            <!-- Çıkış -->
            <li class="nav-item mt-3">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </li>
        </ul>
    </div>
</nav>