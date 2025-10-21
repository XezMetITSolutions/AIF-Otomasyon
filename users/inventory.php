<?php
require_once 'auth.php';

// Login kontrolü kaldırıldı - direkt erişim
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
                            <h3 class="text-primary">156</h3>
                            <p class="text-muted mb-0">Toplam Demirbaş</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success">142</h3>
                            <p class="text-muted mb-0">Aktif</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">8</h3>
                            <p class="text-muted mb-0">Bakımda</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-danger">6</h3>
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
                            <p class="text-muted mb-0">45 adet</p>
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
                            <p class="text-muted mb-0">32 adet</p>
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
                            <p class="text-muted mb-0">28 adet</p>
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
                            <p class="text-muted mb-0">5 adet</p>
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
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="asset-icon computer me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                                <i class="fas fa-laptop"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">Dell Laptop</div>
                                                <small class="text-muted">Model: Latitude 5520</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Bilgisayar</td>
                                    <td>DL001</td>
                                    <td>Ahmet Yılmaz</td>
                                    <td><span class="badge status-active">Aktif</span></td>
                                    <td>15.01.2023</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="asset-icon furniture me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                                <i class="fas fa-chair"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">Ofis Koltuğu</div>
                                                <small class="text-muted">Model: Ergonomik Pro</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Mobilya</td>
                                    <td>MF002</td>
                                    <td>Fatma Demir</td>
                                    <td><span class="badge status-maintenance">Bakımda</span></td>
                                    <td>20.03.2023</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="asset-icon equipment me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                                <i class="fas fa-tools"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">Projeksiyon</div>
                                                <small class="text-muted">Model: Epson PowerLite</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Ekipman</td>
                                    <td>EQ003</td>
                                    <td>Mehmet Kaya</td>
                                    <td><span class="badge status-active">Aktif</span></td>
                                    <td>10.05.2023</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">Önceki</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Sonraki</a>
                            </li>
                        </ul>
                    </nav>
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
                    <form id="addAssetForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Demirbaş Adı</label>
                                <input type="text" class="form-control" id="assetName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" id="assetCategory" required>
                                    <option value="">Seçiniz</option>
                                    <option value="computer">Bilgisayar</option>
                                    <option value="furniture">Mobilya</option>
                                    <option value="equipment">Ekipman</option>
                                    <option value="vehicle">Araç</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Seri No</label>
                                <input type="text" class="form-control" id="assetSerial" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" id="assetModel">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sorumlu Kişi</label>
                                <select class="form-select" id="assetResponsible" required>
                                    <option value="">Seçiniz</option>
                                    <option value="Ahmet Yılmaz">Ahmet Yılmaz</option>
                                    <option value="Fatma Demir">Fatma Demir</option>
                                    <option value="Mehmet Kaya">Mehmet Kaya</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Satın Alma Tarihi</label>
                                <input type="date" class="form-control" id="assetPurchaseDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Satın Alma Fiyatı</label>
                                <input type="number" class="form-control" id="assetPrice" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" id="assetStatus" required>
                                    <option value="active">Aktif</option>
                                    <option value="maintenance">Bakımda</option>
                                    <option value="retired">Hurdaya Ayrıldı</option>
                                    <option value="lost">Kayıp</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" id="assetDescription" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="saveAsset">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Search functionality
            $('input[type="text"]').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('tbody tr').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(searchTerm));
                });
            });
            
            // Save Asset
            $('#saveAsset').click(function() {
                const assetName = $('#assetName').val();
                const assetCategory = $('#assetCategory').val();
                const assetSerial = $('#assetSerial').val();
                const assetResponsible = $('#assetResponsible').val();
                const assetStatus = $('#assetStatus').val();
                
                if (assetName && assetCategory && assetSerial && assetResponsible && assetStatus) {
                    $('#addAssetModal').modal('hide');
                    $('#addAssetForm')[0].reset();
                    showNotification('Demirbaş başarıyla eklendi!', 'success');
                } else {
                    showNotification('Lütfen tüm zorunlu alanları doldurun!', 'warning');
                }
            });
            
            // Show notification function
            function showNotification(message, type) {
                const alertClass = type === 'success' ? 'alert-success' : 
                                  type === 'warning' ? 'alert-warning' : 
                                  type === 'danger' ? 'alert-danger' : 'alert-info';
                
                const alert = $(`
                    <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                         style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                          type === 'warning' ? 'exclamation-triangle' : 
                                          type === 'danger' ? 'times-circle' : 'info-circle'}"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                $('body').append(alert);
                
                // Auto remove after 5 seconds
                setTimeout(function() {
                    alert.alert('close');
                }, 5000);
            }
            
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
        });
    </script>
</body>
</html>
