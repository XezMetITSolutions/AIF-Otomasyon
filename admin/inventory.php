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
    <title>AIF Otomasyon - Demirbaş Listesi</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        .asset-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .asset-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .asset-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }
        
        .asset-icon.computer {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }
        
        .asset-icon.furniture {
            background: linear-gradient(135deg, #28a745, #1e7e34);
        }
        
        .asset-icon.equipment {
            background: linear-gradient(135deg, #ffc107, #e0a800);
        }
        
        .asset-icon.vehicle {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .status-active {
            background-color: #28a745 !important;
        }
        
        .status-maintenance {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        
        .status-disposed {
            background-color: #dc3545 !important;
        }
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
                    <h1>Demirbaş Listesi</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                        <i class="fas fa-plus"></i> Demirbaş Ekle
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Asset Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary">0</h3>
                            <p class="text-muted mb-0">Toplam Demirbaş</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success">0</h3>
                            <p class="text-muted mb-0">Aktif</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">0</h3>
                            <p class="text-muted mb-0">Bakımda</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-danger">0</h3>
                            <p class="text-muted mb-0">Hurdaya Ayrıldı</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Categories -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="asset-card">
                        <div class="card-body text-center">
                            <div class="asset-icon computer">
                                <i class="fas fa-laptop"></i>
                            </div>
                            <h5>Bilgisayar</h5>
                            <p class="text-muted mb-0">0 adet</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="asset-card">
                        <div class="card-body text-center">
                            <div class="asset-icon furniture">
                                <i class="fas fa-chair"></i>
                            </div>
                            <h5>Mobilya</h5>
                            <p class="text-muted mb-0">0 adet</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="asset-card">
                        <div class="card-body text-center">
                            <div class="asset-icon equipment">
                                <i class="fas fa-tools"></i>
                            </div>
                            <h5>Ekipman</h5>
                            <p class="text-muted mb-0">0 adet</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="asset-card">
                        <div class="card-body text-center">
                            <div class="asset-icon vehicle">
                                <i class="fas fa-car"></i>
                            </div>
                            <h5>Araç</h5>
                            <p class="text-muted mb-0">0 adet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Management -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-boxes"></i> Demirbaş Listesi</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Demirbaş ara...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <select class="form-select">
                                    <option>Tüm Kategoriler</option>
                                    <option>Bilgisayar</option>
                                    <option>Mobilya</option>
                                    <option>Ekipman</option>
                                    <option>Araç</option>
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
                                    <th>Demirbaş</th>
                                    <th>Kategori</th>
                                    <th>Seri No</th>
                                    <th>Sorumlu</th>
                                    <th>Durum</th>
                                    <th>Satın Alma Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-boxes fa-2x mb-2"></i><br>
                                        Henüz demirbaş bulunmamaktadır.
                                        <br><br>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                                            <i class="fas fa-plus"></i> İlk Demirbaşı Ekle
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

    <!-- Add Asset Modal -->
    <div class="modal fade" id="addAssetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Demirbaş Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Demirbaş Adı</label>
                                <input type="text" class="form-control" placeholder="Demirbaş adını girin">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select class="form-select">
                                    <option>Bilgisayar</option>
                                    <option>Mobilya</option>
                                    <option>Ekipman</option>
                                    <option>Araç</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Seri No</label>
                                <input type="text" class="form-control" placeholder="Seri numarasını girin">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sorumlu</label>
                                <input type="text" class="form-control" placeholder="Sorumlu kişiyi girin">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Satın Alma Tarihi</label>
                                <input type="date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select">
                                    <option>Aktif</option>
                                    <option>Bakımda</option>
                                    <option>Hurdaya Ayrıldı</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" rows="3" placeholder="Demirbaş hakkında açıklama"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function saveAsset() {
            const form = document.getElementById('addAssetForm');
            const formData = new FormData(form);
            
            // Simulate API call
            showAlert('Demirbaş başarıyla eklendi!', 'success');
            $('#addAssetModal').modal('hide');
            form.reset();
            
            // In real implementation, you would send data to server
            console.log('Adding asset:', Object.fromEntries(formData));
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