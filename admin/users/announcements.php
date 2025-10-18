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
                    <button class="btn btn-primary" onclick="addAnnouncement()">
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
                            <h3 class="text-primary">23</h3>
                            <p class="text-muted mb-0">Toplam Duyuru</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success">18</h3>
                            <p class="text-muted mb-0">Aktif Duyuru</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">3</h3>
                            <p class="text-muted mb-0">Beklemede</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-info">1,456</h3>
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
                                    <td>
                                        <div class="fw-bold">Sistem Bakımı</div>
                                        <small class="text-muted">#001</small>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;">
                                            Sistem bakımı nedeniyle 15 Ocak'ta hizmet kesintisi yaşanacaktır.
                                        </div>
                                    </td>
                                    <td>15.01.2024</td>
                                    <td><span class="badge bg-success">Aktif</span></td>
                                    <td><span class="badge bg-info">245</span></td>
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
                                        <div class="fw-bold">Yeni Özellik</div>
                                        <small class="text-muted">#002</small>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;">
                                            Mobil uygulama güncellemesi yayınlandı. Yeni özellikler eklendi.
                                        </div>
                                    </td>
                                    <td>14.01.2024</td>
                                    <td><span class="badge bg-warning">Beklemede</span></td>
                                    <td><span class="badge bg-info">89</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-success me-1">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Etkinlik Duyurusu</div>
                                        <small class="text-muted">#003</small>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;">
                                            Bu hafta sonu düzenlenecek etkinlik için kayıtlar başlamıştır.
                                        </div>
                                    </td>
                                    <td>13.01.2024</td>
                                    <td><span class="badge bg-success">Aktif</span></td>
                                    <td><span class="badge bg-info">156</span></td>
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

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function addAnnouncement() {
            showAlert('Duyuru ekleme özelliği aktif!', 'success');
            console.log('Adding announcement...');
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
        
        $(document).ready(function() {
            // Search functionality
            $('input[type="text"]').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('tbody tr').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(searchTerm));
                });
            });
            
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
        });
    </script>
</body>
</html>
