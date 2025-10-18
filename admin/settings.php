<?php
require_once 'auth.php';

// Session kontrolü - sadece superadmin giriş yapabilir
SessionManager::requireRole('superadmin');

$currentUser = SessionManager::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Ayarlar</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Sistem Ayarları</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="saveSettings()">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Settings Categories -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <i class="fas fa-cog fa-2x text-primary mb-2"></i>
                            <h5>Genel Ayarlar</h5>
                            <p class="text-muted mb-0">Sistem genel ayarları</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x text-success mb-2"></i>
                            <h5>Kullanıcı Ayarları</h5>
                            <p class="text-muted mb-0">Kullanıcı yönetimi</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt fa-2x text-warning mb-2"></i>
                            <h5>Güvenlik</h5>
                            <p class="text-muted mb-0">Güvenlik ayarları</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <i class="fas fa-bell fa-2x text-info mb-2"></i>
                            <h5>Bildirimler</h5>
                            <p class="text-muted mb-0">Bildirim ayarları</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Forms -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-cog"></i> Genel Ayarlar</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Site Adı</label>
                                    <input type="text" class="form-control" value="AIF Otomasyon">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Site Açıklaması</label>
                                    <textarea class="form-control" rows="3" placeholder="Site açıklaması"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Dil</label>
                                    <select class="form-select">
                                        <option>Türkçe</option>
                                        <option>English</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Zaman Dilimi</label>
                                    <select class="form-select">
                                        <option>Europe/Vienna</option>
                                        <option>Europe/Istanbul</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-shield-alt"></i> Güvenlik Ayarları</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Oturum Süresi (dakika)</label>
                                    <input type="number" class="form-control" value="60">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Maksimum Giriş Denemesi</label>
                                    <input type="number" class="form-control" value="5">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="twoFactor">
                                        <label class="form-check-label" for="twoFactor">
                                            İki Faktörlü Doğrulama
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="passwordPolicy">
                                        <label class="form-check-label" for="passwordPolicy">
                                            Güçlü Şifre Politikası
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function saveSettings() {
            showAlert('Ayarlar başarıyla kaydedildi!', 'success');
            console.log('Saving settings...');
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
        }
        
        function logout() {
            if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=logout'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '../index.php';
                    }
                });
            }
        }
    </script>
</body>
</html>