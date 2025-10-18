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
    <title>AIF Otomasyon - Duyuru Yönetimi</title>
    
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
                    <h1>Duyuru Yönetimi</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                        <i class="fas fa-plus"></i> Yeni Duyuru
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Announcement Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary">0</h3>
                            <p class="text-muted mb-0">Toplam Duyuru</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success">0</h3>
                            <p class="text-muted mb-0">Aktif Duyuru</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">0</h3>
                            <p class="text-muted mb-0">Beklemede</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-info">0</h3>
                            <p class="text-muted mb-0">Toplam Görüntüleme</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Announcement Management -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-bullhorn"></i> Duyuru Listesi</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Duyuru ara...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <select class="form-select">
                                    <option>Tüm Durumlar</option>
                                    <option>Aktif</option>
                                    <option>Pasif</option>
                                    <option>Beklemede</option>
                                </select>
                                <button class="btn btn-outline-primary">
                                    <i class="fas fa-filter"></i> Filtrele
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Başlık</th>
                                    <th>İçerik</th>
                                    <th>Yayın Tarihi</th>
                                    <th>Durum</th>
                                    <th>Görüntüleme</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-bullhorn fa-2x mb-2"></i><br>
                                        Henüz duyuru bulunmamaktadır.
                                        <br><br>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                                            <i class="fas fa-plus"></i> İlk Duyuruyu Ekle
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Announcement Modal -->
    <div class="modal fade" id="addAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Duyuru Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addAnnouncementForm">
                        <div class="mb-3">
                            <label class="form-label">Duyuru Başlığı</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duyuru İçeriği</label>
                            <textarea class="form-control" name="content" rows="5" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yayın Tarihi</label>
                                <input type="datetime-local" class="form-control" name="publish_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" name="status">
                                    <option value="active">Aktif</option>
                                    <option value="pending">Beklemede</option>
                                    <option value="draft">Taslak</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hedef Kitle</label>
                            <select class="form-select" name="target_audience">
                                <option value="all">Tüm Kullanıcılar</option>
                                <option value="admin">Sadece Adminler</option>
                                <option value="member">Sadece Üyeler</option>
                                <option value="byk_at">AT Üyeleri</option>
                                <option value="byk_kt">KT Üyeleri</option>
                                <option value="byk_kgt">KGT Üyeleri</option>
                                <option value="byk_gt">GT Üyeleri</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" onclick="saveAnnouncement()">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function saveAnnouncement() {
            const form = document.getElementById('addAnnouncementForm');
            const formData = new FormData(form);
            
            // Simulate API call
            showAlert('Duyuru başarıyla eklendi!', 'success');
            $('#addAnnouncementModal').modal('hide');
            form.reset();
            
            // In real implementation, you would send data to server
            console.log('Adding announcement:', Object.fromEntries(formData));
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