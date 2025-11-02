<?php
/**
 * Ortak Footer Bileşeni
 */
$appConfig = require __DIR__ . '/../config/app.php';
$auth = new Auth();
$user = $auth->getUser();
?>

    <!-- Footer -->
    <?php if ($user): ?>
        <footer class="bg-light mt-auto py-3">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            &copy; <?php echo date('Y'); ?> <?php echo $appConfig['app_name']; ?> v<?php echo $appConfig['app_version']; ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <?php echo htmlspecialchars($user['name']); ?> (<?php 
                            echo $user['role'] === 'super_admin' ? 'Ana Yönetici' : 
                                ($user['role'] === 'baskan' ? 'Başkan' : 'Üye'); 
                            ?>)
                        </small>
                    </div>
                </div>
            </div>
        </footer>
    <?php endif; ?>
    
    <!-- jQuery 3.7.1 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap 5.3.0 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS (Animate On Scroll) -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    </script>
    
    <!-- Custom JS -->
    <script src="/assets/js/main.js"></script>
    
    <?php if (isset($pageSpecificJS)): ?>
        <?php foreach ($pageSpecificJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if ($user): ?>
        <script>
            // Bildirimleri yükle
            function loadNotifications() {
                $.ajax({
                    url: '/api/bildirimler.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            updateNotificationUI(response.data);
                        }
                    }
                });
            }
            
            function updateNotificationUI(notifications) {
                const count = notifications.filter(n => !n.okundu).length;
                $('#notificationCount').text(count);
                
                const list = $('#notificationsList');
                list.find('li:not(.dropdown-header):not(.dropdown-divider)').remove();
                
                if (notifications.length === 0) {
                    list.append('<li class="text-center p-3"><small class="text-muted">Bildirim bulunmamaktadır</small></li>');
                    return;
                }
                
                notifications.slice(0, 5).forEach(function(notif) {
                    const iconClass = {
                        'bilgi': 'fa-info-circle text-info',
                        'uyari': 'fa-exclamation-triangle text-warning',
                        'basarili': 'fa-check-circle text-success',
                        'hata': 'fa-times-circle text-danger'
                    }[notif.tip] || 'fa-bell';
                    
                    const unreadClass = notif.okundu ? '' : 'fw-bold';
                    const unreadDot = notif.okundu ? '' : '<span class="badge bg-primary rounded-pill ms-2">Yeni</span>';
                    
                    const item = `
                        <li>
                            <a class="dropdown-item ${unreadClass}" href="${notif.link || '#'}">
                                <i class="fas ${iconClass} me-2"></i>
                                <div>
                                    <div>${notif.baslik}</div>
                                    <small class="text-muted">${notif.mesaj}</small>
                                </div>
                                ${unreadDot}
                            </a>
                        </li>
                    `;
                    list.find('.dropdown-divider').after(item);
                });
            }
            
            // Sayfa yüklendiğinde bildirimleri yükle
            $(document).ready(function() {
                loadNotifications();
                setInterval(loadNotifications, 30000); // 30 saniyede bir güncelle
            });
        </script>
    <?php endif; ?>
</body>
</html>

