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
                                ($user['role'] === 'uye' ? 'Başkan' : 'Üye'); 
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
    
    <?php if ($enableAnimations ?? false): ?>
        <!-- AOS (Animate On Scroll) -->
        <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
        <script>
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
        </script>
    <?php endif; ?>
    
    <!-- Custom JS -->
    <script src="/assets/js/main.js"></script>
    
    <?php if (isset($pageSpecificJS)): ?>
        <?php foreach ($pageSpecificJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if ($user): ?>
        <div id="page-loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; display: none; align-items: center; justify-content: center;">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
        </div>

        <script>
            // Global AJAX Navigation (Simple SPA)
            $(document).ready(function() {
                
                // 1. Link Interception
                $(document).on('click', 'a', function(e) {
                    const el = $(this);
                    const href = el.attr('href');
                    const target = el.attr('target');
                    
                    // Basic exclusions
                    if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:') || target === '_blank' || el.hasClass('no-ajax')) {
                        return;
                    }

                    // Check if external
                    const isAbsolute = href.indexOf('://') > 0 || href.indexOf('//') === 0;
                    if (isAbsolute) {
                        // Allow if it matches current origin
                        const linkUrl = new URL(href, window.location.origin);
                        if (linkUrl.origin !== window.location.origin) {
                             return;
                        }
                    }

                    // Determine if we should handle it
                    // effectively handle virtually all other links as internal SPA links
                    e.preventDefault();
                    
                    // Normalize URL for loadPage (keep relative or full, loadPage/fetch handles it)
                    loadPage(href);
                });

                // 2. Form Interception
                $(document).on('submit', 'form', function(e) {
                    if ($(this).hasClass('no-ajax')) return;
                    
                    e.preventDefault();
                    const form = $(this);
                    let url = form.attr('action') || (window.location.pathname + window.location.search);
                    const method = (form.attr('method') || 'POST').toUpperCase();
                    const formData = new FormData(this); // Supports file uploads too

                    // Add submit button value if clicked (basic handling)
                    // (For simple forms, this usually works. For complex multi-button forms, might need explicit monitoring)

                    showLoader();

                    // Handle GET vs POST differently
                    let fetchOptions = {};
                    
                    if (method === 'GET') {
                        // For GET requests, append form data as URL parameters
                        const params = new URLSearchParams();
                        for (const [key, value] of formData.entries()) {
                            if (value !== '' && value !== null) {
                                params.append(key, value);
                            }
                        }
                        const queryString = params.toString();
                        if (queryString) {
                            url = url.split('?')[0] + '?' + queryString;
                        }
                        fetchOptions = { method: 'GET' };
                    } else {
                        // For POST requests, send form data in body
                        fetchOptions = { method: 'POST', body: formData };
                    }

                    fetch(url, fetchOptions)
                    .then(response => {
                         // Check for redirect headers if possible (opaque in fetch unless handled)
                         // But usually we just get the result HTML
                         return response.text().then(html => ({ html, url: response.url }));
                    })
                    .then(data => {
                        updateContent(data.html, data.url);
                        // Update browser URL for both GET and POST (e.g. after redirects)
                        if (method === 'GET' || data.url !== window.location.href) {
                            window.history.pushState({path: data.url}, '', data.url);
                        }
                        hideLoader();
                    })
                    .catch(err => {
                        console.error('Form Submit Error:', err);
                        window.location.reload(); // Fallback
                    });
                });

                // 3. History Handling
                window.addEventListener('popstate', function(e) {
                    if (e.state && e.state.path) {
                        loadPage(e.state.path, false);
                    } else {
                        // Initial page or manual change
                         window.location.reload();
                    }
                });
            });

            function loadPage(url, push = true) {
                showLoader();
                
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        updateContent(html, url);
                        if (push) {
                            window.history.pushState({path: url}, '', url);
                        }
                        hideLoader();
                    })
                    .catch(err => {
                        console.error('Load Error:', err);
                        hideLoader();
                        // Optional: Show error toast
                    });
            }

            function updateContent(html, url) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Replace Content
                const newContent = doc.querySelector('.content-wrapper');
                if (newContent) {
                    const currentWrapper = document.querySelector('.content-wrapper');
                    if(currentWrapper) {
                        currentWrapper.innerHTML = newContent.innerHTML;
                        
                        // Re-execute scripts in the new content
                        const scripts = newContent.querySelectorAll('script');
                        scripts.forEach(oldScript => {
                            const newScript = document.createElement('script');
                            Array.from(oldScript.attributes).forEach(attr => {
                                newScript.setAttribute(attr.name, attr.value);
                            });
                            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                            currentWrapper.appendChild(newScript);
                        });
                    }
                } else {
                    // Fallback if structure is different
                    // document.body.innerHTML = doc.body.innerHTML;
                    // Provide fallback reload if structure mismatch
                    window.location.reload();
                    return;
                }
                
                // Update Title
                document.title = doc.title;
                
                // Update specific layout parts if needed (e.g. user menu name?)
                // Usually content-wrapper is enough for main admin pages.

                // Update Active Sidebar Link
                updateSidebarState(url);
                
                // Re-initialize specific JS plugins (like FullCalendar)
                // This is tricky. Ideally, each page should check for its needs.
                // Or we can trigger a custom event
                $(document).trigger('page:loaded');
                
                // Re-run notifications update
                loadNotifications();
                loadSidebarCounts();
            }

            function updateSidebarState(url) {
                // Remove active from all
                $('.sidebar a').removeClass('active');
                
                // Simple matching strategy
                // Try to find link with exact href
                // Or href starting with...
                
                // Clean url from query params for basic matching if needed
                const cleanUrl = url.split('?')[0];
                
                let match = $('.sidebar a[href="' + url + '"]');
                if (match.length === 0) {
                     match = $('.sidebar a[href^="' + cleanUrl + '"]');
                }
                
                if (match.length > 0) {
                    match.addClass('active');
                }
            }

            function showLoader() {
                $('#page-loading-overlay').fadeIn(200);
            }
            function hideLoader() {
                $('#page-loading-overlay').fadeOut(200);
            }

            // Bildirimleri yükle (Existing function kept below)
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
            
            // Sayfa yüklendiğinde bildirimleri ve sayıları yükle
            $(document).ready(function() {
                loadNotifications();
                loadSidebarCounts();
                
                setInterval(loadNotifications, 30000); // 30 saniyede bir güncelle
                setInterval(loadSidebarCounts, 15000); // 15 saniyede bir sayıları güncelle (Daha hızlı)
            });

            function loadSidebarCounts() {
                $.ajax({
                    url: '/api/counts.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.counts) {
                            updateBadge('pendingIzinCount', response.counts.pendingIzinCount);
                            updateBadge('pendingHarcamaCount', response.counts.pendingHarcamaCount);
                            updateBadge('pendingIadeCount', response.counts.pendingIadeCount);
                            updateBadge('pendingRaggalCount', response.counts.pendingRaggalCount);
                        }
                    }
                });
            }

            function updateBadge(id, count) {
                const badge = $('#' + id);
                if (badge.length) {
                    badge.text(count);
                    if (count > 0) {
                        badge.show();
                    } else {
                        // Seçimsel: 0 ise gizle veya gri yap
                        badge.text('0'); 
                        // badge.hide(); 
                    }
                }
            }
        </script>
    <?php endif; ?>
</body>
</html>

